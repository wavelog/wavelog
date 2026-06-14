/**
 * MapComponent - Map display using Leaflet
 * Shows QSO locations and gridsquares
 * Integrates with DataStore for real-time updates
 */
class MapComponent {
	constructor(containerId, dataStore) {
		this.container = document.getElementById(containerId);
		if (!this.container) {
			console.error(`MapComponent: Container #${containerId} not found`);
			return;
		}

		this.dataStore = dataStore;
		this.map = null;
		this.markers = null;
		this.scriptsLoaded = false;
		this.iconDotUrl = base_url + 'assets/images/dot.png';
		this.iconSize = [12, 12];
		this.redIcon = null; // Will be initialized after Leaflet loads

		// Load dependencies then initialize
		this.loadDependencies().then(() => {
			this.init();
		}).catch(err => {
			console.error('MapComponent: Failed to load dependencies', err);
		});
	}

	/**
	 * Dynamically load Leaflet and plugins
	 * @returns {Promise}
	 */
	loadDependencies() {
		// Check if already loaded globally
		if (typeof L !== 'undefined' && L.map) {
			this.scriptsLoaded = true;
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const assets = window.MapComponentAssets;
			if (!assets) {
				reject('MapComponentAssets not found');
				return;
			}

			const totalScripts = assets.length;

			const loadScript = (index) => {
				if (index >= totalScripts) {
					// All scripts loaded, wait for L to be available
					this.waitForLeaflet().then(() => {
						this.scriptsLoaded = true;
						resolve();
					}).catch(reject);
					return;
				}

				const script = document.createElement('script');
				script.src = assets[index];
				script.async = false; // Force sequential execution
				script.onload = () => {
					// Small delay to ensure script is executed
					setTimeout(() => loadScript(index + 1), 50);
				};
				script.onerror = () => {
					reject(`Failed to load ${assets[index]}`);
				};
				document.head.appendChild(script);
			};

			// Start loading from first script
			loadScript(0);
		});
	}

	/**
	 * Wait for Leaflet library to be fully initialized
	 * @returns {Promise}
	 */
	waitForLeaflet() {
		return new Promise((resolve, reject) => {
			let attempts = 0;
			const maxAttempts = 50; // 5 seconds max

			const check = () => {
				attempts++;

				if (typeof L !== 'undefined' && L.map && typeof L.map === 'function') {
					resolve();
				} else if (attempts >= maxAttempts) {
					reject('Timeout waiting for Leaflet to initialize');
				} else {
					setTimeout(check, 100);
				}
			};

			check();
		});
	}

	/**
	 * Decode a Maidenhead locator to [lat, lon] (center of the field).
	 * JS mirror of Qra::qra2latlong() (application/libraries/Qra.php). Accepts 2/4/6/8/10
	 * character locators; pads to the field center like the PHP version. Returns null on
	 * invalid input.
	 * @param {string} grid
	 * @returns {[number, number]|null}
	 */
	gridToLatLng(grid) {
		if (!grid) return null;
		let q = String(grid).replace(/\s+/g, '').toUpperCase();
		if (q.length % 2 !== 0 || q.length > 10) return null;

		// Pad to center, matching the PHP implementation
		if (q.length === 2) q += '55';
		if (q.length === 4) q += 'LL';
		if (q.length === 6) q += '55';
		if (q.length === 8) q += 'LL';

		if (!/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/.test(q)) return null;

		const c = q.split('');
		const A = 'A'.charCodeAt(0), Z = '0'.charCodeAt(0);
		const a = c[0].charCodeAt(0) - A, b = c[1].charCodeAt(0) - A;
		const d = c[2].charCodeAt(0) - Z, e = c[3].charCodeAt(0) - Z;
		const f = c[4].charCodeAt(0) - A, g = c[5].charCodeAt(0) - A;
		const h = c[6].charCodeAt(0) - Z, i = c[7].charCodeAt(0) - Z;
		const j = c[8].charCodeAt(0) - A, k = c[9].charCodeAt(0) - A;

		const lon = (a * 20) + (d * 2) + (f / 12) + (h / 120) + (j / 2880) - 180;
		const lat = (b * 10) + e + (g / 24) + (i / 240) + (k / 5760) - 90;
		return [lat, lon];
	}

	/**
	 * Resolve a QSO's display coordinates: prefer the entered gridsquare (precise),
	 * fall back to DXCC country center lat/lon, else null.
	 * @param {Object} qso
	 * @returns {[number, number]|null}
	 */
	resolveLatLng(qso) {
		if (!qso) return null;
		const fromGrid = this.gridToLatLng(qso.grid ?? qso.gridsquare_rcvd ?? qso.locator);
		if (fromGrid) return fromGrid;
		const lat = parseFloat(qso.lat), lon = parseFloat(qso.lon ?? qso.long);
		if (Number.isFinite(lat) && Number.isFinite(lon)) return [lat, lon];
		return null;
	}

	init() {
		// Verify Leaflet is loaded
		if (typeof L === 'undefined' || !L.map) {
			console.error('MapComponent: Leaflet library not loaded');
			return;
		}

		// Icons: red dot = current QSO, dim dot = trail, home = own station
		this.redIcon = L.icon({ iconUrl: this.iconDotUrl, iconSize: this.iconSize });
		this.trailIcon = L.divIcon({
			className: 'contest-map-trail-dot',
			html: '<span></span>',
			iconSize: [8, 8]
		});
		this.stationIcon = L.divIcon({
			className: 'contest-map-station',
			html: '&#9733;', // star
			iconSize: [18, 18]
		});
		this.blueIcon = L.divIcon({
			className: 'contest-map-current-qso',
			html: '<span></span>',
			iconSize: [8, 8]
		});

		// Per-QSO markers, keyed by serverId or tmpId, so edits move a marker instead of
		// duplicating it. Mirrors how the QSO table tracks rows.
		this.qsoMarkers = new Map();
		this.currentKey = null;   // key of the highlighted (current) QSO
		this.pathLine = null;     // geodesic line station -> current QSO
		this.stationMarker = null;
		this.stationLatLng = null;
		this.nightLayer = null;
		this.nightInterval = null;

		// User map preferences (per-user, server-persisted), default all on
		const prefs = window.ContestLoggerConfig?.mapPrefs || {};
		this.prefs = {
			nightshadow: prefs.nightshadow !== false,
			pathline: prefs.pathline !== false,
			station: prefs.station !== false,
			autofit: prefs.autofit !== false,
			grid: prefs.grid !== false
		};

		// Initialize Leaflet map
		this.initMap();

		// Listen for QSO updates from DataStore
		if (this.dataStore) {
			this.dataStore.on('qso_added', (qso) => {
				this.upsertQsoMarker(qso, true);
			});

			// Current QSO location preview (callsign/grid entry, before logging)
			this.dataStore.on('qso_location_updated', (location) => {
				this.showCurrentLocation(location);
			});

			// Full resync / delta applied: re-plot all QSOs
			this.dataStore.on('qsos_resynced', () => {
				this.plotAllQsos();
			});
		}

		// Initial fill from whatever is already in the store
		this.plotAllQsos();

		// console.info('MapComponent: Initialized');
	}

	initMap() {
		const mapConfig = window.ContestLoggerConfig?.map || {};
		const tileServer = mapConfig.tileServer || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		const copyright = mapConfig.tileServerCopyright || '&copy; OpenStreetMap contributors';
		const subdomains = mapConfig.tileSubdomains || 'abc';

		// Initialize map with default view
		this.map = L.map(this.container, {
			center: [20, 0],
			zoom: 2,
			zoomControl: true
		});

		// Add tile layer
		L.tileLayer(tileServer, {
			attribution: copyright,
			subdomains: subdomains,
			maxZoom: 18
		}).addTo(this.map);

		// Initialize marker layer
		this.markers = L.layerGroup().addTo(this.map);

		// Add fullscreen control if available
		if (L.control.fullscreen) {
			L.control.fullscreen({
				position: 'topleft'
			}).addTo(this.map);
		}

		// Own station marker (from session gridsquare)
		const stationGrid = window.ContestLoggerConfig?.sessionInfo?.station_gridsquare;
		this.stationLatLng = this.gridToLatLng(stationGrid);
		if (this.stationLatLng && this.prefs.station) {
			this.stationMarker = L.marker(this.stationLatLng, { icon: this.stationIcon })
				.addTo(this.map);
		}

		// Maidenhead grid overlay
		if (typeof L.maidenheadqrb === 'function') {
			this.gridLayer = L.maidenheadqrb();
			if (this.prefs.grid) this.gridLayer.addTo(this.map);
		}

		// Night shadow (day/night terminator)
		if (typeof L.terminator === 'function') {
			this.nightLayer = L.terminator();
			if (this.prefs.nightshadow) this.nightLayer.addTo(this.map);
			// Keep the grayline current in this long-lived view
			this.nightInterval = setInterval(() => {
				if (this.nightLayer && typeof this.nightLayer.setTime === 'function') {
					this.nightLayer.setTime(new Date());
				}
			}, 5 * 60 * 1000);
		}

		// Layer toggle control (persisted per-user)
		this.addToggleControl();

		// Invalidate size to ensure proper rendering
		// Multiple times to handle window creation/resize
		setTimeout(() => {
			if (this.map) {
				this.map.invalidateSize();
			}
		}, 100);

		setTimeout(() => {
			if (this.map) {
				this.map.invalidateSize();
			}
		}, 500);

		// Listen for window resize events
		const resizeObserver = new ResizeObserver(() => {
			if (this.map) {
				this.map.invalidateSize();
			}
		});

		if (this.container.parentElement) {
			resizeObserver.observe(this.container.parentElement);
			this._resizeObserver = resizeObserver;
		}

		// Reveal coordinate bar (elements start hidden via .cohidden)
		const mapContainer = this.container.closest('.map-container');
		if (mapContainer) {
			mapContainer.querySelectorAll('.cohidden').forEach(el => el.classList.remove('cohidden'));
		}

		// Live coordinate bar: update on mouse move
		this.map.on('mousemove', (e) => {
			this._updateCoordinateBar(e.latlng.lat, e.latlng.lng);
		});
	}

	/**
	 * Stable key for a QSO marker: prefer serverId, fall back to tmpId.
	 */
	_qsoKey(qso) {
		return qso.serverId ? `s${qso.serverId}` : (qso.tmpId ? `t${qso.tmpId}` : null);
	}

	/**
	 * Insert or move a QSO's marker. Markers are kept in a Map keyed by _qsoKey, so an
	 * edit (same QSO, new position) moves the existing marker instead of duplicating it.
	 * @param {Object} qso
	 * @param {boolean} makeCurrent  Highlight this QSO as the current one (red + pan).
	 */
	upsertQsoMarker(qso, makeCurrent = false) {
		if (!this.map || !this.markers) return;
		const key = this._qsoKey(qso);
		if (!key) return;

		const latlng = this.resolveLatLng(qso);
		if (!latlng) {
			// No position: drop any stale marker for this key
			this._removeMarker(key);
			return;
		}

		let marker = this.qsoMarkers.get(key);
		if (marker) {
			marker.setLatLng(latlng);
		} else {
			marker = L.marker(latlng, { icon: this.trailIcon });
			this.markers.addLayer(marker);
			this.qsoMarkers.set(key, marker);
		}

		if (makeCurrent) this.setCurrent(key, latlng);
	}

	_removeMarker(key) {
		const m = this.qsoMarkers.get(key);
		if (m) {
			this.markers.removeLayer(m);
			this.qsoMarkers.delete(key);
		}
	}

	/**
	 * Mark the given marker as the current QSO: red icon + pan, and demote the previous
	 * current marker back to the dim trail icon. Draws the station→QSO path line.
	 */
	setCurrent(key, latlng) {
		if (this.currentKey && this.currentKey !== key) {
			const prev = this.qsoMarkers.get(this.currentKey);
			if (prev) prev.setIcon(this.trailIcon);
		}
		this.currentKey = key;
		const marker = this.qsoMarkers.get(key);
		if (marker) marker.setIcon(this.redIcon);
		if (latlng) {
			if (this.prefs.autofit) {
				this.fitAllMarkers();
			} else {
				this.map.panTo(latlng);
			}
			this.updatePathLine(latlng);
		}
	}

	/**
	 * Live preview of the QSO being entered (callsign/grid), before it is logged.
	 * Uses a transient marker that is not part of the persisted trail.
	 */
	showCurrentLocation(location) {
		if (!this.map) return;
		const latlng = this.resolveLatLng(location);

		// Remove old preview marker
		if (this._previewMarker) {
			this.markers.removeLayer(this._previewMarker);
			this._previewMarker = null;
		}

		if (!latlng) {
			// No preview active: restore last logged QSO to red
			if (this.currentKey) {
				const m = this.qsoMarkers.get(this.currentKey);
				if (m) m.setIcon(this.redIcon);
			}
			this.clearPathLine();
			// Redraw path to last logged QSO if it has a known position
			if (this.currentKey) {
				const m = this.qsoMarkers.get(this.currentKey);
				if (m) this.updatePathLine(m.getLatLng());
			}
			this.dataStore?.emit('qso_bearing_updated', null);
			return;
		}

		// Emit bearing/distance for the QSO form's info line
		if (this.stationLatLng) {
			const dist = this._haversineKm(this.stationLatLng, latlng);
			const az   = this._bearing(this.stationLatLng, latlng);
			this.dataStore?.emit('qso_bearing_updated', { distance: Math.round(dist), azimuth: Math.round(az) });
		}

		// Preview active: dim the last logged QSO to blue, show preview in red
		if (this.currentKey) {
			const m = this.qsoMarkers.get(this.currentKey);
			if (m) m.setIcon(this.blueIcon);
		}

		this._previewMarker = L.marker(latlng, { icon: this.redIcon }).addTo(this.markers);
		if (this.prefs.autofit) {
			this.fitAllMarkers(latlng);
		} else {
			this.map.panTo(latlng);
		}
		this.updatePathLine(latlng);
	}

	/**
	 * Draw/replace the geodesic line from the own station to the given point.
	 * No-op if disabled, or if the station position is unknown.
	 */
	updatePathLine(toLatLng) {
		this.clearPathLine();
		if (!this.prefs.pathline || !this.stationLatLng || !toLatLng) return;
		if (typeof L.geodesic !== 'function') return;
		this.pathLine = L.geodesic([this.stationLatLng, toLatLng], {
			color: '#ff0000',
			weight: 1.2,
			opacity: 1,
			dashArray: '5, 5',
			wrap: false,
			steps: 50
		}).addTo(this.map);
	}

	clearPathLine() {
		if (this.pathLine) {
			this.map.removeLayer(this.pathLine);
			this.pathLine = null;
		}
	}

	/**
	 * Fit the map view so all QSO markers, the own station, and an optional extra point
	 * are visible with padding. Falls back to setView for a single point.
	 * @param {[number,number]|null} extraLatLng  Additional coordinate to include (e.g. preview)
	 */
	fitAllMarkers(extraLatLng = null) {
		if (!this.map) return;
		const points = [];
		this.qsoMarkers.forEach((m) => points.push(m.getLatLng()));
		if (extraLatLng) {
			points.push(Array.isArray(extraLatLng)
				? L.latLng(extraLatLng[0], extraLatLng[1])
				: extraLatLng);
		}
		if (this.stationLatLng) {
			points.push(L.latLng(this.stationLatLng[0], this.stationLatLng[1]));
		}
		if (points.length === 0) return;
		if (points.length === 1) {
			this.map.setView(points[0], Math.max(this.map.getZoom(), 5));
			return;
		}
		this.map.fitBounds(L.latLngBounds(points), { padding: [40, 40], maxZoom: 12 });
	}

	/**
	 * Re-plot all QSOs from the DataStore as the trail, highlighting the newest as current.
	 */
	plotAllQsos() {
		if (!this.map || !this.markers || !this.dataStore) return;

		// Clear existing trail markers (keep station marker, which lives on the map directly)
		this.qsoMarkers.forEach((m) => this.markers.removeLayer(m));
		this.qsoMarkers.clear();
		this.currentKey = null;

		const qsos = Array.from(this.dataStore.getPattern('qso.*').values());
		qsos.forEach((qso) => this.upsertQsoMarker(qso, false));

		// Highlight the most recent QSO (visual only, no pan — fitAllMarkers handles navigation)
		if (qsos.length) {
			const newest = qsos.reduce((a, b) =>
				(this._qsoTime(b) >= this._qsoTime(a) ? b : a));
			const key = this._qsoKey(newest);
			const latlng = this.resolveLatLng(newest);
			if (key && latlng) {
				this.currentKey = key;
				const marker = this.qsoMarkers.get(key);
				if (marker) marker.setIcon(this.redIcon);
				this.updatePathLine(latlng);
			}
		}

		// Always fit all markers on initial load / full resync
		this.fitAllMarkers();
	}

	_qsoTime(q) {
		const t = Date.parse(q.created || q.time_on || `${q.date} ${q.time}`);
		return Number.isFinite(t) ? t : 0;
	}

	/**
	 * Leaflet control with three toggles (night shadow, path line, station). Each toggle
	 * updates the layer immediately and persists the preference per-user via the server.
	 */
	addToggleControl() {
		const self = this;
		const Toggle = L.Control.extend({
			options: { position: 'topright' },
			onAdd() {
				const div = L.DomUtil.create('div', 'leaflet-bar contest-map-toggles');
				div.style.background = 'rgba(255,255,255,0.85)';
				div.style.padding = '4px 6px';
				div.style.font = '12px/1.4 sans-serif';
				div.style.color = '#000';
				const row = (id, label, checked) =>
					`<label style="display:block;cursor:pointer;white-space:nowrap;">
						<input type="checkbox" data-pref="${id}" ${checked ? 'checked' : ''}> ${label}
					</label>`;
				div.innerHTML =
					row('nightshadow', lang_map_nightshadow ?? 'Night', self.prefs.nightshadow) +
					row('pathline', lang_map_pathline ?? 'Path', self.prefs.pathline) +
					row('station', lang_map_station ?? 'Station', self.prefs.station) +
					row('autofit', lang_map_autofit ?? 'Auto-fit', self.prefs.autofit) +
					row('grid', lang_map_grid ?? 'Grid', self.prefs.grid);

				L.DomEvent.disableClickPropagation(div);
				div.querySelectorAll('input[data-pref]').forEach((cb) => {
					cb.addEventListener('change', (e) => {
						self.onTogglePref(e.target.dataset.pref, e.target.checked);
					});
				});
				return div;
			}
		});
		this.map.addControl(new Toggle());
	}

	onTogglePref(pref, enabled) {
		this.prefs[pref] = enabled;

		if (pref === 'nightshadow' && this.nightLayer) {
			if (enabled) this.nightLayer.addTo(this.map);
			else this.map.removeLayer(this.nightLayer);
		} else if (pref === 'station') {
			if (enabled && this.stationLatLng && !this.stationMarker) {
				this.stationMarker = L.marker(this.stationLatLng, { icon: this.stationIcon }).addTo(this.map);
			} else if (!enabled && this.stationMarker) {
				this.map.removeLayer(this.stationMarker);
				this.stationMarker = null;
			}
		} else if (pref === 'pathline') {
			if (!enabled) {
				this.clearPathLine();
			} else if (this.currentKey) {
				const m = this.qsoMarkers.get(this.currentKey);
				if (m) this.updatePathLine(m.getLatLng());
			}
		} else if (pref === 'autofit') {
			if (enabled) this.fitAllMarkers();
		} else if (pref === 'grid' && this.gridLayer) {
			if (enabled) this.gridLayer.addTo(this.map);
			else this.map.removeLayer(this.gridLayer);
		}

		this.saveMapPrefs();
	}

	/**
	 * Persist the current toggle state per-user (server-side, user_options).
	 */
	saveMapPrefs() {
		fetch(base_url + 'index.php/contesting/save_map_prefs', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(this.prefs)
		}).catch((err) => console.warn('MapComponent: failed to save map prefs', err));
	}

	_latLngToLocator(lat, lng) {
		if (lng < -180) lng += 360;
		if (lng > 180)  lng -= 360;
		const longitude = lng + 180;
		const latitude  = lat + 90;
		const C = 65;
		const squareLat = Math.floor(latitude % 10);
		return String.fromCharCode(C + Math.floor(longitude / 20)) +
			String.fromCharCode(C + Math.floor(latitude / 10)) +
			Math.floor(longitude % 20 / 2) +
			squareLat +
			String.fromCharCode(C + Math.floor((longitude % 20 % 2) * 12)).toLowerCase() +
			String.fromCharCode(C + Math.floor((latitude % 10 - squareLat) * 24)).toLowerCase();
	}

	_updateCoordinateBar(lat, lng) {
		if (lng < -180) lng += 360;
		if (lng > 180)  lng -= 360;

		const pad = (n, w) => String(Math.floor(Math.abs(n))).padStart(w, '0');
		const dms = (val, pos, neg, degW) => {
			const abs = Math.abs(val) + 1e-9;
			const deg = Math.floor(abs);
			const minFull = (abs % 1) * 60;
			const min = Math.floor(minFull);
			const sec = Math.floor((minFull % 1) * 6000) / 100;
			return (val < 0 ? neg : pos) + ' ' + pad(deg, degW) + '° ' + pad(min, 2) + "' " + sec + '"';
		};

		const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
		set('latDeg', dms(lat, 'N', 'S', 2));
		set('lngDeg', dms(lng, 'E', 'W', 3));
		set('locator', this._latLngToLocator(lat, lng));

		if (this.stationLatLng) {
			const cfg = window.ContestLoggerConfig;
			const base = cfg?.measurement_base || 'K';
			const km = this._haversineKm(this.stationLatLng, [lat, lng]);
			const dist = base === 'M' ? km * 0.621371 : (base === 'N' ? km * 0.539957 : km);
			const unit = base === 'M' ? 'mi' : (base === 'N' ? 'nmi' : 'km');
			set('distance', Math.round(dist * 10) / 10 + ' ' + unit);
			set('bearing', Math.round(this._bearing(this.stationLatLng, [lat, lng])) + '°');
		}
	}

	_haversineKm([lat1, lon1], [lat2, lon2]) {
		const R = 6371;
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
	}

	_bearing([lat1, lon1], [lat2, lon2]) {
		const toRad = d => d * Math.PI / 180;
		const y = Math.sin(toRad(lon2-lon1)) * Math.cos(toRad(lat2));
		const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(toRad(lon2-lon1));
		return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
	}

	destroy() {
		if (this._resizeObserver) {
			this._resizeObserver.disconnect();
			delete this._resizeObserver;
		}
		if (this.nightInterval) {
			clearInterval(this.nightInterval);
			this.nightInterval = null;
		}
		if (this.map) {
			this.map.remove();
			this.map = null;
		}
	}
}

