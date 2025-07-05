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
		}
	});
}
