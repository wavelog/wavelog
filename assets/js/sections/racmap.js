var osmUrl = tileUrl;
var province;
var geojson;
var map;
var info;
var clickmarkers = [];

const states = 'AB,BC,MB,NB,NL,NT,NS,NU,ON,PE,QC,SK,YT';

const racmarkers = [
    [ "55", "-115" ],      // AB Alberta
    [ "55", "-125" ],      // BC British Columbia
    [ "55", "-99" ],       // MB Manitoba
    [ "46.5", "-66.4" ],   // NB New Brunswick
    [ "54.5", "-62.5" ],   // NL Newfoundland And Labrador
	[ "65", "-120" ],      // NT Northwest Territories
	[ "45.2", "-63.2" ],   // NS Nova Scotia
    [ "65", "-99" ],       // NU Nunavut
    [ "52", "-89" ],       // ON Ontaria
    [ "46.3", "-62.9" ],   // PE Prince Edward Island
    [ "52", "-75" ],       // QC Quebec
	[ "55", "-105" ],      // SK Saskatchewan
    [ "65", "-135" ]       // YT Yukon
];

  var statearray = states.split(",");


function load_rac_map() {
    $('.nav-tabs a[href="#racmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/rac_map',
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
            load_rac_map2(data);
        },
        error: function() {

        },
    });
}

function load_rac_map2(data) {

   // If map is already initialized
  var container = L.DomUtil.get('racmap');

  if(container != null){
	  container._leaflet_id = null;
	  container.remove();
	  $("#racmaptab").append('<div id="racmap"></div>');
  }

  map = new L.Map('racmap', {
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
		var mapColor = 'red';

		if (data[k] == 'C') {
			mapColor = 'green';
			confirmed++;
			notworked--;
		}
		if (data[k] == 'W') {
		mapColor = 'orange';
		workednotconfirmed++;
		notworked--;
		}
	}


  /*Legend specific*/
  var legend = L.control({ position: "topright" });

  legend.onAdd = function(map) {
	  var div = L.DomUtil.create("div", "legend");
	  div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
	  div.innerHTML += "<i style='background: green'></i><span>" + lang_general_word_confirmed + " (" + confirmed + ")</span><br>";
	  div.innerHTML += "<i style='background: orange'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmed + ")</span><br>";
	  div.innerHTML += "<i style='background: red'></i><span>" + lang_general_word_not_worked + " (" + notworked + ")</span><br>";
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
    this._div.innerHTML = '<h4>Province</h4>' +  (props ?
        '<b>' + props.id.substring(3,5) + ' - ' + props.name + '</b><br />' : 'Hover over a province');
};

info.addTo(map);

  geojson = L.geoJson(mapcoordinates, {style: style, onEachFeature: onEachFeature}).addTo(map);

  map.setView([70, -100], 3);

  addMarkers();

  map.on('zoomed', function() {
    clearMarkers();
    addMarkers();
  });

  var layerControl = new L.Control.Layers(null, { 'Gridsquares': maidenhead = L.maidenhead() }).addTo(map);
  maidenhead.addTo(map);
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
	  [racmarkers[i][0], racmarkers[i][1]], {
		icon: myIcon,
		title: (statearray[i]),
		zIndex: 1000,
	  }
	).addTo(map).on('click', onClick2);
	clickmarkers.push(marker);
  }

function getColor(d) {
    return 	province[d] == 'C' ? 'green'  :
			province[d] == 'W' ? 'orange' :
									  'red';
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
        fillColor: getColor(feature.id),
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
  displayContactsOnMap($("#racmap"),marker.feature.id, $('#band2').val(), 'All', 'All', $('#mode').val(), 'RAC');
}

function onClick2(e) {
	var marker = e.target;
	displayContactsOnMap($("#racmap"), marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'RAC');
  }
