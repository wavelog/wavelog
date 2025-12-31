let osmUrl = tileUrl;
let prefectures;
let geojson;
let map;
let info;
let clickmarkers = [];

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

const states = '01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47';

const wajamarkers = [
    [ "43.5", "143" ],     // 01 Hokkaido
    [ "40.7", "140.8" ],   // 02 Aomori
    [ "39.6", "141.3" ],   // 03 Iwate
    [ "39.8", "140.4" ],   // 04 Akita
    [ "38.6", "140" ],     // 05 Yamagata
    [ "38.5", "140.9" ],   // 06 Miyagi
    [ "37.5", "140.3" ],   // 07 Fukushima
    [ "37.5", "139" ],     // 08 Niigata
    [ "36.3", "138" ],     // 09 Nagano
    [ "35.7", "139.5" ],   // 10 Tokyo
    [ "35.5", "139.3" ],   // 11 Kanagawa
    [ "35.4", "140.2" ],   // 12 Chiba
    [ "36", "139.4" ],     // 13 Saitama
    [ "36.3", "140.3" ],   // 14 Ibaraki
    [ "36.7", "139.8" ],   // 15 Tochigi
    [ "36.6", "138.9" ],   // 16 Gunma
    [ "35.7", "138.6" ],   // 17 Yamanashi
    [ "35", "138.1" ],     // 18 Shizuoka
    [ "35.8", "137.1" ],   // 19 Gifu
    [ "35.1", "137.3" ],   // 20 Aichi
    [ "34.5", "136.4" ],   // 21 Mie
    [ "35.2", "135.5" ],   // 22 Kyoto
    [ "35.2", "136.2" ],   // 23 Shiga
    [ "34.4", "135.85" ],  // 24 Nara
    [ "34.65", "135.55" ], // 25 Osaka
    [ "33.95", "135.4" ],  // 26 Wakayama
    [ "35.1", "134.8" ],   // 27 Hyogo
    [ "36.65", "137.2" ],  // 28 Toyama
    [ "36", "136.3" ],     // 29 Fukui
    [ "36.4", "136.6" ],   // 30 Ishikawa
    [ "34.9", "133.8" ],   // 31 Okayama
    [ "35.05", "132.5" ],  // 32 Shimane
    [ "34.25", "131.5" ],  // 33 Yamaguchi
    [ "35.45", "133.8" ],  // 34 Tottori
    [ "34.7", "132.85" ],  // 35 Hiroshima
    [ "34.25", "133.85" ], // 36 Kagawa
    [ "33.95", "134.2" ],  // 37 Tokushima
    [ "33.7", "132.8" ],   // 38 Ehime
    [ "33.55", "133.2" ],  // 39 Kochi
    [ "33.55", "130.65" ], // 40 Fukuoka
    [ "33.35", "130.1" ],  // 41 Saga
    [ "32.8", "129.95" ],  // 42 Nagasaki
    [ "32.7", "130.8" ],   // 43 Kumamoto
    [ "33.15", "131.45" ], // 44 Oita
    [ "32.3", "131.3" ],   // 45 Miyazaki
    [ "31.9", "130.5" ],   // 46 Kagoshima
    [ "26.8", "128.2" ],   // 47 Okinawa
];

  var statearray = states.split(",");

function load_waja_map() {
    $('.nav-tabs a[href="#wajamaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/waja_map',
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
            prefectures = data;
            load_waja_map2(data);
        },
        error: function() {

        },
    });
}

function load_waja_map2(data) {
	$.getJSON(base_url + 'assets/json/geojson/states_339.geojson', function(mapcoordinates) {

   // If map is already initialized
  var container = L.DomUtil.get('wajamap');

  if(container != null){
	  container._leaflet_id = null;
	  container.remove();
	  $("#wajamaptab").append('<div id="wajamap" class="map-leaflet"></div>');
  }

  map = new L.Map('wajamap', {
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

  legend.addTo(map);

  info = L.control();

info.onAdd = function (map) {
    this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
    this.update();
    return this._div;
};

// method that we will use to update the control based on feature properties passed
info.update = function (props) {
    this._div.innerHTML = '<h4>' + lang_japan_province + '</h4>' +  (props ?
        '<b>' + props.code + ' - ' + props.name + '</b><br />' : lang_hover_over_a_prefecture);
};

info.addTo(map);

  geojson = L.geoJson(mapcoordinates, {style: style, onEachFeature: onEachFeature}).addTo(map);

  map.setView([35, 140], 5);

  addMarkers();

  map.on('zoomed', function() {
    clearMarkers();
    addMarkers();
  });

  var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: maidenhead = L.maidenhead() }).addTo(map);
  maidenhead.addTo(map);

	});
}

function clearMarkers() {
  clickmarkers.forEach(function (item) {
    map.removeLayer(item)
  });
}

function addMarkers() {
  var zoom = map.getZoom();

  for (var i = 0; i < statearray.length; i++) {
    createMarker(i);
  }
}

function createMarker(i) {
  var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1em; font-weight: 900;">' + (statearray[i]) + '</font></span>';
  var myIcon = L.divIcon({className: 'my-div-icon', html: title});
  var marker = L.marker(
    [wajamarkers[i][0], wajamarkers[i][1]], {
      icon: myIcon,
      title: (statearray[i]),
      zIndex: 1000,
    }
  ).addTo(map).on('click', onClick2);
  clickmarkers.push(marker);
}

function getColor(d) {
  return 	prefectures[d] == 'C' ? confirmedColor :
    prefectures[d] == 'W' ? workedColor :
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
  displayContactsOnMap($("#wajamap"),res, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAJA');
}

function onClick2(e) {
  var marker = e.target;
  displayContactsOnMap($("#wajamap"), marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAJA');
}
