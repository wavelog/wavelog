$(document).ready(function () {
	init_datatable()
	init_expression_tooltips();

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
                text: 'Refresh',
                action: function (e, dt, node, config) {
                    reloadCrons();
                }
            }
        ]
    });
}

function editCronDialog(e) {
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
		},
		error: function (data) {

		},
	});
	return false;
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




























// const SPECIAL_EXPRESSIONS = {
//     '@yearly': '0 0 1 1 *',
//     '@annualy': '0 0 1 1 *',
//     '@monthly': '0 0 1 * *',
//     '@weekly': '0 0 * * 0',
//     '@daily': '0 0 * * *',
//     '@midnight': '0 0 * * *',
//     '@hourly': '0 * * * *'
// };

// function convSpecialExpression(expression) {
//     if (expression.startsWith('@')) {
//         const specialExpression = SPECIAL_EXPRESSIONS[expression];
//         if (specialExpression) {
//             return specialExpression;
//         } else {
//             throw new Error('Unknown special expression');
//         }
//     } else {
//         return expression;
//     }
// }