$(document).ready(function () {
	$("#station_locations_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	if (window.location.pathname.indexOf("/station/edit") !== -1 || window.location.pathname.indexOf("/station/create") !== -1 || window.location.pathname.indexOf("/station/copy") !== -1) {
		selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');
		updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
		$("#dxcc_id").change(function () {
			updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
		});

		$('#qrz_apitest_btn').click(function(){

			var apikey = $('#qrzApiKey').val();
			var msg_div = $('#qrz_apitest_msg');

			msg_div.hide();
			msg_div.removeClass('alert-success alert-danger')

			$.ajax({
				url: base_url+'index.php/qrz/qrz_apitest',
				type: 'POST',
				data: {
					'APIKEY': apikey
				},
				success: function(res) {
					if(res.status == 'OK') {
						msg_div.addClass('alert-success');
						msg_div.text('Your API Key works. You are good to go!');
						msg_div.show();
					} else {
						msg_div.addClass('alert-danger');
						msg_div.text('Your API Key failed. Are you sure you have a valid QRZ subsription?');
						msg_div.show();
						$('#qrzrealtime').val(-1);
					}
				},
				error: function(res) {
					msg_div.addClass('alert-danger');
					msg_div.text('ERROR: Something went wrong on serverside. We\'re sorry..');
					msg_div.show();
				},
			});
		});

	}
});

