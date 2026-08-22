// Notice shown above the result after an update re-run, set by applyCountyUpdates()
var pendingUpdateNotice = null;

// Bind the county-check click handler (defined once, bound once jQuery is available)
function bindCountyCheck() {
	$('#startCountyCheck').on('click', function() {
		runCountyCheck();
	});

	// Open every QSO with the given callsign in a modal dialog (delegated so it
	// survives re-rendering of the result table)
	$(document).on('click', '.callsign-search', function(e) {
		e.preventDefault();
		showCallQsos($(this).attr('data-call'));
	});

	// Open a map dialog for a gridsquare showing its derived and logged counties
	$(document).on('click', '.county-map', function(e) {
		e.preventDefault();
		openCountyMap($(this).attr('data-grid'), $(this).attr('data-loggedstate'), $(this).attr('data-loggedcounty'));
	});

	// Select/deselect every row checkbox
	$(document).on('change', '#countySelectAll', function() {
		$('.county-update-check').prop('checked', this.checked);
	});

	// Write the geojson county of the checked rows into the log
	$(document).on('click', '#updateCountiesBtn', function() {
		applyCountyUpdates();
	});
}

function runCountyCheck() {
	$('#startCountyCheck').addClass('running').prop('disabled', true);
	$('.result').html('');
	$.ajax({
		url: site_url + '/countychecker/doCountyCheck',
		type: "POST",
		data: {
			de: $('#de').val(),
			includeMissing: $('#includeMissing').is(':checked') ? 1 : 0
		},
		success: function(response) {
			$('.result').html(response);
			if (pendingUpdateNotice) {
				$('.result').prepend('<div class="alert alert-success">' + pendingUpdateNotice + '</div>');
				pendingUpdateNotice = null;
			}
			$('.result [data-bs-toggle="tooltip"]').tooltip();
			$('#startCountyCheck').removeClass('running').prop('disabled', false);
		},
		error: function(xhr, status, error) {
			$('.result').html('<div class="alert alert-danger" role="alert">An error occurred while processing the request.</div>');
			$('#startCountyCheck').removeClass('running').prop('disabled', false);
		}
	});
}

function applyCountyUpdates() {
	var rows = [];
	$('.county-update-check:checked').each(function() {
		rows.push({ id: $(this).data('id'), state: $(this).data('state'), county: $(this).data('county') });
	});
	if (!rows.length) {
		BootstrapDialog.show({
			title: 'Update counties',
			message: 'No rows selected.',
			buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
		});
		return;
	}

	BootstrapDialog.confirm({
		title: 'Update counties',
		message: 'Write the geojson county into the log for ' + rows.length + ' QSO(s)? This overwrites the current county value of those QSOs.',
		btnOKLabel: 'Update',
		btnOKClass: 'btn-warning',
		callback: function(confirmed) {
			if (!confirmed) { return; }
			$.ajax({
				url: site_url + '/countychecker/updateCounties',
				type: 'POST',
				dataType: 'json',
				data: { qsos: JSON.stringify(rows) },
				success: function(resp) {
					if (!resp || resp.error) {
						BootstrapDialog.show({
							title: 'Update counties',
							message: '<div class="alert alert-danger mb-0">' + ((resp && resp.error) ? resp.error : 'Update failed.') + '</div>',
							buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
						});
						return;
					}
					pendingUpdateNotice = 'Updated the county of ' + resp.updated + ' QSO(s).';
					runCountyCheck();
				},
				error: function() {
					BootstrapDialog.show({
						title: 'Update counties',
						message: '<div class="alert alert-danger mb-0">Update failed.</div>',
						buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
					});
				}
			});
		}
	});
}

function showCallQsos(call) {
	BootstrapDialog.show({
		title: call,
		cssClass: 'qso-dialog',
		size: BootstrapDialog.SIZE_WIDE,
		nl2br: false,
		message: function(dialog) {
			var $content = $('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>');
			$.get(site_url + '/calltester/call_info/' + encodeURIComponent(call), function(html) {
				$content.html(html);
				$content.find('[data-bs-toggle="tooltip"]').tooltip();
			});
			return $content;
		}
	});
}

function openCountyMap(grid, loggedState, loggedCounty) {
	let dialog = BootstrapDialog.show({
		title: grid + ' · ' + (loggedState ? loggedState + ', ' : '') + loggedCounty,
		size: BootstrapDialog.SIZE_WIDE,
		nl2br: false,
		message: '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>',
		buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
	});

	$.ajax({
		url: site_url + '/countychecker/mapData',
		type: 'POST',
		dataType: 'json',
		data: { gridsquare: grid, loggedState: loggedState, loggedCounty: loggedCounty },
		success: function(data) {
			if (!data || data.error || (!data.feature && !data.logged_feature)) {
				dialog.setMessage('<div class="alert alert-warning mb-0">' + ((data && data.error) ? data.error : 'No county found for this gridsquare.') + '</div>');
				return;
			}
			var mapId = 'countychecker_map_' + Math.random().toString(36).slice(2, 10);
			dialog.setMessage('<div id="' + mapId + '" style="width:100%; height:60vh;"></div>');
			renderCountyMap(data, grid, mapId);
		},
		error: function() {
			dialog.setMessage('<div class="alert alert-danger mb-0">Error loading map data.</div>');
		}
	});
}

