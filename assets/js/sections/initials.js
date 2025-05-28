function showinitials() {
	var data = {
        band: $('#band').val(),
        mode: $('#mode').val()
    };

	$.ajax({
		url: base_url + "index.php/statistics/getInitials",
		type: "post",
		data: data,
		success: function (html) {
			$(".resulttable").empty();
			$(".resulttable").html(html);
			$(".intialstable").DataTable({
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
		},
	});
}
