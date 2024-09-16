$(document).ready(function () {
	init_datatable();

	$(document).on('click', '.editCron', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editCronDialog(e);
	});

	$(document).on('click', '.enableCronSwitch', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		toggleEnableCronSwitch(e.currentTarget.id, this);
	});
});

function copyCron(id) {
	var content = $('#' + id).text();

	navigator.clipboard.writeText(content).then(function () { });

	$('#' + id).addClass('flash-copy').delay('1000').queue(function () {
		$('#' + id).removeClass('flash-copy').dequeue();
	});
}

function init_expression_tooltips() {
	$('.crontable tbody tr').each(function () {
		var expression = $(this).find('td:eq(3)').text().trim();

		var humanReadable = cronstrue.toString(expression);

		$(this).find('#humanreadable_tooltip').attr('data-bs-original-title', humanReadable).tooltip();
	});
}

function init_datatable() {
	$('.crontable').DataTable({
		"pageLength": 25,
		responsive: true,
		ordering: true,
		"scrollY": "600px",
		"scrollCollapse": true,
		"paging": false,
		"scrollX": true,
		"autoWidth": false,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		buttons: [
			{
				text: lang_general_refresh_list,
				action: function (e, dt, node, config) {
					reloadCrons();
				}
			}
		]
	});
	init_expression_tooltips();
}

function modalEventListener() {
	$('#edit_cron_expression_custom').on('input change', function (e) {
		humanReadableInEditDialog()
	});

	$('#edit_cron_expression_dropdown').change(function () {
		humanReadableInEditDialog()
	});
}

function displayMessages(category, message) {
	var html_class;
	var message_area = $('#cron_message_area');

	if (category == 'success') {
		html_class = 'alert alert-success';
	} else if (category == 'warning') {
		html_class = 'alert alert-warning';
	} else if (category == 'error') {
		html_class = 'alert alert-danger';
	} else {
		html_class = 'alert alert-info';
	}

	message_area.show();
	message_area.addClass(html_class);
	message_area.text(message);

	setTimeout(function () {
		message_area.fadeOut();
	}, 7000);
}

function editCronDialog(e) {
	$('#editCronModal').remove();

	$.ajax({
		url: base_url + 'index.php/cron/editDialog',
		type: 'post',
		data: {
			id: e.currentTarget.id,
		},
		success: function (data) {
			$('body').append(data);

			var editCronModal = new bootstrap.Modal(document.getElementById('editCronModal'));
			editCronModal.show();
			modalEventListener();
			$('[data-bs-toggle="tooltip"]').tooltip();
		},
		error: function (data) {

		},
	});
	return false;
}

function editCron() {
	var $cron_id = $('#edit_cron_id').val();
	var $cron_description = $('#edit_cron_description').val();
	var $cron_expression = $('#edit_cron_expression_custom').val();
	var $cron_enabled = $('#edit_' + $cron_id).is(':checked') ? 'true' : 'false';

	$.ajax({
		url: base_url + 'index.php/cron/edit',
		method: 'POST',
		data: {
			cron_id: $cron_id,
			cron_description: $cron_description,
			cron_expression: $cron_expression,
			cron_enabled: $cron_enabled
		},
		success: function (response) {
			if (response.success) {
				reloadCrons();
				displayMessages(response.messagecategory, response.message);
			} else {
				displayMessages(response.messagecategory, response.message);
			}
		},
		error: function (response) {
			displayMessages('error', 'The query failed for a unknown reason');
		}
	});

}

function humanReadableInEditDialog() {
	var exp_inputID = $('#edit_cron_expression_custom');
	var exp_dropdownID = $('#edit_cron_expression_dropdown');
	var exp_humanreadableID = $('#exp_humanreadable');
	var humanReadable = '';

	exp_inputID.on('input', function () {
		exp_dropdownID.val('');
	});

	if (exp_dropdownID.val() == '') {
		exp_humanreadableID.show();

		try {
			humanReadable = cronstrue.toString(exp_inputID.val());
		} catch (error) {
			humanReadable = 'waiting for complete expression...';
		}

		exp_humanreadableID.text(humanReadable);
	} else {
		exp_humanreadableID.hide();

		exp_inputID.val(exp_dropdownID.val());
	}
}


function toggleEnableCronSwitch(id, thisvar) {
	$.ajax({
		url: base_url + 'index.php/cron/toogleEnableCronSwitch',
		type: 'post',
		data: {
			id: id,
			checked: $(thisvar).is(':checked')
		},
		success: function (data) {
			reloadCrons();
		},
		error: function (data) {
		},
	});
	return false;
}

function reloadCrons() {
	$.ajax({
		url: base_url + 'index.php/cron/fetchCrons',
		type: 'post',
		dataType: 'json',
		success: function (data) {
			loadCronTable(data);
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

function loadCronTable(rows) {
	var uninitialized = $('.crontable').filter(function () {
		return !$.fn.DataTable.fnIsDataTable(this);
	});

	uninitialized.each(function () {
		init_datatable();
	});

	var table = $('.crontable').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let cron = rows[i];

		var data = [];
		data.push(cron.cron_id);
		data.push(cron.cron_description);
		data.push(cron.cron_status);
		data.push(cron.cron_expression);
		data.push(cron.cron_last_run);
		data.push(cron.cron_next_run);
		data.push(cron.cron_edit);
		data.push(cron.cron_enabled);

		let createdRow = table.row.add(data).index();
	}
	table.draw();
	init_expression_tooltips();
}

