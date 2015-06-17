<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <title>ClusterPies</title>
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css"/>
    <link rel="stylesheet" href="https://raw.githubusercontent.com/Leaflet/Leaflet.markercluster/master/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="clusterpies.css"/>
</head>
<body>
<script src="../assets/jquery/jquery.min.js"></script>
<script src="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js" charset="utf-8"></script>
<script src="http://leaflet.github.io/Leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>

<!-- Printing Maps @see:https://github.com/rowanwins/leaflet-easyPrint-->
<link rel="stylesheet" href="../assets/easy_print/easyPrint.css"/>
<script src="../assets/easy_print/jQuery.print.js" charset="utf-8"></script>
<script src="../assets/easy_print/leaflet.easyPrint.js" charset="utf-8"></script>
<!--http://bl.ocks.org/gisminister/10001728-->
<div id="container">
    <div id="map"/>
</div>
<script>
    "use strict"


    var     geojson,
        metadata = {
            "attribution":"Heritage Survey of Kathmandu Valley(2015), KMC & geospatiallab@ku",
            "description":"The survey was conducted by Kathmandu Municipality from June xx to June xx  2015 after Nepal Earthquake 2015",
            "fields":{
                "damage_type":{
                    "name":"Damage Type",
                    "lookup":{
                        "no damage":"No Damage",
                        "minor crack":"Minor Crack",
                        "partial damage":"Partial Damage",
                        "full damage":"Full Damage"
                    }
                },
                "inventory_id":{
                    "name":"Inventory Id"
                }
            }
        },
        geojsonPath = 'traffic_accidents.json',
        categoryField = 'damage_type', //This is the fieldname for marker category (used in the pie and legend)
        categories={
            "damage_type":{
                "values":["no damage","minor crack","partial damage","full damage"],
                "cssStyles":[
                    {"fill":"#40d47e","stroke":"#ffffff","background":"#40d47e","border-color":"#ffffff"},
                    {"fill":"#f1c40f","stroke":"#ffffff","background":"#f1c40f","border-color":"#ffffff"},
                    {"fill":"#d35400","stroke":"#ffffff","background":"#d35400","border-color":"#ffffff"},
                    {"fill":"#e74c3c","stroke":"#ffffff","background":"#e74c3c","border-color":"#ffffff"}
                ]
            }
        },
        icons={
            "damage_type":{
                "values":["no damage","minor crack","partial damage","full damage"],
                "cssStyles":[
                    {"background-image": "url('http://www.iconsdb.com/icons/download/black/running-16.png')","background-repeat": "no-repeat","background-position": "0px 1px"},
                    {"background-image": "url('http://www.iconsdb.com/icons/download/black/bicycle-16.png')')","background-repeat": "no-repeat","background-position": "0px -2px"},
                    {"background-image": "url('http://www.iconsdb.com/icons/download/black/motorcycle-16.png')","background-repeat": "no-repeat","background-position": "0px -2px"},
                    {"background-image": "url('http://www.iconsdb.com/icons/download/black/car-16.png')","background-repeat": "no-repeat","background-position": "0px -2px"},
                ]
            }
        },
        iconField = 'damage_type', //This is the fieldame for marker icon
        popupFields = ['damage_type','inventory_id'], //Popup will display these fields
        tileServer = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        tileAttribution = 'Map data: <a href="http://openstreetmap.org">OSM</a>',
        rmax = 30, //Maximum radius for cluster pies
        markerclusters = L.markerClusterGroup({
            maxClusterRadius: 2 * rmax,
            iconCreateFunction: defineClusterIcon //this is where the magic happens
        });


    var map = L.map('map').setView([27.70, 85.30], 8);

    L.easyPrint().addTo(map)

    //Add basemap
    L.tileLayer(tileServer, {attribution: tileAttribution, maxZoom: 20}).addTo(map);
    //and the empty markercluster layer
    map.addLayer(markerclusters);


    //        Heritage Layer
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
    $.ajax({
        url: URL,
        dataType: 'jsonp',
        jsonpCallback: 'getJson',
        success: function (data) {
            geojson = data;
            var markers = L.geoJson(geojson, {
                pointToLayer: defineFeature,
                onEachFeature: defineFeaturePopup
            });
            markerclusters.addLayer(markers);
            map.fitBounds(markers.getBounds());
            map.attributionControl.addAttribution(metadata.attribution);
            renderLegend();
            renderCssStyle();
        },
        error:function(){
            console.log('Could not load data...');
        }
    });

    //       ./ Heritage Layer

    function defineFeature(feature, latlng) {
        var categoryVal = feature.properties[categoryField],
            iconVal = feature.properties[iconField];
        var myClass = 'marker category-' + categoryVal + ' icon-' + space2underscore(iconVal);

        var myIcon = L.divIcon({
            className: myClass,
            iconSize: null
        });
        return L.marker(latlng, {icon: myIcon});
    }

    function defineFeaturePopup(feature, layer) {
        var props = feature.properties,
            fields = metadata.fields,
            popupContent = '';

        popupFields.map(function (key) {
            if (props[key]) {
                var val = props[key],
                    label = fields[key].name;
                if (fields[key].lookup) {
                    val = fields[key].lookup[val];
                }
                popupContent += '<span class="attribute"><span class="label">' + label + ':</span> ' + val + '</span>';
            }
        });
        popupContent = '<div class="map-popup">' + popupContent + '</div>';
        layer.bindPopup(popupContent, {offset: L.point(1, -2)});
    }

    function defineClusterIcon(cluster) {
        var children = cluster.getAllChildMarkers(),
            n = children.length, //Get number of markers in cluster
            strokeWidth = 1, //Set clusterpie stroke width
            r = rmax - 2 * strokeWidth - (n < 10 ? 12 : n < 100 ? 8 : n < 1000 ? 4 : 0), //Calculate clusterpie radius...
            iconDim = (r + strokeWidth) * 2, //...and divIcon dimensions (leaflet really want to know the size)
            data = d3.nest() //Build a dataset for the pie chart
                .key(function (d) {
                    return d.feature.properties[categoryField];
                })
                .entries(children, d3.map),
        //bake some svg markup
            html = bakeThePie({
                data: data,
                valueFunc: function (d) {
                    return d.values.length;
                },
                strokeWidth: 1,
                outerRadius: r,
                innerRadius: r - 10,
                pieClass: 'cluster-pie',
                pieLabel: n,
                pieLabelClass: 'marker-cluster-pie-label',
                pathClassFunc: function (d) {
                    return "category-" + space2underscore(d.data.key);
                },
                pathTitleFunc: function (d) {
                    return metadata.fields[categoryField].lookup[d.data.key] + ' (' + d.data.values.length + ' monument' + (d.data.values.length != 1 ? 's' : '') + ')';
                }
            }),
        //Create a new divIcon and assign the svg markup to the html property
            myIcon = new L.DivIcon({
                html: html,
                className: 'marker-cluster',
                iconSize: new L.Point(iconDim, iconDim)
            });
        return myIcon;
    }

    /*function that generates a svg markup for the pie chart*/
    function bakeThePie(options) {
        /*data and valueFunc are required*/
        if (!options.data || !options.valueFunc) {
            return '';
        }
        var data = options.data,
            valueFunc = options.valueFunc,
            r = options.outerRadius ? options.outerRadius : 35, //Default outer radius = 28px
            rInner = options.innerRadius ? options.innerRadius : r - 15, //Default inner radius = r-10
            strokeWidth = options.strokeWidth ? options.strokeWidth : 1, //Default stroke is 1
            pathClassFunc = options.pathClassFunc ? options.pathClassFunc : function () {
                return '';
            }, //Class for each path
            pathTitleFunc = options.pathTitleFunc ? options.pathTitleFunc : function () {
                return '';
            }, //Title for each path
            pieClass = options.pieClass ? options.pieClass : 'marker-cluster-pie', //Class for the whole pie
            pieLabel = options.pieLabel ? options.pieLabel : d3.sum(data, valueFunc), //Label for the whole pie
            pieLabelClass = options.pieLabelClass ? options.pieLabelClass : 'marker-cluster-pie-label',//Class for the pie label

            origo = (r + strokeWidth), //Center coordinate
            w = origo * 2, //width and height of the svg element
            h = w,
            donut = d3.layout.pie(),
            arc = d3.svg.arc().innerRadius(rInner).outerRadius(r);

        //Create an svg element
        var svg = document.createElementNS(d3.ns.prefix.svg, 'svg');
        //Create the pie chart
        var vis = d3.select(svg)
            .data([data])
            .attr('class', pieClass)
            .attr('width', w)
            .attr('height', h);

        var arcs = vis.selectAll('g.arc')
            .data(donut.value(valueFunc))
            .enter().append('svg:g')
            .attr('class', 'arc')
            .attr('transform', 'translate(' + origo + ',' + origo + ')');

        arcs.append('svg:path')
            .attr('class', pathClassFunc)
            .attr('stroke-width', strokeWidth)
            .attr('d', arc)
            .append('svg:title')
            .text(pathTitleFunc);

        vis.append('text')
            .attr('x', origo)
            .attr('y', origo)
            .attr('class', pieLabelClass)
            .attr('text-anchor', 'middle')
            //.attr('dominant-baseline', 'central')
            /*IE doesn't seem to support dominant-baseline, but setting dy to .3em does the trick*/
            .attr('dy', '.3em')
            .text(pieLabel);


        //Return the svg-markup rather than the actual element
        return serializeXmlNode(svg);
    }


    /*Function for generating a legend with the same categories as in the clusterPie*/
    function renderLegend() {
        var data = d3.entries(metadata.fields[categoryField].lookup),
            legenddiv = d3.select('body').append('div')
                .attr('id', 'legend');

        var heading = legenddiv.append('div')
            .classed('legendheading', true)
            .text(metadata.fields[categoryField].name);

        var legenditems = legenddiv.selectAll('.legenditem')
            .data(data);

        legenditems
            .enter()
            .append('div')
            .attr('class', function (d) {
                return 'category-' + space2underscore(d.key);
            })
            .classed({'legenditem': true})
            .text(function (d) {
                return d.value;
            });
    }

    function renderCssStyle(){
        var sheet=addStylesheet('styleCategory');
        var clusterCategory=categories[categoryField];

        $.each(clusterCategory.values, function(idx,value){
            var selector = '.category-' + space2underscore(value);
            var cssStyleObj = clusterCategory.cssStyles[idx];
            var cssStyleStr=selector+'{'+styleObj2css(cssStyleObj)+'}';
            sheet.insertRule(cssStyleStr,0);
        });

        var iconCategory = categories[iconField];
        $.each(iconCategory.values, function(idx,value){
            var selector = '.icon-' + space2underscore(value);
            var cssStyleObj = iconCategory.cssStyles[idx];
            var cssStyleStr=selector+'{'+styleObj2css(cssStyleObj)+'}';
            sheet.insertRule(cssStyleStr,0);
        });

        /*
         categoryField:5065
         Sample stylesheet "properties": {
         "5055": "2013-06-17",
         "5065": "4",
         "5074": "5"
         }
         */
        /*sheet.insertRule(".category-1{fill: #F88;stroke: #800; background: #F88; border-color: #800;}",0);
         sheet.insertRule(".category-2{fill: #FA0;stroke: #B60; background: #FA0; border-color: #B60;}",0);
         sheet.insertRule(".category-3{fill: #FF3;stroke: #D80; background: #FF3; border-color: #D80;}",0);
         sheet.insertRule(".category-4{fill: #BFB;stroke: #070; background: #BFB; border-color: #070;}",0);
         sheet.insertRule(".category-5{fill: #9DF;stroke: #007; background: #9DF; border-color: #007);}",0);
         sheet.insertRule(".category-6{fill: #CCC;stroke: #444; background: #CCC; border-color: #444;}",0);*/

    }

    /* Helper function*/
    function serializeXmlNode(xmlNode) {
        if (typeof window.XMLSerializer != "undefined") {
            return (new window.XMLSerializer()).serializeToString(xmlNode);
        } else if (typeof xmlNode.xml != "undefined") {
            return xmlNode.xml;
        }
        return "";
    }

    function addStylesheet(id){
        var sheet = (function() {
            // Create the <style> tag
            var style = document.createElement("style");

            // Add a media (and/or media query) here if you'd like!
            // style.setAttribute("media", "screen")
            // style.setAttribute("media", "only screen and (max-width : 1024px)")

            // WebKit hack :(
            style.appendChild(document.createTextNode(""));

            // Add the <style> element to the page
            document.head.appendChild(style);

            return style.sheet;
        })();
        return sheet;
    }

    function addCSSRule(sheet, selector, rules){
        //Backward searching of the selector matching cssRules
        var index=sheet.cssRules.length-1;
        for(var i=index; i>0; i--){
            var current_style = sheet.cssRules[i];
            if(current_style.selectorText === selector){
                //Append the new rules to the current content of the cssRule;
                rules=current_style.style.cssText + rules;
                sheet.deleteRule(i);
                index=i;
            }
        }
        if(sheet.insertRule){
            sheet.insertRule(selector + "{" + rules + "}", index);
        }
        else{
            sheet.addRule(selector, rules, index);
        }
        return sheet.cssRules[index].cssText;
    }// Use it!  addCSSRule(document.styleSheets[0], "header", "float: left");

    function clearCSSRules(sheet){
        var i = sheet.cssRules.length - 1 ;
        // Remove all the rules from the end inwards.
        while(i >= 0){
            if("deleteRule" in sheet) { sheet.deleteRule(i); }
            else if("removeRule" in sheet) { sheet.removeRule(i); }
            i--;
        }
    }

    function styleObj2css(styleObject){
        var css='';
        var keys=Object.keys(styleObject);
        for(var i=0;i<keys.length;i++){
            css += keys[i]+':'+styleObject[keys[i]]+';';
        }
        return css;
    }

    function space2underscore(str){
        return str.split(' ').join('_');
    }
    /* ./Helper function*/

</script>
</body>
</html>