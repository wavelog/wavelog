/*
 * Database Tools (Data Repair Tools)
 *
 * Moved from assets/js/sections/logbookadvanced.js when the dbtools dialog
 * became its own page (Tools -> Database Tools).
 */

// Shared DataTable init for all dbtools result tables.
// opts.ordering: enable ordering (default false)
// opts.textExtractor: fn($cell) -> string, custom cell text for filter options
// opts.onInit: fn, called at the end of initComplete
function initDbtoolsTable(tableSelector, opts) {
	opts = opts || {};
	$(tableSelector).DataTable({
		"pageLength": 25,
		responsive: false,
		ordering: !!opts.ordering,
		"scrollY": "510px",
		"scrollCollapse": true,
		"paging": false,
		"scrollX": false,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		initComplete: function () {
			this.api()
				.columns('.select-filter')
				.every(function () {
					var column = this;
					var select = $('<select class="form-select form-select-sm"><option value=""></option></select>')
						.appendTo($(column.footer()).empty())
						.on('change', function () {
							var val = $.fn.dataTable.util.escapeRegex($(this).val());
							// Search in rendered content, not just data
							column.search(val ? val : '', true, false).draw();
						});

					// Count occurrences of each unique value
					var counts = {};
					column.nodes().flatten().to$().each(function () {
						var $cell = $(this);
						var text = opts.textExtractor ? opts.textExtractor($cell) : $cell.text().trim();
						if (text) {
							counts[text] = (counts[text] || 0) + 1;
						}
					});

					// Add options with counts
					for (var text in counts) {
						if (!select.find('option[value="' + text + '"]').length) {
							select.append($('<option/>', { value: text }).text(text + ' (' + counts[text] + ')'));
						}
					}

					// Sort options
					select.find('option:not(:first)').sort(function(a, b) {
						return a.text.localeCompare(b.text);
					}).appendTo(select);
				});
			if (opts.onInit) opts.onInit();
		},
	});
}

function checkUpdateDistances() {
	$('#checkUpdateDistancesBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkdistance',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkUpdateDistancesBtn').prop("disabled", false).removeClass("running");

			$('.result').html(response);
		},
		error: function(xhr, status, error) {
			$('#checkUpdateDistancesBtn').prop("disabled", false).removeClass("running");

			let errorMsg = 'Error checking distance information';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMsg += ': ' + xhr.responseJSON.message;
			}

			BootstrapDialog.alert({
				title: 'Error',
				message: errorMsg,
				type: BootstrapDialog.TYPE_DANGER
			});
		}
	});
}

function checkFixContinent() {
	$('#checkFixContinentBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkcontinent',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkFixContinentBtn').prop("disabled", false).removeClass("running");
			$('.result').html(response);
		},
		error: function(xhr, status, error) {
			$('#checkFixContinentBtn').prop('disabled', false).removeClass("running");

			let errorMsg = 'Error checking continent information';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMsg += ': ' + xhr.responseJSON.message;
			}

			BootstrapDialog.alert({
				title: 'Error',
				message: errorMsg,
				type: BootstrapDialog.TYPE_DANGER
			});
		}
	});
}

function checkFixState() {
	$('#checkFixStateBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkstate',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkFixStateBtn').prop("disabled", false).removeClass("running");

			$('.result').html(response);
		},
		error: function(xhr, status, error) {
			$('#checkFixStateBtn').prop('disabled', false).removeClass("running");

			let errorMsg = 'Error checking state information';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMsg += ': ' + xhr.responseJSON.message;
			}

			BootstrapDialog.alert({
				title: 'Error',
				message: errorMsg,
				type: BootstrapDialog.TYPE_DANGER
			});
		}
	});
}

function fixState(dxcc, country) {
	$('#fixStateBtn_' + dxcc).prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/fixStateBatch',
		type: 'post',
		data: {
			dxcc: dxcc,
			country: country,
			stationid: $('#dbtools_station_id').val()
		},
		success: function (response) {
			$('#fixStateBtn_' + dxcc).prop("disabled", false).removeClass("running");
			$('.result').html(response);
		},
		error: function () {
			$('#fixStateBtn_' + dxcc).prop("disabled", false).removeClass("running");
		}
	});
}

