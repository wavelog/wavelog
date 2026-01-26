let callBookProcessingDialog = null;
let inCallbookProcessing = false;
let inCallbookItemProcessing = false;
let stateFixingDialog = null;
let inStateFixing = false;
let stateFixStats = {fixed: 0, skipped: 0, fixedDxcc: new Set(), skippedDxcc: new Set(), skipReasons: new Set(), skippedDetails: []};
let lastChecked = null;
let silentReset = false;

document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll('.dropdown').forEach(dd => {
    dd.addEventListener('hide.bs.dropdown', function (e) {
      if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
        e.preventDefault();
      }
    });

    dd.querySelectorAll('.dropdown-action').forEach(btn => {
      btn.addEventListener('click', function() {
        const dropdown = bootstrap.Dropdown.getInstance(dd.querySelector('[data-bs-toggle="dropdown"]'));
        if (dropdown) dropdown.hide();
      });
    });
  });
});


$('#band').change(function () {
	var band = $("#band option:selected").text();
	if (band != "SAT") {
		$(".sats_dropdown").attr("hidden", true);
		$(".orbits_dropdown").attr("hidden", true);
	} else {
		$(".sats_dropdown").removeAttr("hidden");
		$(".orbits_dropdown").removeAttr("hidden");
	}
});

$('#selectPropagation').change(function () {
	var prop_mode = $("#selectPropagation option:selected").text();
	if (prop_mode != "Satellite") {
		$(".sats_dropdown").attr("hidden", true);
		$(".orbits_dropdown").attr("hidden", true);
	} else {
		$(".sats_dropdown").removeAttr("hidden");
		$(".orbits_dropdown").removeAttr("hidden");
	}
});

function getSelectedIds() {
	let id_list = [];
	$('#qsoList tbody input:checked').each(function () {
		let id = $(this).closest('tr').attr('id')?.replace(/\D/g, '');
		id_list.push(id);
	});
	return id_list;
}

