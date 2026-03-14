/**
 * QSO Form Component
 * Handles QSO logging via DataStore
 */
class QsoFormComponent {
	constructor(containerId = 'qso-form', dataStore, windowManager, syncEngine = null) {
		this.container = document.querySelector(`.window#${containerId}`);
		this.dataStore = dataStore;
		this.windowmanager = windowManager;
		this.syncEngine = syncEngine;
		this.isInitialized = false;
		this.lastDxccInfo = null;
		this.lastDxccCallsign = null;
		this.dxccLookupToken = 0;

		if (!this.container) {
			console.warn(`QsoFormComponent: Container not found, retrying...`);
			setTimeout(() => this.retryInit(containerId, dataStore), 100);
			return;
		}

		this.init();
	}

	retryInit(containerId, dataStore) {
		this.container = document.querySelector(`.window#${containerId}`);
		if (this.container) {
			this.init();
		} else {
			setTimeout(() => this.retryInit(containerId, dataStore), 100);
		}
	}

	init() {
		if (this.isInitialized) return;
		this.isInitialized = true;

		this.registerSyncHandler();
		this.setupEventListeners();
		this.loadExistingQSOs();

		// console.info('QsoFormComponent: Initialized');
	}

	async waitForRadioComponent(timeoutMs = 1000, intervalMs = 50) {
		const start = Date.now();
		while (Date.now() - start < timeoutMs) {
			const radio = this.radioComponent;
			if (radio && typeof radio.getBand === 'function' && typeof radio.getMode === 'function') {
				return radio;
			}
			await new Promise(resolve => setTimeout(resolve, intervalMs));
		}
		return this.radioComponent;
	}

	registerSyncHandler() {
		if (!this.syncEngine) return;

		this.syncEngine.registerSyncHandler('qso.*', {
			buildRequest: () => null,
			buildRequests: (dataStore) => [{
				type: 'check_sync',
				client_qso_count: dataStore.getSyncedQSOCount()
			}],
			buildCommands: (dataStore) => this.buildQsoCommands(dataStore),
			canHandle: (responseData) => {
				return responseData.saved_qsos !== undefined || responseData.needs_resync !== undefined;
			},
			processResponse: (responseData, dataStore) => {
				this.processQsoSyncResponse(responseData, dataStore);
			}
		});
	}

	// Getters to avoid race conditions
	get scpComponent() {
		return window.contestApp?.scpComponent;
	}

	get radioComponent() {
		return window.contestApp?.radioComponent;
	}

	setupEventListeners() {
		// Enter key in input fields
		const inputs = this.container.querySelectorAll('input[type="text"]');
		inputs.forEach(input => {
			input.addEventListener('keypress', (e) => {
				if (e.key === 'Enter') {
					this.logQso();
				}
			});
		});

		// Auto-uppercase callsign
		const callsignInput = this.container.querySelector('#qso-callsign');
		if (callsignInput) {
			callsignInput.addEventListener('input', (e) => {
				e.target.value = e.target.value.toUpperCase();

				if (this.lastDxccCallsign && e.target.value.trim().toUpperCase() !== this.lastDxccCallsign) {
					this.lastDxccCallsign = null;
					this.lastDxccInfo = null;
					this.updateDxccInfoDisplay(null);
				}

				// Trigger SCP search if component is available
				if (this.scpComponent && e.target.value.length >= 1) {
					this.scpComponent.searchCallsign(e.target.value);
				} else if (this.scpComponent && e.target.value.length === 0) {
					this.scpComponent.searchCallsign('');
				}
			});

			callsignInput.addEventListener('blur', (e) => {
				this.handleCallsignBlur(e);
			});
		}
	}

