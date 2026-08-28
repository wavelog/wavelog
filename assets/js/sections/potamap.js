let confirmedColor = 'rgba(144,238,144)';
if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
	confirmedColor = user_map_custom.qsoconfirm.color;
}
let workedColor = 'rgba(229, 165, 10)';
if (typeof(user_map_custom.qso) !== 'undefined') {
	workedColor = user_map_custom.qso.color;
}

let osmUrl = $('#potamapjs').attr("tileUrl");

// Holds the current Leaflet map instance so we can dispose of it properly
// before rebuilding. See load_pota_map2().
let potaMap = null;

function getPotaFilterData() {
	return {
		band: $('#band').val(),
		mode: $('#mode').val(),
		worked: +$('#worked').prop('checked'),
		confirmed: +$('#confirmed').prop('checked'),
		qsl: +$('#qsl').prop('checked'),
		lotw: +$('#lotw').prop('checked'),
		eqsl: +$('#eqsl').prop('checked'),
		qrz: +$('#qrz').prop('checked'),
		clublog: +$('#clublog').prop('checked'),
		dateFrom: $('#dateFrom').val(),
		dateTo: $('#dateTo').val(),
	};
}

function load_pota_map() {
	$('.nav-tabs a[href="#potamaptab"]').tab('show');
	refresh_pota_map();
}

function refresh_pota_map() {
	$.ajax({
		url: base_url + 'index.php/awards/pota_map',
		type: 'post',
		data: getPotaFilterData(),
		success: function(data) {
			load_pota_map2(data);
		},
		error: function() {
			BootstrapDialog.alert({
				title: lang_general_word_error,
				message: lang_pota_map_error,
				type: BootstrapDialog.TYPE_DANGER,
			});
		},
	});
}

function refresh_pota_table() {
	$.ajax({
		url: base_url + 'index.php/awards/pota_table',
		type: 'post',
		data: getPotaFilterData(),
		success: function(resp) {
			var dt = $('#potatable').DataTable();
			dt.clear();
			dt.rows.add(resp.data || []);
			dt.draw();
		},
		error: function() {
			BootstrapDialog.alert({
				title: lang_general_word_error,
				message: lang_pota_map_error,
				type: BootstrapDialog.TYPE_DANGER,
			});
		},
	});
}

function applyPotaFilters() {
	refresh_pota_table();
	if ($('#potamaptab').hasClass('active')) {
		refresh_pota_map();
	}
	bootstrap.Dropdown.getOrCreateInstance(document.getElementById('potaFilterDropdown')).hide();
}

function load_pota_map2(data) {

	// Tear down the previous map instance properly instead of just dropping
	// its DOM node. map.remove() clears layers, panes and every event
	// listener (including document-level ones) and frees the container for
	// re-use, so repeated "Apply Filters" refreshes don't accumulate leaked
	// Leaflet internals.
	if (potaMap !== null) {
		potaMap.remove();
		potaMap = null;
	}

	$("#potamap_status").empty();

	var map = new L.Map('potamap', {
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
	});
	potaMap = map;

	L.tileLayer(
		osmUrl,
		{
			attribution: option_map_tile_server_copyright,
			maxZoom: 18
		}
	).addTo(map);

	var confirmedCount = 0;
	var workedNotConfirmedCount = 0;
	var withoutCoords = 0;

	var markers = L.markerClusterGroup({
		chunkedLoading: true,
		maxClusterRadius: 50,
		showCoverageOnHover: false
	});

	for (var i = 0; i < data.length; i++) {
		var D = data[i];
		if (D.status == 'C') {
			confirmedCount++;
		} else {
			workedNotConfirmedCount++;
		}
		if (D.lat && D.lon) {
			var mapColor = (D.status == 'C') ? confirmedColor : workedColor;
			addMarker(L, D, mapColor, markers);
		} else {
			withoutCoords++;
		}
	}

	markers.addTo(map);

	if (data.length === 0) {
		$("#potamap_status").html('<div class="alert alert-info">' + lang_pota_no_refs + '</div>');
	} else if (withoutCoords === data.length) {
		$("#potamap_status").html('<div class="alert alert-warning">' + lang_pota_dir_empty + '</div>');
	}

	/*Legend specific*/
	var legend = L.control({ position: "topright" });

	legend.onAdd = function(map) {
		var div = L.DomUtil.create("div", "legend");
		var band = $('#band').val();
		if (band == 'All') {
			div.innerHTML += "<h4>" + lang_award_info_all_bands + "</h4>";
		} else {
			div.innerHTML += "<h4>Band: " + band + "</h4>";
		}
		div.innerHTML += '<i style="background: ' + confirmedColor + '"></i><span>' + lang_general_word_confirmed + ' (' + confirmedCount + ')</span><br>';
		div.innerHTML += '<i style="background: ' + workedColor + '"></i><span>' + lang_general_word_worked_not_confirmed + ' (' + workedNotConfirmedCount + ')</span><br>';
		div.innerHTML += '<i style="background: #999"></i><span>' + lang_pota_without_coordinates + ' (' + withoutCoords + ')</span><br>';
		return div;
	};

	legend.addTo(map);

	map.setView([20, 0], 2);
}

