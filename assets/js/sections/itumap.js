var osmUrl = tileUrl;
var ituz;
var geojson;
var map;
var info;

function load_itu_map() {
    $('.nav-tabs a[href="#itumaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/itu_map',
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
			ituz = data;
            load_itu_map2(data);
        },
        error: function() {

        },
    });
}

function load_itu_map2(data) {

	const zonemarkers = [
		["60","-160"],
		["55","-125"],
		["55","-100"],
		["55","-78"],
		["73","-40"],
		["40","-119"],
		["40","-100"],
		["40","-80"],
		["55","-60"],
		["20","-102"],
		["21","-75"],
		["-3","-72"],
		["-5","-45"],
		["-30","-65"],
		["-25","-45"],
		["-50","-65"],
		["61","-26"],
		["70","10"],
		["70","40"],
		["70","62.5"],
		["70","82.5"],
		["70","100"],
		["70","122.5"],
		["70","142.5"],
		["70","162.5"],
		["70","180"],
		["52","2"],
		["45","18"],
		["53","36"],
		["53","62.5"],
		["53","82.5"],
		["53","100"],
		["53","122.5"],
		["53","142"],
		["55","160"],
		["35","-25"],
		["35","0"],
		["27.5","22.5"],
		["27","42"],
		["32","56"],
		["10","75"],
		["39","82.5"],
		["33","100"],
		["33","118"],
		["33","140"],
		["15","-10"],
		["12.5","22"],
		["5","40"],
		["15","100"],
		["10","120"],
		["-4","150"],
		["-7","17"],
		["-12.5","45"],
		["-2","115"],
		["-20","140"],
		["-20","170"],
		["-30","24"],
		["-25","120"],
		["-40","140"],
		["-40","170"],
		["15","-170"],
		["-15","-170"],
		["-15","-135"],
		["10","140"],
		["10","162"],
		["-23","-11"],
		["-70","10"],
		["-47.5","60"],
		["-70","70"],
		["-70","130"],
		["-70","-170"],
		["-70","-110"],
		["-70","-050"],
		["-82.5","0"],
		["82.5","0"],
		["40","-150"],
		["15","-135"],
		["-15","-95"],
		["-40","-160"],
		["-40","-125"],
		["-40","-90"],
		["50","-30"],
		["25","-47.5"],
		["-45","-40"],
		["-45","10"],
		["-25","70"],
		["-25","95"],
		["-50","95"],
		["-54","140"],
		["39","165"]
	];

    // If map is already initialized
    var container = L.DomUtil.get('itumap');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#itumaptab").append('<div id="itumap" class="map-leaflet" ></div>');
    }

    map = new L.Map('itumap', {
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
		this._div.innerHTML = '<h4>'+lang_general_hamradio_ituzone+'</h4>' +  (props ? createContentITU(props.itu_zone_number, props.itu_zone_name) : lang_hover_over_a_zone);
		};

	info.addTo(map);
	geojson = L.geoJson(ituzonestuff, {style: style, onEachFeature: onEachFeature}).addTo(map);

    map.setView([20, 0], 2);
}

function getColor(d) {
    return 	ituz[d-1] == 'C' ? 'green'  :
			ituz[d-1] == 'W' ? 'orange' :
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
        fillColor: getColor(feature.properties.itu_zone_number),
        weight: 1,
        opacity: 1,
        color: 'white',
        // dashArray: '3',
        fillOpacity: 0.6
    };
}

function onClick(e) {
    var marker = e.target;
    displayContactsOnMap($("#itumap"),marker.options.title, $('#band2').val(), 'All', 'All', $('#mode').val(), 'ITU');
}

function onClick2(e) {
	zoomToFeature(e);
	console.log(e);
    var marker = e.target;
    displayContactsOnMap($("#itumap"),marker.feature.properties.itu_zone_number, $('#band2').val(), 'All', 'All', $('#mode').val(), 'ITU');
}

function createContentITU(zone, text){
	var ctemp, ptemp, rtemp, value, value1;
	var rowspan = (text.match(/#/g) || []).length + 1;
	var rows = text.split("#");
	var row = rows[0];
	var col = row.split(":");
	ptemp = (col[0].match(/!/g) || []).length;
	if (ptemp > 1){
		ptemp = 'rowspan="'+ptemp+'" ';
	} else {
		ptemp = '';
	}
	rowspan = (col[1].match(/!/g) || []).length;
	if (rowspan!=0){
		rtemp = 'rowspan="'+rowspan+'" ';
	} else {
		rtemp = '';
	}
	colspan = (col[2].match(/\*/g) || []).length;
	if (colspan != 0) {
		ctemp = 'colspan="'+colspan+'" ';
	} else {
		ctemp = '';
	}
	value1 = col[2].replace(/\+/g,'<br>');
	var content = '<table style="border-color: white" class="table-sm table-striped table-bordered">'+
	'<tbody>'+
	'<tr style="vertical-align: middle;">'+
	'<td '+ptemp+' style="font-size: x-large; color: #ff0000;text-align: center;">'+zone+'</td>'+
	'<td '+rtemp+'style="vertical-align: top;font-size: small;">'+col[1].slice(rowspan)+'</td>'+
	'<td '+ctemp+'style="vertical-align: top;font-size: small;">'+value1.slice(colspan)+'</td>'+
	'</tr>';

	for (var i = 1; i < rows.length; i++) {
		var row = rows[i];
		var col = row.split(":");
		rowspan = (row.match(/!/g) || []).length;
		if (rowspan!=0){
			rtemp = 'rowspan="'+rowspan+'" ';
		}else{
			rtemp = '';
		}
		colspan = (row.match(/\*/g) || []).length;
		if (colspan!=0){
			ctemp = 'colspan="'+colspan+'" ';
		}else{
			ctemp = '';
		}
		value1 = col[1].replace(/\+/g,'<br>');
		content += '<tr style="font-size: small;">'+
					'<td '+rtemp+'style="vertical-align: top;">'+col[0].slice(rowspan)+'</td>'+
					'<td '+ctemp+'style="vertical-align: top;">'+value1.slice(colspan)+'</td>'+
					'</tr>';
	}

	content += '</tbody></table>';

	return content;
}


$(document).ready(function(){
	$('.tableitu').DataTable({
		"pageLength": 25,
		responsive: false,
		ordering: false,
		"scrollY":        "400px",
		"scrollCollapse": true,
		"paging":         false,
		"scrollX": true,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		buttons: [
			'csv'
		]
	});

	$('.tablesummary').DataTable({
		info: false,
		searching: false,
		ordering: false,
		"paging":         false,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		buttons: [
			'csv'
		]
	});

	// change color of csv-button if dark mode is chosen
	if (isDarkModeTheme()) {
		$(".buttons-csv").css("color", "white");
	}
});
