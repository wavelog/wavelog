$(document).ready(function () {
	// empty yet
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