function addMarker(L, D, mapColor, markers) {
	var dot = L.circleMarker([D.lat, D.lon], {
		radius: 6,
		weight: 1,
		color: '#fff',
		fillColor: mapColor,
		fillOpacity: 0.9,
		pota: D.reference
	});
	dot.bindTooltip(D.reference + ' - ' + (D.name || ''));
	dot.on('click', onClick);
	markers.addLayer(dot);
}

function onClick(e) {
	var marker = e.target;
	displayContactsOnMap($("#potamap"), marker.options.pota, $('#band').val(), 'All', 'All', $('#mode').val(), 'POTA', '', $('#dateFrom').val(), $('#dateTo').val());
}

// Date presets
function applyPreset(preset) {
	const dateFrom = document.getElementById('dateFrom');
	const dateTo = document.getElementById('dateTo');
	const today = new Date();

	function formatDate(date) {
		const year = date.getUTCFullYear();
		const month = String(date.getUTCMonth() + 1).padStart(2, '0');
		const day = String(date.getUTCDate()).padStart(2, '0');
		return `${year}-${month}-${day}`;
	}

	switch(preset) {
		case 'today':
			dateFrom.value = formatDate(today);
			dateTo.value = formatDate(today);
			break;
		case 'yesterday':
			const yesterday = new Date(today);
			yesterday.setDate(yesterday.getUTCDate() - 1);
			dateFrom.value = formatDate(yesterday);
			dateTo.value = formatDate(yesterday);
			break;
		case 'last7days':
			const sevenDaysAgo = new Date(today);
			sevenDaysAgo.setDate(sevenDaysAgo.getUTCDate() - 7);
			dateFrom.value = formatDate(sevenDaysAgo);
			dateTo.value = formatDate(today);
			break;
		case 'last30days':
			const thirtyDaysAgo = new Date(today);
			thirtyDaysAgo.setDate(thirtyDaysAgo.getUTCDate() - 30);
			dateFrom.value = formatDate(thirtyDaysAgo);
			dateTo.value = formatDate(today);
			break;
		case 'thismonth':
			const firstDayOfMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1));
			dateFrom.value = formatDate(firstDayOfMonth);
			dateTo.value = formatDate(today);
			break;
		case 'lastmonth':
			const firstDayOfLastMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth() - 1, 1));
			const lastDayOfLastMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 0));
			dateFrom.value = formatDate(firstDayOfLastMonth);
			dateTo.value = formatDate(lastDayOfLastMonth);
			break;
		case 'thisyear':
			const firstDayOfYear = new Date(Date.UTC(today.getUTCFullYear(), 0, 1));
			dateFrom.value = formatDate(firstDayOfYear);
			dateTo.value = formatDate(today);
			break;
		case 'lastyear':
			const lastYear = today.getUTCFullYear() - 1;
			const firstDayOfLastYear = new Date(Date.UTC(lastYear, 0, 1));
			const lastDayOfLastYear = new Date(Date.UTC(lastYear, 11, 31));
			dateFrom.value = formatDate(firstDayOfLastYear);
			dateTo.value = formatDate(lastDayOfLastYear);
			break;
	}
}

function resetDates() {
	document.getElementById('dateFrom').value = '';
	document.getElementById('dateTo').value = '';
}
