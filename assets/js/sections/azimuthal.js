/*
 * Azimuthal Map — full-world azimuthal projection (D3) centered on the
 * active station location's gridsquare.
 *
 * Projections: azimuthal equidistant (default) and Lambert equal-area,
 * both clipped to a full disk (clipAngle ~180°). Land shapes come from
 * the local Natural Earth 1:110m GeoJSON. All styling is applied inline
 * (styles on text, attributes on shapes) so it survives both the themes'
 * `svg text { ... }` overrides in overrides.css and PNG serialization.
 */

let az_land = null;              // fetched ne_110m_land FeatureCollection
let az_mode = 'equidistant';     // 'equidistant' | 'equalarea'
let az_spacing = 5000;           // km between distance rings
let az_show_fields = true;       // Maidenhead field overlay
let az_show_cq = false;          // CQ zone overlay
let az_show_itu = false;         // ITU zone overlay
let az_show_dxcc = false;        // worked DXCC marker overlay
let az_cq = null;                // fetched cqzones FeatureCollection (lazy)
let az_itu = null;               // fetched ituzones FeatureCollection (lazy)

const AZ_KM_PER_DEG = 111.32;    // mean Earth radius 6371 km
const AZ_MAX_SIZE = 900;         // px cap for the square svg

function az_theme() {
	// darkmodehelpers.js is loaded globally on every page
	if (isDarkModeTheme()) {
		return { ocean: '#212930', land: '#5d6d7a', landStroke: '#141a1f',
			graticule: '#46535c', field: '#39454d', ring: '#93a1ab',
			text: '#dee2e6', sphere: '#8a979f', center: '#dc3545',
			cq: '#c9a55c', itu: '#7aa5d0', dxcc: '#2e9e5b' };
	}
	return { ocean: '#ffffff', land: '#c3ccd4', landStroke: '#ffffff',
		graticule: '#c4ccd3', field: '#d3dade', ring: '#77828a',
		text: '#212529', sphere: '#5c676e', center: '#dc3545',
		cq: '#94753a', itu: '#4a7396', dxcc: '#198754' };
}

// Great-circle destination point [lng, lat] from the center, distance in
// degrees of arc along a given initial bearing (used for ring labels).
function az_destination(distDeg, bearingDeg) {
	var r = Math.PI / 180;
	var f1 = az_center.lat * r, l1 = az_center.lng * r, th = bearingDeg * r, d = distDeg * r;
	var sf2 = Math.sin(f1) * Math.cos(d) + Math.cos(f1) * Math.sin(d) * Math.cos(th);
	var f2 = Math.asin(sf2);
	var l2 = l1 + Math.atan2(Math.sin(th) * Math.sin(d) * Math.cos(f1), Math.cos(d) - Math.sin(f1) * sf2);
	return [((l2 / r + 540) % 360) - 180, f2 / r];
}

// Append an SVG <text> with inline styles (inline beats the `svg text`
// rules in the themes' overrides.css; presentation attributes do not).
function az_text(svg, x, y, text, font, fill, opts) {
	opts = opts || {};
	return svg.append('text')
		.attr('x', x).attr('y', y)
		.attr('dy', opts.dy || '0.35em')
		.attr('text-anchor', opts.anchor || 'middle')
		.style('font-size', font + 'px')
		.style('font-weight', opts.weight || 'normal')
		.style('fill', fill)
		.style('opacity', opts.opacity != null ? opts.opacity : 1)
		.text(text);
}

