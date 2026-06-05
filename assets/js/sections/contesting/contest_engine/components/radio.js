/**
 * RadioComponent - Radio monitoring display
 * Shows CAT frequency and mode from selected radio (one-way)
 * Uses subscribe pattern for real-time updates from DataStore
 */

import { WsTransport } from '../core/ws-transport.js';

class RadioComponent {
	constructor(containerId, dataStore, syncEngine, radios = []) {
		this.container = document.getElementById(containerId);
		if (!this.container) {
			console.error(`RadioComponent: Container #${containerId} not found`);
			return;
		}

		this.dataStore = dataStore;
		this.syncEngine = syncEngine;
		this.radios = radios;
		this.selectedRadio = '0'; // '0' = None (manual mode)
		this.manualMode = true;

		this._websocket = null;
		this._wsIntentionallyClosed = false;
		this._wsReconnectAttempts = 0;
		this._wsHasTriedFallback = false;
		this._radioWsTransport = null;

		// Cache DOM elements
		this.qrgUnitElement = document.getElementById('qrg_unit');
		this.freqCalculated = document.getElementById('freq_calculated');
		this.frequency = document.getElementById('frequency');
		this.frequencyRx = document.getElementById('frequency_rx');
		this.mode = document.getElementById('mode');
		this.radioSelect = document.getElementById('radio-select');
		this.statusInfo = document.getElementById('radio-status-info');
		this.selectedBand = null; // Will be set by band button clicks

		// Bound callbacks for subscriptions
		this.boundFrequencyCallback = this.updateFrequencyDisplay.bind(this);
		this.boundModeCallback = this.updateModeDisplay.bind(this);
		this.boundTimestampCallback = this.updateStatusDisplay.bind(this);

		// Register sync handler for radio data
		this.registerSyncHandler();

		this.init();
	}

	/**
	 * Register sync handler with SyncEngine
	 */
	registerSyncHandler() {
		this.syncEngine.registerSyncHandler('radio.*', {
			buildRequest: (key, dataStore) => {
				// WebSocket mode handles radio updates itself — skip polling
				if (this.selectedRadio === 'ws') return null;

				// Extract radio ID from key: "radio.1.frequency" -> "1"
				const parts = key.split('.');
				const radioId = parts[1];

				// Only request if this is the frequency key or if no specific property is requested
				if (parts[2] === 'frequency' || parts.length === 2) {
					return {
						type: 'get_radio_status',
						radio_id: radioId
					};
				}
				return null;
			},
			
			canHandle: (responseData) => {
				return responseData.radio_status !== undefined;
			},
			
			processResponse: (responseData, dataStore) => {
				const status = responseData.radio_status;
				
				if (status && status !== null) {
					const radioId = this.selectedRadio;
					
					// Update datastore with radio data (only in-memory, not persisted)
					if (status.frequency !== undefined) {
						dataStore.setLocal(`radio.${radioId}.frequency`, status.frequency);
					}
					if (status.mode !== undefined) {
						dataStore.setLocal(`radio.${radioId}.mode`, status.mode);
					}
					if (status.timestamp !== undefined) {
						dataStore.setLocal(`radio.${radioId}.timestamp`, status.timestamp);
					}
					if (status.updated_minutes_ago !== undefined) {
						dataStore.setLocal(`radio.${radioId}.updated_minutes_ago`, status.updated_minutes_ago);
					}
				} else {
					// No data from radio - clear values
					const radioId = this.selectedRadio;
					dataStore.setLocal(`radio.${radioId}.frequency`, null);
					dataStore.setLocal(`radio.${radioId}.mode`, null);
					dataStore.setLocal(`radio.${radioId}.timestamp`, null);
					dataStore.setLocal(`radio.${radioId}.updated_minutes_ago`, null);
				}
			}
		});
	}

	init() {
		this.attachEventListeners();
		this.attachQRGEventListeners();

		// Set default band to 160m and load its default frequency
		this.setDefaultBand('160m');
	}

	/**
	 * Set default band and load its frequency
	 * @param {string} band - Band name (e.g., '160m')
	 */
	async setDefaultBand(band) {
		this.selectedBand = band;
		this.updateBandButtons(band, true);
		await this.loadDefaultFrequencyForBand(band);
	}

