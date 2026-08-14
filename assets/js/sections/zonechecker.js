// Bind the zone-check click handler (defined once, bound once jQuery is available)
function bindZoneCheck() {
	$('#startDxccCheck').on('click', function() {
		let de = $('#de').val();
		let zoneType = $('#zoneType').val();
		$('#startDxccCheck').addClass('running').prop('disabled', true);
		$('.result').html('');
		$.ajax({
			url: site_url + '/zonechecker/doWazCheck',
			type: "POST",
			data: {
				de: de,
				zoneType: zoneType
			},
			success: function(response) {
				$('.result').html(response);
				$('.result [data-bs-toggle="tooltip"]').tooltip();
				$('#startDxccCheck').removeClass('running').prop('disabled', false);
			},
			error: function(xhr, status, error) {
				$('.result').html('<div class="alert alert-danger" role="alert">An error occurred while processing the request.</div>');
				$('#startDxccCheck').removeClass('running').prop('disabled', false);
			}
		});
	});

	// Open every QSO with the given callsign in a modal dialog (delegated so it
	// survives re-rendering of the result table)
	$(document).on('click', '.callsign-search', function(e) {
		e.preventDefault();
		showCallQsos($(this).attr('data-call'));
	});

	// Open a map dialog for a gridsquare showing its geojson zone
	$(document).on('click', '.zone-map', function(e) {
		e.preventDefault();
		openZoneMap($(this).attr('data-grid'), $(this).attr('data-zonetype'), $(this).attr('data-zone'));
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

function openZoneMap(grid, zoneType, zoneNum) {
	let dialog = BootstrapDialog.show({
		title: grid + ' · ' + (zoneType === 'itu' ? 'ITU' : 'CQ') + ' ' + zoneNum,
		size: BootstrapDialog.SIZE_WIDE,
		nl2br: false,
		message: '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>',
		buttons: [{ label: 'Close', action: function(d) { d.close(); } }]
	});

	$.ajax({
		url: site_url + '/zonechecker/mapData',
		type: 'POST',
		dataType: 'json',
		data: { gridsquare: grid, zoneType: zoneType },
		success: function(data) {
			if (!data || data.error || !data.feature) {
				dialog.setMessage('<div class="alert alert-warning mb-0">' + ((data && data.error) ? data.error : 'No zone found for this gridsquare.') + '</div>');
				return;
			}
			var mapId = 'zonechecker_map_' + Math.random().toString(36).slice(2, 10);
			dialog.setMessage('<div id="' + mapId + '" style="width:100%; height:60vh;"></div>');
			renderZoneMap(data, grid, zoneType, mapId);
		},
		error: function() {
			dialog.setMessage('<div class="alert alert-danger mb-0">Error loading map data.</div>');
		}
	});
}

function renderZoneMap(data, grid, zoneType, mapId) {
	var map = L.map(mapId).setView([data.lat, data.lng], 5);

	L.tileLayer(option_map_tile_server, {
		attribution: option_map_tile_server_copyright,
		maxZoom: 18
	}).addTo(map);

	var zoneLayer = L.geoJSON({ type: 'FeatureCollection', features: [data.feature] }, {
		style: { color: '#ff4136', weight: 2, fillColor: '#ff4136', fillOpacity: 0.15 }
	}).addTo(map);

	var props = (data.feature && data.feature.properties) ? data.feature.properties : {};
	var zoneNo = zoneType === 'itu' ? (props.itu_zone_number || '') : (props.cq_zone_number || '');
	var zoneName = zoneType === 'itu' ? (props.itu_zone_name || '') : (props.cq_zone_name || '');
	var label = (zoneType === 'itu' ? 'ITU' : 'CQ') + ' ' + zoneNo;

	L.marker([data.lat, data.lng]).addTo(map).bindPopup(
		'<b>' + grid + '</b><br>' + label + (zoneName ? '<br>' + zoneName : '')
	).openPopup();

	try {
		map.fitBounds(zoneLayer.getBounds().pad(0.15));
	} catch (e) { /* single-point or invalid bounds */ }

	setTimeout(function() { map.invalidateSize(); }, 120);
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
					bindZoneCheck();
				}
			}, 100);
		} else {
			bindZoneCheck();
		}
	});
} else {
	// jQuery already loaded
	$(document).ready(function() {
		bindZoneCheck();
	});
}
