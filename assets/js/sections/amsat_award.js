var modalloading = false;

function buildQslString() {
	var qsl = '';
	if (document.getElementById('lotw')?.checked) qsl += 'L';
	if (document.getElementById('qsl')?.checked) qsl += 'Q';
	return qsl || 'LQ';
}

function displayRoverGridQsos(grid) {
	if (modalloading) return;
	modalloading = true;
	$.ajax({
		url: base_url + 'index.php/awards/qso_details_ajax',
		type: 'post',
		data: {
			Searchphrase: grid,
			Band: 'SAT',
			Sat: 'All',
			Orbit: 'All',
			Mode: 'All',
			Propagation: 'SAT',
			Type: 'VUCC',
			searchmode: 'activated',
			QSL: buildQslString()
		},
		success: function(html) {
			BootstrapDialog.show({
				title: lang_general_word_qso_data,
				cssClass: 'qso-dialog',
				size: BootstrapDialog.SIZE_WIDE,
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					modalloading = false;
					$('[data-bs-toggle="tooltip"]').tooltip();
					$('.displaycontactstable').DataTable({
						pageLength: 25,
						responsive: false,
						ordering: false,
						scrollY: "550px",
						scrollCollapse: true,
						paging: false,
						scrollX: true,
						language: { url: getDataTablesLanguageUrl() },
						dom: 'Bfrtip',
						buttons: ['csv']
					});
				}
			});
		}
	});
}
