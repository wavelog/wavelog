$(document).ready(function () {
	$('.sattable').DataTable({
		"pageLength": 25,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		responsive: false,
		ordering: false,
		"scrollY": "500px",
		"scrollCollapse": true,
		"paging": false,
		"scrollX": true,
		"language": {
			url: getDataTablesLanguageUrl(),
		}
	});
});

function createSatelliteDialog() {
	$.ajax({
		url: base_url + 'index.php/satellite/create',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create satellite',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'create-band-dialog',
				nl2br: false,
				message: html,
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

function createSatellite(form) {
	$(".alert").remove();
	if (form.nameInput.value == "") {
		$('#create_satellite').prepend('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Please enter a name!</div>');
	}
	else {
		$.ajax({
			url: base_url + 'index.php/satellite/createSatellite',
			type: 'post',
			data: {
				'name': form.nameInput.value,
				'exportname': form.exportNameInput.value,
				'orbit': form.orbit.value,
				'modename': form.mode.value,
				'uplinkmode': form.uplinkMode.value,
				'uplinkfrequency': form.uplinkFrequency.value,
				'downlinkmode': form.downlinkMode.value,
				'downlinkfrequency': form.downlinkFrequency.value,
			},
			success: function (html) {
				location.reload();
			}
		});
	}
}

function editSatelliteDialog(id) {
	$.ajax({
		url: base_url + 'index.php/satellite/edit',
		type: 'post',
		data: {
			'id': id
		},
		success: function (html) {
			BootstrapDialog.show({
				title: 'Edit satellite',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'edit-band-dialog',
				nl2br: false,
				message: html,
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

function saveUpdatedSatellite(form) {
	$(".alert").remove();
	if (form.nameInput.value == "") {
		$('#edit_satellite_dialog').prepend('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Please enter a name!</div>');
	}
	else {
		$.ajax({
			url: base_url + 'index.php/satellite/saveupdatedsatellite',
			type: 'post',
			data: {'id': form.id.value,
					'name': form.nameInput.value,
					'exportname': form.exportNameInput.value,
					'orbit': form.orbit.value,
			},
			success: function (html) {
				location.reload();
			}
		});
	}
}

function deleteSatellite(id, satellite) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: 'Do you really want to delete ' + satellite + '?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger btn-sm',
		btnCancelClass: 'btn-secondary btn-sm',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/satellite/delete',
					type: 'post',
					data: {
						'id': id
					},
					success: function (data) {
						$(".satellite_" + id).parent("tr:first").remove(); // removes satellite from table
					}
				});
			}
		}
	});
}

function editSatmode(id) {

    $(".satmode_" + id).find("#deleteButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="cancelButton">' + '<button type="button" class="btn btn-sm btn-danger" onclick="cancelChanges(' + id + ');' + '">Cancel</button>' + '</td>'
    );

	$(".satmode_" + id).find("#editButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="saveButton">' + '<button type="button" class="btn btn-sm btn-success" onclick="saveChanges(' + id + ');' + '">Save</button>' + '</td>'
    );

	var tbl_row = $(".satmode_" + id).closest('tr');
	tbl_row.find('.row_data')
	.attr('contenteditable', 'true')
	.addClass('bg-danger');

	tbl_row.find('.row_data').each(function(index, val)
	{
		$(this).attr('original_entry', $(this).html());
	});

	$('#modename_' + id).focus();
}

function saveChanges(id) {
	$.ajax({
		url: base_url + 'index.php/satellite/saveSatModeChanges',
		type: 'post',
		data: {
			'id': id,
			'name': $('#modename_'+id).first().closest('td').html(),
			'uplink_mode': $('#uplink_mode_'+id).first().closest('td').html(),
			'uplink_freq': $('#uplink_freq_'+id).first().closest('td').html(),
			'downlink_mode': $('#downlink_mode_'+id).first().closest('td').html(),
			'downlink_freq': $('#downlink_freq_'+id).first().closest('td').html(),
		},
		success: function (data) {

		}
	});

    restoreLine(id);
}

function cancelChanges(id) {
	var tbl_row = $(".satmode_" + id).closest('tr');
	tbl_row.find('.row_data').each(function(index, val)
	{
		$(this).html( $(this).attr('original_entry') );
	});

	restoreLine(id);
}

function restoreLine(id) {
	var tbl_row = $(".satmode_" + id).closest('tr');
	tbl_row.find('.row_data')
	.attr('contenteditable', 'false')
	.removeClass('bg-danger');

    $(".satmode_" + id).find("#cancelButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="deleteButton">' + '<button type="button" class="btn btn-sm btn-danger" onclick="deleteSatmode(' + id + ');' + '"><i class="fas fa-trash-alt"></i></button>' + '</td>'
    );

	$(".satmode_" + id).find("#saveButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="editButton">' + '<button type="button" class="btn btn-sm btn-success" onclick="editSatmode(' + id + ');' + '"><i class="fas fa-edit"></i></button>' + '</td>'
    );
}

function deleteSatmode(id) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: 'Do you really want to delete this mode?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger btn-sm',
		btnCancelClass: 'btn-secondary btn-sm',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/satellite/deleteSatMode',
					type: 'post',
					data: {
						'id': id
					},
					success: function (data) {
						$(".satmode_" + id).remove(); // removes satellite from table
					}
				});
			}
		}
	});
}
