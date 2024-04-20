var wab_squares = $.ajax({
	url: base_url+"assets/js/sections/wab_geojson.js",
	dataType: "json",
	success: console.log("WAB data successfully loaded."),
	error: function(xhr) {
		alert(xhr.statusText)
	}
})

$.when(wab_squares).done(function() {
	$.ajax({
		url: site_url + '/awards/wab_map',
		type: 'post',
		data: {
		},
		success: function (data) {
			wabmap(data);
		},
		error: function (data) {
		},
	})
})

function wabmap(data) {
	var map = L.map('wabmap').setView([51.5074, -0.1278], 9);
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
				displayContactsOnMap($("#wabmap"), feature.properties.name, 'All', 'All', 'All', 'All', 'WAB');
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