function renderCountyMap(data, grid, mapId) {
	var map = L.map(mapId).setView([data.lat, data.lng], 9);

	L.tileLayer(option_map_tile_server, {
		attribution: option_map_tile_server_copyright,
		maxZoom: 18
	}).addTo(map);

	var layers = [];
	// Permanent map labels, styled like the activation planner overlays
	var labelOpts = { permanent: true, direction: 'center', className: 'overlay-region-label' };

	// County derived from the gridsquare (red)
	if (data.feature) {
		layers.push(L.geoJSON({ type: 'FeatureCollection', features: [data.feature] }, {
			style: { color: '#ff4136', weight: 2, fillColor: '#ff4136', fillOpacity: 0.15 },
			onEachFeature: function (feature, lyr) {
				lyr.bindTooltip(data.state + ', ' + data.county, labelOpts);
			}
		}).addTo(map));
	}

	// County as logged in the QSO (blue)
	if (data.logged_feature) {
		layers.push(L.geoJSON({ type: 'FeatureCollection', features: [data.logged_feature] }, {
			style: { color: '#0074d9', weight: 2, fillColor: '#0074d9', fillOpacity: 0.15 },
			onEachFeature: function (feature, lyr) {
				lyr.bindTooltip(data.logged_state + ', ' + data.logged_county, labelOpts);
			}
		}).addTo(map));
	}

	// Other counties the grid box overlaps (orange): with confidence below
	// 100% the QSO could be in any of them
	var candidateLegendEntries = [];
	(data.candidates || []).forEach(function(cand) {
		layers.push(L.geoJSON({ type: 'FeatureCollection', features: [cand.feature] }, {
			style: { color: '#ff851b', weight: 2, fillColor: '#ff851b', fillOpacity: 0.15 },
			onEachFeature: function (feature, lyr) {
				lyr.bindTooltip(cand.state + ', ' + cand.county, labelOpts);
			}
		}).addTo(map));
		candidateLegendEntries.push({ style: 'background:#ff851b;', label: 'Possible: ' + cand.state + ', ' + cand.county });
	});

	// Outline of the gridsquare itself (6-char grids are 5' x 2.5')
	if (data.grid_bounds) {
		L.rectangle([[data.grid_bounds[0], data.grid_bounds[1]], [data.grid_bounds[2], data.grid_bounds[3]]], {
			color: '#ffdc00', weight: 2, fill: false, dashArray: '4 3', interactive: false
		}).bindTooltip(grid, { permanent: true, direction: 'top', offset: [0, -4], className: 'overlay-region-label' }).addTo(map);
	}

	var popup = '<b>' + grid + '</b>'
		+ '<br>' + (data.state ? 'GeoJSON: ' + data.state + ', ' + data.county : '')
		+ '<br>' + (data.logged_state ? 'Log: ' + data.logged_state + ', ' + data.logged_county : '');

	L.marker([data.lat, data.lng]).addTo(map).bindPopup(popup).openPopup();

	addCountyLegend(map, [
		data.feature && { style: 'background:#ff4136;', label: 'GeoJSON: ' + data.state + ', ' + data.county },
		data.logged_feature && { style: 'background:#0074d9;', label: 'Log: ' + data.logged_state + ', ' + data.logged_county }
	].concat(candidateLegendEntries).concat([
		data.grid_bounds && { style: 'border:2px dashed #ffdc00;background:transparent;', label: grid }
	]).filter(Boolean));

	try {
		map.fitBounds(L.featureGroup(layers).getBounds().pad(0.15));
	} catch (e) { /* invalid bounds */ }

	setTimeout(function() { map.invalidateSize(); }, 120);
}

/* Legend in the top right corner, styled like the DXCC award map legend
   (.legend class from general.css), one entry per drawn layer. */
function addCountyLegend(map, entries) {
	if (!entries.length) { return; }
	var legend = L.control({ position: 'topright' });
	legend.onAdd = function() {
		var div = L.DomUtil.create('div', 'legend');
		div.innerHTML = entries.map(function(e) {
			return '<i style="' + e.style + '"></i><span>' + e.label + '</span><br>';
		}).join('');
		return div;
	};
	legend.addTo(map);
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
					bindCountyCheck();
				}
			}, 100);
		} else {
			bindCountyCheck();
		}
	});
} else {
	// jQuery already loaded
	$(document).ready(function() {
		bindCountyCheck();
	});
}
