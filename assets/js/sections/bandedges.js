function editBandEdge(id) {

    $(".bandedge_" + id).find("#deleteButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="cancelButton">' + '<button type="button" class="btn btn-sm btn-danger" onclick="cancelChanges(' + id + ');' + '">Cancel</button>' + '</td>'
    );

	$(".bandedge_" + id).find("#editButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="saveButton">' + '<button type="button" class="btn btn-sm btn-success" onclick="saveChanges(' + id + ');' + '">Save</button>' + '</td>'
    );

	// Get the current mode value from the cell
	var currentMode = $("#mode_" + id).text().trim().toLowerCase();

	var tbl_row = $(".bandedge_" + id).closest('tr');
	tbl_row.addClass('editRow');
	tbl_row.find('.row_data')
	.attr('contenteditable', 'true')
	.addClass('bg-danger');

	tbl_row.find('.row_data').each(function(index, val)
	{
		$(this).attr('original_entry', $(this).html());
	});

	// Build the select with the current mode selected
	var selectHtml = '<select id="mode_select_' + id + '" style="text-align-last: center;" class="d-inline-block w-auto text-center form-control form-control-sm">';
	selectHtml += '<option value="phone"' + (currentMode === 'phone' ? ' selected' : '') + '>phone</option>';
	selectHtml += '<option value="cw"' + (currentMode === 'cw' ? ' selected' : '') + '>cw</option>';
	selectHtml += '<option value="digi"' + (currentMode === 'digi' ? ' selected' : '') + '>digi</option>';
	selectHtml += '</select>';

	// Replace the cell content with the select
	$("#mode_" + id).html(selectHtml);

	$('#frequencyfrom_' + id).focus();
}

function saveChanges(id) {
	$('.addsatmode').prop("disabled", false);
	var frequencyfrom = $('#frequencyfrom_'+id).first().closest('td').html();
	var frequencyto = $('#frequencyto_'+id).first().closest('td').html();
	var mode = $('#mode_select_'+id).val();

	if (!$.isNumeric(frequencyfrom) || !$.isNumeric(frequencyto)) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: "Please enter valid numbers for frequency.",
			type: BootstrapDialog.TYPE_INFO,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-info',
			callback: function (result) {
				// Callback function after the dialog is closed
			}
		});
		return;
	}
	if (frequencyfrom >= frequencyto) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: "The 'From' frequency must be less than the 'To' frequency.",
			type: BootstrapDialog.TYPE_INFO,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-info',
			callback: function (result) {
				// Callback function after the dialog is closed
			}
		});
		return;
	}

	$.ajax({
		url: base_url + 'index.php/band/saveBandEdgeChanges',
		type: 'post',
		data: {
			'id': id,
			'frequencyfrom': frequencyfrom,
			'frequencyto': frequencyto,
			'mode': mode,
		},
		success: function (data) {

		}
	});

    restoreLine(id);
}

function cancelChanges(id) {
	$('.addsatmode').prop("disabled", false);
	var tbl_row = $(".bandedge_" + id).closest('tr');
	tbl_row.find('.row_data').each(function(index, val)
	{
		$(this).html( $(this).attr('original_entry') );
	});

	restoreLine(id);
}

function restoreLine(id) {
	var tbl_row = $(".bandedge_" + id).closest('tr');
	tbl_row.removeClass('editRow');
	tbl_row.find('.row_data')
	.attr('contenteditable', 'false')
	.removeClass('bg-danger');

    $(".bandedge_" + id).find("#cancelButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="deleteButton">' + '<button onclick="deleteBandEdge(' + id + ')" class="btn btn-sm btn-danger deleteBandEdge" infotext id="' + id + '"><i class="fas fa-trash-alt"></i></button>' + '</td>'
    );

	$(".bandedge_" + id).find("#saveButton").replaceWith(
        '<td style="text-align: center; vertical-align: middle;" id="editButton">' + '<button onclick="editBandEdge(' + id + ')" type="button" class="btn btn-sm btn-success editBandEdge" id="' + id + '"><i class="fas fa-edit"></i></button>' + '</td>'
    );
	$("#mode_" + id).html($("#mode_select_" + id).val());
}

function saveUpdatedBandEdge(form) {
	$(".alert").remove();
	if (form.band.value == "") {
		$('#edit_band_dialog').prepend('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Please enter a band!</div>');
	}
	else {
		$.ajax({
			url: base_url + 'index.php/band/saveupdatedbandedge',
			type: 'post',
			data: {'id': form.id.value,
				'band': form.band.value,
				'bandgroup': form.bandgroup.value,
				'ssbqrg': form.ssbqrg.value,
				'dataqrg': form.dataqrg.value,
				'cwqrg': form.cwqrg.value
			},
			success: function (html) {
				location.reload();
			}
		});
	}
}

function deleteBandEdge(id) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: 'Are you sure you want to delete this band edge?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/band/deletebandedge',
					type: 'post',
					data: {
						'id': id
					},
					success: function (data) {
						$(".bandedge_" + id).remove(); // removes band from table
					}
				});
			}
		}
	});
}
