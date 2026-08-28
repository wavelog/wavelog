// WAB from Gridsquare tool: batch assign WAB squares from logged gridsquares
// (functions are prefixed wabtool* because section scripts share the global scope)
//
// The preview table is a server-side DataTable: the candidate set can reach
// log size (tens of thousands of rows), so only one page is ever fetched
// and rendered. Selection state lives in wabtoolSelected (per id) plus the
// wabtoolAllMatching flag ("apply everything the scan matches").

var wabtoolTable = null; // DataTables API instance of the preview table
var wabtoolSelected = {}; // qso id -> 1, rows checked by the user
var wabtoolAllMatching = false; // apply every matching QSO, not just checked ones
var wabtoolRecordsFiltered = 0; // rows matching the current table filter
var wabtoolSummaryPending = false; // request the one-time scan summary on the next ajax

function wabtoolEscapeHtml(s) {
	return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
		return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
	});
}

function wabtoolCornerTooltip(cornerSquares) {
	var squares = (cornerSquares && cornerSquares.length) ? cornerSquares.join(', ') : '';
	return '<span class="text-warning" data-bs-toggle="tooltip" title="Gridsquare corners fall into: ' + wabtoolEscapeHtml(squares) + '"><i class="fas fa-exclamation-triangle"></i></span>';
}

function wabtoolRenderSummary(summary) {
	var html = '';
	if (summary) {
		html += '<p class="mb-2">'
			+ '<strong>' + summary.qsos_scanned + '</strong> QSOs with gridsquare &middot; '
			+ '<strong>' + summary.unique_grids + '</strong> unique grids &middot; '
			+ '<strong>' + summary.matched + '</strong> matched to a WAB square';
		if (summary.ambiguous) {
			html += ' &middot; <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + summary.ambiguous + ' straddling square boundaries</span>';
		}
		if (summary.unmatched) {
			html += ' &middot; ' + summary.unmatched + ' outside WAB coverage';
		}
		html += '</p>';
	}
	html += '<button type="button" class="btn btn-sm btn-outline-primary mb-2" id="wabtoolSelectAllMatching"></button>';
	$('.wabtool-summary').html(html);
}

function wabtoolUpdateSelectAllButton() {
	var btn = $('#wabtoolSelectAllMatching');
	if (!btn.length) {
		return;
	}
	if (wabtoolAllMatching) {
		btn.text('Clear selection (' + wabtoolRecordsFiltered + ' selected)');
	} else {
		var n = wabtoolRecordsFiltered;
		btn.text(n ? 'Select all ' + n + ' matching QSOs' : 'Select all matching QSOs');
	}
}

function wabtoolSyncHeaderCheckbox() {
	var $rows = $('#wabtoolTable tbody .wabtool-row');
	$('#wabtoolSelectAll').prop('checked', $rows.length > 0 && $rows.length === $rows.filter(':checked').length);
}

// Render the preview table shell and let DataTables fetch page 1 itself
function wabtoolInitTable() {
	var html = '<div class="wabtool-summary"></div>'
		+ '<div class="table-responsive"><table class="table table-sm table-striped" id="wabtoolTable">'
		+ '<thead><tr>'
		+ '<th style="width: 2rem;"><input type="checkbox" id="wabtoolSelectAll"></th>'
		+ '<th>Date/Time</th><th>Callsign</th><th>Band</th><th>Grid</th><th>WAB Square</th><th>Station</th>'
		+ '</tr></thead><tbody></tbody></table></div>';

	$('.scanresult').html(html);

	wabtoolTable = $('#wabtoolTable').DataTable({
		serverSide: true,
		processing: true,
		ajax: {
			url: site_url + '/wabtool/scan',
			type: 'POST',
			data: function(d) {
				d.station_id = $('#de').val();
				if (wabtoolSummaryPending) {
					// whole-log summary only on the first load after a scan
					d.wabtool_summary = 1;
					wabtoolSummaryPending = false;
				}
			}
		},
		pageLength: 25,
		lengthMenu: [10, 25, 50, 100],
		order: [[1, 'desc']],
		searchDelay: 400,
		language: {
			url: getDataTablesLanguageUrl(),
		},
		columns: [
			{
				data: null,
				orderable: false,
				searchable: false,
				render: function(data, type, row) {
					if (type !== 'display') {
						return '';
					}
					if (!row.square) {
						return ''; // unresolvable rows are never selectable
					}
					return '<input type="checkbox" class="wabtool-row" value="' + row.id + '"'
						+ ((wabtoolAllMatching || wabtoolSelected[row.id]) ? ' checked' : '') + '>';
				}
			},
			{ data: 'datetime', render: wabtoolRenderCell },
			{ data: 'callsign', render: wabtoolRenderCell, className: 'callsign' },
			{ data: 'band', render: wabtoolRenderCell },
			{ data: 'grid', render: wabtoolRenderCell },
			{
				data: 'square',
				orderable: false,
				render: function(data, type, row) {
					if (type !== 'display') {
						return row.square || '';
					}
					var html = row.square
						? '<span class="badge bg-primary">' + wabtoolEscapeHtml(row.square) + '</span>'
						: '<span class="text-muted">&mdash;</span>';
					if (row.square && row.ambiguous) {
						html += ' ' + wabtoolCornerTooltip(row.corner_squares);
					}
					html += ' <a href="#" class="wabtool-map text-muted" data-grid="' + wabtoolEscapeHtml(row.grid) + '" data-bs-toggle="tooltip" title="Show on map"><i class="fas fa-map-marked-alt"></i></a>';
					return html;
				}
			},
			{ data: 'station', render: wabtoolRenderCell }
		],
		createdRow: function(row) {
			$(row).find('[data-bs-toggle="tooltip"]').tooltip();
		}
	});

	// Every ajax response updates the counters, the summary area and the
	// selection affordances (the one-time summary rides the first response)
	wabtoolTable.on('xhr', function(e, settings, json) {
		if (!json || json.error) {
			return;
		}
		wabtoolRecordsFiltered = json.recordsFiltered || 0;

		if (json.summary !== undefined) {
			if (json.recordsTotal > 0) {
				wabtoolRenderSummary(json.summary);
				$('#applyWab').removeClass('d-none');
			} else {
				$('.wabtool-summary').html('<div class="alert alert-info mb-2">No QSOs found that need a WAB square.</div>');
				$('#applyWab').addClass('d-none');
			}
		}
		wabtoolUpdateSelectAllButton();
	});

	wabtoolTable.on('draw', function() {
		wabtoolSyncHeaderCheckbox();
		$('#startScan').removeClass('running').prop('disabled', false);
	});

	wabtoolTable.on('error', function() {
		$('.wabtool-summary').html('<div class="alert alert-danger mb-2">An error occurred while processing the request.</div>');
		$('#startScan').removeClass('running').prop('disabled', false);
		$('#applyWab').addClass('d-none');
	});
}

