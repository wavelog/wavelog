$(document).ready(function () {
	$("#station_locations_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	if (window.location.pathname.indexOf("/station/edit") !== -1) {
		updateStateDropdown();
		$("#dxcc_select").change(function () {
			updateStateDropdown();
		});
	}
});

function updateStateDropdown() {
	var selectedDxcc = $("#dxcc_select");

	if (selectedDxcc.val() !== "") {
		$.ajax({
			url: base_url + "index.php/lookup/get_state_list",
			type: "POST",
			data: { dxcc: selectedDxcc.val() },
			success: function (response) {
				if (response.status === "ok") {
					statesDropdown(response, set_state);
                    $('#stateInputLabel').html(response.subdivision_name);
				} else {
                    statesDropdown(response);
                }
			},
			error: function () {
				console.log('ERROR', response.status);
			},
		});
	} 

    if (selectedDxcc.val() == '291' || selectedDxcc.val() == '110' || selectedDxcc.val() == '6') {
        $("#location_us_county").show();
    } else {
        $("#location_us_county").hide();
    }
}
