/*
 * Shared gridsquare QSO modal for all grid map pages (gridmap, activated
 * gridmap, ffma, gridmaster, mostwantedgrids).
 *
 * Each page sets gridModalQuery to a function that builds the awards/
 * qso_details_ajax POST data for a clicked 4-char locator; the map layer
 * files (L.Maidenhead*) then call spawnGridsquareModal(locator).
 */
var gridModalQuery = null;
var modalloading = false;

function spawnGridsquareModal(loc_4char) {
	if (!(modalloading) && gridModalQuery !== null) {
		var ajax_data = gridModalQuery(loc_4char);
		modalloading = true;
		$.ajax({
			url: base_url + 'index.php/awards/qso_details_ajax',
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
						modalloading = false;
						$('[data-bs-toggle="tooltip"]').tooltip();
						$('.displaycontactstable').DataTable({
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
					onhide: function(dialog) {
						enableMap();
					},
					buttons: [{
						label: lang_admin_close,
						action: function(dialogItself) {
							dialogItself.close();
						}
					}]
				});
				dialog.realize();
				$('#gridsquare_map').append(dialog.getModal());
				disableMap();
				dialog.open();
			},
			error: function(e) {
				modalloading = false;
			}
		});
	}
}

function spawnGridsquareActivatorsModal(loc_4char) {
	if (!(modalloading) && gridModalQuery !== null) {
		var ajax_data = gridModalQuery(loc_4char);
		modalloading = true;
		$.ajax({
			url: base_url + 'index.php/mostwantedgrids/activators_details_ajax',
			type: 'post',
			data: ajax_data,
			success: function (html) {
				var dialog = new BootstrapDialog({
					title: lang_general_word_last_activations,
					cssClass: 'qso-dialog',
					size: BootstrapDialog.SIZE_WIDE,
					nl2br: false,
					message: html,
					onshown: function(dialog) {
						modalloading = false;
						$('[data-bs-toggle="tooltip"]').tooltip();
						$('.displaycontactstable').DataTable({
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
					onhide: function(dialog) {
						enableMap();
					},
					buttons: [{
						label: lang_admin_close,
						action: function(dialogItself) {
							dialogItself.close();
						}
					}]
				});
				dialog.realize();
				$('#gridsquare_map').append(dialog.getModal());
				disableMap();
				dialog.open();
			},
			error: function(e) {
				modalloading = false;
			}
		});
	}
}