	async handleCallsignBlur(e) {
		const callsign = e.target.value.trim().toUpperCase();
		if (!callsign) {
			this.lastDxccCallsign = null;
			this.lastDxccInfo = null;
			this.updateDxccInfoDisplay(null);
			this.writeDxccToView(null);
			this.dataStore?.emit('qso_location_updated', null);
			return;
		}

		const lookupToken = ++this.dxccLookupToken;
		this.updateDxccInfoDisplay({ status: 'loading' });

		// Get band and mode from the radio component (if available)
		const radio = await this.waitForRadioComponent();
		const band = radio.getBand();
		const mode = radio.getMode();

		try {
			const dxccInfo = await this.lookupDxcc(callsign, band, mode);
			if (lookupToken !== this.dxccLookupToken) return;

			this.lastDxccCallsign = callsign;
			this.lastDxccInfo = dxccInfo || null;
			this.updateDxccInfoDisplay(dxccInfo);
			this.writeDxccToView(dxccInfo);

			const latValue = dxccInfo?.lat ?? dxccInfo?.latitude;
			const lonValue = dxccInfo?.long ?? dxccInfo?.lon ?? dxccInfo?.longitude;
			const lat = parseFloat(latValue);
			const lon = parseFloat(lonValue);
			if (Number.isFinite(lat) && Number.isFinite(lon)) {
				this.dataStore?.emit('qso_location_updated', { lat, lon });
			} else {
				this.dataStore?.emit('qso_location_updated', null);
			}
		} catch (error) {
			if (lookupToken !== this.dxccLookupToken) return;
			console.error('QSO Form: DXCC lookup failed', error);
			this.updateDxccInfoDisplay({ status: 'error' });
			this.writeDxccToView(null);
			this.dataStore?.emit('qso_location_updated', null);
		}
	}

	writeDxccToView(dxccInfo) {
		if (!this.container) return;

		const fields = {
			'#qso-dxcc': '',
			'#qso-dxcc-adif': '',
			'#qso-dxcc-cont': '',
			'#qso-dxcc-entity': '',
			'#qso-dxcc-cqz': '',
			'#qso-dxcc-lat': '',
			'#qso-dxcc-long': '',
			'#qso-dxcc-start': '',
			'#qso-dxcc-end': ''
		};

		if (dxccInfo && !dxccInfo.status) {
			const adif = dxccInfo.adif ?? '';
			fields['#qso-dxcc'] = adif;
			fields['#qso-dxcc-adif'] = adif;
			fields['#qso-dxcc-cont'] = dxccInfo.cont ?? '';
			fields['#qso-dxcc-entity'] = dxccInfo.entity ?? '';
			fields['#qso-dxcc-cqz'] = dxccInfo.cqz ?? '';
			fields['#qso-dxcc-lat'] = dxccInfo.lat ?? dxccInfo.latitude ?? '';
			fields['#qso-dxcc-long'] = dxccInfo.long ?? dxccInfo.longitude ?? '';
			fields['#qso-dxcc-start'] = dxccInfo.start ?? '';
			fields['#qso-dxcc-end'] = dxccInfo.end ?? '';
		}

		Object.entries(fields).forEach(([selector, value]) => {
			const el = this.container.querySelector(selector);
			if (el) el.value = value ?? '';
		});
	}

	async lookupDxcc(callsign, band, mode) {
		if (!callsign) return null;

		const cacheKey = `dxcc.${callsign}.${band || 'all'}.${mode || 'all'}`;
		const cached = this.dataStore?.get(cacheKey);
		if (cached) return cached;

		const url = `${base_url}index.php/contesting/dxcheck?call=${encodeURIComponent(callsign)}`;
		const response = await fetch(url, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		});
		if (!response.ok) {
			throw new Error(`DXCC lookup failed: HTTP ${response.status}`);
		}

		const result = await response.json();
		if (this.dataStore && result) {
			this.dataStore.set(cacheKey, result);
		}

