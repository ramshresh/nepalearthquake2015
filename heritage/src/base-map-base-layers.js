
/**
 * Created by User on 6/4/2015.
 */
// Create layers instances
var layerOSM = new ol.layer.Tile({
    source: new ol.source.OSM(),
    name: 'OpenStreetMap'
});

var layerMQ = new ol.layer.Tile({
    source: new ol.source.MapQuest({
        layer: 'osm'
    }),
    name: 'MapQuest'
});

var key = 'Ak-dzM4wZjSqTlzveKz5u0d4IQ4bRzVI309GxmkgSVr1ewS6iPSrOvOKhA-CJlm3';

var layerImagery = new ol.layer.Tile({
    source: new ol.source.BingMaps({
        key: key,
        imagerySet: 'Aerial'
    })
});

var baseLayers = {
    Names:['layerOSM','layerMQ','layerImagery'],
    olLayer:[layerOSM,layerMQ,layerImagery]
};

/*
var baseGroup = new ol.layer.Group({
    'title': 'Base',
    layers: []
});
baseGroup.getLayers().push(layerMQ);
*/
