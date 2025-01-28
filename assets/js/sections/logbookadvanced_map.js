var clicklines = [];
var map;
var maidenhead;
var geojson;
var itugeojson;
var zonemarkers = [];
var ituzonemarkers = [];
var nightlayer;

var defaultlinecolor = 'blue';

if (isDarkModeTheme()) {
	defaultlinecolor = 'red';
}

var iconsList = { 'qso': { 'color': defaultlinecolor, 'icon': 'fas fa-dot-circle', 'iconSize': [5, 5] }, 'qsoconfirm': { 'color': defaultlinecolor, 'icon': 'fas fa-dot-circle', 'iconSize': [5, 5] } };

var stationIcon = L.divIcon({ className: 'cspot_station', iconSize: [5, 5], iconAnchor: [5, 5]});
var qsoIcon = L.divIcon({ className: 'cspot_qso', iconSize: [5, 5], iconAnchor: [5, 5] }); //default (fas fa-dot-circle red)
var qsoconfirmIcon = L.divIcon({ className: 'cspot_qsoconfirm', iconSize: [5, 5], iconAnchor: [5, 5] });
var redIconImg = L.icon({ iconUrl: icon_dot_url, iconSize: [5, 5] }); // old //

function toggleCqZones(bool) {
	if(!bool) {
		zonemarkers.forEach(function (item) {
			map.removeLayer(item);
		});
		if (geojson != undefined) {
			map.removeLayer(geojson);
		}
	} else {
		geojson = L.geoJson(zonestuff, {style: style}).addTo(map);
		for (var i = 0; i < cqzonenames.length; i++) {

			var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1.5em; font-weight: 900;">' + (Number(i)+Number(1)) + '</font></span>';
			var myIcon = L.divIcon({className: 'my-div-icon', html: title});

			var marker = L.marker(
				[cqzonenames[i][0], cqzonenames[i][1]], {
					icon: myIcon,
					title: (Number(i)+Number(1)),
					zIndex: 1000,
				}
			).addTo(map);
			zonemarkers.push(marker);
		}
	}
}

function toggleItuZones(bool) {
	if(!bool) {
		ituzonemarkers.forEach(function (item) {
			map.removeLayer(item);
		});
		if (itugeojson != undefined) {
			map.removeLayer(itugeojson);
		}
	} else {
		itugeojson = L.geoJson(ituzonestuff, {style: style}).addTo(map);
		for (var i = 0; i < ituzonenames.length; i++) {

			var title = '<span class="grid-text" style="cursor: default"><font style="color: \'white\'; font-size: 1.5em; font-weight: 900;">' + (Number(i)+Number(1)) + '</font></span>';
			var myIcon = L.divIcon({className: 'my-div-icon', html: title});

			var marker = L.marker(
				[ituzonenames[i][0], ituzonenames[i][1]], {
					icon: myIcon,
					title: (Number(i)+Number(1)),
					zIndex: 1000,
				}
			).addTo(map);
			ituzonemarkers.push(marker);
		}
	}
}

function toggleNightShadow(bool) {
	if(!bool) {
		map.removeLayer(nightlayer);
	} else {
		nightlayer.addTo(map);
	}
}

function style(feature) {
	var bordercolor = "black";
	if (isDarkModeTheme()) {
		bordercolor = "white";
	}
	return {
		fillColor: "white",
		fillOpacity: 0,
		opacity: 0.65,
		color: bordercolor,
		weight: 1,
	};
}

function clearLines() {
	clicklines.forEach(function (item) {
		map.removeLayer(item);
	});
}

function addLines() {
	clicklines.forEach(function (item) {
		map.addLayer(item);
	});
}

function toggleFunction(bool) {
	if(bool) {
		addLines();
	} else {
		clearLines();
	}
};

function toggleGridsquares(bool) {
	if(!bool) {
		map.removeLayer(maidenhead);
	} else {
		maidenhead.addTo(map);
	}
};

