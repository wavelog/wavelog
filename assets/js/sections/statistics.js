totalSatQsos();
totalQsosPerYear();

var activeTab='totalQsosPerYear()';

// Preset functionality
function applyPreset(preset) {
	const dateFrom = document.getElementById('dateFrom');
	const dateTo = document.getElementById('dateTo');
	const today = new Date();

	// Format date as YYYY-MM-DD
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
			const firstDayOfMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 1);
			dateFrom.value = formatDate(firstDayOfMonth);
			dateTo.value = formatDate(today);
			break;

		case 'lastmonth':
			const firstDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth() - 1, 1);
			const lastDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 0);
			dateFrom.value = formatDate(firstDayOfLastMonth);
			dateTo.value = formatDate(lastDayOfLastMonth);
			break;

		case 'thisyear':
			const firstDayOfYear = new Date(today.getUTCFullYear(), 0, 1);
			dateFrom.value = formatDate(firstDayOfYear);
			dateTo.value = formatDate(today);
			break;

		case 'lastyear':
			const lastYear = today.getUTCFullYear() - 1;
			const firstDayOfLastYear = new Date(lastYear, 0, 1);
			const lastDayOfLastYear = new Date(lastYear, 11, 31);
			dateFrom.value = formatDate(firstDayOfLastYear);
			dateTo.value = formatDate(lastDayOfLastYear);
			break;

		case 'alltime':
			dateFrom.value = '';
			dateTo.value = '';
			break;
	}
	// Trigger refresh after applying preset
	eval(activeTab);
}

// Reset dates function
function resetDates() {
	const dateFrom = document.getElementById('dateFrom');
	const dateTo = document.getElementById('dateTo');
	dateFrom.value = '';
	dateTo.value = '';
	// Trigger refresh after resetting
	eval(activeTab);
}

$("a[href='#satellite']").on('shown.bs.tab', function(e) {
	totalSatQsos();
	activeTab='totalSatQsos()';
	$(".sattable").DataTable().columns.adjust();
	$("#dateFilterContainer").show();
});

$("a[href='#sattab']").on('shown.bs.tab', function(e) {
	activeTab='totalSatQsos()';
	totalSatQsos();
	$("#dateFilterContainer").show();
});

$("a[href='#home']").on('shown.bs.tab', function(e) {
	activeTab='totalQsosPerYear()';
	totalQsosPerYear();
	$("#dateFilterContainer").show();
});

$("a[href='#yearstab']").on('shown.bs.tab', function(e) {
	activeTab='totalQsosPerYear()';
	totalQsosPerYear();
	$("#dateFilterContainer").hide();
});

$("a[href='#monthstab']").on('shown.bs.tab', function(e) {
	activeTab='totalQsosPerMonth()';
	totalQsosPerMonth();
	$("#dateFilterContainer").show();
});

$("a[href='#bandtab']").on('shown.bs.tab', function(e) {
	totalBandQsos();
	activeTab='totalBandQsos()';
	$("#dateFilterContainer").show();
});

$("a[href='#modetab']").on('shown.bs.tab', function(e) {
	totalModeQsos();
	activeTab='totalModeQsos()';
	$("#dateFilterContainer").show();
});

$("a[href='#qsotab']").on('shown.bs.tab', function(e) {
	totalQsos();
	activeTab='totalQsos()';
	$("#dateFilterContainer").show();
});

$("a[href='#operatorstab']").on('shown.bs.tab', function(e) {
	totalOperatorQsos();
	activeTab='totalOperatorQsos()';
	$("#dateFilterContainer").show();
});

$("a[href='#satqsostab']").on('shown.bs.tab', function(e) {
	totalSatQsosC();
	activeTab='totalSatQsosC()';
	$("#dateFilterContainer").show();
});

$("a[href='#uniquetab']").on('shown.bs.tab', function(e) {
	uniqueCallsigns();
	activeTab='uniqueCallsigns()';
	$("#dateFilterContainer").show();
});

$("a[href='#satuniquetab']").on('shown.bs.tab', function(e) {
	uniqueSatCallsigns();
	activeTab='uniqueSatCallsigns()';
	$("#dateFilterContainer").show();
});

