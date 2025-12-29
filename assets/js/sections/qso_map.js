let maidenhead;
let zonemarkers = [];
let ituzonemarkers = [];
let map = null;
let info;
let geojsonlayer;

// Wait for jQuery to be loaded
function initMap() {
    let markers = [];
    let geojsonLayers = []; // Store multiple GeoJSON layers
    let allQsos = []; // Store all QSOs for filtering
    let legendAdded = false; // Track if legend has been added
    let legendControl = null; // Store legend control for updates

    // Enable/disable load button based on country selection
    $('#countrySelect, #locationSelect').on('change', function() {
        const countrySelected = $('#countrySelect').val();
        $('#loadMapBtn').prop('disabled', !countrySelected);
        $('#showOnlyOutside').prop('disabled', !countrySelected);
        $('#mapContainer, #noDataMessage').hide();
    });

    // Handle checkbox change
    $('#showOnlyOutside').on('change', function() {
        if (allQsos.length > 0) {
            filterAndDisplayMarkers(allQsos, $(this).is(':checked'));
        }
    });

    // Load map when button is clicked
    $('#loadMapBtn').on('click', function() {
        const country = $('#countrySelect').val();
        const dxcc = $('#countrySelect option:selected').data('dxcc');
        const stationId = $('#locationSelect').val();
        if (!country) return;

        // Fetch QSO data
        const loadingText = country === 'all' ? 'Loading QSOs for all countries (this may take a moment)...' : 'Loading QSO data...';
        $('#loadingSpinner').removeClass('d-none');
        $('#loadingText').text(loadingText).removeClass('d-none');
        $('#loadMapBtn').prop('disabled', true);

        // Set timeout for long-running requests
        const timeout = setTimeout(function() {
            $('#loadingText').text('Still loading... Processing large dataset, please wait...');
        }, 5000);

        $.ajax({
            url: base_url + 'index.php/map/get_qsos_for_country',
            method: 'POST',
            dataType: 'json',
            data: {
                country: country,
                dxcc: dxcc,
                station_id: stationId
            },
            success: function(response) {
                clearTimeout(timeout);
                $('#loadingSpinner').addClass('d-none');
                $('#loadingText').addClass('d-none');
                $('#loadMapBtn').prop('disabled', false);

                // Check if response is a string and parse it if needed
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        alert('Error parsing response: ' + e.message);
                        return;
                    }
                }

                if (response.error) {
                    alert('Error: ' + response.error);
                    return;
                }

                if (!Array.isArray(response)) {
                    console.log('Response is not an array:', response);
                    alert('Error: Expected an array of QSOs but received something else');
                    return;
                }

                if (response.length === 0) {
                    $('#noDataMessage').show();
                    return;
                }

                // Store all QSOs and initialize map
                allQsos = response;
                const showOnlyOutside = $('#showOnlyOutside').is(':checked');
                filterAndDisplayMarkers(allQsos, showOnlyOutside);
            }
        }).fail(function() {
            clearTimeout(timeout);
            $('#loadingSpinner').addClass('d-none');
            $('#loadingText').addClass('d-none');
            $('#loadMapBtn').prop('disabled', false);
            alert('Failed to load QSO data. Please try again.');
        });
    });

    async function filterAndDisplayMarkers(qsos, showOnlyOutside = false) {
        // Clear existing markers and layers
        clearMap();

        // Filter QSOs if checkbox is checked
        const filteredQsos = showOnlyOutside ? qsos.filter(qso => qso.inside_geojson === false) : qsos;

        // Create map if it doesn't exist
        if (!map) {
            map = L.map('mapgeojson').setView([40, 0], 2);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

		maidenhead = L.maidenheadqrb().addTo(map);
		map.on('mousemove', onMapMove);
		$('.cohidden').show();

		if (typeof gridsquare_layer !== 'undefined') {
			toggleGridsquares(gridsquare_layer);
		} else {
			toggleGridsquares(false);
		}

        // Check if we have country boundaries
        const selectedOption = $('#countrySelect option:selected');
        const dxcc = selectedOption.data('dxcc');
        const country = $('#countrySelect').val();

        // Add QSO markers first
        let bounds = [];
        let outsideCount = 0;
        let insideCount = 0;

        filteredQsos.forEach(function(qso) {
            let marker;
            let icon;

            // Check if QSO is inside GeoJSON boundary
            if (qso.inside_geojson === false) {
                // Create red X icon for QSOs outside GeoJSON
                icon = L.divIcon({
                    html: '<div style="background-color: #ff0000; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">✕</div>',
                    iconSize: [24, 24],
                    className: 'custom-div-icon'
                });
                outsideCount++;
            } else {
                // Create green checkmark icon for QSOs inside GeoJSON
                icon = L.divIcon({
                    html: '<div style="background-color: #28a745; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">✓</div>',
                    iconSize: [24, 24],
                    className: 'custom-div-icon'
                });
                insideCount++;
            }

            marker = L.marker([qso.lat, qso.lng], { icon: icon })
                .bindPopup(qso.popup +
                    (qso.inside_geojson === false ? '<br><span style="color: red;"><strong>⚠ Outside country boundaries</strong></span>' :
                    '<br><span style="color: green;"><strong>✓ Inside country boundaries</strong></span>'))
                .addTo(map);

            markers.push(marker);
            bounds.push([qso.lat, qso.lng]);
        });

        // Try to load GeoJSON for the country/countries
        if (dxcc && supportedDxccs.includes(parseInt(dxcc))) {
            // Single country GeoJSON
            $.ajax({
                url: base_url + "index.php/map/get_country_geojson/",
                type: 'post',
                data: { dxcc: dxcc },
                success: function(geojson) {
                    if (geojson && !geojson.error) {
                        geojsonlayer = L.geoJSON(geojson, {
                            style: {
                                color: '#ff0000',
                                weight: 2,
                                opacity: 0.5,
                                fillOpacity: 0.2
                            },
							onEachFeature: onEachFeature
                        }).addTo(map);
                        geojsonLayers.push(geojsonlayer);



                        // Fit map to show both GeoJSON and markers
                        setTimeout(function() {
                            const geoBounds = geojsonlayer.getBounds();
                            if (bounds.length > 0) {
                                const markerBounds = L.latLngBounds(bounds);
                                // Combine bounds
                                geoBounds.extend(markerBounds);
                            }
                            map.fitBounds(geoBounds, { padding: [20, 20] });
                        }, 100);
                    } else {
                        // No GeoJSON, fit to markers only
                        if (bounds.length > 0) {
                            const markerBounds = L.latLngBounds(bounds);
                            map.fitBounds(markerBounds, { padding: [50, 50] });
                        }
                    }
                },
                error: function() {
                    // GeoJSON failed to load, fit to markers only
                    if (bounds.length > 0) {
                        const markerBounds = L.latLngBounds(bounds);
                        map.fitBounds(markerBounds, { padding: [50, 50] });
                    }
                }
            });
        } else {
            // No GeoJSON support, fit to markers only
            if (bounds.length > 0) {
                const markerBounds = L.latLngBounds(bounds);
                map.fitBounds(markerBounds, { padding: [50, 50] });
            }
        }

 		$('#mapContainer').show();

        // Remove existing info control if it exists
        if (info) {
            map.removeControl(info);
        }

        // Add or update legend
        if (!legendAdded) {
            addLegend(insideCount, outsideCount, qsos.length, showOnlyOutside);
            legendAdded = true;
        } else {
            // Update existing legend counts
            updateLegend(insideCount, outsideCount, qsos.length, showOnlyOutside);
        }

        // Always re-add info control after legend to ensure correct order
        info = L.control();

        info.onAdd = function (map) {
            this._div = L.DomUtil.create('div', 'info');
            this.update();
            return this._div;
        };

        info.update = function (props) {
            this._div.innerHTML = '<h4>Region</h4>' +  (props ?
            '<b>' + props.code + ' - ' + props.name + '</b><br />' : 'Hover over a region');
        };

        info.addTo(map);

        // Force map to recalculate its size
        setTimeout(function() {
            if (map) {
                map.invalidateSize();

                // Re-fit bounds after size invalidation
                if (bounds.length > 0) {
                    const markerBounds = L.latLngBounds(bounds);
                    map.fitBounds(markerBounds, { padding: [50, 50] });
                }
            }
        }, 100);
    }

	function onEachFeature(feature, layer) {
		layer.on({
			mouseover: highlightFeature,
			mouseout: resetHighlight,
			click: onClick2
		});
	}

	function highlightFeature(e) {
		var layer = e.target;

		layer.setStyle({
			weight: 3,
			// color: 'white',
			dashArray: '',
			fillOpacity: 0.6
		});

		layer.bringToFront();
		info.update(layer.feature.properties);
	}

	function zoomToFeature(e) {
		map.fitBounds(e.target.getBounds());
	}

	function onClick2(e) {
		zoomToFeature(e);
		let marker = e.target;
	}

	function resetHighlight(e) {
		geojsonlayer.resetStyle(e.target);
		info.update();
	}

    function addLegend(insideCount, outsideCount, totalCount, showOnlyOutside) {
        const legend = L.control({ position: 'topright' });

        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'legend');

            let html = '<h4>Legend</h4>';

            // Inside boundaries
            html += '<div class="legend-item">';
            html += '<div class="legend-icon">';
            html += '<div style="background-color: #28a745; color: white; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);">✓</div>';
            html += '</div>';
            html += '<span>Inside boundaries <strong>(' + insideCount + ')</strong></span>';
            html += '</div>';

            // Outside boundaries
            html += '<div class="legend-item">';
            html += '<div class="legend-icon">';
            html += '<div style="background-color: #ff0000; color: white; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);">✕</div>';
            html += '</div>';
            html += '<span>Outside boundaries <strong>(' + outsideCount + ')</strong></span>';
            html += '</div>';

            // GeoJSON boundaries
            html += '<div class="legend-item">';
            html += '<div class="legend-icon">';
            html += '<svg width="20" height="3"><line x1="0" y1="1.5" x2="20" y2="1.5" stroke="#ff0000" stroke-width="2"/></svg>';
            html += '</div>';
            html += '<span>Country/State boundaries</span>';
            html += '</div>';

            // Total QSOs (shown differently when filtering)
            if (showOnlyOutside) {
                html += '<div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 12px;">';
                html += '<em>Showing ' + outsideCount + ' of ' + totalCount + ' total QSOs</em>';
                html += '</div>';
            } else {
                html += '<div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 12px;">';
                html += '<em>Total: ' + totalCount + ' QSOs with 6+ char grids</em>';
                html += '</div>';
            }

            html += '<br />';
            html += '<h4>Toggle layers</h4>';
            html += '<input type="checkbox" onclick="toggleGridsquares(this.checked)" ' + (typeof gridsquare_layer !== 'undefined' && gridsquare_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_gridsquares + '</span><br>';
            html += '<input type="checkbox" onclick="toggleCqZones(this.checked)" ' + (typeof cqzones_layer !== 'undefined' && cqzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_cq_zones + '</span><br>';
            html += '<input type="checkbox" onclick="toggleItuZones(this.checked)" ' + (typeof ituzones_layer !== 'undefined' && ituzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_itu_zones + '</span><br>';

            div.innerHTML = html;

            // Prevent map events on the legend
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);

            return div;
        };

        legendControl = legend;
        legend.addTo(map);
    }

    function updateLegend(insideCount, outsideCount, totalCount, showOnlyOutside) {
        if (!legendControl) return;

        // Remove the legend and re-add it with updated counts
        map.removeControl(legendControl);
        addLegend(insideCount, outsideCount, totalCount, showOnlyOutside);
    }

    function clearMap() {
        // Remove existing markers
        markers.forEach(function(marker) {
            map.removeLayer(marker);
        });
        markers = [];

        // Remove all GeoJSON layers
        geojsonLayers.forEach(function(layer) {
            map.removeLayer(layer);
        });
        geojsonLayers = [];
    }
}

// Check if jQuery is loaded, if not wait for it
if (typeof $ === 'undefined') {
    // jQuery not yet loaded, add event listener
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ === 'undefined') {
            // Wait for jQuery to load
            var checkJQuery = setInterval(function() {
                if (typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
                    initMap();
                }
            }, 100);
        } else {
            initMap();
        }
    });
} else {
    // jQuery already loaded
    $(document).ready(function() {
        initMap();
    });
}


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
