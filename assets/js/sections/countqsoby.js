$('#distinctplot_bands').change(function(){
	var band = $("#distinctplot_bands").val();
	if (band != "SAT") {
		$("#distinctsatrow").hide();
		$("#distinctorbitrow").hide();
		$("#distinctplot_sats").val('All');
		$("#distinctorbits").val('All');
		if ($("#distinctpropmode").val() == "SAT") {
			$("#distinctpropmode").val('All');
		}
	} else {
		$("#distinctsatrow").show();
		$("#distinctorbitrow").show();
		$("#distinctpropmode").val('SAT');
	}
});

$('#distinctplot_sats').change(function(){
	if ($("#distinctplot_sats").val() != 'All') {
		$("#distinctplot_bands").val('SAT');
		$("#distinctpropmode").val('SAT');
		$("#distinctsatrow").show();
		$("#distinctorbitrow").show();
	}
});

function distinctApplyPreset(preset) {
	const dateFrom = document.getElementById('distinctdateFrom');
	const dateTo = document.getElementById('distinctdateTo');
	const today = new Date();

	function formatDate(date) {
		const year = date.getUTCFullYear();
		const month = String(date.getUTCMonth() + 1).padStart(2, '0');
		const day = String(date.getUTCDate()).padStart(2, '0');
		return `${year}-${month}-${day}`;
	}

	switch(preset) {
		case 'today':
			dateFrom.value = formatDate(today);
			dateTo.value = formatDate(today);
			break;

		case 'yesterday':
			const yesterday = new Date(today);
			yesterday.setDate(yesterday.getUTCDate() - 1);
			dateFrom.value = formatDate(yesterday);
			dateTo.value = formatDate(yesterday);
			break;

		case 'last7days':
			const sevenDaysAgo = new Date(today);
			sevenDaysAgo.setDate(sevenDaysAgo.getUTCDate() - 7);
			dateFrom.value = formatDate(sevenDaysAgo);
			dateTo.value = formatDate(today);
			break;

		case 'last30days':
			const thirtyDaysAgo = new Date(today);
			thirtyDaysAgo.setDate(thirtyDaysAgo.getUTCDate() - 30);
			dateFrom.value = formatDate(thirtyDaysAgo);
			dateTo.value = formatDate(today);
			break;

		case 'thismonth':
			const firstDayOfMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1));
			dateFrom.value = formatDate(firstDayOfMonth);
			dateTo.value = formatDate(today);
			break;

		case 'lastmonth':
			const firstDayOfLastMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth() - 1, 1));
			const lastDayOfLastMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 0));
			dateFrom.value = formatDate(firstDayOfLastMonth);
			dateTo.value = formatDate(lastDayOfLastMonth);
			break;

		case 'thisyear':
			const firstDayOfYear = new Date(Date.UTC(today.getUTCFullYear(), 0, 1));
			dateFrom.value = formatDate(firstDayOfYear);
			dateTo.value = formatDate(today);
			break;

		case 'lastyear':
			const lastYear = today.getUTCFullYear() - 1;
			const firstDayOfLastYear = new Date(Date.UTC(lastYear, 0, 1));
			const lastDayOfLastYear = new Date(Date.UTC(lastYear, 11, 31));
			dateFrom.value = formatDate(firstDayOfLastYear);
			dateTo.value = formatDate(lastDayOfLastYear);
			break;
	}
}

function distinctResetDates() {
	document.getElementById('distinctdateFrom').value = '';
	document.getElementById('distinctdateTo').value = '';
}

var distinctTable = null;

function distinctPlot() {
	$(".ld-ext-right-distinctplot").addClass('running');
	$(".ld-ext-right-distinctplot").prop('disabled', true);
	$(".alert").remove();
	$.ajax({
		url: site_url + '/countqsoby/get_counts',
		type: 'post',
		data: {
			'type': $("#distincttype").val(),
			'band': $("#distinctplot_bands").val(),
			'sat': $("#distinctplot_sats").val(),
			'orbit': $("#distinctorbits").val(),
			'propagation': $("#distinctpropmode").val(),
			'mode': $("#distinctplot_mode").val(),
			'dateFrom': $("#distinctdateFrom").val(),
			'dateTo': $("#distinctdateTo").val(),
			'qsl': $("#distinctqsl").is(":checked"),
			'lotw': $("#distinctlotw").is(":checked"),
			'eqsl': $("#distincteqsl").is(":checked"),
			'qrz': $("#distinctqrz").is(":checked"),
			'clublog': $("#distinctclublog").is(":checked")
		},
		success: function(tmp) {
			if (tmp.ok == 'OK') {
				renderDistinctTable(tmp);
			} else {
				destroyDistinctTable();
				$("#distinct_summary").empty();
				$("#countqsoby_div").append('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' + escapeHtml(tmp.Error) + '</div>');
			}
			$(".ld-ext-right-distinctplot").removeClass('running');
			$(".ld-ext-right-distinctplot").prop('disabled', false);
		}
	});
}

function destroyDistinctTable() {
	if ($.fn.DataTable.isDataTable('#distincttable')) {
		$('#distincttable').DataTable().destroy();
	}
	$('#distincttable').empty();
	distinctTable = null;
}

