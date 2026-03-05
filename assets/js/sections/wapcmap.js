let osmUrl = tileUrl;
let provinces;
let geojson;
let map;
let info;

let confirmedColor = 'rgba(144,238,144)';
if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
      confirmedColor = user_map_custom.qsoconfirm.color;
}
let workedColor = 'rgba(229, 165, 10)';
if (typeof(user_map_custom.qso) !== 'undefined') {
      workedColor = user_map_custom.qso.color;
}
let unworkedColor = 'rgba(204, 55, 45)';
if (typeof(user_map_custom.unworked) !== 'undefined') {
   unworkedColor = user_map_custom.unworked.color;
}

function load_wapc_map() {
    $('.nav-tabs a[href="#wapcmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/wapc_map',
        type: 'post',
        data: {
            band: $('#band2').val(),
            mode: $('#mode').val(),
            worked: +$('#worked').prop('checked'),
            confirmed: +$('#confirmed').prop('checked'),
            notworked: +$('#notworked').prop('checked'),
            qsl: +$('#qsl').prop('checked'),
            lotw: +$('#lotw').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
            qrz: +$('#qrz').prop('checked'),
            clublog: +$('#clublog').prop('checked'),
        },
        success: function(data) {
            provinces = data;
            load_wapc_map2(data);
        },
        error: function() {

        },
    });
}

function load_wapc_map2(data) {
    $.getJSON(base_url + 'assets/json/geojson/states_318.geojson', function(mapcoordinates) {
        
        var container = L.DomUtil.get('wapcmap');

        if(container != null){
            container._leaflet_id = null;
            container.remove();
            $("#wapcmaptab").append('<div id="wapcmap" class="map-leaflet"></div>');
        }

        map = new L.Map('wapcmap', {
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: 'topleft'
            },
        });

        L.tileLayer(
            osmUrl,
            {
                attribution: option_map_tile_server_copyright,
                maxZoom: 18
            }
        ).addTo(map);

        var notworked = mapcoordinates.features.length;
        var confirmed = 0;
        var workednotconfirmed = 0;

        for(var k in data) {
            var mapColor = unworkedColor;

            if (data[k] == 'C') {
                mapColor = confirmedColor;
                confirmed++;
                notworked--;
            }
            if (data[k] == 'W') {
                mapColor = workedColor;
                workednotconfirmed++;
                notworked--;
            }
        }

        var legend = L.control({ position: "topright" });

        legend.onAdd = function(map) {
            var div = L.DomUtil.create("div", "legend");
            div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
            div.innerHTML += "<i style='background: " + confirmedColor + "'></i><span>" + lang_general_word_confirmed + " (" + confirmed + ")</span><br>";
            div.innerHTML += "<i style='background: " + workedColor + "'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmed + ")</span><br>";
            div.innerHTML += "<i style='background: " + unworkedColor + "'></i><span>" + lang_general_word_not_worked + " (" + notworked + ")</span><br>";
            return div;
        };

        legend.addTo(map);

        info = L.control();

        info.onAdd = function (map) {
            this._div = L.DomUtil.create('div', 'info');
            this.update();
            return this._div;
        };

        info.update = function (props) {
            this._div.innerHTML = '<h4>' + lang_china_province + '</h4>' +  (props ?
                '<b>' + props.code + ' - ' + props.name + '</b><br />' : lang_hover_over_a_province);
        };

        info.addTo(map);

        geojson = L.geoJson(mapcoordinates, {style: style, onEachFeature: onEachFeature}).addTo(map);

        map.setView([35, 105], 4);

        var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: maidenhead = L.maidenhead() }).addTo(map);
        maidenhead.addTo(map);

    });
}

function getColor(d) {
    return provinces[d] == 'C' ? confirmedColor :
           provinces[d] == 'W' ? workedColor :
           unworkedColor;
}

function highlightFeature(e) {
    var layer = e.target;

    layer.setStyle({
        weight: 3,
        color: 'white',
        dashArray: '',
        fillOpacity: 0.8
    });

    layer.bringToFront();
    info.update(layer.feature.properties);
}

function onEachFeature(feature, layer) {
    layer.on({
        mouseover: highlightFeature,
        mouseout: resetHighlight,
        click: onClick
    });
}

function zoomToFeature(e) {
    map.fitBounds(e.target.getBounds());
}

function resetHighlight(e) {
    geojson.resetStyle(e.target);
    info.update();
}

function style(feature) {
    return {
        fillColor: getColor(feature.properties.code),
        weight: 1,
        opacity: 1,
        color: 'white',
        // dashArray: '3',
        fillOpacity: 0.6
    };
}

function onClick(e) {
    zoomToFeature(e);
    var marker = e.target;
    var res = marker.feature.properties.code;
    displayContactsOnMap($("#wapcmap"),res, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAPC');
}