	attachEventListeners() {
		if (!this.radioSelect) return;

		this.radioSelect.addEventListener('change', (e) => {
			const newRadioId = e.target.value;
			this.setRadio(newRadioId);
		});

		// Band button click handlers (compact version)
		const bandButtons = document.querySelectorAll('.band-btn-compact');
		bandButtons.forEach(button => {
			button.addEventListener('click', (e) => {
				if (e.currentTarget.disabled) return;

				const selectedBand = e.currentTarget.dataset.band;
				this.selectedBand = selectedBand;

				this.updateBandButtons(selectedBand, true);

				// Store selected band in config namespace
				this.dataStore.set('config.selected_band', selectedBand);

				// Load default frequency for this band/mode
				this.loadDefaultFrequencyForBand(selectedBand);
			});
		});
	}

	/**
	 * Set radio and subscribe to its updates
	 * @param {string} radioId - Radio ID or '0' for manual mode
	 */
	setRadio(radioId) {
		// Unsubscribe from old radio
		if (this.selectedRadio && this.selectedRadio !== '0') {
			this.dataStore.unsubscribe(
				`radio.${this.selectedRadio}.frequency`,
				this.boundFrequencyCallback
			);
			this.dataStore.unsubscribe(
				`radio.${this.selectedRadio}.mode`,
				this.boundModeCallback
			);
			this.dataStore.unsubscribe(
				`radio.${this.selectedRadio}.updated_minutes_ago`,
				this.boundTimestampCallback
			);
		}

		if (this.selectedRadio === 'ws') {
			this._closeWebSocket();
		}

		this._disconnectFromWorkerTopic();

		this.selectedRadio = radioId;
		this.manualMode = (radioId === '0');

		if (this.manualMode) {
			this.clearDisplay();
			this.showStatusInfo(lang_radio_manual_mode, 'info');
			this.updateBandButtons(this.selectedBand, true);
		} else if (radioId === 'ws') {
			this._subscribeToRadio('radio.ws');
			this.clearDisplay();
			this.showStatusInfo(lang_radio_ws_connecting, 'info');
			this.updateBandButtons(null, false);
			this._initWebSocket();
		} else {
			this._subscribeToRadio(`radio.${radioId}`);
			this.clearDisplay();
			this.showStatusInfo(lang_radio_waiting, 'info');
			this.updateBandButtons(null, false);
			this._connectToWorkerTopic(radioId);
		}
	}

	/**
	 * Update band button states: highlight active band, enable or disable all buttons.
	 * @param {string|null} band - Active band name (e.g. '40m'), or null to clear
	 * @param {boolean} enabled - Whether buttons should be clickable
	 */
	updateBandButtons(band, enabled) {
		document.querySelectorAll('.band-btn-compact').forEach(btn => {
			const isActive = band && btn.dataset.band === band;
			btn.toggleAttribute('data-selected', !!isActive);
			btn.classList.toggle('active', !!isActive);
			btn.disabled = !enabled;
		});
	}

	/**
	 * Update frequency display (subscription callback)
	 */
	updateFrequencyDisplay(freq) {
		if (this.manualMode) return;

		if (!freq || !this.frequency) return;

		const detectedBand = this.frequencyToBand(parseInt(freq));
		if (detectedBand && detectedBand !== this.selectedBand) {
			this.selectedBand = detectedBand;
			this.dataStore.setLocal('config.selected_band', detectedBand);
		}

		this.frequency.value = freq;
		this.set_qrg();
		this.updateBandButtons(this.selectedBand, false);
	}

	/**
	 * Update mode display (subscription callback)
	 */
	updateModeDisplay(mode) {
		if (this.manualMode) return;

		if (mode && this.mode) {
			this.mode.value = mode;
			this.mode.dispatchEvent(new Event('change'));
		}
	}

	/**
	 * Update status display (subscription callback)
	 */
	updateStatusDisplay(minutesAgo) {
		if (this.manualMode) return;
		
		if (minutesAgo === null || minutesAgo === undefined) {
			this.showStatusInfo(`⚠️ ${lang_radio_no_data}`, 'warning');
		} else if (minutesAgo > 5) {
			this.showStatusInfo(`⚠️ ${lang_radio_data_old.replace('%s', minutesAgo)}`, 'warning');
		} else {
			this.showStatusInfo(`✓ ${lang_radio_connected}`, 'success');
		}
	}

