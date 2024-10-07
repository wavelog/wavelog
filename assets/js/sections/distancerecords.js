var modalloading=false;
function displayDistanceQsos(sat) {
	if (!(modalloading)) {
		var ajax_data = ({
			'Sat': sat,
		})
		modalloading=true;
		$.ajax({
			url: base_url + 'index.php/distancerecords/sat_records_ajax',
			type: 'post',
			data: ajax_data,
			success: function (html) {
		    		var dialog = new BootstrapDialog({
					title: lang_general_word_qso_data,
					cssClass: 'qso-dialog',
					size: BootstrapDialog.SIZE_WIDE,
					nl2br: false,
					message: html,
					onshown: function(dialog) {
						modalloading=false;
						$('[data-bs-toggle="tooltip"]').tooltip();
						$('.contacttable').DataTable({
							"pageLength": 25,
							responsive: false,
							ordering: false,
							"scrollY":        "550px",
							"scrollCollapse": true,
							"paging":         false,
							"scrollX": true,
							"language": {
								url: getDataTablesLanguageUrl(),
							},
							dom: 'Bfrtip',
							buttons: [
								'csv'
							]
						});
						// change color of csv-button if dark mode is chosen
						if (isDarkModeTheme()) {
							$(".buttons-csv").css("color", "white");
						}
						$('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
							showQsoActionsMenu($(this).closest('.dropdown'));
						});
					},
					buttons: [{
						label: lang_admin_close,
						action: function(dialogItself) {
							dialogItself.close();
						}
					}]
				});
			    dialog.realize();
		    		$("body").append(dialog.getModal());
		    		dialog.open();
},
			error: function(e) {
				modalloading=false;
			}
		});
	}
}
