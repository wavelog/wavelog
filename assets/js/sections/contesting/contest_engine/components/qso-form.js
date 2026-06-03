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
		this._bearingInfo = null;
		this.callbookLookupToken = 0;
		this.nextSerialSent = 1;
		this.exchangeType = null;

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

		// Watermark of the highest change we have seen from the server, as a
		// (last_modified ms, serverId) pair. The serverId breaks ties within the same
		// 1-second last_modified bucket so a bulk import in one second is not re-sent on
		// every heartbeat. Both start at 0 so the first check_sync pulls the full set.
		this.lastSeenTs = 0;
		this.lastSeenId = 0;
		this.currentOperator = (window.ContestLoggerConfig?.operator ?? '').toUpperCase();

		this.registerSyncHandler();
		this.setupEventListeners();
		this.setupEditListeners();
		this.initExchangeType();
		this.loadExistingQSOs();
		this.applyRstDefaults();
		this._setupBandmapListener();
	}

	_setupBandmapListener() {
		const bc = new BroadcastChannel('qso_wish');
		bc.onmessage = (ev) => {
			const data = ev.data;
			if (!data || !data.call) return;

			const callsignInput = this.container.querySelector('#qso-callsign');
			if (!callsignInput) return;

			callsignInput.value = data.call.toUpperCase();
			callsignInput.dispatchEvent(new Event('input', { bubbles: true }));
			callsignInput.dispatchEvent(new Event('blur', { bubbles: true }));
		};

		const bcWin = new BroadcastChannel('contest_window');
		bcWin.onmessage = (ev) => {
			if (ev.data === 'ping') bcWin.postMessage('pong');
		};
	}

	defaultRst() {
		const mode = (this.radioComponent?.getMode() || '').toUpperCase();
		return (mode === 'CW') ? '599' : '59';
	}

	applyRstDefaults() {
		const rst = this.defaultRst();
		const rstSent = this.container.querySelector('#qso-rst-sent');
		const rstRcvd = this.container.querySelector('#qso-rst-received');
		if (rstSent) { rstSent.value = rst; rstSent.placeholder = rst; }
		if (rstRcvd) { rstRcvd.value = rst; rstRcvd.placeholder = rst; }
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

	initExchangeType() {
		const sessionInfo = window.ContestLoggerConfig?.sessionInfo ?? {};

		let fields = Array.isArray(sessionInfo.exchangefields) && sessionInfo.exchangefields.length > 0
			? sessionInfo.exchangefields
			: ['exchange'];

		this.exchangeFields = fields;

		const hasSerial    = fields.includes('serial');
		const hasGrid      = fields.includes('gridsquare');
		const hasExchange  = fields.includes('exchange');

		this.container.querySelectorAll('.serial-field, .serial-col').forEach(el => {
			el.style.display = hasSerial ? '' : 'none';
		});
		this.container.querySelectorAll('.gridsquare-field, .gridsquare-col').forEach(el => {
			el.style.display = hasGrid ? '' : 'none';
		});
		this.container.querySelectorAll('.exchange-text-field, .exchange-text-col').forEach(el => {
			el.style.display = hasExchange ? '' : 'none';
		});

		if (hasGrid) {
			const gridSentInput = this.container.querySelector('#qso-gridsquare-sent');
			if (gridSentInput) {
				gridSentInput.value = (sessionInfo.station_gridsquare ?? '').toUpperCase();
				gridSentInput.disabled = true;
			}
		}

		this._applyFieldOrder(fields);
	}

	_applyFieldOrder(fields) {
		const row = this.container.querySelector('#qso-form .row');
		if (!row) return;

		// Group variable field elements by type
		const groups = {
			serial:     [...row.querySelectorAll('.serial-field')],
			gridsquare: [...row.querySelectorAll('.gridsquare-field')],
			exchange:   [...row.querySelectorAll('.exchange-text-field')],
		};

		// Detach all variable groups, then re-append in the configured order
		Object.values(groups).flat().forEach(el => el.remove());
		fields.forEach(field => {
			(groups[field] ?? []).forEach(el => row.appendChild(el));
		});

		// Assign tabindex: callsign=1, received inputs in field order starting at 2.
		// Callsign must be explicit tabindex=1 so it stays in the positive-tabindex cycle
		// alongside the received fields — without it, callsign falls into tabindex=0 and
		// browsers visit it after all positive-tabindex elements on the whole page.
		const callsignInput = this.container.querySelector('#qso-callsign');
		if (callsignInput) callsignInput.setAttribute('tabindex', '1');

		const receivedIds = {
			serial:     '#qso-serial-received',
			gridsquare: '#qso-gridsquare-received',
			exchange:   '#qso-exchange-received',
		};
		let tabIdx = 2;
		fields.forEach(field => {
			const input = row.querySelector(receivedIds[field]);
			if (input) input.setAttribute('tabindex', tabIdx++);
		});
	}

	computeNextSerial() {
		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());
		let maxSerial = 0;
		allQsos.forEach(qso => {
			const s = parseInt(qso.serial_sent, 10);
			if (Number.isFinite(s) && s > maxSerial) maxSerial = s;
		});
		return maxSerial + 1;
	}

	updateSerialSentDisplay() {
		const input = this.container.querySelector('#qso-serial-sent');
		if (input) input.value = this.nextSerialSent;
	}

	registerSyncHandler() {
		if (!this.syncEngine) return;

		this.syncEngine.registerSyncHandler('qso.*', {
			buildRequest: () => null,
			buildRequests: (dataStore) => [{
				type: 'check_sync',
				client_qso_count: dataStore.getSyncedQSOCount(),
				since_ts: this.lastSeenTs ?? 0,
				since_id: this.lastSeenId ?? 0
			}],
			buildCommands: (dataStore) => this.buildQsoCommands(dataStore),
			canHandle: (responseData) => {
				return responseData.saved_qsos !== undefined || responseData.needs_resync !== undefined;
			},
			processResponse: (responseData, dataStore) => {
				this.processQsoSyncResponse(responseData, dataStore);
			}
		});
		this.syncEngine.triggerNow();
	}

	// Getters to avoid race conditions
	get scpComponent() {
		return window.contestApp?.scpComponent;
	}

	get radioComponent() {
		return window.contestApp?.radioComponent;
	}

	setupEventListeners() {
		// Space in callsign: prevent inserting a space; if callsign has a value,
		// jump to the first empty visible input after it
		const callsignInputEl = this.container.querySelector('#qso-callsign');
		if (callsignInputEl) {
			callsignInputEl.addEventListener('keydown', (e) => {
				if (e.key !== ' ') return;
				e.preventDefault();
				if (!callsignInputEl.value.trim()) return;
				const visibleInputs = Array.from(
					this.container.querySelectorAll('input[type="text"], input[type="number"]')
				).filter(el => el.offsetParent !== null);
				const callsignIdx = visibleInputs.indexOf(callsignInputEl);
				const next = visibleInputs.slice(callsignIdx + 1).find(el => !el.value.trim());
				if (next) next.focus();
			});
		}

		// Enter key in input fields logs QSO
		const inputs = this.container.querySelectorAll('input[type="text"], input[type="number"]');
		inputs.forEach(input => {
			input.addEventListener('keydown', (e) => {
				if (e.key === 'Enter') {
					this.logQso();
				}
			});
		});

		// Update RST defaults and re-check worked-before when mode changes
		const modeSelect = document.getElementById('mode');
		if (modeSelect) {
			modeSelect.addEventListener('change', () => {
				this.applyRstDefaults();
				const callsign = this.container.querySelector('#qso-callsign')?.value.trim().toUpperCase() || '';
				this.updateWorkedBeforeWarning(callsign);
			});
		}
		window.addEventListener('radioComponentReady', () => {
			this.applyRstDefaults();
			const callsign = this.container.querySelector('#qso-callsign')?.value.trim().toUpperCase() || '';
			this.updateWorkedBeforeWarning(callsign);
		});

		// Re-check worked-before when band changes
		this.dataStore.subscribe('config.selected_band', () => {
			const callsign = this.container.querySelector('#qso-callsign')?.value.trim().toUpperCase() || '';
			this.updateWorkedBeforeWarning(callsign);
		});

		// Live map preview: re-emit location when the received gridsquare changes, so the
		// map can plot the exact grid (with the current DXCC info as fallback).
		const gridRcvd = this.container.querySelector('#qso-gridsquare-received');
		if (gridRcvd) {
			gridRcvd.addEventListener('input', () => this.emitQsoLocation(this.lastDxccInfo));
		}

		// Escape resets the form — fires on keyup so it wins over any browser default
		// action on keydown (e.g. Chrome restoring input values on Escape)
		document.addEventListener('keyup', (e) => {
			if (e.key !== 'Escape') return;
			const active = document.activeElement;
			if (!active || active === document.body || this.container.contains(active)) {
				this.clearForm();
			}
		});

		// Auto-uppercase callsign
		const callsignInput = this.container.querySelector('#qso-callsign');
		if (callsignInput) {
			callsignInput.addEventListener('input', (e) => {
				e.target.value = e.target.value.toUpperCase().trim();
				const callsign = e.target.value;

				if (this.lastDxccCallsign && callsign !== this.lastDxccCallsign) {
					this.lastDxccCallsign = null;
					this.lastDxccInfo = null;
					this.updateDxccInfoDisplay(null);
				}

				this.updateWorkedBeforeWarning(callsign);

				// Trigger SCP search if component is available
				if (this.scpComponent && callsign.length >= 1) {
					this.scpComponent.searchCallsign(callsign);
				} else if (this.scpComponent && callsign.length === 0) {
					this.scpComponent.searchCallsign('');
				}
			});

			callsignInput.addEventListener('blur', (e) => {
				this.handleCallsignBlur(e);
			});
		}
	}

	setupEditListeners() {
		const tbody = this.container?.querySelector('#qso-tbody');
		if (!tbody) return;

		tbody.addEventListener('dblclick', (e) => {
			const row = e.target.closest('tr');
			if (row && row.querySelector('.qso-action-edit')) this.startEditMode(row);
		});

		// Delegated click handler for hamburger dropdown items
		tbody.addEventListener('click', (e) => {
			const editBtn   = e.target.closest('.qso-action-edit');
			const deleteBtn = e.target.closest('.qso-action-delete');
			if (!editBtn && !deleteBtn) return;

			e.preventDefault();
			e.stopPropagation();
			const row = e.target.closest('tr[data-qso-id]');
			if (!row) return;

			if (editBtn)   this.startEditMode(row);
			if (deleteBtn) this.deleteQso(row);
		});
	}

	_renderQsoDropdown(enabled = true) {
		if (!enabled) {
			return `<span title="${lang_qso_not_own}" style="display:inline-flex;">` +
				`<div class="btn btn-secondary py-0 px-1" style="font-size:1rem; width:1.8rem; height:1.8rem; display:inline-flex; align-items:center; justify-content:center; opacity:0.4; cursor:not-allowed; pointer-events:none;">&#9776;</div>` +
				`</span>`;
		}
		return `<div class="dropdown d-inline-block ms-1">
			<div class="btn btn-secondary py-0 px-1" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size:1rem; width:1.8rem; height:1.8rem; display:inline-flex; align-items:center; justify-content:center;">&#9776;</div>
			<div class="dropdown-menu dropdown-menu-end">
				<a class="dropdown-item qso-action-edit" href="#"><i class="fas fa-edit me-1"></i>${lang_qso_edit}</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item text-danger qso-action-delete" href="#"><i class="fas fa-trash me-1"></i>${lang_qso_delete}</a>
			</div>
		</div>`;
	}

	async deleteQso(row) {
		const qsoId    = row.dataset.qsoId;
		const serverId = row.dataset.serverId ? parseInt(row.dataset.serverId) : null;
		const sessionId = window.ContestLoggerConfig?.sessionInfo?.contest_session_id;

		if (!confirm(lang_delete_qso_confirm)) return;

		if (!serverId) {
			// Pending QSO not yet synced to server — remove locally only
			this.dataStore.delete(`qso.${qsoId}`);
			row.remove();
			this.updateQSOCount();
			return;
		}

		try {
			const resp = await fetch(base_url + 'contesting/delete_qso', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ contest_session_id: sessionId, qso_id: serverId }),
			});

			if (!resp.ok) {
				const data = await resp.json().catch(() => ({}));
				throw new Error(data.error ?? `HTTP ${resp.status}`);
			}

			this.dataStore.delete(`qso.${qsoId}`);
			row.remove();
			this.updateQSOCount();
		} catch (err) {
			console.error('deleteQso failed:', err);
			alert('Failed to delete QSO: ' + err.message);
		}
	}


	startEditMode(row) {
		if (!row || row.dataset.editing === 'true') return;
		const serverId = parseInt(row.dataset.serverId);
		if (!serverId) return;

		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());
		const qso = allQsos.find(q => q.serverId === serverId);
		if (!qso) return;

		row.dataset.editing = 'true';

		row.innerHTML = `
			${this._buildDataCells(qso, true)}
			<td class="text-nowrap text-end">
				<button class="btn btn-sm btn-success contest-qso-save-btn" style="line-height:1;" title="${lang_qso_save}">&#10003;</button>
				<button class="btn btn-sm btn-secondary contest-qso-cancel-btn ms-1" style="line-height:1;" title="${lang_qso_cancel}">&#10007;</button>
			</td>
			<td class="text-nowrap text-center">${this.getStatusIndicator(qso.state)}</td>
		`;

		row.querySelector('[name="callsign"]')?.focus();

		row.querySelector('.contest-qso-save-btn').addEventListener('click', () => this.saveEdit(row, qso));
		row.querySelector('.contest-qso-cancel-btn').addEventListener('click', () => {
			row.dataset.editing = 'false';
			this._renderQsoRow(row, qso);
		});

		row.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') { e.preventDefault(); this.saveEdit(row, qso); }
			if (e.key === 'Escape') { e.preventDefault(); row.querySelector('.contest-qso-cancel-btn').click(); }
		});
	}

	_esc(str) {
		return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	/**
	 * Builds a signature of the fields shown in a QSO row. Used to skip re-rendering
	 * rows whose displayed data has not changed (deltas re-send unchanged rows due to
	 * the >= watermark overlap), which avoids destroying an open dropdown mid-click.
	 */
	_qsoRowSignature(qso) {
		return [
			qso.time, qso.callsign, qso.band, qso.frequency, qso.mode, qso.rst_rcvd,
			qso.serial_sent, qso.serial_rcvd ?? qso.serial_recv,
			qso.gridsquare_rcvd, qso.exchange_rcvd, qso.state, qso.serverId
		].join('|');
	}

	_buildDataCells(qso, editMode) {
		const fields          = this.exchangeFields ?? ['exchange'];
		const hasSerial       = fields.includes('serial');
		const hasTextExchange = fields.includes('exchange');
		const hasGridsquare   = fields.includes('gridsquare');
		const isClubStation   = !!(window.ContestLoggerConfig?.isClubStation);

		const band    = qso.band || this.convertQrgToBand(parseInt(qso.frequency));
		const qrg_mhz = qso.frequency ? (parseInt(qso.frequency) / 1e6).toFixed(3) + ' MHz' : '';
		const timeStr = (qso.time || '').substring(0, 8);
		const op      = (qso.operator ?? '').toUpperCase();

		const inp = (val, name, cls = '') =>
			`<input type="text" class="form-control form-control-sm p-0 px-1 ${cls}" style="min-width:3rem;" name="${name}" value="${this._esc(val ?? '')}">`;

		const cols = [
			{ cls: 'text-nowrap', style: editMode ? 'font-size:0.75rem;' : '',
			  display: timeStr,
			  edit: `<input type="text" class="form-control form-control-sm p-0 px-1" style="min-width:5rem;" name="time_on" placeholder="HH:MM:SS" maxlength="8" value="${(qso.time || qso.time_on?.split(' ')?.[1] || '').substring(0, 8)}">` },
			{ cls: editMode ? '' : 'fw-bold',
			  display: qso.callsign,
			  edit: inp(qso.callsign, 'callsign', 'fw-bold text-uppercase') },
			{ title: editMode ? '' : qrg_mhz,
			  display: band || '-',
			  edit: inp(qso.band, 'band', 'text-uppercase') },
			{ display: qso.mode || '-',
			  edit: inp(qso.mode, 'mode', 'text-uppercase') },
			{ display: qso.rst_rcvd || '-',
			  edit: inp(qso.rst_rcvd, 'rst_rcvd') },
			{ cls: 'serial-col', style: hasSerial ? '' : 'display:none;',
			  display: qso.serial_sent ?? '',
			  edit: inp(qso.serial_sent, 'serial_sent') },
			{ cls: 'serial-col', style: hasSerial ? '' : 'display:none;',
			  display: qso.serial_rcvd ?? qso.serial_recv ?? '',
			  edit: inp(qso.serial_rcvd ?? qso.serial_recv, 'serial_rcvd') },
			{ cls: 'gridsquare-col', style: hasGridsquare ? '' : 'display:none;',
			  display: qso.gridsquare_rcvd || '',
			  edit: inp(qso.gridsquare_rcvd, 'gridsquare_rcvd', 'text-uppercase') },
			{ cls: 'exchange-text-col', style: hasTextExchange ? '' : 'display:none;',
			  display: qso.exchange_rcvd || '',
			  edit: inp(qso.exchange_rcvd, 'exchange_rcvd') },
			...isClubStation ? [{ cls: 'operator-col', display: op || '-', edit: '' }] : [],
		];

		return cols.map(({ cls, style, title, display, edit }) => {
			const attrs = [
				cls   ? `class="${cls}"`   : '',
				style ? `style="${style}"` : '',
				title ? `title="${title}"` : '',
			].filter(Boolean).join(' ');
			return `<td${attrs ? ' ' + attrs : ''}>${editMode ? edit : display}</td>`;
		}).join('\n\t\t\t');
	}

	_renderQsoRow(row, qso) {
		row.dataset.qsoId = qso.tmpId || qso.serverId;
		if (qso.serverId) row.dataset.serverId = qso.serverId;
		row.dataset.sig = this._qsoRowSignature(qso);

		const qsoOperator = (qso.operator ?? '').toUpperCase();
		const isEditable = !!qso.serverId && qsoOperator === this.currentOperator;
		if (isEditable) row.style.cursor = 'pointer';

		row.innerHTML = `
			${this._buildDataCells(qso, false)}
			<td class="text-nowrap text-end">${qso.serverId ? this._renderQsoDropdown(isEditable) : ''}</td>
			<td class="text-nowrap text-center">${this.getStatusIndicator(qso.state)}</td>
		`;
	}

	async saveEdit(row, qso) {
		const inputs = row.querySelectorAll('input[name]');
		const data = { qso_id: qso.serverId };
		inputs.forEach(input => {
			// Skip inputs inside hidden cells — their empty values would overwrite DB data
			if (input.closest('td')?.offsetParent !== null) {
				data[input.name] = input.value.trim().toUpperCase();
			}
		});

		const saveBtn = row.querySelector('.contest-qso-save-btn');

		if (data.time_on !== undefined) {
			// Accept HH:MM:SS (contest precision) or HH:MM (seconds default to :00).
			const m = data.time_on.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
			if (!m) {
				const input = row.querySelector('[name="time_on"]');
				if (input) { input.classList.add('is-invalid'); input.focus(); }
				return;
			}
			const datePart = (qso.time_on || '').split(' ')[0] || qso.date || '';
			data.time_on = `${datePart} ${m[1]}:${m[2]}:${m[3] ?? '00'}`;
		}

		const sessionInfo = window.ContestLoggerConfig?.sessionInfo ?? {};
		data.contest_session_id = sessionInfo.contest_session_id;

		if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '…'; }

		try {
			const resp = await fetch(base_url + 'index.php/contesting/update_qso', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(data)
			});
			const result = await resp.json();

			if (!result.success) throw new Error(result.error || 'Server error');

			// Update local DataStore
			const updated = { ...qso, ...data, operator: qso.operator };
			// Normalize field names to match DataStore conventions
			if (data.serial_rcvd !== undefined) updated.serial_rcvd = data.serial_rcvd;
			if (data.exchange_rcvd !== undefined) updated.exchange_rcvd = data.exchange_rcvd;
			if (data.time_on !== undefined) updated.time = data.time_on.split(' ')[1];
			this.dataStore.set(`qso.${qso.tmpId}`, updated);

			// No need to guard against a self-resync: the next check_sync may return this
			// QSO again, but applyDelta() upserts by serverId and is idempotent.

			row.dataset.editing = 'false';
			this._renderQsoRow(row, updated);

		} catch (err) {
			console.error('QSO edit failed:', err);
			if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '✓'; }
			const lastCell = row.querySelector('td:last-child');
			if (lastCell) {
				const errSpan = document.createElement('span');
				errSpan.className = 'text-danger ms-1';
				errSpan.title = err.message;
				errSpan.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
				lastCell.appendChild(errSpan);
				setTimeout(() => errSpan.remove(), 3000);
			}
		}
	}

	async handleCallsignBlur(e) {
		const callsign = e.target.value.trim().toUpperCase();
		if (!callsign) {
			this.lastDxccCallsign = null;
			this.lastDxccInfo = null;
			this.updateDxccInfoDisplay(null);
			this.updateWorkedBeforeWarning('');
			this.writeDxccToView(null);
			this.writeCallbookToView(null);
			this.dataStore?.emit('qso_location_updated', null);
			return;
		}

		const lookupToken = ++this.callbookLookupToken;
		this.updateDxccInfoDisplay({ status: 'loading' });

		try {
			const result = await this.lookupCallbook(callsign);
			if (lookupToken !== this.callbookLookupToken) return;

			this.lastDxccCallsign = callsign;
			this.lastDxccInfo = result || null;
			this.updateDxccInfoDisplay(result);
			this.writeDxccToView(result);
			this.writeCallbookToView(result);
			this.emitQsoLocation(result);
		} catch (error) {
			if (lookupToken !== this.callbookLookupToken) return;
			console.error('QSO Form: callbook lookup failed', error);
			this.updateDxccInfoDisplay({ status: 'error' });
			this.writeDxccToView(null);
			this.dataStore?.emit('qso_location_updated', null);
		}
	}

	/**
	 * Emit the current QSO's map location. Prefers the entered gridsquare (precise);
	 * the map decodes it. Falls back to the DXCC country center from the lookup.
	 * Passing the raw grid lets the map plot the exact field instead of the country.
	 * @param {Object|null} dxccInfo
	 */
	emitQsoLocation(dxccInfo) {
		if (!this.dataStore) return;
		const grid = this.container?.querySelector('#qso-gridsquare-received')?.value.trim().toUpperCase() || '';
		const lat = parseFloat(dxccInfo?.lat ?? dxccInfo?.latitude);
		const lon = parseFloat(dxccInfo?.long ?? dxccInfo?.lon ?? dxccInfo?.longitude);
		const hasDxcc = Number.isFinite(lat) && Number.isFinite(lon);

		if (!grid && !hasDxcc) {
			this.dataStore.emit('qso_location_updated', null);
			return;
		}
		this.dataStore.emit('qso_location_updated', {
			grid: grid || null,
			lat: hasDxcc ? lat : null,
			lon: hasDxcc ? lon : null
		});
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

	async lookupCallbook(callsign) {
		if (!callsign) return null;

		const cacheKey = `callbook.${callsign}`;
		const cached = this.dataStore?.get(cacheKey);
		if (cached !== undefined) return cached;

		const callbookEnabled = window.ContestLoggerConfig?.sessionInfo?.callbook_lookup !== false;
		const url = `${base_url}index.php/contesting/callbook?call=${encodeURIComponent(callsign)}${callbookEnabled ? '' : '&dxcc_only=1'}`;
		const response = await fetch(url, {
			method: 'GET',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		});
		if (!response.ok) return null;
		const result = await response.json();
		if (this.dataStore && result) {
			this.dataStore.setLocal(cacheKey, result);
		}
		return result;
	}

	writeCallbookToView(result) {
		if (!this.container) return;

		const fields = {
			'#qso-callbook-name': result?.name ?? '',
			'#qso-callbook-qth':  result?.qth  ?? '',
			'#qso-callbook-grid': result?.grid  ?? '',
			'#qso-callbook-ituz': result?.ituz  ?? '',
		};
		Object.entries(fields).forEach(([sel, val]) => {
			const el = this.container.querySelector(sel);
			if (el) el.value = val;
		});

		// Prefill gridsquare-received if currently empty
		const gridInput = this.container.querySelector('#qso-gridsquare-received');
		if (gridInput && !gridInput.value && result?.grid) {
			gridInput.value = result.grid;
		}

		// Show callbook info line (name · QTH)
		const infoEl = this.container.querySelector('#qso-callbook-info');
		if (infoEl) {
			const parts = [result?.name, result?.qth].filter(Boolean);
			infoEl.textContent = parts.join(' · ');
			infoEl.style.display = parts.length ? '' : 'none';
		}
	}

	updateDxccInfoDisplay(dxccInfo) {
		const infoEl = this.container?.querySelector('#qso-dxcc-info');
		if (!infoEl) return;

		if (!dxccInfo) {
			infoEl.textContent = '';
			return;
		}

		if (dxccInfo.status === 'loading') {
			infoEl.textContent = lang_dxcc_lookup;
			return;
		}

		if (dxccInfo.status === 'error') {
			infoEl.textContent = lang_dxcc_lookup_failed;
			return;
		}

		const entity = dxccInfo.entity || dxccInfo.entity_name || '-';
		const cont = dxccInfo.cont || dxccInfo.continent || '-';
		const cqz = dxccInfo.cqz || dxccInfo.CQ_zone || '-';
		const adif = dxccInfo.adif ?? null;
		const isUnknown = adif === 0 || entity.toUpperCase().includes('NONE');

		if (isUnknown) {
			infoEl.textContent = lang_dxcc_not_found;
			return;
		}

		let infoText = `DXCC: ${entity} · ${cont} · CQ ${cqz}`;
		if (this._bearingInfo) {
			infoText += ` · D ${this._bearingInfo.distance} km · Az ${this._bearingInfo.azimuth}°`;
		}
		infoEl.textContent = infoText;
	}

	checkWorkedBefore(callsign) {
		if (!callsign || !this.dataStore || !this.radioComponent) {
			return false;
		}

		const currentBand = this.radioComponent.getBand();
		if (!currentBand) {
			return false;
		}

		const currentMode = this.radioComponent.getMode();
		if (!currentMode) {
			return false;
		}

		for (const qso of this.dataStore.getPattern('qso.*').values()) {
			if ((qso.callsign || '').toUpperCase() !== callsign) {
				continue;
			}
			const qsoBand = qso.band || this.convertQrgToBand(parseInt(qso.frequency));
			const qsoMode = qso.mode || null;
			if (qsoBand === currentBand && qsoMode === currentMode) {
				return true;
			}
		}
		return false;
	}

	updateWorkedBeforeWarning(callsign) {
		const warning = this.container?.querySelector('#qso-worked-before-warning');
		const qsoinput = this.container?.querySelector('#qso-callsign');
		if (!warning) {
			return;
		}

		if (callsign && this.checkWorkedBefore(callsign)) {
			const band = this.radioComponent?.getBand() || '?';
			const mode = this.radioComponent?.getMode()?.toUpperCase() || '?';
			qsoinput?.classList.add('border-danger');
			warning.textContent = lang_worked_before.replace('%s', band + '/' + mode);
			warning.style.display = '';
		} else {
			qsoinput?.classList.remove('border-danger');
			warning.textContent = '';
			warning.style.display = 'none';
		}
	}

	loadExistingQSOs() {
		if (!this.dataStore) return;

		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());

		const sorted = this.sortQsosByNewest(allQsos);
		sorted.forEach(qso => this.addQSOToTable(qso));
		this.updateQSOCount();

		this.nextSerialSent = this.computeNextSerial();
		this.updateSerialSentDisplay();

		const exchangeSentInput = this.container.querySelector('#qso-exchange-sent');
		if (exchangeSentInput) exchangeSentInput.value = this.getLastExchangeSent();

		// Bearing/distance from map component (emitted when qso_location_updated resolves)
		this.dataStore.on('qso_bearing_updated', (data) => {
			this._bearingInfo = data;
			this.updateDxccInfoDisplay(this.lastDxccInfo);
			if (data) {
				this.radioComponent?.sendLookupResult({
					callsign: this.lastDxccCallsign,
					dxcc_id:  this.lastDxccInfo?.adif  ?? null,
					name:     this.lastDxccInfo?.name  ?? null,
					grid:     this.lastDxccInfo?.grid  ?? null,
					bearing:  `${data.azimuth}° ${data.distance} km`,
					azimuth:  data.azimuth,
					distance: data.distance,
				});
			}
		});

		// Listen for QSO state changes
		this.dataStore.on('qso_state_changed', (eventData) => this.handleQSOStateChanged(eventData));

		// Listen for full resync events (server -> client) to refresh table
		this.dataStore.on('qsos_resynced', (eventData) => this.handleQSOsResynced(eventData));
	}

	handleQSOsResynced(eventData) {
		if (!this.dataStore) return;

		// Defer the destructive table rebuild while the user is interacting with a row:
		// an open edit form (data-editing) or an open action dropdown would otherwise be
		// wiped under the user. The data is already in the DataStore; the next heartbeat
		// after the interaction finishes will re-render.
		const tbody = this.container?.querySelector('#qso-tbody');
		if (tbody && (
			tbody.querySelector('tr[data-editing="true"]') ||
			tbody.querySelector('.dropdown-menu.show') ||
			tbody.querySelector('[data-bs-toggle="dropdown"][aria-expanded="true"]')
		)) {
			return;
		}

		this.clearTable();
		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());

		const sorted = this.sortQsosByNewest(allQsos);
		sorted.forEach(qso => this.addQSOToTable(qso));
		this.updateQSOCount();

		this.nextSerialSent = this.computeNextSerial();
		this.updateSerialSentDisplay();

		console.debug(`QSO Form: Resynced table (server=${eventData?.server ?? '?'}, protected=${eventData?.protected ?? '?'})`);
	}

	handleQSOStateChanged(eventData) {
		const { qso, oldState, newState } = eventData;
		this.updateQSOInTable(qso);
		console.debug(`QSO Form: QSO ${qso.id} state changed from ${oldState} to ${newState}`);
	}

	getStatusIndicator(state) {
		if (state === 'pending') {
			return `<span title="${lang_status_new}" style="color: orange;">&#9679;</span>`;
		} else if (state === 'synced') {
			return `<span title="${lang_status_synced}" style="color: green;">&#9679;</span>`;
		} else if (state === 'error') {
			return `<span title="${lang_status_error}" style="color: red;">&#9679;</span>`;
		} else {
			return `<span title="${lang_status_unknown}" style="color: gray;">&#9679;</span>`;
		}
	}

	addQSOToTable(qso) {
		if (!this.container) return;

		const tbody = this.container.querySelector('#qso-tbody');
		if (!tbody) return;

		const row = document.createElement('tr');
		this._renderQsoRow(row, qso);
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

		if (existingRow.dataset.editing === 'true') return;

		// Update data attributes so the edit mode can find the server ID
		existingRow.dataset.qsoId = qsoId;
		if (qso.serverId) existingRow.dataset.serverId = qso.serverId;

		// Show pointer cursor once the QSO is editable (synced + own operator)
		const isEditable = !!qso.serverId && (qso.operator ?? '').toUpperCase() === this.currentOperator;
		existingRow.style.cursor = isEditable ? 'pointer' : '';

		// Update dropdown and status indicator in their respective cells
		const cells = existingRow.querySelectorAll('td');
		const statusCell   = cells[cells.length - 1];
		const dropdownCell = cells[cells.length - 2];
		if (statusCell)   statusCell.innerHTML   = this.getStatusIndicator(qso.state);
		if (dropdownCell) dropdownCell.innerHTML = qso.serverId ? this._renderQsoDropdown(isEditable) : '';
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

	getLastExchangeSent() {
		const allQsos = Array.from(this.dataStore.getPattern('qso.*').values());
		if (!allQsos.length) return '';
		const sorted = allQsos.slice().sort((a, b) => this.getQsoTimestamp(b) - this.getQsoTimestamp(a));
		return sorted[0]?.exchange_sent ?? '';
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
		if (exchangeSentInput) exchangeSentInput.value = this.getLastExchangeSent();
		if (exchangeReceivedInput) exchangeReceivedInput.value = '';

		const serialRcvdInput = this.container.querySelector('#qso-serial-received');
		if (serialRcvdInput) serialRcvdInput.value = '';

		const gridsquareRcvdInput = this.container.querySelector('#qso-gridsquare-received');
		if (gridsquareRcvdInput) gridsquareRcvdInput.value = '';

		this.lastDxccCallsign = null;
		this.lastDxccInfo = null;
		this.updateDxccInfoDisplay(null);
		this.writeDxccToView(null);
		this.updateWorkedBeforeWarning('');
		this.applyRstDefaults();

		// Clear callbook fields and invalidate any in-flight lookup
		['#qso-callbook-name', '#qso-callbook-qth', '#qso-callbook-grid', '#qso-callbook-ituz']
			.forEach(sel => { const el = this.container.querySelector(sel); if (el) el.value = ''; });
		const cbInfo = this.container.querySelector('#qso-callbook-info');
		if (cbInfo) { cbInfo.textContent = ''; cbInfo.style.display = 'none'; }
		this.callbookLookupToken++;
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
				serial_sent: qso.serial_sent ?? null,
				serial_rcvd: qso.serial_rcvd ?? null,
				gridsquare_sent: qso.gridsquare_sent ?? null,
				gridsquare_rcvd: qso.gridsquare_rcvd ?? null,
				operator: qso.operator,
				country: qso.country || qso.entity || null,
				continent: qso.continent || qso.cont || null,
				dxcc_id: qso.dxcc_id || qso.dxcc || null,
				cqz: qso.cqz || null,
				name: qso.name || null,
				qth:  qso.qth  || null,
				ituz: qso.ituz || null,
			}
		}));
	}

	processQsoSyncResponse(responseData, dataStore) {
		if (responseData.saved_qsos && responseData.saved_qsos.length > 0) {
			this.processSavedQsos(responseData.saved_qsos, dataStore);
			console.debug(`QSO Form: ${responseData.saved_qsos.length} QSO(s) saved to server`);
		}

		if (responseData.needs_resync && responseData.all_qsos) {
			// Count mismatch (e.g. a delete elsewhere): replace local state with the full set.
			this.resyncWithServer(responseData.all_qsos, responseData.saved_qsos || [], dataStore);
		} else if (responseData.needs_resync) {
			console.error('QSO Form: needs_resync=true but all_qsos missing!');
		} else if (responseData.changed_qsos && responseData.changed_qsos.length > 0) {
			// Normal case: apply only the QSOs changed since our watermark.
			this.applyDelta(responseData.changed_qsos, dataStore);
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

					dataStore.setLocal(`qso.${saved.tmp_id}`, updated);

					// Advance the watermark so the next check_sync does not pull this QSO
					// back as a delta.
					this._advanceWatermark(saved.last_modified_ms, updated.serverId);

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

	/**
	 * Maps a server QSO row to a local DataStore QSO object (state 'synced').
	 * @param {Object} sq Server QSO row
	 * @param {string} tmpId Local key to assign
	 * @returns {Object}
	 */
	_mapServerQso(sq, tmpId) {
		const timeOn = sq.time_on || '';
		const [datePart, timePart] = timeOn.includes(' ')
			? timeOn.split(' ')
			: [sq.date, sq.time];

		const freq = sq.frequency === undefined
			? undefined
			: typeof sq.frequency === 'string'
				? Number(sq.frequency) || sq.frequency
				: sq.frequency;

		const dxccLat = parseFloat(sq.dxcc_lat);
		const dxccLon = parseFloat(sq.dxcc_lon);

		return {
			serverId: parseInt(sq.id ?? sq.qso_id),
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
			gridsquare_rcvd: sq.locator ?? null,
			lat: Number.isFinite(dxccLat) ? dxccLat : undefined,
			lon: Number.isFinite(dxccLon) ? dxccLon : undefined,
			operator: sq.operator,
			state: 'synced'
		};
	}

	/**
	 * Advances the (lastSeenTs, lastSeenId) watermark for one server QSO.
	 * Within the same last_modified second the higher serverId wins; a newer second
	 * resets the id baseline. Mirrors the server-side (second, qso_id) comparison.
	 */
	_advanceWatermark(lastModifiedMs, serverId) {
		const ms = Number(lastModifiedMs) || 0;
		const id = parseInt(serverId) || 0;
		if (ms > this.lastSeenTs) {
			this.lastSeenTs = ms;
			this.lastSeenId = id;
		} else if (ms === this.lastSeenTs && id > this.lastSeenId) {
			this.lastSeenId = id;
		}
	}

	/**
	 * Full replace of synced QSOs with the server's complete set.
	 * Only used on count mismatch (e.g. a delete elsewhere). Pending (unconfirmed
	 * local) QSOs are preserved.
	 */
	resyncWithServer(serverQsos, savedQsos = [], dataStore) {
		const localPendingQsos = Array.from(dataStore.getPattern('qso.*').values()).filter(q => q.state === 'pending');
		const tmpIdMap = new Map(savedQsos.map(s => [s.tmp_id, s.server_id]));
		const protectedNewQsos = localPendingQsos.filter(q => !tmpIdMap.has(q.tmpId));

		// Remove all non-pending QSOs
		for (const [key, qso] of dataStore.getPattern('qso.*').entries()) {
			if (qso.state !== 'pending') dataStore.delete(key);
		}

		serverQsos.forEach((sq) => {
			const tmpId = dataStore.generateId();
			const qso = this._mapServerQso(sq, tmpId);
			dataStore.setLocal(`qso.${tmpId}`, qso);
			this._advanceWatermark(sq.last_modified_ms, qso.serverId);
		});

		dataStore.emit('qsos_resynced', {
			server: serverQsos.length,
			protected: protectedNewQsos.length
		});
	}

	/**
	 * Applies an incremental set of changed QSOs (adds + edits) without touching
	 * unrelated local QSOs. Idempotent: matches existing QSOs by serverId so a row
	 * re-sent due to the >= watermark overlap simply overwrites itself.
	 * Pending (unconfirmed local) QSOs are never touched.
	 */
	applyDelta(changedQsos, dataStore) {
		// Index existing local QSOs by serverId for upsert lookup
		const keyByServerId = new Map();
		for (const [key, qso] of dataStore.getPattern('qso.*').entries()) {
			if (qso.serverId) keyByServerId.set(parseInt(qso.serverId), key);
		}

		changedQsos.forEach((sq) => {
			const serverId = parseInt(sq.id ?? sq.qso_id);
			const existingKey = keyByServerId.get(serverId);
			const tmpId = existingKey ? dataStore.get(existingKey).tmpId : dataStore.generateId();
			const key = existingKey ?? `qso.${tmpId}`;
			const qso = this._mapServerQso(sq, tmpId);

			dataStore.setLocal(key, qso);
			this._advanceWatermark(sq.last_modified_ms, qso.serverId);

			// Render only this row instead of rebuilding the whole table — the delta
			// usually carries a single QSO, so we touch O(changed) rows, not O(all).
			this._upsertQsoRow(qso);
		});

		this.updateQSOCount();
		this.nextSerialSent = this.computeNextSerial();
		this.updateSerialSentDisplay();
	}

	/**
	 * Inserts or updates a single QSO row in place, without rebuilding the table.
	 * Matches the existing row by serverId, falling back to the local tmpId key.
	 * Skips the row if it is currently being edited or its action menu is open, so a
	 * background delta cannot wipe the user's interaction (the next delta re-renders it).
	 */
	_upsertQsoRow(qso) {
		const tbody = this.container?.querySelector('#qso-tbody');
		if (!tbody) return;

		const existingRow = (qso.serverId && tbody.querySelector(`tr[data-server-id="${qso.serverId}"]`)) ||
			tbody.querySelector(`tr[data-qso-id="${qso.tmpId}"]`);

		if (existingRow) {
			// Skip if the displayed data is unchanged — avoids needless re-renders that
			// would destroy an open dropdown mid-interaction (deltas re-send unchanged rows).
			if (existingRow.dataset.sig === this._qsoRowSignature(qso)) return;

			// Do not disturb a row the user is interacting with
			if (existingRow.dataset.editing === 'true' ||
				existingRow.querySelector('.dropdown-menu.show') ||
				existingRow.querySelector('[data-bs-toggle="dropdown"][aria-expanded="true"]')) {
				return;
			}
			this._renderQsoRow(existingRow, qso);
		} else {
			this.addQSOToTable(qso);
		}
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
		const exchangeSent = this.container.querySelector('#qso-exchange-sent')?.value.trim().toUpperCase();
		const exchangeRcvd = this.container.querySelector('#qso-exchange-received')?.value.trim().toUpperCase();
		const serialSent = this.container.querySelector('#qso-serial-sent')?.value || null;
		const serialRcvd = this.container.querySelector('#qso-serial-received')?.value || null;
		const gridsquareSent = this.container.querySelector('#qso-gridsquare-sent')?.value.trim().toUpperCase() || null;
		const gridsquareRcvd = this.container.querySelector('#qso-gridsquare-received')?.value.trim().toUpperCase() || null;
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
			this.windowmanager.showToast(lang_error, lang_radio_component_not_available, 'bg-danger text-white', 5000);
			return;
		}

		const frequency = this.radioComponent.getFrequency();
		const mode = this.radioComponent.getMode();

		if (!frequency || !mode) {
			console.error('QSO Form: Frequency or Mode not available', { frequency, mode });
			this.windowmanager.showToast(lang_error, lang_frequency_or_mode_not_set, 'bg-danger text-white', 5000);
			return;
		}

		// Create QSO data object
		const qsoData = {
			callsign,
			rst_sent: rstSent || this.defaultRst(),
			rst_rcvd: rstReceived || this.defaultRst(),
			exchange_sent: exchangeSent,
			exchange_rcvd: exchangeRcvd,
			serial_sent: serialSent,
			serial_rcvd: serialRcvd,
			gridsquare_sent: gridsquareSent,
			gridsquare_rcvd: gridsquareRcvd,
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

		const cbName = this.container.querySelector('#qso-callbook-name')?.value || null;
		const cbQth  = this.container.querySelector('#qso-callbook-qth')?.value  || null;
		const cbItuz = this.container.querySelector('#qso-callbook-ituz')?.value || null;
		if (cbName) qsoData.name = cbName;
		if (cbQth)  qsoData.qth  = cbQth;
		if (cbItuz) qsoData.ituz = cbItuz;

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

		if (serialSent !== null) {
			const numVal = parseInt(serialSent, 10);
			const padLen = serialSent.length > 1 && serialSent.startsWith('0') ? serialSent.length : 0;
			const next = numVal + 1;
			this.nextSerialSent = padLen > 0 ? String(next).padStart(padLen, '0') : next;
			this.updateSerialSentDisplay();
		}

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