function fixAllStates() {
	$('#fixAllStatesBtn').prop("disabled", true).addClass("running");
	// Disable the per-row "Run fix" buttons while the batch is running
	$('button[id^="fixStateBtn_"]').prop("disabled", true);

	$.ajax({
		url: base_url + 'index.php/dbtools/fixStateAll',
		type: 'post',
		data: {
			stationid: $('#dbtools_station_id').val()
		},
		success: function (response) {
			// The response replaces the whole result pane (including the
			// buttons above), so there is nothing left to re-enable here.
			$('.result').html(response);
		},
		error: function (xhr, status, error) {
			$('#fixAllStatesBtn').prop("disabled", false).removeClass("running");
			$('button[id^="fixStateBtn_"]').prop("disabled", false);

			let errorMsg = 'Error fixing state information';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMsg += ': ' + xhr.responseJSON.message;
			}

			BootstrapDialog.alert({
				title: 'Error',
				message: errorMsg,
				type: BootstrapDialog.TYPE_DANGER
			});
		}
	});
}

function openStateList(dxcc, country) {
	$('#openStateListBtn_' + dxcc).prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/openStateList',
		type: 'post',
		data: {
			dxcc: dxcc,
			country: country,
			stationid: $('#dbtools_station_id').val()
		},
		success: function (response) {
			$('#openStateListBtn_' + dxcc).prop("disabled", false).removeClass("running");
			BootstrapDialog.show({
				title: 'QSO List',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'options',
				nl2br: false,
				message: response,
				buttons: [
				{
					label: lang_admin_close,
					cssClass: 'btn-sm btn-secondary',
					id: 'closeButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					return;
				},
			});
		},
		error: function () {
			$('#openStateListBtn_' + dxcc).prop("disabled", false).removeClass("running");
		}
	});
}

