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

    // Hide overlay when modal is shown (loading complete)
    $(document).on('shown.bs.modal', '.modal', function() {
        hideModalLoadingOverlay();
    });

    // Reset modal loading flag and hide overlay when modal is closed
    $(document).on('hidden.bs.modal', '.modal', function() {
        hideModalLoadingOverlay();
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
var isModalLoading = false; // Prevent duplicate modal opens

// Use user-customizable map colors (same as RAC and other awards)
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

function showModalLoadingOverlay() {
    var mapContainer = $('#polska-map');
    if (!mapContainer.find('.modal-loading-overlay').length) {
        mapContainer.append('<div class="modal-loading-overlay" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:1000;cursor:wait;"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    }
    mapContainer.find('.modal-loading-overlay').show();
}

function hideModalLoadingOverlay() {
    $('#polska-map .modal-loading-overlay').hide();
    isModalLoading = false;
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

    // Add legend first (colors info box - appears at top right)
    addLegend(data);

    // Info control for displaying voivodeship name on hover (below legend)
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
        div.innerHTML += "<i style='background: " + confirmedColor + "'></i><span>" + lang_general_word_confirmed + " (" + confirmed + ")</span><br>";
        div.innerHTML += "<i style='background: " + workedColor + "'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmed + ")</span><br>";
        div.innerHTML += "<i style='background: " + unworkedColor + "'></i><span>" + lang_general_word_not_worked + " (" + notworked + ")</span><br>";
        return div;
    };

    legend.addTo(map);
}

function getColor(voivCode) {
    // Get status from voivodeships data by voivodeship code
    var status = voivodeships[voivCode];
    return status == 'C' ? confirmedColor :   // Confirmed
           status == 'W' ? workedColor :       // Worked
           unworkedColor;                       // Not worked
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
        click: onClickRegion
    });
}

function onClickRegion(e) {
    // Prevent double-clicks while modal is loading
    if (isModalLoading) {
        return;
    }
    isModalLoading = true;

    // Show loading overlay to block further clicks
    showModalLoadingOverlay();

    zoomToFeature(e);
    var layer = e.target;
    var voivCode = layer.feature.properties.code;
    var category = $('#polska-category-select').val() || 'MIXED';

    // Determine band and mode based on category selection
    var band = 'All';
    var mode = category;

    // Check if category is a band (like 20M, 40M, etc.)
    var validBands = ['160M', '80M', '40M', '30M', '20M', '17M', '15M', '12M', '10M', '6M', '2M'];
    if (validBands.indexOf(category) !== -1) {
        band = category;
        mode = 'All';
    }

    displayContactsOnMap($("#polska-map"), voivCode, band, 'All', 'All', mode, 'POLSKA');
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
        fillOpacity: 0.6
    };
}