$("a[href='#satuniquegridtab']").on('shown.bs.tab', function(e) {
	uniqueSatGrids();
	activeTab='uniqueSatGrids()';
	$("#dateFilterContainer").show();
});

$("#dateFrom, #dateTo").on('change', function(e) {
	eval(activeTab);
});

function uniqueSatGrids() {
    $.ajax({
        url: base_url+'index.php/statistics/get_unique_sat_grids',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
	    $(".satuniquegrid").html('');
            if (data.length > 0) {
                $(".satuniquegrid").html(data);
            }
        }
    });
}

function uniqueSatCallsigns() {
    $.ajax({
        url: base_url+'index.php/statistics/get_unique_sat_callsigns',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
	    $(".satunique").html('');
            if (data.length > 0) {
                $(".satunique").html(data);
            }
        }
    });
}

function uniqueCallsigns() {
    $.ajax({
        url: base_url+'index.php/statistics/get_unique_callsigns',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
	    $(".unique").html('');
            if (data.length > 0) {
                $(".unique").html(data);
            }
        }
    });
}

function totalQsos() {
    $.ajax({
        url: base_url+'index.php/statistics/get_total_qsos',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
	    $(".qsos").html('');
            if (data.length > 0) {
                $(".qsos").html(data);
            }
        }
    });
}

function totalSatQsosC() {
    $.ajax({
        url: base_url+'index.php/statistics/get_total_sat_qsos',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
	    $(".satqsos").html('');
            if (data.length > 0) {
                $(".satqsos").html(data);
            }
        }
    });
}

function totalQsosPerYear() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');

	$.ajax({
		url: base_url+'index.php/statistics/get_year',
		type: 'post',
		data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
		success: function (data) {
			if (data.length > 0) {
				$(".years").html('');
				$(".years").append('<h2>' + lang_statistics_years + '</h2><div id="yearContainer"></div><div id="yearTable"></div>');
				$("#yearContainer").append("<canvas id=\"yearChart\" width=\"400\" height=\"100\"></canvas>");

				// appending table to hold the data
				$("#yearTable").append('<table style="width:100%" class="yeartable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + lang_statistics_year +'</td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');

				var labels = [];
				var dataQso = [];

				var $myTable = $('.yeartable');
				var i = 1;

				// building the rows in the table
				var rowElements = data.map(function (row) {

					var $row = $('<tr></tr>');

					var $iterator = $('<td></td>').html(i++);
					var $type = $('<td></td>').html(row.year);
					var $content = $('<td></td>').html(row.total);

					$row.append($iterator, $type, $content);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				$.each(data, function () {
					labels.push(this.year);
					dataQso.push(this.total);
				});

				labels.reverse();
				dataQso.reverse();

				var ctx = document.getElementById("yearChart").getContext('2d');
				var myChart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							label: decodeHtml(lang_statistics_number_of_qso_worked_each_year),
							data: dataQso,
							backgroundColor: 'rgba(54, 162, 235, 0.2)',
							borderColor: 'rgba(54, 162, 235, 1)',
							borderWidth: 2,
							color: color
						}]
					},
					options: {
						scales: {
							y: {
								ticks: {
									beginAtZero: true,
									color: color
								}
							},
							x: {
								ticks: {
									color: color
								}
							}
						},
						plugins: {
							legend: {
								labels: {
									color: color
								}
							}

						}
					}
				});
				$('.yeartable').DataTable({
					responsive: false,
					ordering: false,
					"scrollY": "320px",
					"scrollCollapse": true,
					"paging": false,
					"scrollX": true,
					"language": {
						url: getDataTablesLanguageUrl(),
					},
					bFilter: false,
					bInfo: false
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $('body').css("background-color");

				if (background != ('rgb(255, 255, 255)')) {
					$(".buttons-csv").css("color", "white");
				}
			}
		}
	});
}

