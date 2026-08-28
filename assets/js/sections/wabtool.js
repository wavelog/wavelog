// WAB from Gridsquare tool: batch assign WAB squares from logged gridsquares
// (functions are prefixed wabtool* because section scripts share the global scope)

function wabtoolEscapeHtml(s) {
	return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
		return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
	});
}

function wabtoolCornerTooltip(cornerSquares) {
	var squares = (cornerSquares && cornerSquares.length) ? cornerSquares.join(', ') : '';
	return '<span class="text-warning" data-bs-toggle="tooltip" title="Gridsquare corners fall into: ' + wabtoolEscapeHtml(squares) + '"><i class="fas fa-exclamation-triangle"></i></span>';
}

function wabtoolRenderScan(data) {
	if (!data || data.error) {
		$('.scanresult').html('<div class="alert alert-danger mb-0">' + ((data && data.error) ? wabtoolEscapeHtml(data.error) : 'An error occurred while processing the request.') + '</div>');
		$('#applyWab').addClass('d-none');
		return;
	}

	var s = data.summary;
	var html = '<p class="mb-2">'
		+ '<strong>' + s.qsos_scanned + '</strong> QSOs with gridsquare &middot; '
		+ '<strong>' + s.unique_grids + '</strong> unique grids &middot; '
		+ '<strong>' + s.matched + '</strong> matched to a WAB square';
	if (s.ambiguous) {
		html += ' &middot; <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + s.ambiguous + ' straddling square boundaries</span>';
	}
	if (s.unmatched) {
		html += ' &middot; ' + s.unmatched + ' outside WAB coverage';
	}
	html += '</p>';

	if (!data.rows.length) {
		html += '<div class="alert alert-info mb-0">No QSOs found that need a WAB square.</div>';
		$('.scanresult').html(html);
		$('#applyWab').addClass('d-none');
		return;
	}

	html += '<div class="table-responsive"><table class="table table-sm table-striped">'
		+ '<thead><tr>'
		+ '<th style="width: 2rem;"><input type="checkbox" id="wabtoolSelectAll" checked></th>'
		+ '<th>Date/Time</th><th>Callsign</th><th>Band</th><th>Grid</th><th>WAB Square</th><th>Station</th>'
		+ '</tr></thead><tbody>';

	$.each(data.rows, function(i, row) {
		html += '<tr>'
			+ '<td>' + (row.square ? '<input type="checkbox" class="wabtool-row" value="' + row.id + '" checked>' : '') + '</td>'
			+ '<td class="text-nowrap">' + wabtoolEscapeHtml(row.datetime) + '</td>'
			+ '<td class="callsign">' + wabtoolEscapeHtml(row.callsign) + '</td>'
			+ '<td>' + wabtoolEscapeHtml(row.band) + '</td>'
			+ '<td>' + wabtoolEscapeHtml(row.grid) + '</td>'
			+ '<td class="text-nowrap">';
		if (row.square) {
			html += '<span class="badge bg-primary">' + wabtoolEscapeHtml(row.square) + '</span>';
			if (row.ambiguous) {
				html += ' ' + wabtoolCornerTooltip(row.corner_squares);
			}
		} else {
			html += '<span class="text-muted">&mdash;</span>';
		}
		html += ' <a href="#" class="wabtool-map text-muted" data-grid="' + wabtoolEscapeHtml(row.grid) + '" data-bs-toggle="tooltip" title="Show on map"><i class="fas fa-map-marked-alt"></i></a>';
		html += '</td>'
			+ '<td>' + wabtoolEscapeHtml(row.station) + '</td>'
			+ '</tr>';
	});

	html += '</tbody></table></div>';

	$('.scanresult').html(html);
	$('.scanresult [data-bs-toggle="tooltip"]').tooltip();
	$('#applyWab').removeClass('d-none');
}

function wabtoolRenderApplyResult(data) {
	if (!data || data.error) {
		$('.applyresult').html('<div class="alert alert-danger mb-0">' + ((data && data.error) ? wabtoolEscapeHtml(data.error) : 'An error occurred while processing the request.') + '</div>');
		return;
	}

	var squares = Object.keys(data.squares || {}).map(function(k) {
		return k + ' (' + data.squares[k] + ')';
	}).join(', ');

	var html = '<div class="alert alert-success mb-2">'
		+ '<strong>' + data.updated + '</strong> QSO(s) updated'
		+ (data.skipped ? ', <strong>' + data.skipped + '</strong> skipped' : '')
		+ (squares ? ' &mdash; ' + wabtoolEscapeHtml(squares) : '')
		+ '</div>';

	$('.applyresult').html(html);
}

function wabtoolStartScan() {
	$('#startScan').addClass('running').prop('disabled', true);
	$('.scanresult').html('');
	$('.applyresult').html('');
	$('#applyWab').addClass('d-none');
	$.ajax({
		url: site_url + '/wabtool/scan',
		type: 'POST',
		dataType: 'json',
		data: {
			station_id: $('#de').val()
		},
		success: wabtoolRenderScan,
		error: function() {
			wabtoolRenderScan(null);
		},
		complete: function() {
			$('#startScan').removeClass('running').prop('disabled', false);
		}
	});
}

