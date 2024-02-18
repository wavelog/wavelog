function editQsos() {
	var elements = $('#qsoList tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		return;
	}
	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').data('qsoID')
		id_list.push(id);
	});

	$('#editButton').prop("disabled", true);

	$.ajax({
		url: base_url + 'index.php/logbookadvanced/editDialog',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Batch edit for QSOs',
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'options',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					$('#editDxcc').html($('#dxcc').html());
					$('#editDxcc option[value=""]').remove();

					$('#editIota').html($('#iota').html());

					$('#editPropagation').html($('#selectPropagation').html());
					$('#editPropagation option[value=""]').remove();
					var option = $('<option>');
					option.val('').text('-');
					$('#editPropagation').prepend(option);
					$('#editPropagation').val('').trigger('chosen:updated');

					$('#editColumn').change(function(){
						var type = $('#editColumn').val();
						changeEditType(type);
					});
				},
				buttons: [{
					label: 'Save',
					cssClass: 'btn-primary btn-sm',
					id: 'saveButton',
					action: function (dialogItself) {
						saveBatchEditQsos(id_list);
						$('#editButton').prop("disabled", false);
						$('#closeButton').prop("disabled", true);
						dialogItself.close();
					}
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-sm',
					id: 'closeButton',
					action: function (dialogItself) {
						$('#editButton').prop("disabled", false);
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					$('#editButton').prop("disabled", false);
				},
			});
		}
	});
}

function saveBatchEditQsos(id_list) {
	var column = $("#editColumn").val();
	var value;
	if (column == 'cqz') {
		value = $("#editCqz").val();
	}
	if (column == 'dxcc') {
		value = $("#editDxcc").val();
	}
	if (column == 'iota') {
		value = $("#editIota").val();
	}
	if (column == 'was') {
		value = $("#editState").val();
	}
	if (column == 'propagation') {
		value = $("#editPropagation").val();
	}

	$.ajax({
		url: base_url + 'index.php/logbookadvanced/saveBatchEditQsos',
		type: 'post',
		data: {
			ids: JSON.stringify(id_list, null, 2),
			column: column,
			value: value
		},
		success: function (data) {
			if (data != []) {
				$.each(data, function(k, v) {
					updateRow(this);
					unselectQsoID(this.qsoID);
				});
			}
		}
	});
}

function changeEditType(type) {
	if (type == "dxcc") {
		$('#editCqz').hide();
		$('#editIota').hide();
		$('#editDxcc').show();
		$('#editState').hide();
		$('#editPropagation').hide();
	} else if (type == "iota") {
		$('#editCqz').hide();
		$('#editIota').show();
		$('#editDxcc').hide();
		$('#editState').hide();
		$('#editPropagation').hide();
	} else if (type == "vucc" || type == "sota" || type == "wwff") {
		$('#editCqz').hide();
		$('#editIota').hide();
		$('#editDxcc').hide();
		$('#editState').hide();
		$('#editPropagation').hide();
	} else if (type == "cqz") {
		$('#editCqz').show();
		$('#editIota').hide();
		$('#editDxcc').hide();
		$('#editState').hide();
		$('#editPropagation').hide();
	} else if (type == "was") {
		$('#editCqz').hide();
		$('#editIota').hide();
		$('#editDxcc').hide();
		$('#editState').show();
		$('#editPropagation').hide();
	} else if (type == "propagation") {
		$('#editCqz').hide();
		$('#editIota').hide();
		$('#editDxcc').hide();
		$('#editState').hide();
		$('#editPropagation').show();
	}
}