	clearDisplay() {
		if (this.frequency) this.frequency.value = '';
		if (this.mode) this.mode.value = '';
		if (this.statusInfo) this.statusInfo.innerHTML = '';
	}

	showStatusInfo(message, type = 'info') {
		if (!this.statusInfo) return;

		const iconMap = {
			success: 'fas fa-check-circle',
			warning: 'fas fa-exclamation-triangle',
			error: 'fas fa-times-circle',
			info: 'fas fa-info-circle'
		};

		const colorMap = {
			success: 'text-success',
			warning: 'text-warning',
			error: 'text-danger',
			info: 'text-info'
		};

		this.statusInfo.innerHTML = `
			<small class="${colorMap[type]}">
				<i class="${iconMap[type]}"></i> ${message}
			</small>
		`;
	}

	/**
	 * Format frequency from Hz to MHz
	 * @param {string|number} freq - Frequency in Hz
	 * @returns {string}
	 */
	formatFrequency(freq) {
		const freqNum = parseFloat(freq);
		if (isNaN(freqNum)) return '---';

		const mhz = freqNum / 1000000;
		return mhz.toFixed(3);
	}

	/**
	 * Escape HTML for safe rendering
	 * @param {string} text
	 * @returns {string}
	 */
	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Get current frequency in Hz
	 * @returns {number|null}
	 */
	getFrequency() {
		const frequency = document.getElementById('frequency');
		if (frequency && frequency.value) {
			return parseInt(frequency.value);
		}
		return null;
	}

	/**
	 * Get current band
	 */
	getBand() {
		return this.selectedBand;
	}

	/**
	 * Get current mode
	 * @returns {string|null}
	 */
	getMode() {
		return this.mode.value;
	}

	/**
	 * Check if in manual mode
	 * @returns {boolean}
	 */
	isManualMode() {
		return this.manualMode;
	}

	/**
	 * Determine band from frequency in Hz
	 * Ported from radiohelpers.js frequencyToBand()
	 * @param {number} frequency - Frequency in Hz
	 * @returns {string|null} Band name (e.g., '40m') or null if not in any band
	 */
	frequencyToBand(frequency) {
		const freqHz = parseInt(frequency);
		const marginHz = 0; // No margin by default

		// MF/HF Bands
		if (freqHz >= (1800000 - marginHz) && freqHz <= (2000000 + marginHz)) return '160m';
		if (freqHz >= (3500000 - marginHz) && freqHz <= (4000000 + marginHz)) return '80m';
		if (freqHz >= (5250000 - marginHz) && freqHz <= (5450000 + marginHz)) return '60m';
		if (freqHz >= (7000000 - marginHz) && freqHz <= (7300000 + marginHz)) return '40m';
		if (freqHz >= (10100000 - marginHz) && freqHz <= (10150000 + marginHz)) return '30m';
		if (freqHz >= (14000000 - marginHz) && freqHz <= (14350000 + marginHz)) return '20m';
		if (freqHz >= (18068000 - marginHz) && freqHz <= (18168000 + marginHz)) return '17m';
		if (freqHz >= (21000000 - marginHz) && freqHz <= (21450000 + marginHz)) return '15m';
		if (freqHz >= (24890000 - marginHz) && freqHz <= (24990000 + marginHz)) return '12m';
		if (freqHz >= (28000000 - marginHz) && freqHz <= (29700000 + marginHz)) return '10m';

		// VHF Bands
		if (freqHz >= (50000000 - marginHz) && freqHz <= (54000000 + marginHz)) return '6m';
		if (freqHz >= (70000000 - marginHz) && freqHz <= (71000000 + marginHz)) return '4m';
		if (freqHz >= (144000000 - marginHz) && freqHz <= (148000000 + marginHz)) return '2m';
		if (freqHz >= (222000000 - marginHz) && freqHz <= (225000000 + marginHz)) return '1.25m';

		// UHF Bands
		if (freqHz >= (420000000 - marginHz) && freqHz <= (450000000 + marginHz)) return '70cm';
		if (freqHz >= (902000000 - marginHz) && freqHz <= (928000000 + marginHz)) return '33cm';
		if (freqHz >= (1240000000 - marginHz) && freqHz <= (1300000000 + marginHz)) return '23cm';

		// SHF Bands
		if (freqHz >= (2300000000 - marginHz) && freqHz <= (2450000000 + marginHz)) return '13cm';
		if (freqHz >= (3300000000 - marginHz) && freqHz <= (3500000000 + marginHz)) return '9cm';
		if (freqHz >= (5650000000 - marginHz) && freqHz <= (5925000000 + marginHz)) return '6cm';
		if (freqHz >= (10000000000 - marginHz) && freqHz <= (10500000000 + marginHz)) return '3cm';
		if (freqHz >= (24000000000 - marginHz) && freqHz <= (24250000000 + marginHz)) return '1.25cm';
		if (freqHz >= (47000000000 - marginHz) && freqHz <= (47200000000 + marginHz)) return '6mm';
		if (freqHz >= (75500000000 - marginHz) && freqHz <= (81000000000 + marginHz)) return '4mm';
		if (freqHz >= (119980000000 - marginHz) && freqHz <= (120020000000 + marginHz)) return '2.5mm';
		if (freqHz >= (142000000000 - marginHz) && freqHz <= (149000000000 + marginHz)) return '2mm';
		if (freqHz >= (241000000000 - marginHz) && freqHz <= (250000000000 + marginHz)) return '1mm';

		return null;
	}

