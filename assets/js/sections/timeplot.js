function timeplot(form) {
	$(".ld-ext-right").addClass('running');
	$(".ld-ext-right").prop('disabled', true);
	$(".alert").remove();
	$.ajax({
		url: base_url+'index.php/timeplotter/getTimes',
		type: 'post',
		data: {'band': form.band.value, 'dxcc': form.dxcc.value, 'cqzone': form.cqzone.value, 'mode': form.mode.value},
		success: function(tmp) {
			$(".ld-ext-right").removeClass('running');
			$(".ld-ext-right").prop('disabled', false);
			if (tmp.ok == 'OK') {
				$('#timeplotterTabs').show();
				plotTimeplotterChart(tmp);
				updateSummaryCards(tmp, form);
				renderHeatmap(tmp);
			}
			else {
				$("#container").remove();
				$("#info").remove();
				$("#timeplotter_div").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>\n' +
					tmp.error +
					'</div>');
				$('#timeplotter-summary').hide();
				$('#timeplotter-heatmap-card').hide();
			}
		}
	});
}

function updateSummaryCards(tmp, form) {
	if (!tmp.qsodata || tmp.qsodata.length === 0) {
		$('#timeplotter-summary').hide();
		return;
	}

	// Use backend-calculated best window
	if (tmp.best_window && tmp.best_window.label) {
		$('#summary-best-window').text(tmp.best_window.label);
		$('#summary-best-window-count').text(formatCount(tmp.best_window.total));
	} else {
		$('#summary-best-window').text('-');
		$('#summary-best-window-count').text('');
	}

	// Use backend-calculated best band
	if (tmp.best_band && tmp.best_band.value) {
		$('#summary-best-band').text(tmp.best_band.value);
		$('#summary-best-band-count').text(formatCount(tmp.best_band.total));
	} else {
		var bandText = form.band.value === 'All' ? 'All Bands' : form.band.value;
		$('#summary-best-band').text(bandText);
		$('#summary-best-band-count').text('');
	}

	// Use backend-calculated best mode
	if (tmp.best_mode && tmp.best_mode.value) {
		$('#summary-best-mode').text(tmp.best_mode.value);
		$('#summary-best-mode-count').text(formatCount(tmp.best_mode.total));
	} else {
		var modeText = form.mode.value === 'All' ? 'All Modes' : form.mode.value.toUpperCase();
		$('#summary-best-mode').text(modeText);
		$('#summary-best-mode-count').text('');
	}

	// Total QSOs
	$('#summary-total-qsos').text(tmp.qsocount);

	$('#timeplotter-summary').show();
}

function renderHeatmap(tmp) {
	if (!tmp.hourly_by_band || Object.keys(tmp.hourly_by_band).length === 0) {
		$('#timeplotter-heatmap-card').hide();
		return;
	}

	var fallbackGrid = $('#timeplotterHeatmap');
	fallbackGrid.empty();
	fallbackGrid.show();
	$('#timeplotter-heatmap-card').show();

	// Set grid to have label column + 24 hour columns
	fallbackGrid.css('grid-template-columns', 'auto repeat(24, minmax(0, 1fr))');

	// Add empty spacer and hour headers
	var spacer = $('<div class="heatmap-row-label"></div>');
	spacer.html('&nbsp;');
	fallbackGrid.append(spacer);

	for (var h = 0; h < 24; h++) {
		var header = $('<div class="heatmap-header"></div>');
		header.text(pad(h));
		fallbackGrid.append(header);
	}

	// Calculate max across all bands for color scaling
	var allValues = [];
	$.each(tmp.hourly_by_band, function(_, hours) {
		$.each(hours, function(_, count) {
			allValues.push(count);
		});
	});
	var max = Math.max.apply(null, allValues);

	// Add rows per band
	$.each(tmp.hourly_by_band, function(band, hours) {
		// Band label
		var labelCell = $('<div class="heatmap-row-label"></div>');
		labelCell.text(band);
		fallbackGrid.append(labelCell);

		// Hour cells for this band
		$.each(hours, function(h, count) {
			var cell = $('<div class="heatmap-cell"></div>');
			cell.attr('title', band + ' @ ' + pad(h) + ':00 UTC - ' + count + ' QSOs');
			cell.text(count > 0 ? count : '');

			var intensity = max > 0 ? Math.min(1, count / max) : 0;
			if (intensity > 0) {
				if (intensity < 0.25) {
					cell.addClass('glanceyear-legend-1');
				} else if (intensity < 0.5) {
					cell.addClass('glanceyear-legend-2');
				} else if (intensity < 0.75) {
					cell.addClass('glanceyear-legend-3');
				} else {
					cell.addClass('glanceyear-legend-4');
				}
			}

			fallbackGrid.append(cell);
		});
	});
}

function pad(value) {
	value = parseInt(value, 10);
	if (isNaN(value)) return value;
	return value < 10 ? '0' + value : '' + value;
}

function formatCount(count) {
	if (typeof count === 'undefined' || count === null) {
		return '';
	}
	var value = parseInt(count, 10) || 0;
	return value + ' QSO' + (value === 1 ? '' : 's');
}

// Store chart data for re-rendering when tab switches
var storedChartData = null;
var storedChartInstance = null;

function plotTimeplotterChart(tmp) {
	// Store data for re-rendering
	storedChartData = tmp;

	$("#container").remove();
	$("#info").remove();
	$("#timeplotter_div").append('<div id="container" style="height: 600px;"></div>');
	var color = ifDarkModeThemeReturn('white', 'grey');

	// Build categories from time slots
	var categories = [];
	$.each(tmp.qsodata, function(){
		categories.push(this.time);
	});

	var options = {
		chart: {
			type: 'column',
			zoomType: 'xy',
			renderTo: 'container',
			backgroundColor: getBodyBackground()
		},
		title: {
			text: lang_statistics_timeplotter_chart_header,
			style: {
				color: color
			}
		},
		xAxis: {
			categories: categories,
			crosshair: true,
			type: "category",
			min: 0,
			max: 47,
			labels: {
				style: {
					color: color
				}
			}
		},
		yAxis: {
			title: {
				text: lang_statistics_timeplotter_number_of_qsos,
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
		plotOptions: {
			column: {
				stacking: 'normal',
				pointPadding: 0,
				borderWidth: 0
			}
		},
		tooltip: {
			formatter: function () {
				if(this.point) {
					return '<strong>' + this.series.name + '</strong><br />' +
						lang_general_word_time + ": " + this.x + '<br />' +
						lang_statistics_timeplotter_number_of_qsos + ": <strong>" + this.y + "</strong>";
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

	// Create one series per band
	if (tmp.by_band && Object.keys(tmp.by_band).length > 0) {
		$.each(tmp.bands, function(_, band) {
			var bandData = tmp.by_band[band];
			var series = {
				name: band,
				data: bandData
			};
			options.series.push(series);
		});
	} else {
		// Fallback to original single series if no band data
		var series = {
			name: lang_statistics_timeplotter_number_of_qsos,
			data: []
		};
		$.each(tmp.qsodata, function(){
			series.data.push(this.count);
		});
		options.series.push(series);
	}

	storedChartInstance = new Highcharts.Chart(options);
}

// Handle tab switching to re-render chart when chart tab is shown
$(document).ready(function() {
	$('#chart-tab').on('shown.bs.tab', function() {
		if (storedChartData && storedChartInstance) {
			// Reflow the chart to adjust to visible container
			if (storedChartInstance.reflow) {
				storedChartInstance.reflow();
			}
		}
	});
});
