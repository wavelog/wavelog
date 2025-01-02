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

	$(document).on('click','.deleteSatmode', function (e) {
		deleteSatmode(e.currentTarget.id,e.currentTarget.attributes.infotext.value);
	});

	$(document).on('click','.editSatmode', function (e) {
		editSatmode(e.currentTarget.id);
	});
});

	function sanit(text) {
		out = text.replace("\\"," ");
		out = text.replace("\"","'");
		return $("<textarea/>").text(out).html();
	}


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
	if (form.displayNameInput.value == "") {
		$('#create_satellite').prepend('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> Please enter a name!</div>');
	}
	else {
		$.ajax({
			url: base_url + 'index.php/satellite/createSatellite',
			type: 'post',
			data: {
				'name': form.nameInput.value,
				'displayname': form.displayNameInput.value,
				'orbit': form.orbit.value,
				'modename': form.mode.value,
				'uplinkmode': form.uplinkMode.value,
				'uplinkfrequency': form.uplinkFrequency.value,
				'downlinkmode': form.downlinkMode.value,
				'downlinkfrequency': form.downlinkFrequency.value,
				'lotw': form.lotwAccepted.value,
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
						location.reload();
					}
				}]
			});
		}
	});
}

function saveUpdatedSatellite(form) {
	$(".alert").remove();
	if (form.displayNameInput.value == "") {
		$('#edit_satellite_dialog').prepend('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> Please enter a name!</div>');
	}
	else {
		$.ajax({
			url: base_url + 'index.php/satellite/saveupdatedsatellite',
			type: 'post',
			data: {'id': form.id.value,
					'name': form.nameInput.value,
					'displayname': form.displayNameInput.value,
					'lotw': form.lotwAccepted.value,
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
	tbl_row.addClass('editRow');
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
	$('.addsatmode').prop("disabled", false);
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
	$('.addsatmode').prop("disabled", false);
	var tbl_row = $(".satmode_" + id).closest('tr');
	tbl_row.find('.row_data').each(function(index, val)
	{
		$(this).html( $(this).attr('original_entry') );
	});

	restoreLine(id);
}

function restoreLine(id) {
	var tbl_row = $(".satmode_" + id).closest('tr');
	tbl_row.removeClass('editRow');
	tbl_row.find('.row_data')
	.attr('contenteditable', 'false')
	.removeClass('bg-danger');

    $(".satmode_" + id).find("#cancelButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="deleteButton">' + '<button type="button" class="btn btn-sm btn-danger deleteSatmode" infotext id="' + id + '"><i class="fas fa-trash-alt"></i></button>' + '</td>'
    );

	$(".satmode_" + id).find("#saveButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="editButton">' + '<button type="button" class="btn btn-sm btn-success editSatmode" id="' + id + '"><i class="fas fa-edit"></i></button>' + '</td>'
    );
}

function deleteSatmode(id, satmode) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: 'Do you really want to delete the mode ' + satmode +'?',
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

function addSatMode() {
	$('.addsatmode').prop("disabled", true)
	$('.satmodetable tbody').append($('<tr class="editRow">')
		.append($('<td class="row_data" style="text-align: center; vertical-align: middle;">').append("").attr('contenteditable', 'true').addClass('bg-danger'))
		.append($('<td class="row_data" style="text-align: center; vertical-align: middle;">').append("").attr('contenteditable', 'true').addClass('bg-danger'))
		.append($('<td class="row_data" style="text-align: center; vertical-align: middle;">').append("").attr('contenteditable', 'true').addClass('bg-danger'))
		.append($('<td class="row_data" style="text-align: center; vertical-align: middle;">').append("").attr('contenteditable', 'true').addClass('bg-danger'))
		.append($('<td class="row_data" style="text-align: center; vertical-align: middle;">').append("").attr('contenteditable', 'true').addClass('bg-danger'))
		.append($('<td id="saveButton" style="text-align: center; vertical-align: middle;">').append('<button type="button" class="btn btn-sm btn-success savenewline">Save</button>'))
		.append($('<td id="cancelButton" style="text-align: center; vertical-align: middle;">').append('<button type="button" class="btn btn-sm btn-danger cancelnewline">Cancel</button>'))
	)
	$('.satmodetable tr:last-child td:first-child').focus();

	$(".cancelnewline").click(function() {
		$(this).closest("tr").remove();
		$('.addsatmode').prop("disabled", false);
	});

	$(".savenewline").click(function() {
		$('.addsatmode').prop("disabled", false);
		var modename = $(this).closest("tr").find('td:eq(0)');
		var uplink_mode = $(this).closest("tr").find('td:eq(1)');
		var uplink_freq = $(this).closest("tr").find('td:eq(2)');
		var downlink_mode = $(this).closest("tr").find('td:eq(3)');
		var downlink_freq = $(this).closest("tr").find('td:eq(4)');
		var id = $('#satelliteid').val();

		var tempthis = this;
		$.ajax({
			url: base_url + 'index.php/satellite/addSatMode',
			type: 'post',
			data: {
				'id': id,
				'name': modename.html(),
				'uplink_mode': uplink_mode.html(),
				'uplink_freq': uplink_freq.html(),
				'downlink_mode': downlink_mode.html(),
				'downlink_freq': downlink_freq.html(),
			},
			success: function (data) {
				var tbl_row = $(tempthis).closest('tr');
				tbl_row.addClass('satmode_'+data.inserted_id);
				tbl_row.removeClass('editRow');

				modename.attr('id','modename_'+data.inserted_id);
				uplink_mode.attr('id','uplink_mode_'+data.inserted_id);
				uplink_freq.attr('id','uplink_freq_'+data.inserted_id);
				downlink_mode.attr('id','downlink_mode_'+data.inserted_id);
				downlink_freq.attr('id','downlink_freq_'+data.inserted_id);

				tbl_row.find('.row_data')
				.attr('contenteditable', 'false')
				.removeClass('bg-danger');
				tbl_row.find("#cancelButton").replaceWith(
					'<td style="text-align: center; vertical-align: middle;" id="deleteButton">' + '<button type="button" class="btn btn-sm btn-danger deleteSatmode" id="'+data.inserted_id+'" infotext="'+sanit(modename.html())+'"><i class="fas fa-trash-alt"></i></button></td>'
				);

				tbl_row.find("#saveButton").replaceWith(
					'<td style="text-align: center; vertical-align: middle;" id="editButton">' + '<button type="button" class="btn btn-sm btn-success editSatmode" id="'+data.inserted_id+'"><i class="fas fa-edit"></i></button></td>'
				);
			}
		});
	});
}
