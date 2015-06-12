OpenLayers.Strategy.Cluster.pendingFeatures = [];
OpenLayers.Strategy.Cluster.removedFeatures = [];
OpenLayers.Strategy.Cluster.cluster = function (event) {
    if ((!event || event.zoomChanged || (event && event.recluster)) && this.features) {
        if (this.removedFeatures) {
            this.removeFeaturesByLayerFeatures(this.removedFeatures, true);
        }
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
};