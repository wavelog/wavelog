var callBookProcessingDialog = null;
var inCallbookProcessing = false;
var inCallbookItemProcessing = false;

// Array of valid continent codes
const validContinents = ['AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN'];

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

function updateRow(qso) {
	let row = $('#qsoID-' + qso.qsoID);
	let cells = row.find('td');
	let c = 1;
	if (user_options.datetime.show == "true"){
		cells.eq(c++).text(qso.qsoDateTime);
	}
	if (user_options.de.show == "true"){
		cells.eq(c++).text(qso.de);
	}
	if (user_options.dx.show == "true"){
		cells.eq(c++).html('<span class="qso_call"><a id="edit_qso" href="javascript:displayQso('+qso.qsoID+')"><span id="dx">'+qso.dx.replaceAll('0', 'Ø')+'</span></a><span class="qso_icons">' + (qso.callsign == '' ? '' : ' <a href="https://lotw.arrl.org/lotwuser/act?act='+qso.callsign+'" target="_blank"><small id="lotw_info" class="badge bg-success'+qso.lotw_hint+'" data-bs-toggle="tooltip" title="LoTW User. Last upload was ' + qso.lastupload + '">L</small></a>') + ' <a target="_blank" href="https://www.qrz.com/db/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/qrz.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/hamqth.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on HamQTH"></a> <a target="_blank" href="https://clublog.org/logsearch.php?log='+qso.dx+'&call='+qso.de+'"><img width="16" height="16" src="'+base_url+'images/icons/clublog.png" alt="Clublog Log Search"></a></span></span>');
	}
	if (user_options.mode.show == "true"){
		cells.eq(c++).text(qso.mode);
	}
	if (user_options.rsts.show == "true"){
		cells.eq(c++).text(qso.rstS);
	}
	if (user_options.rstr.show == "true"){
		cells.eq(c++).text(qso.rstR);
	}
	if (user_options.band.show == "true"){
		cells.eq(c++).text(qso.band);
	}
	if ( (user_options.gridsquare) && (user_options.gridsquare.show == "true")){
		cells.eq(c++).html(qso.gridsquare);
	}
	if (user_options.name.show == "true"){
		cells.eq(c++).text(qso.name);
	}
	if (user_options.qslvia.show == "true"){
		cells.eq(c++).text(qso.qslVia);
	}
	if (user_options.clublog.show == "true"){
		cells.eq(c++).html(qso.clublog);
	}
	if (user_options.qsl.show == "true"){
		cells.eq(c++).html(qso.qsl);
	}
	if ($(".eqslconfirmation")[0] && user_options.eqsl.show == "true"){
		cells.eq(c++).html(qso.eqsl);
	}
	if ($(".lotwconfirmation")[0] && user_options.lotw.show == "true"){
		cells.eq(c++).html(qso.lotw);
	}
	if (user_options.qrz.show == "true"){
		cells.eq(c++).html(qso.qrz);
	}
	if (user_options.qslmsg.show == "true"){
		cells.eq(c++).text(qso.qslMessage);
	}
	if (user_options.dxcc.show == "true"){
		cells.eq(c++).html(qso.dxccname);
	}
	if (user_options.state.show == "true"){
		cells.eq(c++).html(qso.state);
	}
	if (user_options.cqzone.show == "true"){
		cells.eq(c++).html(qso.cqzone);
	}
	if (user_options.ituzone.show == "true"){
		cells.eq(c++).html(qso.ituzone);
	}
	if (user_options.iota.show == "true"){
		cells.eq(c++).html(qso.iota);
	}
	if (user_options.pota.show == "true"){
		cells.eq(c++).html(qso.pota);
	}
	if ( (user_options.operator) && (user_options.operator.show == "true")){
		cells.eq(c++).html(qso.operator);
	}
	if ( (user_options.comment) && (user_options.comment.show == "true")){
		cells.eq(c++).html(qso.comment);
	}
	if ( (user_options.propagation) && (user_options.propagation.show == "true")){
		cells.eq(c++).html(qso.propagation);
	}
	if ( (user_options.contest) && (user_options.contest.show == "true")){
		cells.eq(c++).html(qso.contest);
	}
	if ( (user_options.sota) && (user_options.sota.show == "true")){
		cells.eq(c++).html(qso.sota);
	}
	if ( (user_options.dok) && (user_options.dok.show == "true")){
		cells.eq(c++).html(qso.dok);
	}
	if ( (user_options.wwff) && (user_options.wwff.show == "true")){
		cells.eq(c++).html(qso.wwff);
	}
	if ( (user_options.sig) && (user_options.sig.show == "true")){
		cells.eq(c++).html(qso.sig);
	}
	if (user_options.myrefs.show == "true"){
		cells.eq(c++).text(qso.deRefs);
	}
	if (user_options.continent.show == "true"){
		cells.eq(c++).text(qso.continent);
	}

	$('[data-bs-toggle="tooltip"]').tooltip();
	return row;
}

