$('.modetable').DataTable({
	"pageLength": 25,
	responsive: false,
	ordering: false,
	"scrollY": "500px",
	"scrollCollapse": true,
	"paging": false,
	"scrollX": true,
	"language": {
		url: getDataTablesLanguageUrl(),
	},
	initComplete: function () {
		this.api()
			.columns('.select-filter')
			.every(function () {
				var column = this;
				var select = $('<select class="form-select"><option value=""></option></select>')
					.appendTo($(column.footer()).empty())
					.on('change', function () {
						var val = $.fn.dataTable.util.escapeRegex($(this).val());

						column.search(val ? '^' + val + '$' : '', true, false).draw();
					});

				column
					.data()
					.unique()
					.sort()
					.each(function (d, j) {
						select.append('<option value="' + d + '">' + d + '</option>');
					});
			});
	},
});

function createModeDialog() {
	$.ajax({
		url: base_url + 'index.php/mode/create',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: lang_create_mode,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'create-mode-dialog',
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

function createMode(form) {
	if (form.mode.value != '') {
		$.ajax({
			url: base_url + 'index.php/mode/create',
			type: 'post',
			data: {
				'mode': form.mode.value,
				'submode': form.submode.value,
				'qrgmode': form.qrgmode.value,
				'active': form.active.value
			},
			success: function (html) {
				location.reload();
			}
		});
	}
}

function deactivateMode(modeid) {
	$.ajax({
		url: base_url + 'index.php/mode/deactivate',
		type: 'post',
		data: { 'id': modeid },
		success: function (html) {
			$(".mode_" + modeid).text(lang_mode_not_active);
			$('.btn_' + modeid).html(lang_activate_mode);
			$('.btn_' + modeid).removeClass('btn-secondary');
			$('.btn_' + modeid).addClass('btn-primary');
			$('.btn_' + modeid).attr('onclick', 'activateMode(' + modeid + ')')
		}
	});
}

function activateMode(modeid) {
	$.ajax({
		url: base_url + 'index.php/mode/activate',
		type: 'post',
		data: { 'id': modeid },
		success: function (html) {
			$('.mode_' + modeid).text(lang_mode_active);
			$('.btn_' + modeid).html(lang_deactivate_mode);
			$('.btn_' + modeid).removeClass('btn-primary');
			$('.btn_' + modeid).addClass('btn-secondary');
			$('.btn_' + modeid).attr('onclick', 'deactivateMode(' + modeid + ')')
		}
	});
}

function deleteMode(id, mode) {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: lang_mode_deletion_confirm + ' ' + mode,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/mode/delete',
					type: 'post',
					data: {
						'id': id
					},
					success: function (data) {
						$(".mode_" + id).parent("tr:first").remove(); // removes mode from table
					}
				});
			}
		}
	});
}

function activateAllModes() {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: lang_active_all_confirm,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/mode/activateall',
					type: 'post',
					success: function (data) {
						location.reload();
					}
				});
			}
		}
	});
}

function deactivateAllModes() {
	BootstrapDialog.confirm({
		title: lang_general_word_danger,
		message: lang_deactive_all_confirm,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/mode/deactivateall',
					type: 'post',
					success: function (data) {
						location.reload();
					}
				});
			}
		}
	});
}