function runUpdateDistancesFix(dialogItself) {
	$('#updateDistanceButton').prop("disabled", true).addClass("running");
	$.ajax({
		url: base_url + 'index.php/dbtools/updateDistances',
		data: {
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function (response) {
			$('#updateDistanceButton').prop("disabled", false).removeClass("running");
			if (dialogItself != '') {
				dialogItself.close();
			}
			$('.result').html(response);
		},
		error: function(xhr, status, error) {
			$('#updateDistanceButton').prop("disabled", false).removeClass("running");
			if (dialogItself != '') {
				dialogItself.close();
			}
			$('.result').html(error);
		}
	});
}

function runContinentFix(dialogItself) {
	$('#updateContinentButton').prop("disabled", true).addClass("running");
	$.ajax({
		url: base_url + 'index.php/dbtools/fixContinent',
		data: {
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function (response) {
			$('#updateContinentButton').prop("disabled", false).removeClass("running");
			if (dialogItself != '') {
				dialogItself.close();
			}
			$('.result').html(response);
		},
		error: function(xhr, status, error) {
			$('#updateContinentButton').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

let missingGridDialog = null;
let inMissingGridLookup = false;
let missingGridLookupFinished = false;
let missingGridStats = { updated: 0, notfound: 0, error: 0 };

function checkGrids() {
	$('#checkGridsBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/missingGridList',
		data: {
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkGridsBtn').prop("disabled", false).removeClass("running");
			showMissingGridDialog(response);
		},
		error: function(xhr, status, error) {
			$('#checkGridsBtn').prop("disabled", false).removeClass("running");

			$('.result').html(error);
		}
	});
}

function showMissingGridDialog(html) {
	// Reset the state of any previous run when the dialog is (re)opened
	inMissingGridLookup = false;
	missingGridLookupFinished = false;
	missingGridStats = { updated: 0, notfound: 0, error: 0 };

	missingGridDialog = BootstrapDialog.show({
		title: 'Callbook lookup',
		size: BootstrapDialog.SIZE_WIDE,
		cssClass: 'options',
		nl2br: false,
		message: html,
		onhide: function(dialogRef) {
			// Closing the dialog also stops a running lookup
			inMissingGridLookup = false;
		},
		buttons: [
		{
			label: 'Cancel',
			cssClass: 'btn-sm btn-secondary',
			id: 'missingGridCancelBtn',
			action: function(dialog) {
				if (inMissingGridLookup) {
					finishMissingGridLookup(true);
				}
			}
		},
		{
			label: 'Start lookup',
			cssClass: 'btn-sm btn-primary',
			id: 'missingGridStartBtn',
			action: function(dialog) {
				startMissingGridLookup();
			}
		},
		{
			label: lang_admin_close,
			cssClass: 'btn-sm btn-secondary',
			id: 'closeButton',
			action: function(dialogItself) {
				dialogItself.close();
			}
		}]
	});

	// Cancel is only meaningful while the lookup is running
	missingGridDialog.getButton('missingGridCancelBtn').disable();

	updateMissingGridCounter();

	$('#checkBoxAllMissingGrids').change(function (event) {
		$('#missingGridTable tbody .row-check').prop('checked', this.checked);
		updateMissingGridCounter();
	});

	$('#missingGridTable').on('change', 'input.row-check', function () {
		updateMissingGridCounter();
	});

	$('#missingGridUnconfirmedOnly').change(function (event) {
		if (this.checked) {
			// Hide the confirmed QSOs and take them out of the lookup queue
			$('#missingGridTable tbody tr[data-confirmed="1"]').each(function () {
				$(this).hide().find('.row-check').prop('checked', false);
			});
		} else {
			$('#missingGridTable tbody tr[data-confirmed="1"]').show();
		}
		updateMissingGridCounter();
	});
}

function updateMissingGridCounter() {
	var count = $('#missingGridTable tbody input.row-check:checked').length;
	var template = (inMissingGridLookup || missingGridLookupFinished) ? 'msgRemaining' : 'msgSelected';
	$('#missingGridCounter').text(missingGridMessage(template).replace('%s', count));

	if (missingGridStats.updated + missingGridStats.notfound + missingGridStats.error > 0) {
		$('#missingGridCounterStats').html(
			'<span class="badge rounded-pill text-bg-success">' + escapeHtml(missingGridMessage('msgStatUpdated').replace('%s', missingGridStats.updated)) + '</span> ' +
			'<span class="badge rounded-pill text-bg-secondary">' + escapeHtml(missingGridMessage('msgStatNotfound').replace('%s', missingGridStats.notfound)) + '</span> ' +
			'<span class="badge rounded-pill text-bg-danger">' + escapeHtml(missingGridMessage('msgStatError').replace('%s', missingGridStats.error)) + '</span>'
		);
	} else {
		$('#missingGridCounterStats').empty();
	}
}

function missingGridMessage(key) {
	return $('#missingGridDialogContent').data(key);
}

function startMissingGridLookup() {
	inMissingGridLookup = true;
	missingGridLookupFinished = false;
	missingGridStats = { updated: 0, notfound: 0, error: 0 };

	missingGridDialog.getButton('missingGridStartBtn').disable();
	missingGridDialog.getButton('missingGridCancelBtn').enable();

	$('#missingGridStatus').text(missingGridMessage('msgRunning'));
	updateMissingGridCounter();

	processNextMissingGridItem();
}

function processNextMissingGridItem() {
	if (!inMissingGridLookup) return;

	var elements = $('#missingGridTable tbody input.row-check:checked');
	var remaining = elements.length;

	if (remaining == 0) {
		finishMissingGridLookup(false);
		return;
	}

	var row = elements.first().closest('tr');
	var id = row.attr('id')?.replace(/\D/g, ''); // Removes non-numeric characters

	updateMissingGridCounter();
	row.find('.lookupResult').html('<i class="fa fa-spinner fa-spin"></i>');

	$.ajax({
		url: base_url + 'index.php/dbtools/lookupMissingGrid',
		type: 'post',
		data: {
			qsoID: id
		},
		dataType: 'json',
		success: function (data) {
			updateMissingGridResultRow(row, data ?? {});
			row.find('.row-check').prop('checked', false);
			setTimeout(processNextMissingGridItem, 50);
		},
		error: function () {
			updateMissingGridResultRow(row, { status: 'error' });
			row.find('.row-check').prop('checked', false);
			setTimeout(processNextMissingGridItem, 50);
		}
	});
}

function updateMissingGridResultRow(row, data) {
	var result = row.find('.lookupResult');
	if (data.status == 'updated') {
		missingGridStats.updated++;
		result.html('<span class="text-success"><i class="fa fa-check"></i> ' + escapeHtml(data.gridsquare) + '</span>');
	} else if (data.status == 'error') {
		missingGridStats.error++;
		result.html('<span class="text-danger">' + escapeHtml(missingGridMessage('msgError')) + '</span>');
	} else if (data.status == 'skipped') {
		missingGridStats.notfound++;
		result.html('<span class="text-muted">' + escapeHtml(missingGridMessage('msgSkipped')) + '</span>');
	} else {
		missingGridStats.notfound++;
		result.html('<span class="text-muted">' + escapeHtml(missingGridMessage('msgNotfound')) + '</span>');
	}

	updateMissingGridCounter();
}

function finishMissingGridLookup(cancelled) {
	inMissingGridLookup = false;
	missingGridLookupFinished = true;

	updateMissingGridCounter();

	var summary = missingGridMessage('msgFinished')
		.replace('%s', missingGridStats.updated)
		.replace('%s', missingGridStats.notfound)
		.replace('%s', missingGridStats.error);

	if (cancelled) {
		summary = missingGridMessage('msgCancelled') + ' ' + summary;
	}

	$('#missingGridStatus').text(summary);

	missingGridDialog.getButton('missingGridCancelBtn').disable();
}

function checkDxcc() {
	$('#checkDxccBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkdxcc',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkDxccBtn').prop("disabled", false).removeClass("running");
			$('.result').html(response);
			initDbtoolsTable('#dxccCheckTable', {
				ordering: true,
				onInit: rebind_checkbox_trigger_dxcc,
			});
		},
		error: function(xhr, status, error) {
			$('#checkDxccBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function checkIncorrectCqZones() {
	$('#checkIncorrectCqZonesBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkincorrectcqzones',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkIncorrectCqZonesBtn').prop("disabled", false).removeClass("running");
			$('.result').html(response);
			initDbtoolsTable('#incorrectcqzonetable', {
				onInit: function () {
					rebind_checkbox_trigger_cq_zone();

					$('#forceMultiZoneUpdateCq').on('change', function() {
						$('#incorrectcqzonetable').DataTable().column(8).search('').draw();
						$('#checkBoxAllCqZones').prop('checked', false);
						$('#incorrectcqzonetable tbody input[type="checkbox"]').prop('checked', false);
						$('#incorrectcqzonetable tbody tr.activeRow').removeClass('activeRow');
					});
				},
			});
		},
		error: function(xhr, status, error) {
			$('#checkIncorrectCqZonesBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function checkIncorrectItuZones() {
	$('#checkIncorrectItuZonesBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkincorrectituzones',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkIncorrectItuZonesBtn').prop("disabled", false).removeClass("running");
			$('.result').html(response);
			initDbtoolsTable('#incorrectituzonetable', {
				onInit: rebind_checkbox_trigger_itu_zone,
			});

			$('#forceMultiZoneUpdate').on('change', function() {
				$('#incorrectituzonetable').DataTable().column(8).search('').draw();
				$('#checkBoxAllItuZones').prop('checked', false);
				$('#incorrectituzonetable tbody input[type="checkbox"]').prop('checked', false);
				$('#incorrectituzonetable tbody tr.activeRow').removeClass('activeRow');
			});

		},
		error: function(xhr, status, error) {
			$('#checkIncorrectItuZonesBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function rebind_checkbox_trigger_dxcc() {
	$('#checkBoxAllDxcc').change(function (event) {
		if (this.checked) {
			$('#dxccCheckTable tbody tr').each(function (i) {
				selectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'dxccCheckTable');
			});
		} else {
			$('#dxccCheckTable tbody tr').each(function (i) {
				unselectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'dxccCheckTable');
			});
		}
	});
}

function selectQsoIdDxcc(qsoID, tablename) {
	var element = $("#" + tablename + " tbody tr#qsoID-" + qsoID);
	element.find(".row-check").prop("checked", true);
	element.addClass('activeRow');
}

function unselectQsoIdDxcc(qsoID, tablename) {
	var element = $("#" + tablename + " tbody tr#qsoID-" + qsoID);
	element.find(".row-check").prop("checked", false);
	element.removeClass('activeRow');
}

function rebind_checkbox_trigger_cq_zone() {
	$('#checkBoxAllCqZones').change(function (event) {
		if (this.checked) {
			$('#incorrectcqzonetable tbody tr').each(function (i) {
				if (!$(this).first().closest('tr').find("td[id='cqZones']").text().includes(',') || $('#forceMultiZoneUpdateCq').prop("checked")) {
					selectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'incorrectcqzonetable');
				}
			});
			if (!$('#forceMultiZoneUpdateCq').prop("checked")) {
				$('#incorrectcqzonetable').DataTable().column(8).search('^[^,]*$', true, false).draw();
			}
		} else {
			$('#incorrectcqzonetable tbody tr').each(function (i) {
				unselectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'incorrectcqzonetable');
			});
			$('#incorrectcqzonetable').DataTable().column(8).search('').draw();
		}
	});
}

function rebind_checkbox_trigger_itu_zone() {
	$('#checkBoxAllItuZones').change(function (event) {
		if (this.checked) {
			$('#incorrectituzonetable tbody tr').each(function (i) {
				if (!$(this).first().closest('tr').find("td[id='ituZones']").text().includes(',') || $('#forceMultiZoneUpdate').prop("checked")) {
					selectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'incorrectituzonetable');
				}
			});
			if (!$('#forceMultiZoneUpdate').prop("checked")) {
				$('#incorrectituzonetable').DataTable().column(8).search('^[^,]*$', true, false).draw();
			}
		} else {
			$('#incorrectituzonetable tbody tr').each(function (i) {
				unselectQsoIdDxcc($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''), 'incorrectituzonetable');
			});
			$('#incorrectituzonetable').DataTable().column(8).search('').draw();
		}
	});
}

function fixDxccSelected() {
	let id_list = [];
	$('#dxccCheckTable tbody input:checked').each(function () {
		let id = $(this).closest('tr').attr('id')?.replace(/\D/g, '');
		id_list.push(id);
	});

	if (id_list.length === 0) {
		BootstrapDialog.alert({
			title: lang_gen_advanced_logbook_info,
			message: lang_gen_advanced_logbook_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}

	let table = $('#dxccCheckTable').DataTable();

	$('#fixSelectedDxccBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/fixDxccSelected',
		type: 'post',
		data: {'ids': JSON.stringify(id_list, null, 2)},
		success: function(data) {
			$('#fixSelectedDxccBtn').prop("disabled", false).removeClass("running");
			id_list.forEach(function(id) {
				let row = $("#dxccCheckTable tbody tr#qsoID-" + id);
				table.row(row).remove();
				$('#checkBoxAllDxcc').prop('checked', false);
			});
			table.draw(false);
			$('.dxcctablediv').html(data.message);
		},
		error: function(xhr, status, error) {
			$('#fixSelectedDxccBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function checkIncorrectGridsquares() {
	$('#checkIncorrectGridsquaresBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkincorrectgridsquares',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkIncorrectGridsquaresBtn').prop("disabled", false).removeClass("running");
			$('.result').html(response);
			initDbtoolsTable('#gridsquareCheckTable');
		},
		error: function(xhr, status, error) {
			$('#checkIncorrectGridsquaresBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function toggleGridsquare(id) {
	const shortSpan = document.getElementById(id + '-short');
	const fullSpan = document.getElementById(id + '-full');
	const link = document.getElementById(id + '-link');

	if (shortSpan.style.display === 'none') {
		shortSpan.style.display = 'inline';
		fullSpan.style.display = 'none';
		link.textContent = lang_gen_advanced_logbook_show_more;
	} else {
		shortSpan.style.display = 'none';
		fullSpan.style.display = 'inline';
		link.textContent = lang_gen_advanced_logbook_show_less;
	}
}

function fixCqZoneSelected() {
	let id_list = [];
	$('#incorrectcqzonetable tbody input:checked').each(function () {
		let id = $(this).closest('tr').attr('id')?.replace(/\D/g, '');
		// Skip entry if DXCC covers multiple CQ zones as the matching one cannot be identified automagically atm or force update
		if (!$(this).closest('tr').find("td[id='cqZones']").text().includes(',') || $('#forceMultiZoneUpdateCq').prop("checked")) {
			id_list.push(id);
		}
	});

	if (id_list.length === 0) {
		BootstrapDialog.alert({
			title: lang_gen_advanced_logbook_info,
			message: lang_gen_advanced_logbook_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}

	let table = $('#incorrectcqzonetable').DataTable();

	$('#fixSelectedCqZoneBtn').prop("disabled", true).addClass("running");

	// The fixCqZones endpoint is shared with the Advanced Logbook bulk actions,
	// so it stayed in the logbookadvanced controller
	$.ajax({
		url: base_url + 'index.php/logbookadvanced/fixCqZones',
		type: 'post',
		data: {'ids': JSON.stringify(id_list, null, 2)},
		success: function(data) {
			$('#fixSelectedCqZoneBtn').prop("disabled", false).removeClass("running");
			id_list.forEach(function(id) {
				let row = $("#incorrectcqzonetable tbody tr#qsoID-" + id);
				table.row(row).remove();
			});
			table.draw(false);
		},
		error: function(xhr, status, error) {
			$('#fixSelectedCqZoneBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function fixItuZoneSelected() {
	let id_list = [];
	$('#incorrectituzonetable tbody input:checked').each(function () {
		let id = $(this).closest('tr').attr('id')?.replace(/\D/g, '');
		// Skip entry if DXCC covers multiple ITU zones as the matching one cannot be identified automagically atm or force update
		if (!$(this).closest('tr').find("td[id='ituZones']").text().includes(',') || $('#forceMultiZoneUpdate').prop("checked")) {
			id_list.push(id);
		}
	});

	if (id_list.length === 0) {
		BootstrapDialog.alert({
			title: lang_gen_advanced_logbook_info,
			message: lang_gen_advanced_logbook_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}

	let table = $('#incorrectituzonetable').DataTable();

	$('#fixSelectedItuZoneBtn').prop("disabled", true).addClass("running");

	// The fixItuZones endpoint is shared with the Advanced Logbook bulk actions,
	// so it stayed in the logbookadvanced controller
	$.ajax({
		url: base_url + 'index.php/logbookadvanced/fixItuZones',
		type: 'post',
		data: {'ids': JSON.stringify(id_list, null, 2)},
		success: function(data) {
			$('#fixSelectedItuZoneBtn').prop("disabled", false).removeClass("running");
			id_list.forEach(function(id) {
				let row = $("#incorrectituzonetable tbody tr#qsoID-" + id);
				table.row(row).remove();
			});
			table.draw(false);
		},
		error: function(xhr, status, error) {
			$('#fixSelectedItuZoneBtn').prop("disabled", false).removeClass("running");
			$('.result').html(error);
		}
	});
}

function checkIota() {
	$('#checkIotaBtn').prop("disabled", true).addClass("running");

	$.ajax({
		url: base_url + 'index.php/dbtools/checkDb',
		data: {
			type: 'checkiota',
			stationid: $('#dbtools_station_id').val()
		},
		type: 'POST',
		success: function(response) {
			$('#checkIotaBtn').prop("disabled", false).removeClass("running");

			$('.result').html(response);
			initDbtoolsTable('#iotaCheckTable', {
				textExtractor: function ($cell) {
					// Get text from the first anchor link which contains the IOTA reference
					var $anchor = $cell.find('a').first();
					var text = $anchor.length ? $anchor.text().trim() : $cell.text().trim();
					// Remove any extra whitespace
					return text.split(/\s+/)[0];
				},
			});
		},
		error: function(xhr, status, error) {
			$('#checkIotaBtn').prop("disabled", false).removeClass("running");

			let errorMsg = 'Error checking iota information';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errorMsg += ': ' + xhr.responseJSON.message;
			}

			BootstrapDialog.alert({
				title: 'Error',
				message: errorMsg,
				type: BootstrapDialog.TYPE_DANGER
			});
		}
	});
}

// Helper function to convert maidenhead grid to lat/lng bounds
function maidenheadToBounds(grid) {
	if (!grid || grid.length < 2) return null;

	grid = grid.toUpperCase();
	const d1 = "ABCDEFGHIJKLMNOPQR";
	const d2 = "ABCDEFGHIJKLMNOPQRSTUVWX";

	let lon = -180;
	let lat = -90;
	let lonWidth = 20;
	let latHeight = 10;

	// First pair (field)
	if (grid.length >= 2) {
		const lonIdx = d1.indexOf(grid[0]);
		const latIdx = d1.indexOf(grid[1]);
		if (lonIdx >= 0 && latIdx >= 0) {
			lon += lonIdx * 20;
			lat += latIdx * 10;
			lonWidth = 20;
			latHeight = 10;
		}
	}

	// Second pair (square)
	if (grid.length >= 4) {
		const lonIdx = parseInt(grid[2]);
		const latIdx = parseInt(grid[3]);
		if (!isNaN(lonIdx) && !isNaN(latIdx)) {
			lon += lonIdx * 2;
			lat += latIdx * 1;
			lonWidth = 2;
			latHeight = 1;
		}
	}

	// Third pair (subsquare)
	if (grid.length >= 6) {
		const lonIdx = d2.indexOf(grid[4]);
		const latIdx = d2.indexOf(grid[5]);
		if (lonIdx >= 0 && latIdx >= 0) {
			lon += lonIdx * (2 / 24);
			lat += latIdx * (1 / 24);
			lonWidth = 2 / 24;
			latHeight = 1 / 24;
		}
	}

	return L.latLngBounds([lat, lon], [lat + latHeight, lon + lonWidth]);
}

function showMapForIncorrectGrid(gridsquare, dxcc, dxccname) {
	$.ajax({
		url: base_url + 'index.php/dbtools/showMapForIncorrectGrid',
		type: 'post',
		data: {
			gridsquare: gridsquare,
			dxcc: dxcc,
			dxccname: dxccname
		},
		success: function (data) {
			// Add metadata to data object
			data.gridsquareDisplay = gridsquare;
			data.dxccnameDisplay = dxccname;

			BootstrapDialog.show({
				title: data.title,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'mapdialog',
				nl2br: false,
				message: '<div class="mapgridcontent"><div id="mapgridcontainer" style="Height: 70vh"></div></div>',
				onshown: function(dialog) {
					drawMap(data);
				},
				buttons: [{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
		}
	});
}

function drawMap(data) {
	let confirmedColor = '#00aa00';
	let workedColor = '#ff0000';
	if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
		confirmedColor = user_map_custom.qsoconfirm.color;
	}
	if (typeof(user_map_custom.qso) !== 'undefined') {
		workedColor = user_map_custom.qso.color;
	}
	let container = L.DomUtil.get('mapgridcontainer');

	if(container != null){
		container._leaflet_id = null;
		container.remove();
		$(".mapgridcontent").html('<div id="mapgridcontainer" style="Height:70vh"></div>');
	}

	// Initialize global arrays for colored maidenhead overlay
	if (typeof grid_two === 'undefined') grid_two = [];
	if (typeof grid_four === 'undefined') grid_four = [];
	if (typeof grid_six === 'undefined') grid_six = [];
	if (typeof grid_two_confirmed === 'undefined') grid_two_confirmed = [];
	if (typeof grid_four_confirmed === 'undefined') grid_four_confirmed = [];
	if (typeof grid_six_confirmed === 'undefined') grid_six_confirmed = [];

	// Clear arrays
	grid_two.length = 0;
	grid_four.length = 0;
	grid_six.length = 0;
	grid_two_confirmed.length = 0;
	grid_four_confirmed.length = 0;
	grid_six_confirmed.length = 0;
	grids = data.grids;

	// Process data.grids - mark in green (confirmed)
	if (data.grids) {
		// data.grids can be a comma-separated string or an array
		let gridsArray = Array.isArray(data.grids) ? data.grids : data.grids.split(',').map(g => g.trim());
		gridsArray.forEach(function(grid) {
			let gridUpper = grid.toUpperCase();
			if (gridUpper.length === 2) {
				grid_two_confirmed.push(gridUpper);
				grid_two.push(gridUpper); // Also add to worked so it shows up
			} else if (gridUpper.length === 4) {
				grid_four_confirmed.push(gridUpper);
				grid_four.push(gridUpper); // Also add to worked so it shows up
			} else if (gridUpper.length === 6) {
				grid_six_confirmed.push(gridUpper);
				grid_six.push(gridUpper); // Also add to worked so it shows up
			}
		});
	}

	// Process data.gridsquare - mark first 4 letters in red (worked)
	if (data.gridsquare) {
		let gridsquareUpper = data.gridsquare.toUpperCase().substring(0, 4);
		if (gridsquareUpper.length >= 2) {
			let twoChar = gridsquareUpper.substring(0, 2);
			if (!grid_two_confirmed.includes(twoChar)) {
				grid_two.push(twoChar);
			}
		}
		if (gridsquareUpper.length >= 4) {
			let fourChar = gridsquareUpper.substring(0, 4);
			if (!grid_four_confirmed.includes(fourChar)) {
				grid_four.push(fourChar);
			}
		}
	}

	// Collect all grids to calculate bounds for auto-zoom
	// Include both data.grids (green) and data.gridsquare (red)
	let allGrids = [];
	if (data.grids) {
		let gridsArray = Array.isArray(data.grids) ? data.grids : data.grids.split(',').map(g => g.trim());
		allGrids = allGrids.concat(gridsArray);
	}
	if (data.gridsquare) {
		allGrids.push(data.gridsquare.substring(0, Math.min(4, data.gridsquare.length)));
	}

	// Calculate bounds and center for auto-zoom
	let bounds = null;
	let centerLat = 0;
	let centerLng = 0;
	let minLat = 90;
	let maxLat = -90;
	let allLngs = [];

	allGrids.forEach(function(grid) {
		let gridBounds = maidenheadToBounds(grid);
		if (gridBounds) {
			// Track center points and extents for better handling
			let gridCenter = gridBounds.getCenter();
			centerLat += gridCenter.lat;
			allLngs.push(gridCenter.lng);

			if (gridBounds.getSouth() < minLat) minLat = gridBounds.getSouth();
			if (gridBounds.getNorth() > maxLat) maxLat = gridBounds.getNorth();

			if (bounds) {
				bounds.extend(gridBounds);
			} else {
				bounds = gridBounds;
			}
		}
	});

	// Calculate average center
	if (allLngs.length > 0) {
		centerLat = centerLat / allGrids.length;

		// Check if longitudes span more than 180° (crossing antimeridian or covering large area)
		let minLng = Math.min(...allLngs);
		let maxLng = Math.max(...allLngs);
		let lngSpan = maxLng - minLng;

		if (lngSpan > 300) {
			// Spans nearly the entire globe (like Asiatic Russia from -180 to 180)
			// Use a predefined sensible center for such cases
			centerLng = 120; // Center of Asiatic Russia/mainland Russia
		} else if (lngSpan > 180) {
			// When spanning >180°, we should go the "other way around" the globe
			// Add 360° to any negative longitudes, then average, then normalize back
			let wrappedLngs = allLngs.map(lng => lng < 0 ? lng + 360 : lng);
			let avgWrapped = wrappedLngs.reduce((a, b) => a + b, 0) / wrappedLngs.length;

			// Normalize to -180 to 180 range
			if (avgWrapped > 180) avgWrapped -= 360;
			centerLng = avgWrapped;
		} else {
			// Normal case - simple average
			centerLng = allLngs.reduce((a, b) => a + b, 0) / allLngs.length;
		}
	}

	// Make map global for L.MaidenheadColouredGridMap.js
	window.map = new L.Map('mapgridcontainer', {
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
	});

	let maidenhead = L.maidenhead().addTo(window.map);

	let osmUrl = option_map_tile_server;
	let osmAttrib= option_map_tile_server_copyright;
	let osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});

	let redIcon = L.icon({
					iconUrl: icon_dot_url,
					iconSize:     [10, 10], // size of the icon
				});

	window.map.addLayer(osm);

	// Add legend
	let legend = L.control({position: 'topright'});
	legend.onAdd = function (map) {
		let div = L.DomUtil.create('div', 'info legend');
		div.style.backgroundColor = 'white';
		div.style.padding = '10px';
		div.style.borderRadius = '5px';
		div.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';

		let rows = [
			{ color: confirmedColor, text: lang_gen_advanced_logbook_confirmedLabel + ' ' + data.dxccnameDisplay },
			{ color: workedColor, text: lang_gen_advanced_logbook_workedLabel + ' ' + data.gridsquareDisplay },
		];
		for (let i = 0; i < rows.length; i++) {
			let row = document.createElement('div');
			row.style.display = 'flex';
			row.style.alignItems = 'center';
			if (i === 0) row.style.marginBottom = '8px';

			let swatch = document.createElement('div');
			swatch.style.width = '20px';
			swatch.style.height = '20px';
			swatch.style.backgroundColor = rows[i].color;
			swatch.style.border = '1px solid #ccc';
			swatch.style.marginRight = '8px';
			row.appendChild(swatch);

			let label = document.createElement('span');
			label.style.fontSize = '12px';
			label.textContent = rows[i].text;
			row.appendChild(label);

			div.appendChild(row);
		}
		return div;
	};
	legend.addTo(window.map);

	// Zoom to fit all grids with padding
	if (bounds) {
		const latSpan = maxLat - minLat;
		const lngSpan = Math.max(...allLngs) - Math.min(...allLngs);

		// For extremely large spans (near 360° like Asiatic Russia), use manual center
		// For moderate spans (100-200° like Japan+GM05), use fitBounds with lower maxZoom
		// For smaller spans, use fitBounds normally

		if (lngSpan > 300) {
			// Spans nearly the entire globe - use calculated center with fixed zoom
			let zoom = 3; // Increased from 2 to 3 for better detail
			window.map.setView([centerLat, centerLng], zoom);
		} else if (lngSpan > 100) {
			// Large span (like Japan to western hemisphere) - use fitBounds but limit zoom
			window.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 3 });
		} else {
			// Normal case - use fitBounds
			let maxZoom = 10;
			if (lngSpan < 50) maxZoom = 7;
			if (lngSpan < 20) maxZoom = 10;
			window.map.fitBounds(bounds, { padding: [50, 50], maxZoom: maxZoom });
		}
	} else {
		window.map.setView([30, 0], 1.5);
	}
}