	/**
	 * Setup QRG input type for small screens
	 */
	qrg_inputtype() {
		// on small screens we change the input type of the frequency and frequency_rx fields to number to show the numeric keyboard
		if (this.freqCalculated) {
			this.freqCalculated.type = 'number';
			this.freqCalculated.step = '0.001';
			this.freqCalculated.inputMode = 'decimal';
			this.freqCalculated.lang = 'en';
		}

		if (this.frequencyRx) {
			this.frequencyRx.type = 'number';
			this.frequencyRx.step = '0.001';
			this.frequencyRx.inputMode = 'decimal';
			this.frequencyRx.lang = 'en';
		}
	}

	/**
	 * Load default frequency for a band and mode
	 * Uses cached band defaults from ContestLoggerConfig when available
	 * @param {string} band - Band name (e.g., '40m')
	 */
	async loadDefaultFrequencyForBand(band) {
		if (!band) {
			console.warn('RadioComponent: No band specified');
			return;
		}

		const modeValue = this.mode?.value || 'SSB';

		// Check if band defaults are cached in ContestLoggerConfig
		if (window.ContestLoggerConfig?.bandDefaults && window.ContestLoggerConfig.bandDefaults[band]) {
			const freqHz = window.ContestLoggerConfig.bandDefaults[band][modeValue]
				|| window.ContestLoggerConfig.bandDefaults[band]['SSB']; // fallback to SSB

			if (freqHz) {
				this.frequency.value = freqHz;
				await this.set_qrg();
				return;
			}
		}

		// Fallback: fetch from backend if cache not available
		if (typeof base_url === 'undefined') {
			console.warn('RadioComponent: No base_url available');
			return;
		}

		try {
			const response = await fetch(base_url + 'index.php/qso/band_to_freq/' + band + '/' + modeValue);
			const result = await response.text();

			if (result) {
				const freqHz = parseInt(result);
				this.frequency.value = freqHz;
				await this.set_qrg();
			}
		} catch (error) {
			console.error('RadioComponent: Failed to fetch default frequency for band:', error);
		}
	}