function az_render() {
	if (!az_land) return; // land geometry not fetched yet

	var wrap = document.getElementById('az_map_wrap');
	var size = Math.min(wrap.clientWidth || AZ_MAX_SIZE, AZ_MAX_SIZE);
	if (size < 100) size = 600;

	var t = az_theme();
	var pad = 52; // room for the bearing labels around the rim

	var svg = d3.select('#az_map');
	svg.selectAll('*').remove();
	svg.attr('width', size)
		.attr('height', size)
		.attr('viewBox', '0 0 ' + size + ' ' + size)
		.attr('font-family', 'Arial, Helvetica, sans-serif');

	var proj = (az_mode === 'equalarea') ? d3.geoAzimuthalEqualArea() : d3.geoAzimuthalEquidistant();
	proj.rotate([-az_center.lng, -az_center.lat])
		.clipAngle(179.9)
		.fitExtent([[pad, pad], [size - pad, size - pad]], { type: 'Sphere' });
	var path = d3.geoPath(proj);

	// Disk center/radius from the projected sphere bounds
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
		.data(az_land.features)
		.join('path')
		.attr('d', path)
		.attr('fill', t.land)
		.attr('stroke', t.landStroke)
		.attr('stroke-width', 0.4);

	// 10° graticule
	svg.append('path')
		.attr('d', path(d3.geoGraticule().step([10, 10])()))
		.attr('fill', 'none')
		.attr('stroke', t.graticule)
		.attr('stroke-width', 0.4);

	// Maidenhead field borders (20° x 10°)
	if (az_show_fields) {
		svg.append('path')
			.attr('d', path(d3.geoGraticule().step([20, 10])()))
			.attr('fill', 'none')
			.attr('stroke', t.field)
			.attr('stroke-width', 0.8);
	}

	// CQ / ITU zone overlays (borders + zone numbers at the geojson's label anchor)
	var zoneFont = Math.max(10, size / 70);
	var zoneOverlays = [];
	if (az_show_cq && az_cq) zoneOverlays.push({ color: t.cq, data: az_cq, num: 'cq_zone_number', loc: 'cq_zone_name_loc' });
	if (az_show_itu && az_itu) zoneOverlays.push({ color: t.itu, data: az_itu, num: 'itu_zone_number', loc: 'itu_zone_name_loc' });
	zoneOverlays.forEach(function (z) {
		svg.append('g')
			.selectAll('path')
			.data(z.data.features)
			.join('path')
			.attr('d', path)
			.attr('fill', 'none')
			.attr('stroke', z.color)
			.attr('stroke-width', 1)
			.attr('opacity', 0.9);

		z.data.features.forEach(function (f) {
			var loc = f.properties[z.loc]; // stored as [lat, lng]
			var xy = loc ? proj([loc[1], loc[0]]) : null;
			if (xy && Math.hypot(xy[0] - cx, xy[1] - cy) < R * 0.95) {
				az_text(svg, xy[0], xy[1], String(f.properties[z.num]), zoneFont, z.color, { weight: 'bold', opacity: 0.9 });
			}
		});
	});

	// Distance rings (dashed) + labels on the 45° bearing
	var ringFont = Math.max(12, size / 50);
	for (var km = az_spacing; km / AZ_KM_PER_DEG < 170; km += az_spacing) {
		var deg = km / AZ_KM_PER_DEG;
		svg.append('path')
			.attr('d', path(d3.geoCircle().center([az_center.lng, az_center.lat]).radius(deg)()))
			.attr('fill', 'none')
			.attr('stroke', t.ring)
			.attr('stroke-width', 0.8)
			.attr('stroke-dasharray', '3 3');

		var lp = proj(az_destination(deg, 45));
		if (lp) {
			var lx = lp[0], ly = lp[1];
			if (Math.hypot(lx - cx, ly - cy) < R * 0.95) {
				az_text(svg, lx, ly, km.toLocaleString() + ' km', ringFont, t.ring, { dy: '0.9em' });
			}
		}
	}

	// DXCC entity markers with visible prefix labels (hover for the full name)
	if (az_show_dxcc && az_dxcc_list.length) {
		var dxccR = Math.max(1.5, size / 350);
		var dxccFont = Math.max(8, size / 80);
		var dxccG = svg.append('g');
		az_dxcc_list.forEach(function (d) {
			var xy = proj([d.lng, d.lat]);
			if (!xy) return;
			dxccG.append('circle')
				.attr('cx', xy[0]).attr('cy', xy[1])
				.attr('r', dxccR)
				.attr('fill', t.dxcc)
				.append('title').text(d.name);
			if (Math.hypot(xy[0] - cx, xy[1] - cy) < R * 0.92) { // skip labels smeared near the antipode
				az_text(dxccG, xy[0] + dxccR + 2, xy[1], d.prefix, dxccFont, t.dxcc, { weight: 'bold', anchor: 'start' });
			}
		});
	}

	// Maidenhead field letters at field centers
	if (az_show_fields) {
		var fieldFont = Math.max(10, size / 55);
		for (var i = 0; i < 18; i++) {
			for (var j = 0; j < 18; j++) {
				var xy = proj([-180 + i * 20 + 10, -90 + j * 10 + 5]);
				if (!xy) continue;
				if (Math.hypot(xy[0] - cx, xy[1] - cy) > R * 0.95) continue; // declutter near the antipode
				az_text(svg, xy[0], xy[1], String.fromCharCode(65 + i) + String.fromCharCode(65 + j), fieldFont, t.text, { weight: 'bold', opacity: 0.5 });
			}
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
			az_text(svg, cx + Math.cos(a) * R * 1.05, cy - Math.sin(a) * R * 1.05,
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
	az_text(svg, cx, cy, az_homegrid, Math.max(12, size / 45), t.text, { dy: '-8', weight: 'bold' });

	$('#az_caption').text(az_station_label + ' · ' + az_homegrid + ' · '
		+ az_center.lat.toFixed(4) + '°, ' + az_center.lng.toFixed(4) + '°');
}

function az_export_png() {
	var svgNode = document.getElementById('az_map');
	var size = +svgNode.getAttribute('width') || AZ_MAX_SIZE;
	var scale = 2;

	var clone = svgNode.cloneNode(true);
	clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
	clone.setAttribute('width', size * scale);
	clone.setAttribute('height', size * scale);

	var data = new XMLSerializer().serializeToString(clone);
	var url = URL.createObjectURL(new Blob([data], { type: 'image/svg+xml;charset=utf-8' }));

	var img = new Image();
	img.onload = function () {
		var canvas = document.createElement('canvas');
		canvas.width = size * scale;
		canvas.height = size * scale;
		var ctx = canvas.getContext('2d');
		ctx.fillStyle = az_theme().ocean; // avoid a transparent background
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
		URL.revokeObjectURL(url);

		var a = document.createElement('a');
		a.download = 'azimuthal_' + (az_homegrid || 'map') + '.png';
		a.href = canvas.toDataURL('image/png');
		document.body.appendChild(a);
		a.click();
		a.remove();
	};
	img.onerror = function () {
		URL.revokeObjectURL(url);
		showToast('Error', 'Failed to export the map as PNG.', 'bg-danger text-white', 5000);
	};
	img.src = url;
}

$(document).ready(function () {
	if (typeof az_center === 'undefined' || !az_center || az_center.lat === null) {
		return; // no usable center — the view already shows a warning
	}

	// Initial state comes from the rendered controls — the view defines the defaults
	az_mode = $('#az_projection_toggle button.active').data('projection') || az_mode;
	az_spacing = +$('#az_ring_spacing').val() || az_spacing;
	az_show_fields = $('#az_fields').prop('checked');
	az_show_cq = $('#az_cq').prop('checked');
	az_show_itu = $('#az_itu').prop('checked');
	az_show_dxcc = $('#az_dxcc').prop('checked');

	// Zones pre-checked in the view still need their (lazy) geojson loaded
	if (az_show_cq) $('#az_cq').trigger('change');
	if (az_show_itu) $('#az_itu').trigger('change');

	$.getJSON(base_url + 'assets/json/geojson/ne_110m_land.geojson', function (gj) {
		az_land = gj;
		az_render();
	}).fail(function () {
		showToast('Error', 'Failed to load the land geometry (ne_110m_land.geojson).', 'bg-danger text-white', 5000);
	});

	$('#az_projection_toggle button').on('click', function () {
		$('#az_projection_toggle button').removeClass('active');
		$(this).addClass('active');
		az_mode = this.dataset.projection;
		az_render();
	});

	$('#az_ring_spacing').on('change', function () {
		az_spacing = +this.value;
		az_render();
	});

	$('#az_fields').on('change', function () {
		az_show_fields = this.checked;
		az_render();
	});

	$('#az_cq').on('change', function () {
		az_show_cq = this.checked;
		if (az_show_cq && az_cq === null) {
			// Lazy load: only fetch the 2.8 MB file when the overlay is first used
			$.getJSON(base_url + 'assets/json/geojson/cqzones.geojson', function (gj) {
				az_cq = gj;
				az_render();
			}).fail(function () {
				showToast('Error', 'Failed to load cqzones.geojson.', 'bg-danger text-white', 5000);
			});
		} else {
			az_render();
		}
	});

	$('#az_itu').on('change', function () {
		az_show_itu = this.checked;
		if (az_show_itu && az_itu === null) {
			$.getJSON(base_url + 'assets/json/geojson/ituzones.geojson', function (gj) {
				az_itu = gj;
				az_render();
			}).fail(function () {
				showToast('Error', 'Failed to load ituzones.geojson.', 'bg-danger text-white', 5000);
			});
		} else {
			az_render();
		}
	});

	$('#az_dxcc').on('change', function () {
		az_show_dxcc = this.checked;
		az_render();
	});

	$('#az_btn_png').on('click', az_export_png);

	var resizeTimer;
	$(window).on('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(az_render, 250);
	});
});
