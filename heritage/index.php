<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 6/4/2015
 * Time: 2:25 PM
 */
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Kathmandu Municipality Heritage Profile</title>
    <link rel="stylesheet" href="assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/ol3/ol.css">
        <link rel="stylesheet" href="assets/ol3/ol3-layerswitcher.css">
        <link rel="stylesheet" href="assets/ol3/ol3-popup.css">
    <style>
        .map-3col-center {
            border-color: lightseagreen;
            border-style: groove;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- {{{START: Top row    -->
    <div class="row">
        <!--START: select district and vdc -->
        <!--Requires src/select-vdc-dropdown.js-->
        <div class="form-group" style="margin-top:25px !important;">
            <div class="col-sm-6">
                <!--							<select name="test" class="selectboxit">-->
                <select id="district_name" name="district_name" class="form-control">
                    <option value="" class="display:none">Select District</option>
                </select>
            </div>
            <div class="col-sm-6">
                <!--							<select name="test" class="selectboxit">-->
                <select id="vdc_name" name="vdc_name" class="form-control">
                    <option value="" class="display:none;">Select VDC/Municipality</option>
                </select>
            </div>
        </div>
        <!--END: select district and vdc -->
    </div>
    <!-- {{{End: Top row    -->

    <!-- {{{START: Left side Column    -->
    <div class="col-lg-3">
        <!--END: select district and vdc -->
        <div id="mapLegendAndLayers" class="row">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active"><a href="#layers" role="tab" data-toggle="tab">Layers</a></li>
                        <li><a href="#legend" role="tab" data-toggle="tab">Legend</a></li>
                    </ul>

                </div>
                <!-- Tab panes + Panel body -->
                <div class="panel-body tab-content">
                    <div class="tab-pane active" id="layers">Layers</div>
                    <div class="tab-pane" id="legend">Legend</div>
                </div>
            </div>
        </div>
    </div>
    <!-- END: Left Side Column}}}   -->

    <!-- {{{START: center Column    -->
    <div class="col-lg-6">
        <!--{{{START: base-map -->
        <div id="mapBoxMain" class="map-3col-center row"></div>
        <!--END: base-map }}}-->
    </div>
    <!-- END: Center Column}}}   -->

    <!-- {{{START: Right Side Column    -->
    <div class="col-lg-3">
        <!--{{{START: chartBox        -->
        <div class="row" id="chartBox">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active"><a href="#chart_1" role="tab" data-toggle="tab">Chart 1</a></li>
                        <li><a href="#chart_2" role="tab" data-toggle="tab">Chart 2</a></li>
                    </ul>

                </div>
                <!-- Tab panes + Panel body -->
                <div class="panel-body tab-content">
                    <div class="tab-pane active" id="chart_1">Chart 1</div>
                    <div class="tab-pane" id="chart_2">Chart 2</div>
                </div>
            </div>
        </div>
        <!--END: chartBox}}}        -->
    </div>
    <!-- END: Right Side Column}}}   -->

</div>

<script src="assets/jquery/jquery.min.js"></script>
<script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="assets/ol3/ol-debug.js"></script>
<script src="assets/ol3/ol3-layerswitcher.js"></script>
<script src="assets/ol3/ol3-popup.js"></script>
<script src="src/adminExtents.js"></script>
<script src="src/select-vdc-dropdown.js"></script>
<!-- requires src/adminExtents.js-->
<!--<script src="src/base-map-base-layers.js"></script>-->
<!--<script src="src/base-map.js"></script>-->
<!--<script src="src/index-map-box.js"></script>-->
<!--<script src="src/layer-vector-heritage.js"></script>-->
<!--<script src="src/index-pos-ready.js"></script>-->

<script>
    $(document).ready(function(){
        //var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:report_item_incident&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
        // format used to parse WFS GetFeature responses
        var geojsonFormat = new ol.format.GeoJSON();

        var vectorSource = new ol.source.Vector({
            loader: function(extent, resolution, projection) {
                /*var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?' +
                    'service=WFS' +
                    '&version=1.1.0' +
                    '&request=GetFeature' +
                    '&typename=dmis:heritage' +
                    '&outputFormat=text/javascript' +
                    '&format_options=callback:loadFeatures' +
                    '&srsname=EPSG:3857' +
                    '&bbox=' + extent.join(',') + ',EPSG:3857';*/
                var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:heritage&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
                // use jsonp: false to prevent jQuery from adding the "callback"
                // parameter to the URL
                $.ajax({url: url, dataType: 'jsonp', jsonp: false});
            },
            strategy: ol.loadingstrategy.tile(new ol.tilegrid.XYZ({
                maxZoom: 19
            }))
        });

// the global function whose name is specified in the URL of JSONP WFS
// GetFeature requests
        var loadFeatures = function(response) {
            vectorSource.addFeatures(geojsonFormat.readFeatures(response));
        };

        window.loadFeatures=loadFeatures;
        var vector = new ol.layer.Vector({
            source: vectorSource,
            style: new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: 'rgba(0, 0, 255, 1.0)',
                    width: 2
                })
            })
        });

        var raster = new ol.layer.Tile({
            source: new ol.source.BingMaps({
                imagerySet: 'Aerial',
                key: 'Ak-dzM4wZjSqTlzveKz5u0d4IQ4bRzVI309GxmkgSVr1ewS6iPSrOvOKhA-CJlm3'
            })
        });

        var map = new ol.Map({
            layers: [raster, vector],
            target: document.getElementById('mapBoxMain'),
            view: new ol.View({
                center: [8908887.277395891, 5381918.072437216],
                maxZoom: 19,
                zoom: 2
            })
        });
    });
</script>

</body>
</html>
