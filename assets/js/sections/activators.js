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

$(document).ready(function () {

	let bandselect = $('#band');

	showHideLeoGeo(bandselect);
	bandselect.change(function () {
		showHideLeoGeo(bandselect);
	});

});

function showHideLeoGeo(bandselect) {

	if (bandselect.val() == "SAT") {
		$("#leogeo").show();
	} else {
		$("#leogeo").hide();
	}
}

function displayActivatorsContacts(call, band, leogeo) {
	var data = { 
        Searchphrase: call, 
        Band: band,
        Type: 'CALL'
    };

    if (leogeo != 'both') {
        data.Orbit = leogeo;
    }

	$.ajax({
		url: base_url + "index.php/awards/qso_details_ajax",
		type: "post",
		data: data,
		success: function (html) {
			BootstrapDialog.show({
				title: lang_general_word_qso_data,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: "qso-was-dialog",
				nl2br: false,
				message: html,
				onshown: function (dialog) {
					$('[data-bs-toggle="tooltip"]').tooltip();
					$('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
						showQsoActionsMenu($(this).closest('.dropdown'));
					});
				},
				buttons: [
					{
						label: lang_admin_close,
						action: function (dialogItself) {
							dialogItself.close();
						},
					},
				],
			});
		},
	});
}