		return result;
	}

	updateDxccInfoDisplay(dxccInfo) {
		const infoEl = this.container?.querySelector('#qso-dxcc-info');
		if (!infoEl) return;

		if (!dxccInfo) {
			infoEl.textContent = '';
			return;
		}

		if (dxccInfo.status === 'loading') {
			infoEl.textContent = 'DXCC Lookup...';
			return;
		}

		if (dxccInfo.status === 'error') {
			infoEl.textContent = 'DXCC Lookup fehlgeschlagen';
			return;
		}

		const entity = dxccInfo.entity || dxccInfo.entity_name || '-';
		const cont = dxccInfo.cont || dxccInfo.continent || '-';
		const cqz = dxccInfo.cqz || dxccInfo.CQ_zone || '-';
		const adif = dxccInfo.adif ?? null;
		const isUnknown = adif === 0 || entity.toUpperCase().includes('NONE');

		if (isUnknown) {
			infoEl.textContent = 'DXCC: nicht gefunden';
			return;
		}

		infoEl.textContent = `DXCC: ${entity} · ${cont} · CQ ${cqz}`;
	}

	loadExistingQSOs() {
		if (!this.dataStore) return;

		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());

		const sorted = this.sortQsosByNewest(allQsos);
		sorted.forEach(qso => this.addQSOToTable(qso));
		this.updateQSOCount();

		// Listen for QSO state changes
		this.dataStore.on('qso_state_changed', (eventData) => this.handleQSOStateChanged(eventData));

		// Listen for full resync events (server -> client) to refresh table
		this.dataStore.on('qsos_resynced', (eventData) => this.handleQSOsResynced(eventData));
	}

	handleQSOsResynced(eventData) {
		if (!this.dataStore) return;

		this.clearTable();
		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());

		const sorted = this.sortQsosByNewest(allQsos);
		sorted.forEach(qso => this.addQSOToTable(qso));
		this.updateQSOCount();

		console.debug(`QSO Form: Resynced table (server=${eventData?.server ?? '?'}, protected=${eventData?.protected ?? '?'})`);
	}

	handleQSOStateChanged(eventData) {
		const { qso, oldState, newState } = eventData;
		this.updateQSOInTable(qso);
		console.debug(`QSO Form: QSO ${qso.id} state changed from ${oldState} to ${newState}`);
	}

	getStatusIndicator(state) {
		if (state === 'pending') {
			return '<span title="New" style="color: orange;">&#9679;</span>';
		} else if (state === 'synced') {
			return '<span title="Confirmed" style="color: green;">&#9679;</span>';
		} else if (state === 'error') {
			return '<span title="Error" style="color: red;">&#9679;</span>';
		} else {
			return '<span title="Unknown" style="color: gray;">&#9679;</span>';
		}
	}

	addQSOToTable(qso) {
		if (!this.container) return;

		const tbody = this.container.querySelector('#qso-tbody');
		if (!tbody) return;

		const row = document.createElement('tr');
		row.dataset.qsoId = qso.tmpId || qso.serverId;
		let band = this.convertQrgToBand(parseInt(qso.frequency));
		let qrg_mhz = qso.frequency ? (parseInt(qso.frequency) / 1e6).toFixed(3) + ' MHz' : '';
		row.innerHTML = `
			<td>${qso.time}</td>
			<td>${qso.callsign}</td>
			<td title="${qrg_mhz}">${band || '-'}</td>
			<td>${qso.mode || '-'}</td>
			<td>${qso.rst_rcvd}</td>
			<td>${qso.exchange_rcvd || ''}</td>
			<td>${this.getStatusIndicator(qso.state)}</td>
		`;

		tbody.insertBefore(row, tbody.firstChild);
	}

	updateQSOInTable(qso) {
		if (!this.container) return;

		const tbody = this.container.querySelector('#qso-tbody');
		if (!tbody) return;

		const qsoId = qso.tmpId || qso.serverId;
		const existingRow = tbody.querySelector(`tr[data-qso-id="${qsoId}"]`) ||
			tbody.querySelector(`tr[data-qso-id^="tmp_"]`);

		if (!existingRow) {
			this.addQSOToTable(qso);
			return;
		}

		existingRow.dataset.qsoId = qsoId;

		const statusCell = existingRow.querySelector('td:last-child');
		if (statusCell) {
			statusCell.innerHTML = this.getStatusIndicator(qso.state);
		}
	}

	clearTable() {
		const tbody = this.container?.querySelector('#qso-tbody');
		if (!tbody) return;
		tbody.innerHTML = '';
	}

	sortQsosByNewest(qsos) {
		return qsos
			.slice()
			.sort((a, b) => this.getQsoTimestamp(a) - this.getQsoTimestamp(b));
	}

	getQsoTimestamp(q) {
		const dateStr = q.date || (q.time_on ? q.time_on.split(' ')[0] : null);
		const timeStr = q.time || (q.time_on ? q.time_on.split(' ')[1] : null);
		const isoLike = dateStr && timeStr ? `${dateStr}T${timeStr}` : null;
		const candidate = isoLike || q.time_on || q.created || '';
		const ts = Date.parse(candidate);
		return Number.isFinite(ts) ? ts : 0;
	}

	updateQSOCount() {
		if (!this.container || !this.dataStore) return;

		const count = this.dataStore.getPattern('qso.*').size;

		// Update count badge in table header
		const countBadge = this.container.querySelector('#qso-count-badge');
		if (countBadge) {
			countBadge.textContent = count;
		}
	}

	clearForm() {
		if (!this.container) return;

		const callsignInput = this.container.querySelector('#qso-callsign');
		const exchangeSentInput = this.container.querySelector('#qso-exchange-sent');
		const exchangeReceivedInput = this.container.querySelector('#qso-exchange-received');

		if (callsignInput) {
			callsignInput.value = '';
			callsignInput.focus();
		}
		if (exchangeSentInput) exchangeSentInput.value = '';
		if (exchangeReceivedInput) exchangeReceivedInput.value = '';

		this.lastDxccCallsign = null;
		this.lastDxccInfo = null;
		this.updateDxccInfoDisplay(null);
		this.writeDxccToView(null);
	}

	buildQsoCommands(dataStore) {
		const newQsos = Array.from(dataStore.getPattern('qso.*').values()).filter(q => q.state === 'pending');

		return newQsos.map(qso => ({
			type: 'save_qso',
			data: {
				tmp_id: qso.tmpId,
				callsign: qso.callsign,
				frequency: qso.frequency,
				mode: qso.mode,
				rst_sent: qso.rst_sent,
				rst_rcvd: qso.rst_rcvd,
				qso_date: qso.qso_date,
				time_on: qso.time_on,
				time_off: qso.time_off,
				date: qso.date,
				time: qso.time,
				exchange_sent: qso.exchange_sent,
				exchange_rcvd: qso.exchange_rcvd,
				operator: qso.operator,
				country: qso.country || qso.entity || null,
				continent: qso.continent || qso.cont || null,
				dxcc_id: qso.dxcc_id || qso.dxcc || null,
				cqz: qso.cqz || null
			}
		}));
	}

	processQsoSyncResponse(responseData, dataStore) {
		if (responseData.saved_qsos && responseData.saved_qsos.length > 0) {
			this.processSavedQsos(responseData.saved_qsos, dataStore);
			console.debug(`QSO Form: ${responseData.saved_qsos.length} QSO(s) saved to server`);
		}

		if (responseData.needs_resync && responseData.all_qsos) {
			this.resyncWithServer(responseData.all_qsos, responseData.saved_qsos || [], dataStore);
		} else if (responseData.needs_resync) {
			console.error('QSO Form: needs_resync=true but all_qsos missing!');
		}
	}

	processSavedQsos(savedQsos, dataStore) {
		savedQsos.forEach(saved => {
			if (saved.tmp_id && saved.server_id) {
				const qso = dataStore.get(`qso.${saved.tmp_id}`);

				if (qso) {
					const oldState = qso.state;
					const updated = {
						...qso,
						serverId: parseInt(saved.server_id),
						band: qso.band || this.calculateBand(qso.frequency),
						time_on: qso.time_on || `${qso.date} ${qso.time}`,
						state: 'synced'
					};

					dataStore.set(`qso.${saved.tmp_id}`, updated);

					dataStore.emit('qso_state_changed', {
						qso: updated,
						oldState,
						newState: 'synced'
					});

					console.debug(`QSO Form: QSO ${saved.tmp_id} → ${updated.serverId} synced`);
				}
			}
		});
	}

	calculateBand(frequency) {
		if (!frequency) return null;
		const band = this.convertQrgToBand(parseInt(frequency));
		return band === '??' ? null : band;
	}

	resyncWithServer(serverQsos, savedQsos = [], dataStore) {
		const localPendingQsos = Array.from(dataStore.getPattern('qso.*').values()).filter(q => q.state === 'pending');
		const tmpIdMap = new Map(savedQsos.map(s => [s.tmp_id, s.server_id]));
		const protectedNewQsos = localPendingQsos.filter(q => !tmpIdMap.has(q.tmpId));

		// Remove all non-pending QSOs
		for (const [key, qso] of dataStore.getPattern('qso.*').entries()) {
			if (qso.state !== 'pending') dataStore.delete(key);
		}

		serverQsos.forEach((sq) => {
			const timeOn = sq.time_on || '';
			const [datePart, timePart] = timeOn.includes(' ')
				? timeOn.split(' ')
				: [sq.date, sq.time];

			const freq = sq.frequency === undefined
				? undefined
				: typeof sq.frequency === 'string'
					? Number(sq.frequency) || sq.frequency
					: sq.frequency;

			const serverId = parseInt(sq.id ?? sq.qso_id);
			const tmpId = dataStore.generateId();

			const qso = {
				serverId: serverId,
				tmpId: tmpId,
				callsign: sq.callsign || sq.call,
				frequency: freq,
				mode: sq.mode,
				submode: sq.submode,
				band: sq.band,
				date: sq.date || datePart,
				time: sq.time || timePart,
				time_on: sq.time_on,
				time_off: sq.time_off,
				rst_sent: sq.rst_sent,
				rst_rcvd: sq.rst_rcvd ?? sq.rst_recv,
				serial_sent: sq.serial_sent,
				serial_recv: sq.serial_recv,
				exchange_sent: sq.exchange_sent ?? sq.exch_sent ?? '',
				exchange_rcvd: sq.exchange_rcvd ?? sq.exch_recv ?? '',
				locator: sq.locator,
				operator: sq.operator,
				state: 'synced'
			};

			dataStore.set(`qso.${tmpId}`, qso);
		});

		dataStore.emit('qsos_resynced', {
			server: serverQsos.length,
			protected: protectedNewQsos.length
		});
	}

	convertQrgToBand(frequency) {
		if (!frequency) return null;
		if (frequency >= 1800000 && frequency < 2000000) return '160m';
		if (frequency >= 3500000 && frequency < 4000000) return '80m';
		if (frequency >= 5300000 && frequency < 5400000) return '60m';
		if (frequency >= 7000000 && frequency < 7300000) return '40m';
		if (frequency >= 10000000 && frequency < 10150000) return '30m';
		if (frequency >= 14000000 && frequency < 14350000) return '20m';
		if (frequency >= 18000000 && frequency < 18200000) return '17m';
		if (frequency >= 21000000 && frequency < 21450000) return '15m';
		if (frequency >= 24000000 && frequency < 24990000) return '12m';
		if (frequency >= 28000000 && frequency < 29700000) return '10m';
		if (frequency >= 50000000 && frequency < 54000000) return '6m';
		if (frequency >= 144000000 && frequency < 148000000) return '2m';
		if (frequency >= 222000000 && frequency < 225000000) return '1.25m';
		if (frequency >= 420000000 && frequency < 450000000) return '70cm';
		return '??';
	}

	logQso() {
		if (!this.isInitialized || !this.container) {
			console.error('QSO Form: Not initialized');
			return;
		}

		if (!this.dataStore) {
			console.error('QSO Form: DataStore not available');
			return;
		}

		// Get input values
		const callsign = this.container.querySelector('#qso-callsign')?.value.trim().toUpperCase();
		const rstSent = this.container.querySelector('#qso-rst-sent')?.value.trim();
		const rstReceived = this.container.querySelector('#qso-rst-received')?.value.trim();
		const exchangeSent = this.container.querySelector('#qso-exchange-sent')?.value.trim();
		const exchangeRcvd = this.container.querySelector('#qso-exchange-received')?.value.trim();
		const dxccAdif = this.container.querySelector('#qso-dxcc-adif')?.value.trim();
		const dxccCont = this.container.querySelector('#qso-dxcc-cont')?.value.trim();
		const dxccEntity = this.container.querySelector('#qso-dxcc-entity')?.value.trim();
		const dxccCqz = this.container.querySelector('#qso-dxcc-cqz')?.value.trim();
		const dxccLat = this.container.querySelector('#qso-dxcc-lat')?.value.trim();
		const dxccLong = this.container.querySelector('#qso-dxcc-long')?.value.trim();
		const dxccStart = this.container.querySelector('#qso-dxcc-start')?.value.trim();
		const dxccEnd = this.container.querySelector('#qso-dxcc-end')?.value.trim();

		// Validate
		if (!callsign) {
			console.warn('QSO Form: No callsign entered');
			this.container.querySelector('#qso-callsign')?.focus();
			return;
		}

		// Get frequency and mode from RadioComponent
		if (!this.radioComponent) {
			console.error('QSO Form: RadioComponent not available');
			this.windowmanager.showToast('Error', 'RadioComponent not available. Can not save QSO.', 'bg-danger text-white', 5000);
			return;
		}

		const frequency = this.radioComponent.getFrequency();
		const mode = this.radioComponent.getMode();

		if (!frequency || !mode) {
			console.error('QSO Form: Frequency or Mode not available', { frequency, mode });
			this.windowmanager.showToast('Error', 'Frequency or Mode not set. Please check radio settings.', 'bg-danger text-white', 5000);
			return;
		}

		// Create QSO data object
		const qsoData = {
			callsign,
			rst_sent: rstSent || '59',
			rst_rcvd: rstReceived || '59',
			exchange_sent: exchangeSent,
			exchange_rcvd: exchangeRcvd,
			frequency: frequency,
			mode: mode,
			date: new Date().toISOString().split('T')[0],
			time: new Date().toISOString().split('T')[1].substring(0, 8),
			operator: window.ContestLoggerConfig?.operator || '',
		};

		const lat = parseFloat(dxccLat);
		const lon = parseFloat(dxccLong);
		if (Number.isFinite(lat)) qsoData.lat = lat;
		if (Number.isFinite(lon)) {
			qsoData.lon = lon;
			qsoData.long = lon;
		}
		if (dxccAdif) {
			qsoData.dxcc_id = dxccAdif;
			qsoData.dxcc = dxccAdif;
		}
		if (dxccCont) qsoData.cont = dxccCont;
		if (dxccCqz) qsoData.cqz = dxccCqz;
		if (dxccEntity) qsoData.entity = dxccEntity;
		if (dxccStart) qsoData.start = dxccStart;
		if (dxccEnd) qsoData.end = dxccEnd;

		// Save to DataStore using new API
		const tmpId = this.dataStore.generateId();
		const qso = {
			...qsoData,
			tmpId,
			serverId: null,
			state: 'pending',
			created: new Date().toISOString()
		};

		this.dataStore.set(`qso.${tmpId}`, qso);
		console.log('QSO Form: QSO saved', qso);

		this.dataStore.emit('qso_added', qso);

		// Update UI
		this.addQSOToTable(qso);
		this.updateQSOCount();
		if (this.scpComponent) {
			this.scpComponent.clearResults();
		}
		this.clearForm();
	}
}

// Self-register when app is ready
window.addEventListener('contestAppReady', (event) => {
	const { ds, wm, syncEngine } = event.detail;

	// Wait for DOM to be ready
	const initComponent = () => {
		const qsoFormComponent = new QsoFormComponent('qso-form', ds, wm, syncEngine);

		// Expose to contestApp and globally for onclick handler
		if (window.contestApp) {
			window.contestApp.qsoFormComponent = qsoFormComponent;
		}

		// Global logQso function for onclick handler
		window.logQso = () => qsoFormComponent.logQso();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => setTimeout(initComponent, 200));
	} else {
		setTimeout(initComponent, 200);
	}
});

// Register globally for debugging
window.QsoFormComponent = QsoFormComponent;