function totalQsosPerMonth() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');

	$.ajax({
		url: base_url+'index.php/statistics/get_year_month',
		type: 'post',
		data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
		success: function (data) {
			if (data.length > 0) {
				$(".months").html('');
				$(".months").append('<h2>' + lang_statistics_months + '</h2><div id="monthContainer"></div><div id="monthTable"></div>');
				$("#monthContainer").append("<canvas id=\"monthChart\" width=\"400\" height=\"100\"></canvas>");

				// appending table to hold the data
				$("#monthTable").append('<table style="width:100%" class="monthtable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + decodeHtml(lang_statistics_month) +'</td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');

				var labels = [];
				var dataQso = [];

				var $myTable = $('.monthtable');
				var i = 1;

				// building the rows in the table
				var rowElements = data.map(function (row) {
					var $row = $('<tr></tr>');

					// Convert month number to string with leading zero
					var monthKey = row.month.toString().padStart(2, '0');
					var monthName = decodeHtml(monthNames[monthKey] || monthKey);

					var $iterator = $('<td></td>').html(i++);
					var $type = $('<td></td>').html(monthName);
					var $content = $('<td></td>').html(row.total);

					$row.append($iterator, $type, $content);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				$.each(data, function () {
					var monthKey = this.month.toString().padStart(2, '0');
					var monthName = decodeHtml(monthNames[monthKey] || monthKey);
					labels.push(monthName);
					dataQso.push(this.total);
				});

				var ctx = document.getElementById("monthChart").getContext('2d');
				var myChart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							label: decodeHtml(lang_statistics_number_of_qso_worked_each_month),
							data: dataQso,
							backgroundColor: 'rgba(54, 162, 235, 0.2)',
							borderColor: 'rgba(54, 162, 235, 1)',
							borderWidth: 2,
							color: color
						}]
					},
					options: {
						scales: {
							y: {
								ticks: {
									beginAtZero: true,
									color: color
								}
							},
							x: {
								ticks: {
									color: color
								}
							}
						},
						plugins: {
							legend: {
								labels: {
									color: color
								}
							}

						}
					}
				});
				$('.monthtable').DataTable({
					responsive: false,
					ordering: false,
					"scrollY": "320px",
					"scrollCollapse": true,
					"paging": false,
					"scrollX": true,
					"language": {
						url: getDataTablesLanguageUrl(),
					},
					bFilter: false,
					bInfo: false
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $('body').css("background-color");

				if (background != ('rgb(255, 255, 255)')) {
					$(".buttons-csv").css("color", "white");
				}
			}
		}
	});
}

