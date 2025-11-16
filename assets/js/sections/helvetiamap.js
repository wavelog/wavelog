let osmUrl = tileUrl;
let province;
let geojson;
let map;
let info;
let clickmarkers = [];

let confirmedColor = user_map_custom.qsoconfirm.color;
let workedColor = user_map_custom.qso.color;
let unworkedColor = '';
if (typeof(user_map_custom.unworked) !== 'undefined') {
	unworkedColor = user_map_custom.unworked.color;
} else {
	unworkedColor = 'red';
}

const states = 'AG,AI,AR,BE,BL,BS,FR,GE,GL,GR,JU,LU,NE,NW,OW,SG,SH,SO,SZ,TG,TI,UR,VD,VS,ZG,ZH';

var statearray = states.split(",");

function load_helvetia_map() {
    $('.nav-tabs a[href="#helvetiamaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/helvetia_map',
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
            load_helvetia_map2(data);
        },
        error: function() {

        },
    });
}

function load_helvetia_map2(data) {

   // If map is already initialized
  var container = L.DomUtil.get('helvetiamap');

  if(container != null){
	  container._leaflet_id = null;
	  container.remove();
	  $("#helvetiamaptab").append('<div id="helvetiamap"></div>');
  }

  map = new L.Map('helvetiamap', {
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
    this._div.innerHTML = '<h4>' + lang_switzerland_canton + '</h4>' +  (props ? '<b>' + props.kan_code + ' - ' + props.kan_name + '</b><br />' : lang_hover_over_a_canton);
};

info.addTo(map);

geojson = L.geoJSON (mapcoordinates, {
	style: style,
	onEachFeature: onEachFeature,
}).addTo(map);


map.setView([46.8, 8.4], 8);

  //addMarkers();

  map.on('zoomed', function() {
    clearMarkers();
    addMarkers();
  });

  var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: maidenhead = L.maidenhead() }).addTo(map);
  maidenhead.addTo(map);
}

function createMarker(i) {
	var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1em; font-weight: 900;">' + (i.properties.kan_code) + '</font></span>';
	var myIcon = L.divIcon({className: 'my-div-icon', html: title});
	var marker = L.marker(
		[i.properties.geo_point_2d.lat, i.properties.geo_point_2d.lon], {
		icon: myIcon,
		title: i.properties.kan_code,
		zIndex: 1000,
	}
	).addTo(map).on('click', onClick2);
	clickmarkers.push(marker);
}

function getColor(d) {
    return 	province[d] == 'C' ? confirmedColor  :
			province[d] == 'W' ? workedColor :
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
	createMarker(feature);
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
        fillColor: getColor(feature.properties.kan_code),
        weight: 1,
        opacity: 1,
        color: 'white',
        // dashArray: '3',
        fillOpacity: 0.6
    };
}

function onClick(e) {
	var marker = e.target;
	displayContactsOnMap($("#helvetiamap"),marker.feature.properties.kan_code[0], $('#band2').val(), 'All', 'All', $('#mode').val(), 'helvetia');
}

function onClick2(e) {
	var marker = e.target;
	displayContactsOnMap($("#helvetiamap"), marker.options.title[0], $('#band2').val(), 'All', 'All', $('#mode').val(), 'helvetia');
}
