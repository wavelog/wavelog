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
	}
});

