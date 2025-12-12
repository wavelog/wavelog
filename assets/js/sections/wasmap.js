let osmUrl = tileUrl;
let was;
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

const states =  'AK,AL,AR,AZ,CA,CO,CT,DE,FL,GA,HI,IA,ID,IL,IN,KS,KY,LA,MA,MD,ME,MI,MN,MO,MS,MT,NC,ND,NE,NH,NJ,NM,NV,NY,OH,OK,OR,PA,RI,SC,SD,TN,TX,UT,VA,VT,WA,WI,WV,WY';

const wasmarkers = [
	[ "66", "-153" ], //AK
	[ "32.9", "-87" ], //AL
	[ "35.2", "-93" ], //AR
	[ "35.2", "-112" ], //AZ
	[ "37", "-120" ], //CA
	[ "39.2", "-105.7" ], //CO
	[ "41.7", "-72.7" ], //CT
	[ "38.7", "-75.5" ], //DE
	[ "30", "-82" ], //FL
	[ "32.9", "-84" ], //GA
	[ "19.5", "-159.5" ], // HI
	[ "42.5", "-94" ], //IA
	[ "44", "-115" ], //ID
	[ "40.5", "-89" ], //IL
	[ "40.5", "-86.5" ], //IN
	[ "38.7", "-99" ], //KS
	[ "38", "-85" ], //KY
	[ "32", "-93" ], //LA
	[ "42.5", "-71.7" ], //MA
	[ "39.5", "-77" ], //MD
	[ "46", "-69" ], //ME
	[ "43.5", "-85" ], //MI
	[ "47.2", "-95" ], //MN
	[ "38.7", "-93" ], //MO
	[ "32.6", "-89.8" ], //MS
	[ "47.2", "-108.5" ], //MT
	[ "35.5", "-79" ], //NC
	[ "47.7", "-100.5" ], //ND
	[ "41.7", "-100.5" ], //NE
	[ "44", "-71.7" ], //NH
	[ "40.7", "-74.6" ], //NJ
	[ "35.2", "-107" ], //NM
	[ "39.2", "-117.5" ], //NV
	[ "43.3", "-75" ], //NY
	[ "40.5", "-83" ], //OH
	[ "36", "-97.7" ], //OK
	[ "44", "-121" ], //OR
	[ "41", "-78" ], //PA
	[ "41.7", "-71.6" ], //RI
	[ "34", "-81" ], //SC
	[ "44.7", "-100.5" ], //SD
	[ "36", "-86.5" ], //TN
	[ "32", "-100" ], //TX
	[ "39.2", "-112" ], //UT
	[ "37.5", "-79" ], //VA
	[ "44", "-72.7" ], //VT
	[ "47.6", "-121" ], //WA --
	[ "45", "-89.7" ], //WI
	[ "38.7", "-81" ], //WV
	[ "43", "-108" ], //WY
  ];

  var statearray = states.split(",");

function load_was_map() {
    $('.nav-tabs a[href="#wasmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/was_map',
        type: 'post',
        data: {
            band: $('#band2').val(),
            mode: $('#mode').val(),
            worked: +$('#worked').prop('checked'),
            confirmed: +$('#confirmed').prop('checked'),
            notworked: +$('#notworked').prop('checked'),
            qsl: +$('#qsl').prop('checked'),
            lotw: +$('#lotw').prop('checked'),
            qrz: +$('#qrz').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
        },
        success: function(data) {
            was = data;
            // Fetch main 48 states + AK (DXCC 6) + HI (DXCC 110) separately
            Promise.all([
                fetch(base_url + 'assets/json/geojson/states_291.geojson').then(r => r.json()),
                fetch(base_url + 'assets/json/geojson/states_6.geojson').then(r => r.json()),
                fetch(base_url + 'assets/json/geojson/states_110.geojson').then(r => r.json())
            ]).then(([states48, ak, hi]) => {
                // Inject AK and HI features into the FeatureCollection
                states48.features.push(ak.features[0], hi.features[0]);
                // Remove DC from 48 states pulled from geojson which are 49 states actually
                i=0;
                for (k in states48.features) {
                   if (states48.features[k].id == 'DC') {
                      states48.features.splice(i, 1);
                      break;
                   }
                   i++;
                }
                load_was_map2(data, states48);
            });
        },
        error: function() {

        },
    });
}

function load_was_map2(data, mapcoordinates) {




   // If map is already initialized
  var container = L.DomUtil.get('wasmap');

  if(container != null){
	  container._leaflet_id = null;
	  container.remove();
	  $("#wasmaptab").append('<div id="wasmap"></div>');
  }

  map = new L.Map('wasmap', {
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
		mapColor = unworkedColor;
		workednotconfirmed++;
		notworked--;
		}
	}



	/*for (var i = 0; i < wasmarkers.length; i++) {
		var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1.5em; font-weight: 900;">' + (Number(i)+Number(1)) + '</font></span>';
		var myIcon = L.divIcon({className: 'my-div-icon', html: title});
		L.marker(
			[wasmarkers[i][0], wasmarkers[i][1]], {
				icon: myIcon,
				title: (Number(i)+Number(1)),
				zIndex: 1000,
			}
		).addTo(map).on('click', onClick);
	}*/

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
	var displayText = '';
	if (props) {
		var stateName = props.name;
		var stateCode = props.code;
		// Show that DC and MD are combined
		if (props.code === 'DC') {
			displayText = '<b>DC (' + lang_inc + ' MD) - ' + stateName + '</b>';
		} else if (props.code === 'MD') {
			displayText = '<b>MD (' + lang_inc + ' DC) - ' + stateName + '</b>';
		} else {
			displayText = '<b>' + stateCode + ' - ' + stateName + '</b>';
		}
	} else {
		displayText = lang_hover_over_a_state;
	}
    this._div.innerHTML = '<h4>' + lang_usa_state + '</h4>' + displayText;
};

info.addTo(map);

  geojson = L.geoJson(mapcoordinates, {style: style, onEachFeature: onEachFeature}).addTo(map);

  map.setView([52, -100], 3);

  addMarkers();

  map.on('zoomend', function() {
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
		if (zoom < 4) {
			if (statearray[i] != 'RI' && statearray[i] != 'DE' && statearray[i] != 'VT' && statearray[i] != 'CT' && statearray[i] != 'NH' && statearray[i] != 'MA' && statearray[i] != 'MD') {
				createMarker(i);
			}
		} else {
			createMarker(i);
		}
	}
}

function createMarker(i) {
	var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1em; font-weight: 900;">' + (statearray[i]) + '</font></span>';
	var myIcon = L.divIcon({className: 'my-div-icon', html: title});
	var marker = L.marker(
		[wasmarkers[i][0], wasmarkers[i][1]], {
			icon: myIcon,
			title: (statearray[i]),
			zIndex: 1000,
		}
	).addTo(map).on('click', onClick2);
	clickmarkers.push(marker);
}

function getColor(d) {
	// DC is combined with MD for WAS award
	var stateCode = (d === 'DC') ? 'MD' : d;
    return 	was[stateCode] == 'C' ? confirmedColor :
			was[stateCode] == 'W' ? workedColor :
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
  var res = marker.feature.id;
  // DC is combined with MD - search for MD when clicking DC
  if (res === 'DC') {
    res = 'MD';
  }
  displayContactsOnMap($("#wasmap"), res, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAS');
}

function onClick2(e) {
    var marker = e.target;
    displayContactsOnMap($("#wasmap"), marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'WAS')
}
