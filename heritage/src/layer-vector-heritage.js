/**
 * Created by User on 6/7/2015.
 */


// format used to parse WFS GetFeature responses
var geoJSONFormat = new ol.format.GeoJSON();

var vectorSourceHeritage = new ol.source.Vector({
    loader: function(extent, resolution, projection) {
        var url =  'http://118.91.160.230:8080/geoserver/dmis/ows?' +
            'service=WFS' +
            '&version=1.0.0' +
            '&request=GetFeature' +
            '&typeName=dmis:report_item_incident' +
            '&srsname=EPSG:3857' +
            '&outputformat=text/javascript' +
            '&format_options=callback:loadFeatures' +
            '&bbox=' + extent.join(',');
        // use jsonp: false to prevent jQuery from adding the "callback"
        // parameter to the URL
        $.ajax({url: url, dataType: 'jsonp', jsonp: false});
    }
});

var vectorSource = new ol.source.ServerVector({
    format: new ol.format.GeoJSON({}),
    loader: function(extent, resolution, projection) {
        // var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:report_item&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
        var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:report_item_incident&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
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
    alert('loading');
    vectorSource.addFeatures(vectorSource.readFeatures(response));
};
window.loadFeatures = loadFeatures;

console.log(vector);

