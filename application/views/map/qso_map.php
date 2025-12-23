<div class="container">
    <h2><?= ('GeoJSON QSO Map'); ?></h2>

    <div class="row mb-3 align-items-end">
        <div class="col-auto">
            <label for="countrySelect" class="form-label"><?= __("Select Country:"); ?></label>
            <select class="form-select" id="countrySelect" style="min-width: 200px;">
                <option value=""><?= __("Choose a country...") ?></option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo htmlspecialchars($country['COL_COUNTRY']); ?>"
                            data-dxcc="<?php echo htmlspecialchars($country['COL_DXCC']); ?>">
                        <?php echo htmlspecialchars($country['COL_COUNTRY'] . ' (' . $country['qso_count'] . ' ' . __("QSOs") . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label for="locationSelect" class="form-label">Location:</label>
            <select class="form-select" id="locationSelect" style="min-width: 200px;">
                <option value="all">All</option>
                <?php foreach ($station_profiles as $profile): ?>
                    <option value="<?php echo htmlspecialchars($profile['station_id']); ?>">
                        <?php echo htmlspecialchars($profile['station_profile_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button id="loadMapBtn" class="btn btn-primary" disabled>Load Map</button>
        </div>
        <div class="col-auto">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="showOnlyOutside" disabled checked>
                <label class="form-check-label" for="showOnlyOutside">
                    <?= ('Show only QSOs outside boundaries') ?>
                </label>
            </div>
        </div>
        <div class="col-auto d-flex align-items-center">
            <div id="loadingSpinner" class="spinner-border text-primary d-none" role="status">
                <span class="visually-hidden"><?= ('Loading...') ?></span>
            </div>
            <div id="loadingText" class="ms-2 text-muted d-none"></div>
        </div>
    </div>

    <div id="mapContainer" class="mt-3" style="display: none;">
        <div id="mapgeojson" style="border: 1px solid #ccc;"></div>
        <div class="mt-2">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                <?= ('Map shows QSOs with 6+ character gridsquares.') ?>
            </small>
        </div>
    </div>

    <div id="noDataMessage" class="alert alert-warning mt-3" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <?= ('No QSOs with 6+ character grids found for the selected country.') ?>
    </div>
</div>

<style>
#mapgeojson {
    border-radius: 4px;
    height: 1000px !important;
    width: 100% !important;
    min-height: 600px;
}
.leaflet-popup-content {
    min-width: 200px;
}
.marker-cluster {
    background-color: rgba(110, 204, 57, 0.6);
}
.leaflet-marker-qso {
    background-color: #3388ff;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.leaflet-container {
    height: 600px !important;
    width: 100% !important;
}
.custom-div-icon {
    background: transparent;
    border: none;
}
.custom-div-icon i {
    color: red;
}
.legend {
    background: rgba(255, 255, 255, 0.95);
    padding: 12px;
    border-radius: 6px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.4);
    line-height: 1.6;
    border: 1px solid #ccc;
    min-width: 200px;
}
.legend h4 {
    margin: 0 0 10px 0;
    font-size: 15px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
.legend-item {
    display: flex;
    align-items: center;
    margin: 8px 0;
}
.legend-icon {
    margin-right: 10px;
    flex-shrink: 0;
}
</style>


<script>
// Pass supported DXCC list from PHP to JavaScript
const supportedDxccs = <?php echo json_encode(array_keys($supported_dxccs)); ?>;

// Wait for jQuery to be loaded
function initMap() {
    let map = null;
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
            url: '<?php echo site_url("map/get_qsos_for_country"); ?>',
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

    function filterAndDisplayMarkers(qsos, showOnlyOutside = false) {
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
                url: base_url + "map/get_country_geojson/",
                type: 'post',
				data: { dxcc: dxcc },
                success: function(geojson) {
                    if (geojson && !geojson.error) {
                        const layer = L.geoJSON(geojson, {
                            style: {
                                color: '#ff0000',
                                weight: 2,
                                opacity: 0.5,
                                fillOpacity: 0.1
                            }
                        }).addTo(map);
                        geojsonLayers.push(layer);

                        // Fit map to show both GeoJSON and markers
                        setTimeout(function() {
                            const geoBounds = layer.getBounds();
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

        // Add legend to the map only once
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
</script>
