//http://stackoverflow.com/questions/6457437/openlayers-cluster-recalculate
/*
 var clustering=new OpenLayers.Strategy.Cluster()
 var vectorlayer = new OpenLayers.Layer.Vector('Vectorlayer', {
 strategies: [clustering]
 });

 //ADD_LOTS_OF_FEATURES_TO_VECTOR_LAYER

 clustering.distance=value;
 clustering.recluster();
 */
OpenLayers.Strategy.Cluster = OpenLayers.Class(OpenLayers.Strategy, {

    /**
     * APIProperty: distance
     * {Integer} Pixel distance between features that should be considered a
     *     single cluster.  Default is 20 pixels.
     */
    distance: 20,

    /**
     * APIProperty: threshold
     * {Integer} Optional threshold below which original features will be
     *     added to the layer instead of clusters.  For example, a threshold
     *     of 3 would mean that any time there are 2 or fewer features in
     *     a cluster, those features will be added directly to the layer instead
     *     of a cluster representing those features.  Default is null (which is
     *     equivalent to 1 - meaning that clusters may contain just one feature).
     */
    threshold: null,

    pendingFeatures: [],

    /**
     * Property: removedFeatures
     * {Array(<OpenLayers.Feature.Vector>)} removedFached features.
     */
    removedFeatures: [],

    /**
     * Property: features
     * {Array(<OpenLayers.Feature.Vector>)} Cached features.
     */
    features: null,


    /**
     * Property: clusters
     * {Array(<OpenLayers.Feature.Vector>)} Calculated clusters.
     */
    clusters: null,

    /**
     * Property: clustering
     * {Boolean} The strategy is currently clustering features.
     */
    clustering: false,

    /**
     * Property: resolution
     * {Float} The resolution (map units per pixel) of the current cluster set.
     */
    resolution: null,

    /**
     * Constructor: OpenLayers.Strategy.Cluster
     * Create a new clustering strategy.
     *
     * Parameters:
     * options - {Object} Optional object whose properties will be set on the
     *     instance.
     */

    /**
     * APIMethod: activate
     * Activate the strategy.  Register any listeners, do appropriate setup.
     *
     * Returns:
     * {Boolean} The strategy was successfully activated.
     */
    activate: function () {
        var activated = OpenLayers.Strategy.prototype.activate.call(this);
        if (activated) {
            this.layer.events.on({
                "beforefeaturesadded": this.cacheFeatures,
                "moveend": this.cluster,
                scope: this
            });
        }
        return activated;
    },

    /**
     * APIMethod: deactivate
     * Deactivate the strategy.  Unregister any listeners, do appropriate
     *     tear-down.
     *
     * Returns:
     * {Boolean} The strategy was successfully deactivated.
     */
    deactivate: function () {
        var deactivated = OpenLayers.Strategy.prototype.deactivate.call(this);
        if (deactivated) {
            this.clearCache();
            this.layer.events.un({
                "beforefeaturesadded": this.cacheFeatures,
                "moveend": this.cluster,
                scope: this
            });
        }
        return deactivated;
    },

    /**
     * Method: cacheFeatures
     * Cache features before they are added to the layer.
     *
     * Parameters:
     * event - {Object} The event that this was listening for.  This will come
     *     with a batch of features to be clustered.
     *
     * Returns:
     * {Boolean} False to stop features from being added to the layer.
     */
    cacheFeatures: function (event) {

        var propagate = true;
        if (!this.clustering) {
            this.clearCache();
            this.features = event.features;
            this.cluster();
            propagate = false;
        }
        return propagate;
    },

    /**
     * Method: clearCache
     * Clear out the cached features.
     */
    clearCache: function () {
        this.features = null;
    },

    /**
     * Method: cluster
     * Cluster features based on some threshold distance.
     *
     * Parameters:
     * event - {Object} The event received when cluster is called as a
     *     result of a moveend event.
     */
    cluster: function (event) {
        if ((!event || event.zoomChanged || (event && event.recluster)) && this.features) {
            var resolution = this.layer.map.getResolution();
            if (resolution != this.resolution || !this.clustersExist() || (event && event.recluster)) {
                this.resolution = resolution;
                var clusters = [];
                var feature, clustered, cluster;
                for (var i = 0; i < this.features.length; ++i) {
                    feature = this.features[i];
                    if (feature.geometry) {
                        clustered = false;
                        for (var j = clusters.length - 1; j >= 0; --j) {
                            cluster = clusters[j];
                            if (this.shouldCluster(cluster, feature)) {
                                this.addToCluster(cluster, feature);
                                clustered = true;
                                break;
                            }
                        }
                        if (!clustered) {
                            clusters.push(this.createCluster(this.features[i]));
                        }
                    }
                }
                this.layer.removeAllFeatures();
                if (clusters.length > 0) {
                    if (this.threshold > 1) {
                        var clone = clusters.slice();
                        clusters = [];
                        var candidate;
                        for (var i = 0, len = clone.length; i < len; ++i) {
                            candidate = clone[i];
                            if (candidate.attributes.count < this.threshold) {
                                Array.prototype.push.apply(clusters, candidate.cluster);
                            } else {
                                clusters.push(candidate);
                            }
                        }
                    }
                    this.clustering = true;
                    // A legitimate feature addition could occur during this
                    // addFeatures call.  For clustering to behave well, features
                    // should be removed from a layer before requesting a new batch.
                    this.layer.addFeatures(clusters);
                    this.clustering = false;
                }
                this.clusters = clusters;
            }
        }
    },

    /**
     * Method: recluster
     * User-callable function to recluster features
     * Useful for instances where a clustering attribute (distance, threshold, ...)
     *     has changed
     */
    recluster: function () {
        var event = {"recluster": true};
        this.cluster(event);
    },

    /**
     * Method: clustersExist
     * Determine whether calculated clusters are already on the layer.
     *
     * Returns:
     * {Boolean} The calculated clusters are already on the layer.
     */
    clustersExist: function () {
        var exist = false;
        if (this.clusters && this.clusters.length > 0 &&
            this.clusters.length == this.layer.features.length) {
            exist = true;
            for (var i = 0; i < this.clusters.length; ++i) {
                if (this.clusters[i] != this.layer.features[i]) {
                    exist = false;
                    break;
                }
            }
        }
        return exist;
    },

    /**
     * Method: shouldCluster
     * Determine whether to include a feature in a given cluster.
     *
     * Parameters:
     * cluster - {<OpenLayers.Feature.Vector>} A cluster.
     * feature - {<OpenLayers.Feature.Vector>} A feature.
     *
     * Returns:
     * {Boolean} The feature should be included in the cluster.
     */
    shouldCluster: function (cluster, feature) {
        var cc = cluster.geometry.getBounds().getCenterLonLat();
        var fc = feature.geometry.getBounds().getCenterLonLat();
        var distance = (
        Math.sqrt(
            Math.pow((cc.lon - fc.lon), 2) + Math.pow((cc.lat - fc.lat), 2)
        ) / this.resolution
        );
        return (distance <= this.distance);
    },

    /**
     * Method: addToCluster
     * Add a feature to a cluster.
     *
     * Parameters:
     * cluster - {<OpenLayers.Feature.Vector>} A cluster.
     * feature - {<OpenLayers.Feature.Vector>} A feature.
     */
    addToCluster: function (cluster, feature) {
        cluster.cluster.push(feature);
        cluster.attributes.count += 1;
    },

    /**
     * Method: createCluster
     * Given a feature, create a cluster.
     *
     * Parameters:
     * feature - {<OpenLayers.Feature.Vector>}
     *
     * Returns:
     * {<OpenLayers.Feature.Vector>} A cluster.
     */
    createCluster: function (feature) {
        var center = feature.geometry.getBounds().getCenterLonLat();
        var cluster = new OpenLayers.Feature.Vector(
            new OpenLayers.Geometry.Point(center.lon, center.lat),
            {count: 1}
        );
        cluster.cluster = [feature];
        return cluster;
    },

    /**
     * Method: addToCachedRemovedFeatures
     * Add a feature to a cluster.
     *
     * Parameters:
     *
     * feature - {<OpenLayers.Feature.Vector>} A feature.
     */
    addToCachedRemovedFeatures: function (feature) {
        var flag = true;
        if (feature) {
            if (this.removedFeatures) {
                var i = 0, rf = this.removedFeatures;
                while ((i < rf.length) && (flag == true)) {
                    flag = flag && (feature.fid != rf[i].fid);
                    i += 1;
                }
                if (flag == true) {
                    this.removedFeatures.push(feature);
                }
            }
        }
    },


    /**
     * Method: getCachedFeaturesByLayerFeature
     * Given an array of features, returns corresponding cachedFeature in cluster strategy
     *
     * Returns:
     * [{<OpenLayers.Feature.Vector>}] An array.
     */
    getCachedFeaturesByLayerFeatures: function (features) {
        var matchedCachedFeatures = [];

        if (this.features) {
            for (var k = 0; k < features.length; k++) {
                for (var i = 0; i < this.features.length; i++) {
                    if (this.features[i].fid == features[k].fid) {
                        matchedCachedFeatures.push(this.features[i]);
                    }
                }
            }
        }
        return matchedCachedFeatures;
    },


    /**
     * Method: removeFeatures
     * Given a features, removes from cluster strategy
     *
     * Returns:
     * undefined
     */
    removeFeature: function (feature, recluster) {

        if (this.features) {
            OpenLayers.Util.removeItem(this.features, feature);
            this.addToCachedRemovedFeatures(feature);
            if (recluster) {
                this.recluster();
            }

        }
    },

    /**
     * Method: removeFeaturesByLayerFeature
     * Given an array of features, removes corresponding cachedFeature in cluster strategy
     * [{<OpenLayers.Feature.Vector>}] An array.
     * Returns:
     * [{<OpenLayers.Feature.Vector>}] An array.
     */
    removeFeaturesByLayerFeatures: function (features, recluster) {
        if (this.features) {
            var selectedFeatures = this.getCachedFeaturesByLayerFeatures(features);
            for (var i = 0; i < selectedFeatures.length; i++) {
                this.removeFeature(selectedFeatures[i], recluster);
            }
        }
        return this.features;
    },

    /**
     * Method: addFeature
     * Given a feature, adds from cluster strategy
     *
     * Returns:
     * undefined
     */
    addFeature: function (feature, recluster) {
        if (feature) {
            this.features.push(feature);
            //console.log(feature);
        }
        if (recluster) {
            this.recluster();
        }
    },

    /**
     * Method: addFeaturesByLayerFeature
     * Given an array of features, adds corresponding cachedFeature in cluster strategy
     * [{<OpenLayers.Feature.Vector>}] An array.
     * Returns:
     * [{<OpenLayers.Feature.Vector>}] An array.
     */
    getPendingFeatures: function (features) {
        this.pendingFeatures.length = 0;
        if (features) {
            for (var i = 0; i < features.length; i++) {
                //console.log(features);
                for (var j = 0; j < this.removedFeatures.length; j++) {
                    if (features[i].fid == this.removedFeatures[j].fid) {
                        console.log('true');
                        this.pendingFeatures.push(this.removedFeatures[j]);
                        OpenLayers.Util.removeItem(this.removedFeatures, this.removedFeatures[j]);
                    } else {
                        console.log('false');
                    }
                }
            }
        }
        return this.pendingFeatures;
    },

    /**
     * Method: addFeaturesByLayerFeature
     * Given an array of features, adds corresponding cachedFeature in cluster strategy
     * [{<OpenLayers.Feature.Vector>}] An array.
     * Returns:
     * [{<OpenLayers.Feature.Vector>}] An array.
     */
    addFeaturesByLayerFeatures: function (features, recluster) {
        if (this.features) {
            for (var i = 0, pf = this.getPendingFeatures(features); i < pf.length; i++) {
                //console.log(pf);
                this.addFeature(pf[i], true);
                OpenLayers.Util.removeItem(this.pendingFeatures, pf[i]);
            }
        }
        return this.features;
    },

    CLASS_NAME: "OpenLayers.Strategy.Cluster"
});

/*
 -----
 USAGE
 -----
 var c=$("#map").data('clusterStrategy') // get cluster strategy

 var s = $('#map').data('map').map.getLayersByName('Disaster Incidents')[0]; // get layer

 var f=s.getFeaturesByAttribute('type', 'building collapsed',true); // get filter feature

 //Attach on featuresAddedHandler where you need to s.getFeaturesByAttribute() and remove the attributes
 s.events.on({
 "featureadded": function(event) {
 var feature = event.feature; console.log('feature added!');f=s.getFeaturesByAttribute('type', 'building collapsed',true);console.log(feature);c.removeFeaturesByLayerFeatures(f);
 }
 });
 */