function updateRow(qso) {
	let row = $('#qsoID-' + qso.qsoID);
	let cells = row.find('td');
	let c = 1;
	if ((user_options.datetime.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.qsoDateTime);
	}
	if ((user_options.last_modification.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.last_modified);
	}
	if ((user_options.de.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.de);
	}
	if ((user_options.dx.show ?? 'true') == "true"){
		cells.eq(c++).html('<span class="qso_call"><a id="edit_qso" href="javascript:displayQso('+qso.qsoID+')"><span id="dx">'+qso.dx.replaceAll('0', 'Ø')+'</span></a><span class="qso_icons">' + (qso.callsign == '' ? '' : ' <a href="https://lotw.arrl.org/lotwuser/act?act='+qso.callsign+'" target="_blank"><small id="lotw_info" class="badge bg-success'+qso.lotw_hint+'" data-bs-toggle="tooltip" title="LoTW User. Last upload was ' + qso.lastupload + '">L</small></a>') + ' <a target="_blank" href="https://www.qrz.com/db/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/qrz.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/hamqth.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on HamQTH"></a> <a target="_blank" href="https://clublog.org/logsearch.php?log='+qso.dx+'&call='+qso.de+'"><img width="16" height="16" src="'+base_url+'images/icons/clublog.png" alt="Clublog Log Search"></a></span></span>');
	}
	if ((user_options.mode.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.mode);
	}
	if ((user_options.rsts.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.rstS);
	}
	if ((user_options.rstr.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.rstR);
	}
	if ((user_options.band.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.band);
	}
	if ((user_options.frequency.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.frequency);
	}
	if ( (user_options.gridsquare) && ((user_options.gridsquare.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.gridsquare);
	}
	if ((user_options.name.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.name);
	}
	if ((user_options.qth.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.qth);
	}
	if ((user_options.qslvia.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.qslVia);
	}
	if ((user_options.clublog.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.clublog);
	}
	if ((user_options.qsl.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.qsl);
	}
	if ($(".eqslconfirmation")[0] && ((user_options.eqsl.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.eqsl);
	}
	if ($(".lotwconfirmation")[0] && ((user_options.lotw.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.lotw);
	}
	if ((user_options.qrz.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.qrz);
	}
	if ((user_options.dcl.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.dcl);
	}
	if ((user_options.qslmsgs.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.qslMessage);
	}
	if ((user_options.qslmsgr.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.qslMessageR);
	}
	if ((user_options.dxcc.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.dxcc+qso.flag);
	}
	if ((user_options.state.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.state);
	}
	if ((user_options.county.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.county);
	}
	if ((user_options.cqzone.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.cqzone);
	}
	if ((user_options.ituzone.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.ituzone);
	}
	if ((user_options.iota.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.iota);
	}
	if ((user_options.pota.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.pota);
	}
	if ((user_options.sota) && ((user_options.sota.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.sota);
	}
	if ((user_options.dok) && ((user_options.dok.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.dok);
	}
	if ((user_options.wwff) && ((user_options.wwff.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.wwff);
	}
	if ((user_options.sig) && ((user_options.sig.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.sig);
	}
	if ((user_options.region) && ((user_options.region.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.region);
	}
	if ((user_options.operator) && ((user_options.operator.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.operator);
	}
	if ((user_options.comment) && ((user_options.comment.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.comment);
	}
	if ((user_options.propagation) && ((user_options.propagation.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.propagation);
	}
	if ((user_options.contest) && ((user_options.contest.show ?? 'true') == "true")){
		cells.eq(c++).html(qso.contest);
	}
	if ((user_options.myrefs.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.deRefs);
	}
	if ((user_options.continent.show ?? 'true') == "true"){
		cells.eq(c++).html(qso.continent);
	}
	if ((user_options.distance.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.distance);
	}
	if ((user_options.antennaazimuth.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.antennaazimuth);
	}
	if ((user_options.antennaelevation.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.antennaelevation);
	}
	if ((user_options.profilename.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.profilename);
	}
	if ((user_options.stationpower.show ?? 'true') == "true"){
		cells.eq(c++).text(qso.stationpower);
	}

	$('[data-bs-toggle="tooltip"]').tooltip();
	return row;
}

function loadQSOTable(rows) {
	const $table = $('#qsoList');

	// Prevent initializing if already a DataTable
	if ($.fn.DataTable.isDataTable($table)) {
		$table.DataTable().clear().destroy();
	}

	const langUrl = getDataTablesLanguageUrl();

	const initTable = function(language) {
		$.fn.dataTable.moment(custom_date_format + ' HH:mm');

		const table = $table.DataTable({
			searching: false,
			responsive: false,
			ordering: true,
			scrollY: window.innerHeight - $('#searchForm').innerHeight() - 250,
			scrollCollapse: true,
			paging: false,
			language: language,
			createdRow: function (row, data, dataIndex) {
				$(row).attr('id', data.id);
			},
			columnDefs: [
				{ orderable: false, targets: 0 },
				{ targets: $(".distance-column-sort").index(), type: "numbersort" },
				{ targets: $(".antennaazimuth-column-sort").index(), type: "numbersort" },
				{ targets: $(".antennaelevation-column-sort").index(), type: "numbersort" },
				{ targets: $(".stationpower-column-sort").index(), type: "numbersort" },
			],
			dom: 'Bfrtip',
			buttons: [
						{
							extend: 'csv',
							className: 'mb-1 btn btn-sm btn-primary', // Bootstrap classes
								init: function(api, node, config) {
									$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
								},
								exportOptions: {
								columns: ':visible:not(:eq(0))', // export all visible except column 4
								format: {
									body: function (data, row, column, node) {
										// strip HTML tags first (like DataTables does by default)
										if (typeof data === 'string' && data.includes('<br />')) {
												data = data.replace(/<br \/>/g, '');
										}
										if (typeof data === 'string') {
											data = data.replace(/<[^>]*>/g, '');
										}
										// then replace Ø with 0 in specific columns
										if (column === 1 || column === 2 || column === 3) {
											// remove a trailing "L" and trim whitespaces
											data = data.replace(/\s*L\s*$/, '').trim();
											if (typeof data === 'string' && data.includes('Ø')) {
												data = data.replace(/Ø/g, '0');
											}
										}
										if (typeof data === 'string' && data.includes('&#9650')) {
												data = data.replace(/&#9650;/g, '');
										}
										if (typeof data === 'string' && data.includes('&#9660')) {
												data = data.replace(/&#9660;/g, '');
										}

										data = data.replace(/ data-bs-toggle="tooltip" data-bs-html="true" class="[^"]*">/g, '');
										return data;
									}
								}
							}
						}
                    ]
		});

	for (i = 0; i < rows.length; i++) {
		let qso = rows[i];

		var data = [];
		data.push('<div class="form-check"><input class="row-check form-check-input" type="checkbox" /></div>');
		if ((user_options.datetime.show ?? 'true') == "true"){
			if (qso.datetime === '') {
				data.push('<span class="bg-danger">Missing date</span>');
			} else {
				data.push(qso.qsoDateTime);
			}
		}
		if ((user_options.last_modification.show ?? 'true') == "true"){
			data.push(qso.last_modified);
		}
		if ((user_options.de.show ?? 'true') == "true"){
			data.push(qso.de.replaceAll('0', 'Ø'));
		}
		if ((user_options.dx.show ?? 'true') == "true"){
			if (qso.dx === '') {
				data.push('<span class="bg-danger">Missing callsign</span>');
			} else {
				data.push('<span class="qso_call"><a id="edit_qso" href="javascript:displayQso('+qso.qsoID+')"><span id="dx">'+qso.dx.replaceAll('0', 'Ø')+'</span></a><span class="qso_icons">' + (qso.callsign == '' ? '' : ' <a href="https://lotw.arrl.org/lotwuser/act?act='+qso.callsign+'" target="_blank"><small id="lotw_info" class="badge bg-success'+qso.lotw_hint+'" data-bs-toggle="tooltip" title="LoTW User. Last upload was ' + qso.lastupload + ' ">L</small></a>') + ' <a target="_blank" href="https://www.qrz.com/db/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/qrz.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/hamqth.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on HamQTH"></a> <a target="_blank" href="https://clublog.org/logsearch.php?log='+qso.dx+'&call='+qso.de+'"><img width="16" height="16" src="'+base_url+'images/icons/clublog.png" alt="Clublog Log Search"></a></span></span>');
			}
		}
		if ((user_options.mode.show ?? 'true') == "true"){
			if (qso.mode === '') {
				data.push('<span class="bg-danger">Missing mode</span>');
			} else {
				data.push(qso.mode);
			}
		}
		if ((user_options.rsts.show ?? 'true') == "true"){
			data.push(qso.rstS);
		}
		if ((user_options.rstr.show ?? 'true') == "true"){
			data.push(qso.rstR);
		}
		if ((user_options.band.show ?? 'true') == "true"){
			if (qso.band === '') {
				data.push('<span class="bg-danger">Missing band</span>');
			} else {
				data.push(qso.band);
			}
		}
		if ((user_options.frequency.show ?? 'true') == "true"){
			data.push(qso.frequency);
		}
		if ((user_options.gridsquare.show ?? 'true') == "true"){
			data.push(qso.gridsquare);
		}
		if ((user_options.name.show ?? 'true') == "true"){
			data.push(qso.name);
		}
		if ((user_options.qth.show ?? 'true') == "true"){
			data.push(qso.qth);
		}
		if ((user_options.qslvia.show ?? 'true') == "true"){
			data.push(qso.qslVia);
		}
		if ((user_options.clublog.show ?? 'true') == "true"){
			data.push(qso.clublog);
		}
		if ((user_options.qsl.show ?? 'true') == "true"){
			data.push(qso.qsl);
		}
		if ($(".eqslconfirmation")[0] && (user_options.eqsl.show ?? 'true') == "true"){
			data.push(qso.eqsl);
		}
		if ($(".lotwconfirmation")[0] && (user_options.lotw.show ?? 'true') == "true"){
			data.push(qso.lotw);
		}
		if ((user_options.qrz.show ?? 'true') == "true"){
			data.push(qso.qrz);
		}
		if ((user_options.dcl.show ?? 'true') == "true"){
			data.push(qso.dcl);
		}
		if ((user_options.qslmsgs.show ?? 'true') == "true"){
			data.push(qso.qslMessage);
		}
		if ((user_options.qslmsgr.show ?? 'true') == "true"){
			data.push(qso.qslMessageR);
		}
		if ((user_options.dxcc.show ?? 'true') == "true"){
			data.push(qso.dxcc+qso.flag+(qso.end == null ? '' : ' <span class="badge bg-danger">Deleted DXCC</span>'));
		}
		if ((user_options.state.show ?? 'true') == "true"){
			data.push(qso.state);
		}
		if ((user_options.county.show ?? 'true') == "true"){
			data.push(qso.county);
		}
		if ((user_options.cqzone.show ?? 'true') == "true"){
			data.push(qso.cqzone);
		}
		if ((user_options.ituzone.show ?? 'true') == "true"){
			data.push(qso.ituzone);
		}
		if ((user_options.iota.show ?? 'true') == "true"){
			data.push(qso.iota);
		}
		if ((user_options.pota.show ?? 'true') == "true"){
			data.push(qso.pota);
		}
		if ((user_options.sota.show ?? 'true') == "true"){
			data.push(qso.sota);
		}
		if ((user_options.dok.show ?? 'true') == "true"){
			data.push(qso.dok);
		}
		if ((user_options.wwff.show ?? 'true') == "true"){
			data.push(qso.wwff);
		}
		if ((user_options.sig.show ?? 'true') == "true"){
			data.push(qso.sig);
		}
		if ((user_options.region.show ?? 'true') == "true"){
			data.push(qso.region);
		}
		if ((user_options.operator.show ?? 'true') == "true"){
			data.push(qso.operator);
		}
		if ((user_options.comment.show ?? 'true') == "true"){
			data.push(qso.comment);
		}
		if ((user_options.propagation.show ?? 'true') == "true"){
			data.push(qso.propagation);
		}
		if ((user_options.contest.show ?? 'true') == "true"){
			data.push(qso.contest);
		}
		if ((user_options.myrefs.show ?? 'true') == "true"){
			data.push(qso.deRefs);
		}
		if ((user_options.continent.show ?? 'true') == "true"){
			data.push(qso.continent);
		}
		if ((user_options.distance.show ?? 'true') == "true"){
			data.push(qso.distance);
		}
		if ((user_options.antennaazimuth.show ?? 'true') == "true"){
			data.push(qso.antennaazimuth);
		}
		if ((user_options.antennaelevation.show ?? 'true') == "true"){
			data.push(qso.antennaelevation);
		}
		if ((user_options.profilename.show ?? 'true') == "true"){
			data.push(qso.profilename);
		}
		if ((user_options.stationpower.show ?? 'true') == "true"){
			data.push(qso.stationpower);
		}
		data.id='qsoID-' + qso.qsoID;
		let createdRow = table.row.add(data).index();
		table.rows(createdRow).nodes().to$().data('qsoID', qso.qsoID);
	//	table.row(createdRow).node().to$().attr("id", 'qsoID-' + qso.qsoID);
	}
	try {
		table.columns.adjust().draw(true);
	} catch (e) {
		table.draw(true);
	}
	rebind_checkbox_trigger();
	$('[data-bs-toggle="tooltip"]').tooltip();

		document.querySelectorAll('.row-check').forEach(checkbox => {
			checkbox.addEventListener('click', function (e) {
				const checkboxes = Array.from(document.querySelectorAll('.row-check'));
				if (e.shiftKey && lastChecked) {
					let start = checkboxes.indexOf(this);
					let end = checkboxes.indexOf(lastChecked);
					[start, end] = [Math.min(start, end), Math.max(start, end)];

					for (let i = start; i <= end; i++) {
						checkboxes[i].checked = lastChecked.checked;
						$(checkboxes[i]).closest('tr').toggleClass('activeRow', lastChecked.checked);
					}
				}
				lastChecked = this;
			});
		});
	};

	if (langUrl) {
		// Load language file first
		$.getJSON(langUrl)
			.done(function(language) {
				initTable(language);
			})
			.fail(function() {
				console.error("Failed to load DataTables language file at " + langUrl);
				initTable({});  // fallback to default English
			});
	} else {
		// No language file needed (English)
		initTable({});
	}
}

$.fn.dataTable.ext.type.order['numbersort-pre'] = function(data) {
    var num = parseFloat(data);
    return isNaN(num) ? 0 : num;
};

function processNextCallbookItem(gridsquareAccuracyCheck) {
	if (!inCallbookProcessing) return;

	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		inCallbookProcessing = false;
		callBookProcessingDialog.close();
		let table = $('#qsoList').DataTable();
		table.draw(false);
		return;
	}

	let id = elements.first().closest('tr').attr('id')?.replace(/\D/g, ''); // Removes non-numeric characters

	callBookProcessingDialog.setMessage("Retrieving callbook data : " + nElements + " remaining");

	$.ajax({
		url: site_url + '/logbookadvanced/updateFromCallbook',
		type: 'post',
		data: {
			qsoID: id,
			gridsquareAccuracyCheck: gridsquareAccuracyCheck ? 1 : 0
		},
		dataType: 'json',
		success: function (data) {
			if (data && data.dx) {
				updateRow(data);
			}
			unselectQsoID(id);
			setTimeout(function() {
				processNextCallbookItem(gridsquareAccuracyCheck);
			}, 50);
		},
		error: function (data) {
			unselectQsoID(id);
			setTimeout(function() {
				processNextCallbookItem(gridsquareAccuracyCheck);
			}, 50);
		},
	});
}

function processNextStateFixItem() {
	if (!inStateFixing) return;

	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;

	if (nElements == 0) {
		inStateFixing = false;
		stateFixingDialog.close();

		// Show summary
		let message = '';
		message += lang_gen_advanced_logbook_fixed_with_count.replace('%s', stateFixStats.fixed);
		message += '<br>';
		message += lang_gen_advanced_logbook_skipped_with_count.replace('%s', stateFixStats.skipped);
		if (stateFixStats.skippedDetails.length > 0) {
			message += '<div class="border rounded p-2 mt-2" style="max-height: 150px; overflow-y: auto; background-color: var(--bs-body-bg); color: var(--bs-body-color);">';
			message += '<small>';
			stateFixStats.skippedDetails.forEach(function(detail, index) {
				if (index > 0) message += '<br>';
				message += detail;
			});
			message += '</small>';
			message += '</div>';
		}

		if (stateFixStats.skipped > 0) {
			message += '<div class="alert alert-info mt-3">';
			message += '<small>' + lang_gen_advanced_logbook_state_not_supported.replace('%s', lang_gen_advanced_logbook_github_link);
			message += '</small>';
			message += '</div>';
		}

		BootstrapDialog.alert({
			title: lang_gen_advanced_logbook_state_fix_complete,
			message: function(dialog) {
				return message;
			},
			type: stateFixStats.fixed > 0 ? BootstrapDialog.TYPE_SUCCESS : BootstrapDialog.TYPE_INFO,
			nl2br: false
		});

		let table = $('#qsoList').DataTable();
		table.draw(false);
		return;
	}

	let id = elements.first().closest('tr').attr('id')?.replace(/\D/g, '');

	stateFixingDialog.setMessage(lang_gen_advanced_logbook_fixing_state_remaining.replace('%s', nElements));

	$.ajax({
		url: site_url + '/logbookadvanced/fixStateProgress',
		type: 'post',
		data: {
			qsoID: id
		},
		dataType: 'json',
		success: function (data) {
			if (data.success && data.qso) {
				updateRow(data.qso);
				stateFixStats.fixed++;
				if (data.dxcc_name) {
					stateFixStats.fixedDxcc.add(data.dxcc_name);
				}
			} else if (data.skipped) {
				stateFixStats.skipped++;
				if (data.dxcc_name) {
					stateFixStats.skippedDxcc.add(data.dxcc_name);
				}
				if (data.reason) {
					stateFixStats.skipReasons.add(data.reason);
					// Build detailed skip entry: CALLSIGN - DXCC - reason
					let skipDetail = (data.callsign || 'Unknown') + ' - ' + (data.dxcc_name || 'Unknown DXCC') + ' - ' + data.reason;
					stateFixStats.skippedDetails.push(skipDetail);
				}
			}
			unselectQsoID(id);
			setTimeout("processNextStateFixItem()", 50);
		},
		error: function (data) {
			stateFixStats.skipped++;
			unselectQsoID(id);
			setTimeout("processNextStateFixItem()", 50);
		},
	});
}

function selectQsoID(qsoID) {
	var element = $("#qsoID-" + qsoID);
	element.find("input[type=checkbox]").prop("checked", true);
	element.addClass('activeRow');
}

function unselectQsoID(qsoID) {
	var element = $("#qsoID-" + qsoID);
	element.find("input[type=checkbox]").prop("checked", false);
	element.removeClass('activeRow');
	$('#checkBoxAll').prop("checked", false);
}

$(document).ready(function () {
	// initialize multiselect dropdown for locations
	// Documentation: https://davidstutz.github.io/bootstrap-multiselect/index.html

	$('#de').multiselect({
		// template is needed for bs5 support
		enableFiltering: true,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		templates: {
		  button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		numberDisplayed: 1,
		inheritClass: true,
		includeSelectAllOption: true
	});

	$('#dxcc').multiselect({
		// template is needed for bs5 support
		templates: {
		  button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		enableFiltering: true,
		enableFullValueFiltering: false,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		numberDisplayed: 1,
		inheritClass: true,
		buttonWidth: '100%',
		maxHeight: 600
	});
	$('.multiselect-container .multiselect-filter', $('#dxcc').parent()).css({
		'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color':'inherit', 'width':'100%', 'height':'37px'
	})

	/*Pull from localStorage to set form input value*/
	if (localStorage.hasOwnProperty(`user_${user_id}_qsoresults`)) {
		document.getElementById('qsoResults').value = localStorage.getItem(`user_${user_id}_qsoresults`);
	}

	if (localStorage.hasOwnProperty(`user_${user_id}_selectedlocations`)) {
		const selectedLocations = localStorage.getItem(`user_${user_id}_selectedlocations`);
		const locationsArray = selectedLocations ? selectedLocations.split(',') : [];
		// First, deselect all options
		$('#de').multiselect('deselectAll', false);

		// Then, select the stored locations
		$('#de').multiselect('select', locationsArray);
	}

	$('#searchForm').submit(function (e) {
		let container = L.DomUtil.get('advancedmap');
		let selectedlocations = $('#de').val();
		let qsoids = '';
		if (Array.isArray(selectedlocations) && selectedlocations.length === 0) {
			BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_info,
					message: lang_gen_advanced_logbook_select_at_least_one_location,
					type: BootstrapDialog.TYPE_INFO,
					closable: false,
					draggable: false,
					callback: function (result) {
					}
				});
			return false;
		}

		if(container != null){
			container._leaflet_id = null;
			container.remove();
			$(".coordinates").remove();
		}

		$("#qsoList").attr("Hidden", false);
		$("#qsoList_wrapper").attr("Hidden", false);
		$("#qsoList_info").attr("Hidden", false);

		localStorage.setItem(`user_${user_id}_qsoresults`, this.qsoresults.value);
		localStorage.setItem(`user_${user_id}_selectedlocations`, $('#de').val());
		$('#searchButton').prop("disabled", true).addClass("running");

		let qsoresults = this.qsoresults.value;

		if (localStorage.hasOwnProperty(`user_${user_id}_qsoids`)) {
			qsoids = localStorage.getItem(`user_${user_id}_qsoids`);

			qsoresults = qsoids
				.split(',')
				.filter(i => i.trim() !== '').length;
			localStorage.removeItem(`user_${user_id}_qsoids`);
		}
		$.ajax({
			url: this.action,
			type: 'post',
			data: {
				dateFrom: this.dateFrom.value,
				dateTo: this.dateTo.value,
				de: selectedlocations,
				dx: this.dx.value,
				mode: this.mode.value,
				band: this.band.value,
				qslSent: this.qslSent.value,
				qslReceived: this.qslReceived.value,
				qslSentMethod: this.qslSentMethod.value,
				qslReceivedMethod: this.qslReceivedMethod.value,
				iota: this.iota.value,
				operator: this.operator.value,
				dxcc: this.dxcc.value,
				propmode: this.propmode.value,
				gridsquare: this.gridsquare.value,
				state: this.state.value,
				county: this.county.value,
				qsoresults: qsoresults,
				sats: this.sats.value,
				orbits: this.orbits.value,
				cqzone: this.cqzone.value,
				ituzone: this.ituzone.value,
				lotwSent: this.lotwSent.value,
				lotwReceived: this.lotwReceived.value,
				clublogSent: this.clublogSent.value,
				clublogReceived: this.clublogReceived.value,
				eqslSent: this.eqslSent.value,
				eqslReceived: this.eqslReceived.value,
				dclSent: this.dclSent.value,
				dclReceived: this.dclReceived.value,
				qslvia: $('[name="qslvia"]').val(),
				sota: this.sota.value,
				pota: this.pota.value,
				wwff: this.wwff.value,
				qslimages: this.qslimages.value,
				dupes: this.dupes.value,
				dupedate: this.dupedate.value,
				dupemode: this.dupemode.value,
				dupeband: this.dupeband.value,
				dupesat: this.dupesat.value,
				contest: this.contest.value,
				invalid: this.invalid.value,
				continent: this.continent.value,
				comment: this.comment.value,
				qsoids: qsoids,
				dok: this.dok.value,
				qrzSent: this.qrzSent.value,
				qrzReceived: this.qrzReceived.value,
				distance: this.distance.value,
				sortcolumn: this.sortcolumn.value,
				sortdirection: this.sortdirection.value
			},
			dataType: 'json',
			success: function (data) {
				$('#searchButton').prop("disabled", false).removeClass("running");
				loadQSOTable(data);
				if (qsoids !== '') {
					$('#checkBoxAll').prop("checked", true);
					$('#checkBoxAll').trigger('change');
				}
				$('#checkBoxAll').prop("checked", false);
			},
			error: function (data) {
				$('#searchButton').prop("disabled", false).removeClass("running");
				BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_error,
					message: lang_gen_advanced_logbook_an_error_ocurred_while_making_request,
					type: BootstrapDialog.TYPE_DANGER,
					closable: false,
					draggable: false,
					callback: function (result) {
					}
				});
			},
		});
		$("#dupes").val("");
		$("#invalid").val("");
		return false;
	});

	$('#qsoList').on('click', 'input[type="checkbox"]', function() {
		if ($(this).is(":checked")) {
			$(this).closest('tr').addClass('activeRow');
		} else {
			$(this).closest('tr').removeClass('activeRow');
		}
	});

	$('#btnUpdateFromCallbook').click(function (event) {
		let elements = $('#qsoList tbody input:checked');
		let nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_at_least_one_row_callbook,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/callbookDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: 'Callbook options',
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_admin_close,
						cssClass: 'btn-sm btn-secondary',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					},
					{
						label: 'Update',
						cssClass: 'btn-sm btn-primary',
						id: 'updateButton',
						action: function (dialogItself) {
							startProcessingCallbook(nElements, $('[name="gridsquareaccuracycheck"]').is(":checked"));
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						return;
					},
				});
			}
		});


	});

	function startProcessingCallbook(nElements, gridsquareAccuracyCheck) {
		inCallbookProcessing = true;

		callBookProcessingDialog = BootstrapDialog.show({
			title: "Retrieving callbook data for " + nElements + " QSOs",
			message: "Retrieving callbook data for " + nElements + " QSOs",
			type: BootstrapDialog.TYPE_DANGER,
			closable: false,
			draggable: false,
			buttons: [{
				label: 'Cancel',
				action: function(dialog) {
					inCallbookProcessing = false;
					dialog.close();
				}
			}]
		});
		processNextCallbookItem(gridsquareAccuracyCheck);
	}

	$('#helpButton').click(function (event) {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/helpDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_help,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_admin_close,
						cssClass: 'btn-sm btn-secondary',
						id: 'closeButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						$('#optionButton').prop("disabled", false);
					},
				});
			}
		});

	});

	$('#deleteQsos').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_at_least_one_row_delete,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		$('#deleteQsos').prop("disabled", true);

		var table = $('#qsoList').DataTable();

		BootstrapDialog.confirm({
			title: lang_general_word_danger,
			message: lang_filter_actions_delete_warning+'<br/>'+id_list.length+lang_filter_actions_delete_warning_details,
			type: BootstrapDialog.TYPE_DANGER,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-danger',
			callback: function(result) {
				if(result) {
					$.ajax({
						url: base_url + 'index.php/logbookadvanced/batchDeleteQsos',
						type: 'post',
						data: {
							'ids': JSON.stringify(id_list, null, 2)
						},
						success: function(data) {
							id_list.forEach(function(id) {
								let row = $("#qsoID-" + id);
								table.row(row).remove();
							});
							$('#deleteQsos').prop("disabled", false);
							table.draw(false);
							$('#checkBoxAll').prop("checked", false);
						}
					})
				}
			},
			onhide: function(dialogRef){
				$('#deleteQsos').prop("disabled", false);
			},
		});
	});

	$('#exportAdif').click(function (event) {
		$('#exportAdif').prop("disabled", true);
		const id_list = getSelectedIdsForMap();

		xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				var a;
				if (xhttp.readyState === 4 && xhttp.status === 200) {
					// Trick for making downloadable link
					a = document.createElement('a');
					a.href = window.URL.createObjectURL(xhttp.response);
					// Give filename you wish to download
					a.download = "logbook_export.adi";
					a.style.display = 'none';
					document.body.appendChild(a);
					a.click();
				}
			};

		if (id_list.length > 0) {
			// Post data to URL which handles post request
			xhttp.open("POST", site_url+'/logbookadvanced/export_to_adif', true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			// You should set responseType as blob for binary responses
			xhttp.responseType = 'blob';
			xhttp.send("id=" + JSON.stringify(id_list, null, 2)+"&sortcolumn=" +$('#sortcolumn').val()+"&sortdirection=" +$('#sortdirection').val());
		} else {

			// Post data to URL which handles post request
			xhttp.open("POST", site_url+'/logbookadvanced/export_to_adif_params', true);
			xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			// You should set responseType as blob for binary responses

			xhttp.responseType = 'blob';
			xhttp.send($('#searchForm').serialize()+"&de=" +$("#de").val());
		}
		$('#exportAdif').prop("disabled", false);
	});

	$('#queueBureau').click(function (event) {
		handleQsl('Q','B', 'queueBureau');
	});

	$('#queueDirect').click(function (event) {
		handleQsl('Q','D', 'queueDirect');
	});

    $('#queueElectronic').click(function (event) {
		handleQsl('Q','E', 'queueElectronic');
	});

	$('#sentBureau').click(function (event) {
		handleQsl('Y','B', 'sentBureau');
	});

	$('#sentDirect').click(function (event) {
		handleQsl('Y','D', 'sentDirect');
	});

    $('#sentElectronic').click(function (event) {
		handleQsl('Y','E', 'sentElectronic');
	});

	$('#dontSend').click(function (event) {
		handleQsl('N','', 'dontSend');
	});
	$('#notRequired').click(function (event) {
		handleQsl('I','', 'notRequired');
	});
	$('#notReceived').click(function (event) {
		handleQslReceived('N','', 'notReceived');
	});
	$('#receivedBureau').click(function (event) {
		handleQslReceived('Y','B', 'receivedBureau');
	});

	$('#receivedDirect').click(function (event) {
		handleQslReceived('Y','D', 'receivedDirect');
	});

	$('#receivedElectronic').click(function (event) {
		handleQslReceived('Y','E', 'receivedElectronic');
	});

	$('#searchGridsquare').click(function (event) {
		quickSearch('gridsquare');
	});

	$('#searchState').click(function (event) {
		quickSearch('state');
	});

	$('#searchIota').click(function (event) {
		quickSearch('iota');
	});

	$('#searchDxcc').click(function (event) {
		quickSearch('dxcc');
	});

	$('#searchCallsign').click(function (event) {
		quickSearch('dx');
	});

	$('#searchDate').click(function (event) {
		quickSearch('date');
	});

	$('#searchCqZone').click(function (event) {
		quickSearch('cqzone');
	});

	$('#searchItuZone').click(function (event) {
		quickSearch('ituzone');
	});

	$('#searchMode').click(function (event) {
		quickSearch('mode');
	});

	$('#searchBand').click(function (event) {
		quickSearch('band');
	});

	$('#searchSota').click(function (event) {
		quickSearch('sota');
	});

	$('#searchWwff').click(function (event) {
		quickSearch('wwff');
	});

	$('#searchPota').click(function (event) {
		quickSearch('pota');
	});

	$('#searchOperator').click(function (event) {
		quickSearch('operator');
	});

	$('#dupeButton').click(function (event) {
		dupeSearchDialog();
	});

	$('#invalidButton').click(function (event) {
		invalidSearch();
	});

	$('#editButton').click(function (event) {
		editQsos();
	});

	$('#optionButton').click(function (event) {
		$('#optionButton').prop("disabled", true);
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/userOptions',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_options,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					onshown: function(dialog) {
					},
					buttons: [{
						label: lang_gen_advanced_logbook_save,
						cssClass: 'btn-primary btn-sm',
						id: 'saveButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							$('#closeButton').prop("disabled", true);
							saveOptions().then(() => {
								dialogItself.close();
								location.reload();
							}).catch(error => {
								BootstrapDialog.alert({
									title: lang_gen_advanced_logbook_error,
									message: lang_gen_advanced_logbook_error_saving_options + error,
									type: BootstrapDialog.TYPE_DANGER, // Sets the dialog style to "danger"
									closable: true,
									buttonLabel: lang_gen_advanced_logbook_close
								});
							});
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						$('#optionButton').prop("disabled", false);
					},
				});
			}
		});
	});

	$('#qslSlideshow').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_at_least_one_row_qslcard,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
		$('#qslSlideshow').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/qslSlideshow',
			type: 'post',
			data: {
				ids: JSON.stringify(id_list),
			},
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_qsl_card,
					size: BootstrapDialog.SIZE_WIDE,
					cssClass: 'lookup-dialog',
					nl2br: false,
					message: html,
					onshown: function(dialog) {

					},
					buttons: [{
						label: lang_admin_close,
						action: function (dialogItself) {
							$('#qslSlideshow').prop("disabled", false);
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						$('#qslSlideshow').prop("disabled", false);
					},
				});
			}
		});
	});

	$('#fixCqZones').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_row_cq_zones,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixCqZones',
			type: 'POST',
			data: { 'ids': JSON.stringify(id_list, null, 2) },
			success: function (response) {
				if (response != []) {
					$.each(response, function(k, v) {
						updateRow(this);
						unselectQsoID(this.qsoID);
					});
				}
				BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_success,
					message: lang_gen_advanced_logbook_cq_zones_updated,
					type: BootstrapDialog.TYPE_SUCCESS
				});
			},
			error: function () {
				BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_error,
					message: lang_gen_advanced_logbook_problem_fixing_cq_zones,
					type: BootstrapDialog.TYPE_DANGER
				});
			}
		});
	});

	$('#fixContinent').click(function (event) {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/continentDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_continent_fix,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_gen_advanced_logbook_update_now + ' <div class="ld ld-ring ld-spin"></div>',
						cssClass: 'btn btn-sm btn-primary ld-ext-right',
						id: 'updateContinentButton',
						action: function (dialogItself) {
							runContinentFix(dialogItself);
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn btn-sm btn-secondary',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			}
		});
	});

	$('#updateDistances').click(function (event) {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/distanceDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_update_distances,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_gen_advanced_logbook_update_now  + ' <div class="ld ld-ring ld-spin"></div>',
						cssClass: 'btn btn-sm btn-primary ld-ext-right',
						id: 'updateDistanceButton',
						action: function (dialogItself) {
							runUpdateDistancesFix(dialogItself);
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn btn-sm btn-secondary',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			}
		});
	});

	$('#dbtools').click(function (event) {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/dbtoolsDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: 'Database tools',
					size: BootstrapDialog.SIZE_EXTRAWIDE,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_admin_close,
						cssClass: 'btn btn-sm btn-secondary',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			}
		});
	});



	$('#fixItuZones').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_row_itu_zones,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixItuZones',
			type: 'post',
			data: {
				'ids': JSON.stringify(id_list, null, 2)
			},
			success: function (response) {
				if (response != []) {
					$.each(response, function(k, v) {
						updateRow(this);
						unselectQsoID(this.qsoID);
					});
				}
				BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_success,
					message: lang_gen_advanced_logbook_itu_zones_updated,
					type: BootstrapDialog.TYPE_SUCCESS
				});
			},
			error: function () {
				BootstrapDialog.alert({
					title: lang_gen_advanced_logbook_error,
					message: lang_gen_advanced_logbook_problem_fixing_itu_zones,
					type: BootstrapDialog.TYPE_DANGER
				});
			}
		});
	});

	// Fix State button handler
	$('#fixState').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_row_state,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/stateDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_fixing_state,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
					{
						label: lang_gen_advanced_logbook_update_now + ' <div class="ld ld-ring ld-spin"></div>',
						cssClass: 'btn btn-sm btn-primary ld-ext-right',
						id: 'updateStateButton',
						action: function (dialogItself) {
							const id_list = getSelectedIds();

							if (inStateFixing) {
								return;
							}
							inStateFixing = true;

							// Close the info dialog
							dialogItself.close();

							// Reset statistics
							stateFixStats = {fixed: 0, skipped: 0, fixedDxcc: new Set(), skippedDxcc: new Set(), skipReasons: new Set(), skippedDetails: []};

							const nElements = id_list.length;
							stateFixingDialog = BootstrapDialog.show({
								title: lang_gen_advanced_logbook_fixing_state_qsos.replace('%s', nElements),
								message: lang_gen_advanced_logbook_fixing_state_remaining.replace('%s', nElements),
								type: BootstrapDialog.TYPE_INFO,
								closable: false,
								draggable: false,
								buttons: [{
									label: lang_admin_close,
									action: function(dialog) {
										inStateFixing = false;
										dialog.close();
									}
								}]
							});
							processNextStateFixItem();
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn btn-sm btn-secondary',
						id: 'closeStateDialogButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			}
		});
	});

	function dupeSearchDialog() {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/dupeSearchDialog',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_dupe_search,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					buttons: [
						{
							label: lang_gen_advanced_logbook_search + ' <div class="ld ld-ring ld-spin"></div>',
							cssClass: 'btn btn-sm btn-primary ld-ext-right',
							id: 'dupeSearchButton',
							action: function (dialogItself) {
								dialogItself.close();
								$('#dupedate').val($('#date_check').is(':checked') ? "Y" : "N");
								$('#dupemode').val($('#mode_check').is(':checked') ? "Y" : "N");
								$('#dupeband').val($('#band_check').is(':checked') ? "Y" : "N");
								$('#dupesat').val($('#satellite_check').is(':checked') ? "Y" : "N");
								dupeSearch();
							}
						},
						{
							label: lang_admin_close,
							cssClass: 'btn btn-sm btn-secondary',
							id: 'closeDupeDialogButton',
							action: function (dialogItself) {
								dialogItself.close();
							}
						}],
				});
			}
		});
	}

	function dupeSearch() {
		$("#dupes").val("Y");
		$('#dupeButton').prop('disabled', true).addClass('running');
		setTimeout(() => {
			$('#dupeButton').prop('disabled', false).removeClass("running");
		}, 1000);
		$('#searchForm').submit();
	}

	function invalidSearch() {
		$("#invalid").val("Y");
		$('#invalidButton').prop('disabled', true).addClass('running');
		setTimeout(() => {
			$('#invalidButton').prop('disabled', false).removeClass("running");
		}, 1000);
		$('#searchForm').submit();
	}

	function quickSearch(type) {
		var elements = $('#qsoList tbody input:checked');
		var nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_at_least_one_row_quickfilter,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
		if (nElements > 1) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_warning,
				message: lang_gen_advanced_logbook_select_only_one_row_quickfilter,
				type: BootstrapDialog.TYPE_WARNING,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		elements.each(function() {
			var currentRow = $(this).first().closest('tr');
			var col1 = '';
			switch (type) {
				case 'dxcc': 		col1 = currentRow.find('#dxcc').html(); col1 = col1.match(/\d/g); col1 = col1.join(""); break;
				case 'cqzone': 		col1 = currentRow.find('#cqzone').text(); break;
				case 'ituzone': 	col1 = currentRow.find('#ituzone').text(); break;
				case 'iota': 		col1 = currentRow.find('#iota').text(); col1 = col1.trim(); break;
				case 'state': 		col1 = currentRow.find('#state').text(); break;
				case 'dx': 			col1 = currentRow.find('#dx').text().replaceAll('Ø', '0'); col1 = col1.match(/^([^\s]+)/gm); break;
				case 'gridsquare': 	col1 = $(currentRow).find('#dxgrid').text(); col1 = col1.substring(0, 4); break;
				case 'sota': 		col1 = $(currentRow).find('#dxsota').text(); break;
				case 'wwff': 		col1 = $(currentRow).find('#dxwwff').text(); break;
				case 'pota': 		col1 = $(currentRow).find('#dxpota').text(); break;
				case 'operator': 	col1 = $(currentRow).find('#operator').text(); break;
				case 'mode': 		col1 = currentRow.find("td:eq(4)").text(); break;
				case 'band': 		col1 = currentRow.find("td:eq(7)").text(); col1 = col1.match(/\S\w*/); break;
				case 'date': 		col1 = currentRow.find("td:eq(1)").text(); break;
			}
			if (col1.length == 0) return;
			silentReset = true;
			$('#searchForm').trigger("reset");

			if (type == 'date') {
				let dateParts;
				let formattedDate;

			switch (custom_date_format) {
				case "DD/MM/YY":
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${ensureFourDigitYear(dateParts[2])}-${dateParts[1]}-${dateParts[0]}`;
					break;

				case "DD/MM/YYYY":
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
					break;

				case "MM/DD/YY":
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${ensureFourDigitYear(dateParts[2])}-${dateParts[0]}-${dateParts[1]}`;
					break;

				case "MM/DD/YYYY":
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${dateParts[2]}-${dateParts[0]}-${dateParts[1]}`;
					break;

				case "DD.MM.YYYY":
					dateParts = col1.split(' ')[0].split('.');
					formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
					break;

				case "YY/MM/DD":
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${ensureFourDigitYear(dateParts[0])}-${dateParts[1]}-${dateParts[2]}`;
					break;

				case "YYYY-MM-DD":
					dateParts = col1.split(' ')[0].split('-');
					formattedDate = `${dateParts[0]}-${dateParts[1]}-${dateParts[2]}`;
					break;

				case "MMM DD, YY":
				case "MMM DD, YYYY":
					const monthNames = {
						Jan: "01",
						Feb: "02",
						Mar: "03",
						Apr: "04",
						May: "05",
						Jun: "06",
						Jul: "07",
						Aug: "08",
						Sep: "09",
						Oct: "10",
						Nov: "11",
						Dec: "12"
					};

					// Split by space and comma
					const parts = col1.replace(',', '').split(' '); // Example: ["Dec", "03", "24"]

					const month = monthNames[parts[0]]; // Convert month name to numeric format
					const day = parts[1].padStart(2, '0'); // Ensure day has leading zero
					const year = ensureFourDigitYear(parts[2]); // Ensure 4-digit year

					formattedDate = `${year}-${month}-${day}`; // Convert to 'YYYY-MM-DD'
					break;

				default:
					dateParts = col1.split(' ')[0].split('/');
					formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
			}
				$("#dateFrom").val(formattedDate);
				$("#dateTo").val(formattedDate);
			} else {
				$("#"+type).val(col1);
			}
			$('#searchForm').submit();
		});
	}

	function ensureFourDigitYear(year) { 				// Utility function to handle 2-digit year conversion
		if (year.length === 2) {
			return parseInt(year, 10) <= 49
				? `20${year}` // Years 00-49 are in the 21st century
				: `19${year}`; // Years 50-99 are in the 20th century
		}
		return year; // If already 4 digits, return as is
	}

	$('#printLabel').click(function (event) {
		const id_list = getSelectedIds();

		if (id_list.length === 0) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_info,
				message: lang_gen_advanced_logbook_select_at_least_one_row_label,
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
		$('#printLabel').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/startAtLabel',
			type: 'post',
			success: function (html) {
				BootstrapDialog.show({
					title: lang_gen_advanced_logbook_start_printing_at_which_label,
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'qso-dialog',
					nl2br: false,
					message: html,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Print',
						cssClass: 'btn-primary btn-sm',
						action: function (dialogItself) {
							printlabel(id_list);
							dialogItself.close();
						}
					},
						{
						label: lang_admin_close,
						action: function (dialogItself) {
							$('#printLabel').prop("disabled", false);
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						$('#printLabel').prop("disabled", false);
					},
				});
			}
		});
	});

	$('#searchForm').on('reset', function(e) {
		if (silentReset) {
    	    silentReset = false; // reset flag
        	return; // skip submit
    	}
		setTimeout(function() {
			$('#searchForm').submit();
		});
	});

	rebind_checkbox_trigger();

	$('#searchForm').submit();

});

