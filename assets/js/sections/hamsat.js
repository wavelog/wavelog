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
				"targets": [8, 9, 10], "orderable": false
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
		if (activation.satellite.name != activation.sat_export_name) {
			sat = activation.sat_export_name;
		} else {
			sat = activation.satellite.name;
		}
		if (activation.mhz != null) {
			freq = parseFloat(activation.mhz).toFixed(3);
			dir = '';
			if (activation.mhz_direction == "up") {
				dir = '&uarr;';
			} else if (activation.mhz_direction == "down") {
				dir = '&darr;';
			}
			data.push("<span data-bs-toggle=\"tooltip\" data-bs-original-title=\""+freq+" MHz "+dir+"\">"+sat+"</span>");
		} else {
			data.push(sat);
		}
		data.push("<span title=\""+activation.mode+"\" class=\"badge "+activation.mode_class+"\">"+activation.mode+"</span>");
		grids = [];
		for (var j=0; j < activation.grids_wkd.length; j++) {
			if (!grids.some(str => str.includes(activation.grids[j].substring(0, 4)))) {
				if (activation.grids_wkd[j] == 1) {
					grids.push("<a href=\"javascript:displayContacts('"+activation.grids[j].substring(0, 4)+"','SAT','All','All','All','VUCC','');\"><span data-bs-toggle=\"tooltip\" title=\"Worked\" class=\"badge bg-success\">"+activation.grids[j].substring(0, 4)+"</span></a>")
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
			data.push('<span data-bs-toggle=\"tooltip\" title=\"Unknown\" class=\"badge bg-warning\">Unknown</span>');
		}
		if (activation.likes != '0') {
			data.push('<div style="white-space: nowrap; margin-right: 15px;"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.493 18.75c-.425 0-.82-.236-.975-.632A7.48 7.48 0 016 15.375c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23h-.777zM2.331 10.977a11.969 11.969 0 00-.831 4.398 12 12 0 00.52 3.507c.26.85 1.084 1.368 1.973 1.368H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 01-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227z"></path></svg> '+activation.likes+'</div>');
		} else {
			data.push('<div style="white-space: nowrap; margin-right: 15px;"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" stroke-width="1.5"><path d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z" stroke-linejoin="round" stroke-linecap="round"></path></svg>&nbsp;</div>');
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