	/**
	 * Set QRG display based on frequency and band
	 */
	async set_qrg() {
		if (!this.frequency || !this.freqCalculated || !this.qrgUnitElement) {
			console.warn('RadioComponent: set_qrg() - Missing DOM elements');
			return;
		}

		let frequency = this.frequency.value;
		let band = this.selectedBand;

		if (!band) {
			console.warn('RadioComponent: set_qrg() - No band selected');
			return;
		}

		// Get QRG unit from cache, with default fallback to 'kHz'
		let qrgunit = window.ContestLoggerConfig?.qrgUnits?.[band] || 'kHz';

		this.qrgUnitElement.innerHTML = qrgunit;

		if (qrgunit == 'Hz') {
			this.freqCalculated.value = frequency;
		} else if (qrgunit == 'kHz') {
			this.freqCalculated.value = frequency / 1000;
		} else if (qrgunit == 'MHz') {
			this.freqCalculated.value = frequency / 1000000;
		} else if (qrgunit == 'GHz') {
			this.freqCalculated.value = frequency / 1000000000;
		}
	}

	/**
	 * Set new QRG from calculated field
	 */
	async set_new_qrg() {
		console.log('RadioComponent: set_new_qrg() called');

		if (!this.freqCalculated) {
			console.warn('RadioComponent: set_new_qrg() - freqCalculated not found');
			return;
		}

		let new_qrg = this.freqCalculated.value;

		// Set flag to indicate this is a manual form update (not from CAT/radio)
		window.user_updating_frequency = true;

		// Trim and validate input
		if (new_qrg !== null && new_qrg !== undefined) {
			new_qrg = new_qrg.trim();
		}

		let parsed_qrg = parseFloat(new_qrg);

		// If field is empty or parsing failed, load default frequency for current band/mode
		if (!new_qrg || new_qrg === '' || isNaN(parsed_qrg) || !isFinite(parsed_qrg) || parsed_qrg <= 0) {
			if (!this.selectedBand) {
				console.warn('RadioComponent: No band selected');
				window.user_updating_frequency = false;
				return;
			}
			await this.loadDefaultFrequencyForBand(this.selectedBand);
			window.user_updating_frequency = false;
			return;
		}

		let unit = this.qrgUnitElement.innerHTML;

		// check if the input contains a unit and parse the qrg
		if (/^\d+(\.\d+)?\s*(hz|h)$/i.test(new_qrg)) {
			unit = 'Hz';
			parsed_qrg = parseFloat(new_qrg);
		} else if (/^\d+(\.\d+)?\s*(khz|k)$/i.test(new_qrg)) {
			unit = 'kHz';
			parsed_qrg = parseFloat(new_qrg);
		} else if (/^\d+(\.\d+)?\s*(mhz|m)$/i.test(new_qrg)) {
			unit = 'MHz';
			parsed_qrg = parseFloat(new_qrg);
		} else if (/^\d+(\.\d+)?\s*(ghz|g)$/i.test(new_qrg)) {
			unit = 'GHz';
			parsed_qrg = parseFloat(new_qrg);
		}

		// update the unit if there was any change
		this.qrgUnitElement.innerHTML = unit;

		// calculate the frequency in Hz
		let qrg_hz;
		switch (unit) {
			case 'Hz':
				qrg_hz = parsed_qrg;
				break;
			case 'kHz':
				qrg_hz = parsed_qrg * 1000;
				break;
			case 'MHz':
				qrg_hz = parsed_qrg * 1000000;
				break;
			case 'GHz':
				qrg_hz = parsed_qrg * 1000000000;
				break;
			default:
				qrg_hz = 0;
				console.error('Invalid unit');
		}

		// Determine band from frequency using local method
		const freq_khz = qrg_hz / 1000;
		let new_band = this.selectedBand; // fallback to current band
		const detectedBand = this.frequencyToBand(qrg_hz);
		if (detectedBand) {
			new_band = detectedBand;
			console.log('RadioComponent: Determined band from frequency:', { freq_hz: qrg_hz, freq_khz, band: new_band });
		}

		localStorage.setItem('qrgunit_' + new_band, unit);

		this.frequency.value = qrg_hz;
		this.freqCalculated.value = parsed_qrg;
		this.selectedBand = new_band;

		this.updateBandButtons(new_band, true);

		window.user_updating_frequency = false;
	}

	/**
	 * Subscribe to DataStore updates for a radio key prefix
	 * @param {string} prefix - e.g. 'radio.ws' or 'radio.1'
	 */
	_subscribeToRadio(prefix) {
		this.dataStore.subscribe(`${prefix}.frequency`,          this.boundFrequencyCallback,  { realtime: true });
		this.dataStore.subscribe(`${prefix}.mode`,               this.boundModeCallback,        { realtime: true });
		this.dataStore.subscribe(`${prefix}.updated_minutes_ago`, this.boundTimestampCallback,  { realtime: true });
	}