function totalModeQsos() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');
	$.ajax({
		url: base_url+'index.php/statistics/get_mode',
		type: 'post',
		data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
		success: function (data) {
			if (data.length > 0) {
				$(".mode").html('');
				var labels = [];
				var dataQso = [];

				$.each(data, function () {
					labels.push(this.mode.toUpperCase());
					dataQso.push(this.total);
				});

				if (dataQso[0] === null && dataQso[1] === null && dataQso[2] === null && dataQso[3] === null) return;

				$(".mode").append('<br /><div style="display: flex;" id="modeContainer"><h2>' + lang_statistics_modes + '</h2><div style="flex: 1;"><canvas id="modeChart" width="500" height="500"></canvas></div><div style="flex: 1;" id="modeTable"></div></div><br />');

				// appending table to hold the data
				$("#modeTable").append('<table style="width:100%" class=\"modetable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + lang_gen_hamradio_mode + ' </td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');


				var $myTable = $('.modetable');
				var i = 1;

				// building the rows in the table
				var rowElements = data.map(function (row) {

					var $row = $('<tr></tr>');

					var $iterator = $('<td></td>').html(i++);
					var $type = $('<td></td>').html(row.mode.toUpperCase());
					var $content = $('<td></td>').html(row.total);

					$row.append($iterator, $type, $content);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				const COLORS = ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"]

				var ctx = document.getElementById("modeChart").getContext('2d');
				var myChart = new Chart(ctx, {
					type: 'pie',
					plugins: [ChartPieChartOutlabels],
					data: {
						labels: labels,
						datasets: [{
							label: 'Number of QSO\'s worked',
							data: dataQso,
							backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"],
							borderWidth: 1,
							borderColor: 'rgba(54, 162, 235, 1)',
						}]
					},

					options: {
						layout: {
							padding: 100
						},
						title: {
							color: color,
							fullSize: true,
						},
						responsive: false,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								display: false,
								labels: {
									boxWidth: 15,
									color: color,
									font: {
										size: 14,
									}
								},
								position: 'right',
								align: "middle"
							},
							outlabels: {
								backgroundColor: COLORS,
								borderColor: COLORS,
								borderRadius: 2, // Border radius of Label
								borderWidth: 2, // Thickness of border
								color: 'white',
								stretch: 45,
								padding: 0,
								font: {
									resizable: true,
									minSize: 15,
									maxSize: 25,
									family: Chart.defaults.font.family,
									size: Chart.defaults.font.size,
									style: Chart.defaults.font.style,
									lineHeight: Chart.defaults.font.lineHeight,
								},
								zoomOutPercentage: 100,
								textAlign: 'start',
								backgroundColor: COLORS,
							}

						}
					}
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $('body').css("background-color");

				if (background != ('rgb(255, 255, 255)')) {
					$(".buttons-csv").css("color", "white");
				}
			}
		}
	});
}

function totalBandQsos() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');

	$.ajax({
		url: base_url+'index.php/statistics/get_band',
		type: 'post',
		data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
		success: function (data) {
			if (data.length > 0) {
				$(".band").html('');
				$(".band").append('<br /><div style="display: flex;" id="bandContainer"><h2>' + lang_statistics_bands + '</h2><div style="flex: 1;"><canvas id="bandChart" width="500" height="500"></canvas></div><div style="flex: 1;" id="bandTable"></div></div><br />');

				// appending table to hold the data
				$("#bandTable").append('<table style="width:100%" class="bandtable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + lang_gen_hamradio_band + '</td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');
				var labels = [];
				var dataQso = [];
				var totalQso = Number(0);

				var $myTable = $('.bandtable');
				var i = 1;

				// building the rows in the table
				var rowElements = data.map(function (row) {

					var $row = $('<tr></tr>');

					var $iterator = $('<td></td>').html(i++);
					var $type = $('<td></td>').html(row.band);
					var $content = $('<td></td>').html(row.count);

					$row.append($iterator, $type, $content);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				$.each(data, function () {
					labels.push(this.band);
					dataQso.push(this.count);
					totalQso = Number(totalQso) + Number(this.count);
				});

				const COLORS = ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"]
				var ctx = document.getElementById("bandChart").getContext('2d');
				var myChart = new Chart(ctx, {
					plugins: [ChartPieChartOutlabels],
					type: 'doughnut',
					data: {
						labels: labels,
						datasets: [{
							label: 'Number of QSO\'s worked',
							data: dataQso,
							borderColor: 'rgba(54, 162, 235, 1)',
							backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"],
							borderWidth: 1,
						}]
					},
					options: {
						layout: {
							padding: 100
						},
						title: {
							fontColor: color,
							fullSize: true,
						},
						responsive: true,
						maintainAspectRatio: true,
						plugins: {
							legend: {
								display: false,
								labels: {
									boxWidth: 15,
									color: color,
									font: {
										size: 14,
									}
								},
								position: 'right',
								align: "middle"
							},
							outlabels: {
								display: function(context) { // Hide labels with low percentage
									return ((context.dataset.data[context.dataIndex] / totalQso * 100) > 1)
								},
								backgroundColor: COLORS,
								borderColor: COLORS,
								borderRadius: 2, // Border radius of Label
								borderWidth: 2, // Thickness of border
								color: 'white',
								stretch: 10,
								padding: 0,
								font: {
									resizable: true,
									minSize: 12,
									maxSize: 25,
									family: Chart.defaults.font.family,
									size: Chart.defaults.font.size,
									style: Chart.defaults.font.style,
									lineHeight: Chart.defaults.font.lineHeight,
								},
								zoomOutPercentage: 100,
								textAlign: 'start',
								backgroundColor: COLORS,
							}
						}
					}
				});

				$('.bandtable').DataTable({
					responsive: false,
					ordering: false,
					"scrollY": "330px",
					"scrollCollapse": true,
					"paging": false,
					"scrollX": true,
					"language": {
						url: getDataTablesLanguageUrl(),
					},
					bFilter: false,
					bInfo: false,
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $('body').css("background-color");

				if (background != ('rgb(255, 255, 255)')) {
					$(".buttons-csv").css("color", "white");
				}
			}
		}
	});
}

function totalOperatorQsos() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');

	$.ajax({
		url: base_url+'index.php/statistics/get_operators',
		type: 'post',
		data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
		success: function (data) {
			if (data.length > 0) {
				$(".operators").html('');
				$(".operators").append('<br /><div style="display: flex;" id="operatorContainer"><h2>' + lang_statistics_operators + '</h2><div style="flex: 1;"><canvas id="operatorChart" width="500" height="500"></canvas></div><div style="flex: 1;" id="operatorTable"></div></div><br />');

				// appending table to hold the data
				$("#operatorTable").append('<table style="width:100%" class="operatorTable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + lang_gen_hamradio_operator + '</td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');
				var labels = [];
				var dataQso = [];
				var totalQso = Number(0);

				var $myTable = $('.operatorTable');
				var i = 1;

				// building the rows in the table
				var rowElements = data.map(function (row) {

					var $row = $('<tr></tr>');

					var $iterator = $('<td></td>').html(i++);
					var $type = $('<td></td>').html(row.operator);
					var $content = $('<td></td>').html(row.count);

					$row.append($iterator, $type, $content);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				$.each(data, function () {
					labels.push(this.operator);
					dataQso.push(this.count);
					totalQso = Number(totalQso) + Number(this.count);
				});

				const COLORS = ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"]
				var ctx = document.getElementById("operatorChart").getContext('2d');
				var myChart = new Chart(ctx, {
					plugins: [ChartPieChartOutlabels],
					type: 'doughnut',
					data: {
						labels: labels,
						datasets: [{
							label: 'Number of QSO\'s worked',
							data: dataQso,
							borderColor: 'rgba(54, 162, 235, 1)',
							backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"],
							borderWidth: 1,
						}]
					},
					options: {
						layout: {
							padding: 100
						},
						title: {
							fontColor: color,
							fullSize: true,
						},
						responsive: true,
						maintainAspectRatio: true,
						plugins: {
							legend: {
								display: false,
								labels: {
									boxWidth: 15,
									color: color,
									font: {
										size: 14,
									}
								},
								position: 'right',
								align: "middle"
							},
							outlabels: {
								display: function(context) { // Hide labels with low percentage
									return ((context.dataset.data[context.dataIndex] / totalQso * 100) > 1)
								},
								backgroundColor: COLORS,
								borderColor: COLORS,
								borderRadius: 2, // Border radius of Label
								borderWidth: 2, // Thickness of border
								color: 'white',
								stretch: 10,
								padding: 0,
								font: {
									resizable: true,
									minSize: 12,
									maxSize: 25,
									family: Chart.defaults.font.family,
									size: Chart.defaults.font.size,
									style: Chart.defaults.font.style,
									lineHeight: Chart.defaults.font.lineHeight,
								},
								zoomOutPercentage: 100,
								textAlign: 'start',
								backgroundColor: COLORS,
							}
						}
					}
				});

				$('.operatorTable').DataTable({
					responsive: false,
					ordering: false,
					"scrollY": "400px",
					"scrollCollapse": true,
					"paging": false,
					"scrollX": true,
					"language": {
						url: getDataTablesLanguageUrl(),
					},
					bFilter: false,
					bInfo: false,
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $('body').css("background-color");

				if (background != ('rgb(255, 255, 255)')) {
					$(".buttons-csv").css("color", "white");
				}
			}
		}
	});
}

function totalSatQsos() {
    // using this to change color of legend and label according to background color
    var color = ifDarkModeThemeReturn('white', 'grey');

    $.ajax({
        url: base_url+'index.php/statistics/get_sat',
        type: 'post',
	data: { dateFrom: $('#dateFrom').val(), dateTo: $('#dateTo').val() },
        success: function (data) {
            $(".satsummary").html('');
            if (data.length > 0) {
				$(".satsummary").append('<br /><div style="display: flex;" id="satContainer"><div style="flex: 1;"><canvas id="satChart" width="500" height="500"></canvas></div><div style="flex: 1;" id="satTable"></div></div><br />');

				// appending table to hold the data
				$("#satTable").append('<table style="width:100%" class="sattable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
					'<tr>' +
					'<td>#</td>' +
					'<td>' + lang_gen_satellite + '</td>' +
					'<td>' + lang_statistics_number_of_qso_worked + ' </td>' +
					'</tr>' +
					'</thead>' +
					'<tbody></tbody></table>');

		$('.tabs').removeAttr('hidden');

                var labels = [];
                var dataQso = [];
                var totalQso = Number(0);

                var $myTable = $('.sattable');
                var i = 1;

                // building the rows in the table
                var rowElements = data.map(function (row) {

                    var $row = $('<tr></tr>');

                    var $iterator = $('<td></td>').html(i++);
                    var $type = $('<td></td>').html(row.sat);
                    var $content = '<td><a href="javascript:displaySatQsos(\''+row.sat+'\')">'+row.count+'</a></td>';

                    $row.append($iterator, $type, $content);

                    return $row;
                });

                // finally inserting the rows
                $myTable.append(rowElements);

                $.each(data, function () {
                    labels.push(this.sat);
                    dataQso.push(this.count);
                    totalQso = Number(totalQso) + Number(this.count);
                });

                const COLORS = ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"]
                var ctx = document.getElementById("satChart").getContext('2d');
                var myChart = new Chart(ctx, {
                    plugins: [ChartPieChartOutlabels],
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            borderColor: 'rgba(54, 162, 235, 1)',
                            label: 'Number of QSO\'s worked',
                            data: dataQso,
                            backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499"],
                            borderWidth: 1,
                            labels: labels,
                        }]
                    },

                    options: {
                        layout: {
                            padding: 100
                        },
                        title: {
                            fontColor: color,
                            fullSize: true,
                        },
                        responsive: false,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false,
                                labels: {
                                    boxWidth: 15,
                                    color: color,
                                    font: {
                                        size: 14,
                                    }
                                },
                                position: 'right',
                                align: "middle"
                            },
                            outlabels: {
                                display: function(context) { // Hide labels with low percentage
                                    return ((context.dataset.data[context.dataIndex] / totalQso * 100) > 1)
                                },
                                backgroundColor: COLORS,
                                borderColor: COLORS,
                                borderRadius: 2, // Border radius of Label
                                borderWidth: 2, // Thickness of border
                                color: 'white',
                                stretch: 10,
                                padding: 0,
                                font: {
                                    resizable: true,
                                    minSize: 12,
                                    maxSize: 25,
                                    family: Chart.defaults.font.family,
                                    size: Chart.defaults.font.size,
                                    style: Chart.defaults.font.style,
                                    lineHeight: Chart.defaults.font.lineHeight,
                                },
                                zoomOutPercentage: 100,
                                textAlign: 'start',
                                backgroundColor: COLORS,
                              }
                        }
                    }
                });

                // using this to change color of csv-button if dark mode is chosen
                var background = $('body').css("background-color");

                if (background != ('rgb(255, 255, 255)')) {
                    $(".buttons-csv").css("color", "white");
                }

                $('.sattable').DataTable({
                    responsive: false,
                    ordering: false,
                    "scrollY": "330px",
                    "scrollX": true,
                    "language": {
                        url: getDataTablesLanguageUrl(),
                    },
                    "ScrollCollapse": true,
                    "paging": false,
                    bFilter: false,
                    bInfo: false,
                });
            }
        }
    });
}

var modalloading=false;
function displaySatQsos(sat,mode) {
	if (!(modalloading)) {
		var ajax_data = ({
			'Sat': sat,
			'Mode': mode,
			'dateFrom': $("#dateFrom").val(),
			'dateTo': $("#dateTo").val(),
		})
		modalloading=true;
		$.ajax({
			url: base_url + 'index.php/statistics/sat_qsos_ajax',
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
