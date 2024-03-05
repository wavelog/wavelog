$(document).ready(function () {
	$("#station_locations_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	if (window.location.pathname.indexOf("/station/edit") !== -1 || window.location.pathname.indexOf("/station/create") !== -1 || window.location.pathname.indexOf("/station/copy") !== -1) {
		selectize_usa_county();
		updateStateDropdown();
		$("#dxcc_id").change(function () {
			updateStateDropdown();
		});
	}
});

