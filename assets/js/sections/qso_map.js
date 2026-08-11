let maidenhead;
let zonemarkers = [];
let ituzonemarkers = [];
let map = null;
let info;
let geojsonlayer;

// Region choropleth colours, taken from the user's map options (worked /
// confirmed) with sensible fallbacks. Each subdivision is filled by status.
function qsoMapHexToRgba(hex, alpha) {
	if (!hex) return null;
	hex = hex.replace(/^#/, '');
	if (hex.length === 3) {
		hex = hex.split('').map(function (c) { return c + c; }).join('');
	}
	const num = parseInt(hex, 16);
	return 'rgba(' + ((num >> 16) & 255) + ', ' + ((num >> 8) & 255) + ', ' + (num & 255) + ', ' + alpha + ')';
}
let qsoMapWorkedColor = 'rgba(229, 165, 10, 0.55)';
let qsoMapConfirmedColor = 'rgba(144, 238, 144, 0.55)';
let qsoMapUnworkedColor = 'rgba(204, 55, 45, 0.3)'; // #CC372D fallback
if (typeof user_map_custom !== 'undefined') {
	if (user_map_custom.qso && user_map_custom.qso.color) {
		qsoMapWorkedColor = qsoMapHexToRgba(user_map_custom.qso.color, 0.55);
	}
	if (user_map_custom.qsoconfirm && user_map_custom.qsoconfirm.color) {
		qsoMapConfirmedColor = qsoMapHexToRgba(user_map_custom.qsoconfirm.color, 0.55);
	}
	if (user_map_custom.unworked && user_map_custom.unworked.color) {
		qsoMapUnworkedColor = qsoMapHexToRgba(user_map_custom.unworked.color, 0.3);
	}
}
const qsoMapIsDark = (typeof isDarkModeTheme === 'function') ? isDarkModeTheme() : false;
const qsoMapLineColor = qsoMapIsDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.55)';