function rebind_checkbox_trigger() {
	$('#checkBoxAll').change(function (event) {
		if (this.checked) {
			$('#qsoList tbody tr').each(function (i) {
				selectQsoID($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''));
			});
		} else {
			$('#qsoList tbody tr').each(function (i) {
				unselectQsoID($(this).first().closest('tr').attr('id')?.replace(/\D/g, ''));
			});
		}
	});
}

function handleQsl(sent, method, tag) {
	const id_list = getSelectedIdsForMap();

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

	$('#'+tag).prop("disabled", true);

	$.ajax({
		url: base_url + 'index.php/logbookadvanced/update_qsl',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
			'sent' : sent,
			'method' : method
		},
		success: function(data) {
			if (data != []) {
				$.each(data, function(k, v) {
					updateRow(this);
					unselectQsoID(this.qsoID);
				});
			}
			$('#'+tag).prop("disabled", false);
		}
	});
}

function handleQslReceived(sent, method, tag) {
	const id_list = getSelectedIdsForMap();

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

	$.ajax({
		url: base_url + 'index.php/logbookadvanced/update_qsl_received',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
			'sent' : sent,
			'method' : method
		},
		success: function(data) {
			if (data != []) {
				$.each(data, function(k, v) {
					updateRow(this);
					unselectQsoID(this.qsoID);
				});
			}
			$('#'+tag).prop("disabled", false);
		}
	});
}

