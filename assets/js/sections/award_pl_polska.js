$(document).ready(function() {
    // Auto-load map when Map tab is shown
    $('#map-tab').on('shown.bs.tab', function (e) {
        if (!map) {
            load_polska_map();
        }
    });

    // Reload map when category changes
    $('#polska-category-select').on('change', function() {
        load_polska_map();
    });
});

// Polska Award Map Implementation
var voivodeships;
var geojson;
var map;
var info;
var legend;
var maidenhead;
var cachedGeoJSON = null;  // Cache for GeoJSON data
var isLoading = false;     // Prevent duplicate API calls

function showMapSpinner() {
    var mapContainer = $('#polska-map');
    if (!mapContainer.find('.map-spinner-overlay').length) {
        mapContainer.append('<div class="map-spinner-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    }
    mapContainer.find('.map-spinner-overlay').show();
}

function hideMapSpinner() {
    $('#polska-map .map-spinner-overlay').hide();
}

function load_polska_map() {
    // Prevent duplicate calls while loading
    if (isLoading) {
        return;
    }
    isLoading = true;

    var category = $('#polska-category-select').val() || 'MIXED';

    showMapSpinner();

    $.ajax({
        url: base_url + 'index.php/awards/polska_map',
        type: 'post',
        data: {
            category: category,
            qsl: +$('#qsl').prop('checked'),
            lotw: +$('#lotw').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
            qrz: +$('#qrz').prop('checked'),
            clublog: +$('#clublog').prop('checked'),
        },
        success: function(data) {
            voivodeships = data;

            // Load GeoJSON only once, then cache it
            if (cachedGeoJSON) {
                updatePolskaMap(data);
            } else {
                $.getJSON(base_url + 'assets/json/geojson/states_269.geojson', function(mapcoordinates) {
                    cachedGeoJSON = mapcoordinates;
                    initPolskaMap(data);
                });
            }
        },
        error: function() {
            console.error("Failed to load Polska map data");
        },
        complete: function() {
            isLoading = false;
            hideMapSpinner();
        }
    });
}

// Initialize map for the first time
function initPolskaMap(data) {
    // Initialize the map
    map = L.map('polska-map');

    L.tileLayer(tileUrl, {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Info control for displaying voivodeship name on hover
    info = L.control();

    info.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info');
        this.update();
        return this._div;
    };

    info.update = function (props) {
        this._div.innerHTML = '<h4>' + lang_polish_voivodeship + '</h4>' + (props ?
            '<b>' + props.name + '</b><br />&nbsp;' : lang_hover_over_voivodeship + '<br />&nbsp;');
    };

    info.addTo(map);

    // Add legend
    addLegend(data);

    // Add GeoJSON layer
    geojson = L.geoJson(cachedGeoJSON, {
        style: style,
        onEachFeature: onEachFeature
    }).addTo(map);

    // Center map on Poland
    map.setView([52, 19], 6);

    // Add maidenhead grid overlay
    maidenhead = L.maidenhead();
    L.control.layers(null, {
        [lang_general_gridsquares]: maidenhead
    }).addTo(map);
    maidenhead.addTo(map);
}

// Update map when category/filters change (reuse existing map)
function updatePolskaMap(data) {
    // Remove old legend
    if (legend) {
        map.removeControl(legend);
        legend = null;
    }

    // Remove old GeoJSON layer
    if (geojson) {
        map.removeLayer(geojson);
        geojson = null;
    }

    // Add updated legend
    addLegend(data);

    // Add updated GeoJSON layer with new colors
    geojson = L.geoJson(cachedGeoJSON, {
        style: style,
        onEachFeature: onEachFeature
    }).addTo(map);
}

// Add legend with current counts
function addLegend(data) {
    // Count statuses for legend
    var confirmed = 0;
    var workednotconfirmed = 0;
    var notworked = 0;

    cachedGeoJSON.features.forEach(function(feature) {
        var voivCode = feature.properties.code;
        var status = data[voivCode];
        if (status == 'C') {
            confirmed++;
        } else if (status == 'W') {
            workednotconfirmed++;
        } else {
            notworked++;
        }
    });

    legend = L.control({ position: "topright" });

    legend.onAdd = function(map) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
        div.innerHTML += "<i style='background: green'></i><span>" + lang_general_word_confirmed + " (" + confirmed + ")</span><br>";
        div.innerHTML += "<i style='background: orange'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmed + ")</span><br>";
        div.innerHTML += "<i style='background: red'></i><span>" + lang_general_word_not_worked + " (" + notworked + ")</span><br>";
        return div;
    };

    legend.addTo(map);
}

function getColor(voivCode) {
    // Get status from voivodeships data by voivodeship code
    var status = voivodeships[voivCode];
    return status == 'C' ? 'green' :   // Confirmed
           status == 'W' ? 'orange' :   // Worked
           'red';                        // Not worked
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
        mouseout: resetHighlight
    });
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
        fillOpacity: 0.6
    };
}


