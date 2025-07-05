function searchAdditionalQsos(filename) {
	$.ajax({
		url: base_url + 'index.php/qsl/searchQsos',
		type: 'post',
		data: {'callsign': $('#callsign').val(), 'filename': filename},
		success: function(html) {
			$('#searchresult').empty();
			$('#searchresult').append(html);
		}
	});
}

function getConfirmations() {
	$.ajax({
		url: base_url + 'index.php/qsl/searchConfirmations',
		type: 'post',
		data: {'type': $('#confirmationtype').val()},
		success: function(html) {
			$('#searchresult').empty();
			$('#searchresult').append(html);
			$(".confirmationtable").DataTable({
				responsive: false,
				scrollY: "400px",
				scrollCollapse: true,
				paging: false,
				scrollX: true,
				sortable: true,
				language: {
					url: getDataTablesLanguageUrl(),
				},
				dom: "Bfrtip",
				order: [1, 'desc'],
				buttons: [
					{
						extend: 'csv'
					},
					{
						extend: 'clear',
						text: lang_admin_clear
					}
				]
			});
		}
	});
}
