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

	init() {
		// Verify Leaflet is loaded
		if (typeof L === 'undefined' || !L.map) {
			console.error('MapComponent: Leaflet library not loaded');
			return;
		}

		// Initialize icon now that Leaflet is loaded
		this.redIcon = L.icon({ iconUrl: this.iconDotUrl, iconSize: this.iconSize });

		// Initialize Leaflet map
		this.initMap();

		// Listen for QSO updates from DataStore
		if (this.dataStore) {
			this.dataStore.on('qso_added', (qso) => {
				this.addQsoToMap(qso);
			});

			this.dataStore.on('qso_location_updated', (location) => {
				if (!location || !Number.isFinite(location.lat) || !Number.isFinite(location.lon)) {
					this.clearMarkers();
					return;
				}
				this.addQsoToMap(location);
			});
		}

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
	}

	addQsoToMap(qso) {
		if (!qso.lat || !qso.lon) {
			return;
		}
		if (!this.map || !this.markers) return;

		this.clearMarkers();
		const marker = L.marker([qso.lat, qso.lon], { icon: this.redIcon });
		this.map.panTo([qso.lat, qso.lon]);
		this.map.setView([qso.lat, qso.lon], 3);
		this.markers.addLayer(marker);
	}

	clearMarkers() {
		if (this.markers) this.markers.clearLayers();
	}

	destroy() {
		if (this._resizeObserver) {
			this._resizeObserver.disconnect();
			delete this._resizeObserver;
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
