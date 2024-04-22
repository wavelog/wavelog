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
	responsive: false,
	ordering: false,
	"scrollY": "500px",
	"scrollCollapse": true,
	"paging": false,
	"scrollX": true,
	"language": {
		url: getDataTablesLanguageUrl(),
	}
});