// Map popup: grid rectangle + WAB square outline(s)
function wabtoolOpenMap(grid) {
	var dialog = BootstrapDialog.show({
		title: grid,
		size: BootstrapDialog.SIZE_WIDE,
		nl2br: false,
		message: '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>',
		buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
	});

	$.ajax({
		url: site_url + '/wabtool/map_data',
		type: 'POST',
		dataType: 'json',
		data: {
			grid: grid
		},
		success: function(data) {
			wabtoolRenderMap(data, dialog);
		},
		error: function() {
			wabtoolRenderMap(null, dialog);
		}
	});
}

function wabtoolRenderMap(data, dialog) {
	if (!data || data.error) {
		dialog.setMessage('<div class="alert alert-warning mb-0">' + ((data && data.error) ? wabtoolEscapeHtml(data.error) : 'Error loading map data.') + '</div>');
		return;
	}

	var mapId = 'wabtool_map_' + Math.random().toString(36).slice(2, 10);
	dialog.setMessage('<div id="' + mapId + '" style="width:100%; height:60vh;"></div>');

	var map = L.map(mapId).setView([data.lat, data.lng], 11);
	L.tileLayer(option_map_tile_server, {
		attribution: option_map_tile_server_copyright,
		maxZoom: 18
	}).addTo(map);

	var gridBounds = L.latLngBounds(
		[data.grid_bounds.south, data.grid_bounds.west],
		[data.grid_bounds.north, data.grid_bounds.east]
	);

	// WAB square outlines: assigned square in blue, extra corner squares dashed orange
	var squareLayer = L.geoJSON({ type: 'FeatureCollection', features: data.features }, {
		style: function(feature) {
			return feature.properties.role === 'assigned'
				? { color: '#2196f3', weight: 2, fillColor: '#2196f3', fillOpacity: 0.15 }
				: { color: '#ff9800', weight: 2, fillColor: '#ff9800', fillOpacity: 0.1, dashArray: '6 4' };
		},
		onEachFeature: function(feature, layer) {
			layer.bindTooltip(feature.properties.name, { permanent: true, direction: 'center' });
		}
	}).addTo(map);

	// gridsquare rectangle in red
	L.rectangle(gridBounds, { color: '#ff4136', weight: 2, fillOpacity: 0.08, dashArray: '4 3' }).addTo(map);

	L.marker([data.lat, data.lng]).addTo(map).bindPopup(
		'<b>' + wabtoolEscapeHtml(data.grid) + '</b><br>WAB: ' + (data.square ? wabtoolEscapeHtml(data.square) : '&mdash;')
	).openPopup();

	var fitBounds = gridBounds;
	try { fitBounds = fitBounds.extend(squareLayer.getBounds()); } catch (e) { /* no square features */ }

	try {
		map.fitBounds(fitBounds.pad(0.3));
	} catch (e) { /* invalid bounds */ }

	setTimeout(function() { map.invalidateSize(); }, 120);
}

// Bind the WAB tool handlers (defined once, bound once jQuery is available)
function bindWabTool() {

	$('#startScan').on('click', wabtoolStartScan);

	$('#applyWab').on('click', function() {
		var ids = $('.wabtool-row:checked').map(function() {
			return $(this).val();
		}).get();

		if (!ids.length) {
			BootstrapDialog.show({
				type: BootstrapDialog.TYPE_WARNING,
				message: 'No QSOs selected.',
				buttons: [{ label: 'OK', action: function(d) { d.close(); } }]
			});
			return;
		}

		var btn = $('#applyWab');
		BootstrapDialog.confirm({
			title: btn.data('confirm-title'),
			message: btn.data('confirm-msg') + ' (' + ids.length + ')',
			type: BootstrapDialog.TYPE_PRIMARY,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-success',
			callback: function(result) {
				if (!result) {
					return;
				}
				btn.addClass('running').prop('disabled', true);
				$.ajax({
					url: site_url + '/wabtool/apply',
					type: 'POST',
					dataType: 'json',
					data: {
						ids: JSON.stringify(ids)
					},
					success: function(data) {
						wabtoolRenderApplyResult(data);
						wabtoolStartScan(); // refresh the preview table
					},
					error: function() {
						wabtoolRenderApplyResult(null);
					},
					complete: function() {
						btn.removeClass('running').prop('disabled', false);
					}
				});
			}
		});
	});

	// Select-all checkbox toggles only the selectable rows (delegated so it
	// survives re-rendering of the preview table)
	$(document).on('change', '#wabtoolSelectAll', function() {
		$('.wabtool-row').prop('checked', this.checked);
	});

	// Map popup per preview row (delegated so it survives re-rendering)
	$(document).on('click', '.wabtool-map', function(e) {
		e.preventDefault();
		wabtoolOpenMap($(this).attr('data-grid'));
	});
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
					bindWabTool();
				}
			}, 100);
		} else {
			bindWabTool();
		}
	});
} else {
	// jQuery already loaded
	$(document).ready(function() {
		bindWabTool();
	});
}
