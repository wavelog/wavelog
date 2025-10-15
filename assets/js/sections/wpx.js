document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll('.dropdown').forEach(dd => {
		dd.addEventListener('hide.bs.dropdown', function (e) {
			if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
				e.preventDefault(); // stop Bootstrap from closing
			}
		});
	});
});

function wpxLoadDetails(status, band) {
	$('.showWpxResults').empty();
	$.ajax({
		url: site_url + '/awards/wpx_details',
		type: 'post',
		data: {
			band: $('#band2').val(),
			mode: $('#mode').val(),
			status: status,
			sats: $('#sats').val(),
			orbit: $('#orbit').is(':checked') ? 1 : 0,
			Asia: $('#Asia').is(':checked') ? 1 : 0,
			Africa: $('#Africa').is(':checked') ? 1 : 0,
			NorthAmerica: $('#NorthAmerica').is(':checked') ? 1 : 0,
			SouthAmerica: $('#SouthAmerica').is(':checked') ? 1 : 0,
			Antarctica: $('#Antarctica').is(':checked') ? 1 : 0,
			Europe: $('#Europe').is(':checked') ? 1 : 0,
			Oceania: $('#Oceania').is(':checked') ? 1 : 0,
			qsl: $('#qsl').is(':checked') ? 1 : 0,
			lotw: $('#lotw').is(':checked') ? 1 : 0,
			eqsl: $('#eqsl').is(':checked') ? 1 : 0,
			qrz: $('#qrz').is(':checked') ? 1 : 0,
			clublog: $('#clublog').is(':checked') ? 1 : 0,
			summaryband: band
		},
		type: 'post',
		success: function (html) {
			$('.showWpxResults').html(html);
			$('.wpxdetails').DataTable({
                    "pageLength": 25,
                    responsive: false,
                    ordering: false,
                    "scrollY": "400px",
                    "scrollCollapse": true,
                    "paging": false,
                    "scrollX": true,
                    "language": {
                        url: getDataTablesLanguageUrl(),
                    },
                    dom: 'Bfrtip',
                    buttons: [
						{
							extend: 'csv',
							className: 'mb-1 btn btn-sm btn-primary', // Bootstrap classes
								init: function(api, node, config) {
									$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
								},
						}
                    ]
                });
                // change color of csv-button if dark mode is chosen
                if (isDarkModeTheme()) {
                    $(".buttons-csv").css("color", "white");
                }
		},
		error: function (data) {
		},
	});
}
