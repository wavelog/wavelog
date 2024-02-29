$(document).ready(function () {
	$('.sattable').DataTable({
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
});
