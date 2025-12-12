var osmUrl = tileUrl;
var province;
var geojson;
var map;
var info;
var clickmarkers = [];

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

const states = 'DR,FL,FR,GD,GR,LB,NB,NH,OV,UT,ZH,ZL';

const wapmarkers = [
    [ "52.9", "6.6" ],    // DR Drenthe
    [ "52.5", "5.5" ],    // FL Flevoland
    [ "53.2", "5.7" ],    // FR Friesland
    [ "52.0", "6.0" ],     // GD Gelderland
    [ "53.3", "6.8" ],     // GR Groningen
    [ "51.3", "5.9" ],     // LB Limburg
    [ "51.6", "5.4" ],     // NB Noord-Brabant
    [ "52.6", "4.8" ],     // NH Noord-Holland
    [ "52.4", "6.5" ],     // OV Overijssel
    [ "52.1", "5.1" ],     // UT Utrecht
    [ "52.0", "4.3" ],     // ZH Zuid-Holland
    [ "51.5", "3.8" ]      // ZL Zeeland
];

  var statearray = states.split(",");


function load_wap_map() {
    $('.nav-tabs a[href="#wapmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/wap_map',
        type: 'post',
        data: {
            band: $('#band2').val(),
            mode: $('#mode').val(),
            worked: +$('#worked').prop('checked'),
            confirmed: +$('#confirmed').prop('checked'),
            notworked: +$('#notworked').prop('checked'),
            qsl: +$('#qsl').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
            lotw: +$('#lotw').prop('checked'),
            qrz: +$('#qrz').prop('checked'),
        },
        success: function(data) {
            province = data;
            load_wap_map2(data);
        },
        error: function() {

        },
    });
}

function load_wap_map2(data) {

   // If map is already initialized
  var container = L.DomUtil.get('wapmap');

  if(container != null){
	  container._leaflet_id = null;
	  container.remove();
	  $("#wapmaptab").append('<div id="wapmap"></div>');
  }

  map = new L.Map('wapmap', {
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

  // Load GeoJSON from external file
  $.getJSON(base_url + 'assets/json/geojson/states_263.geojson', function(mapcoordinates) {
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
    this._div.innerHTML = '<h4>' + lang_netherlands_province + '</h4>' +  (props ?
        '<b>' + props.code + ' - ' + props.name + '</b><br />' : lang_hover_over_a_province);
};

info.addTo(map);

  geojson = L.geoJson(mapcoordinates, {style: style, onEachFeature: onEachFeature}).addTo(map);

  map.setView([52, 5], 6);

  addMarkers();

  map.on('zoomed', function() {
    clearMarkers();
    addMarkers();
  });

  var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: maidenhead = L.maidenhead() }).addTo(map);
  maidenhead.addTo(map);
  }); // end $.getJSON
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
	  [wapmarkers[i][0], wapmarkers[i][1]], {
		icon: myIcon,
		title: (statearray[i]),
		zIndex: 1000,
	  }
	).addTo(map).on('click', onClick2);
	clickmarkers.push(marker);
  }

function getColor(d) {
    return 	province[d] == 'C' ? confirmedColor  :
			province[d] == 'W' ? workedColor :
											  unworkedColor;
}function highlightFeature(e) {
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
  displayContactsOnMap($("#wapmap"), marker.feature.properties.code, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAP');
}

function onClick2(e) {
	var marker = e.target;
	displayContactsOnMap($("#wapmap"), marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAP');
  }
