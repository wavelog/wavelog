totalSatQsos();
totalQsosPerYear();

var activeTab='totalQsosPerYear()';

$("a[href='#satellite']").on('shown.bs.tab', function(e) {
	totalSatQsos();
	activeTab='totalSatQsos()';
	$(".sattable").DataTable().columns.adjust();
	$("#yr").show();
});

$("a[href='#sattab']").on('shown.bs.tab', function(e) {
	activeTab='totalSatQsos()';
	totalSatQsos();
	$("#yr").show();
});

$("a[href='#home']").on('shown.bs.tab', function(e) {
	activeTab='totalQsosPerYear()';
	totalQsosPerYear();
	$("#yr").hide();
});

$("a[href='#yearstab']").on('shown.bs.tab', function(e) {
	activeTab='totalQsosPerYear()';
	totalQsosPerYear();
	$("#yr").hide();
});

$("a[href='#bandtab']").on('shown.bs.tab', function(e) {
	totalBandQsos();
	activeTab='totalBandQsos()'
	$("#yr").show();
});

$("a[href='#modetab']").on('shown.bs.tab', function(e) {
	totalModeQsos();
	activeTab='totalModeQsos()'
	$("#yr").show();
});

$("a[href='#qsotab']").on('shown.bs.tab', function(e) {
	totalQsos();
	activeTab='totalQsos()'
	$("#yr").show();
});

$("a[href='#operatorstab']").on('shown.bs.tab', function(e) {
	totalOperatorQsos();
	activeTab='totalOperatorQsos()'
	$("#yr").show();
});

$("a[href='#satqsostab']").on('shown.bs.tab', function(e) {
	totalSatQsosC();
	activeTab='totalSatQsosC()'
	$("#yr").show();
});

$("a[href='#uniquetab']").on('shown.bs.tab', function(e) {
	uniqueCallsigns();
	activeTab='uniqueCallsigns()'
	$("#yr").show();
});

$("a[href='#satuniquetab']").on('shown.bs.tab', function(e) {
	uniqueSatCallsigns();
	activeTab='uniqueSatCallsigns()'
	$("#yr").show();
});

$("#yr").on('change',function(e) {
	eval(activeTab);
});

function uniqueSatCallsigns() {
    $.ajax({
        url: base_url+'index.php/statistics/get_unique_sat_callsigns',
        type: 'post',
	data: { yr: $("#yr option:selected").val() },
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
	data: { yr: $("#yr option:selected").val() },
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
	data: { yr: $("#yr option:selected").val() },
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
	data: { yr: $("#yr option:selected").val() },
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
		data: { yr: $("#yr option:selected").val() },
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
							label: lang_statistics_number_of_qso_worked_each_year,
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

function totalModeQsos() {
	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');
	$.ajax({
		url: base_url+'index.php/statistics/get_mode',
		type: 'post',
		data: { yr: $("#yr option:selected").val() },
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
		data: { yr: $("#yr option:selected").val() },
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
		data: { yr: $("#yr option:selected").val() },
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
	data: { yr: $("#yr option:selected").val() },
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
                    var $content = $('<td></td>').html(row.count);

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
