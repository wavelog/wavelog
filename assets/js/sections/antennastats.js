var azimuthChart;
var elevationChart;

$('#band').change(function () {
	var band = $("#band option:selected").text();
	if (band != "SAT") {
		$(".sats_dropdown").attr("hidden", true);
		$(".orbits_dropdown").attr("hidden", true);
	} else {
		$(".sats_dropdown").removeAttr("hidden");
		$(".orbits_dropdown").removeAttr("hidden");
	}
});

function plot_satel() {
	if (elevationChart) {
		elevationChart.destroy();
	}
	let selectedOrbits = $('#orbitel').val();
	if (Array.isArray(selectedOrbits) && selectedOrbits.length === 0) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: 'You need to select at least one orbit type location to do a search!',
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return false;
	}

	$.ajax({
		url: base_url + 'index.php/statistics/get_elevation_data',
		type: 'post',
		data: {
			'sat': $('#satel').val(),
			'orbit': $('#orbitel').val()
		},
		success: function (tmp) {
			var labels = [];
			var dataQso = [];
			$.each(tmp, function () {
				labels.push(this.elevation);
				dataQso.push(this.qsos);
			});

			// using this to change color of legend and label according to background color
			var color = ifDarkModeThemeReturn('white', 'grey');
			var ctx = document.getElementById("elevationchart").getContext('2d');
			elevationChart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [{
						label: '# QSOs for elevation',
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
							title: {
								display: true,
								text: '# QSOs',
								font: {
									size: 15
								},
								color: color
							},
							ticks: {
								beginAtZero: true,
								color: color,
								stepSize: 1
							}
						},
						x: {
							title: {
								display: true,
								text: 'Elevation',
								font: {
									size: 15
								},
								color: color
							},
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
						},
						tooltip: {
							callbacks: {
								title: function(context) {
									return context[0].label+"° elevation";
								}
							}
						},

					}
				}
			});

			// using this to change color of csv-button if dark mode is chosen
			var background = $('body').css("background-color");

			if (background != ('rgb(255, 255, 255)')) {
				$(".buttons-csv").css("color", "white");
			}
		}
	});
}

function plot_azimuth() {
	if (azimuthChart) {
		azimuthChart.destroy();
	}
	let band = $('#band').val();
	let selectedOrbits = $('#orbit').val();
	if (band == 'SAT' && Array.isArray(selectedOrbits) && selectedOrbits.length === 0) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: 'You need to select at least one orbit type location to do a search!',
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return false;
	}

	$.ajax({
		url: base_url + 'index.php/statistics/get_azimuth_data',
		type: 'post',
		data: {
			'band': $('#band').val(),
			'mode': $('#mode').val(),
			'sat': $('#sat').val(),
			'orbit': $('#orbit').val()
		},
		success: function (tmp) {
			var dataQso = [];
			var labels = [];
			for (let i=0; i<360; i++) {
				labels.push(i);
				dataQso.push(0);
			}
			$.each(tmp, function () {
				dataQso[this.azimuth] = this.qsos;
			});

			// using this to change color of legend and label according to background color
			var color = ifDarkModeThemeReturn('white', 'grey');
			var ctx = document.getElementById("azimuthchart").getContext('2d');
			azimuthChart = new Chart(ctx, {
				type: 'radar',
				data: {
					labels: labels,
					datasets: [{
						label: '# QSOs',
						data: dataQso,
						backgroundColor: 'rgba(54, 162, 235, 1)',
						borderColor: 'rgba(54, 162, 235, 1)',
						borderWidth: 2,
						color: color
					}]
				},
				options: {
					plugins: {
						legend: {
							labels: {
								color: color
							}
						},
						tooltip: {
							callbacks: {
								title: function(context) {
									return context[0].label+"° azimuth";
								}
							}
						},
					},
					scales: {
						r: { // Radial scale (angle and radius)
							// startAngle: -Math.PI / 2, // Start at the top (default is 0, which starts at the right)
							pointLabels: {
								callback: (label, index) => {
									// Show labels only for every 5 degrees
									return label % 10 === 0 ? `${label}°` : '';
								},
								color: color
							},
							grid: {
								circular: true, // Show circular grid lines
								color: color
							}
						}
					}
				},
			});

			// using this to change color of csv-button if dark mode is chosen
			var background = $('body').css("background-color");

			if (background != ('rgb(255, 255, 255)')) {
				$(".buttons-csv").css("color", "white");
			}
		}
	});
}


$(document).ready(function () {
   $('#orbit').multiselect({
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
      filterPlaceholder: lang_general_word_search,
      templates: {
         button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
      },
      numberDisplayed: 1,
      inheritClass: true,
      includeSelectAllOption: true
   });


   $('#orbitel').multiselect({
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
      filterPlaceholder: lang_general_word_search,
      templates: {
         button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
      },
      numberDisplayed: 1,
      inheritClass: true,
      includeSelectAllOption: true
   });

})
