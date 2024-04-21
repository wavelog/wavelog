$('#band').change(function(){
	var band = $("#band option:selected").text();
	if (band != "SAT") {
		$("#sats").val('All');
		$("#orbits").val('All');
		$("#sats").hide();
		$("#orbits").hide();
		$("#satslabel").hide();
		$("#orbitslabel").hide();
	} else {
		$("#sats").show();
		$("#orbits").show();
		$("#orbitslabel").show();
		$("#satslabel").show();
	}
});

var wab_squares = $.ajax({
	url: base_url+"assets/js/sections/wab_geojson.js",
	dataType: "json",
	success: console.log("WAB data successfully loaded."),
	error: function(xhr) {
		alert(xhr.statusText)
	}
})

function showlist() {
	$(".ld-ext-right-list").addClass('running');
    $(".ld-ext-right-list").prop('disabled', true);
    $('#list').prop("disabled", true);
	$.ajax({
		url: site_url + '/awards/wab_list',
		type: 'post',
		data: {
			band: $("#band").val(),
            mode: $("#mode").val(),
            qsl:  $("#qsl").is(":checked"),
            lotw: $("#lotw").is(":checked"),
            eqsl: $("#eqsl").is(":checked"),
            qrz: $("#qrz").is(":checked"),
            sat: $("#sats").val(),
            orbit: $("#orbits").val(),
		},
		success: function (data) {
			wablist(data);
		},
		error: function (data) {
		},
	})
}

function wablist(data) {
	$('#wablist').remove();
	var container = L.DomUtil.get('wabmap');

	if(container != null){
		container._leaflet_id = null;
		container.remove();
	}
	$("#mapcontainer").append('<div id="wablist"></div>');
	$("#wablist").append(data);
	$(".ld-ext-right-list").removeClass('running');
	$(".ld-ext-right-list").prop('disabled', false);
	$('#list').prop("disabled", false);
	$('.wabtable').DataTable({
		"pageLength": 25,
		responsive: false,
		ordering: false,
		"scrollY":        "550px",
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
}

function plotmap() {
	$('#wablist').remove();
	$(".ld-ext-right-plot").addClass('running');
    $(".ld-ext-right-plot").prop('disabled', true);
    $('#plot').prop("disabled", true);
	$.ajax({
		url: site_url + '/awards/wab_map',
		type: 'post',
		data: {
			band: $("#band").val(),
            mode: $("#mode").val(),
            qsl:  $("#qsl").is(":checked"),
            lotw: $("#lotw").is(":checked"),
            eqsl: $("#eqsl").is(":checked"),
            qrz: $("#qrz").is(":checked"),
            sat: $("#sats").val(),
            orbit: $("#orbits").val(),
		},
		success: function (data) {
			wabmap(data);
		},
		error: function (data) {
		},
	})
}

function wabmap(data) {
	var container = L.DomUtil.get('wabmap');

	if(container != null){
		container._leaflet_id = null;
		container.remove();
	}
	$("#mapcontainer").append('<div id="wabmap" style="width: 100%; height: 85vh;"></div>');

	$(".ld-ext-right-plot").removeClass('running');
	$(".ld-ext-right-plot").prop('disabled', false);
	$('#plot').prop("disabled", false);
	var map = L.map('wabmap',{
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
	}).setView([51.5074, -1], 9);

	var confirmedcount = 0;
	var workedcount = 0;

	L.tileLayer(tileUrl, {
		attribution: attributionInfo
	}).addTo(map);

	// Add requested external GeoJSON to map
	var kywab_squares = L.geoJSON(wab_squares.responseJSON, {
		style: function(feature) {
				if (feature.properties && feature.properties.name) {
						if (data[feature.properties.name] == 'C') {
							confirmedcount++;
							return {
								fillColor: 'green',
								fill: true,
								fillOpacity: 0.8,
							};
						}
						if (data[feature.properties.name] == 'W') {
							workedcount++;
							return {
								fillColor: 'orange',
								fill: true,
								fillOpacity: 0.8,
							};
						}
				}
		},
		pointToLayer: function(feature, latlng) {
			if (feature.properties && feature.properties.name) {
				// Create a custom icon that displays the name from the GeoJSON data
				var labelIcon = L.divIcon({
					className: 'text-labels', // Set class for CSS styling
					html: feature.properties.name
				});

				// Create a marker at the location of the point
				return L.marker(latlng, {
					icon: labelIcon
				});
			}
		},
		onEachFeature: function(feature, layer) {
			layer.on('click', function() {
				// Code to execute when the area is clicked
				displayContactsOnMap($("#wabmap"), feature.properties.name, $('#band').val(), $('#sats').val(), $('#orbits').val(), $('#mode').val(), 'WAB');
			});
		}
	}).addTo(map);
	// Function to update labels based on zoom level
	function updateLabels() {
		var currentZoom = map.getZoom();
		kywab_squares.eachLayer(function(layer) {
			if (currentZoom >= 8) {
				// Show labels if zoom level is 10 or higher
				layer.getElement().style.display = 'block';
			} else {
				// Hide labels if zoom level is less than 10
				layer.getElement().style.display = 'none';
			}
		});
	}

	// Update labels when the map zoom changes
	map.on('zoomend', updateLabels);

	// Update labels immediately after adding the GeoJSON data to the map
	updateLabels();

	var printer = L.easyPrint({
		tileLayer: tileUrl,
		sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
		filename: 'Wavelog',
		exportOnly: true,
		hideControlContainer: true
	}).addTo(map);

	/*Legend specific*/
    var legend = L.control({ position: "topright" });

    legend.onAdd = function(map) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
        div.innerHTML += "<i style='background: green'></i><span>" + lang_general_word_confirmed + " (" + confirmedcount + ")</span><br>";
        div.innerHTML += "<i style='background: orange'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workedcount + ")</span><br>";
        return div;
    };

    legend.addTo(map);
};