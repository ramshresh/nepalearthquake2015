/**
 * Created by User on 6/4/2015.
 */
/*******************ol3 map object*****************/
var overlayGroup = new ol.layer.Group({
    title: 'Overlays',
    layers: []
});

var baseGroup = new ol.layer.Group({
    'title': 'Base',
    layers: []
});


map = new ol.Map({
    target: 'mapBoxMain',
    renderer: 'canvas',
    layers: [
        layerMQ
        //layerImagery,
        //layerOSM
    ],
    controls:
        [
           // new ol.control.LayerSwitcher(),
            new ol.control.Zoom()
        ],
    view:new ol.View({
        center: ol.proj.transform([85.4278, 28.5522], 'EPSG:4326', 'EPSG:3857'),
        zoom: 4
    })
});