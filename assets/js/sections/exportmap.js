var zonemarkers = [];
var clicklines = [];

var iconsList = { 'qso': { 'color': defaultlinecolor, 'icon': 'fas fa-dot-circle', 'iconSize': [5, 5] }, 'qsoconfirm': { 'color': defaultlinecolor, 'icon': 'fas fa-dot-circle', 'iconSize': [5, 5] } };

var stationIcon = L.divIcon({ className: 'cspot_station', iconSize: [5, 5], iconAnchor: [5, 5]});
var qsoIcon = L.divIcon({ className: 'cspot_qso', iconSize: [5, 5], iconAnchor: [5, 5] }); //default (fas fa-dot-circle red)
var qsoconfirmIcon = L.divIcon({ className: 'cspot_qsoconfirm', iconSize: [5, 5], iconAnchor: [5, 5] });
var redIconImg = L.icon({ iconUrl: icon_dot_url, iconSize: [5, 5] }); // old //

var defaultlinecolor = 'blue';

if (isDarkModeTheme()) {
	defaultlinecolor = 'red';
}

$(document).ready(function () {
	mapQsos();
});

function mapQsos() {
	// const queryString = window.location.search;
	// const urlParams = new URLSearchParams(queryString);
	// const slug = urlParams.get('slug');
	// const qsocount = urlParams.get('qsocount');
	// const showgrid = urlParams.get('showgrid');
	// const showcq = urlParams.get('showcq');
	// const band = urlParams.get('band');
	// const showlines = urlParams.get('showlines');
	let iconsList;

	$.ajax({
		url: base_url + 'index.php/visitor/get_map_custom',
		type: 'POST',
		dataType: 'json',
		data: {
			slug: slug,
		},
		error: function () {
		},
		success: function (json_mapinfo) {
			if (typeof json_mapinfo.qso !== "undefined") {
				iconsList = json_mapinfo;
			}
			loadQsos(slug, iconsList);
		}
	});


};

function loadQsos(slug, iconsList) {
	$.ajax({
		url: base_url + 'index.php/visitor/mapqsos',
		type: 'post',
		data: {
			slug: slug,
			qsocount: iconsList.qsocount,
			band: iconsList.band
		},
		success: function(data) {

			loadMap(data, iconsList);
		},
		error: function() {
		},
	});
}

function loadMap(data, iconsList) {
	var osmUrl=tileUrl;
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	// If map is already initialized
	var container = L.DomUtil.get('exportmap');

	var bounds = L.latLngBounds()

	if(container != null){
		container._leaflet_id = null;
		container.remove();
		$("body").append('<div id="exportmap" class="map-leaflet"></div>');
	}

	map = new L.Map('exportmap', {
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
	});

	var osm = L.tileLayer(
		osmUrl,
		{
			attribution: '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
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

		if (this.latlng2[1] < -170) {
			this.latlng2[1] =  parseFloat(this.latlng2[1])+360;
		}
		if (this.latlng1[1] < -170) {
			this.latlng1[1] =  parseFloat(this.latlng1[1])+360;
		}

		var marker = L.marker([this.latlng1[0], this.latlng1[1]], {icon: stationIcon}, {closeOnClick: false, autoClose: false}).addTo(map);

		// var marker2 = L.marker([this.latlng2[0], this.latlng2[1]], {icon: redIcon},{closeOnClick: false, autoClose: false}).addTo(map);

		if (this.confirmed && iconsList.qsoconfirm.icon !== "0") {
			var marker2 = L.marker([this.latlng2[0], this.latlng2[1]], {icon: qsoconfirmIcon},{closeOnClick: false, autoClose: false}).addTo(map);
			linecolor = iconsList.qsoconfirm.color;
		} else {
			var marker2 = L.marker([this.latlng2[0], this.latlng2[1]], {icon: qsoIcon},{closeOnClick: false, autoClose: false}).addTo(map);
			linecolor = iconsList.qso.color;
		}

		if (iconsList.path_lines === "true") {
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

			map.addLayer(geodesic);
		}
	});

	if (iconsList.gridsquare_layer === "true") {
		maidenhead = L.maidenheadqrb().addTo(map);
	}

	if (iconsList.nightshadow_layer === "true") {
		nightlayer = L.terminator().addTo(map);
	}

	if (iconsList.cqzone_layer === "true") {
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

	$.each(iconsList, function (icon, data) {
		$('#exportmap' + ' .cspot_' + icon).addClass(data.icon).css("color", data.color);
	});

	map.addLayer(osm);

	var printer = L.easyPrint({
		tileLayer: osm,
		sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
		filename: 'Wavelog',
		exportOnly: true,
		hideControlContainer: true
	}).addTo(map);
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

const cqzonenames = [
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