	_initWebSocket() {
		this._wsIntentionallyClosed = false;
		const tryWss = !this._wsHasTriedFallback;
		const protocol = tryWss ? 'wss' : 'ws';
		const port     = tryWss ? '54323' : '54322';

		try {
			this._websocket = new WebSocket(`${protocol}://127.0.0.1:${port}`);
		} catch (e) {
			return;
		}

		this._websocket.onopen = () => {
			this._wsReconnectAttempts = 0;
			this.showStatusInfo(`${lang_radio_ws_connected}`, 'success');
		};

		this._websocket.onmessage = (event) => {
			try {
				this._handleWsMessage(JSON.parse(event.data));
			} catch (_) { /* ignore malformed frames */ }
		};

		this._websocket.onerror = () => {
			// Try WS fallback once if WSS failed on first attempt
			if (tryWss && !this._wsHasTriedFallback) {
				this._wsHasTriedFallback = true;
				if (this._websocket && this._websocket.readyState === WebSocket.CONNECTING) {
					this._websocket.close();
				}
				setTimeout(() => this._initWebSocket(), 100);
				return;
			}
			this.showStatusInfo(`${lang_radio_ws_error}`, 'warning');
		};

		this._websocket.onclose = () => {
			if (this._wsIntentionallyClosed) {
				this._wsHasTriedFallback = false;
				return;
			}
			if (this._wsReconnectAttempts < 5) {
				this.showStatusInfo(lang_radio_ws_reconnecting, 'warning');
				setTimeout(() => {
					this._wsReconnectAttempts++;
					this._initWebSocket();
				}, 2000 * (this._wsReconnectAttempts + 1));
			} else {
				this.showStatusInfo(`${lang_radio_ws_offline}`, 'warning');
			}
		};
	}

	_handleWsMessage(data) {
		if (data.type !== 'radio_status' || !data.radio) return;
		const minutesAgo = data.timestamp
			? Math.floor((Date.now() - data.timestamp) / 60000)
			: 0;
		this.dataStore.setLocal('radio.ws.frequency',           data.frequency);
		this.dataStore.setLocal('radio.ws.mode',                data.mode);
		this.dataStore.setLocal('radio.ws.timestamp',           data.timestamp || Date.now());
		this.dataStore.setLocal('radio.ws.updated_minutes_ago', minutesAgo);
	}

	_closeWebSocket() {
		this._wsIntentionallyClosed = true;
		this._wsReconnectAttempts = 0;
		this._wsHasTriedFallback = false;
		if (this._websocket) {
			this._websocket.close();
			this._websocket = null;
		}
	}

	_connectToWorkerTopic(radioId) {
		this._disconnectFromWorkerTopic();
		const workerCfg = window.ContestLoggerConfig?.worker;
		if (!workerCfg?.url || !workerCfg?.radio_topics?.[radioId]) return;

		const { topic, token } = workerCfg.radio_topics[radioId];
		const ws = new WsTransport(window.contestApp?.ajaxTransport ?? null, workerCfg.url, topic, token);
		ws.onPush = (payload) => {
			if (payload?.type !== 'radio_updated' || !payload.radio_status) return;
			const s = payload.radio_status;
			const prefix = `radio.${radioId}`;
			if (s.frequency           !== undefined) this.dataStore.setLocal(`${prefix}.frequency`,           s.frequency);
			if (s.mode                !== undefined) this.dataStore.setLocal(`${prefix}.mode`,                s.mode);
			if (s.timestamp           !== undefined) this.dataStore.setLocal(`${prefix}.timestamp`,           s.timestamp);
			if (s.updated_minutes_ago !== undefined) this.dataStore.setLocal(`${prefix}.updated_minutes_ago`, s.updated_minutes_ago);
		};
		ws.connect();
		this._radioWsTransport = ws;
	}