function wabtoolRenderCell(data, type) {
	if (type !== 'display') {
		return data;
	}
	return wabtoolEscapeHtml(data);
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

function wabtoolStartScan(clearApplyResult) {
	$('#startScan').addClass('running').prop('disabled', true);
	if (clearApplyResult !== false) {
		$('.applyresult').html('');
	}
	$('#applyWab').addClass('d-none');

	// release the old table before its markup is wiped
	if (wabtoolTable !== null) {
		try { wabtoolTable.destroy(); } catch (e) { /* already gone */ }
		wabtoolTable = null;
	}
	$('.scanresult').html('');

	wabtoolSelected = {};
	wabtoolAllMatching = false;
	wabtoolRecordsFiltered = 0;
	wabtoolSummaryPending = true;

	wabtoolInitTable();
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

	$('#startScan').on('click', function() {
		wabtoolStartScan();
	});

	$('#applyWab').on('click', function() {
		var payloadIds, count, searchData;
		if (wabtoolAllMatching) {
			// let the server enumerate the matching set; station filter and
			// table search mirror the scan request
			payloadIds = 'ALL';
			count = wabtoolRecordsFiltered;
			searchData = {
				ids: payloadIds,
				station_id: $('#de').val(),
				search: wabtoolTable !== null ? wabtoolTable.search() : ''
			};
		} else {
			var ids = Object.keys(wabtoolSelected);
			if (!ids.length) {
				BootstrapDialog.show({
					type: BootstrapDialog.TYPE_WARNING,
					message: 'No QSOs selected.',
					buttons: [{ label: 'OK', action: function(d) { d.close(); } }]
				});
				return;
			}
			payloadIds = JSON.stringify(ids);
			count = ids.length;
			// One JSON string var, not ids[]: arrays post one PHP input var
			// per row and max_input_vars silently drops the excess, causing
			// a partial apply
			searchData = { ids: payloadIds };
		}

		var btn = $('#applyWab');
		BootstrapDialog.confirm({
			title: btn.data('confirm-title'),
			message: btn.data('confirm-msg') + ' (' + count + ')',
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
					data: searchData,
					success: function(data) {
						wabtoolRenderApplyResult(data);
						// refresh the preview table, keep the result alert visible
						wabtoolStartScan(false);
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

	// Row checkbox: track per-id selection. Unchecking a row while
	// "select all matching" is active leaves that mode (everything else
	// would otherwise still be applied).
	$(document).on('change', '.wabtool-row', function() {
		var id = $(this).val();
		if (this.checked) {
			wabtoolSelected[id] = 1;
		} else {
			if (wabtoolAllMatching) {
				wabtoolAllMatching = false;
				wabtoolSelected = {};
				$('.wabtool-row').prop('checked', false);
			} else {
				delete wabtoolSelected[id];
			}
		}
		wabtoolSyncHeaderCheckbox();
		wabtoolUpdateSelectAllButton();
	});

	// Header checkbox toggles the selectable rows on the current page only
	$(document).on('change', '#wabtoolSelectAll', function() {
		var headerChecked = this.checked;
		$('#wabtoolTable tbody .wabtool-row').each(function() {
			$(this).prop('checked', headerChecked);
			var id = $(this).val();
			if (headerChecked) {
				wabtoolSelected[id] = 1;
			} else {
				delete wabtoolSelected[id];
			}
		});
		wabtoolUpdateSelectAllButton();
	});

	$(document).on('click', '#wabtoolSelectAllMatching', function() {
		wabtoolAllMatching = !wabtoolAllMatching;
		if (!wabtoolAllMatching) {
			wabtoolSelected = {};
		}
		$('.wabtool-row').prop('checked', wabtoolAllMatching);
		wabtoolSyncHeaderCheckbox();
		wabtoolUpdateSelectAllButton();
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