// Auto-initialize when component is loaded and app is ready
function setupMapComponent() {
	function tryInitMapComponent() {
		const container = document.getElementById('map-display');
		const dataStore = window.contestApp?.ds;

		if (!container) {
			return false;
		}

		if (!dataStore) {
			return false;
		}

		// Initialize MapComponent (will load Leaflet dynamically)
		const mapComponent = new MapComponent('map-display', dataStore);

		// Store reference in global contestApp for debugging
		if (window.contestApp) {
			window.contestApp.mapComponent = mapComponent;
		}

		return true;
	}

	return { tryInitMapComponent };
}

// Self-register when app is ready
window.addEventListener('contestAppReady', () => {
	const { tryInitMapComponent } = setupMapComponent();

	// Try to initialize immediately if DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function initWhenReady() {
			const interval = setInterval(() => {
				if (tryInitMapComponent()) {
					clearInterval(interval);
				}
			}, 100);
		});
	} else {
		// DOM already loaded, wait for dependencies
		const interval = setInterval(() => {
			if (tryInitMapComponent()) {
				clearInterval(interval);
				// Expose to contestApp
				if (window.contestApp && tryInitMapComponent.instance) {
					window.contestApp.mapComponent = tryInitMapComponent.instance;
				}
			}
		}, 100);
	}
});

// Register globally for debugging
window.MapComponent = MapComponent;
