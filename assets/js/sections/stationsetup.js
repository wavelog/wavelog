$(document).ready(function () {

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
				} else {
					$("#flashdata").html(jdata.flashdata);
				}
			},
			error: function(e) {
				$("#flashdata").html("An unknown Error occured");
			}
		});
		
	});

	$("#station_logbooks_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});
});

function setActiveStationLocation() {

}

function setActiveStationLogbook() {

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
