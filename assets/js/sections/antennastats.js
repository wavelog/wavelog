var elevationChart;
var azimuthChart;
var azimuthView = 'map';   // 'map' | 'radar'
var lastAzCounts = null;   // last histogram, so switching views doesn't refetch

$('#band').change(function () {
	var isSat = ($('#band').val() === "SAT");
	if (isSat) {
		$(".sats_dropdown").removeAttr("hidden");
		$(".orbits_dropdown").removeAttr("hidden");
	} else {
		$(".sats_dropdown").attr("hidden", true);
		$(".orbits_dropdown").attr("hidden", true);
	}
	// the sat/orbit filters only apply to satellite QSOs, so clear them when
	// leaving SAT and restore the all-selected default when coming back
	$('#sat').val('All');
	if ($('#orbit').length) {
		$('#orbit').find('option').prop('selected', isSat);
		$('#orbit').multiselect('refresh');
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
	let band = $('#band').val();
	let isSat = (band === 'SAT');
	let selectedOrbits = $('#orbit').val() || [];
	if (isSat && selectedOrbits.length === 0) {
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

	let postData = {
		'band': band,
		'mode': $('#mode').val()
	};
	// only send the satellite-only filters when actually querying satellite QSOs
	if (isSat) {
		postData.sat = $('#sat').val();
		postData.orbit = selectedOrbits;
	}

	$.ajax({
		url: base_url + 'index.php/statistics/get_azimuth_data',
		type: 'post',
		data: postData,
		success: function (tmp) {
			var dataQso = [];
			for (let i=0; i<360; i++) {
				dataQso.push(0);
			}
			$.each(tmp, function () {
				dataQso[((this.azimuth % 360) + 360) % 360] += +this.qsos || 0; // JSON may deliver the counts as strings; wrap out-of-range bearings into 0-359
			});

			lastAzCounts = dataQso;
			renderAzimuthView();
		}
	});
}

// Draw whichever azimuth view (map or radar) is currently selected
function renderAzimuthView() {
	if (!lastAzCounts) return;
	if (azimuthView === 'radar') {
		renderAzimuthRadar(lastAzCounts);
	} else {
		renderAzimuthMap(lastAzCounts);
	}
}

// The original Chart.js radar plot, kept as an alternative view
function renderAzimuthRadar(counts) {
	if (azimuthChart) {
		azimuthChart.destroy();
	}
	var labels = [];
	for (let i = 0; i < 360; i++) {
		labels.push(i);
	}

	// using this to change color of legend and label according to background color
	var color = ifDarkModeThemeReturn('white', 'grey');
	var ctx = document.getElementById("azimuthchart").getContext('2d');
	azimuthChart = new Chart(ctx, {
		type: 'radar',
		data: {
			labels: labels,
			datasets: [{
				label: '# QSOs',
				data: counts,
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
					pointLabels: {
						callback: (label, index) => {
							// Show labels only for every 10 degrees
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
}

/*
 * Azimuth tab map — the per-degree QSO histogram drawn as a radar polygon
 * over an azimuthal equidistant world map centered on the active station
 * location, so beam directions line up with real geography.
 */
var antAzLand = null; // cached ne_110m_land geojson

function ant_az_theme() {
	if (isDarkModeTheme()) {
		return { ocean: '#212930', land: '#5d6d7a', landStroke: '#141a1f',
			graticule: '#46535c', text: '#dee2e6', sphere: '#8a979f', center: '#dc3545' };
	}
	return { ocean: '#ffffff', land: '#c3ccd4', landStroke: '#ffffff',
		graticule: '#c4ccd3', text: '#212529', sphere: '#5c676e', center: '#dc3545' };
}

// Inline-styled text (beats the `svg text` rules in the themes' overrides.css)
function ant_az_text(svg, x, y, text, font, fill, opts) {
	opts = opts || {};
	return svg.append('text')
		.attr('x', x).attr('y', y)
		.attr('dy', opts.dy || '0.35em')
		.attr('text-anchor', opts.anchor || 'middle')
		.style('font-size', font + 'px')
		.style('font-weight', opts.weight || 'normal')
		.style('fill', fill)
		.text(text);
}

function renderAzimuthMap(counts) {
	var center = (typeof ant_center !== 'undefined' && ant_center && ant_center.lat !== null)
		? ant_center : { lat: 0, lng: 0 };

	function draw() {
		var wrap = document.getElementById('azimuthal_wrap');
		var size = Math.min(wrap.clientWidth || 900, 900);
		if (size < 100) size = 600;
		var t = ant_az_theme();
		var pad = 52; // room for the bearing labels around the rim

		var svg = d3.select('#azimuthal_svg');
		svg.selectAll('*').remove();
		svg.attr('width', size)
			.attr('height', size)
			.attr('viewBox', '0 0 ' + size + ' ' + size)
			.attr('font-family', 'Arial, Helvetica, sans-serif');

		var proj = d3.geoAzimuthalEquidistant()
			.rotate([-center.lng, -center.lat])
			.clipAngle(179.9)
			.fitExtent([[pad, pad], [size - pad, size - pad]], { type: 'Sphere' });
		var path = d3.geoPath(proj);
		var b = path.bounds({ type: 'Sphere' });
		var cx = (b[0][0] + b[1][0]) / 2, cy = (b[0][1] + b[1][1]) / 2, R = (b[1][0] - b[0][0]) / 2;

		// Ocean disk + rim
		svg.append('path')
			.attr('d', path({ type: 'Sphere' }))
			.attr('fill', t.ocean)
			.attr('stroke', t.sphere)
			.attr('stroke-width', 1.5);

		// Land (Natural Earth 1:110m)
		svg.append('g')
			.selectAll('path')
			.data(antAzLand.features)
			.join('path')
			.attr('d', path)
			.attr('fill', t.land)
			.attr('stroke', t.landStroke)
			.attr('stroke-width', 0.4);

		// 30° graticule (kept light so the histogram stays readable)
		svg.append('path')
			.attr('d', path(d3.geoGraticule().step([30, 30])()))
			.attr('fill', 'none')
			.attr('stroke', t.graticule)
			.attr('stroke-width', 0.4);

		var maxCount = Math.max.apply(null, counts);

		// Radial guides (25 / 50 / 75 % of the peak) with QSO-count labels
		var guideFont = Math.max(9, size / 80);
		var ga = -45 * Math.PI / 180; // label positions towards SE
		[0.25, 0.5, 0.75].forEach(function (f) {
			var rg = R * 0.97 * f;
			svg.append('circle')
				.attr('cx', cx).attr('cy', cy).attr('r', rg)
				.attr('fill', 'none')
				.attr('stroke', t.graticule)
				.attr('stroke-width', 0.4)
				.attr('stroke-dasharray', '2 4');
			if (maxCount > 0) {
				ant_az_text(svg, cx + Math.cos(ga) * rg + 4, cy - Math.sin(ga) * rg,
					String(Math.round(maxCount * f)), guideFont, t.text, { anchor: 'start' });
			}
		});

		// The QSO-per-degree histogram as a radar polygon over the map
		if (maxCount > 0) {
			var pts = [];
			for (var i = 0; i < 360; i++) {
				var ang = (90 - i) * Math.PI / 180;
				var r = (counts[i] / maxCount) * R * 0.97;
				pts.push((cx + Math.cos(ang) * r).toFixed(1) + ',' + (cy - Math.sin(ang) * r).toFixed(1));
			}
			svg.append('polygon')
				.attr('points', pts.join(' '))
				.attr('fill', 'rgba(54, 162, 235, 0.35)')
				.attr('stroke', 'rgba(54, 162, 235, 1)')
				.attr('stroke-width', 1.5);
		}

		// Invisible hover wedges — one per degree, so hovering any direction
		// reports its azimuth and QSO count
		var hoverG = svg.append('g');
		for (var d1 = 0; d1 < 360; d1++) {
			var w1 = (90 - d1) * Math.PI / 180;
			var w2 = (90 - (d1 + 1)) * Math.PI / 180;
			var wr = R * 0.97;
			hoverG.append('path')
				.attr('d', 'M' + cx + ',' + cy
					+ ' L' + (cx + Math.cos(w1) * wr).toFixed(1) + ',' + (cy - Math.sin(w1) * wr).toFixed(1)
					+ ' L' + (cx + Math.cos(w2) * wr).toFixed(1) + ',' + (cy - Math.sin(w2) * wr).toFixed(1) + ' Z')
				.attr('fill', 'none')
				.style('pointer-events', 'all')
				.append('title').text(d1 + '° · ' + counts[d1] + ' QSOs');
		}

		// Markers every 5° where data exists — radius scales with the count
		if (maxCount > 0) {
			var markG = svg.append('g');
			for (var d5 = 0; d5 < 360; d5 += 5) {
				if (!counts[d5]) continue;
				var am = (90 - d5) * Math.PI / 180;
				var rm = (counts[d5] / maxCount) * R * 0.97;
				markG.append('circle')
					.attr('cx', (cx + Math.cos(am) * rm).toFixed(1))
					.attr('cy', (cy - Math.sin(am) * rm).toFixed(1))
					.attr('r', (1.5 + 2.5 * Math.sqrt(counts[d5] / maxCount)).toFixed(2))
					.attr('fill', 'rgba(54, 162, 235, 1)')
					.attr('stroke', t.ocean)
					.attr('stroke-width', 0.5)
					.append('title').text(d5 + '° · ' + counts[d5] + ' QSOs');
			}
		}

		// Bearing ticks and labels (000 = N up, 090 = E right)
		var tickFont = Math.max(13, size / 36);
		for (var deg10 = 0; deg10 < 360; deg10 += 10) {
			var a = (90 - deg10) * Math.PI / 180;
			var major = (deg10 % 30 === 0);
			var r0 = major ? R * 0.95 : R * 0.965;
			var r1 = major ? R : R * 0.985;
			svg.append('line')
				.attr('x1', cx + Math.cos(a) * r0).attr('y1', cy - Math.sin(a) * r0)
				.attr('x2', cx + Math.cos(a) * r1).attr('y2', cy - Math.sin(a) * r1)
				.attr('stroke', t.sphere)
				.attr('stroke-width', major ? 1.4 : 0.7);
			if (major) {
				ant_az_text(svg, cx + Math.cos(a) * R * 1.05, cy - Math.sin(a) * R * 1.05,
					String(deg10).padStart(3, '0'), tickFont, t.text, { weight: 'bold' });
			}
		}

		// Center marker (the station)
		svg.append('circle')
			.attr('cx', cx).attr('cy', cy)
			.attr('r', 3.5)
			.attr('fill', t.center)
			.attr('stroke', t.ocean)
			.attr('stroke-width', 1.5);
		if (typeof ant_homegrid !== 'undefined' && ant_homegrid) {
			ant_az_text(svg, cx, cy, ant_homegrid, Math.max(12, size / 45), t.text, { dy: '-8', weight: 'bold' });
		}

		// Legend info: peak bearing (+ compass point) and the map center
		var compass = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
		if (maxCount > 0) {
			var peakDeg = counts.indexOf(maxCount);
			$('#azimuthal_peak').text('Peak: ' + maxCount.toLocaleString() + ' QSOs at ' + peakDeg + '° (' + compass[Math.round(peakDeg / 22.5) % 16] + ')');
		} else {
			$('#azimuthal_peak').text('No azimuth data for this filter');
		}
		$('#azimuthal_center').text((typeof ant_homegrid !== 'undefined' && ant_homegrid) ? 'Center: ' + ant_homegrid : '');
	}

	if (antAzLand) {
		draw();
	} else {
		$.getJSON(base_url + 'assets/json/geojson/ne_110m_land.geojson', function (gj) {
			antAzLand = gj;
			draw();
		}).fail(function () {
			showToast('Error', 'Failed to load the land geometry (ne_110m_land.geojson).', 'bg-danger text-white', 5000);
		});
	}
}


$(document).ready(function () {
	$('#azimuth_view_toggle button').on('click', function () {
		$('#azimuth_view_toggle button').removeClass('active');
		$(this).addClass('active');
		azimuthView = this.dataset.view;
		var showMap = (azimuthView === 'map');
		$('#azimuthal_wrap').attr('hidden', !showMap);
		$('#azimuthchart').attr('hidden', showMap);
		renderAzimuthView(); // re-render from the cached histogram
	});

	if ($('#orbit').length) {
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
	}

	if ($('#orbitel').length) {
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
	}
})
