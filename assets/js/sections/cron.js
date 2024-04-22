function copyCron(id) {
    var content = $('#' + id).text();

    navigator.clipboard.writeText(content).then(function () {});

    $('#' + id).addClass('flash-copy').delay('1000').queue(function () {
        $('#' + id).removeClass('flash-copy').dequeue();
    });
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