$('#distplot_bands').change(function(){
	var band = $("#distplot_bands option:selected").text();
	if (band != "SAT") {
		$("#distplot_sats").hide();
		$("#orbits").hide();
		$("#satslabel").hide();
		$("#orbitslabel").hide();
		$("#distplot_sats").val('All');
		$("#orbits").val('All');
	} else {
		$("#distplot_sats").show();
		$("#orbits").show();
		$("#orbitslabel").show();
		$("#satslabel").show();
	}
});

function distPlot(form) {
	$(".ld-ext-right-plot").addClass('running');
    $(".ld-ext-right-plot").prop('disabled', true);
    $('#plot').prop("disabled", true);
	$(".alert").remove();
	var baseURL= "<?php echo base_url();?>";
	$.ajax({
		url: base_url+'index.php/distances/get_distances',
		type: 'post',
		data: {'band': form.distplot_bands.value,
			'sat': form.distplot_sats.value,
			'orbit': form.orbits.value,
			'propagation': form.propmode.value
		},
		success: function(tmp) {
			if (tmp.ok == 'OK') {
				if (!($('#information').length > 0))
					$("#distances_div").append('<div id="information"></div><div id="graphcontainer" style="height: 600px; margin: 0 auto"></div>');
				var color = ifDarkModeThemeReturn('white', 'grey');
				var options = {
					chart: {
						type: 'column',
						zoomType: 'xy',
						renderTo: 'graphcontainer',
						backgroundColor: getBodyBackground()
					},
					title: {
						text: lang_statistics_distances_worked,
						style: {
							color: color
						}
					},
					xAxis: {
						categories: [],
						crosshair: true,
						type: "category",
						min:0,
						max:100,
						labels: {
							style: {
								color: color
							}
						}
					},
					yAxis: {
						title: {
							text: lang_statistics_distances_number_of_qsos,
							style: {
								color: color
							}
						},
						labels: {
							style: {
								color: color
							}
						}
					},
					navigator: {
						enabled: true,
						xAxis: {
							labels: {
								formatter: function() {
									return this.value * '50' + ' ' + tmp.unit;
								},
								style: {
									color: color
								}
							}
						}
					},
					rangeSelector: {
						selected: 1
					},
					tooltip: {
						formatter: function () {
							if(this.point) {
								return lang_gen_hamradio_distance + ": " + options.xAxis.categories[this.point.x] +
									"<br />" + lang_statistics_distances_callsigns_worked + ": " + myComments[this.point.x] +
									"<br />" + lang_statistics_distances_number_of_qsos + ": <strong>" + series.data[this.point.x] + "</strong>";
							}
						}
					},
					legend: {
						itemStyle: {
							color: color
						}
					},
					series: []
				};
				var myComments=[];

				var series = {
					data: [],
					showInNavigator: true,
					point: {
						events: {
							click: function () {
								getDistanceQsos(this.category);

							}
						}
					}
				};

				$.each(tmp.qsodata, function(){
					myComments.push(this.calls);
					options.xAxis.categories.push(this.dist);
					series.name = lang_statistics_distances_number_of_qsos;
					series.data.push(this.count);
				});

				options.series.push(series);

				$('#information').html(tmp.qrb.Qsos + " " + lang_statistics_distances_part1_contacts_were_plotted_furthest + " " + tmp.qrb.Callsign
					+ " " + lang_statistics_distances_part2_contacts_were_plotted_furthest + " " + tmp.qrb.Grid
					+". " + lang_statistics_distances_part3_contacts_were_plotted_furthest + " "
					+ tmp.qrb.Distance + tmp.unit + ".");

				var chart = new Highcharts.Chart(options);
			}
			else {
				if (($('#information').length > 0)) {
					$("#information").remove();
					$("#graphcontainer").remove();
				}
				$("#distances_div").append('<div class="alert alert-danger" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' + tmp.Error + '</div>');
			}
			$(".ld-ext-right-plot").removeClass('running');
            $(".ld-ext-right-plot").prop('disabled', false);
            $('#plot').prop("disabled", false);
		}
	});
}

function getDistanceQsos(distance) {
	$.ajax({
		url: base_url + 'index.php/distances/getDistanceQsos',
		type: 'post',
		data: {
			'distance': distance,
			'band': $("#distplot_bands").val(),
			'sat' : $("#distplot_sats").val(),
			'orbit': $("#orbits").val(),
			'propagation': $("#propmode").val()
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

	var target = document.body;
	var observer = new MutationObserver(function() {
		$('#dt-search-0').on('keyup', function (e) {
			tocrappyzero=$(this).val().toUpperCase().replaceAll(/0/g, 'Ø');
			$(this).val(tocrappyzero);
			$(this).trigger("input");
		});
	});
	var config = { childList: true, subtree: true};
	// pass in the target node, as well as the observer options
	observer.observe(target, config);
});
