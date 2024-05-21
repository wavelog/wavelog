var osmUrl = tileUrl;
var cqz;
var geojson;
var map;
var info;

function load_cq_map() {
    $('.nav-tabs a[href="#cqmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/cq_map',
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
			cqz = data;
            load_cq_map2(data);
        },
        error: function() {

        },
    });
}

function load_cq_map2(data) {

  const zonemarkers = [
      [ "75", "-140" ],
      [ "70", "-82.5" ],
      [ "45", "-125" ],
      [ "45", "-100" ],
      [ "45", "-65" ],
      [ "25.5", "-115" ],
      [ "14.5", "-90" ],
      [ "22", "-60" ],
      [ "11.5", "-70" ],
      [ "-5", "-100" ],
      [ "-9", "-45" ],
      [ "-45", "-106" ],
      [ "-45", "-55" ],
      [ "52", "-14" ],
      [ "46", "11" ],
      [ "60", "35" ],
      [ "55", "65" ],
      [ "70", "90" ],
      [ "70", "150" ],
      [ "42", "29" ],
      [ "28", "53" ],
      [ "6", "75" ],
      [ "44", "93" ],
      [ "33", "110" ],
      [ "38", "134" ],
      [ "16", "100" ],
      [ "15", "140" ],
      [ "0", "125" ],
      [ "-25", "115" ],
      [ "-25", "145" ],
      [ "15", "-165" ],
      [ "-25", "-165" ],
      [ "32", "-26" ],
      [ "25", "25.5" ],
      [ "15", "-6" ],
      [ "-5", "-6" ],
      [ "6", "51" ],
	  [ "-45", "8" ],
      [ "-25", "55"],
      [  "78", "-10"],
    ];

    // If map is already initialized
    var container = L.DomUtil.get('cqmap');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#cqmaptab").append('<div id="cqmap" class="map-leaflet" ></div>');
    }

    map = new L.Map('cqmap', {
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

    var notworked = zonemarkers.length;
    var confirmed = 0;
    var workednotconfirmed = 0;

	for (var i = 0; i < zonemarkers.length; i++) {
        var mapColor = 'red';

        if (data[i] == 'C') {
            mapColor = 'green';
            confirmed++;
            notworked--;
        }
        if (data[i] == 'W') {
			mapColor = 'orange';
			workednotconfirmed++;
			notworked--;
        }

        var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1.5em; font-weight: 900;">' + (Number(i)+Number(1)) + '</font></span>';
        var myIcon = L.divIcon({className: 'my-div-icon', html: title});

        L.marker(
            [zonemarkers[i][0], zonemarkers[i][1]], {
                icon: myIcon,
                title: (Number(i)+Number(1)),
                zIndex: 1000,
            }
        ).addTo(map).on('click', onClick);
    }


	map.setView([52, -100], 3);
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
		this._div.innerHTML = '<h4>CQ Zone</h4>' +  (props ?
			'<b>' + props.cq_zone_name + ' ' + props.cq_zone_number + '</b><br />' : 'Hover over a zone');
		};

	info.addTo(map);
	geojson = L.geoJson(zonestuff, {style: style, onEachFeature: onEachFeature}).addTo(map);

    map.setView([20, 0], 2);
}

function getColor(d) {
    return 	cqz[d-1] == 'C' ? 'green'  :
			cqz[d-1] == 'W' ? 'orange' :
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
        click: onClick2
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
        fillColor: getColor(feature.properties.cq_zone_number),
        weight: 1,
        opacity: 1,
        color: 'white',
        // dashArray: '3',
        fillOpacity: 0.6
    };
}

function onClick(e) {
    var marker = e.target;
    displayContactsOnMap($("#cqmap"),marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'CQZone');
}

function onClick2(e) {
	zoomToFeature(e);
	console.log(e);
    var marker = e.target;
    displayContactsOnMap($("#cqmap"),marker.feature.properties.cq_zone_number, $('#band2').val(), 'All', 'All', $('#mode').val(), 'CQZone');
}
