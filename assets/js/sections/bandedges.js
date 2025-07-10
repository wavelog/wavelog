function editBandEdge(id) {
	$('.addnewrowbutton').prop("disabled", true);

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
	selectHtml += '<option value="phone"' + (currentMode === 'phone' ? ' selected' : '') + '>Phone</option>';
	selectHtml += '<option value="cw"' + (currentMode === 'cw' ? ' selected' : '') + '>CW</option>';
	selectHtml += '<option value="digi"' + (currentMode === 'digi' ? ' selected' : '') + '>Digi</option>';
	selectHtml += '</select>';

	// Replace the cell content with the select
	$("#mode_" + id).html(selectHtml);

	$('#frequencyfrom_' + id).focus();
}

function saveChanges(id) {
	$('.addnewrowbutton').prop("disabled", false);
	var frequencyfrom = $('#frequencyfrom_'+id).first().closest('td').html();
	var frequencyto = $('#frequencyto_'+id).first().closest('td').html();
	var mode = $('#mode_select_'+id).val();

	if (!$.isNumeric(frequencyfrom) || !$.isNumeric(frequencyto)) {
		BootstrapDialog.alert({
			title: 'Error',
			message: lang_edge_invalid_number,
			type: BootstrapDialog.TYPE_DANGER,
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
			title: 'Error',
			message: lang_edge_from_gt_to,
			type: BootstrapDialog.TYPE_DANGER,
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
		url: base_url + 'index.php/band/saveBandEdge',
		type: 'post',
		data: {
			'id': id,
			'frequencyfrom': frequencyfrom,
			'frequencyto': frequencyto,
			'mode': mode,
		},
		success: function (data) {
			response=JSON.parse(data);
			console.log(response);
			if ((response.message ?? '') !== 'OK') {
				BootstrapDialog.alert({
					title: 'Error',
					message: lang_edge_overlap,
					type: BootstrapDialog.TYPE_DANGER,
					closable: true,
					draggable: true,
					btnOKClass: 'btn-info',
					callback: function (result) {
						location.reload();
					}
				});
			}
		}
	});
	restoreLine(id);
}

function cancelChanges(id) {
	$('.addnewrowbutton').prop("disabled", false);
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

function deleteBandEdge(id) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: lang_edge_remove,
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

function addBandEdgeRow() {
	$('.addnewrowbutton').prop("disabled", true);
    // Prevent multiple add rows
    if ($('.bandtable tbody tr.add-row').length) return;

    var newRow = `
        <tr class="add-row">
            <td><input type="text" class="form-control form-control-sm" id="new_frequencyfrom"></td>
            <td><input type="text" class="form-control form-control-sm" id="new_frequencyto"></td>
            <td>
                <select id="new_mode" class="form-control form-control-sm">
                    <option value="phone">Phone</option>
                    <option value="cw">CW</option>
                    <option value="digi">Digi</option>
                </select>
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-success" onclick="saveNewBandEdgeRow()">Save</button>
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-danger" onclick="cancelNewBandEdgeRow()">Cancel</button>
            </td>
        </tr>
    `;
    $('.bandtable tbody').prepend(newRow);
    $('#bandtable').DataTable().table().container().getElementsByClassName('dt-scroll-body')[0].scrollTop = 0;
}

function saveNewBandEdgeRow() {
	var frequencyfrom = $('#new_frequencyfrom').val();
	var frequencyto = $('#new_frequencyto').val();
	var mode = $('#new_mode').val();

	if (!$.isNumeric(frequencyfrom) || !$.isNumeric(frequencyto)) {
		BootstrapDialog.alert({
			title: 'Error',
			message: lang_edge_invalid_number,
			type: BootstrapDialog.TYPE_DANGER,
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
			title: 'Error',
			message: lang_edge_from_gt_to,
			type: BootstrapDialog.TYPE_DANGER,
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
		url: base_url + 'index.php/band/saveBandEdge',
		type: 'post',
		data: {
			'frequencyfrom': frequencyfrom,
			'frequencyto': frequencyto,
			'mode': mode,
		},
		success: function (data) {
			response=JSON.parse(data);
			console.log(response);
			if ((response.message ?? '') !== 'OK') {
				BootstrapDialog.alert({
					title: 'Error',
					message: lang_edge_overlap,
					type: BootstrapDialog.TYPE_DANGER,
					closable: true,
					draggable: true,
					btnOKClass: 'btn-info',
					callback: function (result) {
						location.reload();
					}
				});
			} else {
				location.reload();
			}
		}
	});
}

function cancelNewBandEdgeRow() {
    $('.bandtable tbody tr.add-row').remove();
	$('.addnewrowbutton').prop("disabled", false);
}
