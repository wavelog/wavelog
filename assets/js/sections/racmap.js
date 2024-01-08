var osmUrl = tileUrl;
var province;
var geojson;
var map;
var info;

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
            lotw: +$('#lotw').prop('checked'),
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
		  attribution: '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
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
  displayContactsOnMap($("#racmap"),marker.feature.id, $('#band2').val(), $('#mode').val(), 'RAC');
}
