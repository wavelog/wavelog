$(document).ready(function() {
    // Band/Mode multi-select dropdowns, same bootstrap-multiselect widget/config
    // as the Satellite Pass page's satellite picker (assets/js/sections/satpasses.js).
    var multiselectOptions = {
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        filterPlaceholder: lang_general_word_search,
        templates: {
            button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
        },
        numberDisplayed: 1,
        inheritClass: true,
        includeSelectAllOption: true,
        buttonTextAlignment: 'left',
    };
    $('#countiesBand').multiselect($.extend({}, multiselectOptions, {nonSelectedText: lang_counties_every_band}));
    $('#countiesMode').multiselect($.extend({}, multiselectOptions, {nonSelectedText: lang_counties_every_mode}));
});

let osmUrl = tileUrl;
let countyStatus;
let countiesGeojsonLayer;
let countiesMap;
let countiesInfo;

let confirmedColor = 'rgba(144,238,144)';
if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
      confirmedColor = user_map_custom.qsoconfirm.color;
}
let workedColor = '#f0ad4e';
if (typeof(user_map_custom.qso) !== 'undefined') {
      workedColor = user_map_custom.qso.color;
}
let unworkedColor = 'rgba(204, 55, 45)';
if (typeof(user_map_custom.unworked) !== 'undefined') {
   unworkedColor = user_map_custom.unworked.color;
}

function load_counties_map() {
    $('.nav-tabs a[href="#countiesmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/counties_map',
        type: 'post',
        data: {
            qslFilterSet: 1,
            qsl: +$('#countiesQsl').prop('checked'),
            lotw: +$('#countiesLotw').prop('checked'),
            eqsl: +$('#countiesEqsl').prop('checked'),
            qrz: +$('#countiesQrz').prop('checked'),
            clublog: +$('#countiesClublog').prop('checked'),
            band: $('#countiesBand').val() || [],
            mode: $('#countiesMode').val() || [],
        },
        success: function(data) {
            countyStatus = data;
            // Fetch lower 48 + DC (DXCC 291), Alaska (DXCC 6) and Hawaii (DXCC 110) separately,
            // plus the state-level outline for each, drawn bolder on top of the counties so
            // state lines stand out from county lines. The outline files are the *same* county
            // boundaries dissolved per state (see COUNTIES_SOURCE.md), not the WAS map's
            // independently-simplified states_*.geojson - reusing that caused the bold outline
            // to visibly diverge from the county shading underneath at high zoom, since the two
            // were different simplifications of the same physical line.
            // Routed through counties_geojson() rather than fetched as static assets so the
            // combined payload gets an explicit long-lived Cache-Control/ETag (see
            // Awards::counties_geojson()) instead of depending on the host's web server defaults.
            Promise.all([
                fetch(base_url + 'index.php/awards/counties_geojson/counties_291').then(r => r.json()),
                fetch(base_url + 'index.php/awards/counties_geojson/counties_6').then(r => r.json()),
                fetch(base_url + 'index.php/awards/counties_geojson/counties_110').then(r => r.json()),
                fetch(base_url + 'index.php/awards/counties_geojson/counties_state_outline_291').then(r => r.json()),
                fetch(base_url + 'index.php/awards/counties_geojson/counties_state_outline_6').then(r => r.json()),
                fetch(base_url + 'index.php/awards/counties_geojson/counties_state_outline_110').then(r => r.json())
            ]).then(([counties48, ak, hi, states48, statesAk, statesHi]) => {
                counties48.features = counties48.features.concat(ak.features, hi.features);
                states48.features = states48.features.concat(statesAk.features, statesHi.features);
                load_counties_map2(counties48, states48);
            });
        },
        error: function() {

        },
    });
}

function load_counties_map2(mapcoordinates, stateCoordinates) {

  // If map is already initialized
  var container = L.DomUtil.get('countiesmap');

  if (container != null) {
      container._leaflet_id = null;
      container.remove();
      $("#countiesmaptab").append('<div id="countiesmap" class="map-leaflet"></div>');
  }

  countiesMap = new L.Map('countiesmap', {
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
  ).addTo(countiesMap);

  var confirmed = 0;
  var workednotconfirmed = 0;
  var notworked = mapcoordinates.features.length;

  for (var k in countyStatus) {
      if (countyStatus[k] == 'C') {
          confirmed++;
          notworked--;
      } else if (countyStatus[k] == 'W') {
          workednotconfirmed++;
          notworked--;
      }
  }

  /*Legend specific*/
  var legend = L.control({ position: "topright" });

  legend.onAdd = function(map) {
      var div = L.DomUtil.create("div", "legend");
      div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
      div.innerHTML += "<i style='background: " + confirmedColor + "'></i><span>" + lang_general_word_confirmed + " (" + confirmed + ")</span><br>";
      div.innerHTML += "<i style='background: " + workedColor + "'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmed + ")</span><br>";
      div.innerHTML += "<i style='background: " + unworkedColor + "'></i><span>" + lang_general_word_not_worked + " (" + notworked + ")</span><br>";
      return div;
  };

  legend.addTo(countiesMap);

  countiesInfo = L.control();

  countiesInfo.onAdd = function (map) {
      this._div = L.DomUtil.create('div', 'info');
      this.update();
      return this._div;
  };

  countiesInfo.update = function (props) {
      var displayText = '';
      if (props) {
          displayText = '<b>' + props.county + ', ' + props.state + '</b>';
      } else {
          displayText = lang_hover_over_a_county;
      }
      this._div.innerHTML = '<h4>' + lang_usa_county + '</h4>' + displayText;
  };

  countiesInfo.addTo(countiesMap);

  countiesGeojsonLayer = L.geoJson(mapcoordinates, {style: countyStyle, onEachFeature: onEachCountyFeature}).addTo(countiesMap);

  // Bolder, non-interactive state outline drawn on top so state lines read
  // clearly through the thin county boundaries underneath.
  L.geoJson(stateCoordinates, {
      interactive: false,
      style: { fill: false, color: 'white', weight: 2.5, opacity: 1 },
  }).addTo(countiesMap);

  countiesMap.setView([40, -97], 4);

  var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: countyMaidenhead = L.maidenhead() }).addTo(countiesMap);
  countyMaidenhead.addTo(countiesMap);
}

function getCountyColor(id) {
    // Uppercase to match Awards::counties_map()'s case-insensitive key
    // (a QSO's logged county name can be typed/imported in any case, but
    // the boundary GeoJSON's feature ids use fixed ARRL/MARAC casing).
    var status = countyStatus[id.toUpperCase()];
    return status == 'C' ? confirmedColor :
           status == 'W' ? workedColor :
                            unworkedColor;
}

function countyStyle(feature) {
    return {
        fillColor: getCountyColor(feature.id),
        weight: 0.5,
        opacity: 1,
        color: 'white',
        fillOpacity: 0.6
    };
}

function highlightCountyFeature(e) {
    var layer = e.target;

    layer.setStyle({
        weight: 2,
        color: 'white',
        fillOpacity: 0.85
    });

    layer.bringToFront();
    countiesInfo.update(layer.feature.properties);
}

function resetCountyHighlight(e) {
    countiesGeojsonLayer.resetStyle(e.target);
    countiesInfo.update();
}

function onEachCountyFeature(feature, layer) {
    layer.on({
        mouseover: highlightCountyFeature,
        mouseout: resetCountyHighlight,
        click: onCountyClick
    });
}

function onCountyClick(e) {
    var props = e.target.feature.properties;
    displayCountyContacts(props.state, props.county);
}