function printlabel(id_list) {
	let markchecked = $('#markprinted')[0].checked;

	$.ajax({
		url: base_url + 'index.php/labels/printids',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
				'startat': $('#startat').val(),
				'grid': $('#gridlabel')[0].checked,
				'via': $('#via')[0].checked,
				'tnxmsg': $('#tnxmsg')[0].checked,
				'qslmsg': $('#qslmsg')[0].checked,
				'reference': $('#reference')[0].checked
			},
		xhr:function(){
			var xhr = new XMLHttpRequest();
			xhr.responseType= 'blob'
			return xhr;
		},
		success: function(data) {
			if (markchecked) {
				handleQsl('Y','B', 'sentBureau');
			} else {
				$.each(id_list, function(k, v) {
					unselectQsoID(this);
				});
			}
			$.each(BootstrapDialog.dialogs, function(id, dialog){
				dialog.close();
			});
			if(data){
				var file = new Blob([data], {type: 'application/pdf'});
				var fileURL = URL.createObjectURL(file);
				window.open(fileURL);
			}
			$('#printLabel').prop("disabled", false);
		},
		error: function (data) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_error,
				message: lang_gen_advanced_logbook_label_print_error,
				type: BootstrapDialog.TYPE_DANGER,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			$.each(id_list, function(k, v) {
				unselectQsoID(this);
			});
			$('#printLabel').prop("disabled", false);
		},
	});
}

