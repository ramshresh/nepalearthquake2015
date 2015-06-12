<DOCTYPE html>
    <html>

    <head>
        <title>Nepal Eq-2015 </title>
        <script src="lib/jquery.js"></script>
        <link rel="stylesheet" href="lib/ol.css">
        <link rel="stylesheet" href="lib/ol3-layerswitcher.css">
        <link rel="stylesheet" href="lib/ol3-popup.css">
        <!--<link rel="stylesheet" href="lib/bootstrap.min.css">-->
        <script type='text/javascript' src='lib/ol-debug.js'></script>
        <!--<script type='text/javascript' src='lib/bootstrap.min.js'></script>-->
        <script type='text/javascript' src='lib/ol3-layerswitcher.js'></script>

        <script src="lib/ol3-popup.js"></script>
        <style>
            .ol-popup-closer:after {
                content: "[x]";
                color: red;
                font-size: 16px;
            }
            .ol-popup {
                display: none;
                position: absolute;
                background-color: white;
                padding: 15px;
                border: 1px solid rgb(57, 52, 86);
            ;
                bottom: 12px;
                left: -50px;
                height: auto;
                width: 250px;
                max-height: auto;
            }
            .ol-popup-content {
                width: 250px;
                height: auto;
                max-height: 300px;
                max-width: 230px;
                overflow-y:auto;
            }
            .ol-popup:before {
                border-top-color: rgb(57, 52, 86);
                border-width: 11px;
                left: 48px;
                margin-left: -11px;
            }
        </style>
        <script>
            /* Defining basic Map with OSM as basemap*/
            $(document).ready(function() {

                /*******************Overlay Group*****************/
                var overlayGroup = new ol.layer.Group({
                    title: 'Overlays',
                    layers: []
                });

                var baseGroup = new ol.layer.Group({
                    'title': 'Base',
                    layers: []
                })
                var vectorSource = new ol.source.ServerVector({
                    format: new ol.format.GeoJSON({}),
                    loader: function(extent, resolution, projection) {
                        var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:report_item&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
                        $.ajax({
                            url: url,
                            type: 'GET',
                            dataType: 'jsonp',
                            success: function(data) {},
                            error: function(data) {},
                            timeout: 30000 // 1 minute timeout
                        });
                    },
                    strategy: ol.loadingstrategy.createTile(new ol.tilegrid.XYZ({
                        maxZoom: 19
                    }))
                });



                var styleCache = {};
                var vector = new ol.layer.Vector({

                    source: new ol.source.Cluster({
                        distance: 40,
                        source: vectorSource
                    }),
                    //style: styleFunction
                    style: function(feature, resolution) {
                        var size = feature.get('features').length;
                        if (size === 1) {

                            style = [new ol.style.Style({
                                image: new ol.style.Icon(({
                                    src: 'http://vignette2.wikia.nocookie.net/fallout/images/7/73/Icon_damage.png/revision/latest?cb=20091010164957',
                                    //  src: src_icon(),
                                    offset: [1, 1],
                                    // size: [32,32],
                                    scale: 0.1
                                }))
                            })]
                        } else {
                            // styleCache[size] = style;
                            style = styleCache[size];
                            if (!style) {
                                style = [
                                    new ol.style.Style({
                                        image: new ol.style.Circle({
                                            radius: 17,
                                            stroke: new ol.style.Stroke({
                                                color: '#ffcc33'
                                            }),
                                            fill: new ol.style.Fill({
                                                color: '#000000'
                                            })
                                        }),
                                        text: new ol.style.Text({
                                            textAlign: "center",
                                            textBaseline: "middle",
                                            font: 'Normal 12px Arial',
                                            text: size.toString(),
                                            fill: new ol.style.Fill({
                                                color: '#ffcc33'
                                            }),
                                            stroke: new ol.style.Stroke({
                                                color: '#000000',
                                                width: 1
                                            }),
                                            offsetX: 0,
                                            offsetY: 0,
                                            rotation: 0
                                        })
                                    })
                                ];
                                styleCache[size] = style;
                            }
                        }

                        return style;
                    }
                });
                window.vector = vector;
                var loadFeatures = function(response) {
                    vectorSource.addFeatures(vectorSource.readFeatures(response));
                };
                window.loadFeatures = loadFeatures;
                overlayGroup.getLayers().push(vector);
                /*end*/

                var nepal_vdc = new ol.layer.Tile({
                    title: 'Nepal VDC',
                    name: 'Nepal VDC',
                    type: 'overlay',
                    source: new ol.source.TileWMS(
                        ({
                            url: 'http://118.91.160.230:8080/geoserver/wms',
                            params: {
                                'LAYERS': 'dmis:nepal_vdc',
                                'TILED': true
                            }
                        }))
                });

                //overlayGroup.getLayers().push(nepal_vdc);

                var view = new ol.View({

                    center: ol.proj.transform([87.2345, 29.3535], 'EPSG:4326', 'EPSG:3857'),
                    zoom: 2
                });
                var osm = new ol.layer.Tile({
                    title: 'OSM',
                    type: 'base',
                    source: new ol.source.OSM()
                });

                var key = 'Ak-dzM4wZjSqTlzveKz5u0d4IQ4bRzVI309GxmkgSVr1ewS6iPSrOvOKhA-CJlm3';

                var imagery = new ol.layer.Tile({
                    source: new ol.source.BingMaps({
                        key: key,
                        imagerySet: 'Aerial'
                    })
                });

                baseGroup.getLayers().push(osm);
                baseGroup.getLayers().push(imagery);


                /*******************ol3 map object*****************/
                map = new ol.Map({
                    target: 'map',
                    renderer: 'canvas',
                    layers: [
                        baseGroup,
                        overlayGroup
                    ],
                    controls:
                    //ol.control.defaults({
                    //    attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
                    //        collapsible: false
                    //    })
                    // }).extend(
                        [
                            new ol.control.LayerSwitcher(),
                            new ol.control.Zoom(),
                            // new ol.control.ZoomToExtent({
                            //extent: [-180,-90,180,90]
                            //    extent: [8858052.801082317, 2602714.8048996064, 10081045.253645137, 3825707.2574624266]
                            // })
                        ],
                    //),

                    view: view
                });

                var radius = 100;
                $(document).keydown(function(evt) {
                    if (evt.which === 38) {
                        radius = Math.min(radius + 5, 150);
                        map.render();
                    } else if (evt.which === 40) {
                        radius = Math.max(radius - 5, 25);
                        map.render();
                    }
                });

                // get the pixel position with every move
                var mousePosition = null;
                $(map.getViewport()).on('mousemove', function(evt) {
                    mousePosition = map.getEventPixel(evt.originalEvent);
                    map.render();
                }).on('mouseout', function() {
                    mousePosition = null;
                    map.render();
                });

                // before rendering the layer, do some clipping
                imagery.on('precompose', function(event) {
                    var ctx = event.context;
                    var pixelRatio = event.frameState.pixelRatio;
                    ctx.save();
                    ctx.beginPath();
                    if (mousePosition) {
                        // only show a circle around the mouse
                        ctx.arc(mousePosition[0] * pixelRatio, mousePosition[1] * pixelRatio,
                            radius * pixelRatio, 0, 2 * Math.PI);
                        ctx.lineWidth = 5 * pixelRatio;
                        ctx.strokeStyle = 'rgba(0,0,0,0.5)';
                        ctx.stroke();
                    }
                    ctx.clip();
                });

                // after rendering the layer, restore the canvas context
                imagery.on('postcompose', function(event) {
                    var ctx = event.context;
                    ctx.restore();
                });


                /***********function for counting unique values in an array**********/
                function unique_count(arr) {
                    var a = [],
                        b = [],
                        prev;

                    arr.sort();
                    for (var i = 0; i < arr.length; i++) {
                        if (arr[i] !== prev) {
                            a.push(arr[i]);
                            b.push(1);
                        } else {
                            b[b.length - 1]++;
                        }
                        prev = arr[i];
                    }

                    return [a, b];
                }
                /*********************/


                var popup = new ol.Overlay.Popup();
                map.addOverlay(popup);
                var image = function(id) {
                    $.ajax({
                        url: 'http://118.91.160.230/girc/dmis/api/rapid_assessment/report-items',
                        data: {
                            expand: 'galleryImages',
                            id: id
                        },
                        success: function(data) {
                            var src;
                            if (data) {
                                console.log(data);
                                if (data[0]) {
                                    if (data[0].galleryImages[0]) {
                                        if (data[0].galleryImages[0].src) {
                                            src = data[0].galleryImages[0].src;
                                            console.log("src");
                                            console.log(src);
                                            console.log("src");
                                        }
                                    }
                                }
                            } else {
                                console.log('no photo');
                            }

                            if (src) {
                                img_src = '<img src="http://118.91.160.230' + src + '" alt="" style="height:auto;width:200px;">';
                                // console.log(img_src);
                            } else {
                                img_src = '';
                            }
                            //  popup.show(evt.coordinate, popupContent);

                        }
                    });
                    //console.log(img_src);
                    //console.log(img(id));
                    return img_src;
                }
                var highlight;
                var displayFeatureInfo = function(pixel) {

                    var feature = map.forEachFeatureAtPixel(pixel, function(feature, layer) {
                        return feature;
                    });

                    var info = document.getElementById('info');
                    try {
                        var size = feature.get('features').length;
                    } catch (e) {}
                    //  console.log(size);
                    if (feature) {
                        coor_feature = feature.values_.geometry.flatCoordinates;
                        if (size == 1) {
                            console.log(feature);
                            fid = feature.values_.features[0].id_;
                            //id = fid.split('-')[1];
                            id = fid.split('.')[1];
                            // popup.show(coor_feature, '<h4>' + feature.values_.features[0].values_.item_name + '</h4><p>Human Casulty :</p> ' + feature.values_.features[0].values_.magnitude + image(id));
                            popup.show(coor_feature, '<h4>' + feature.values_.features[0].values_.item_name + '<hr>' + image(id));
                            // popup.show(coor_feature, '<h4>' + feature.values_.features[0].values_.item_name);
                        } else {

                            var text = '';
                            var items_array_incident = [];
                            var items_array_need = [];
                            var items_array_impact = [];
                            var items_array_supplied = [];

                            var mag_array_incident = [];
                            var mag_array_need = [];
                            var mag_array_impact = [];
                            var mag_array_supplied = [];

                            $.each(feature.values_.features, function(index, value) {
                                if (value.values_.type == "incident") {
                                    items_array_incident.push(value.values_.class_name);
                                } else if (value.values_.type == "impact") {
                                    items_array_impact.push(value.values_.item_name);
                                    mag_array_impact.push(value.values_.magnitude);
                                } else {
                                    items_array_need.push(value.values_.item_name);
                                    mag_array_need.push(value.values_.magnitude);
                                }
                            });



                            var mapping_impact = {};
                            var mapping_need = {};

                            var incidents = unique_count(items_array_incident);
                            var incident_unique = incidents[0];
                            var incident_counts = incidents[1];

                            var popup_content_incident = '<strong>Report Details</strong><hr><strong>Incident</strong><table class="table">';
                            for (i = 0; i < incidents.length + 1; i++) {
                                if( typeof incident_unique[i]==="undefined"){

                                }
                                else{
                                    popup_content_incident += '<tr><td>' + incident_counts[i] + '</td><td>house(s)</td><td>' + incident_unique[i] + '</td></tr>';
                                }
                            }

                            //impact
                            for (var i = 0, count = items_array_impact.length; i < count; i++) {
                                mapping_impact[items_array_impact[i]] = (mapping_impact[items_array_impact[i]] || 0) + mag_array_impact[i];
                            }
                            var popup_content_impact = '</table><hr><strong>Impact</strong><table class="table">';

                            for (var item in mapping_impact) {
                                popup_content_impact += '<tr><td>' + item + '</td><td>:' + mapping_impact[item] + '</td></tr>';
                                //count_items_people = item + ': ' + mapping[item];
                            }

                            //need
                            for (var i = 0, count = items_array_need.length; i < count; i++) {
                                mapping_need[items_array_need[i]] = (mapping_need[items_array_need[i]] || 0) + mag_array_need[i];
                            }
                            var popup_content_need = '</table><hr><strong>Need<strong><table class="table">';

                            for (var item in mapping_need) {
                                popup_content_need += '<tr><td>' + item + '</td><td>for ' + mapping_need[item] + ' people </td></tr>';
                                //count_items_people = item + ': ' + mapping[item];
                            }

                            popup_content_need +='</table>';
                            popup.show(coor_feature, popup_content_incident + popup_content_impact + popup_content_need);

                        }
                    } else {
                        $(".ol-popup").hide();
                    }
                };

                map.on('click', function(evt) {
                    displayFeatureInfo(evt.pixel);
                });



            });
        </script>
    </head>
    <body>
    <div id="map" style="height:100%;width:100%;"></div>
    </body>
    </html>