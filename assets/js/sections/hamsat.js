var hamsAtTimer;
var workableRows = 0;

function loadHamsAt(show_workable_only) {
	clearInterval(hamsAtTimer);
	workable_only = show_workable_only.value;

	$.ajax({
		dataType: "json",
		url: base_url + 'index.php/hamsat/activations',
		type: 'post',
		success: function(result) {
			loadActivationsTable(result, workable_only);
		}
	});
	obj = {
		value: workable_only,
	};
	hamsAtTimer = setInterval(function() {
		loadHamsAt(obj);
	}, 60000);
}

function configureButton(rowLength) {
	if (feed_key_set > 1) {
		if (workableRows != rowLength) {
			if (workable_only == '1') {
				$('#workable_hint').text("Only workable passes shown.");
				$('#toggle_workable').prop('value', 0);
				if (rowLength > workableRows) {
					$('#toggle_workable').html('Show all passes ('+rowLength+')');
				} else {
					$('#toggle_workable').html('Show all passes');
				}
				$('#toggle_workable').show()
			} else {
				$('#workable_hint').text("All passes shown.");
				$('#toggle_workable').prop('value', 1);
				if (workableRows < rowLength) {
					$('#toggle_workable').html('Show workable passes only ('+workableRows+')');
				} else {
					$('#toggle_workable').html('Show workable passes only');
				}
				$('#toggle_workable').show()
			}
		}
	}
}

function loadActivationsTable(rows, show_workable_only) {
	var uninitialized = $('#activationsList').filter(function() {
		if ($.fn.DataTable.fnIsDataTable(this)) {
			return false;
		} else {
			configureButton(rows.length);
			return true;
		}
	});

	$.fn.dataTable.ext.buttons.clear = {
		className: 'buttons-clear',
		action: function ( e, dt, node, config ) {
			dt.search('').draw();
		}
	};

	uninitialized.each(function() {
		$.fn.dataTable.moment(custom_date_format);
		$(this).DataTable({
			"pageLength": 25,
			"columnDefs": [{
				"targets": [8, 9], "orderable": false
		}],	
			searching: true,
			responsive: false,
			ordering: true,
			"scrollY": window.innerHeight - $('#searchForm').innerHeight() - 250,
			"scrollCollapse": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			dom: 'Bfrtip',
			buttons: [
				{
					extend: 'csv'
				},
				{
					extend: 'clear',
					text: lang_admin_clear
				},
			],
			initComplete: function () {
				configureButton(rows.length);
			},
		});
	});

	configureButton(rows.length);

	var table = $('#activationsList').DataTable();

	table.clear();
	workableRows = 0;

	for (i = 0; i < rows.length; i++) {
		let activation = rows[i];

		if (workable_only == "1" && activation.is_workable == false) {
			continue;
		} else {
			if (activation.is_workable == true) {
				workableRows++;
			}
		}

		var data = [];
		data.push(activation.aos_at_date);
		data.push(activation.aos_to_los);
		if (activation.callsign_wkd == 1) {;
			data.push("<span class=\"text-success\">"+activation.callsign.replaceAll('0', 'Ø')+"</span>");
		} else {
			data.push(activation.callsign.replaceAll('0', 'Ø'));
		}
		data.push(activation.comment);
		if (activation.mhz != null) {
			freq = parseFloat(activation.mhz).toFixed(3);
			dir = '';
			if (activation.mhz_direction == "up") {
				dir = '&uarr;';
			} else if (activation.mhz_direction == "down") {
				dir = '&darr;';
			}
			data.push("<span data-bs-toggle=\"tooltip\" data-bs-original-title=\""+freq+" MHz "+dir+"\">"+activation.satellite.name+"</span>");
		} else {
			data.push(activation.satellite.name);
		}
		data.push("<span title=\""+activation.mode+"\" class=\"badge "+activation.mode_class+"\">"+activation.mode+"</span>");
		grids = [];
		for (var j=0; j < activation.grids_wkd.length; j++) {
			if (!grids.some(str => str.includes(activation.grids[j].substring(0, 4)))) {
				if (activation.grids_wkd[j] == 1) {
					grids.push("<span data-bs-toggle=\"tooltip\" title=\"Worked\" class=\"badge bg-success\">"+activation.grids[j].substring(0, 4)+"</span>")
				} else {
					grids.push("<span data-bs-toggle=\"tooltip\" title=\"Not Worked\" class=\"badge bg-danger\">"+activation.grids[j].substring(0, 4)+"</span>")
				}
			}
		}
		data.push(grids.join(' '));
		if (feed_key_set > 0) {
			if (activation.is_workable == true) {
				data.push(activation.workable_from_to);
			} else {
				data.push('<span data-bs-toggle="tooltip" class="badge bg-danger" data-bs-original-title="Not Workable">No</span>');
			}
		} else {
			data.push('<span data-bs-toggle=\"tooltip\" title=\"Unkown\" class=\"badge bg-warning\">Unknown</span>');
		}
		data.push("<a href=\""+activation.url+"\" target=\"_blank\">Track</a>");
		if (activation.is_workable == true) {
			data.push("<a href=\"https://sat.fg8oj.com/sked.php?s%5B%5D="+activation.sat_export_name+"&l="+activation.my_gridsquare+"&el1=0&l2="+activation.grids[0]+"&el2=0&duration=1&start=0&OK=Search\" target=\"_blank\">Sked</a>");
		} else {
			data.push('');
		}

		let createdRow = table.row.add(data).index();
		table.rows(createdRow).nodes().to$().data('activationID', activation.id);
		table.row(createdRow).node().id = 'activationID-' + activation.id;
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

$(document).ready(function() {
	const obj = {
		value: workable_preset,
	};
	loadHamsAt(obj);
	hamsAtTimer = setInterval(function() {
		loadHamsAt(obj);
	}, 60000);
});
