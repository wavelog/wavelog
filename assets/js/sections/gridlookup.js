(function () {
	'use strict';

	let cfg           = window.gridlookupConfig || {};
	let tileUrl       = cfg.tileUrl    || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	let tileAttr      = cfg.tileAttr   || '&copy; OpenStreetMap contributors';
	let glOverlays    = cfg.overlays    || [];
	let glGeojsonBase = cfg.geojsonBase || '';
	let invalidMsg    = cfg.invalidMsg  || 'Invalid gridsquare';
	let bearingLbl    = cfg.bearingLbl  || 'Bearing';
	let measurementBase = cfg.measurementBase || 'M';
	let stateUrl        = cfg.stateUrl || '';
	let wwffUrl         = cfg.wwffUrl || '';
	let potaUrl         = cfg.potaUrl || '';
	let sotaUrl         = cfg.sotaUrl || '';
	let locatingMsg     = cfg.locatingMsg    || 'Locating…';
	let geoDenied       = cfg.geoDenied      || 'Location access denied.';
	let geoUnavailable  = cfg.geoUnavailable || 'Location unavailable.';
	let geoTimeout      = cfg.geoTimeout     || 'Location request timed out.';
	let bordersLbl      = cfg.bordersLbl     || 'Grid square borders';
	let closeLbl        = cfg.closeLbl       || 'Close';

	let PALETTE = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#17becf', '#bcbd22', '#393b79'];
	let overlayCfg = {};     // id -> overlay config
	let overlayLayers = {};  // id -> cached L.geoJSON layer
	let zoneData = {};       // zoneId -> decoded GeoJSON FeatureCollection (cached)
	let zoneFetching = {};   // zoneId -> in-flight fetch Promise
	let zoneReq = 0;         // monotonic guard so stale zone lookups don't overwrite the info bar

	let map, highlight, highlight2, marker, marker2, pathLine, gridOverlay, clickMarker, clickSquare, wwffCluster, potaCluster, sotaCluster, bordersControl;

	// The world view the map opens at — and that Clear zooms back out to.
	let initialView = [20, 0], initialZoom = 3;

	/*
	 * Convert a Maidenhead locator to the exact centre + corner bounds of its
	 * grid cell. Mirrors application/libraries/Qra.php::qra2latlong() so the
	 * drawn box lines up perfectly with Wavelog's own grid convention.
	 * Accepts 2/4/6/8/10-character locators; returns null on bad input.
	 */
	function locatorToCell(loc) {
		loc = String(loc || '').toUpperCase().replace(/\s+/g, '');
		if (loc.length % 2 !== 0 || loc.length < 2 || loc.length > 10) return null;

		// Pad to 10 chars at the centre of the cell, exactly like Qra.php.
		let full = loc;
		if (full.length === 2)       full += '55AA00AA';
		else if (full.length === 4)  full += 'MM00AA';
		else if (full.length === 6)  full += '55AA';
		else if (full.length === 8)  full += 'MM';

		if (!/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/.test(full)) return null;

		let c = full.split('');
		let A = 'A'.charCodeAt(0), Z = '0'.charCodeAt(0);
		let a = c[0].charCodeAt(0) - A, b = c[1].charCodeAt(0) - A;
		let d = c[2].charCodeAt(0) - Z, e = c[3].charCodeAt(0) - Z;
		let f = c[4].charCodeAt(0) - A, g = c[5].charCodeAt(0) - A;
		let h = c[6].charCodeAt(0) - Z, i = c[7].charCodeAt(0) - Z;
		let j = c[8].charCodeAt(0) - A, k = c[9].charCodeAt(0) - A;

		let lngCenter = (a * 20) + (d * 2) + (f / 12) + (h / 120) + (j / 2880) - 180;
		let latCenter = (b * 10) + e + (g / 24) + (i / 240) + (k / 5760) - 90;

		// Cell size for this precision.
		let spanLng, spanLat, label;
		switch (loc.length) {
			case 2:  spanLng = 20;       spanLat = 10;       label = 'Field';             break;
			case 4:  spanLng = 2;        spanLat = 1;        label = 'Grid square';       break;
			case 6:  spanLng = 1 / 12;   spanLat = 1 / 24;   label = 'Subsquare';         break;
			case 8:  spanLng = 1 / 120;  spanLat = 1 / 240;  label = 'Extended square';   break;
			case 10: spanLng = 1 / 2880; spanLat = 1 / 5760; label = 'Locus';             break;
			default: return null;
		}

		return {
			loc: loc,
			label: label,
			center: [latCenter, lngCenter],
			sw: [latCenter - spanLat / 2, lngCenter - spanLng / 2],
			ne: [latCenter + spanLat / 2, lngCenter + spanLng / 2]
		};
	}

	function fmtLat(lat) { return Math.abs(lat).toFixed(5) + '°' + (lat >= 0 ? 'N' : 'S'); }
	function fmtLng(lng) { return Math.abs(lng).toFixed(5) + '°' + (lng >= 0 ? 'E' : 'W'); }

	function deg2rad(d) { return d * Math.PI / 180; }
	function rad2deg(r) { return r * 180 / Math.PI; }

	/*
	 * Great-circle distance between two points. unit is 'K' (km), 'M' (miles)
	 * or 'N' (nautical). Faithful port of application/libraries/Qra.php::calc_distance().
	 */
	function calcDistance(lat1, lon1, lat2, lon2, unit) {
		let theta = lon1 - lon2;
		let dist = Math.sin(deg2rad(lat1)) * Math.sin(deg2rad(lat2)) +
			Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.cos(deg2rad(theta));
		dist = Math.acos(Math.max(-1, Math.min(1, dist)));   // clamp against float drift
		dist = rad2deg(dist) * 60 * 1.1515;
		if (unit === 'K')      { dist *= 1.609344; }
		else if (unit === 'N') { dist *= 0.8684; }
		if (isNaN(dist) || !isFinite(dist)) { dist = 0; }    // same-grid / error guard
		return Math.round(dist * 10) / 10;
	}

	/* Initial great-circle bearing from point1 -> point2, whole degrees. Port of Qra.php::get_bearing(). */
	function getBearing(lat1, lon1, lat2, lon2) {
		let b = (Math.trunc(rad2deg(Math.atan2(
			Math.sin(deg2rad(lon2) - deg2rad(lon1)) * Math.cos(deg2rad(lat2)),
			Math.cos(deg2rad(lat1)) * Math.sin(deg2rad(lat2)) -
			Math.sin(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.cos(deg2rad(lon2) - deg2rad(lon1))
		))) + 360) % 360;
		return b;
	}

	/* 16-sector -> 8-point compass label, exactly like Qra.php::bearing(). */
	function cardinal(bearing) {
		let dirs = ['N', 'E', 'S', 'W'];
		let r = Math.round(bearing / 22.5) % 16;
		if (r % 4 === 0) { return dirs[r / 4]; }
		return dirs[2 * Math.floor(((Math.floor(r / 4) + 1) % 4) / 2)] +
			dirs[1 + 2 * Math.floor(r / 8)];
	}

	/* Sample n+1 points along the great-circle arc from p1 to p2 ([lat, lng] pairs). */
	function greatCircle(p1, p2, n) {
		let lat1 = deg2rad(p1[0]), lon1 = deg2rad(p1[1]);
		let lat2 = deg2rad(p2[0]), lon2 = deg2rad(p2[1]);
		let d = Math.acos(Math.max(-1, Math.min(1,
			Math.sin(lat1) * Math.sin(lat2) + Math.cos(lat1) * Math.cos(lat2) * Math.cos(lon2 - lon1))));
		if (d < 1e-9) { return [p1, p2]; }
		let pts = [];
		let sinD = Math.sin(d);
		for (let i = 0; i <= n; i++) {
			let f = i / n;
			let a = Math.sin((1 - f) * d) / sinD;
			let b = Math.sin(f * d) / sinD;
			let x = a * Math.cos(lat1) * Math.cos(lon1) + b * Math.cos(lat2) * Math.cos(lon2);
			let y = a * Math.cos(lat1) * Math.sin(lon1) + b * Math.cos(lat2) * Math.sin(lon2);
			let z = a * Math.sin(lat1) + b * Math.sin(lat2);
			pts.push([rad2deg(Math.atan2(z, Math.sqrt(x * x + y * y))), rad2deg(Math.atan2(y, x))]);
		}
		return pts;
	}

	/* "6,234.5" style grouping for the distance readout. */
	function groupThousands(n) {
		let parts = String(n).split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		return parts.join('.');
	}

	/* Short unit label for the user's measurement base: km / mi / nm. */
	function unitLabel(u) { return u === 'K' ? 'km' : u === 'N' ? 'nm' : 'mi'; }

	/*
	 * Distance + bearing from [lat,lng] to each side of its 4-character
	 * Maidenhead square (2°×1°): the four edges (N/S/E/W) and the four corner
	 * intersections (NE/NW/SE/SW), plus the square you'd step into beyond each.
	 * Square grid lines fall on whole degrees of latitude (1°) and every 2° of
	 * longitude, so the outline points are simple ceil/floor steps. Reuses
	 * calcDistance/getBearing (ports of Qra.php) for consistency with the QRB readout.
	 */
	function squareBorders(lat, lng) {
		lng = ((lng + 180) % 360 + 360) % 360 - 180;                 // wrap to [-180,180)
		let eastLng  = Math.ceil((lng + 180) / 2) * 2 - 180;
		let westLng  = Math.floor((lng + 180) / 2) * 2 - 180;
		let northLat = Math.min(90, Math.ceil(lat + 90) - 90);
		let southLat = Math.max(-90, Math.floor(lat + 90) - 90);
		let eps = 1e-4;                                              // nudge just across the line
		let u = measurementBase;

		// Distance + bearing to a point on the square's outline (edge midpoint
		// or corner vertex), plus the 4-char square just beyond it.
		function leg(name, tLat, tLng, acrossLat, acrossLng) {
			acrossLat = Math.max(-89.9999, Math.min(89.9999, acrossLat));   // keep off the poles
			acrossLng = ((acrossLng + 180) % 360 + 360) % 360 - 180;        // wrap at the date line
			return {
				dir: name,
				across: latLngToLocator(acrossLat, acrossLng, 2),
				dist: calcDistance(lat, lng, tLat, tLng, u),
				brg: getBearing(lat, lng, tLat, tLng)
			};
		}

		return {
			N:  leg('N',  northLat, lng,     northLat + eps, lng),
			NE: leg('NE', northLat, eastLng, northLat + eps, eastLng + eps),
			E:  leg('E',  lat,      eastLng, lat,           eastLng + eps),
			SE: leg('SE', southLat, eastLng, southLat - eps, eastLng + eps),
			S:  leg('S',  southLat, lng,     southLat - eps, lng),
			SW: leg('SW', southLat, westLng, southLat - eps, westLng - eps),
			W:  leg('W',  lat,      westLng, lat,           westLng - eps),
			NW: leg('NW', northLat, westLng, northLat + eps, westLng - eps)
		};
	}

	/*
	 * Compass-style readout of the surrounding squares: a 3×3 grid with the
	 * current square centred and its eight neighbours (N/NE/E/SE/S/SW/W/NW)
	 * around it. Each cell shows the grid you'd step into, the distance to that
	 * side of the square and the bearing; the nearest is highlighted.
	 * Built on squareBorders().
	 */
	function bordersHTML(lat, lng) {
		let b = squareBorders(lat, lng);
		let order = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
		let minDist = Infinity;
		order.forEach(function (d) { if (b[d].dist < minDist) { minDist = b[d].dist; } });

		function cell(r) {
			let nearest = r.dist === minDist;
			return '<div class="gl-comp-edge' + (nearest ? ' gl-comp-nearest' : '') + '" ' +
				'title="' + r.dir + ' &middot; ' + groupThousands(r.dist) + ' ' +
				unitLabel(measurementBase) + ' &middot; ' + r.brg + '&deg;">' +
				'<span class="gl-badge">' + r.dir + '</span>' +
				'<span class="gl-comp-grid">' + esc(r.across) + '</span>' +
				'<span class="gl-comp-meta">' + groupThousands(r.dist) + ' ' + unitLabel(measurementBase) +
				' &middot; ' + r.brg + '&deg;</span>' +
				'</div>';
		}
		let here = latLngToLocator(lat, lng, 2);
		let centre = '<div class="gl-comp-center">' +
			'<span class="gl-badge"><i class="fa fa-location-crosshairs gl-comp-pin"></i></span>' +
			'<span class="gl-comp-grid">' + esc(here) + '</span>' +
			'</div>';

		return '<button type="button" class="gl-comp-close" aria-label="' + esc(closeLbl) + '">&times;</button>' +
			'<h4 class="gl-comp-title text-center">' + bordersLbl + '</h4>' +
			'<div class="gl-compass">' +
				cell(b.NW) + cell(b.N) + cell(b.NE) +
				cell(b.W)  + centre   + cell(b.E) +
				cell(b.SW) + cell(b.S) + cell(b.SE) +
			'</div>';
	}

	function showBorders(lat, lng) {
		if (bordersControl) { bordersControl.setContent(bordersHTML(lat, lng)); }
	}
	function clearBorders() {
		if (bordersControl) { bordersControl.clear(); }
	}

	/* Format a state_for_point() response as "State (CODE), Country" (or "" if none). */
	function stateStr(s) {
		if (!s || !s.state) { return ''; }
		return s.state + (s.code ? ' (' + s.code + ')' : '') + (s.country ? ', ' + s.country : '');
	}

	/* Join a marker popup's base line with any resolved zones/state (one per line). */
	function popupContent(base, z, s) {
		let parts = [base];
		if (z) { parts.push(z); }
		if (s) { parts.push(s); }
		return parts.join('<br>');
	}

	/* Base popup line for a resolved grid cell (used by Go's blue/orange markers). */
	function cellPopupBase(cell) {
		return '<strong>' + cell.loc + '</strong><br>' + cell.label + '<br>' +
			fmtLat(cell.center[0]) + ', ' + fmtLng(cell.center[1]);
	}

	/*
	 * Reverse of locatorToCell: lat/lng -> Maidenhead locator. The cell step
	 * sizes (20/10, 2/1, 1/12, 1/24, ...) mirror application/libraries/Qra.php
	 * so a locator round-trips exactly. `pairs` sets the length (3 => 6-char).
	 */
	function latLngToLocator(lat, lng, pairs) {
		pairs = pairs || 3;
		lng = ((lng + 180) % 360 + 360) % 360 - 180;   // wrap to [-180, 180)
		lat = Math.max(-90, Math.min(90, lat));
		let x = lng + 180, y = lat + 90;
		let A = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		let out = '';
		out += A[Math.floor(x / 20)] + A[Math.floor(y / 10)];            // field (A-R)
		x = x % 20; y = y % 10;
		out += Math.floor(x / 2) + '' + Math.floor(y);                   // square (0-9)
		x = x % 2; y = y % 1;
		out += A[Math.floor(x / (1 / 12))] + A[Math.floor(y / (1 / 24))]; // subsquare (A-X)
		x = x % (1 / 12); y = y % (1 / 24);
		out += Math.floor(x / (1 / 120)) + '' + Math.floor(y / (1 / 240)); // extended (0-9)
		return out.substring(0, pairs * 2);
	}

	/* Format decimal degrees as DMS (N/S, E/W) — same format as geocoding.js. */
	function pad2(n, width) {
		n = n + '';
		return n.length >= width ? n : new Array(width - n.length + 1).join('0') + n;
	}
	function toDMS(lat, lng) {
		if (lng < -180) { lng += 360; }
		if (lng > 180)  { lng -= 360; }
		let la = lat < 0 ? -lat : lat;
		let lo = lng < 0 ? -lng : lng;
		let latDeg = (lat < 0 ? 'S' : 'N') + ' ' + pad2(0 | la, 2) + '° ' +
			pad2(0 | (((la + 1e-9) % 1) * 60), 2) + "' " +
			((0 | (((la * 60) % 1) * 6000)) / 100) + '"';
		let lngDeg = (lng < 0 ? 'W' : 'E') + ' ' + pad2(0 | lo, 3) + '° ' +
			pad2(0 | (((lo + 1e-9) % 1) * 60), 2) + "' " +
			((0 | (((lo * 60) % 1) * 6000)) / 100) + '"';
		return { latDeg: latDeg, lngDeg: lngDeg };
	}

	/* ---- GeoJSON overlays: zones + per-country states/provinces ---- */
	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function styleFor(color) {
		return { color: color, weight: 1, opacity: 0.85, fillColor: color, fillOpacity: 0.12 };
	}
	function overlayPopup(props, cfg) {
		props = props || {};
		if (cfg.type === 'state') {
			return '<strong>' + esc(props[cfg.nameKey]) + '</strong>' +
				(props[cfg.codeKey] ? ' <span class="text-muted">(' + esc(props[cfg.codeKey]) + ')</span>' : '');
		}
		let prefix = cfg.type === 'cq' ? 'CQ Zone' : 'ITU Zone';
		return '<strong>' + prefix + ' ' + esc(props[cfg.numKey]) + '</strong><br>' + esc(props[cfg.nameKey]);
	}
	// Build the Overlays dropdown from the server-provided config (glOverlays).
	function buildOverlays(host) {
		if (!host || !glOverlays.length) return;
		let stateIdx = 0;
		glOverlays.forEach(function (cfg) {
			overlayCfg[cfg.id] = cfg;
			if (cfg.type === 'cq')       cfg.color = '#9b59b6';
			else if (cfg.type === 'itu') cfg.color = '#e67e22';
			else                         cfg.color = PALETTE[stateIdx++ % PALETTE.length];
		});

		let drop = document.createElement('div');
		drop.className = 'dropdown';

		let btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'btn btn-outline-primary btn-sm dropdown-toggle';
		btn.setAttribute('data-bs-toggle', 'dropdown');
		btn.setAttribute('data-bs-auto-close', 'outside');
		btn.setAttribute('aria-expanded', 'false');
		btn.textContent = 'Overlays';

		let menu = document.createElement('ul');
		menu.className = 'dropdown-menu p-2';
		menu.style.maxHeight = '60vh';
		menu.style.overflowY = 'auto';

		let lastGroup = null;
		glOverlays.forEach(function (cfg) {
			if (cfg.group !== lastGroup) {
				if (lastGroup !== null) {
					menu.appendChild(Object.assign(document.createElement('li'), { innerHTML: '<hr class="dropdown-divider">' }));
				}
				menu.appendChild(Object.assign(document.createElement('li'), { innerHTML: '<h6 class="dropdown-header">' + esc(cfg.group) + '</h6>' }));
				lastGroup = cfg.group;
			}
			let li = document.createElement('li');
			let label = document.createElement('label');
			label.className = 'dropdown-item d-flex align-items-center';
			label.innerHTML = '<input type="checkbox" class="form-check-input me-2" data-oid="' + esc(cfg.id) + '"> ' + esc(cfg.label);
			li.appendChild(label);
			menu.appendChild(li);
		});

		menu.addEventListener('change', function (e) {
			let id = e.target.getAttribute('data-oid');
			if (!id) return;
			if (e.target.checked) { addOverlay(id, e.target); } else { removeOverlay(id); }
		});

		drop.appendChild(btn);
		drop.appendChild(menu);
		host.appendChild(drop);
	}
	// Fetch (once, cached) and add a GeoJSON overlay layer to the map.
	function addOverlay(id, cb) {
		let cfg = overlayCfg[id];
		if (!cfg) return;
		if (overlayLayers[id]) { map.addLayer(overlayLayers[id]); return; }
		if (cb) { cb.disabled = true; }
		fetch(glGeojsonBase + cfg.file)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				let layer = L.geoJSON(data, {
					style: function () { return styleFor(cfg.color); },
					onEachFeature: function (feature, lyr) {
						lyr.bindPopup(overlayPopup(feature.properties, cfg));
						lyr.on('mouseover', function (ev) { ev.target.setStyle({ weight: 3, fillOpacity: 0.32 }); });
						lyr.on('mouseout',  function (ev) { ev.target.setStyle(styleFor(cfg.color)); });
					}
				});
				overlayLayers[id] = layer;            // cache so re-toggle is instant
				if (!cb || cb.checked) { layer.addTo(map); }
			})
			.catch(function (err) { console.error('Overlay load failed:', cfg.file, err); })
			.finally(function () { if (cb) { cb.disabled = false; } });
	}
	function removeOverlay(id) {
		if (overlayLayers[id]) { map.removeLayer(overlayLayers[id]); }
	}

	/* ---- WWFF reference directory overlay ---- */
	/* Loads the full wwff_directory once, plots every reference as a circleMarker
	 * inside a markerClusterGroup — the same recipe as the WWFF award map. This
	 * is a pure directory lookup (where does a reference sit?), so every point is
	 * one colour. Fetched lazily on first toggle and cached, so the (potentially large) payload is never loaded for
	 * users who leave the toggle off.
	 */
	function enableWwff() {
		if (!wwffUrl) { return; }
		if (wwffCluster) { map.addLayer(wwffCluster); return; }      // cached after first load
		if (typeof L.markerClusterGroup !== 'function') { return; }  // plugin not loaded

		fetch(wwffUrl)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				wwffCluster = L.markerClusterGroup({
					chunkedLoading: true,
					maxClusterRadius: 50,
					showCoverageOnHover: false
				});
				for (var i = 0; i < data.length; i++) {
					var D = data[i];
					if (D.lat == null || D.lon == null) { continue; }
					var dot = L.circleMarker([D.lat, D.lon], {
						radius: 6,
						weight: 1,
						color: '#fff',
						fillColor: '#2b8cbe',
						fillOpacity: 0.9
					});
					dot.bindTooltip(D.reference + (D.name ? ' - ' + D.name : ''));
					dot.bindPopup('<strong>' + esc(D.reference) + '</strong>' +
						(D.name ? '<br>' + esc(D.name) : '') +
						'<br>' + fmtLat(D.lat) + ', ' + fmtLng(D.lon));
					wwffCluster.addLayer(dot);
				}
				map.addLayer(wwffCluster);
			})
			.catch(function (err) { console.error('WWFF directory load failed:', err); });
	}
	function disableWwff() {
		if (wwffCluster) { map.removeLayer(wwffCluster); }
	}
	function enablePota() {
		if (!potaUrl) { return; }
		if (potaCluster) { map.addLayer(potaCluster); return; }      // cached after first load
		if (typeof L.markerClusterGroup !== 'function') { return; }  // plugin not loaded

		fetch(potaUrl)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				potaCluster = L.markerClusterGroup({
					chunkedLoading: true,
					maxClusterRadius: 50,
					showCoverageOnHover: false
				});
				for (var i = 0; i < data.length; i++) {
					var D = data[i];
					if (D.lat == null || D.lon == null) { continue; }
					var dot = L.circleMarker([D.lat, D.lon], {
						radius: 6,
						weight: 1,
						color: '#fff',
						fillColor: '#238b45',
						fillOpacity: 0.9
					});
					dot.bindTooltip(D.reference + (D.name ? ' - ' + D.name : ''));
					dot.bindPopup('<strong>' + esc(D.reference) + '</strong>' +
						(D.name ? '<br>' + esc(D.name) : '') +
						'<br>' + fmtLat(D.lat) + ', ' + fmtLng(D.lon));
					potaCluster.addLayer(dot);
				}
				map.addLayer(potaCluster);
			})
			.catch(function (err) { console.error('POTA directory load failed:', err); });
	}
	function disablePota() {
		if (potaCluster) { map.removeLayer(potaCluster); }
	}
	function enableSota() {
		if (!sotaUrl) { return; }
		if (sotaCluster) { map.addLayer(sotaCluster); return; }      // cached after first load
		if (typeof L.markerClusterGroup !== 'function') { return; }  // plugin not loaded

		fetch(sotaUrl)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				sotaCluster = L.markerClusterGroup({
					chunkedLoading: true,
					maxClusterRadius: 50,
					showCoverageOnHover: false
				});
				for (var i = 0; i < data.length; i++) {
					var D = data[i];
					if (D.lat == null || D.lon == null) { continue; }
					var dot = L.circleMarker([D.lat, D.lon], {
						radius: 6,
						weight: 1,
						color: '#fff',
						fillColor: '#d95f0e',
						fillOpacity: 0.9
					});
					dot.bindTooltip(D.reference + (D.name ? ' - ' + D.name : ''));
					dot.bindPopup('<strong>' + esc(D.reference) + '</strong>' +
						(D.name ? '<br>' + esc(D.name) : '') +
						'<br>' + fmtLat(D.lat) + ', ' + fmtLng(D.lon));
					sotaCluster.addLayer(dot);
				}
				map.addLayer(sotaCluster);
			})
			.catch(function (err) { console.error('SOTA directory load failed:', err); });
	}
	function disableSota() {
		if (sotaCluster) { map.removeLayer(sotaCluster); }
	}

	/* ---- CQ / ITU zone resolution (coordinate -> zone, client-side) ---- */

	/* Lazily fetch + cache the CQ / ITU boundary GeoJSON. Returns a Promise of
	 * the FeatureCollection (or null). Concurrent callers share one fetch; a
	 * failure clears the slot so the next lookup retries. */
	function ensureZoneData(zoneId) {
		if (zoneData[zoneId]) { return Promise.resolve(zoneData[zoneId]); }
		if (zoneFetching[zoneId]) { return zoneFetching[zoneId]; }
		let cfg = overlayCfg[zoneId];
		let file = cfg ? cfg.file : (zoneId === 'cq' ? 'cqzones.geojson' : 'ituzones.geojson');
		zoneFetching[zoneId] = fetch(glGeojsonBase + file)
			.then(function (r) { return r.json(); })
			.then(function (data) { zoneData[zoneId] = data; delete zoneFetching[zoneId]; return data; })
			.catch(function (err) { console.error('Zone load failed:', zoneId, err); delete zoneFetching[zoneId]; return null; });
		return zoneFetching[zoneId];
	}

	/* Ray-casting point-in-polygon for one GeoJSON linear ring of [lng, lat] points. */
	function pointInRing(lat, lng, ring) {
		let inside = false;
		for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
			let yi = ring[i][1], yj = ring[j][1];
			if (((yi > lat) !== (yj > lat)) &&
				(lng < (ring[j][0] - ring[i][0]) * (lat - yi) / (yj - yi) + ring[i][0])) {
				inside = !inside;
			}
		}
		return inside;
	}

	/* True if [lat, lng] falls inside a GeoJSON feature (Polygon or MultiPolygon, holes honoured). */
	function featureContains(lat, lng, feature) {
		let geom = feature.geometry;
		if (!geom) { return false; }
		let polys = geom.type === 'MultiPolygon' ? geom.coordinates : [geom.coordinates];
		for (let p = 0; p < polys.length; p++) {
			let rings = polys[p];
			if (!rings || !rings.length || !pointInRing(lat, lng, rings[0])) { continue; }
			let inHole = false;
			for (let h = 1; h < rings.length; h++) {
				if (pointInRing(lat, lng, rings[h])) { inHole = true; break; }
			}
			if (!inHole) { return true; }
		}
		return false;
	}

	/* Resolve {num} for the zone containing [lat, lng], or null. */
	function zoneFor(lat, lng, zoneId) {
		return ensureZoneData(zoneId).then(function (data) {
			if (!data || !data.features) { return null; }
			let cfg = overlayCfg[zoneId] || {};
			let numKey = cfg.numKey || (zoneId === 'cq' ? 'cq_zone_number' : 'itu_zone_number');
			for (let i = 0; i < data.features.length; i++) {
				if (featureContains(lat, lng, data.features[i])) {
					return { num: (data.features[i].properties || {})[numKey] };
				}
			}
			return null;
		});
	}

	/* Sync sibling of zoneFor for the live mousemove readout: uses only the
	 * already-cached boundary data (no fetch), so it never blocks. Returns
	 * {num} or null when the zone file isn't loaded yet. */
	function zoneForSync(lat, lng, zoneId) {
		let data = zoneData[zoneId];
		if (!data || !data.features) { return null; }
		let cfg = overlayCfg[zoneId] || {};
		let numKey = cfg.numKey || (zoneId === 'cq' ? 'cq_zone_number' : 'itu_zone_number');
		for (let i = 0; i < data.features.length; i++) {
			if (featureContains(lat, lng, data.features[i])) {
				return { num: (data.features[i].properties || {})[numKey] };
			}
		}
		return null;
	}

	/* Compact "CQ 5 / ITU 8" label for a coordinate ("" if unresolved). */
	function zonesLabel(lat, lng) {
		return Promise.all([zoneFor(lat, lng, 'cq'), zoneFor(lat, lng, 'itu')]).then(function (res) {
			let parts = [];
			if (res[0] && res[0].num != null) { parts.push('CQ ' + res[0].num); }
			if (res[1] && res[1].num != null) { parts.push('ITU ' + res[1].num); }
			return parts.join(' / ');
		});
	}

	function init() {
		map = L.map('glMap', { worldCopyJump: true }).setView(initialView, initialZoom);

		L.tileLayer(tileUrl, {
			minZoom: 3,
			maxZoom: 19,
			attribution: tileAttr
		}).addTo(map);

		L.control.scale({ imperial: false, metric: true }).addTo(map);

		if (typeof L.maidenheadqrb === 'function') {
			gridOverlay = L.maidenheadqrb().addTo(map);
		}

		L.control.fullscreen && L.control.fullscreen().addTo(map);

		// Floating compass readout of the surrounding square borders. A custom
		// Leaflet control so it stays parked in the corner while the map pans,
		// and is hidden until a point is selected (see showBorders/clearBorders).
		var BordersControl = L.Control.extend({
			options: { position: 'topright' },
			onAdd: function () {
				this._c = L.DomUtil.create('div', 'gl-borders-control');
				L.DomEvent.disableClickPropagation(this._c);   // let the map keep catching drags
				L.DomEvent.disableScrollPropagation(this._c);
				this._c.style.display = 'none';                // empty until a grid is selected
				return this._c;
			},
			setContent: function (html) {
				if (!this._c) { return; }
				this._c.innerHTML = html;
				this._c.style.display = html ? '' : 'none';
				// Bind the dismiss button directly and stop the event so the click
				// can't bubble on to the map (where it would re-select a grid).
				var btn = html && this._c.querySelector('.gl-comp-close');
				if (btn) {
					L.DomEvent.on(btn, 'click', function (ev) {
						L.DomEvent.stop(ev);
						clearBorders();
					});
				}
			},
			clear: function () { this.setContent(''); }
		});
		bordersControl = new BordersControl();
		bordersControl.addTo(map);

		document.getElementById('glGo').addEventListener('click', go);
		document.getElementById('glClear').addEventListener('click', clearAll);
		var locateBtn = document.getElementById('glLocate');
		if (locateBtn) {
			locateBtn.addEventListener('click', locate);
			// Hide the button entirely when geolocation is unavailable (e.g. plain
			// HTTP on a LAN IP) so users never see a dead control.
			if (!('geolocation' in navigator)) { locateBtn.style.display = 'none'; }
		}

		// Mobile "More options" disclosure: toggles a class on the toolbar that
		// CSS uses to show/hide the secondary controls (grid 2 + overlays).
		var moreBtn = document.getElementById('glMore');
		if (moreBtn) {
			moreBtn.addEventListener('click', function () {
				var controls = document.getElementById('glControls');
				var expanded = controls.classList.toggle('gl-expanded');
				moreBtn.setAttribute('aria-expanded', String(expanded));
			});
		}
		document.getElementById('glGridOverlay').addEventListener('change', function () {
			if (!gridOverlay) return;
			if (this.checked) { gridOverlay.addTo(map); } else { map.removeLayer(gridOverlay); }
		});

		// Optional WWFF reference directory overlay. Built lazily on first toggle
		// (the directory can be large), then cached for instant re-toggle.
		var wwffCb = document.getElementById('glWwffDir');
		if (wwffCb) {
			wwffCb.addEventListener('change', function () {
				if (this.checked) { enableWwff(); } else { disableWwff(); }
			});
		}
		var potaCb = document.getElementById('glPotaDir');
		if (potaCb) {
			potaCb.addEventListener('change', function () {
				if (this.checked) { enablePota(); } else { disablePota(); }
			});
		}
		var sotaCb = document.getElementById('glSotaDir');
		if (sotaCb) {
			sotaCb.addEventListener('change', function () {
				if (this.checked) { enableSota(); } else { disableSota(); }
			});
		}

		// Build the Overlays (GeoJSON) dropdown — zones + per-country states.
		buildOverlays(document.getElementById('glOverlaysHost'));

		// Warm the CQ/ITU zone caches in the background so the first lookup is instant.
		ensureZoneData('cq');
		ensureZoneData('itu');

		let input = document.getElementById('glGrid');
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); go(); }
		});

		let input2 = document.getElementById('glGrid2');
		input2.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); go(); }
		});

		// Click the map to drop a marker showing the gridsquare + coordinates.
		map.on('click', onMapClick);

		// Live coordinate readout at the bottom of the map (like the gridmap).
		// Lat/lng/locator update every move; the CQ/ITU lookup is heavier
		// (point-in-polygon over ~130 features), so coalesce it to one per frame.
		let cqEl = document.getElementById('cqzonedisplay');
		let ituEl = document.getElementById('ituzonedisplay');
		let lastMouse = null, mouseFrame = null;
		map.on('mousemove', function (e) {
			let lat = e.latlng.lat, lng = e.latlng.lng;
			let dms = toDMS(lat, lng);
			document.getElementById('latDeg').textContent = dms.latDeg;
			document.getElementById('lngDeg').textContent = dms.lngDeg;
			document.getElementById('locator').textContent = latLngToLocator(lat, lng, 3);

			lastMouse = { lat: lat, lng: lng };
			if (mouseFrame === null) {
				mouseFrame = requestAnimationFrame(function () {
					mouseFrame = null;
					if (!lastMouse) { return; }
					let cq = zoneForSync(lastMouse.lat, lastMouse.lng, 'cq');
					let itu = zoneForSync(lastMouse.lat, lastMouse.lng, 'itu');
					if (cqEl)  { cqEl.textContent  = (cq  && cq.num  != null) ? cq.num  : ''; }
					if (ituEl) { ituEl.textContent = (itu && itu.num != null) ? itu.num : ''; }
				});
			}
		});

		// Reveal the coordinate bar (elements start hidden via .cohidden).
		document.querySelectorAll('#glCoords .cohidden').forEach(function (el) {
			el.classList.remove('cohidden');
		});

		// Leaflet needs a nudge when its container was sized after init.
		setTimeout(function () { map.invalidateSize(); }, 200);
	}

	function clearAll() {
		if (highlight)   { map.removeLayer(highlight);   highlight = null; }
		if (marker)      { map.removeLayer(marker);      marker = null; }
		if (clickSquare) { map.removeLayer(clickSquare); clickSquare = null; }
		if (clickMarker) { map.removeLayer(clickMarker); clickMarker = null; }
		clearSecond();   // grid-2 square, marker and path line
		clearBorders();  // square-border readout

		// Zoom back out to the default world view.
		map.setView(initialView, initialZoom);

		document.getElementById('glGrid').value = '';
		document.getElementById('glGrid2').value = '';
		document.getElementById('glInfo').textContent = '';
		document.getElementById('glError').textContent = '';
	}

	function onMapClick(e) {
		// Ignore clicks that began on a floating control (the borders panel and
		// its dismiss button). Modern Leaflet routes map clicks through pointer
		// events that disableClickPropagation doesn't stop, so without this the
		// dismiss click would land here and immediately re-select a grid.
		var t = e.originalEvent && e.originalEvent.target;
		if (t && t.closest && t.closest('.gl-borders-control')) { return; }
		selectPoint(e.latlng.lat, e.latlng.lng);
	}

	/*
	 * Drop the green click marker + grid cell at [lat, lng], zoom in, and fill
	 * the info bar (CQ/ITU zones + state enrich asynchronously). Shared by the
	 * map click handler and the "Locate me" geolocation flow so both behave the
	 * same way. `loc` may be supplied to skip recomputation.
	 */
	function selectPoint(lat, lng, loc) {
		loc = loc || latLngToLocator(lat, lng, 3);   // 6-character gridsquare
		let cell = locatorToCell(loc);              // exact bounds of that grid cell

		// Only one click marker + square at a time: drop the previous ones.
		if (clickMarker) { map.removeLayer(clickMarker); clickMarker = null; }
		if (clickSquare) { map.removeLayer(clickSquare); clickSquare = null; }

		clickSquare = L.rectangle([cell.sw, cell.ne], {
			color: '#198754', weight: 3, fillColor: '#198754', fillOpacity: 0.18
		}).addTo(map);

		let popupBase = '<strong>' + loc + '</strong><br>' + fmtLat(lat) + ', ' + fmtLng(lng);
		clickMarker = L.marker([lat, lng]).addTo(map)
			.bindPopup(popupBase)
			.openPopup();

		// Zoom in to the grid cell — same fitBounds call as typing a gridsquare
		// above, so the square becomes visible instead of a sub-pixel box.
		map.fitBounds([cell.sw, cell.ne], { padding: [60, 60], maxZoom: 17 });

		document.getElementById('glError').textContent = '';
		let info = document.getElementById('glInfo');
		let baseInfo = '<strong>' + loc + '</strong> &middot; ' + fmtLat(lat) + ', ' + fmtLng(lng);
		let zones = '', stateLabel = '';

		// Re-render from whatever has resolved so far, so the independently
		// async zones and state enrichments compose instead of clobbering each
		// other. zoneReq guards against a newer click overwriting this one.
		function renderInfo() {
			let parts = [baseInfo];
			if (zones) { parts.push(zones); }
			if (stateLabel) { parts.push(stateLabel); }
			info.innerHTML = parts.join(' &middot; ');
		}
		function renderPopup() {
			if (clickMarker) { clickMarker.setPopupContent(popupContent(popupBase, zones, stateLabel)); }
		}
		renderInfo();
		showBorders(lat, lng);   // distance/bearing to each surrounding square edge

		let myReq = ++zoneReq;
		// CQ/ITU zones — client-side against the cached boundaries.
		zonesLabel(lat, lng).then(function (z) {
			if (myReq !== zoneReq) { return; }
			zones = z || '';
			renderInfo();
			renderPopup();
		});
		// State/province subdivision — server-side across the supported GeoJSON.
		if (stateUrl) {
			fetch(stateUrl + '?lat=' + lat + '&lng=' + lng)
				.then(function (r) { return r.json(); })
				.then(function (s) {
					if (myReq !== zoneReq || !s || !s.state) { return; }
					stateLabel = stateStr(s);
					renderInfo();
					renderPopup();
				})
				.catch(function () { /* unsupported point or transient — ignore */ });
		}
	}

	/*
	 * "Locate me": ask the browser for the current GPS position (mobile or
	 * desktop), convert it to a gridsquare, and zoom in — exactly like clicking
	 * the map there. Requires a secure context (HTTPS or localhost); the button
	 * is hidden up front in init() when geolocation is unavailable.
	 */
	function locate() {
		if (!('geolocation' in navigator)) { return; }

		let err = document.getElementById('glError');
		let info = document.getElementById('glInfo');
		let btn = document.getElementById('glLocate');

		err.textContent = '';
		info.textContent = locatingMsg;
		if (btn) { btn.disabled = true; }

		navigator.geolocation.getCurrentPosition(function (pos) {
			if (btn) { btn.disabled = false; }
			let lat = pos.coords.latitude, lng = pos.coords.longitude;
			let loc = latLngToLocator(lat, lng, 3);
			// Fill grid 1 so the user can immediately type a second grid and hit Go
			// for a QRB (distance/bearing) from their current location.
			document.getElementById('glGrid').value = loc;
			selectPoint(lat, lng, loc);
		}, function (geoErr) {
			if (btn) { btn.disabled = false; }
			info.textContent = '';
			switch (geoErr.code) {
				case 1:  err.textContent = geoDenied; break;        // permission denied
				case 2:  err.textContent = geoUnavailable; break;   // position unavailable
				case 3:  err.textContent = geoTimeout; break;       // timed out
				default: err.textContent = geoErr.message || geoUnavailable;
			}
		}, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
	}

	function go() {
		let err = document.getElementById('glError');
		let info = document.getElementById('glInfo');
		err.textContent = '';

		// Grid 1 is required; grid 2 is optional and enables distance/bearing.
		let cell1 = locatorToCell(document.getElementById('glGrid').value);
		let raw2  = (document.getElementById('glGrid2').value || '').trim();
		let cell2 = raw2 ? locatorToCell(raw2) : null;
		if (!cell1 || (raw2 && !cell2)) {
			info.textContent = '';
			err.textContent = invalidMsg;
			return;
		}

		// Drop any second-grid artefacts; rebuilt below only when grid 2 is set.
		clearSecond();
		clearBorders();   // only the single-grid case shows square borders below

		// Grid 1 square + marker (blue).
		if (highlight) { map.removeLayer(highlight); }
		highlight = L.rectangle([cell1.sw, cell1.ne], {
			color: '#0d6efd', weight: 3, fillColor: '#0d6efd', fillOpacity: 0.18
		}).addTo(map);

		if (marker) { map.removeLayer(marker); }
		marker = L.marker(cell1.center).addTo(map).bindPopup(cellPopupBase(cell1));

		if (cell2) {
			// Grid 2 square + marker (orange).
			highlight2 = L.rectangle([cell2.sw, cell2.ne], {
				color: '#fd7e14', weight: 3, fillColor: '#fd7e14', fillOpacity: 0.18
			}).addTo(map);

			marker2 = L.marker(cell2.center).addTo(map).bindPopup(cellPopupBase(cell2));

			// Great-circle path between the two cell centres.
			pathLine = L.polyline(greatCircle(cell1.center, cell2.center, 64), {
				color: '#ff2d92', weight: 3, dashArray: '6,6', opacity: 1
			}).addTo(map);

			// QRB: distance + bearing grid1 -> grid2 (port of application/libraries/Qra.php).
			let dist = calcDistance(cell1.center[0], cell1.center[1], cell2.center[0], cell2.center[1], measurementBase);
			let brg = getBearing(cell1.center[0], cell1.center[1], cell2.center[0], cell2.center[1]);

			// Composed readout: each grid's "(zones, state)" tag fills in
			// asynchronously and re-renders. zoneReq guards against stale updates.
			let z1 = '', z2 = '', s1 = '', s2 = '';
			function tag(z, s) {
				let inner = [];
				if (z) { inner.push(z); }
				if (s) { inner.push(s); }
				return inner.length ? ' (' + inner.join(', ') + ')' : '';
			}
			function render() {
				info.innerHTML =
					'<strong>' + cell1.loc + '</strong>' + tag(z1, s1) + ' &rarr; <strong>' + cell2.loc + '</strong>' + tag(z2, s2) +
					' &middot; ' + groupThousands(dist) + ' ' + unitLabel(measurementBase) +
					' &middot; ' + bearingLbl + ' ' + brg + '&deg; (' + cardinal(brg) + ')';
			}
			render();
			function renderPopups() {
				if (marker)  { marker.setPopupContent(popupContent(cellPopupBase(cell1), z1, s1)); }
				if (marker2) { marker2.setPopupContent(popupContent(cellPopupBase(cell2), z2, s2)); }
			}

			map.fitBounds(L.latLngBounds([cell1.sw, cell1.ne, cell2.sw, cell2.ne]),
				{ padding: [60, 60], maxZoom: 17 });

			let myReq = ++zoneReq;
			// CQ/ITU zones (client-side, cached boundaries).
			Promise.all([
				zonesLabel(cell1.center[0], cell1.center[1]),
				zonesLabel(cell2.center[0], cell2.center[1])
			]).then(function (z) {
				if (myReq !== zoneReq) { return; }
				z1 = z[0] || ''; z2 = z[1] || '';
				render();
				renderPopups();
			});
			// State/province subdivision (server-side, one call per grid).
			if (stateUrl) {
				Promise.all([
					fetch(stateUrl + '?lat=' + cell1.center[0] + '&lng=' + cell1.center[1]).then(function (r) { return r.json(); }).catch(function () { return null; }),
					fetch(stateUrl + '?lat=' + cell2.center[0] + '&lng=' + cell2.center[1]).then(function (r) { return r.json(); }).catch(function () { return null; })
				]).then(function (s) {
					if (myReq !== zoneReq) { return; }
					s1 = stateStr(s[0]); s2 = stateStr(s[1]);
					render();
					renderPopups();
				});
			}
		} else {
			marker.openPopup();
			map.fitBounds([cell1.sw, cell1.ne], { padding: [60, 60], maxZoom: 17 });

			let z1 = '', s1 = '';
			function render() {
				let parts = [
					'<strong>' + cell1.loc + '</strong> &middot; ' + cell1.label + ' &middot; ' +
					fmtLat(cell1.center[0]) + ', ' + fmtLng(cell1.center[1])
				];
				if (z1) { parts.push(z1); }
				if (s1) { parts.push(s1); }
				info.innerHTML = parts.join(' &middot; ');
			}
			render();
			function renderPopup() {
				if (marker) { marker.setPopupContent(popupContent(cellPopupBase(cell1), z1, s1)); }
			}
			showBorders(cell1.center[0], cell1.center[1]);   // surrounding square edges for this grid

			let myReq = ++zoneReq;
			// CQ/ITU zones (client-side, cached boundaries).
			zonesLabel(cell1.center[0], cell1.center[1]).then(function (z) {
				if (myReq !== zoneReq) { return; }
				z1 = z || '';
				render();
				renderPopup();
			});
			// State/province subdivision (server-side, across the supported GeoJSON).
			if (stateUrl) {
				fetch(stateUrl + '?lat=' + cell1.center[0] + '&lng=' + cell1.center[1])
					.then(function (r) { return r.json(); })
					.then(function (s) {
						if (myReq !== zoneReq) { return; }
						s1 = stateStr(s);
						render();
						renderPopup();
					})
					.catch(function () { /* unsupported point or transient — ignore */ });
			}
		}
	}

	/* Remove the grid-2 square, marker and path line (shared by Go and Clear). */
	function clearSecond() {
		if (highlight2) { map.removeLayer(highlight2); highlight2 = null; }
		if (marker2)    { map.removeLayer(marker2);    marker2 = null; }
		if (pathLine)   { map.removeLayer(pathLine);   pathLine = null; }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
