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
    <title>Kathmandu Municipality Heritage Profile - Leaflet</title>
    <link rel="stylesheet" href="assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css"/>

    <!--    Marker Cluster-->
    <link rel="stylesheet" href="https://raw.githubusercontent.com/Leaflet/Leaflet.markercluster/master/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="clusterpies.css"/>

    <style>
        .editor-modal {
            position: absolute;
            top: 20px;
            left: 30px;
        }

        body {
            padding: 0;
            margin: 0;
        }

        .map-3col-center {
            height: 550px;
            width: 100%;
            border-color: lightseagreen;
            border-style: groove;
        }

        .leaflet-popup-content {
            width: 500px;
        }

        .carousel-heritage-photo {
            width: 100%;
            height: 225px;
            max-height: 225px;
        }

        .popup-table {
            position: relative;
            width: inherit;
        }
    </style>
</head>
<body>

<div id="mapBoxMain" class="map-3col-center row"></div>

<script src="assets/jquery/jquery.min.js"></script>
<script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js"></script>

<!--Marker Cluster-->
<script src="http://leaflet.github.io/Leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>


<script>
    $(document).ready(function () {


        //var url = 'http://118.91.160.230:8080/geoserver/dmis/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=dmis:heritage&srsname=EPSG:3857&outputformat=text/javascript&format_options=callback:loadFeatures&bbox=' + extent.join(',');
        var map = L.map('mapBoxMain').setView([27.5, 85.4], 8);

        osmLayer = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            maxZoom: 24,
            id: 'your.mapbox.project.id',
            accessToken: 'your.mapbox.public.access.token'
        });
        osmLayer.addTo(map);


        var owsrootUrl = 'http://118.91.160.230:8080/geoserver/dmis/ows';

        var defaultParameters = {
            service: 'WFS',
            version: '2.0',
            request: 'GetFeature',
            typeName: 'dmis:heritage',
            outputFormat: 'text/javascript',
            format_options: 'callback:getJson',
            SrsName: 'EPSG:4326'
        };
        var parameters = L.Util.extend(defaultParameters);
        var URL = owsrootUrl + L.Util.getParamString(parameters);

        var layerWFSHeritage = null;
        var heritageDetails = null;
        var heritageUploadedBy = {};
        var heritagePhotos = [];
        var heritagePopupContent = '<div id="popup_container-heritage" class="row"></div>'
        var heritageHighChartsSeries_pie = [];
        var heritageLegend = {
            damage_type: {
                values: ["no damage", "minor crack", "partial damage", "full damage"],
                color: ["#40d47e", "#f1c40f", "#d35400", "#e74c3c"]
            }
        };

        function getLegendColor(legendData,attribute,value) {
            var index = legendData[attribute].values.indexOf(value);
            return legendData[attribute].color[index];
        }

        function getPointToLayerFunction(legendData,attribute){
            return function (feature, latlng) {
                return L.circleMarker(latlng, {
                    radius: 5,
                    fillColor: getLegendColor(legendData,attribute,feature.properties[attribute]),
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                });
            }
        }


        var ajax = $.ajax({
            url: URL,
            dataType: 'jsonp',
            jsonpCallback: 'getJson',
            success: function (response) {
                layerWFSHeritage = L.geoJson(response, {
                    pointToLayer:getPointToLayerFunction(heritageLegend,'damage_type'),
                    onEachFeature: function (feature, layer) {
                        popupOptions = {maxWidth: 200};
                        /*popupContent =
                         '<div id="popup_container-heritage" class="row">' +
                         '<strong>Inventory ID : </strong>'+feature.properties.inventory_id+
                         '<br>'+
                         '<strong>Damage Type : </strong>'+feature.properties.damage_type+
                         '<div id="popup_user-heritage"></div>'+
                         '</div>';*/

                        layer.on('click', function (e) {
                            var feature = layer.feature;
                            var id = feature.id.split('.')[1];

                            var heritageDetailAjaxParams = {
                                url: 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages',
                                data: {
                                    id: id,
                                    fields: 'id',
                                    expand: 'galleryImages,user'
                                }
                            };

                            setHeritageDetails(heritageDetailAjaxParams.url, heritageDetailAjaxParams.data)
                                .done(function (data) {
                                    heritagePhotos = [];
                                    console.log('getHeritageDetails');
                                    console.log(heritageDetails);
                                    console.log('getHeritageDetails');
                                    if (heritageDetails) {
                                        $.each(heritageDetails, function (heritageDetails_index, heritageDetail) {
                                            // Before Earthquake GalleryImages
                                            if (heritageDetail.galleryImages) {
                                                $.each(heritageDetail.galleryImages, function (galleryImages_index, galleryImage) {
                                                    var links = {};
                                                    if (galleryImage.versions) {
                                                        $.each(JSON.parse(galleryImage.versions), function (versions_index, version) {
                                                            links[version] = galleryImage.route + '/' + galleryImage.ownerId + '/' + galleryImage.id + '/' + version + '.' + galleryImage.extension;
                                                        });
                                                    }
                                                    var photo = {};
                                                    photo['name'] = galleryImage.name;
                                                    photo['description'] = galleryImage.description;
                                                    photo['latitude'] = galleryImage.latitude;
                                                    photo['longitude'] = galleryImage.longitude;
                                                    photo['versions'] = galleryImage.versions;
                                                    photo['links'] = links;
                                                    heritagePhotos.push(photo);
                                                });
                                            }

                                            // User
                                            if (heritageDetail.user) {
                                                var uploadedByUser = heritageDetail.user;
                                                var username = {};
                                                if (uploadedByUser.full_name) {
                                                    username = uploadedByUser.full_name;
                                                } else if (uploadedByUser.username) {
                                                    username = uploadedByUser.username;
                                                } else if (uploadedByUser.email) {
                                                    username = uploadedByUser.email
                                                }
                                                heritageUploadedBy['username'] = username;
                                            }

                                        });
                                    }

                                    console.log('heritagePhotos');
                                    console.log(heritagePhotos);
                                    console.log('heritagePhotos');

                                    console.log('heritageUploadedBy');
                                    console.log(heritageUploadedBy);
                                    console.log('heritageUploadedBy');
                                    $(heritagePopupContent).empty();

                                    heritagePopupContent =

                                        '<p><strong>Inventory Id</strong>' + feature.properties.inventory_id + '</p>' +
                                        '<p><strong>Damage Type</strong>' + feature.properties.damage_type + '</p>' +
                                        '<p><strong>Uploaded By</strong>' + heritageUploadedBy.username + '</p>';

                                    $('#popup_container-heritage').empty();
                                    $('#popup_container-heritage').html(heritagePopupContent);

                                    setHeritagePhotoCarousel('#photoCarousel_heritage', heritagePhotos, 'preview', 'http://118.91.160.230/girc/dmis');
                                }
                            );

                            popupSetImage(id, '#selected_photo');
                            popupSetUser(id, '#popup_user-heritage');
                        });

                        layer.bindPopup(heritagePopupContent, popupOptions);
                    }
                }).addTo(map);

               /* var nepal_vdc = L.tileLayer.wms("http://118.91.160.230:8080/geoserver/dmis/ows", {
                    layers: 'dmis:nepal_vdcs',
                    format: 'image/png',
                    transparent: true,
                    version: '1.1.0',
                    opacity: 0.5
                    //attribution: "myattribution"
                });
                nepal_vdc.addTo(map);
*/
                baseMaps = {
                    "OSM Map": osmLayer
                };
                overlayMaps = {
                    //"VDC": nepal_vdc,
                    "Heritage": layerWFSHeritage
                };
                L.control.layers(baseMaps, overlayMaps).addTo(map);

                map.fitBounds(layerWFSHeritage.getBounds());
            }
        });


        getHighChartSeries = function (chartType, url, queryParam) {
            return $.ajax({
                url: url,
                data: queryParam,
                success: function () {
                    console.log('success getHighChartSeries()');
                },
                error: function () {
                    console.log('error getHighChartSeries()');
                }
            });
        };
        getHeritageDetails = function (url, queryData) {
            var queryData = (queryData) ? queryData : {expand: 'galleryImages,user'};
            var url = (url) ? url : 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages';
            return $.ajax({
                url: url,
                //url: 'http://118.91.160.230/girc/dmis/api/rapid_assessment/report-items/'+id+'/galleries',
                data: queryData,
                cache: true,
                error: function () {
                    console.log('an error occured: for ' + JSON.stringify({url: url, data: queryData}));
                },
                success: function (heritages) {
                    /*if(heritages){
                     $.each(heritages, function(index_heritage, heritage){
                     if(heritage.galleryImages){
                     $.each(heritage.galleryImages, function(index_gallery, galleryImage){
                     console.log(galleryImage.route+'/'+galleryImage.ownerId+'/'+galleryImage.id+'/'+'small'+'.'+galleryImage.extension);
                     });
                     }
                     });
                     }*/
                }
            });
        };
        setHeritageDetails = function (url, queryData) {
            return getHeritageDetails(url, queryData).done(function (data) {
                heritageDetails = data;
            });
        };
        resetHeritageDetails = function () {
            heritageDetails = undefined;
        };
        setHeritagePhotoCarousel = function (jquerySelector, photoData, photoVersion, webRoot) {
            var webRoot = (webRoot) ? webRoot : 'http://118.91.160.230/girc/dmis';
            var indicators = $(jquerySelector).children('.carousel-indicators');
            var inner = $(jquerySelector).children('.carousel-inner');

            $(indicators).empty();
            $(inner).empty();

            var indicatorHtml = '';
            var itemHtml = '';
            $.each(photoData, function (index, photo) {

                if (index == 0) {
                    indicatorHtml += '<li data-target="' + jquerySelector + '" data-slide-to="' + index + '" class="active"></li>';
                    itemHtml += '<div class="item active"><img class="carousel-heritage-photo" src="' + webRoot + '/' + photo.links[photoVersion] + '" alt="Gallery Image"></div>';
                } else {
                    indicatorHtml += '<li data-target="' + jquerySelector + '" data-slide-to="' + index + '" ></li>';
                    itemHtml += '<div class="item"><img class="carousel-heritage-photo" src="' + webRoot + '/' + photo.links[photoVersion] + '" alt="Gallery Image"></div>';
                }


            });
            $(indicators).append($(indicatorHtml));
            $(inner).append($(itemHtml));


            console.log('photoData');
            console.log(photoData);
            console.log('photoData');

        }
        popupSetImage = function (id, imgContainer) {
            $.ajax({
                url: 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages',
                //url: 'http://118.91.160.230/girc/dmis/api/rapid_assessment/report-items/'+id+'/galleries',
                data: {
                    expand: 'galleryImages',
                    id: id
                },
                cache: true,
                success: function (heritages) {
                    if (heritages) {
                        $.each(heritages, function (index_heritage, heritage) {
                            if (heritage.galleryImages) {
                                $.each(heritage.galleryImages, function (index_gallery, galleryImage) {
                                    console.log(galleryImage.route + '/' + galleryImage.ownerId + '/' + galleryImage.id + '/' + 'small' + '.' + galleryImage.extension);
                                });
                            }
                        });
                    }
                    var src;
                    if (heritages) {



                        /*
                         if (data[0]) {
                         if(data[0].galleryImages){
                         // gallery images
                         if (data[0].galleryImages[0]) {
                         if (data[0].galleryImages[0].src) {
                         src = data[0].galleryImages[0].src;
                         }
                         }
                         }
                         }

                         if (src) {
                         img_src = '<img class="popup-image-external" src="http://118.91.160.230' + src + '" alt="" style="height:auto;width:200px;">';
                         // console.log(img_src);
                         } else {
                         img_src = '';
                         }
                         //  popup.show(evt.coordinate, popupContent);

                         $(imgContainer).empty();
                         $(imgContainer).append(img_src);
                         */
                    } else {

                        // console.log('no photo');
                    }
                }
            });
        }
        popupSetUser = function (id, userContainer) {
            $.ajax({
                url: 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages',
                //url: 'http://118.91.160.230/girc/dmis/api/rapid_assessment/report-items/'+id+'/galleries',
                data: {
                    expand: 'user',
                    id: id
                },
                cache: true,
                success: function (data) {

                    var username;
                    var user;
                    if (data) {
                        if (data[0]) {
                            if (data[0].user) {
                                // user
                                user = data[0].user

                            }
                        }

                        if (user) {
                            username_value = (user.username) ? user.username : user.email;
                            username = '<strong>uploaded by:' + username_value + '</strong>';
                            // console.log(img_src);
                        } else {
                            username = '';
                        }
                        //  popup.show(evt.coordinate, popupContent);

                        $(userContainer).append(username);

                    } else {
                        console.log('no user detail');
                    }
                }
            });
        }


        getHeritageUserContribution = function (url, queryData) {
            //#heritageUserContributionTable_count
            var queryData = (queryData) ? queryData : {};
            var url = (url) ? url : 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages/unique-users';
            return $.ajax({
                url: url,
                //url: 'http://118.91.160.230/girc/dmis/api/rapid_assessment/report-items/'+id+'/galleries',
                data: queryData,
                cache: true,
                error: function () {
                    console.log('an error occured: for ' + JSON.stringify({url: url, data: queryData}));
                },
                success: function (data) {
                    console.log('SUCCESS for ' + JSON.stringify({url: url, data: queryData}));
                }
            });
        }

        setHeritageUserContribution = function (jquerySelector, url, queryData, legendData) {
            getHeritageUserContribution(url, queryData, legendData).done(
                function (data) {
                    console.log('done');
                    console.log(data);
                    var heading = '<td>Email</td><td>Total Count</td>'
                    var rows = '';
                    var htmlUserCount1 = '<table class="table table-bordered table-striped table-condensed">' +
                        '<thead>';

                    var htmlUserCount2 = '</thead>' +
                        '<tbody>';

                    var htmlUserCount3 = '</tbody>' +
                        '</table>';

                    $.each(data.keys, function (idx, key) {
                        rows += '<tr><td>' + data.email[key] + '</td><td>' + data.count[key] + '</td></tr>';
                    });
                    var htmlUserCount = htmlUserCount1 + heading + htmlUserCount2 + rows + htmlUserCount3;
                    $('#heritageUserContributionTable_count').empty();
                    $('#heritageUserContributionTable_count').html(htmlUserCount);

                }
            );
        };
       /* getHighChartSeries('pie', 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages/unique/' + 'damage_type', {}, heritageLegend)
            .done(function (data) {
                var hcItems = [];
                var hcColors = [];
                $.each(data, function (idx, item) {
                    var hcItem = [item.value, item.count];
                    hcItems.push(hcItem);


                    //var legIdx = heritageLegend['damage_type'].values.indexOf(item.value);
                    //var hcColor = heritageLegend['damage_type'].color[legIdx];
                    var hcColor=getLegendColor(heritageLegend,'damage_type',item.value);
                    hcColors.push(hcColor);

                    console.log('highchart data.item');
                    console.log(item);
                    console.log('highchart data.item');
                });
                heritageHighChartsSeries_pie = hcItems;
                console.log('hcItems');
                console.log(hcItems);
                console.log('hcItems');

                seriesData = hcItems;

                // Build the chart
                $('#chartContainerPie_Heritage').highcharts({
                    colors: hcColors,
                    chart: {
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: 'Heritage Survey of Kathmandu Valley after Nepal Earthquake 2015'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: false
                            },
                            showInLegend: true
                        }
                    },
                    series: [{
                        type: 'pie',
                        name: 'Damage Type',
                        data: seriesData
                        *//*data: [
                         ['Firefox',   45.0],
                         ['IE',       26.8],
                         {
                         name: 'Chrome',
                         y: 12.8,
                         sliced: true,
                         selected: true
                         },
                         ['Safari',    8.5],
                         ['Opera',     6.2],
                         ['Others',   0.7]
                         ]*//*
                    }]
                });

            });
*/
        setHeritageUserContribution('#heritageUserContributionTable_count', 'http://118.91.160.230/girc/dmis/api/heritage_assessment/heritages/unique-users', {});


    });
</script>

</body>
</html>
