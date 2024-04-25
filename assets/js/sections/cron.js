$(document).ready(function () {
	$('.crontable tbody tr').each(function(){
        var expression = $(this).find('td:eq(2)').text().trim();
        var humanReadable = cronstrue.toString(expression);

        $(this).find('#humanreadable_tooltip').attr('data-bs-original-title', humanReadable).tooltip();
    });
	
	$(document).on('click', '.editCron', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editCron(e);
	});
});

function copyCron(id) {
    var content = $('#' + id).text();

    navigator.clipboard.writeText(content).then(function () {});

    $('#' + id).addClass('flash-copy').delay('1000').queue(function () {
        $('#' + id).removeClass('flash-copy').dequeue();
    });
}

function convert_expression(expression) {
	var string;
	if (expression.startsWith('@')) {
		string = expression;
	} else {
		string = cronstrue.toString(expression);
	}
	return string;
}

function set_tooltip_title(id, expression) {
    var string = convert_expression(expression);
    $('#tooltip_' + id).attr('title', string);
	$('[data-bs-toggle="tooltip"]').tooltip();
}

$('.crontable').DataTable({
	"pageLength": 25,
	"language": {
		url: getDataTablesLanguageUrl(),
	},
	responsive: true,
	ordering: true,
	"scrollY": "600px",
	"scrollCollapse": true,
	"paging": false,
	"scrollX": true,
	"autoWidth": false,
	"language": {
		url: getDataTablesLanguageUrl(),
	}
});

function editCron(e) {
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