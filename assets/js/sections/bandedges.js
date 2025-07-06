function editBandEdgeDialog(id) {
	$.ajax({
		url: base_url + 'index.php/band/bandedgedit',
		type: 'post',
		data: {
			'id': id
		},
		success: function (html) {
			BootstrapDialog.show({
				title: lang_options_bands_edit,
				size: BootstrapDialog.SIZE_NORMAL,
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
						$(".bandedge_" + id).parent("tr:first").remove(); // removes band from table
					}
				});
			}
		}
	});
}

function saveBandEdge(id) {
	$.ajax({
		url: base_url + 'index.php/band/saveBandEdge',
		type: 'post',
		data: {'id': id,
			'frequencyfrom': $('#frequencyfrom').val(),
			'frequencyto': $('#frequencyto').val(),
			'mode': $('#mode').val()
		},
		success: function (html) {
		}
	});
}