const ituzonenames = [
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

function loadMap(data, iconsList) {
	$('#mapButton').prop("disabled", false).removeClass("running");
	var osmUrl = tileUrl;
	var osmAttrib = option_map_tile_server_copyright;
	// If map is already initialized
	var container = L.DomUtil.get('advancedmap');

	var bounds = L.latLngBounds()

	if(container != null){
		container._leaflet_id = null;
		container.remove();
		$(".coordinates").remove();
		$("#lba_div").append('<div id="advancedmap" class="map-leaflet"></div>');
		$("#lba_div").append('<div class="coordinates d-flex">' +
        '<div class="cohidden">' + lang_gen_hamradio_latitude + '&nbsp;</div>' +
        '<div class="cohidden col-auto text-success fw-bold" id="latDeg"></div>' +
        '<div class="cohidden">' + lang_gen_hamradio_longitude + '&nbsp;</div>' +
        '<div class="cohidden col-auto text-success fw-bold" id="lngDeg"></div>' +
        '<div class="cohidden">' + lang_gen_hamradio_gridsquare + '&nbsp;</div>' +
        '<div class="cohidden col-auto text-success fw-bold" id="locator"></div>' +
        '<div class="cohidden">' + lang_gen_hamradio_distance + '&nbsp;</div>' +
        '<div class="cohidden col-auto text-success fw-bold" id="distance"></div>' +
        '<div class="cohidden">' + lang_gen_hamradio_bearing + '&nbsp;</div>' +
        '<div class="cohidden col-auto text-success fw-bold" id="bearing"></div>' +
		'<div class="cohidden">' + lang_gen_hamradio_cqzone + '&nbsp;</div>' +
		'<div class="cohidden col-auto text-success fw-bold" id="cqzonedisplay"></div>' +
		'<div class="cohidden">' + lang_gen_hamradio_ituzone + '&nbsp;</div>' +
		'<div class="cohidden col-auto text-success fw-bold" id="ituzonedisplay"></div>' +
		'</div>');
		$('.cohidden').show();
		set_advancedmap_height();
	}

	map = new L.Map('advancedmap', {
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
	});

	var osm = L.tileLayer(
		osmUrl,
		{
			attribution: osmAttrib,
			maxZoom: 18,
			zoom: 3,
            minZoom: 2,
		}
	).addTo(map);

	map.setView([30, 0], 1.5);

	var redIcon = L.icon({
		iconUrl: icon_dot_url,
		iconSize: [10, 10], // size of the icon
	});

	var counter = 0;

	clicklines = [];
	$.each(data, function(k, v) {
		counter++;
		// Need to fix so that marker is placed at same place as end of line, but this only needs to be done when longitude is < -170
		if (this.latlng2[1] < -170) {
			this.latlng2[1] =  parseFloat(this.latlng2[1])+360;
		}
		if (this.latlng1[1] < -170) {
			this.latlng1[1] =  parseFloat(this.latlng1[1])+360;
		}

		if ((this.latlng1[1] - this.latlng2[1]) < -180) {
			this.latlng2[1] =  parseFloat(this.latlng2[1]) -360;
		} else if ((this.latlng1[1] - this.latlng2[1]) > 180) {
			this.latlng2[1] =  parseFloat(this.latlng2[1]) +360;
		}

		var popupmessage = createContentMessage(this);
		var popupmessage2 = createContentMessageDx(this);

		var marker = L.marker([this.latlng1[0], this.latlng1[1]], {icon: stationIcon}, {closeOnClick: false, autoClose: false}).addTo(map).bindPopup(popupmessage);

		marker.on('mouseover',function(ev) {
			ev.target.openPopup();
		});
		let lat_lng = [this.latlng1[0], this.latlng1[1]];
		bounds.extend(lat_lng);

		if (this.confirmed && iconsList.qsoconfirm.icon !== "0") {
			var marker2 = L.marker([this.latlng2[0], this.latlng2[1]], {icon: qsoconfirmIcon},{closeOnClick: false, autoClose: false}).addTo(map).bindPopup(popupmessage2);
			linecolor = iconsList.qsoconfirm.color;
		} else {
			var marker2 = L.marker([this.latlng2[0], this.latlng2[1]], {icon: qsoIcon},{closeOnClick: false, autoClose: false}).addTo(map).bindPopup(popupmessage2);
			linecolor = iconsList.qso.color;
		}

		marker2.on('mouseover',function(ev) {
			ev.target.openPopup();
		});

		lat_lng = [this.latlng2[0], this.latlng2[1]];
		bounds.extend(lat_lng);

		const multiplelines = [];
		multiplelines.push(
			new L.LatLng(this.latlng1[0], this.latlng1[1]),
			new L.LatLng(this.latlng2[0], this.latlng2[1])
		)

		const geodesic = L.geodesic(multiplelines, {
			weight: 1,
			opacity: 1,
			color: linecolor,
			wrap: false,
			steps: 100
		}).addTo(map);

		clicklines.push(geodesic);
	});

	/*Legend specific*/
    var legend = L.control({ position: "topright" });

    legend.onAdd = function(map) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += '<div>' + counter + " QSO" +(counter > 1 ? 's' : '') +" plotted</div>";
		div.innerHTML += '<input type="checkbox" onclick="toggleFunction(this.checked)" ' + (typeof path_lines !== 'undefined' && path_lines ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_pathlines + '</span><br>';
		div.innerHTML += '<input type="checkbox" onclick="toggleGridsquares(this.checked)" ' + (typeof gridsquare_layer !== 'undefined' && gridsquare_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_gridsquares + '</span><br>';
		div.innerHTML += '<input type="checkbox" onclick="toggleCqZones(this.checked)" ' + (typeof cqzones_layer !== 'undefined' && cqzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_cq_zones + '</span><br>';
		div.innerHTML += '<input type="checkbox" onclick="toggleItuZones(this.checked)" ' + (typeof ituzones_layer !== 'undefined' && ituzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_itu_zones + '</span><br>';
		div.innerHTML += '<input type="checkbox" onclick="toggleNightShadow(this.checked)" ' + (typeof nightshadow_layer !== 'undefined' && nightshadow_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_nightshadow + '</span>';
        return div;
    };

    legend.addTo(map);

	maidenhead = L.maidenheadqrb().addTo(map);
	nightlayer = L.terminator().addTo(map);


	if (bounds && bounds._southWest && bounds._northEast) {
        map.fitBounds(bounds);
    }

	$.each(iconsList, function (icon, data) {
		$('#advancedmap' + ' .cspot_' + icon).addClass(data.icon).css("color", data.color);
	});

	var printer = L.easyPrint({
		tileLayer: osm,
		sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
		filename: 'Wavelog',
		exportOnly: true,
		hideControlContainer: true
	}).addTo(map);

	map.on('mousemove', onMapMove);

	if (typeof gridsquare_layer !== 'undefined') {
		toggleGridsquares(gridsquare_layer);
	} else {
		toggleGridsquares(false);
	}

	if (typeof path_lines !== 'undefined') {
		toggleFunction(path_lines);
	} else {
		toggleFunction(false);
	}

	if (typeof cqzones_layer !== 'undefined') {
		toggleCqZones(cqzones_layer);
	} else {
		toggleCqZones(false);
	}

	if (typeof ituzones_layer !== 'undefined') {
		toggleItuZones(ituzones_layer);
	} else {
		toggleItuZones(false);
	}

	if (typeof nightshadow_layer !== 'undefined') {
		toggleNightShadow(nightshadow_layer);
	} else {
		toggleNightShadow(false);
	}
}

function createContentMessage(qso) {
	var table = '<table><tbody>' +
	'<tr>' +
	'<td>' +
	'Station callsign: ' + qso.mycallsign.replaceAll('0', 'Ø') +
	"</td></tr>" +
	'<tr>' +
	'<td>' +
	'Gridsquare: ' + qso.mygridsquare +
	"</td></tr>";
	return (table += "</tbody></table>");
}

function createContentMessageDx(qso) {
	var table = '<table><tbody>' +
	'<tr>' +
	'<td colspan=2><div class="big-flag">';
	if (qso.dxccFlag != '') {
		table += '<div class="flag">' + qso.dxccFlag + '</div>';
	}
	table += '<a id="edit_qso" href="javascript:displayQso('+qso.id+')">'+qso.callsign.replaceAll('0', 'Ø')+'</a></div>';
	table += '</td>' +
	'</tr>' +
	'<tr>' +
	'<td>Date/Time</td>' +
	'<td>' + qso.datetime + '</td>' +
	'</tr>' +
	'<tr>';
	if (qso.satname != "") {
		table += '<td>Band</td>' +
		'<td>SAT ' + qso.satname
		if (qso.orbit != "") {
			table += ' (' + qso.orbit + ') '
		}
		table += '</td>' +
		'</tr>' +
		'<tr>';
	} else {
		table += '<td>Band</td>' +
		'<td>' + qso.band + '</td>' +
		'</tr>' +
		'<tr>';
	}
	table += '<td>Mode</td>' +
	'<td>' + qso.mode + '</td>' +
	'</tr>' +
	'<tr>';
	if (qso.gridsquare != undefined) {
		table += '<td>Gridsquare</td>' +
		'<td>' + qso.gridsquare + '</td>' +
		'</tr>';
	}
	if (qso.distance != undefined) {
		table += '<td>Distance</td>' +
		'<td>' + qso.distance + '</td>' +
		'</tr>';
	}
	if (qso.bearing != undefined) {
		table += '<td>Bearing</td>' +
		'<td>' + qso.bearing + '</td>' +
		'</tr>';
	}
	return (table += '</tbody></table>');
}

function loadMapOptions(data) {
	let json_mapinfo = user_map_custom;
	if (typeof json_mapinfo.qso !== "undefined") {
		iconsList = json_mapinfo;
	}
	loadMap(data, iconsList)
}

function mapQsos(form) {
	$('#mapButton').prop("disabled", true).addClass("running");

	var id_list=[];
	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;

	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
		unselectQsoID(id);
	});

	$("#qsoList").attr("Hidden", true);
	$("#qsoList_wrapper").attr("Hidden", true);
	$("#qsoList_info").attr("Hidden", true);

	amap = $('#advancedmap').val();
	if (amap == undefined) {
		$("#lba_div").append('<div id="advancedmap" class="map-leaflet"></div>');
	}

		if (id_list.length > 0) {
			$.ajax({
				url: base_url + 'index.php/logbookadvanced/mapSelectedQsos',
				type: 'post',
				data: {
					ids: id_list,
					de: $('#de').val()
				},
				success: function(data) {
					loadMapOptions(data);
				},
				error: function() {
					$('#mapButton').prop("disabled", false).removeClass("running");
				},
			});
		} else {
			$.ajax({
				url: base_url + 'index.php/logbookadvanced/mapQsos',
				type: 'post',
				data: {
					dateFrom: form.dateFrom.value,
					dateTo: form.dateTo.value,
					de: $('#de').val(),
					dx: form.dx.value,
					mode: form.mode.value,
					band: form.band.value,
					qslSent: form.qslSent.value,
					qslReceived: form.qslReceived.value,
					qslSentMethod: this.qslSentMethod.value,
					qslReceivedMethod: this.qslReceivedMethod.value,
					iota: form.iota.value,
					dxcc: form.dxcc.value,
					propmode: form.propmode.value,
					gridsquare: form.gridsquare.value,
					state: form.state.value,
					qsoresults: form.qsoresults.value,
					sats: form.sats.value,
					orbits: form.orbits.value,
					cqzone: form.cqzone.value,
					lotwSent: form.lotwSent.value,
					lotwReceived: form.lotwReceived.value,
					eqslSent: form.eqslSent.value,
					eqslReceived: form.eqslReceived.value,
					qslvia: $('[name="qslvia"]').val(),
					sota: form.sota.value,
					pota: form.pota.value,
					operator: form.operator.value,
					wwff: form.wwff.value,
					qslimages: form.qslimages.value,
					continent: form.continent.value,
					contest: form.contest.value,
					comment: form.comment.value
				},
				success: function(data) {
					loadMapOptions(data);
				},
				error: function() {
					$('#mapButton').prop("disabled", false).removeClass("running");
				},
			});
		}
	};

function mapGlobeQsos(form) {
	var container = L.DomUtil.get('advancedmap');
	if(container != null){
		container._leaflet_id = null;
		container.remove();
		$(".coordinates").remove();
	}

	var id_list=[];
	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;

	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
		unselectQsoID(id);
	});

	$("#qsoList").attr("Hidden", true);
	$("#qsoList_wrapper").attr("Hidden", true);
	$("#qsoList_info").attr("Hidden", true);

	amap = $('#advancedmap').val();
	if (amap == undefined) {
		$("#lba_div").append('<div id="advancedmap" class="map-leaflet"></div>');
	}

	if (id_list.length > 0) {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/mapSelectedQsos',
			type: 'post',
			data: {
				ids: id_list,
				de: $('#de').val()
			},
			success: function(data) {
				globemap(data);
			},
			error: function() {

				},
			});
		} else {
			$.ajax({
				url: base_url + 'index.php/logbookadvanced/mapQsos',
				type: 'post',
				data: {
					dateFrom: form.dateFrom.value,
					dateTo: form.dateTo.value,
					de: $('#de').val(),
					dx: form.dx.value,
					mode: form.mode.value,
					band: form.band.value,
					qslSent: form.qslSent.value,
					qslReceived: form.qslReceived.value,
					qslSentMethod: this.qslSentMethod.value,
					qslReceivedMethod: this.qslReceivedMethod.value,
					iota: form.iota.value,
					dxcc: form.dxcc.value,
					propmode: form.propmode.value,
					gridsquare: form.gridsquare.value,
					state: form.state.value,
					qsoresults: form.qsoresults.value,
					sats: form.sats.value,
					orbits: form.orbits.value,
					cqzone: form.cqzone.value,
					lotwSent: form.lotwSent.value,
					lotwReceived: form.lotwReceived.value,
					eqslSent: form.eqslSent.value,
					eqslReceived: form.eqslReceived.value,
					qslvia: $('[name="qslvia"]').val(),
					sota: form.sota.value,
					pota: form.pota.value,
					operator: form.operator.value,
					wwff: form.wwff.value,
					qslimages: form.qslimages.value,
					continent: form.continent.value,
					contest: form.contest.value,
					comment: form.comment.value
				},
				success: function(data) {
					globemap(data);
				},
				error: function() {

			},
		});
	}
};