// Wait for jQuery to be loaded
function initMap() {
    let markers = [];
    let geojsonLayers = []; // Store multiple GeoJSON layers
    let allQsos = []; // Store all QSOs for filtering
    let countryGrids = []; // Gridsquares belonging to the selected DXCC
    let legendAdded = false; // Track if legend has been added
    let legendControl = null; // Store legend control for updates

    // Enable/disable load button based on country selection
    $('#countrySelect, #locationSelect, #bandSelect').on('change', function() {
        const countrySelected = $('#countrySelect').val();
        $('#loadMapBtn').prop('disabled', !countrySelected);
        $('#showOnlyOutside').prop('disabled', !countrySelected);
        $('#mapContainer').hide();
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
        const loadingText = country === 'all' ? lang_qso_map_loading_all : lang_qso_map_loading;
        $('#loadingSpinner').removeClass('d-none');
        $('#loadingText').text(loadingText).removeClass('d-none');
        $('#loadMapBtn').prop('disabled', true);

        // Set timeout for long-running requests
        const timeout = setTimeout(function() {
            $('#loadingText').text(lang_qso_map_still_loading);
        }, 5000);

        $.ajax({
            url: base_url + 'index.php/map/get_qsos_for_country',
            method: 'POST',
            dataType: 'json',
            data: {
                country: country,
                dxcc: dxcc,
                station_id: stationId,
                band: $('#bandSelect').val()
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
                        alert(lang_qso_map_error_parsing + ' ' + e.message);
                        return;
                    }
                }

                if (response.error) {
                    alert(lang_qso_map_error + ' ' + response.error);
                    return;
                }

                // Response is { qsos: [...], grids: [...] }; grids holds the
                // gridsquares that belong to the selected DXCC, used to limit
                // the maidenhead grid to that DXCC (like the gridmap).
                allQsos = response.qsos || [];
                countryGrids = response.grids || [];

                const showOnlyOutside = $('#showOnlyOutside').is(':checked');
                filterAndDisplayMarkers(allQsos, showOnlyOutside);
            }
        }).fail(function() {
            clearTimeout(timeout);
            $('#loadingSpinner').addClass('d-none');
            $('#loadingText').addClass('d-none');
            $('#loadMapBtn').prop('disabled', false);
            alert(lang_qso_map_load_failed);
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

		if (maidenhead) {
			map.removeLayer(maidenhead);
		}
		maidenhead = L.maidenheadqrb({ grids: countryGrids }).addTo(map);
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

        // Count inside/outside across ALL QSOs so the legend matches the QSO lists
        qsos.forEach(function(qso) {
            if (qso.inside_geojson === false) { outsideCount++; } else { insideCount++; }
        });

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
            } else {
                // Create green checkmark icon for QSOs inside GeoJSON
                icon = L.divIcon({
                    html: '<div style="background-color: #28a745; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">✓</div>',
                    iconSize: [24, 24],
                    className: 'custom-div-icon'
                });
            }

            marker = L.marker([qso.lat, qso.lng], { icon: icon })
                .bindPopup(qso.popup +
                    (qso.inside_geojson === false ? '<br><span style="color: red;"><strong>⚠ ' + lang_qso_map_outside_boundaries + '</strong></span>' :
                    '<br><span style="color: green;"><strong>✓ ' + lang_qso_map_inside_boundaries + '</strong></span>'))
                .addTo(map)
                .on('mouseover', function () { this.openPopup(); });

            markers.push(marker);
            bounds.push([qso.lat, qso.lng]);
        });

        // Build per-region worked/confirmed status from the QSO data, to drive
        // the choropleth fill colour of each subdivision.
        const regionStatus = {};
        qsos.forEach(function(qso) {
            if (qso.state_info) {
                const key = qso.state_info.code || qso.state_info.name;
                if (key !== undefined && key !== null && key !== '') {
                    if (!regionStatus[key]) regionStatus[key] = { worked: false, confirmed: false };
                    regionStatus[key].worked = true;
                    if (qso.confirmed) regionStatus[key].confirmed = true;
                }
            }
        });

        function regionStyle(feature) {
            const key = feature.properties && (feature.properties.code || feature.properties.name);
            const status = regionStatus[key];
            let fillColor = qsoMapUnworkedColor;
            if (status) {
                fillColor = status.confirmed ? qsoMapConfirmedColor : qsoMapWorkedColor;
            }
            return {
                fillColor: fillColor,
                color: qsoMapLineColor,
                weight: 1,
                fillOpacity: 0.6,
                opacity: 0.8
            };
        }

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
                            style: regionStyle,
							onEachFeature: onEachFeature
                        }).addTo(map);
                        geojsonLayers.push(geojsonlayer);

                        // Fill in the "Regions worked: X / Y" readout in the legend
                        const totalRegions = (geojson.features || []).length;
                        $('#legend-region-count').text(Object.keys(regionStatus).length + ' / ' + totalRegions);



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

        // Add or update legend (includes the hovered-region readout)
        if (!legendAdded) {
            addLegend(insideCount, outsideCount, qsos.length, showOnlyOutside);
            legendAdded = true;
        } else {
            // Update existing legend counts
            updateLegend(insideCount, outsideCount, qsos.length, showOnlyOutside);
        }

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
		updateLegendRegion(layer.feature.properties);
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
		updateLegendRegion();
	}

	// Update the hovered-region readout inside the legend
	function updateLegendRegion(props) {
		var el = document.getElementById('legend-region');
		if (!el) return;
		el.innerHTML = props ? ('<b>' + props.code + ' - ' + props.name + '</b>') : '<em>' + lang_qso_map_hover_region + '</em>';
	}

    // Escape text for safe insertion into HTML
    function escQsoHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    // Show a modal listing the QSOs inside or outside the country boundaries
    function showQsoBoundaryList(which) {
        var list = allQsos.filter(function(q) {
            return which === 'outside' ? (q.inside_geojson === false) : (q.inside_geojson !== false);
        });
        var title = (which === 'outside' ? lang_qso_map_outside_label : lang_qso_map_inside_label) + ' (' + list.length + ')';
        var html = '<table class="table table-sm table-striped table-hover w-100 display">' +
            '<thead><tr>' +
            '<th>' + lang_qso_map_th_call + '</th>' +
            '<th>' + lang_qso_map_th_date + '</th>' +
            '<th>' + lang_qso_map_th_band + '</th>' +
            '<th>' + lang_qso_map_th_mode + '</th>' +
            '<th>' + lang_qso_map_th_grid + '</th>' +
            '</tr></thead><tbody>';
        list.forEach(function(q) {
            var when = q.time_formatted || '';
            var bandDisplay = (q.prop_mode === 'SAT')
                ? ('SAT' + (q.sat_name ? ' ' + q.sat_name : '') + (q.sat_mode ? ' (' + q.sat_mode + ')' : ''))
                : (q.band || '');
            html += '<tr>' +
                '<td><a href="javascript:displayQso(' + q.id + ')">' + escQsoHtml(q.call) + '</a></td>' +
                '<td>' + escQsoHtml(when) + '</td>' +
                '<td>' + escQsoHtml(bandDisplay) + '</td>' +
                '<td>' + escQsoHtml(q.mode) + '</td>' +
                '<td>' + escQsoHtml(q.gridsquare) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';

        BootstrapDialog.show({
            title: title,
            size: BootstrapDialog.SIZE_WIDE,
            nl2br: false,
            message: html,
            onshown: function(dialog) {
                $('table.display', dialog.getModal()).DataTable({
                    pageLength: 25,
                    order: [],
                    language: { url: getDataTablesLanguageUrl() }
                });
            }
        });
    }

    function addLegend(insideCount, outsideCount, totalCount, showOnlyOutside) {
        const legend = L.control({ position: 'topright' });

        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'legend');

            let html = '<h4>' + lang_qso_map_legend + '</h4>';

            // Inside boundaries
            html += '<div class="legend-item">';
            html += '<div class="legend-icon">';
            html += '<div style="background-color: #28a745; color: white; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);">✓</div>';
            html += '</div>';
            html += lang_qso_map_inside_label + ' <strong>(' + insideCount + ')</strong>';
            html += '<div class="legend-action qso-map-boundary-list" data-which="inside" title="' + lang_qso_map_show_list + '"><i class="fas fa-list"></i></div>';
            html += '</div>';

            // Outside boundaries
            html += '<div class="legend-item">';
            html += '<div class="legend-icon">';
            html += '<div style="background-color: #ff0000; color: white; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);">✕</div>';
            html += '</div>';
            html += lang_qso_map_outside_label + ' <strong>(' + outsideCount + ')</strong>';
            html += '<div class="legend-action qso-map-boundary-list" data-which="outside" title="' + lang_qso_map_show_list + '"><i class="fas fa-list"></i></div>';
            html += '</div>';

            // Region choropleth: confirmed / worked / not worked
            html += '<div class="legend-item">';
            html += '<div class="legend-icon"><div style="background-color: ' + qsoMapConfirmedColor + '; width: 20px; height: 12px; border: 1px solid ' + qsoMapLineColor + '; border-radius: 2px;"></div></div>';
            html += '<div>' + lang_qso_map_region_confirmed + '</div>';
            html += '</div>';
            html += '<div class="legend-item">';
            html += '<div class="legend-icon"><div style="background-color: ' + qsoMapWorkedColor + '; width: 20px; height: 12px; border: 1px solid ' + qsoMapLineColor + '; border-radius: 2px;"></div></div>';
            html += '<div>' + lang_qso_map_region_worked + '</div>';
            html += '</div>';
            html += '<div class="legend-item">';
            html += '<div class="legend-icon"><div style="background-color: ' + qsoMapUnworkedColor + '; width: 20px; height: 12px; border: 1px solid ' + qsoMapLineColor + '; border-radius: 2px;"></div></div>';
            html += '<div>' + lang_qso_map_region_not_worked + '</div>';
            html += '</div>';
            html += '<div style="font-size: 12px;"><div>' + lang_qso_map_regions_label + ' <span id="legend-region-count">-</span></div></div>';

            // Total QSOs (shown differently when filtering)
            if (showOnlyOutside) {
                html += '<div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 12px;">';
                html += '<div>' + lang_qso_map_showing.replace('%s', outsideCount).replace('%s', totalCount) + '</div>';
                html += '</div>';
            } else {
                html += '<div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 12px;">';
                html += '<div>' + lang_qso_map_total_qsos.replace('%s', totalCount) + '</div>';
                html += '</div>';
            }

            // Hovered region (updates when mousing over subdivisions)
            html += '<div style="margin-top: 10px; padding-top: 8px; font-size: 14px;">';
            html += '<h4>' + lang_qso_map_region + '</h4>';
            html += '<span id="legend-region"><em>' + lang_qso_map_hover_region + '</em></span>';
            html += '</div>';

            html += '<br />';
            html += '<h4>' + lang_qso_map_toggle_layers + '</h4>';
            html += '<input type="checkbox" onclick="toggleGridsquares(this.checked)" ' + (typeof gridsquare_layer !== 'undefined' && gridsquare_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_gridsquares + '</span><br>';
            html += '<input type="checkbox" onclick="toggleCqZones(this.checked)" ' + (typeof cqzones_layer !== 'undefined' && cqzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_cq_zones + '</span><br>';
            html += '<input type="checkbox" onclick="toggleItuZones(this.checked)" ' + (typeof ituzones_layer !== 'undefined' && ituzones_layer ? 'checked' : '') + ' style="outline: none;"><span> ' + lang_gen_hamradio_itu_zones + '</span><br>';

            div.innerHTML = html;

            // QSO list popups for the inside/outside boundary markers
            var listIcons = div.querySelectorAll('.qso-map-boundary-list');
            for (var i = 0; i < listIcons.length; i++) {
                listIcons[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showQsoBoundaryList(this.getAttribute('data-which'));
                });
            }

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