	_disconnectFromWorkerTopic() {
		if (this._radioWsTransport) {
			this._radioWsTransport.disconnect();
			this._radioWsTransport = null;
		}
	}

	sendLookupResult(data) {
		const ws = this._websocket;
		if (!ws || ws.readyState !== WebSocket.OPEN) return;
		try {
			ws.send(JSON.stringify({
				type: 'lookup_result',
				timestamp: new Date().toISOString(),
				payload: {
					callsign: data.callsign ?? null,
					dxcc_id:  data.dxcc_id  ?? null,
					name:     data.name     ?? null,
					grid:     data.grid     ?? null,
					bearing:  data.bearing  ?? null,
					azimuth:  data.azimuth  ?? null,
					distance: data.distance ?? null,
				}
			}));
		} catch (e) { /* ignore */ }
	}

	/**
	 * Attach QRG-related event listeners
	 */
	attachQRGEventListeners() {
		// QRG unit toggle
		if (this.qrgUnitElement) {
			this.qrgUnitElement.addEventListener('click', () => {
				console.log('QRG unit toggle clicked');

				if (!this.freqCalculated || !this.frequency) {
					console.warn('QRG handler: Missing DOM elements');
					return;
				}

				const band = this.selectedBand || 'unknown';

				if (this.qrgUnitElement.innerHTML == 'Hz') {
					this.qrgUnitElement.innerHTML = 'kHz';
					this.freqCalculated.value = this.frequency.value / 1000;
					localStorage.setItem('qrgunit_' + band, 'kHz');
				} else if (this.qrgUnitElement.innerHTML == 'kHz') {
					this.qrgUnitElement.innerHTML = 'MHz';
					this.freqCalculated.value = this.frequency.value / 1000000;
					localStorage.setItem('qrgunit_' + band, 'MHz');
				} else if (this.qrgUnitElement.innerHTML == 'MHz') {
					this.qrgUnitElement.innerHTML = 'GHz';
					this.freqCalculated.value = this.frequency.value / 1000000000;
					localStorage.setItem('qrgunit_' + band, 'GHz');
				} else if (this.qrgUnitElement.innerHTML == 'GHz') {
					this.qrgUnitElement.innerHTML = 'Hz';
					this.freqCalculated.value = this.frequency.value;
					localStorage.setItem('qrgunit_' + band, 'Hz');
				}
			});
		}

		// Frequency change
		if (this.frequency) {
			this.frequency.addEventListener('change', () => {
				this.set_qrg();
			});
		}

		// Frequency calculated input - fire set_new_qrg() on Enter or blur
		if (this.freqCalculated) {
			// Real-time input filtering (commas to dots)
			this.freqCalculated.addEventListener('input', () => {
				if (window.innerWidth > 768) {
					this.freqCalculated.value = this.freqCalculated.value.replace(',', '.');
				}
			});

			// Fire set_new_qrg() when user presses Enter
			this.freqCalculated.addEventListener('keypress', (e) => {
				if (e.key === 'Enter') {
					this.set_new_qrg();
				}
			});

			// Fire set_new_qrg() when user leaves the field (blur)
			this.freqCalculated.addEventListener('blur', () => {
				this.set_new_qrg();
			});
		}
	}
}

// Register globally for debugging
window.RadioComponent = RadioComponent;

/**
 * Initialize the RadioComponent
 */
function initializeRadioComponent() {
	if (!window.contestApp) {
		console.error('RadioComponent: window.contestApp not available');
		return;
	}

	const { ds, syncEngine } = window.contestApp;
	const radios = window.ContestLoggerConfig?.radios || [];
	const containerId = 'radio-component';

	if (!document.getElementById(containerId)) {
		return;
	}

	const radioComponent = new RadioComponent(containerId, ds, syncEngine, radios);
	window.contestApp.radioComponent = radioComponent;

	const readyEvent = new CustomEvent('radioComponentReady', {
		detail: { radioComponent }
	});
	window.dispatchEvent(readyEvent);
}

// Initialize when app is ready
window.addEventListener('contestAppReady', () => {
	initializeRadioComponent();
});

// Also try to initialize if contestApp already exists
if (window.contestApp) {
	setTimeout(() => {
		initializeRadioComponent();
	}, 100);
}
