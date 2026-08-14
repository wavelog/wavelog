function accumulatePlot(form) {
	$(".ld-ext-right").addClass("running");
	$(".ld-ext-right").prop("disabled", true);

	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn("white", "grey");

	var award = form.award.value;
	var mode = form.mode.value;
	var period = form.periodradio.value;
	var propmode = form.propmode.value;
	$.ajax({
		url: base_url + "index.php/accumulated/get_accumulated_data",
		type: "post",
		data: {
			Band: form.band.value,
			Award: award,
			Mode: mode,
			Propmode: propmode,
			Period: period,
		},
		success: function (data) {
			if (!$.trim(data)) {
				$("#accumulateContainer").empty();
				$("#accumulateContainer").append(
					'<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Nothing found!</div>'
				);
				$(".ld-ext-right").removeClass("running");
				$(".ld-ext-right").prop("disabled", false);
			} else {
				// used for switching award text in the table and the chart
				switch (award) {
					case "dxcc":
						var awardtext = lang_statistics_accumulated_worked_dxcc;
						break;
					case "was":
						var awardtext =
							lang_statistics_accumulated_worked_states;
						break;
					case "iota":
						var awardtext = lang_statistics_accumulated_worked_iota;
						break;
					case "waz":
						var awardtext =
							lang_statistics_accumulated_worked_cqzone;
						break;
					case "vucc":
						var awardtext =
							lang_statistics_accumulated_worked_vucc;
						break;
					case "waja":
						var awardtext =
							lang_statistics_accumulated_worked_waja;
						break;
				}

				var periodtext = lang_general_word_year;
				if (period == "month") {
					periodtext =
						lang_general_word_year +
						" + " +
						lang_general_word_month;
				}
				// removing the old chart so that it will not interfere when loading chart again
				$("#accumulateContainer").empty();
				$("#accumulateContainer").append(
					'<canvas id="myChartAccumulate" width="400" height="150"></canvas><div id="accumulateTable"></div>'
				);

				// appending table to hold the data
				$("#accumulateTable").append(
					'<table style="width:100%" class="accutable table table-sm table-bordered table-hover table-striped table-condensed text-center"><thead>' +
						"<tr>" +
						"<th>#</th>" +
						"<th>" +
						periodtext +
						"</th>" +
						"<th>" +
						awardtext +
						"</th>" +
						"<th>" +
						lang_general_word_diff +
						"</th>" +
						"</tr>" +
						"</thead>" +
						"<tbody></tbody></table>"
				);
				var labels = [];
				var dataDxcc = [];

				var $myTable = $(".accutable");
				var i = 1;
				var last_total = 0;

				// building the rows in the table
				var rowElements = data.map(function (row) {
					var $row = $("<tr></tr>");

					var $iterator = $("<td></td>").html(i++);
					var $type = $("<td></td>").html(row.year);
					var $content = $("<td></td>").html(row.total);
					diff = row.total - last_total;
					var $diff = $("<td></td>").html((last_total == 0 || diff == 0) ? '' : "+"+diff);
					last_total = row.total;

					$row.append($iterator, $type, $content, $diff);

					return $row;
				});

				// finally inserting the rows
				$myTable.append(rowElements);

				$.each(data, function () {
					labels.push(this.year);
					dataDxcc.push(this.total);
				});

				var ctx = document
					.getElementById("myChartAccumulate")
					.getContext("2d");
				var headerperiod;
				if (period == "year") {
					headerperiod = lang_general_word_yearly;
				} else if (period == "month") {
					headerperiod = lang_general_word_monthly;
				} else {
					headerperiod = "n/a";
				}
				var myChart = new Chart(ctx, {
					type: "bar",
					data: {
						labels: labels,
						datasets: [
							{
								label: awardtext + " (" + headerperiod + ")",
								data: dataDxcc,
								backgroundColor: "rgba(54, 162, 235, 0.2)",
								borderColor: "rgba(54, 162, 235, 1)",
								borderWidth: 2,
								color: color,
							},
						],
					},
					options: {
						scales: {
							y: {
								ticks: {
									beginAtZero: true,
									color: color,
								},
							},
							x: {
								ticks: {
									color: color,
								},
							},
						},
						plugins: {
							legend: {
								labels: {
									color: color,
								},
							},
						},
					},
				});
				$(".ld-ext-right").removeClass("running");
				$(".ld-ext-right").prop("disabled", false);
				$.fn.dataTable.ext.buttons.clear = {
					className: 'buttons-clear',
					action: function ( e, dt, node, config ) {
						dt.search('');
						dt.order([[1, 'desc']]);
						dt.draw();
					}
				};
				$(".accutable").DataTable({
					responsive: false,
					scrollY: "400px",
					scrollCollapse: true,
					paging: false,
					scrollX: true,
					sortable: true,
					language: {
						url: getDataTablesLanguageUrl(),
					},
					dom: "Bfrtip",
					order: [1, 'desc'],
					buttons: [
						{
							extend: 'csv'
						},
						{
							extend: 'clear',
							text: lang_admin_clear
						}
					]
				});

				// using this to change color of csv-button if dark mode is chosen
				var background = $("body").css("background-color");

				if (background != "rgb(255, 255, 255)") {
					$(".buttons-csv").css("color", "white");
				}
			}
		},
	});
}

$('#band').change(function(){
	var band = $("#band option:selected").text();
	if (band == "SAT") {
        	$('#propmode').val('SAT');
	}
});