function saveOptions() {
	$('#saveButton').prop("disabled", true);
	$('#closeButton').prop("disabled", true);
	return new Promise((resolve, reject) => {
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/setUserOptions',
			type: 'post',
			data: {
				datetime: $('input[name="datetime"]').is(':checked') ? true : false,
				last_modification: $('input[name="last_modification"]').is(':checked') ? true : false,
				de: $('input[name="de"]').is(':checked') ? true : false,
				dx: $('input[name="dx"]').is(':checked') ? true : false,
				mode: $('input[name="mode"]').is(':checked') ? true : false,
				rsts: $('input[name="rsts"]').is(':checked') ? true : false,
				rstr: $('input[name="rstr"]').is(':checked') ? true : false,
				band: $('input[name="band"]').is(':checked') ? true : false,
				myrefs: $('input[name="myrefs"]').is(':checked') ? true : false,
				name: $('input[name="name"]').is(':checked') ? true : false,
				qslvia: $('input[name="qslvia"]').is(':checked') ? true : false,
				qsl: $('input[name="qsl"]').is(':checked') ? true : false,
				clublog: $('input[name="clublog"]').is(':checked') ? true : false,
				lotw: $('input[name="lotw"]').is(':checked') ? true : false,
				eqsl: $('input[name="eqsl"]').is(':checked') ? true : false,
				qslmsgs: $('input[name="qslmsgs"]').is(':checked') ? true : false,
				qslmsgr: $('input[name="qslmsgr"]').is(':checked') ? true : false,
				dxcc: $('input[name="dxcc"]').is(':checked') ? true : false,
				state: $('input[name="state"]').is(':checked') ? true : false,
				county: $('input[name="county"]').is(':checked') ? true : false,
				cqzone: $('input[name="cqzone"]').is(':checked') ? true : false,
				ituzone: $('input[name="ituzone"]').is(':checked') ? true : false,
				iota: $('input[name="iota"]').is(':checked') ? true : false,
				pota: $('input[name="pota"]').is(':checked') ? true : false,
				operator: $('input[name="operator"]').is(':checked') ? true : false,
				comment: $('input[name="comment"]').is(':checked') ? true : false,
				propagation: $('input[name="propagation"]').is(':checked') ? true : false,
				contest: $('input[name="contest"]').is(':checked') ? true : false,
				gridsquare: $('input[name="gridsquare"]').is(':checked') ? true : false,
				sota: $('input[name="sota"]').is(':checked') ? true : false,
				dok: $('input[name="dok"]').is(':checked') ? true : false,
				wwff: $('input[name="wwff"]').is(':checked') ? true : false,
				sig: $('input[name="sig"]').is(':checked') ? true : false,
				region: $('input[name="region"]').is(':checked') ? true : false,
				continent: $('input[name="continent"]').is(':checked') ? true : false,
				distance: $('input[name="distance"]').is(':checked') ? true : false,
				antennaazimuth: $('input[name="antennaazimuth"]').is(':checked') ? true : false,
				antennaelevation: $('input[name="antennaelevation"]').is(':checked') ? true : false,
				qrz: $('input[name="qrz"]').is(':checked') ? true : false,
				profilename: $('input[name="profilename"]').is(':checked') ? true : false,
				stationpower: $('input[name="stationpower"]').is(':checked') ? true : false,
				gridsquare_layer: $('input[name="gridsquareoverlay"]').is(':checked') ? true : false,
				path_lines: $('input[name="pathlines"]').is(':checked') ? true : false,
				cqzone_layer: $('input[name="cqzones"]').is(':checked') ? true : false,
				ituzone_layer: $('input[name="ituzones"]').is(':checked') ? true : false,
				nightshadow_layer: $('input[name="nightshadow"]').is(':checked') ? true : false,
				qth: $('input[name="qth"]').is(':checked') ? true : false,
				frequency: $('input[name="frequency"]').is(':checked') ? true : false,
				dcl: $('input[name="dcl"]').is(':checked') ? true : false
			},
			success: function(data) {
				$('#saveButton').prop("disabled", false);
				$('#closeButton').prop("disabled", false);
				resolve(data);
			},
			error: function(error) {
				$('#saveButton').prop("disabled", false);
				reject(error);
			},
		});
	});
}
// Preset functionality
    function applyPreset(preset) {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const today = new Date();

        // Format date as YYYY-MM-DD
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
                const firstDayOfMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 1);
                dateFrom.value = formatDate(firstDayOfMonth);
                dateTo.value = formatDate(today);
                break;

            case 'lastmonth':
                const firstDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth() - 1, 1);
                const lastDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 0);
                dateFrom.value = formatDate(firstDayOfLastMonth);
                dateTo.value = formatDate(lastDayOfLastMonth);
                break;

            case 'thisyear':
                const firstDayOfYear = new Date(today.getUTCFullYear(), 0, 1);
                dateFrom.value = formatDate(firstDayOfYear);
                dateTo.value = formatDate(today);
                break;

            case 'lastyear':
                const lastYear = today.getUTCFullYear() - 1;
                const firstDayOfLastYear = new Date(lastYear, 0, 1);
                const lastDayOfLastYear = new Date(lastYear, 11, 31);
                dateFrom.value = formatDate(firstDayOfLastYear);
                dateTo.value = formatDate(lastDayOfLastYear);
                break;

            case 'alltime':
                dateFrom.value = '';
                dateTo.value = '';
                break;
        }
    }

    // Reset dates function
    function resetDates() {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        dateFrom.value = '';
        dateTo.value = '';
    }

	function checkUpdateDistances() {
		$('#checkUpdateDistancesBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkdistance'
			},
			type: 'POST',
			success: function(response) {
				$('#checkUpdateDistancesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);

				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkUpdateDistancesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop('disabled', false);

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
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkcontinent'
			},
			type: 'POST',
			success: function(response) {
				$('#checkFixContinentBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkFixContinentBtn').prop('disabled', false).text('<?= __("Check") ?>');
				$('#closeButton').prop('disabled', false);

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
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkstate'
			},
			type: 'POST',
			success: function(response) {
				$('#checkFixStateBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);

				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkFixStateBtn').prop('disabled', false).text('<?= __("Check") ?>');
				$('#closeButton').prop('disabled', false);

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

	function checkFixCqZones() {
		$('#checkFixCqZonesBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkcqzones'
			},
			type: 'POST',
			success: function(response) {
				$('#checkFixCqZonesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkFixCqZonesBtn').prop('disabled', false).text('<?= __("Check") ?>');
				$('#closeButton').prop('disabled', false);

				let errorMsg = '<?= __("Error checking distance information") ?>';
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

	function checkFixItuZones() {
		$('#checkFixItuZonesBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkituzones'
			},
			type: 'POST',
			success: function(response) {
				$('#checkFixItuZonesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkFixItuZonesBtn').prop('disabled', false).text('<?= __("Check") ?>');
				$('#closeButton').prop('disabled', false);

				let errorMsg = '<?= __("Error checking distance information") ?>';
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
			url: base_url + 'index.php/logbookadvanced/fixStateBatch',
			type: 'post',
			data: {
				'dxcc': dxcc,
				'country': country
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

	function openStateList(dxcc, country) {
		$('#openStateListBtn_' + dxcc).prop("disabled", true).addClass("running");

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/OpenStateList',
			type: 'post',
			data: {
				'dxcc': dxcc,
				'country': country
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
		$('#closeButton').prop("disabled", true);
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/updateDistances',
			type: 'POST',
			success: function (response) {
				$('#updateDistanceButton').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
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
				$('#closeButton').prop("disabled", false);
			}
		});
	}

	function runContinentFix(dialogItself) {
		$('#updateContinentButton').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixContinent',
			type: 'POST',
			success: function (response) {
				$('#updateContinentButton').prop("disabled", false).removeClass("running");
				if (dialogItself != '') {
					dialogItself.close();
				}
				$('.result').html(response);
				$('#closeButton').prop("disabled", false);
			},
			error: function(xhr, status, error) {
				$('#updateContinentButton').prop("disabled", false).removeClass("running");
				$('.result').html(error);
				$('#closeButton').prop("disabled", false);
			}
		});
	}

	function checkGrids() {
		$('#checkGridsBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkgrids'
			},
			type: 'POST',
			success: function(response) {
				$('#checkGridsBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#checkGridsBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop('disabled', false);

				$('.result').html(error);
			}
		});
	}

	function fixMissingGrids() {
		$('#updateGridsBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/batchFix',
			data: {
				type: 'grids'
			},
			type: 'POST',
			success: function (response) {
				$('#updateGridsBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('#updateGridsBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(error);
			}
		});
	}

	function checkDxcc() {
		$('#checkDxccBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkdxcc'
			},
			type: 'POST',
			success: function(response) {
				$('#checkDxccBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
				$('#dxccCheckTable').DataTable({
					"pageLength": 25,
					responsive: false,
					ordering: true,
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
									var text = $(this).text().trim();
									if (text) {
										counts[text] = (counts[text] || 0) + 1;
									}
								});

								// Add options with counts
								for (var text in counts) {
									if (!select.find('option[value="' + text + '"]').length) {
										select.append('<option value="' + text + '">' + text + ' (' + counts[text] + ')</option>');
									}
								}

								// Sort options
								select.find('option:not(:first)').sort(function(a, b) {
									return a.text.localeCompare(b.text);
								}).appendTo(select);
							});
							rebind_checkbox_trigger_dxcc();
					},
				});
			},
			error: function(xhr, status, error) {
				$('#checkDxccBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop('disabled', false);
				$('.result').html(error);
			}
		});
	}

	function checkIncorrectCqZones() {
		$('#checkIncorrectCqZonesBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkincorrectcqzones'
			},
			type: 'POST',
			success: function(response) {
				$('#checkIncorrectCqZonesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
				$('#incorrectcqzonetable').DataTable({
					"pageLength": 25,
					responsive: false,
					ordering: false,
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
									var text = $(this).text().trim();
									if (text) {
										counts[text] = (counts[text] || 0) + 1;
									}
								});

								// Add options with counts
								for (var text in counts) {
									if (!select.find('option[value="' + text + '"]').length) {
										select.append('<option value="' + text + '">' + text + ' (' + counts[text] + ')</option>');
									}
								}

								// Sort options
								select.find('option:not(:first)').sort(function(a, b) {
									return a.text.localeCompare(b.text);
								}).appendTo(select);
							});
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
				$('#closeButton').prop('disabled', false);
				$('.result').html(error);
			}
		});
	}

	function checkIncorrectItuZones() {
		$('#checkIncorrectItuZonesBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkincorrectituzones'
			},
			type: 'POST',
			success: function(response) {
				$('#checkIncorrectItuZonesBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
				$('#incorrectituzonetable').DataTable({
					"pageLength": 25,
					responsive: false,
					ordering: false,
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
									var text = $(this).text().trim();
									if (text) {
										counts[text] = (counts[text] || 0) + 1;
									}
								});

								// Add options with counts
								for (var text in counts) {
									if (!select.find('option[value="' + text + '"]').length) {
										select.append('<option value="' + text + '">' + text + ' (' + counts[text] + ')</option>');
									}
								}

								// Sort options
								select.find('option:not(:first)').sort(function(a, b) {
									return a.text.localeCompare(b.text);
								}).appendTo(select);
							});
							rebind_checkbox_trigger_itu_zone();
					},
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
				$('#closeButton').prop('disabled', false);
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
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixDxccSelected',
			type: 'post',
			data: {'ids': JSON.stringify(id_list, null, 2)},
			success: function(data) {
				$('#fixSelectedDxccBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
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
				$('#closeButton').prop("disabled", false);
				$('.result').html(error);
			}
		});
	}

	function checkIncorrectGridsquares() {
		$('#checkIncorrectGridsquaresBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkincorrectgridsquares'
			},
			type: 'POST',
			success: function(response) {
				$('#checkIncorrectGridsquaresBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(response);
				$('#gridsquareCheckTable').DataTable({
					"pageLength": 25,
					responsive: false,
					ordering: false,
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
									var text = $(this).text().trim();
									if (text) {
										counts[text] = (counts[text] || 0) + 1;
									}
								});

								// Add options with counts
								for (var text in counts) {
									if (!select.find('option[value="' + text + '"]').length) {
										select.append('<option value="' + text + '">' + text + ' (' + counts[text] + ')</option>');
									}
								}

								// Sort options
								select.find('option:not(:first)').sort(function(a, b) {
									return a.text.localeCompare(b.text);
								}).appendTo(select);
							});
					},
				});
			},
			error: function(xhr, status, error) {
				$('#checkIncorrectGridsquaresBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop('disabled', false);
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
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixCqZones',
			type: 'post',
			data: {'ids': JSON.stringify(id_list, null, 2)},
			success: function(data) {
				$('#fixSelectedCqZoneBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				id_list.forEach(function(id) {
					let row = $("#incorrectcqzonetable tbody tr#qsoID-" + id);
					table.row(row).remove();
				});
				table.draw(false);
			},
			error: function(xhr, status, error) {
				$('#fixSelectedCqZoneBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
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
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/fixItuZones',
			type: 'post',
			data: {'ids': JSON.stringify(id_list, null, 2)},
			success: function(data) {
				$('#fixSelectedItuZoneBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				id_list.forEach(function(id) {
					let row = $("#incorrectituzonetable tbody tr#qsoID-" + id);
					table.row(row).remove();
				});
				table.draw(false);
			},
			error: function(xhr, status, error) {
				$('#fixSelectedItuZoneBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);
				$('.result').html(error);
			}
		});
	}

	function checkIota() {
		$('#checkIotaBtn').prop("disabled", true).addClass("running");
		$('#closeButton').prop("disabled", true);

		$.ajax({
			url: base_url + 'index.php/logbookadvanced/checkDb',
			data: {
				type: 'checkiota'
			},
			type: 'POST',
			success: function(response) {
				$('#checkIotaBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop("disabled", false);

				$('.result').html(response);
				$('#iotaCheckTable').DataTable({
					"pageLength": 25,
					responsive: false,
					ordering: false,
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
									// Get text from the first anchor link which contains the IOTA reference
									var $anchor = $(this).find('a').first();
									var text = $anchor.length ? $anchor.text().trim() : $(this).text().trim();
									// Remove any extra whitespace
									text = text.split(/\s+/)[0];
									if (text) {
										counts[text] = (counts[text] || 0) + 1;
									}
								});

								// Add options with counts
								for (var text in counts) {
									if (!select.find('option[value="' + text + '"]').length) {
										select.append('<option value="' + text + '">' + text + ' (' + counts[text] + ')</option>');
									}
								}

								// Sort options
								select.find('option:not(:first)').sort(function(a, b) {
									return a.text.localeCompare(b.text);
								}).appendTo(select);
							});
					},
				});
			},
			error: function(xhr, status, error) {
				$('#checkIotaBtn').prop("disabled", false).removeClass("running");
				$('#closeButton').prop('disabled', false);

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
			url: base_url + 'index.php/logbookadvanced/showMapForIncorrectGrid',
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

			div.innerHTML =
				'<div style="display: flex; align-items: center; margin-bottom: 8px;">' +
					'<div style="width: 20px; height: 20px; background-color: ' + confirmedColor + '; border: 1px solid #ccc; margin-right: 8px;"></div>' +
					'<span style="font-size: 12px;">' + lang_gen_advanced_logbook_confirmedLabel + ' ' + data.dxccnameDisplay + '</span>' +
				'</div>' +
				'<div style="display: flex; align-items: center;">' +
					'<div style="width: 20px; height: 20px; background-color: ' + workedColor + '; border: 1px solid #ccc; margin-right: 8px;"></div>' +
					'<span style="font-size: 12px;">' + lang_gen_advanced_logbook_workedLabel + ' ' + data.gridsquareDisplay + '</span>' +
				'</div>';
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