function globemap(x) {
	globePayArc=[];
	globePayLab=[];
	x.forEach((element) => {
		let OneQsoArc={};
		OneQsoArc.startLat=element.latlng1[0];
		OneQsoArc.startLng=element.latlng1[1];
		OneQsoArc.endLat=element.latlng2[0];
		OneQsoArc.endLng=element.latlng2[1];
		OneQsoArc.name=element.callsign;
		if (element.confirmed) {
			OneQsoArc.color = 'green';
		} else {
			OneQsoArc.color = 'red';
		}
		// OneQsoArc.color = [['red', 'white', 'blue', 'green'][Math.round(Math.random() * 3)], ['red', 'white', 'blue', 'green'][Math.round(Math.random() * 3)]]
		OneQsoArc.altitude=0.15;
		globePayArc.push(OneQsoArc);
		let OneQsoLab={};
		OneQsoLab.lat=element.latlng2[0];
		OneQsoLab.lng=element.latlng2[1];
		OneQsoLab.text=element.callsign;
		globePayLab.push(OneQsoLab);
	});
	renderGlobe(globePayArc,globePayLab);
}

function renderGlobe(arcsData,labelData) {
	Globe()
	.globeImageUrl(base_url + '/assets/images/earth-blue-marble.jpg')
	.pointOfView({ lat: arcsData[0].startLat, lng: arcsData[0].startLng, altitude:1}, 100)
	.labelsData(labelData)
	.arcsData(arcsData)
	.arcColor('color')
	//.arcAltitude('altitude')
	.arcAltitudeAutoScale(.37)
	.arcStroke(.2)
	.arcDashLength(() => .1)
	.arcDashGap(() => 0.01)
	.arcDashAnimateTime(() => 4000 + 500)
	(document.getElementById('advancedmap'))
}

// auto setting of gridmap height
function set_advancedmap_height() {
    //header menu
    var headerNavHeight = $('nav').outerHeight();
    // console.log('nav: ' + headerNavHeight);

    // line with coordinates
    var coordinatesHeight = $('.coordinates').outerHeight();
    // console.log('.coordinates: ' + coordinatesHeight);

    // form for gridsquare map
    var qsoManagerHeight = $('.qso_manager').outerHeight();
    // console.log('.qso_manager: ' + qsoManagerHeight);

    // calculate correct map height
    var advancedMapHeight = window.innerHeight - headerNavHeight - coordinatesHeight - qsoManagerHeight;

    // and set it
    $('#advancedmap').css('height', advancedMapHeight + 'px');
    // console.log('#advancedmap: ' + advancedMapHeight);
}

$(document).ready(function() {
	$(window).resize(function() {
		set_advancedmap_height();
	});
	$('.lba_buttons').click(function() {
        // we need some delay because of the bs collapse menu
        setTimeout(set_advancedmap_height, 400);
    });
});
