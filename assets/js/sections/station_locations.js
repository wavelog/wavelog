$(document).ready(function () {
	function btn_pwd_showhide() {
		if ($(this).closest('div').find('input[type="password"]').length>0) {
			$(this).closest('div').find('input[type="password"]').attr('type','text');
			$(this).closest('div').find('.fa-eye-slash').removeClass('fa-eye-slash').addClass('fa-eye');
		} else {
			$(this).closest('div').find('input[type="text"]').attr('type','password');
			$(this).closest('div').find('.fa-eye').removeClass('fa-eye').addClass('fa-eye-slash');
		}
	}
	$('.btn-pwd-showhide').off('click').on('click', btn_pwd_showhide );

	$('#station_locations_table').DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	$('#dxcc_id').multiselect({
		// template is needed for bs5 support
		templates: {
		  button: '<button type="button" style="text-align: left !important;" class="multiselect dropdown-toggle btn btn-secondary w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		enableFiltering: true,
		enableFullValueFiltering: false,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		widthSynchronizationMode: 'always',
		numberDisplayed: 1,
		inheritClass: true,
		buttonWidth: '100%',
		maxHeight: 600
	});
	$('.multiselect-container .multiselect-filter', $('#dxcc_id').parent()).css({
		'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color':'inherit', 'width':'100%', 'height':'37px'
	})

	if (window.location.pathname.indexOf('/station/edit') !== -1 || window.location.pathname.indexOf('/station/create') !== -1 || window.location.pathname.indexOf('/station/copy') !== -1) {
		updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
		$('#location_us_county').show();
		var dxcc = $('#dxcc_id').val();
		switch (dxcc) {
			case '6':
			case '110':
			case '291':
				$('#stationCntyInputEdit').prop('disabled', false);
				selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');
				break;
			case '15':
			case '54':
			case '61':
			case '126':
			case '151':
			case '288':
			case '339':
			case '170':
			case '21':
			case '29':
			case '32':
			case '281':
				$('#stationCntyInputEdit').prop('disabled', false);
				break;
			 default:
				$('#stationCntyInputEdit').prop('disabled', true);   
		}

		$('#dxcc_id').change(function () {
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

$('#stateDropdown').change(function () {
	var dxcc = $('#dxcc_id').val();
	var state = $('#stateDropdown.form-select').val();
	if (state != '') {
		switch (dxcc) {
			case '6':
			case '110':
			case '291':
				$('#stationCntyInputEdit').prop('disabled', false);
				selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');
				break;
			case '15':
			case '54':
			case '61':
			case '126':
			case '151':
			case '288':
			case '339':
			case '170':
			case '21':
			case '29':
			case '32':
			case '281':
				$('#stationCntyInputEdit').prop('disabled', false);
				break;
			default:
				$('#stationCntyInputEdit').prop('disabled', true);
		}
	} else {
		$('#stationCntyInputEdit').val('');
		$('#stationCntyInputEdit').prop('disabled', true);
	}
});