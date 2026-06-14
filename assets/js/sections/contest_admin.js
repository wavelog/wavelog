$('.contesttable').DataTable({
	"pageLength": 25,
	responsive: false,
	ordering: false,
	"scrollY": "600px",
	"scrollCollapse": true,
	"paging": false,
	"scrollX": true,
	"language": {
		url: getDataTablesLanguageUrl(),
	},
	dom: 'Bfrtip',
	buttons: [
		{
			extend: 'csv',
			className: 'mb-1 btn btn-primary',
			init: function(api, node) {
				$(node).removeClass('dt-button').addClass('btn btn-primary');
			},
			exportOptions: {
				columns: [0, 1, 2]
			}
		}
	]
});

// adjust CSV button color when dark theme is active
var contestAdminBackground = $('body').css('background-color');
if (contestAdminBackground !== 'rgb(255, 255, 255)') {
	$('.buttons-csv').css('color', 'white');
}

function createContestDialog() {
	$.ajax({
		url: base_url + 'index.php/contest_admin/create',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: lang_admin_contest_add_contest,
				size: BootstrapDialog.SIZE_WIDE,
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

function createContest(form) {
	$(".alert").remove();
	var contestname = form.contestname.value.trim();
	var adifcontestname = form.adifcontestname.value.trim();

	if (contestname === '' || adifcontestname === '') {
		$('#create_contest').prepend('<div class="alert alert-danger" role="alert">' + lang_contest_provide_name + '</div>');
		return;
	}

	$.ajax({
		url: base_url + 'index.php/contest_admin/create',
		type: 'post',
		data: {
			'name': contestname,
			'adifname': adifcontestname
		},
		success: function () {
			location.reload();
		}
	});
}

function deactivateContest(contestid) {
	$.ajax({
		url: base_url + 'index.php/contest_admin/deactivate',
		type: 'post',
		data: { 'id': contestid },
		success: function () {
			$('.contest_' + contestid).text(lang_admin_contest_menu_n_active);
			$('.btn_' + contestid)
				.html(lang_admin_contest_menu_activate)
				.removeClass('btn-secondary')
				.addClass('btn-primary')
				.attr('onclick', 'activateContest(' + contestid + ')');
		}
	});
}

function activateContest(contestid) {
	$.ajax({
		url: base_url + 'index.php/contest_admin/activate',
		type: 'post',
		data: { 'id': contestid },
		success: function () {
			$('.contest_' + contestid).text(lang_admin_contest_menu_active);
			$('.btn_' + contestid)
				.html(lang_admin_contest_menu_deactivate)
				.removeClass('btn-primary')
				.addClass('btn-secondary')
				.attr('onclick', 'deactivateContest(' + contestid + ')');
		}
	});
}

function deleteContest(id, name) {
	BootstrapDialog.confirm({
		title: lang_admin_danger,
		message: lang_admin_contest_deletion_warning + name + '?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/contest_admin/delete',
					type: 'post',
					data: { 'id': id },
					success: function () {
						$('.contest_' + id).parent('tr:first').remove();
					}
				});
			}
		}
	});
}

function activateAllContests() {
	BootstrapDialog.confirm({
		title: lang_admin_danger,
		message: lang_admin_contest_active_all_warning,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/contest_admin/activateall',
					type: 'post',
					success: function () {
						location.reload();
					}
				});
			}
		}
	});
}

function deactivateAllContests() {
	BootstrapDialog.confirm({
		title: lang_admin_danger,
		message: lang_admin_contest_deactive_all_warning,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + 'index.php/contest_admin/deactivateall',
					type: 'post',
					success: function () {
						location.reload();
					}
				});
			}
		}
	});
}