function loadQSOTable(rows) {
	var uninitialized = $('#qsoList').filter(function() {
		return !$.fn.DataTable.fnIsDataTable(this);
	});

	uninitialized.each(function() {
		$.fn.dataTable.moment(custom_date_format + ' HH:mm');
		$(this).DataTable({
			searching: false,
			responsive: false,
			ordering: true,
			"scrollY": window.innerHeight - $('#searchForm').innerHeight() - 250,
			"scrollCollapse": true,
			"paging":         false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			// colReorder: {
			// 	order: [0, 2,1,3,4,5,6,7,8,9,10,12,13,14,15,16,17,18]
			// 	// order: [0, customsortorder]
			//   },
		});
	});

	var table = $('#qsoList').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let qso = rows[i];

		var data = [];
		data.push('<div class="form-check"><input class="form-check-input" type="checkbox" /></div>');
		if (user_options.datetime.show == "true"){
			if (qso.datetime === '') {
				data.push('<span class="bg-danger">Missing date</span>');
			} else {
				data.push(qso.qsoDateTime);
			}
		}
		if (user_options.de.show == "true"){
			data.push(qso.de.replaceAll('0', 'Ø'));
		}
		if (user_options.dx.show == "true"){
			if (qso.dx === '') {
				data.push('<span class="bg-danger">Missing callsign</span>');
			} else {
				data.push('<span class="qso_call"><a id="edit_qso" href="javascript:displayQso('+qso.qsoID+')"><span id="dx">'+qso.dx.replaceAll('0', 'Ø')+'</span></a><span class="qso_icons">' + (qso.callsign == '' ? '' : ' <a href="https://lotw.arrl.org/lotwuser/act?act='+qso.callsign+'" target="_blank"><small id="lotw_info" class="badge bg-success'+qso.lotw_hint+'" data-bs-toggle="tooltip" title="LoTW User. Last upload was ' + qso.lastupload + ' ">L</small></a>') + ' <a target="_blank" href="https://www.qrz.com/db/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/qrz.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/'+qso.dx+'"><img width="16" height="16" src="'+base_url+ 'images/icons/hamqth.png" alt="Lookup ' + qso.dx.replaceAll('0', 'Ø') + ' on HamQTH"></a> <a target="_blank" href="https://clublog.org/logsearch.php?log='+qso.dx+'&call='+qso.de+'"><img width="16" height="16" src="'+base_url+'images/icons/clublog.png" alt="Clublog Log Search"></a></span></span>');
			}
		}
		if (user_options.mode.show == "true"){
			if (qso.mode === '') {
				data.push('<span class="bg-danger">Missing mode</span>');
			} else {
				data.push(qso.mode);
			}
		}
		if (user_options.rsts.show == "true"){
			data.push(qso.rstS);
		}
		if (user_options.rstr.show == "true"){
			data.push(qso.rstR);
		}
		if (user_options.band.show == "true"){
			if (qso.band === '') {
				data.push('<span class="bg-danger">Missing band</span>');
			} else {
				data.push(qso.band);
			}
		}
		if (user_options.gridsquare.show == "true"){
			data.push(qso.gridsquare);
		}
		if (user_options.name.show == "true"){
			data.push(qso.name);
		}
		if (user_options.qslvia.show == "true"){
			data.push(qso.qslVia);
		}
		if (user_options.clublog.show == "true"){
			data.push(qso.clublog);
		}
		if (user_options.qsl.show == "true"){
			data.push(qso.qsl);
		}
		if ($(".eqslconfirmation")[0] && user_options.eqsl.show == "true"){
			data.push(qso.eqsl);
		}
		if ($(".lotwconfirmation")[0] && user_options.lotw.show == "true"){
			data.push(qso.lotw);
		}
		if (user_options.qrz.show == "true"){
			data.push(qso.qrz);
		}
		if (user_options.qslmsg.show == "true"){
			data.push(qso.qslMessage);
		}
		if (user_options.dxcc.show == "true"){
			data.push(qso.dxcc+(qso.end == null ? '' : ' <span class="badge bg-danger">Deleted DXCC</span>'));
		}
		if (user_options.state.show == "true"){
			data.push(qso.state);
		}
		if (user_options.cqzone.show == "true"){
			data.push(qso.cqzone);
		}
		if (user_options.ituzone.show == "true"){
			data.push(qso.ituzone);
		}
		if (user_options.iota.show == "true"){
			data.push(qso.iota);
		}
		if (user_options.pota.show == "true"){
			data.push(qso.pota);
		}
		if (user_options.operator.show == "true"){
			data.push(qso.operator);
		}
		if (user_options.comment.show == "true"){
			data.push(qso.comment);
		}
		if (user_options.propagation.show == "true"){
			data.push(qso.propagation);
		}
		if (user_options.contest.show == "true"){
			data.push(qso.contest);
		}
		if (user_options.sota.show == "true"){
			data.push(qso.sota);
		}
		if (user_options.dok.show == "true"){
			data.push(qso.dok);
		}
		if (user_options.wwff.show == "true"){
			data.push(qso.wwff);
		}
		if (user_options.sig.show == "true"){
			data.push(qso.sig);
		}
		if (user_options.myrefs.show == "true"){
			data.push(qso.deRefs);
		}
		if (user_options.continent.show == "true"){
			if (qso.continent === '') {
				data.push(qso.continent);
			} else if (!validContinents.includes(qso.continent.toUpperCase())) {
				// Check if qso.continent is not in the list of valid continents
				data.push('<span class="bg-danger">Invalid continent</span> ' + qso.continent);
			} else {
				// Continent is valid
				data.push(qso.continent);
			}
		}

		let createdRow = table.row.add(data).index();
		table.rows(createdRow).nodes().to$().data('qsoID', qso.qsoID);
		table.row(createdRow).node().id = 'qsoID-' + qso.qsoID;
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

function processNextCallbookItem() {
	if (!inCallbookProcessing) return;

	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		inCallbookProcessing = false;
		callBookProcessingDialog.close();
		return;
	}

	callBookProcessingDialog.setMessage("Retrieving callbook data : " + nElements + " remaining");

	unselectQsoID(elements.first().closest('tr').data('qsoID'));

	$.ajax({
		url: site_url + '/logbookadvanced/updateFromCallbook',
		type: 'post',
		data: {
			qsoID: elements.first().closest('tr').data('qsoID')
		},
		dataType: 'json',
		success: function (data) {
			if (data != []) {
				updateRow(data);
			}
			setTimeout("processNextCallbookItem()", 50);
		},
		error: function (data) {
			setTimeout("processNextCallbookItem()", 50);
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


	$('#searchForm').submit(function (e) {
		let container = L.DomUtil.get('advancedmap');
		let selectedlocations = $('#de').val();
		if (Array.isArray(selectedlocations) && selectedlocations.length === 0) {
			BootstrapDialog.alert({
				title: 'INFO',
				message: 'You need to select at least 1 location to do a search!',
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

		$('#searchButton').prop("disabled", true).addClass("running");
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
				qsoresults: this.qsoresults.value,
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
				qslvia: $('[name="qslvia"]').val(),
				sota: this.sota.value,
				pota: this.pota.value,
				wwff: this.wwff.value,
				qslimages: this.qslimages.value,
				dupes: this.dupes.value,
				contest: this.contest.value,
				invalid: this.invalid.value,
				continent: this.continent.value,
			},
			dataType: 'json',
			success: function (data) {
				$('#searchButton').prop("disabled", false).removeClass("running");
				loadQSOTable(data);
			},
			error: function (data) {
				$('#searchButton').prop("disabled", false).removeClass("running");
				BootstrapDialog.alert({
					title: 'ERROR',
					message: 'An error ocurred while making the request',
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
		var elements = $('#qsoList tbody input:checked');
		var nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: 'INFO',
				message: 'You need to select a least 1 row to update from callbook!',
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
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
		processNextCallbookItem();
	});

	$('#deleteQsos').click(function (event) {
		var elements = $('#qsoList tbody input:checked');
		var nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: 'INFO',
				message: 'You need to select a least 1 row to delete!',
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}

		var id_list=[];
		elements.each(function() {
			let id = $(this).first().closest('tr').data('qsoID')
			id_list.push(id);
		});

		$('#deleteQsos').prop("disabled", true);

		var table = $('#qsoList').DataTable();

		BootstrapDialog.confirm({
			title: lang_general_word_danger,
			message: lang_filter_actions_delete_warning,
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
							elements.each(function() {
								let id = $(this).first().closest('tr').data('qsoID')
								var row = $("#qsoID-" + id);
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
		var elements = $('#qsoList tbody input:checked');

		$('#exportAdif').prop("disabled", true);
		var id_list=[];
		elements.each(function() {
			let id = $(this).first().closest('tr').data('qsoID')
			id_list.push(id);
			unselectQsoID(id);
		});

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
			xhttp.send("id=" + JSON.stringify(id_list, null, 2)+"&sortorder=" +$('.table').DataTable().order());
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
		dupeSearch();
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
					title: 'Options for the Advanced Logbook',
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					nl2br: false,
					message: html,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Save',
						cssClass: 'btn-primary btn-sm',
						id: 'saveButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							$('#closeButton').prop("disabled", true);
							saveOptions();
							dialogItself.close();
							location.reload();
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
		var elements = $('#qsoList tbody input:checked');
		var nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: 'INFO',
				message: 'You need to select a least 1 row to display a QSL card!',
				type: BootstrapDialog.TYPE_INFO,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			return;
		}
		$('#qslSlideshow').prop("disabled", true);
		var id_list=[];
		elements.each(function() {
			let id = $(this).first().closest('tr').data('qsoID')
			id_list.push(id);
		});
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/qslSlideshow',
			type: 'post',
			data: {
				ids: id_list,
			},
			success: function (html) {
				BootstrapDialog.show({
					title: 'QSL Card',
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
				title: 'INFO',
				message: 'You need to select a row to use the Quickfilters!',
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
				title: 'WARNING',
				message: 'Only 1 row can be selected for Quickfilter!',
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
			}
			if (col1.length == 0) return;
			$('#searchForm').trigger("reset");
			$("#"+type).val(col1);
			$('#searchForm').submit();
		});
	}

	$('#printLabel').click(function (event) {
		var elements = $('#qsoList tbody input:checked');
		var nElements = elements.length;
		if (nElements == 0) {
			BootstrapDialog.alert({
				title: 'INFO',
				message: 'You need to select at least 1 row to print a label!',
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
					title: 'Start printing at which label?',
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
							printlabel();
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
		setTimeout(function() {
			$('#searchForm').submit();
		});
	});


	$('#checkBoxAll').change(function (event) {
		if (this.checked) {
			$('#qsoList tbody tr').each(function (i) {
				selectQsoID($(this).data('qsoID'))
			});
		} else {
			$('#qsoList tbody tr').each(function (i) {
				unselectQsoID($(this).data('qsoID'))
			});
		}
	});

	$('#searchForm').submit();

});

function handleQsl(sent, method, tag) {
	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: 'You need to select a least 1 row!',
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	$('#'+tag).prop("disabled", true);
	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
	});
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
	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: 'You need to select a least 1 row!',
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	$('#'+tag).prop("disabled", true);
	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
	});
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


function printlabel() {
	let id_list=[];
	let elements = $('#qsoList tbody input:checked');
	let nElements = elements.length;
	let markchecked = $('#markprinted')[0].checked;

	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
	});
	$.ajax({
		url: base_url + 'index.php/labels/printids',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
				'startat': $('#startat').val(),
				'grid': $('#gridlabel')[0].checked,
				'via': $('#via')[0].checked,
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
				title: 'ERROR',
				message: 'Something went wrong with label print. Go to labels and check if you have defined a label, and that it is set for print!',
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
	$.ajax({
		url: base_url + 'index.php/logbookadvanced/setUserOptions',
		type: 'post',
		data: {
			datetime: $('input[name="datetime"]').is(':checked') ? true : false,
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
			qslmsg: $('input[name="qslmsg"]').is(':checked') ? true : false,
			dxcc: $('input[name="dxcc"]').is(':checked') ? true : false,
			state: $('input[name="state"]').is(':checked') ? true : false,
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
			continent: $('input[name="continent"]').is(':checked') ? true : false,
			qrz: $('input[name="qrz"]').is(':checked') ? true : false,
			gridsquare_layer: $('input[name="gridsquareoverlay"]').is(':checked') ? true : false,
			path_lines: $('input[name="pathlines"]').is(':checked') ? true : false,
			cqzone_layer: $('input[name="cqzones"]').is(':checked') ? true : false,
			ituzone_layer: $('input[name="ituzones"]').is(':checked') ? true : false,
			nightshadow_layer: $('input[name="nightshadow"]').is(':checked') ? true : false,
		},
		success: function(data) {
			$('#saveButton').prop("disabled", false);
			$('#closeButton').prop("disabled", false);
		},
		error: function() {
			$('#saveButton').prop("disabled", false);
		},
	});
}