function distinctBadgeStyle(color) {
	var r = parseInt(color.substr(1, 2), 16), g = parseInt(color.substr(3, 2), 16), b = parseInt(color.substr(5, 2), 16);
	var textColor = (0.299 * r + 0.587 * g + 0.114 * b) > 150 ? '#000' : '#fff';
	return 'background: ' + color + '; color: ' + textColor + ';';
}

function renderDistinctTable(tmp) {
	var typeLabels = { dxcc: lang_distinct_counts_type_dxcc, grid: lang_distinct_counts_type_grid, itu: lang_distinct_counts_type_itu, cq: lang_distinct_counts_type_cq };
	var typeLabel = typeLabels[tmp.type] || lang_distinct_counts_type_ref;

	var workedColor = (typeof user_map_custom !== 'undefined' && user_map_custom.qso && user_map_custom.qso.color) ? user_map_custom.qso.color : '#0d6efd';
	var confirmedColor = (typeof user_map_custom !== 'undefined' && user_map_custom.qsoconfirm && user_map_custom.qsoconfirm.color) ? user_map_custom.qsoconfirm.color : '#198754';

	$("#distinct_summary").html(
		'<div class="d-flex flex-wrap justify-content-center align-items-center gap-2">' +
			'<span class="fs-5 fw-semibold">' + decodeHtml(typeLabel) + '</span>' +
			'<span class="badge fs-6" style="' + distinctBadgeStyle(workedColor) + '">' + tmp.summary.distinct + ' ' + decodeHtml(lang_distinct_counts_worked) + '</span>' +
			'<span class="badge fs-6" style="' + distinctBadgeStyle(confirmedColor) + '">' + tmp.summary.confirmed + ' ' + decodeHtml(lang_distinct_counts_confirmed) + '</span>' +
			'<span class="badge text-bg-secondary fs-6">' + tmp.summary.qsos + ' ' + decodeHtml(lang_gen_hamradio_qso_short) + ' (' + decodeHtml(lang_distinct_counts_qsos_total) + ')</span>' +
		'</div>'
	);

	destroyDistinctTable();

	var columns = [
		{ title: decodeHtml(typeLabel), data: 'display_key', className: 'dt-body-left', render: function(data, type, row) {
			if (type === 'display') {
				return data + (row.group_deleted ? ' <span class="badge text-bg-danger">' + decodeHtml(lang_distinct_counts_deleted_dxcc) + '</span>' : '');
			}
			return data;
		} },
		{ title: decodeHtml(lang_gen_hamradio_qso_short), data: 'qso_count', type: 'num', className: 'dt-body-right', render: function(data, type, row) {
			if (type === 'display') {
				return '<a href="#" class="dc-link" data-group="' + row.group_key + '" data-confirmed="false">' + data + '</a>';
			}
			return data;
		} },
		{ title: decodeHtml(lang_distinct_counts_confirmed), data: 'confirmed_count', type: 'num', className: 'dt-body-right', render: function(data, type, row) {
			if (type === 'display') {
				return '<a href="#" class="dc-link" data-group="' + row.group_key + '" data-confirmed="true">' + data + '</a>';
			}
			return data;
		} }
	];

	var rows = [];
	$.each(tmp.groups, function() {
		rows.push({
			group_key: escapeHtml(String(this.group_key)),
			display_key: escapeHtml(this.group_name != null ? String(this.group_name) : String(this.group_key)),
			group_deleted: !!this.group_deleted,
			qso_count: Number(this.qso_count),
			confirmed_count: Number(this.confirmed_count)
		});
	});

	distinctTable = $('#distincttable').DataTable({
		data: rows,
		columns: columns,
		pageLength: 25,
		order: [[1, 'desc']],
		language: {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		buttons: [
			'csv'
		]
	});

	$('#distincttable tbody').off('click', '.dc-link').on('click', '.dc-link', function (e) {
		e.preventDefault();
		e.stopPropagation();
		getDistinctQsos($(this).attr('data-group'), $(this).attr('data-confirmed') === 'true');
	});
}

function getDistinctQsos(group, confirmed) {
	$.ajax({
		url: site_url + '/countqsoby/details',
		type: 'post',
		data: {
			'type': $("#distincttype").val(),
			'group': group,
			'band': $("#distinctplot_bands").val(),
			'sat': $("#distinctplot_sats").val(),
			'orbit': $("#distinctorbits").val(),
			'propagation': $("#distinctpropmode").val(),
			'mode': $("#distinctplot_mode").val(),
			'dateFrom': $("#distinctdateFrom").val(),
			'dateTo': $("#distinctdateTo").val(),
			'confirmed': confirmed === true,
			'qsl': $("#distinctqsl").is(":checked"),
			'lotw': $("#distinctlotw").is(":checked"),
			'eqsl': $("#distincteqsl").is(":checked"),
			'qrz': $("#distinctqrz").is(":checked"),
			'clublog': $("#distinctclublog").is(":checked")
		},
		success: function (html) {
			BootstrapDialog.show({
				title: lang_general_word_qso_data,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
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
					$('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
						showQsoActionsMenu($(this).closest('.dropdown'));
					});
				},
			buttons: [{
				label: lang_admin_close,
				action: function (dialogItself) {
					dialogItself.close();
				}
			}]
		});
	}
});
}

$(document).ready(function(){
	$('#distinctplot_bands').trigger('change');
});
