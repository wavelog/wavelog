$(".activatorstable").DataTable({
	pageLength: 25,
	responsive: false,
	ordering: false,
	scrollY: "500px",
	scrollCollapse: true,
	paging: false,
	scrollX: true,
	language: {
		url: getDataTablesLanguageUrl(),
	},
	dom: "Bfrtip",
	buttons: ["csv"],
});

function showinitials() {
	var data = {
        mode: $('#band').val(),
        band: $('#mode').val()
    };

	$.ajax({
		url: base_url + "index.php/statistics/getInitials",
		type: "post",
		data: data,
		success: function (html) {
			$(".resulttable").empty();
			$(".resulttable").html(html);
			$(".qsotable").DataTable();
		},
	});
}
