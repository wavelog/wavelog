var wabtoolTable = null; // DataTables API instance of the preview table
var wabtoolSelected = {}; // qso id -> 1, rows checked by the user

function wabtoolEscapeHtml(s) {
	return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
		return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
	});
}

function wabtoolSyncHeaderCheckbox() {
	var $rows = $('#wabtoolTable tbody .wabtool-row');
	$('#wabtoolSelectAll').prop('checked', $rows.length > 0 && $rows.length === $rows.filter(':checked').length);
}

// Drawn pages re-render the rows: put the selection state back on the checkboxes
function wabtoolSyncRowChecks() {
	$('#wabtoolTable tbody .wabtool-row').each(function() {
		this.checked = wabtoolSelected[this.value] === 1;
	});
}

function wabtoolLoadResult() {
	// release the old table before its markup is replaced
	if (wabtoolTable !== null) {
		try { wabtoolTable.destroy(); } catch (e) { /* already gone */ }
		wabtoolTable = null;
	}

	$('.scanresult').load(site_url + '/wabtool/table', {
		station_id: $('#de').val()
	}, function(_, textStatus) {
		// a failed request, or a login-page redirect (expired session), would
		// leave no result behind
		if (textStatus !== 'success' || !$('#wabtoolResult').length) {
			$('.scanresult').html('<div class="alert alert-danger mb-2">An error occurred while processing the request.</div>');
			$('#startScan').removeClass('running').prop('disabled', false);
			$('#applyWab').addClass('d-none');
			return;
		}

		$('#applyWab').toggleClass('d-none', !$('#wabtoolTable').length);

		if ($('#wabtoolTable').length) {
			wabtoolTable = $('#wabtoolTable').DataTable({
				stateSave: true,
				pageLength: 25,
				lengthMenu: [10, 25, 50, 100],
				order: [[1, 'desc']],
				language: {
					url: getDataTablesLanguageUrl(),
				},
				columnDefs: [
					{ orderable: false, targets: 0 },
				],
				createdRow: function(row) {
					$(row).find('[data-bs-toggle="tooltip"]').tooltip();
				}
			});

			// redrawn pages lose their checkbox state: restore it
			wabtoolTable.on('draw', function() {
				wabtoolSyncRowChecks();
				wabtoolSyncHeaderCheckbox();
			});

			// Reset checkboxes on change of number of entries being shown
			wabtoolTable.on('length.dt', function (e, settings, len) {
				$('#wabtoolTable tbody .wabtool-row').each(function() {
					var id = $(this).val();
					delete wabtoolSelected[id];
				});
			});
		}

		// the table draws synchronously from the served rows, so the load
		// completing is the moment the scan is done (the first draw happens
		// during .DataTable(), before any draw handler could observe it)
		$('#startScan').removeClass('running').prop('disabled', false);
	});
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

	wabtoolSelected = {};

	wabtoolLoadResult();
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

	// legend for the outlines (swatch styles mirror the layer styles above);
	// the corner-squares row only appears when the grid straddles a boundary
	var hasCornerSquares = (data.features || []).some(function(feature) {
		return feature.properties.role === 'corner';
	});
	var legend = L.control({ position: 'topright' });
	legend.onAdd = function() {
		var div = L.DomUtil.create('div', 'legend');
		var html = '<table border="0">'
			+ '<tr><td><i style="border:2px solid #2196f3; background:rgba(33,150,243,0.15)"></i><span>Assigned WAB square</span></td></tr>'
			+ (hasCornerSquares ? '<tr><td><i style="border:2px dashed #ff9800; background:rgba(255,152,0,0.1)"></i><span>Corner squares</span></td></tr>' : '')
			+ '<tr><td><i style="border:2px dashed #ff4136; background:rgba(255,65,54,0.08)"></i><span>Gridsquare</span></td></tr>'
			+ '</table>';
		div.innerHTML = html;
		return div;
	};
	legend.addTo(map);


	var fitBounds = squareLayer.getBounds().extend(gridBounds);
	if (!fitBounds.isValid()) {
		fitBounds = gridBounds;
	}

	setTimeout(function() {
		map.invalidateSize();
		try {
			map.fitBounds(fitBounds.pad(0.3));
		} catch (e) { /* invalid bounds */ }
	}, 120);
}

// Bind the WAB tool handlers (defined once, bound once jQuery is available)
function bindWabTool() {

	$('#startScan').on('click', function() {
		wabtoolStartScan();
	});

	$('#applyWab').on('click', function() {
		var ids = Object.keys(wabtoolSelected);
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

	// Row checkbox: track per-id selection
	$(document).on('change', '.wabtool-row', function() {
		var id = $(this).val();
		if (this.checked) {
			wabtoolSelected[id] = 1;
		} else {
			delete wabtoolSelected[id];
		}
		wabtoolSyncHeaderCheckbox();
	});

	// Header checkbox toggles the selectable rows on the current page only
	$(document).on('change', '#wabtoolSelectAll', function() {
		var headerChecked = this.checked;
		$('#wabtoolTable tbody .wabtool-row').each(function() {
			this.checked = headerChecked;
			var id = $(this).val();
			if (headerChecked) {
				wabtoolSelected[id] = 1;
			} else {
				delete wabtoolSelected[id];
			}
		});
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
