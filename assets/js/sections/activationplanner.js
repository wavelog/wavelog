(function () {
	'use strict';

	let cfg           = window.activationplannerConfig || {};
	let tileUrl       = cfg.tileUrl    || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	let tileAttr      = cfg.tileAttr   || '&copy; OpenStreetMap contributors';
	let glOverlays    = cfg.overlays    || [];
	let glGeojsonBase = cfg.geojsonBase || '';
	let invalidMsg    = decodeHtml(cfg.invalidMsg) || 'Invalid gridsquare';
	let bearingLbl    = decodeHtml(cfg.bearingLbl) || 'Bearing';
	let measurementBase = cfg.measurementBase || 'M';
	let stateUrl        = cfg.stateUrl || '';
	let wwffUrl         = cfg.wwffUrl || '';
	let potaUrl         = cfg.potaUrl || '';
	let activatedPotaUrl = cfg.activatedPotaUrl || '';
	let potaBoundaryUrl = cfg.potaBoundaryUrl || '';
	let sotaUrl         = cfg.sotaUrl || '';
	let iotaUrl         = cfg.iotaUrl || '';
	let dxccGridUrl     = cfg.dxccGridUrl || '';
	let satPassUrl      = cfg.satPassUrl || '';
	let refsNearbyUrl   = cfg.refsNearbyUrl || '';
	let satPassLbl      = decodeHtml(cfg.satPassLbl) || 'Satellite passes';
	let locatingMsg     = decodeHtml(cfg.locatingMsg) || 'Locating…';
	let geoDenied       = decodeHtml(cfg.geoDenied) || 'Location access denied.';
	let geoUnavailable  = decodeHtml(cfg.geoUnavailable) || 'Location unavailable.';
	let geoTimeout      = decodeHtml(cfg.geoTimeout) || 'Location request timed out.';
	let bordersLbl      = decodeHtml(cfg.bordersLbl) || 'Grid square borders';
	let closeLbl        = decodeHtml(cfg.closeLbl) || 'Close';
	let errorLbl        = decodeHtml(cfg.errorLbl) || 'Error';
	let trackingLbl     = decodeHtml(cfg.trackingLbl) || 'Tracking';
	let fieldLbl        = decodeHtml(cfg.fieldLbl) || 'Field';
	let squareLbl       = decodeHtml(cfg.squareLbl) || 'Square';
	let subsquareLbl    = decodeHtml(cfg.subsquareLbl) || 'Subsquare';
	let createStationUrl = cfg.createStationUrl || '';
	let newStationLocLbl = decodeHtml(cfg.newStationLocLbl) || 'Create station location';
	let refsTitleLbl     = decodeHtml(cfg.refsTitleLbl) || 'References in this grid';
	let activatedLbl     = decodeHtml(cfg.activatedLbl) || 'QSOs';
	let lastLbl          = decodeHtml(cfg.lastLbl) || 'last';
	let inactiveLbl      = decodeHtml(cfg.inactiveLbl) || 'Inactive';
	let validRangeLbl    = decodeHtml(cfg.validRangeLbl) || 'valid';
	let userDxcc         = cfg.userDxcc != null ? cfg.userDxcc : null;
	let shareLbl         = decodeHtml(cfg.shareLbl) || 'Share';
	let shareActivationTitleLbl = decodeHtml(cfg.shareActivationTitleLbl) || 'Share activation';
	let planningActivationLbl   = decodeHtml(cfg.planningActivationLbl) || '📻 Planning an activation from %s';
	let searchPlaceholderLbl = decodeHtml(cfg.searchPlaceholderLbl) || 'Search references…';
	let searchNoMatchesLbl   = decodeHtml(cfg.searchNoMatchesLbl) || 'No matches';
	let searchLoadingLbl     = decodeHtml(cfg.searchLoadingLbl) || 'Loading…';
	let searchRefsHeaderLbl  = decodeHtml(cfg.searchRefsHeaderLbl) || 'References';
	let searchPlacesLbl      = decodeHtml(cfg.searchPlacesLbl) || 'Places';
	let searchEnterHintLbl   = decodeHtml(cfg.searchEnterHintLbl) || 'Press Enter to search places';
	let gridLbl         = decodeHtml(cfg.gridLbl) || 'Gridsquare';
	let nearbyRefsLbl   = decodeHtml(cfg.nearbyRefsLbl) || 'Nearby refs';
	let nearbyRefsRadiusLbl = decodeHtml(cfg.nearbyRefsRadiusLbl) || 'References within %s of the gridsquare';
	let colTypeLbl      = decodeHtml(cfg.colTypeLbl) || 'Type';
	let colReferenceLbl = decodeHtml(cfg.colReferenceLbl) || 'Reference';
	let colNameLbl      = decodeHtml(cfg.colNameLbl) || 'Name';
	let colDistanceLbl  = decodeHtml(cfg.colDistanceLbl) || 'Distance';

	let PALETTE = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#17becf', '#bcbd22', '#393b79'];
	let overlayCfg = {};     // id -> overlay config
	let overlayLayers = {};  // id -> cached L.geoJSON layer
	let zoneData = {};       // zoneId -> decoded GeoJSON FeatureCollection (cached)
	let zoneFetching = {};   // zoneId -> in-flight fetch Promise
	let zoneReq = 0;         // monotonic guard so stale zone lookups don't overwrite the info bar

	let map, highlight, highlight2, marker, marker2, pathLine, gridOverlay, clickMarker, clickSquare, wwffCluster, potaCluster, sotaCluster, iotaLayer, bordersControl, bordersOverlay, refOverlay, searchOverlay, localGridLayer, localGridPoint, trackTimer = null;
	// Drawn POTA park boundaries, keyed by reference (ref -> L.geoJSON layer).
	// Populated on demand when a marker is clicked; cleared by clearAll().
	let boundaryLayers = {};

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
	/* Summit altitude in the user's unit (K -> metres, M/N -> feet). Source is metres. */
	function fmtAltitude(m) {
		if (m == null || m === '' || isNaN(m)) { return ''; }
		let val = measurementBase === 'K' ? Math.round(Number(m)) : Math.round(Number(m) * 3.28084);
		return groupThousands(val) + (measurementBase === 'K' ? ' m' : ' ft');
	}

	/* Trim a DATETIME string (e.g. "2024-07-31 14:05:00") to its YYYY-MM-DD
	 * date for the "last activated" popup row. COL_TIME_ON is stored in UTC,
	 * so the date is taken straight from the string — never run through a
	 * Date object, which would reinterpret it in the viewer's local timezone
	 * and shift the day. Returns '' on bad/empty input. */
	function formatActivatedDate(s) {
		s = String(s || '').trim();
		return /^\d{4}-\d{2}-\d{2}/.test(s) ? s.slice(0, 10) : '';
	}

	/* Suffix for the "Inactive" popup row: when the directory carries
	 * valid_from/valid_till (SOTA + WWFF), append the closed/open range so
	 * the user sees *when* it was active. POTA has only a boolean flag, so
	 * both stay empty and just the "Inactive" label is shown. Open bounds
	 * render as '?' (e.g. '? – 2020-12-31' or '2010-01-01 – ?'). */
	function inactiveRange(D) {
		let from = formatActivatedDate(D.valid_from);
		let till = formatActivatedDate(D.valid_till);
		if (!from && !till) { return ''; }
		return ' &middot; ' + esc(validRangeLbl) + ' ' + esc(from || '?') + ' – ' + esc(till || '?');
	}

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

	/* km → the user's display unit, using the same factors as calcDistance so
	   server-given km distances reconcile with distances computed client-side. */
	function kmToUnit(km, unit) {
		let mi = km / 1.609344;                                        // km → statute miles (calcDistance base)
		let v = unit === 'K' ? km : unit === 'N' ? mi * 0.8684 : mi;   // M → mi
		return Math.round(v * 10) / 10;                                // 1-decimal precision, like calcDistance
	}

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
		let centerLng = (eastLng + westLng) / 2;
		let centerLat = (northLat + southLat) / 2;

		// Distance + bearing to a point on the square's outline (edge midpoint
		// or corner vertex), plus the 4-char square just beyond it. `target` is
		// the true nearest point on the line (used for the arrow + distance/
		// bearing calc); `labelPos` is where the distance label is drawn - for
		// N/S/E/W it's pinned to the border's midpoint so it doesn't drift
		// sideways with the click point, while corners just reuse `target`.
		function leg(name, tLat, tLng, acrossLat, acrossLng, labelLat, labelLng) {
			acrossLat = Math.max(-89.9999, Math.min(89.9999, acrossLat));   // keep off the poles
			acrossLng = ((acrossLng + 180) % 360 + 360) % 360 - 180;        // wrap at the date line
			return {
				dir: name,
				target: [tLat, tLng],
				labelPos: [labelLat === undefined ? tLat : labelLat, labelLng === undefined ? tLng : labelLng],
				across: latLngToLocator(acrossLat, acrossLng, 2),
				dist: calcDistance(lat, lng, tLat, tLng, u),
				brg: getBearing(lat, lng, tLat, tLng)
			};
		}

		return {
			N:  leg('N',  northLat, lng,     northLat + eps, lng,               northLat, centerLng),
			NE: leg('NE', northLat, eastLng, northLat + eps, eastLng + eps),
			E:  leg('E',  lat,      eastLng, lat,           eastLng + eps,      centerLat, eastLng),
			SE: leg('SE', southLat, eastLng, southLat - eps, eastLng + eps),
			S:  leg('S',  southLat, lng,     southLat - eps, lng,               southLat, centerLng),
			SW: leg('SW', southLat, westLng, southLat - eps, westLng - eps),
			W:  leg('W',  lat,      westLng, lat,           westLng - eps,      centerLat, westLng),
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
		drawArrows(lat, lng);
	}
	/* Hide the legend panel only — the on-map arrows stay until a real Clear. */
	function hideBordersPanel() {
		if (bordersControl) { bordersControl.clear(); }
	}
	function clearBorders() {
		hideBordersPanel();
		clearArrows();
		setLabelsDim(false);
		if (localGridLayer) { localGridLayer.clearLayers(); }   // selection's local mesh
		localGridPoint = null;
	}

	/*
	 * Outline + tint the 4-character grid square containing [lat,lng]. Yellow on
	 * dark themes, red on light themes (where yellow is hard to see). Used for
	 * the single-grid selection (with arrows) and for both ends of a QRB.
	 */
	function drawSquareTint(lat, lng) {
		let sq = locatorToCell(latLngToLocator(lat, lng, 2));
		if (!sq) { return; }
		let sqColor = (typeof isDarkModeTheme === 'function' && isDarkModeTheme()) ? '#ffd60a' : '#ff1900';
		bordersOverlay.addLayer(L.rectangle([sq.sw, sq.ne], {
			color: sqColor, weight: 2, dashArray: '8,5',
			fillColor: sqColor, fillOpacity: 0.1,
			interactive: false
		}));
	}

	/* Two-grid QRB: tint both squares and dim the rest of the grid labels. */
	function showTwoGridTint(c1, c2) {
		if (!bordersOverlay) { return; }
		bordersOverlay.clearLayers();
		drawSquareTint(c1.center[0], c1.center[1]);
		drawSquareTint(c2.center[0], c2.center[1]);
		setLabelsDim(true);
	}

	/* Tone down every Maidenhead grid label so the tinted squares stand out. */
	function setLabelsDim(on) {
		let el = map && map.getContainer();
		if (el) { el.classList.toggle('gl-dim-labels', on); }
	}

	/*
	 * Local Maidenhead mesh for a selection: the 3×3 block of 4-char squares
	 * around the selected one, drawn by the selection itself (click / locate /
	 * Go) so the grid context appears with the spot and clears with it — the
	 * global Gridsquare overlay stays off. Skipped (and cleared) while the global
	 * overlay is active, so the two meshes never double up. Cells and labels use
	 * the L.maidenheadqrb overlay's exact constructs (grid-rectangle outlines,
	 * my-div-icon / grid-text labels), so the per-theme body.map-* styling in
	 * general.css applies here too and both meshes look identical.
	 */
	function drawLocalGrid(lat, lng) {
		localGridPoint = [lat, lng];
		if (!localGridLayer) { return; }
		localGridLayer.clearLayers();
		if (gridOverlay && map.hasLayer(gridOverlay)) { return; }   // global mesh already up
		let sq = locatorToCell(latLngToLocator(lat, lng, 2));       // selected 4-char square
		if (!sq) { return; }
		let west = sq.sw[1] - 2, east = sq.ne[1] + 2;       // one square out each side (2° columns)
		let south = sq.sw[0] - 1, north = sq.ne[0] + 1;     // ... and 1° rows

		// Label font sizes per zoom — the same table L.maidenheadqrb indexes.
		let sizes = [0, 10, 14, 16, 6, 13, 14, 16, 24, 36, 12, 14, 20, 36, 60, 12, 20, 36, 60, 12, 24];
		let fpx = sizes[map.getZoom()] || 14;

		for (let x = west; x < east - 1e-9; x += 2) {
			for (let y = south; y < north - 1e-9; y += 1) {
				// Cell outline — same rectangle style the overlay draws per cell.
				localGridLayer.addLayer(L.rectangle([[y, x], [y + 1, x + 2]], {
					className: 'grid-rectangle',
					color: 'rgba(255, 0, 0, 0.4)',
					weight: 1,
					fill: false,
					interactive: false
				}));
				// Cell name — same divIcon markup the overlay's labels use, so the
				// themed grid-text CSS restyles it identically. Nudged up-left of the
				// centre (the divIcon anchors at its top-left corner).
				let name = latLngToLocator(y + 0.6, x + 0.7, 2);
				let html = '<span class="grid-text" style="cursor: default;"><font style="color:rgba(255, 0, 0, 0.4); font-size:' + fpx + 'px; font-weight: 900;">' + esc(name) + '</font></span>';
				localGridLayer.addLayer(L.marker([y + 0.5, x + 0.9], {
					icon: L.divIcon({ className: 'my-div-icon', html: html }),
					interactive: false,
					keyboard: false
				}));
			}
		}
	}

	/*
	 * On-map direction arrows: a spoke from the selected point to each side of
	 * its square (the four edges + four corners), tipped with a label naming the
	 * neighbouring grid and the distance/bearing. Spatial, so it scales to any
	 * screen — the corner compass panel is hidden on phones, where it's too big.
	 */
	function drawArrows(lat, lng) {
		if (!bordersOverlay) { return; }
		bordersOverlay.clearLayers();

		drawSquareTint(lat, lng);   // 4-char grid square outline + tint the arrows point to
		drawLocalGrid(lat, lng);    // 3×3 mesh of neighbouring squares for context

		let b = squareBorders(lat, lng);
		let order = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
		let minDist = Infinity;
		order.forEach(function (d) { if (b[d].dist < minDist) { minDist = b[d].dist; } });
		let from = [lat, lng];
		order.forEach(function (d) {
			let r = b[d];
			let near = r.dist === minDist;
			bordersOverlay.addLayer(L.polyline([from, r.target], {
				color: near ? '#198754' : '#ffffff',
				weight: near ? 3 : 1.5,
				opacity: near ? 0.95 : 0.55,
				dashArray: near ? null : '5,5',
				lineCap: 'round',
				interactive: false
			}));
			bordersOverlay.addLayer(L.marker(r.labelPos, {
				interactive: false,
				keyboard: false,
				icon: L.divIcon({
					className: 'gl-arrow-anchor',
					html: '<div class="gl-arrow-tip' + (near ? ' gl-arrow-near' : '') + '">' +
						'<span class="gl-arrow-head" style="transform:rotate(' + r.brg + 'deg)"></span>' +
						groupThousands(r.dist) + ' ' + unitLabel(measurementBase) +
						'</div>'
				})
			}));
		});
	}
	function clearArrows() {
		if (bordersOverlay) { bordersOverlay.clearLayers(); }
	}

	/* Phones are where the corner compass panel is hidden, so the border arrows
	 * carry the info — match the same breakpoint the CSS uses to hide the panel. */
	function isMobile() {
		return window.matchMedia && window.matchMedia('(max-width: 575.98px)').matches;
	}

	/*
	 * Zoom to a selected grid. Desktop fits the exact cell the user picked
	 * (subsquare for a click, square/field for a typed locator). Mobile instead
	 * frames the 4-character grid square centred on its middle, so the border
	 * arrows and their labels are fully in view on the small screen.
	 */
	function zoomToGrid(lat, lng, tightCell) {
		let sq = locatorToCell(latLngToLocator(lat, lng, 2));   // 4-char square
		if (isMobile()) {
			if (sq) {
				map.fitBounds([sq.sw, sq.ne], { padding: [40, 40], maxZoom: 10 });
				return;
			}
		}
		map.fitBounds([sq.sw, sq.ne], { padding: [40, 40], maxZoom: 8 });
	}

	/* Format a state_for_point() response as "State (CODE), Country" (or "" if none). */
	function stateStr(s) {
		if (!s || !s.state) { return ''; }
		return s.state + (s.code ? ' (' + s.code + ')' : '') + (s.country ? ', ' + s.country : '');
	}

	/*
	 * WWFF/POTA/SOTA/IOTA directory lookup for the clicked 6-character
	 * (subsquare) grid and the search box. The full directories (reference,
	 * name, lat, lon — or a bounding box for IOTA) are fetched once from the
	 * existing endpoints, cached, then filtered by the square's bounds. The
	 * IOTA overlay fetches independently in enableIota(); this only powers the
	 * search index.
	 */
	let refDirs = { wwff: null, pota: null, sota: null, iota: null };
	function loadRefDir(type) {
		if (refDirs[type] !== null) { return Promise.resolve(refDirs[type]); }   // array done, or in-flight promise
		let url = type === 'wwff' ? wwffUrl : type === 'pota' ? potaUrl : type === 'sota' ? sotaUrl : type === 'iota' ? iotaUrl : '';
		if (!url) { refDirs[type] = []; return Promise.resolve([]); }
		let p = fetch(url).then(function (r) { return r.json(); }).then(function (d) {
			refDirs[type] = Array.isArray(d) ? d : [];
			return refDirs[type];
		}).catch(function (err) { console.warn(type + ' directory load failed:', err); refDirs[type] = []; return []; });
		refDirs[type] = p;   // share one in-flight request across callers
		return p;
	}

	/*
	 * Set of POTA references the user has activated in the past, plus per-ref
	 * {last, qso_count}. Fetched lazily only when the POTA overlay is enabled,
	 * so users who leave it off pay nothing. Failed/off → empty set.
	 */
	let activatedPota = null;
	function loadActivatedPota() {
		if (activatedPota !== null) { return Promise.resolve(activatedPota); }
		if (!activatedPotaUrl) { activatedPota = new Map(); return Promise.resolve(activatedPota); }
		let p = fetch(activatedPotaUrl).then(function (r) { return r.json(); }).then(function (d) {
			activatedPota = new Map();
			(Array.isArray(d) ? d : []).forEach(function (a) {
				if (a && a.reference) { activatedPota.set(a.reference, a); }
			});
			return activatedPota;
		}).catch(function (err) { console.warn('activated POTA load failed:', err); activatedPota = new Map(); return activatedPota; });
		activatedPota = p;
		return p;
	}

	/* Resolved {wwff, pota, sota} arrays of references inside the 6-char grid. */
	function refsInSquare(lat, lng) {
		// 20 km radius, matching the "Nearby refs" dialog. A coarse bounding-box
		// prefilter (cheap) then exact haversine. loadRefDir returns the cached
		// directories (the same ones the Refs overlays use), so the markers carry
		// the full data needed for the rich popups.
		let radius = 20, latDeg = radius / 110.0,
			lonDeg = latDeg / Math.max(0.2, Math.cos(Math.abs(lat) * Math.PI / 180));
		function within(r) {
			if (!r || r.lat == null || r.lon == null) { return false; }
			if (r.inactive) { return false; }   // active-only, matching the nearby button
			if (Math.abs(r.lat - lat) > latDeg || Math.abs(r.lon - lng) > lonDeg) { return false; }
			return calcDistance(lat, lng, r.lat, r.lon, 'K') <= radius;
		}
		return Promise.all([
			loadRefDir('wwff').then(function (d) { return d.filter(within); }),
			loadRefDir('pota').then(function (d) { return d.filter(within); }),
			loadRefDir('sota').then(function (d) { return d.filter(within); })
		]).then(function (r) { return { wwff: r[0], pota: r[1], sota: r[2] }; });
	}

	/* Render the references block (empty string if none). */
	function refsSection(refs) {
		if (!refs) { return ''; }
		let groups = [
			{ label: 'POTA', cls: 'is-pota', items: refs.pota },
			{ label: 'SOTA', cls: 'is-sota', items: refs.sota },
			{ label: 'WWFF', cls: 'is-wwff', items: refs.wwff }
		];
		let html = '';
		groups.forEach(function (g) {
			if (!g.items || !g.items.length) { return; }
			let cap = g.items.slice(0, 8);
			let extra = g.items.length > cap.length ? ' <span class="gl-pop-ref-more">+' + (g.items.length - cap.length) + '</span>' : '';
			let items = cap.map(function (r) {
				return '<span class="gl-pop-ref"><b>' + esc(r.reference || '') + '</b>' + (r.name ? ' ' + esc(r.name) : '') + '</span>';
			}).join('');
			html += '<div class="gl-pop-ref-group ' + g.cls + '"><span class="gl-pop-ref-type">' + g.label + '</span>' + items + extra + '</div>';
		});
		if (!html) { return ''; }
		return '<div class="gl-pop-refs"><div class="gl-pop-refs-title">' + esc(refsTitleLbl) + '</div>' + html + '</div>';
	}

	/* Plot the references (from refsInSquare) on the map, using the same lettered
	 * divIcon markers and rich popups as the Refs-dropdown overlays. */
	function drawRefs(refs) {
		if (!refOverlay) { return; }
		refOverlay.clearLayers();
		let groups = [
			{ items: refs.pota, color: '#238b45', label: 'POTA', letter: 'P' },
			{ items: refs.sota, color: '#d95f0e', label: 'SOTA', letter: 'S' },
			{ items: refs.wwff, color: '#2b8cbe', label: 'WWFF', letter: 'W' }
		];
		groups.forEach(function (g) {
			(g.items || []).forEach(function (r) {
				if (r.lat == null || r.lon == null) { return; }
				let icon = r.activated ? refIconActivated(g.letter) : refIcon(g.color, g.letter);
				let m = L.marker([r.lat, r.lon], { icon: icon });
				m.refType = g.label; m.refData = r; m.refColor = g.color;
				m.bindPopup(refPopupRich);
				refOverlay.addLayer(m);
			});
		});
	}

	/*
	 * Rich gridsquare popup (click marker + Go markers): a title, the 4-char
	 * grid large with any subsquare muted, coordinates, a Field/Square/Subsquare
	 * hierarchy, and the nearest grid border (direction + adjacent grid +
	 * distance + arrow). Echoes the corner info box. zones/state are appended
	 * when resolved. Built on squareBorders() for the nearest-border figure.
	 */
	function gridPopupHTML(lat, lng, loc, zones, state, refs, flag, createHref, spotText) {
		// Hierarchy tiers: Field/Square are the "primary" 4-char grid (accent dot),
		// Subsquare is the refinement (muted dot). Only tiers the locator has.
		let tiers = [];
		if (loc.length >= 2) { tiers.push({ lbl: fieldLbl, val: loc.substring(0, 2), strong: true }); }
		if (loc.length >= 4) { tiers.push({ lbl: squareLbl, val: loc.substring(2, 4), strong: true }); }
		if (loc.length >= 6) { tiers.push({ lbl: subsquareLbl, val: loc.substring(4, 6), strong: false }); }
		let hier = tiers.map(function (t) {
			return '<span class="gl-pop-tier' + (t.strong ? ' is-strong' : '') + '">' +
				'<i class="gl-dot"></i>' + esc(t.lbl) + ' (' + esc(t.val) + ')</span>';
		}).join(' ');

		let metaLines = [];
		if (zones) {
			metaLines.push(zones.split(' / ').map(function (z) {
				return z.replace(/^CQ /, 'CQ Zone ').replace(/^ITU /, 'ITU Zone ');
			}).join(' / '));
		}
		if (state) { metaLines.push(state); }

		// Quick actions: share the spot (X / Bluesky / Mastodon via the existing
		// modal) or create a station location here (location pre-filled).
		let actions =
			'<div class="gl-pop-actions">' +
				(spotText ? '<a class="gl-pop-action gl-share" style="cursor: pointer;" data-spot="' + esc(spotText) + '"><i class="fas fa-share-nodes"></i> ' + esc(shareLbl) + '</a>' : '') +
				(createHref ? '<a class="gl-pop-action" href="' + esc(createHref) + '"><i class="fas fa-plus"></i> ' + esc(newStationLocLbl) + '</a>' : '') +
			'</div>';

		return '<div class="gl-popup">' +
			'<div class="gl-pop-head"><span class="ref-popup-type" style="background:#dc3545">' + esc(gridLbl) + '</span></div>' +
			'<div class="gl-pop-grid">' + (Array.isArray(flag) && flag.length ? flag.map(function (fl) { return '<span class="flag-emoji gl-pop-flag">' + esc(fl) + '</span>'; }).join('') : '') + loc + '</div>' +
			'<div class="gl-pop-coords"><i class="fas fa-location-crosshairs"></i> ' + fmtLat(lat) + ', ' + fmtLng(lng) + '</div>' +
			(hier ? '<div class="gl-pop-hier">' + hier + '</div>' : '') +
			refsSection(refs) +
			actions +
			(metaLines.length ? '<div class="gl-pop-meta">' + metaLines.map(esc).join('<br>') + '</div>' : '') +
			(satPassUrl ? '<div class="gl-pop-link"><a href="' + esc(satPassUrl + '?gridsquare=' + encodeURIComponent(loc)) + '" target="_blank" rel="noopener noreferrer"><i class="fas fa-satellite"></i> ' + esc(satPassLbl) + '</a></div>' : '') +
			'</div>';
	}

	/*
	 * "Nearby refs" dialog: WWFF/POTA/SOTA references within 5/10/15/20 km of the
	 * given gridsquare, fetched server-side and shown grouped by distance band.
	 */
	function showNearbyModal(loc, rows) {
		let u = measurementBase, ul = unitLabel(u);
		// Bands are fixed 5 km quadrants of the (server-side, km) query; the km
		// boundary values are converted to the user's unit only for the labels,
		// so bucketing stays exact while the readout matches the page's unit.
		let bv = [5, 10, 15, 20].map(function (k) { return kmToUnit(k, u); });
		let bands = [
			{ label: 'Within ' + bv[0] + ' ' + ul, rows: [] },
			{ label: bv[0] + '-' + bv[1] + ' ' + ul, rows: [] },
			{ label: bv[1] + '-' + bv[2] + ' ' + ul, rows: [] },
			{ label: bv[2] + '-' + bv[3] + ' ' + ul, rows: [] }
		];
		(rows || []).forEach(function (r) {
			bands[Math.min(bands.length - 1, Math.floor(r.dist / 5))].rows.push(r);
		});

		let body = bands.map(function (b) {
			let trs = b.rows.map(function (r) {
				let col = r.type === 'POTA' ? '#238b45' : r.type === 'SOTA' ? '#d95f0e' : '#2b8cbe';
				return '<tr><td><span class="gl-pop-ref-type" style="background:' + col + '">' + esc(r.type) + '</span></td><td>' + esc(r.ref) + '</td><td>' +
					esc(r.name || '') + '</td><td class="text-end">' + groupThousands(kmToUnit(r.dist, u)) + ' ' + ul + '</td></tr>';
			}).join('');
			return '<h6 class="mt-3 mb-1">' + esc(b.label) + ' <span class="badge bg-secondary">' + b.rows.length + '</span></h6>' +
				'<table class="table table-sm table-striped mb-0 gl-nearby-table">' +
				'<thead><tr>' +
				'<th style="width:70px">' + esc(colTypeLbl) + '</th>' +
				'<th style="width:120px">' + esc(colReferenceLbl) + '</th>' +
				'<th>' + esc(colNameLbl) + '</th>' +
				'<th class="text-end" style="width:90px">' + esc(colDistanceLbl) + '</th></tr></thead>' +
				'<tbody>' + (trs || '<tr><td colspan="4" class="text-muted">—</td></tr>') + '</tbody></table>';
		}).join('');

		BootstrapDialog.show({
			title: nearbyRefsLbl + ' · ' + loc,
			message: body,
			size: BootstrapDialog.SIZE_WIDE,
			nl2br: false,
			buttons: [{ label: closeLbl, action: function (dialog) { dialog.close(); } }]
		});
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
	/* Permanent label shown on each overlay region. Zones (CQ/ITU) use their
	 * number; state/province overlays use their full name. */
	function overlayLabel(props, cfg) {
		props = props || {};
		if (cfg.type === 'state') {
			return esc(props[cfg.nameKey] != null ? props[cfg.nameKey] : '');
		}
		return esc(props[cfg.numKey] != null ? props[cfg.numKey] : '');
	}
	/* Highlight a dropdown toggle (btn-filter-active) when any checkbox in its
	 * menu is checked — mirrors the advanced logbook filter button. */
	function syncToggleActive(menu, btn) {
		if (!menu || !btn) return;
		btn.classList.toggle('btn-filter-active', !!menu.querySelector('input[type="checkbox"]:checked'));
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
			syncToggleActive(menu, btn);
		});

		drop.appendChild(btn);
		drop.appendChild(menu);
		host.appendChild(drop);
		syncToggleActive(menu, btn);   // initial state (none checked -> normal)
	}
	// Fetch (once, cached) and add a GeoJSON overlay layer to the map.
	function addOverlay(id, cb) {
		let cfg = overlayCfg[id];
		if (!cfg) return;
		if (overlayLayers[id]) {
			let cached = overlayLayers[id];
			map.addLayer(cached);
			map.fitBounds(cached.getBounds(), { padding: [20, 20] });
			return;
		}
		if (cb) { cb.disabled = true; }
		fetch(glGeojsonBase + cfg.file)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				let layer = L.geoJSON(data, {
					style: function () { return Object.assign(styleFor(cfg.color), { interactive: false }); },
					onEachFeature: function (feature, lyr) {
						lyr.bindTooltip(overlayLabel(feature.properties, cfg), {
							permanent: true,
							direction: 'center',
							className: 'overlay-region-label'
						});
					}
				});
				overlayLayers[id] = layer;            // cache so re-toggle is instant
				if (!cb || cb.checked) {
					layer.addTo(map);
					map.fitBounds(layer.getBounds(), { padding: [20, 20] });
				}
			})
			.catch(function (err) { console.error('Overlay load failed:', cfg.file, err); })
			.finally(function () { if (cb) { cb.disabled = false; } });
	}
	function removeOverlay(id) {
		if (overlayLayers[id]) { map.removeLayer(overlayLayers[id]); }
	}

	/* External info-page URL for a reference. encodeURI keeps SOTA slashes
	 * (G/SP-001) intact; encodeURIComponent would turn them into %2F. */
	function refUrl(type, reference) {
		let r = encodeURI(reference);
		if (type === 'WWFF') { return 'https://www.gma.rocks/zinfo.php?ref=' + r; }
		if (type === 'POTA') { return 'https://pota.app/#/park/' + r; }
		if (type === 'SOTA') { return 'https://www.sotadata.org.uk/en/summit/' + r; }
		return '';
	}

	/* Popup for a SOTA/POTA/WWFF reference point: a coloured left stripe +
	 * type badge with the reference (links to its info page), then name, etc.
	 * Optional createHref appends a "Create station location" quick action
	 * (prefilled with this grid + reference); optional spotText adds a Share
	 * action. */
	function refPopup(type, D, color, createHref, spotText) {
		return '<div class="ref-popup" style="border-left-color:' + esc(color) + '">' +
			'<div class="ref-popup-top">' +
			'<span class="ref-popup-type" style="background:' + esc(color) + '">' + esc(type) + '</span>' +
			'<a class="ref-popup-ref" href="' + esc(refUrl(type, D.reference)) + '" target="_blank" rel="noopener noreferrer">' + esc(D.reference) + ' <i class="fas fa-up-right-from-square"></i></a>' +
			'</div>' +
			(D.name ? '<div class="ref-popup-name">' + esc(D.name) + '</div>' : '') +
			(D.inactive ? '<div class="ref-popup-row is-inactive"><i class="fas fa-circle-exclamation"></i><span>' + esc(inactiveLbl) + inactiveRange(D) + '</span></div>' : '') +
			(D.activated ? '<div class="ref-popup-row is-activated"><i class="fas fa-circle-check"></i><span>' + esc(activatedLbl) + ': ' + D.activated.qso_count + ' &middot; ' + esc(lastLbl) + ' ' + esc(formatActivatedDate(D.activated.last)) + '</span></div>' : '') +
			(D.altitude != null && D.altitude !== '' ? '<div class="ref-popup-row"><i class="fas fa-mountain"></i><span>' + fmtAltitude(D.altitude) + '</span></div>' : '') +
			'<div class="ref-popup-row"><i class="fas fa-th"></i><span>' + esc(latLngToLocator(D.lat, D.lon, 3)) + '</span></div>' +
			'<div class="ref-popup-row"><i class="fas fa-location-crosshairs"></i><span>' + fmtLat(D.lat) + ', ' + fmtLng(D.lon) + '</span></div>' +
			((createHref || spotText) ? '<div class="gl-pop-actions">' +
				(spotText ? '<a class="gl-pop-action gl-share" style="cursor: pointer;" data-spot="' + esc(spotText) + '"><i class="fas fa-share-nodes"></i> ' + esc(shareLbl) + '</a>' : '') +
				(createHref ? '<a class="gl-pop-action" href="' + esc(createHref) + '"><i class="fas fa-plus"></i> ' + esc(newStationLocLbl) + '</a>' : '') +
			'</div>' : '') +
			'</div>';
	}

	/*
	 * Open-time popup builder for a POTA/SOTA/WWFF marker. Reads the stashed
	 * {type, D} from the marker, renders the popup immediately with create +
	 * share actions carrying gridsquare + the reference (always available,
	 * sync), then enriches both with DXCC/CQ/ITU/state (where resolvable) once
	 * the server/client lookups settle. setPopupContent is idempotent if the
	 * popup has already been closed by then.
	 */
	function refPopupRich(layer) {
		let type = layer.refType, D = layer.refData, color = layer.refColor;
		if (!type || !D) { return ''; }
		let loc = latLngToLocator(D.lat, D.lon, 3);
		let field = type.toLowerCase();   // 'wwff' | 'pota' | 'sota'
		let extra = {};
		if (field) { extra[field] = D.reference; }
		let locDesc = D.reference + ' (' + loc + ')';
		let extraHash = '#' + type + ' ' + D.reference;
		let baseHref = buildCreateHref(loc, null, extra);
		let baseSpot = buildSpotText(locDesc, null, extraHash);
		resolvePointMeta(D.lat, D.lon, loc).then(function (meta) {
			if (layer.isPopupOpen && layer.isPopupOpen()) {
				layer.setPopupContent(refPopup(type, D, color, buildCreateHref(loc, meta, extra), buildSpotText(locDesc, meta, extraHash)));
			}
		}).catch(function () { /* leave sync create + share in place */ });
		return refPopup(type, D, color, baseHref, baseSpot);
	}

	/* Coloured circle marker (white letter) for the reference overlays on the
	 * map — same look as the .ref-menu-dot swatch in the Refs dropdown. */
	function refIcon(color, letter) {
		return L.divIcon({
			className: 'ref-map-icon',
			html: '<span class="ref-menu-dot" style="background:' + esc(color) + '">' + esc(letter) + '</span>',
			iconSize: [18, 18],
			iconAnchor: [9, 9],
			popupAnchor: [0, -10]
		});
	}

	/* "Already activated" variant: gold ring + check over the letter. Reuses
	 * .ref-menu-dot so sizing stays consistent with the plain marker. */
	function refIconActivated(letter) {
		return L.divIcon({
			className: 'ref-map-icon',
			html: '<span class="ref-menu-dot is-activated">' + esc(letter) +
				'<i class="fas fa-check ref-activated-check"></i></span>',
			iconSize: [18, 18],
			iconAnchor: [9, 9],
			popupAnchor: [0, -10]
		});
	}

	/* Loading overlay: a blocking spinner over the map while a reference
	 * directory is fetching. A counter backs it so concurrent loads share one. */
	let loadingCount = 0, loadingEl = null;
	function mapLoadingStart() {
		loadingCount++;
		if (loadingCount === 1 && map) {
			if (!loadingEl) {
				loadingEl = document.createElement('div');
				loadingEl.className = 'gl-loading';
				loadingEl.innerHTML = '<div class="spinner-border text-light" role="status" aria-hidden="true"></div>';
				map.getContainer().appendChild(loadingEl);
			}
			loadingEl.style.display = 'flex';
		}
	}
	function mapLoadingDone() {
		if (loadingCount > 0) { loadingCount--; }
		if (loadingCount === 0 && loadingEl) { loadingEl.style.display = 'none'; }
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

		mapLoadingStart();
		loadRefDir('wwff').then(function (data) {
			wwffCluster = L.markerClusterGroup({
				chunkedLoading: true,
				maxClusterRadius: 50,
				showCoverageOnHover: false
			});
			for (let i = 0; i < data.length; i++) {
				let D = data[i];
				if (D.lat == null || D.lon == null) { continue; }
				let dot = L.marker([D.lat, D.lon], { icon: refIcon('#2b8cbe', 'W') });
				dot.refType = 'WWFF'; dot.refData = D; dot.refColor = '#2b8cbe';
				dot.bindPopup(refPopupRich);
				wwffCluster.addLayer(dot);
			}
			map.addLayer(wwffCluster);
			mapLoadingDone();
		}).catch(function (err) { console.error('WWFF overlay build failed:', err); mapLoadingDone(); });
	}
	function disableWwff() {
		if (wwffCluster) { map.removeLayer(wwffCluster); }
	}

	function drawParkBoundary(ref, fit) {
		if (!potaBoundaryUrl || !ref || boundaryLayers[ref]) { return; }
		if (!/^(DE|AT|CH|CZ|DK|LU|LI)-/.test(ref)) { return; }
		boundaryLayers[ref] = true; // sentinel: in-flight / done, prevents refetch
		fetch(potaBoundaryUrl + encodeURIComponent(ref))
			.then(function (r) {
				if (!r.ok) { return null; }
				return r.json();
			})
			.then(function (feature) {
				if (!feature || !feature.geometry) { return; }
				let layer = L.geoJSON(feature, {
					style: { color: '#238b45', weight: 2, fillColor: '#238b45', fillOpacity: 0.15 }
				}).addTo(map);
				boundaryLayers[ref] = layer;
				if (fit) { map.fitBounds(layer.getBounds(), { padding: [40, 40], maxZoom: 13 }); }
			})
			.catch(function () { /* 404 / network: leave the point marker as-is */ });
	}

	/* Remove every drawn park-boundary layer and forget the per-ref cache so
	 * the next popup re-fetches. Used by disablePota() and clearAll(). */
	function clearBoundaries() {
		for (const ref in boundaryLayers) {
			const l = boundaryLayers[ref];
			if (l && l.remove) { map.removeLayer(l); }
		}
		boundaryLayers = {};
	}

	function enablePota() {
		if (!potaUrl) { return; }
		if (potaCluster) { map.addLayer(potaCluster); return; }      // cached after first load
		if (typeof L.markerClusterGroup !== 'function') { return; }  // plugin not loaded

		mapLoadingStart();
		// Resolve the user's activated-refs set in parallel with the directory so
		// the marker loop can tint already-activated parks in a single pass.
		Promise.all([
			loadRefDir('pota'),
			loadActivatedPota()
		]).then(function (res) {
			let data = res[0];
			let activated = res[1] || new Map();
			potaCluster = L.markerClusterGroup({
				chunkedLoading: true,
				maxClusterRadius: 50,
				showCoverageOnHover: false
			});
			for (let i = 0; i < data.length; i++) {
				let D = data[i];
				if (D.lat == null || D.lon == null) { continue; }
				let act = activated.get(D.reference);
				if (act) { D.activated = act; }
				let icon = act ? refIconActivated('P') : refIcon('#238b45', 'P');
				let dot = L.marker([D.lat, D.lon], { icon: icon });
				dot.refType = 'POTA'; dot.refData = D; dot.refColor = '#238b45';
				dot.bindPopup(refPopupRich);
				dot.on('popupopen', function () { drawParkBoundary(D.reference); });
				potaCluster.addLayer(dot);
			}
			map.addLayer(potaCluster);
			mapLoadingDone();
		}).catch(function (err) { console.error('POTA overlay build failed:', err); mapLoadingDone(); });
	}
	function disablePota() {
		if (potaCluster) { map.removeLayer(potaCluster); }
		clearBoundaries();
	}
	function enableSota() {
		if (!sotaUrl) { return; }
		if (sotaCluster) { map.addLayer(sotaCluster); return; }      // cached after first load
		if (typeof L.markerClusterGroup !== 'function') { return; }  // plugin not loaded

		mapLoadingStart();
		loadRefDir('sota').then(function (data) {
			sotaCluster = L.markerClusterGroup({
				chunkedLoading: true,
				maxClusterRadius: 50,
				showCoverageOnHover: false
			});
			for (let i = 0; i < data.length; i++) {
				let D = data[i];
				if (D.lat == null || D.lon == null) { continue; }
				let dot = L.marker([D.lat, D.lon], { icon: refIcon('#d95f0e', 'S') });
				dot.refType = 'SOTA'; dot.refData = D; dot.refColor = '#d95f0e';
				dot.bindPopup(refPopupRich);
				sotaCluster.addLayer(dot);
			}
			map.addLayer(sotaCluster);
			mapLoadingDone();
		}).catch(function (err) { console.error('SOTA overlay build failed:', err); mapLoadingDone(); });
	}
	function disableSota() {
		if (sotaCluster) { map.removeLayer(sotaCluster); }
	}

	/* ---- IOTA reference directory overlay ---- */
	/* IOTA references are bounding-box rectangles, not points. Drawn as a flat
	 * layer of polygons in one colour; click a rectangle for its details. Loads
	 * lazily and is cached. NB: the table stores latitudes sign-reversed, and a
	 * few islands wrap the antimeridian — both handled in iotaRect (mirrors
	 * assets/js/sections/iotamap.js). */
	function enableIota() {
		if (!iotaUrl) { return; }
		if (iotaLayer) { map.addLayer(iotaLayer); return; }       // cached after first load

		mapLoadingStart();
		fetch(iotaUrl)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				iotaLayer = L.layerGroup();
				// Tag labels cluster when zoomed out (the rectangles stay plain layers).
				let cluster = L.markerClusterGroup({
					chunkedLoading: true,
					maxClusterRadius: 50,
					showCoverageOnHover: false
				});
				for (let i = 0; i < data.length; i++) {
					let D = data[i];
					if (D.lat1 == null || D.lon1 == null || D.lat2 == null || D.lon2 == null) { continue; }
					let g = iotaGeometry(D);   // antimeridian-adjusted longitudes
					// The rectangle is purely visual: non-interactive, so map clicks
					// pass straight through it. The tag label is the click target.
					iotaLayer.addLayer(L.polygon(
						[[-D.lat1, g.lon1], [-D.lat2, g.lon1], [-D.lat2, g.lon2], [-D.lat1, g.lon2]],
						{ interactive: false, color: '#17a2b8', weight: 1, fillColor: '#17a2b8', fillOpacity: 0.12 }
					));
					let cLat = -(Number(D.lat1) + Number(D.lat2)) / 2;
					let cLng = (g.lon1 + g.lon2) / 2;
					let m = L.marker([cLat, cLng], { icon: iotaIcon(D.tag) });
					m.refData = D;
					m.bindPopup(iotaPopupRich);
					cluster.addLayer(m);
				}
				iotaLayer.addLayer(cluster);   // cluster rides inside the group → atomic add/remove
				map.addLayer(iotaLayer);
				mapLoadingDone();
			})
			.catch(function (err) { console.error('IOTA directory load failed:', err); mapLoadingDone(); });
	}
	function disableIota() {
		if (iotaLayer) { map.removeLayer(iotaLayer); }
	}
	/* Antimeridian unwrap for islands spanning ±180° (AN-016 is special-cased). */
	function iotaGeometry(D) {
		let lon1 = Number(D.lon1), lon2 = Number(D.lon2);
		if (D.tag !== 'AN-016') {
			if (lon1 > 0 && lon2 < 0 && lon1 - lon2 > 180) { lon2 += 360; }
			if (lon1 < 0 && lon2 > 0 && lon2 - lon1 > 180) { lon1 += 360; }
		}
		return { lon1: lon1, lon2: lon2 };
	}
	/* Clickable tag label (e.g. EU-001) shown centred on each rectangle. */
	function iotaIcon(tag) {
		return L.divIcon({
			className: 'iota-label',
			html: esc(tag),
			iconSize: [48, 14],
			iconAnchor: [24, 7]
		});
	}
	/* IOTA popup, styled like the WWFF/POTA/SOTA reference popups. The tag links
	 * to its iota-world.org map page; grid + coords are the rectangle's centre. */
	function iotaPopup(D, lat, lng, createHref, spotText) {
		return '<div class="ref-popup" style="border-left-color:#17a2b8">' +
			'<div class="ref-popup-top">' +
			'<span class="ref-popup-type" style="background:#17a2b8">IOTA</span>' +
			'<a class="ref-popup-ref" href="https://www.iota-world.org/iotamaps/?grpref=' + encodeURIComponent(D.tag) + '" target="_blank" rel="noopener noreferrer">' + esc(D.tag) + ' <i class="fas fa-up-right-from-square"></i></a>' +
			'</div>' +
			(D.name ? '<div class="ref-popup-name">' + esc(D.name) + '</div>' : '') +
			(D.prefix ? '<div class="ref-popup-row"><i class="fas fa-broadcast-tower"></i><span>' + esc(D.prefix) + '</span></div>' : '') +
			'<div class="ref-popup-row"><i class="fas fa-th"></i><span>' + esc(latLngToLocator(lat, lng, 3)) + '</span></div>' +
			'<div class="ref-popup-row"><i class="fas fa-location-crosshairs"></i><span>' + fmtLat(lat) + ', ' + fmtLng(lng) + '</span></div>' +
			((createHref || spotText) ? '<div class="gl-pop-actions">' +
				(spotText ? '<a class="gl-pop-action gl-share" style="cursor: pointer;" data-spot="' + esc(spotText) + '"><i class="fas fa-share-nodes"></i> ' + esc(shareLbl) + '</a>' : '') +
				(createHref ? '<a class="gl-pop-action" href="' + esc(createHref) + '"><i class="fas fa-plus"></i> ' + esc(newStationLocLbl) + '</a>' : '') +
			'</div>' : '') +
			'</div>';
	}
	/*
	 * Open-time popup builder for an IOTA tag marker. Reads the stashed refData,
	 * renders the popup immediately with create + share actions (sync), then
	 * enriches both with DXCC/CQ/ITU/state once resolvePointMeta settles — the
	 * same recipe as refPopupRich. The marker sits at the rectangle's centre
	 * (antimeridian-unwrapped in enableIota), so its latLng is reused.
	 */
	function iotaPopupRich(layer) {
		let D = layer.refData;
		if (!D) { return ''; }
		let ll = layer.getLatLng();
		let cLat = ll.lat, cLng = ll.lng, loc = latLngToLocator(cLat, cLng, 3);
		let extra = { iota: D.tag };
		let locDesc = D.tag + ' (' + loc + ')';
		let extraHash = '#IOTA ' + D.tag;
		let baseHref = buildCreateHref(loc, null, extra);
		let baseSpot = buildSpotText(locDesc, null, extraHash);
		resolvePointMeta(cLat, cLng, loc).then(function (meta) {
			if (layer.isPopupOpen && layer.isPopupOpen()) {
				layer.setPopupContent(iotaPopup(D, cLat, cLng, buildCreateHref(loc, meta, extra), buildSpotText(locDesc, meta, extraHash)));
			}
		}).catch(function () { /* leave sync create + share in place */ });
		return iotaPopup(D, cLat, cLng, baseHref, baseSpot);
	}

	/* ---- Reference search (WWFF / POTA / SOTA / IOTA by name or reference) ---- */
	/*
	 * Unified free-text search across the four cached directories. The lowercased
	 * search index per directory is built lazily on first search (so toggling an
	 * overlay but never searching costs nothing), then reused for the session.
	 * Match is a case-insensitive substring on the reference/tag AND the name.
	 */
	let SEARCH_TYPES = [
		{ type: 'wwff', label: 'WWFF', letter: 'W', color: '#2b8cbe' },
		{ type: 'pota', label: 'POTA', letter: 'P', color: '#238b45' },
		{ type: 'sota', label: 'SOTA', letter: 'S', color: '#d95f0e' },
		{ type: 'iota', label: 'IOTA', letter: 'I', color: '#17a2b8' }
	];
	let SEARCH_PER_TYPE = 8, SEARCH_TOTAL = 30, SEARCH_MIN = 2, SEARCH_DEBOUNCE = 180;
	let searchIndexes = null;     // {wwff,pota,sota,iota} -> [{a,b,r}], built once
	let searchTimer = null;       // debounce handle
	let searchActiveIdx = -1;     // keyboard-highlighted row
	let searchCurrent = [];       // current result rows [{type,label,letter,color,r}]

	/* The identifying code of a directory entry: 'reference' for point dirs, 'tag' for IOTA. */
	function refKeyOf(r, type) { return type === 'iota' ? (r.tag || '') : (r.reference || ''); }

	/* Build the lowercased index for one directory from its cached array. */
	function buildSearchIndex(type) {
		let data = refDirs[type];
		if (!Array.isArray(data)) { return []; }
		let out = new Array(data.length);
		for (let i = 0; i < data.length; i++) {
			let r = data[i];
			out[i] = { a: refKeyOf(r, type).toLowerCase(), b: (r.name || '').toLowerCase(), r: r };
		}
		return out;
	}

	/* Load all four directories (cached) and build their indexes once. */
	function ensureSearchIndexes() {
		if (searchIndexes) { return Promise.resolve(searchIndexes); }
		return Promise.all([loadRefDir('wwff'), loadRefDir('pota'), loadRefDir('sota'), loadRefDir('iota')]).then(function () {
			searchIndexes = {};
			SEARCH_TYPES.forEach(function (t) { searchIndexes[t.type] = buildSearchIndex(t.type); });
			return searchIndexes;
		});
	}

	/* Substring match across all types, capping per-type and total, early-exiting each type. */
	function runSearch(q) {
		q = q.toLowerCase();
		let out = [];
		for (let ti = 0; ti < SEARCH_TYPES.length; ti++) {
			let t = SEARCH_TYPES[ti];
			let idx = searchIndexes ? searchIndexes[t.type] : null;
			if (!idx) { continue; }
			let typeCount = 0;
			for (let i = 0; i < idx.length && typeCount < SEARCH_PER_TYPE && out.length < SEARCH_TOTAL; i++) {
				let e = idx[i];
				if (e.a.indexOf(q) > -1 || e.b.indexOf(q) > -1) {
					out.push({ type: t.type, label: t.label, letter: t.letter, color: t.color, group: 'refs', r: e.r });
					typeCount++;
				}
			}
		}
		return out;
	}

	/* A short locator string for a result row (subsquare grid), for the row's right side. */
	function refSubGrid(row) {
		let r = row.r;
		if (row.type === 'iota') {
			let g = iotaGeometry(r);
			let cLat = -(Number(r.lat1) + Number(r.lat2)) / 2;
			let cLng = (g.lon1 + g.lon2) / 2;
			return latLngToLocator(cLat, cLng, 3);
		}
		if (r.lat != null && r.lon != null) { return latLngToLocator(r.lat, r.lon, 3); }
		return '';
	}

	function showSearchLoading() {
		let box = document.getElementById('glRefSearchResults');
		if (!box) { return; }
		box.innerHTML = '<div class="gl-search-loading">' + esc(searchLoadingLbl) + '</div>';
		box.hidden = false;
	}
	function hideSearchResults() {
		let box = document.getElementById('glRefSearchResults');
		if (box) { box.hidden = true; box.innerHTML = ''; }
		searchActiveIdx = -1;
		searchCurrent = [];
	}
	function highlightSearchRow() {
		let box = document.getElementById('glRefSearchResults');
		if (!box) { return; }
		let rows = box.querySelectorAll('.gl-search-item');
		rows.forEach(function (el, i) { el.classList.toggle('is-active', i === searchActiveIdx); });
		let active = rows[searchActiveIdx];
		if (active && active.scrollIntoView) { active.scrollIntoView({ block: 'nearest' }); }
	}
	/*
	 * Render the dropdown. Rows are grouped ('refs' from the local directories,
	 * 'places' from Nominatim); a header is emitted on each group change. Places
	 * use a pin badge instead of a directory letter. Pseudo-rows {loading} and
	 * {empty} render status lines inside their group. The footer carries the
	 * Enter hint (before any place search) or the OSM credit once places show.
	 */
	function renderResults(rows) {
		let box = document.getElementById('glRefSearchResults');
		if (!box) { return; }
		searchCurrent = rows;
		searchActiveIdx = -1;
		if (!rows.length) {
			box.innerHTML = '<div class="gl-search-empty">' + esc(searchNoMatchesLbl) + '</div>';
			box.hidden = false;
			return;
		}
		let counts = {};
		rows.forEach(function (row) { if (!row.loading && !row.empty) { counts[row.group] = (counts[row.group] || 0) + 1; } });
		let lastGroup = null, html = '';
		rows.forEach(function (row, i) {
			if (row.group !== lastGroup) {
				lastGroup = row.group;
				let lbl = row.group === 'places' ? searchPlacesLbl : searchRefsHeaderLbl;
				html += '<div class="gl-search-header">' + esc(lbl) +
					(counts[row.group] ? ' <span class="badge bg-secondary">' + counts[row.group] +
					(row.group === 'refs' && counts[row.group] >= SEARCH_TOTAL ? '+' : '') + '</span>' : '') +
					'</div>';
			}
			if (row.loading) { html += '<div class="gl-search-loading">' + esc(searchLoadingLbl) + '</div>'; return; }
			if (row.empty)  { html += '<div class="gl-search-empty">' + esc(searchNoMatchesLbl) + '</div>'; return; }
			let r = row.r, sub = refSubGrid(row);
			let badge = row.type === 'place' ? '<i class="fas fa-location-dot"></i>' : esc(row.letter);
			html += '<div class="gl-search-item" data-idx="' + i + '">' +
				'<span class="ref-menu-dot" style="background:' + esc(row.color) + '">' + badge + '</span>' +
				'<span class="gl-search-ref">' + esc(refKeyOf(r, row.type)) + '</span>' +
				'<span class="gl-search-name">' + esc(r.name || '') + '</span>' +
				(sub ? '<span class="gl-search-sub">' + esc(sub) + '</span>' : '') +
				'</div>';
		});
		html += '<div class="gl-search-footer">' +
			(lastGroup === 'places' ? '&copy; OpenStreetMap contributors' : esc(searchEnterHintLbl) + ' &middot; &copy; OpenStreetMap') +
			'</div>';
		box.innerHTML = html;
		box.hidden = false;
	}

	/*
	 * Place-name search (geocoding) via OSM's Nominatim — client-side fetch, no
	 * proxy. Fired by Enter (not per keystroke) so the public service's max
	 * 1 request/second policy is respected. Results are appended to the current
	 * reference matches as a "Places" group; selecting one reuses selectPoint
	 * (grid square, local mesh, zones, popup) and fits the result's bounding box.
	 */
	let NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
	let placeAbort = null;
	function searchPlaces(q) {
		let localRows = searchCurrent.filter(function (row) { return row.group === 'refs'; });
		renderResults(localRows.concat([{ group: 'places', loading: true }]));
		if (placeAbort) { placeAbort.abort(); }
		let ctl = new AbortController();
		placeAbort = ctl;
		let timer = setTimeout(function () { ctl.abort(); }, 8000);   // don't hang on offline LANs
		fetch(NOMINATIM_URL + '?q=' + encodeURIComponent(q) + '&format=jsonv2&limit=5', { signal: ctl.signal })
			.then(function (res) { return res.json(); })
			.then(function (rows) {
				let places = (Array.isArray(rows) ? rows : []).map(function (p) {
					let parts = String(p.display_name || '').split(', ');
					return {
						type: 'place', label: 'Place', group: 'places', color: '#6c757d',
						r: {
							reference: parts.shift() || '—',             // primary name (bold slot)
							name: parts.join(', '),                      // remainder of display_name
							lat: Number(p.lat), lon: Number(p.lon),
							bbox: Array.isArray(p.boundingbox) ? p.boundingbox.map(Number) : null
						}
					};
				});
				renderResults(localRows.concat(places.length ? places : [{ group: 'places', empty: true }]));
			})
			.catch(function () {   // offline / timeout / aborted → quiet "no matches"
				renderResults(localRows.concat([{ group: 'places', empty: true }]));
			})
			.finally(function () { clearTimeout(timer); });
	}

	/*
	 * Fly to the selected reference and drop its marker into searchOverlay (kept
	 * separate from refOverlay, which Nearby/Clear/Go wipe). Point refs reuse the
	 * drawRefs marker recipe + refPopupRich; IOTA reuses enableIota's recipe +
	 * iotaPopupRich and fits its (antimeridian-unwrapped) rectangle.
	 */
	function onSearchSelect(row) {
		if (!row) { return; }
		let r = row.r;
		hideSearchResults();
		if (searchOverlay) { searchOverlay.clearLayers(); }
		clearBoundaries();   // drop any park outline drawn by a previous selection / the POTA overlay

		if (row.type === 'place') {
			// Reuse the full selection machinery (grid square, local mesh, zones,
			// arrows, popup), then frame the result's extent when it has one.
			selectPoint(r.lat, r.lon);
			if (r.bbox && r.bbox.length === 4) {
				map.fitBounds([[r.bbox[0], r.bbox[2]], [r.bbox[1], r.bbox[3]]], { padding: [40, 40], maxZoom: 13 });
			}
			return;
		}

		if (row.type === 'iota') {
			let g = iotaGeometry(r);                       // unwrap longitudes (AN-016 special-cased)
			let lat1 = -Number(r.lat1), lat2 = -Number(r.lat2);   // stored sign-reversed
			let cLat = (lat1 + lat2) / 2;
			let cLng = (g.lon1 + g.lon2) / 2;
			// Draw the island's bounding-box rectangle (same style as the IOTA overlay).
			searchOverlay.addLayer(L.polygon(
				[[lat1, g.lon1], [lat2, g.lon1], [lat2, g.lon2], [lat1, g.lon2]],
				{ interactive: false, color: '#17a2b8', weight: 1, fillColor: '#17a2b8', fillOpacity: 0.12 }
			));
			let m = L.marker([cLat, cLng], { icon: iotaIcon(r.tag) });
			m.refData = r;
			m.bindPopup(iotaPopupRich);
			searchOverlay.addLayer(m);
			if (g.lon2 - g.lon1 > 180 || g.lon1 > g.lon2) { // spanning / unwrapped box → centre, not a world-spanning fit
				map.flyTo([cLat, cLng], 9);
			} else {
				map.fitBounds([[Math.min(lat1, lat2), g.lon1], [Math.max(lat1, lat2), g.lon2]], { padding: [40, 40], maxZoom: 11 });
			}
			m.openPopup();
			return;
		}

		if (r.lat == null || r.lon == null) { return; }
		let m = L.marker([r.lat, r.lon], { icon: refIcon(row.color, row.letter) });
		m.refType = row.label; m.refData = r; m.refColor = row.color;
		m.bindPopup(refPopupRich);
		searchOverlay.addLayer(m);
		if (row.type === 'pota') {
			// Draw the park outline; drawParkBoundary fits the map to it once the
			// boundary resolves (supported country prefixes). flyTo below gives an
			// immediate zoom while the outline loads, then the fit settles on the area.
			drawParkBoundary(r.reference, true);
		}
		map.flyTo([r.lat, r.lon], 13);
		m.openPopup();
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

	/*
	 * Resolve the raw station-location metadata for [lat, lng]: CQ + ITU zone
	 * numbers (client-side against cached boundaries), DXCC adif + state code
	 * (server-side via state_for_point, which only knows state-supporting
	 * countries), and the DXCC flag(s) for the 4-char grid (vuccgrids). DXCC
	 * prefers the point-precise state_for_point value, falling back to a
	 * single-entity vuccgrids match for the grid; ambiguous (multi-entity) or
	 * unknown → ''. Any failure → ''. Also returns the display labels. Shared
	 * by the click/go popups and the POTA/SOTA/WWFF reference popups.
	 */
	function resolvePointMeta(lat, lng, loc) {
		return Promise.all([
			zoneFor(lat, lng, 'cq'),
			zoneFor(lat, lng, 'itu'),
			stateUrl ? fetch(stateUrl + '?lat=' + lat + '&lng=' + lng).then(function (r) { return r.json(); }).catch(function () { return null; }) : Promise.resolve(null),
			(dxccGridUrl && loc && loc.length >= 4) ? fetch(dxccGridUrl + '?grid=' + encodeURIComponent(loc.substring(0, 4))).then(function (r) { return r.json(); }).catch(function () { return []; }) : Promise.resolve([])
		]).then(function (res) {
			let cqNum = (res[0] && res[0].num != null) ? res[0].num : '';
			let ituNum = (res[1] && res[1].num != null) ? res[1].num : '';
			let s = res[2];
			let rows = Array.isArray(res[3]) ? res[3] : [];
			let flag = rows.map(function (d) { return d.flag; }).filter(Boolean);
			let stateDxcc = (s && s.dxcc != null) ? s.dxcc : '';
			let gridDxcc  = (rows.length === 1 && rows[0].adif != null) ? rows[0].adif : '';
			let dxccName  = stateDxcc !== '' ? (s && s.country ? s.country : '')
			             : (gridDxcc !== '' ? (rows[0].name || '') : '');
			let zParts = [];
			if (cqNum !== '')  { zParts.push('CQ ' + cqNum); }
			if (ituNum !== '') { zParts.push('ITU ' + ituNum); }
			return {
				cq:         cqNum,
				itu:        ituNum,
				dxcc:       stateDxcc || gridDxcc,
				dxccName:   dxccName,
				stateCode:  (s && s.code != null) ? s.code : '',
				zoneLabel:  zParts.join(' / '),
				stateLabel: s ? stateStr(s) : '',
				flag:       flag
			};
		});
	}

	/* Build the station/create URL with location params, omitting empty values. */
	function buildCreateHref(loc, meta, extra) {
		let p = { gridsquare: loc };
		if (meta) {
			if (meta.cq)        { p.station_cq = meta.cq; }
			if (meta.itu)       { p.station_itu = meta.itu; }
			if (meta.dxcc)      { p.dxcc = meta.dxcc; }
			if (meta.stateCode) { p.station_state = meta.stateCode; }
		}
		if (extra) { for (let k in extra) { if (extra[k]) { p[k] = extra[k]; } } }
		let qs = [];
		for (let k in p) { if (p[k] != null && p[k] !== '') { qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(p[k])); } }
		return createStationUrl + (qs.length ? '?' + qs.join('&') : '');
	}

	/*
	 * Compose the social-share text for a point: the translatable lead with the
	 * location descriptor, the DXCC entity name only when it differs from the
	 * user's active station DXCC, then the hashtags (#hamr #wavelog, plus any
	 * extra such as a reference). locDesc is e.g. "JN59ab" or "DL/NW-001 (JN59ab)".
	 */
	function buildSpotText(locDesc, meta, extraHash) {
		if (!locDesc) { return ''; }
		let t = planningActivationLbl.replace('%s', locDesc);
		if (meta && meta.dxcc && meta.dxccName && userDxcc != null &&
			String(meta.dxcc) !== String(userDxcc)) {
			t += ' \u2014 ' + meta.dxccName;   // em dash + entity name
		}
		t += ' #hamr #wavelog';
		if (extraHash) { t += ' ' + extraHash; }
		return t;
	}

	/* Open the existing share modal (X / Bluesky / Mastodon) with a spot text. */
	function shareActivation(text) {
		if (typeof shareModal === 'function' && text) {
			shareModal({ twitter_string: text }, shareActivationTitleLbl);
		}
	}

	function init() {
		map = L.map('glMap', { worldCopyJump: true }).setView(initialView, initialZoom);

		L.tileLayer(tileUrl, {
			minZoom: 3,
			maxZoom: 19,
			attribution: tileAttr
		}).addTo(map);

		L.control.scale({ imperial: false, metric: true }).addTo(map);

		// Build the Maidenhead grid layer but leave it OFF until the Refs → Gridsquare
		// checkbox is toggled on (it defaults to off on page load).
		if (typeof L.maidenheadqrb === 'function') {
			gridOverlay = L.maidenheadqrb();
		}

		L.control.fullscreen && L.control.fullscreen().addTo(map);

		// Delegated handler: any "Share" action inside a popup opens the share
		// modal with the spot text stashed in data-spot. One listener covers the
		// grid popup + all POTA/SOTA/WWFF reference popups.
		map.getContainer().addEventListener('click', function (e) {
			let el = e.target.closest ? e.target.closest('.gl-share') : null;
			if (el) { shareActivation(el.dataset.spot || ''); }
		});

		// Floating compass readout of the surrounding square borders. A custom
		// Leaflet control so it stays parked in the corner while the map pans,
		// and is hidden until a point is selected (see showBorders/clearBorders).
		let BordersControl = L.Control.extend({
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
				let btn = html && this._c.querySelector('.gl-comp-close');
				if (btn) {
					L.DomEvent.on(btn, 'click', function (ev) {
						L.DomEvent.stop(ev);
						hideBordersPanel();   // dismiss the legend only; keep the map arrows
					});
				}
			},
			clear: function () { this.setContent(''); }
		});
		bordersControl = new BordersControl();
		bordersControl.addTo(map);

		// Layer group holding the on-map direction arrows (see drawArrows).
		bordersOverlay = L.layerGroup().addTo(map);

		// Layer group holding the selection's local Maidenhead mesh (see drawLocalGrid).
		localGridLayer = L.layerGroup().addTo(map);

		// Layer group holding the POTA/SOTA/WWFF markers near a click (see drawRefs).
		refOverlay = L.layerGroup().addTo(map);

		// Layer group holding markers dropped by a reference search (see onSearchSelect).
		// Kept separate from refOverlay, which Nearby/Clear/Go wipe — so a search
		// marker survives those actions until a new search or a Clear replaces it.
		searchOverlay = L.layerGroup().addTo(map);

		document.getElementById('glGo').addEventListener('click', go);
		document.getElementById('glClear').addEventListener('click', clearAll);
		let locateBtn = document.getElementById('glLocate');
		if (locateBtn) {
			locateBtn.addEventListener('click', locate);
			// Hide the button entirely when geolocation is unavailable (e.g. plain
			// HTTP on a LAN IP) so users never see a dead control.
			if (!('geolocation' in navigator)) { locateBtn.style.display = 'none'; }
		}
		// "Nearby refs": list WWFF/POTA/SOTA references within 20 km of the grid.
		let nearbyBtn = document.getElementById('glNearby');
		if (nearbyBtn) {
			// Tooltip follows the user's unit (e.g. "References within 12.4 mi …").
			nearbyBtn.title = nearbyRefsRadiusLbl.replace('%s', kmToUnit(20, measurementBase) + ' ' + unitLabel(measurementBase));
			nearbyBtn.addEventListener('click', function () {
				let raw = (document.getElementById('glGrid').value || '').trim();
				let cell = locatorToCell(raw);
				if (!cell) { showToast(errorLbl, esc(invalidMsg), 'bg-warning text-dark', 3000); return; }
				nearbyBtn.disabled = true;
				fetch(refsNearbyUrl + '?lat=' + cell.center[0] + '&lng=' + cell.center[1])
					.then(function (r) { return r.json(); })
					.then(function (rows) { showNearbyModal(raw.toUpperCase(), rows || []); })
					.catch(function () { showToast(errorLbl, 'Failed to load nearby references', 'bg-danger text-white', 3000); })
					.finally(function () { nearbyBtn.disabled = false; });
			});
		}

		// Mobile "More options" disclosure: toggles a class on the toolbar that
		// CSS uses to show/hide the secondary controls (grid 2 + overlays).
		let moreBtn = document.getElementById('glMore');
		if (moreBtn) {
			moreBtn.addEventListener('click', function () {
				let controls = document.getElementById('glControls');
				let expanded = controls.classList.toggle('gl-expanded');
				moreBtn.setAttribute('aria-expanded', String(expanded));
			});
		}
		document.getElementById('glGridOverlay').addEventListener('change', function () {
			if (!gridOverlay) return;
			if (this.checked) {
				gridOverlay.addTo(map);
				// The local selection mesh only shows while the global overlay is
				// off: turning the overlay on clears it.
				if (localGridLayer) { localGridLayer.clearLayers(); }
			} else {
				map.removeLayer(gridOverlay);
				// Turning it off restores the mesh around the current selection (if any).
				if (localGridPoint) {
					drawLocalGrid(localGridPoint[0], localGridPoint[1]);
				}
			}
		});

		// Optional WWFF reference directory overlay. Built lazily on first toggle
		// (the directory can be large), then cached for instant re-toggle.
		let wwffCb = document.getElementById('glWwffDir');
		if (wwffCb) {
			wwffCb.addEventListener('change', function () {
				if (this.checked) { enableWwff(); } else { disableWwff(); }
			});
		}
		let potaCb = document.getElementById('glPotaDir');
		if (potaCb) {
			potaCb.addEventListener('change', function () {
				if (this.checked) { enablePota(); } else { disablePota(); }
			});
		}
		let sotaCb = document.getElementById('glSotaDir');
		if (sotaCb) {
			sotaCb.addEventListener('change', function () {
				if (this.checked) { enableSota(); } else { disableSota(); }
			});
		}
		let iotaCb = document.getElementById('glIotaDir');
		if (iotaCb) {
			iotaCb.addEventListener('change', function () {
				if (this.checked) { enableIota(); } else { disableIota(); }
			});
		}

		// Reflect Refs/Overlays dropdown state on their toggle buttons.
		let refsDrop = document.querySelector('.gl-refs');
		if (refsDrop) {
			let refsBtn = refsDrop.querySelector('.dropdown-toggle');
			let refsMenu = refsDrop.querySelector('.dropdown-menu');
			if (refsBtn && refsMenu) {
				refsMenu.addEventListener('change', function () { syncToggleActive(refsMenu, refsBtn); });
				syncToggleActive(refsMenu, refsBtn);   // nothing checked by default -> inactive
			}
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

		// Reference search: debounced substring match across the four cached
		// directories. Indexes are built lazily on the first qualifying keystroke.
		let searchInput = document.getElementById('glRefSearch');
		if (searchInput) {
			searchInput.addEventListener('input', function () {
				let q = searchInput.value.trim();
				if (q.length < SEARCH_MIN) { hideSearchResults(); return; }
				if (searchTimer) { clearTimeout(searchTimer); }
				searchTimer = setTimeout(function () {
					if (searchIndexes) { renderResults(runSearch(q)); return; }
					showSearchLoading();
					ensureSearchIndexes().then(function () {
						let cur = searchInput.value.trim();      // re-read: user may have kept typing
						if (cur.length < SEARCH_MIN) { hideSearchResults(); return; }
						renderResults(runSearch(cur));
					});
				}, SEARCH_DEBOUNCE);
			});
			searchInput.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') { hideSearchResults(); searchInput.blur(); return; }
				if (e.key === 'Enter') {
					e.preventDefault();
					if (searchActiveIdx >= 0 && searchActiveIdx < searchCurrent.length) {
						onSearchSelect(searchCurrent[searchActiveIdx]);   // highlighted row
					} else {
						let q = searchInput.value.trim();
						if (q.length >= SEARCH_MIN) { searchPlaces(q); } // place-name geocoding
					}
					return;
				}
				if (!searchCurrent.length) { return; }
				if (e.key === 'ArrowDown') {
					e.preventDefault();
					searchActiveIdx = (searchActiveIdx + 1) % searchCurrent.length;
					highlightSearchRow();
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					searchActiveIdx = (searchActiveIdx - 1 + searchCurrent.length) % searchCurrent.length;
					highlightSearchRow();
				}
			});
			// Clicking a result row selects it. mousedown prevention keeps focus on
			// the input so the row's click registers before any blur.
			let searchBox = document.getElementById('glRefSearchResults');
			if (searchBox) {
				searchBox.addEventListener('mousedown', function (e) { e.preventDefault(); });
				searchBox.addEventListener('click', function (e) {
					let item = e.target.closest ? e.target.closest('.gl-search-item') : null;
					if (!item) { return; }
					let idx = parseInt(item.dataset.idx, 10);
					if (!isNaN(idx) && searchCurrent[idx]) { onSearchSelect(searchCurrent[idx]); }
				});
			}
			// Dismiss the dropdown on any click outside the search wrapper.
			document.addEventListener('click', function (e) {
				let wrap = searchInput.closest('.gl-search-wrap');
				if (wrap && !wrap.contains(e.target)) { hideSearchResults(); }
			});
		}

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

		// Grow/shrink the map when the Search panel collapses/expands, and let
		// Leaflet pick up the new size once the transition has finished.
		let topBody = document.getElementById('glTopBody');
		if (topBody) {
			topBody.addEventListener('hidden.bs.collapse', function () { map.invalidateSize(); });
			topBody.addEventListener('shown.bs.collapse', function () { map.invalidateSize(); });
		}

		// Leaflet needs a nudge when its container was sized after init.
		setTimeout(function () { map.invalidateSize(); }, 200);
	}

	function clearAll() {
		if (highlight)   { map.removeLayer(highlight);   highlight = null; }
		if (marker)      { map.removeLayer(marker);      marker = null; }
		if (clickSquare) { map.removeLayer(clickSquare); clickSquare = null; }
		if (clickMarker) { map.removeLayer(clickMarker); clickMarker = null; }
		if (refOverlay)  { refOverlay.clearLayers(); }   // nearby POTA/SOTA/WWFF markers
		clearBoundaries();                               // drawn park boundaries
		clearSecond();   // grid-2 square, marker and path line
		clearBorders();  // square-border readout
		stopTracking();  // stop autotracking, restore the Locate button

		// Zoom back out to the default world view.
		map.setView(initialView, initialZoom);

		document.getElementById('glGrid').value = '';
		document.getElementById('glGrid2').value = '';
		document.getElementById('glInfo').textContent = '';

		// Reference search: clear its field, dismiss the dropdown, drop its marker.
		let searchInput = document.getElementById('glRefSearch');
		if (searchInput) { searchInput.value = ''; }
		hideSearchResults();
		if (searchOverlay) { searchOverlay.clearLayers(); }
	}

	function onMapClick(e) {
		// While autotracking, ignore map clicks so the GPS marker keeps following
		// uninterrupted. Stop tracking (Locate/Clear) to click-select again.
		if (trackTimer !== null) { return; }
		// Ignore clicks that began on a floating control (the borders panel and
		// its dismiss button). Modern Leaflet routes map clicks through pointer
		// events that disableClickPropagation doesn't stop, so without this the
		// dismiss click would land here and immediately re-select a grid.
		let t = e.originalEvent && e.originalEvent.target;
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

		// Fill the gridsquare box so Go / QRB can be used from the clicked point.
		document.getElementById('glGrid').value = loc;

		// Only one click marker + square at a time: drop the previous ones.
		if (clickMarker) { map.removeLayer(clickMarker); clickMarker = null; }
		if (clickSquare) { map.removeLayer(clickSquare); clickSquare = null; }
		// Also clear any from/to (two-grid) artefacts and the dimmed-label state.
		clearSecond();
		if (highlight) { map.removeLayer(highlight); highlight = null; }
		if (marker)    { map.removeLayer(marker);    marker = null; }
		setLabelsDim(false);
		if (refOverlay) { refOverlay.clearLayers(); }

		clickSquare = L.rectangle([cell.sw, cell.ne], {
			color: '#198754', weight: 3, fillColor: '#198754', fillOpacity: 0.18
		}).addTo(map);

		clickMarker = L.marker([lat, lng]).addTo(map)
			.bindPopup(gridPopupHTML(lat, lng, loc, '', '', null, '', buildCreateHref(loc, null), buildSpotText(loc, null)));

		// Frame the selection — on phones this centres on the 4-char square so
		// the border arrows are in view; desktop fits the exact clicked cell.
		zoomToGrid(lat, lng, cell);

		let info = document.getElementById('glInfo');
		let baseInfo = '<strong>' + loc + '</strong> &middot; ' + fmtLat(lat) + ', ' + fmtLng(lng);
		let zones = '', stateLabel = '', refs = null, meta = null, flag = '';

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
			if (clickMarker) { clickMarker.setPopupContent(gridPopupHTML(lat, lng, loc, zones, stateLabel, refs, flag, buildCreateHref(loc, meta), buildSpotText(loc, meta))); }
		}
		renderInfo();
		showBorders(lat, lng);   // distance/bearing to each surrounding square edge

		let myReq = ++zoneReq;
		// CQ/ITU zones + DXCC + state + flag — one combined resolve.
		resolvePointMeta(lat, lng, loc).then(function (m) {
			if (myReq !== zoneReq) { return; }
			meta = m;
			zones = m.zoneLabel;
			stateLabel = m.stateLabel;
			flag = m.flag;
			renderInfo();
			renderPopup();
		});
		// POTA/SOTA/WWFF references inside the clicked 6-char grid, plotted on the map.
		refsInSquare(lat, lng).then(function (rr) {
			if (myReq !== zoneReq) { return; }
			refs = rr;
			drawRefs(rr);
		});
	}

	/*
	 * "Locate me": ask the browser for the current GPS position (mobile or
	 * desktop), convert it to a gridsquare, and zoom in — exactly like clicking
	 * the map there. The click also starts autotracking: the position is polled
	 * every 60 s so the marker/square follow the device. Clear stops it.
	 * Requires a secure context (HTTPS or localhost); the button is
	 * hidden up front in init() when geolocation is unavailable.
	 */
	function locate() {
		if (!('geolocation' in navigator)) { return; }
		startTracking();    // show tracking state + ensure the 60 s poll is running
		locateOnce(true);   // immediate fix (shows "Locating…")
	}

	/* Poll the position every 60 s and re-select on each update. The button is
	 * (re)set on every call so the green "Tracking" state can never get stuck
	 * off while the timer is quietly running. */
	function startTracking() {
		if (trackTimer === null) {
			trackTimer = setInterval(function () { locateOnce(false); }, 60000);
		}
		setTrackingButton(true);
	}

	/* Stop autotracking and restore the Locate button. */
	function stopTracking() {
		if (trackTimer !== null) { clearInterval(trackTimer); trackTimer = null; }
		setTrackingButton(false);
	}

	/* Toggle the Locate button between its normal and active "Tracking" look. */
	function setTrackingButton(on) {
		let btn = document.getElementById('glLocate');
		if (!btn) { return; }
		if (on) {
			if (!btn.dataset.origHtml) { btn.dataset.origHtml = btn.innerHTML; }
			btn.classList.add('gl-tracking');
			btn.innerHTML = '<i class="fa fa-location-crosshairs"></i> ' + esc(trackingLbl);
		} else {
			btn.classList.remove('gl-tracking');
			if (btn.dataset.origHtml) { btn.innerHTML = btn.dataset.origHtml; }
		}
	}

	/*
	 * One position fix. `isInitial` shows the "Locating…" indicator (and a toast
	 * on error) for the first call; periodic polls update silently and only bail
	 * out on a hard permission denial.
	 */
	function locateOnce(isInitial) {
		if (!('geolocation' in navigator)) { return; }
		let info = document.getElementById('glInfo');

		if (isInitial) { info.textContent = locatingMsg; }   // "Locating…" cue (button stays active)

		navigator.geolocation.getCurrentPosition(function (pos) {
			let lat = pos.coords.latitude, lng = pos.coords.longitude;
			let loc = latLngToLocator(lat, lng, 3);
			// Fill grid 1 so the user can immediately type a second grid and hit Go
			// for a QRB (distance/bearing) from their current location.
			document.getElementById('glGrid').value = loc;
			selectPoint(lat, lng, loc);
		}, function (geoErr) {
			if (isInitial) { info.textContent = ''; }
			if (geoErr.code === 1) {                            // permission denied → won't recover
				stopTracking();
				showToast(errorLbl, esc(geoDenied), 'bg-danger text-white', 4000);
				return;
			}
			if (isInitial) {                                    // transient: only toast on first attempt
				let msg;
				switch (geoErr.code) {
					case 2:  msg = geoUnavailable; break;       // position unavailable
					case 3:  msg = geoTimeout; break;           // timed out
					default: msg = geoErr.message || geoUnavailable;
				}
				showToast(errorLbl, esc(msg), 'bg-warning text-dark', 4000);
			}
		}, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
	}

	function go() {
		let info = document.getElementById('glInfo');

		// Grid 1 is required; grid 2 is optional and enables distance/bearing.
		let cell1 = locatorToCell(document.getElementById('glGrid').value);
		let raw2  = (document.getElementById('glGrid2').value || '').trim();
		let cell2 = raw2 ? locatorToCell(raw2) : null;
		if (!cell1 || (raw2 && !cell2)) {
			info.textContent = '';
			showToast(errorLbl, invalidMsg, 'bg-danger text-white', 4000);
			return;
		}

		// Drop any second-grid artefacts; rebuilt below only when grid 2 is set.
		clearSecond();
		clearBorders();   // only the single-grid case shows square borders below
		// Clear any left-over click marker/square from a prior map click.
		if (clickSquare) { map.removeLayer(clickSquare); clickSquare = null; }
		if (clickMarker) { map.removeLayer(clickMarker); clickMarker = null; }
		if (refOverlay)  { refOverlay.clearLayers(); }   // prior POTA/SOTA/WWFF markers

		// Grid 1 square + marker (blue).
		if (highlight) { map.removeLayer(highlight); }
		highlight = L.rectangle([cell1.sw, cell1.ne], {
			color: '#0d6efd', weight: 3, fillColor: '#0d6efd', fillOpacity: 0.18
		}).addTo(map);

		if (marker) { map.removeLayer(marker); }
		marker = L.marker(cell1.center).addTo(map).bindPopup(gridPopupHTML(cell1.center[0], cell1.center[1], cell1.loc, '', '', null, '', buildCreateHref(cell1.loc, null), buildSpotText(cell1.loc, null)));

		if (cell2) {
			// Tint both 4-char squares and dim the rest of the grid labels.
			showTwoGridTint(cell1, cell2);

			// Grid 2 square + marker (orange).
			highlight2 = L.rectangle([cell2.sw, cell2.ne], {
				color: '#fd7e14', weight: 3, fillColor: '#fd7e14', fillOpacity: 0.18
			}).addTo(map);

			marker2 = L.marker(cell2.center).addTo(map).bindPopup(gridPopupHTML(cell2.center[0], cell2.center[1], cell2.loc, '', '', null, '', buildCreateHref(cell2.loc, null), buildSpotText(cell2.loc, null)));

			// Great-circle path between the two cell centres.
			pathLine = L.polyline(greatCircle(cell1.center, cell2.center, 64), {
				color: '#ff2d92', weight: 3, dashArray: '6,6', opacity: 1
			}).addTo(map);

			// QRB: distance + bearing grid1 -> grid2 (port of application/libraries/Qra.php).
			let dist = calcDistance(cell1.center[0], cell1.center[1], cell2.center[0], cell2.center[1], measurementBase);
			let brg = getBearing(cell1.center[0], cell1.center[1], cell2.center[0], cell2.center[1]);

			// Composed readout: each grid's "(zones, state)" tag fills in
			// asynchronously and re-renders. zoneReq guards against stale updates.
			let z1 = '', z2 = '', s1 = '', s2 = '', f1 = '', f2 = '', m1 = null, m2 = null;
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
				if (marker)  { marker.setPopupContent(gridPopupHTML(cell1.center[0], cell1.center[1], cell1.loc, z1, s1, null, f1, buildCreateHref(cell1.loc, m1), buildSpotText(cell1.loc, m1))); }
				if (marker2) { marker2.setPopupContent(gridPopupHTML(cell2.center[0], cell2.center[1], cell2.loc, z2, s2, null, f2, buildCreateHref(cell2.loc, m2), buildSpotText(cell2.loc, m2))); }
			}

			map.fitBounds(L.latLngBounds([cell1.sw, cell1.ne, cell2.sw, cell2.ne]),
				{ padding: [60, 60], maxZoom: 17 });

			let myReq = ++zoneReq;
			// CQ/ITU zones + DXCC + state + flag — one resolve per grid.
			resolvePointMeta(cell1.center[0], cell1.center[1], cell1.loc).then(function (mm) {
				if (myReq !== zoneReq) { return; }
				m1 = mm; z1 = mm.zoneLabel; s1 = mm.stateLabel; f1 = mm.flag;
				render();
				renderPopups();
			});
			resolvePointMeta(cell2.center[0], cell2.center[1], cell2.loc).then(function (mm) {
				if (myReq !== zoneReq) { return; }
				m2 = mm; z2 = mm.zoneLabel; s2 = mm.stateLabel; f2 = mm.flag;
				render();
				renderPopups();
			});
		} else {
			zoomToGrid(cell1.center[0], cell1.center[1], cell1);

			let z1 = '', s1 = '', r1 = null, f1 = '', m1 = null;
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
				if (marker) { marker.setPopupContent(gridPopupHTML(cell1.center[0], cell1.center[1], cell1.loc, z1, s1, r1, f1, buildCreateHref(cell1.loc, m1), buildSpotText(cell1.loc, m1))); }
			}
			showBorders(cell1.center[0], cell1.center[1]);   // surrounding square edges for this grid

			let myReq = ++zoneReq;
			// CQ/ITU zones + DXCC + state + flag — one combined resolve.
			resolvePointMeta(cell1.center[0], cell1.center[1], cell1.loc).then(function (mm) {
				if (myReq !== zoneReq) { return; }
				m1 = mm; z1 = mm.zoneLabel; s1 = mm.stateLabel; f1 = mm.flag;
				render();
				renderPopup();
			});
			// POTA/SOTA/WWFF references inside this grid's 6-char square, plotted on the map.
			refsInSquare(cell1.center[0], cell1.center[1]).then(function (rr) {
				if (myReq !== zoneReq) { return; }
				r1 = rr;
				drawRefs(rr);
				renderPopup();
			});
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
