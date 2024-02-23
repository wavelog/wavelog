$(document).ready(function () {

	reloadStations();
	$("#station_locations_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	$(document).on('click','.newLogbook', function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		$.ajax({
			url: base_url + 'index.php/stationsetup/newLogbook_json',
			type: 'post',
			data: {
				'stationLogbook_Name': $("#logbook_name").val(),
			},
			success: function(data) {
				jdata=JSON.parse(data);
				if (jdata.success == 1) {
					$("#NewStationLogbookModal").modal('hide');
					reloadLogbooks();
				} else {
					$("#flashdata").html(jdata.flashdata);
				}
			},
			error: function(e) {
				$("#flashdata").html("An unknown Error occured");
			}
		});

	});

	$(document).on('click','.deleteLogbook', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('deleteLogbook_json','id2delete',reloadLogbooks,e);
	});

	$(document).on('click','.setActiveLogbook', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('setActiveLogbook_json','id2setActive',reloadLogbooks,e);
	});

	$(document).on('click','.setActiveStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('setActiveStation_json','id2setActive',reloadStations,e);
	});

	$(document).on('click','.DeleteStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('DeleteStation_json','id2del',reloadStations,e);
	});

	$(document).on('click','.EmptyStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('EmptyStation_json','id2Empty',reloadStations,e);
	});

	$("#station_logbooks_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});
});

	async function do_ajax(ajax_uri,ajax_field,succ_callback,event_target) {
		if (event_target.currentTarget.attributes.cnftext) {
			if (!(confirm(event_target.currentTarget.attributes.cnftext.value))) {
				return false;
			}
		}
		$.ajax({
			url: base_url + 'index.php/stationsetup/' + ajax_uri,
			type: 'post',
			data: {
				[ajax_field]: event_target.currentTarget.id,
			},
			success: function(data) {
				jdata=JSON.parse(data);
				if (jdata.success == 1) {
					succ_callback();
				} else {
					$("#flashdata").html(jdata.flashdata);
				}
			},
			error: function(e) {
				$("#flashdata").html("An unknown Error occured");
			}
		});
	}


function reloadLogbooks() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/fetchLogbooks',
		type: 'post',
		dataType: 'json',
		success: function (data) {
			loadLogbookTable(data);
		},
		error: function (data) {
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
	return false;
}
function reloadStations() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/fetchLocations',
		type: 'post',
		dataType: 'json',
		success: function (data) {
			loadLocationTable(data);
		},
		error: function (data) {
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
	return false;
}

function setActiveStationLocation() {

}

function createStationLogbook() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/newLogbook',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create a new station logbook',
				size: BootstrapDialog.SIZE_EXTRAWIDE,
				cssClass: 'options',
				id: "NewStationLogbookModal",
				nl2br: false,
				message: html,
				onshown: function(dialog) {
				},
				buttons: [{
					label: 'Save',
					cssClass: 'btn-primary btn-sm newLogbook',
					id: 'saveButtonNewLogbook',
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-sm',
					id: 'closeButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					$('#optionButton').prop("disabled", false);
				},
			});
		}
	});
}

function createStationLocation() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/newLocation',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create a new station location',
				size: BootstrapDialog.SIZE_EXTRAWIDE,
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
						dialogItself.close();
					}
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-sm',
					id: 'closeButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					$('#optionButton').prop("disabled", false);
				},
			});
		}
	});
}

function loadLogbookTable(rows) {
	var uninitialized = $('#station_logbooks_table').filter(function() {
		return !$.fn.DataTable.fnIsDataTable(this);
	});

	uninitialized.each(function() {
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
		});
	});

	var table = $('#station_logbooks_table').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let logbook = rows[i];

		var data = [];
		data.push(logbook.logbook_name);
		data.push(logbook.logbook_state);
		data.push(logbook.logbook_edit);
		data.push(logbook.logbook_delete);
		data.push(logbook.logbook_link);
		data.push(logbook.logbook_publicsearch);

		let createdRow = table.row.add(data).index();
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

function loadLocationTable(rows) {
	var uninitialized = $('#station_locations_table').filter(function() {
		return !$.fn.DataTable.fnIsDataTable(this);
	});

	uninitialized.each(function() {
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
			'columnDefs': [
				{ 'targets':4,	// Col 4 (Badge) needs some special things...
				  'createdCell':  function (td, cellData, rowData, row, col) {
				  			(td).attr('data-order', 1);	// not sure how to add ID dynamic here
						  }
				}
			]
		});
	});

	var table = $('#station_locations_table').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let locations = rows[i];

		var data = [];

		data.push(locations.station_name);
		data.push(locations.station_callsign);
		data.push(locations.station_country);
		data.push(locations.station_gridsquare);
		data.push(locations.station_badge);
		data.push(locations.station_edit);
		data.push(locations.station_copylog);
		data.push(locations.station_emptylog);
		data.push(locations.station_delete);

		let createdRow = table.row.add(data).index();
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

