let osmUrl = tileUrl;
let province;
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

const states = 'AG,AI,AR,BE,BL,BS,FR,GE,GL,GR,JU,LU,NE,NW,OW,SG,SH,SO,SZ,TG,TI,UR,VD,VS,ZG,ZH';

var statearray = states.split(",");

// Marker positions for Swiss cantons (lat, lon) in order: AG,AI,AR,BE,BL,BS,FR,GE,GL,GR,JU,LU,NE,NW,OW,SG,SH,SO,SZ,TG,TI,UR,VD,VS,ZG,ZH
const helvetiamarkers = [
	[47.40989851746405, 8.156883444500856],   // AG - Aargau
	[47.31718896655919, 9.41640047244825],    // AI - Appenzell Innerrhoden
	[47.363293383981856, 9.28984245688638],   // AR - Appenzell Ausserrhoden
	[46.822605103394274, 7.624467292741663],  // BE - Bern
	[47.4484793116557, 7.8143330908944785],   // BL - Basel-Landschaft
	[47.558019833571144, 7.592255668016036],  // BS - Basel-Stadt
	[46.71876679188163, 7.073999745271962],   // FR - Fribourg
	[46.220484472491464, 6.133009297431582],  // GE - Genève
	[46.981219189054755, 9.065859068284496],  // GL - Glarus
	[46.65606638447119, 9.628623630522638],   // GR - Graubünden
	[47.350756417551864, 7.156197798276755],  // JU - Jura
	[47.067905658071375, 8.11032401543988],   // LU - Luzern
	[46.99559615546845, 6.780254730068154],   // NE - Neuchâtel
	[46.92683016158667, 8.405341758081926],   // NW - Nidwalden
	[46.864910030388085, 8.205700891697775],  // OW - Obwalden
	[47.14150556683778, 9.356125459937824],   // SG - St. Gallen
	[47.72357745796095, 8.55723783458037],    // SH - Schaffhausen
	[47.328541129677976, 7.660459844936144],  // SO - Solothurn
	[47.061777487306394, 8.756666184200009],  // SZ - Schwyz
	[47.568674460332645, 9.09287048949661],   // TG - Thurgau
	[46.29606118571602, 8.80855530833014],    // TI - Ticino
	[46.77202338593801, 8.628869511456779],   // UR - Uri
	[46.57023364793707, 6.6575945953970646],  // VD - Vaud
	[46.2093559529086, 7.60594055741468],     // VS - Valais
	[47.15725299289371, 8.537323457596635],   // ZG - Zug
	[47.41289379832796, 8.655060447982459],   // ZH - Zürich
];

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
            $.getJSON(base_url + 'assets/json/geojson/states_287.geojson', function(mapcoordinates) {
                load_helvetia_map2(data, mapcoordinates);
            });
        },
        error: function() {

        },
    });
}

function load_helvetia_map2(data, mapcoordinates) {

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
    this._div.innerHTML = '<h4>' + lang_switzerland_canton + '</h4>' +  (props ? '<b>' + props.code + ' - ' + props.name + '</b><br />' : lang_hover_over_a_canton);
};

info.addTo(map);

geojson = L.geoJSON (mapcoordinates, {
	style: style,
	onEachFeature: onEachFeature,
}).addTo(map);


map.setView([46.8, 8.4], 8);

  addMarkers();

  map.on('zoomed', function() {
    clearMarkers();
    addMarkers();
  });

  var layerControl = new L.Control.Layers(null, { [lang_general_gridsquares]: maidenhead = L.maidenhead() }).addTo(map);
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
		[helvetiamarkers[i][0], helvetiamarkers[i][1]], {
		icon: myIcon,
		title: statearray[i],
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
	var marker = e.target;
	displayContactsOnMap($("#helvetiamap"),marker.feature.properties.code, $('#band2').val(), 'All', 'All', $('#mode').val(), 'helvetia');
}

function onClick2(e) {
	var marker = e.target;
	displayContactsOnMap($("#helvetiamap"), marker.options.title[0], $('#band2').val(), 'All', 'All', $('#mode').val(), 'helvetia');
}
