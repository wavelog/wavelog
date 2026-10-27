(function () {
	'use strict';
	const form = document.getElementById('prop-form');
	if (!form) return;
	const model = window.PropagationModel;
	const config = propagationConfig;
	const text = config.text;
	const element = id => document.getElementById('prop-' + id);
	const field = name => form.elements.namedItem(name);
	const numeric = ['power', 'dxPower', 'gain', 'dxGain', 'ssn'];
	const savedFields = ['home', 'target', 'mode', 'path', 'noise', 'dxNoise', 'ssnSource', ...numeric];
	let solar = null;
	let state = null, dx = null, hours = [], chart = null, map = null, generation = 0;
	let coverage = null, night = null, homeMarker = null, dxMarker = null, request = null, renderer = null;
	let storageProblem = false;
	let worker = null, nextId = 0, mapGeneration = 0, worldGeneration = 0;
	const pending = new Map();
	const mapCache = new Map();
	let renderedMapKey = null;

	function updateStatus() {
		element('engine-status').textContent = pending.size ? text.engineLoading : '';
	}

	function engineRequest(type, payload) {
		return new Promise((resolve, reject) => {
			try {
				if (!worker) {
					worker = new Worker(config.workerUrl);
					worker.onmessage = event => {
						const response = event.data;
						if (response.id === 0 && response.error) {
							workerFailure(response.error);
							return;
						}
						const job = pending.get(response.id);
						if (!job) return;
						if (Number.isFinite(response.progress)) {
							if (job.type === 'map') element('map-progress').textContent = text.mapLoading + ' ' + response.progress + '%';
							return;
						}
						pending.delete(response.id);
						updateStatus();
						if (response.error) job.reject(new Error(text.engineError + ' ' + response.error));
						else job.resolve(response.result);
					};
					worker.onerror = event => workerFailure(event.message);
					worker.postMessage({type: 'initialize', config: {modelUrl: config.modelUrl, engineUrl: config.engineUrl, dataUrl: config.dataUrl}});
				}
				for (const [id, job] of pending) {
					if (job.type === type) {
						job.resolve(null);
						pending.delete(id);
					}
				}
				const id = ++nextId;
				pending.set(id, {type, resolve, reject});
				updateStatus();
				worker.postMessage({id, type, payload});
			} catch (error) {
				workerFailure(error.message);
				reject(new Error(text.engineError));
			}
		});
	}

	function workerFailure(detail) {
		if (worker) worker.terminate();
		worker = null;
		for (const job of pending.values()) job.reject(new Error(text.engineError + ' ' + detail));
		pending.clear();
		updateStatus();
		showError(text.engineError + ' ' + detail);
	}

	function showError(message) {
		const messages = [message, storageProblem ? text.storageError : ''].filter(Boolean);
		element('error').textContent = messages.join(' ');
		element('error').classList.toggle('d-none', !messages.length);
	}

	function utcInput() {
		field('time').value = new Date().toISOString().slice(0, 13) + ':00';
	}

	function solarStatus(message, warning = false) {
		element('solar-message').textContent = message;
		element('solar-status').classList.toggle('text-warning', warning);
	}

	function solarInfo(date) {
		if (field('ssnSource').value === 'manual') {
			solarStatus(text.solarManual, true);
			return;
		}
		const month = Number.isFinite(date.getTime()) ? date.toISOString().slice(0, 7) : '';
		const value = solar && solar.months[month];
		if (!value || !Number.isInteger(value.ssn) || value.ssn < 1 || value.ssn > 311) {
			field('ssn').value = '';
			solarStatus(text.solarMonthMissing + ' ' + month, true);
			return;
		}
		field('ssn').value = value.ssn;
		solarStatus((value.kind === 'observed' ? text.solarObserved : text.solarPredicted) +
			' | NOAA SWPC / SILSO V2 | ' + month + ': ' + value.value + ' -> ' + value.ssn +
			' | ' + text.solarUpdated + ': ' + new Date(solar.updated * 1000).toISOString().slice(0, 16).replace('T', ' ') +
			(solar.stale ? ' | ' + text.solarStale : ''), solar.stale);
	}

	async function loadSolar(signal) {
		if (field('ssnSource').value === 'manual') {
			solarInfo(new Date(field('time').value + 'Z'));
			return;
		}
		solarStatus(text.solarLoading);
		if (!solar) {
			const controller = new AbortController();
			const abort = () => controller.abort();
			const timeout = setTimeout(abort, 30000);
			signal.addEventListener('abort', abort, {once: true});
			try {
				const response = await fetch(config.solarUrl, {signal: controller.signal, headers: {Accept: 'application/json'}});
				if (!response.ok) throw new Error(text.solarUnavailable);
				const data = await response.json();
				if (!data.months || typeof data.months !== 'object' || !Number.isInteger(data.updated)) {
					throw new Error(text.solarUnavailable);
				}
				solar = data;
			} catch (error) {
				if (signal.aborted) throw error;
				field('ssn').value = '';
				solarStatus(text.solarUnavailable, true);
				throw new Error(text.solarUnavailable);
			} finally {
				clearTimeout(timeout);
				signal.removeEventListener('abort', abort);
			}
		}
		solarInfo(new Date(field('time').value + 'Z'));
		if (field('ssn').value === '') throw new Error(text.solarMonthMissing);
	}

	function solarSource() {
		const manual = field('ssnSource').value === 'manual';
		field('ssn').readOnly = !manual;
		field('ssn').required = manual;
		if (!manual) field('ssn').value = '';
		solarInfo(new Date(field('time').value + 'Z'));
	}

	function readState() {
		let home;
		try {
			home = model.gridPoint(field('home').value.trim());
		} catch (error) {
			throw new Error(text.homeError);
		}
		const settings = {};
		for (const name of numeric) settings[name] = field(name).value === '' ? NaN : Number(field(name).value);
		for (const name of ['mode', 'path', 'noise', 'dxNoise']) settings[name] = field(name).value;
		const date = new Date(field('time').value + 'Z');
		date.setUTCMinutes(0, 0, 0);
		if (field('ssnSource').value === 'auto' && solar && Number.isFinite(date.getTime())) {
			settings.solarMonths = {};
			for (const time of [date, new Date(date.getTime() + 23 * 3600000)]) {
				const month = time.toISOString().slice(0, 7);
				const entry = solar.months[month];
				if (!entry || !Number.isInteger(entry.ssn) || entry.ssn < 1 || entry.ssn > 311) {
					throw new Error(text.solarMonthMissing + ' ' + month);
				}
				settings.solarMonths[month] = entry.ssn;
			}
		}
		try {
			model.validate(settings, date);
		} catch (error) {
			throw new Error(text.calculationError);
		}
		field('time').value = date.toISOString().slice(0, 16);
		return {home, settings, date};
	}

	function saveSettings() {
		try {
			const settings = {};
			for (const name of savedFields) settings[name] = field(name).value;
			localStorage.setItem(config.settingsKey, JSON.stringify(settings));
		} catch (error) {
			storageProblem = true;
			showError('');
		}
	}

	function timeLabel(date) {
		return date.toISOString().slice(0, 16).replace('T', ' ') + ' UTC';
	}

	function selectedTime() {
		const date = new Date(state.date.getTime() + Number(element('hour').value) * 3600000);
		solarInfo(date);
		return date;
	}

	function cell(parent, tag, content) {
		const node = document.createElement(tag);
		node.textContent = content;
		parent.appendChild(node);
		return node;
	}

	function marginLabel(result) {
		const label = value => value === null ? text.noMode : Math.round(value);
		return label(result.rxMargin) + ' / ' + label(result.txMargin) + (result.longPath ? ' LP' : '');
	}

	function resultCell(row, result, onClick) {
		const margin = result.rxMargin === null || result.txMargin === null ? null : model.twoWayMargin(result);
		const td = cell(row, 'td', '');
		td.style.setProperty('--prop-signal-color', model.signalColor(margin));
		const button = cell(td, 'button', marginLabel(result));
		button.type = 'button';
		button.title = result.band + ': ' + text[model.quality(margin)] + ' (RX / TX dB)';
		button.addEventListener('click', onClick);
	}

	function newTable(container, headings, caption) {
		const table = document.createElement('table');
		table.className = 'table table-bordered table-sm prop-table';
		cell(table, 'caption', caption);
		const row = table.createTHead().insertRow();
		headings.forEach(heading => { cell(row, 'th', heading).scope = 'col'; });
		container.replaceChildren(table);
		return table.createTBody();
	}

	async function drawDx() {
		element('dx-table').replaceChildren();
		if (chart) { chart.destroy(); chart = null; }
		if (!dx || !state) {
			element('dx-info').textContent = text.empty;
			return;
		}
		element('dx-info').textContent = dx.target + ' / ' + dx.grid + ' (' + dx.source + ')';
		const current = state, target = dx;
		const forecast = await engineRequest('forecast', {...current, dx: target});
		if (!forecast || current !== state || target !== dx) return;
		hours = forecast.map(hour => ({...hour, time: new Date(hour.time)}));
		const body = newTable(element('dx-table'), [text.band, ...hours.map(hour => timeLabel(hour.time))], dx.target + ' (RX / TX dB)');
		model.bands.forEach((band, index) => {
			const row = body.insertRow();
			cell(row, 'th', band.name).scope = 'row';
			hours.forEach(hour => resultCell(row, hour.bands[index], () => {
				element('chart-band').value = band.name;
				drawChart();
			}));
		});
		drawChart();
	}

	function drawChart() {
		if (chart) { chart.destroy(); chart = null; }
		if (!dx || !hours.length) return;
		const index = model.bands.findIndex(band => band.name === element('chart-band').value);
		const points = hours.map(hour => hour.bands[index]);
		chart = new Chart(element('chart'), {
			type: 'line',
			data: {
				labels: hours.map((hour, i) => timeLabel(hour.time) + (points[i].longPath ? ' LP' : '')),
				datasets: [
					{label: text.rx, data: points.map(point => point.rx), borderColor: '#3288ed', fill: false},
					{label: text.tx, data: points.map(point => point.tx), borderColor: '#d88317', fill: false},
					{label: text.threshold, data: points.map(() => model.thresholds[state.settings.mode]), borderColor: '#888888', borderDash: [5, 5], pointRadius: 0, fill: false}
				]
			},
			options: {responsive: true, maintainAspectRatio: false, animation: false}
		});
	}

	async function drawWorld() {
		if (!state) return;
		const version = ++worldGeneration;
		const time = selectedTime();
		element('hour-label').textContent = timeLabel(time);
		element('world-table').replaceChildren();
		const current = state;
		const predictions = await engineRequest('world', {...current, date: time,
			points: config.regions.map(([, lat, lng]) => ({lat, lng}))});
		if (!predictions || version !== worldGeneration || current !== state) return;
		const body = newTable(element('world-table'), [text.region, ...model.bands.map(band => band.name)], timeLabel(time) + ' (RX / TX dB)');
		for (let i = 0; i < config.regions.length; i++) {
			const name = config.regions[i][0];
			const results = predictions[i].bands;
			const row = body.insertRow();
			cell(row, 'th', name).scope = 'row';
			results.forEach(result => resultCell(row, result, () => {
				element('map-band').value = result.band;
				bootstrap.Tab.getOrCreateInstance(element('tab-map')).show();
			}));
		}
	}

	async function drawMap() {
		if (!state || !element('pane-map').classList.contains('active')) return;
		const current = state;
		const band = model.bands.find(band => band.name === element('map-band').value);
		const time = selectedTime();
		const key = model.coverageKey(current.home, time, current.settings, band);
		const direction = element('direction').value;
		if (renderedMapKey === key && coverage) {
			++mapGeneration;
			element('map-loading').classList.add('d-none');
			element('map').setAttribute('aria-busy', 'false');
			map.invalidateSize();
			for (const layer of coverage.getLayers()) {
				const result = layer.propagationResult;
				layer.setStyle({fillColor: model.signalColor(direction === 'rx' ? result.rxMargin : result.txMargin)});
			}
			drawDxMarker();
			return;
		}
		const version = ++mapGeneration;
		element('map-loading').classList.remove('d-none');
		element('map-progress').textContent = text.mapLoading;
		element('map').setAttribute('aria-busy', 'true');
		try {
		if (!map) {
			map = L.map(element('map'), {worldCopyJump: true}).setView([20, 0], 2);
			renderer = L.canvas({padding: 0.5});
			L.tileLayer(option_map_tile_server, {
				attribution: option_map_tile_server_copyright, subdomains: option_map_tile_subdomains
			}).addTo(map);
			map.on('click', async event => {
				if (!state) return;
				const point = {lat: event.latlng.lat, lng: ((event.latlng.lng + 540) % 360) - 180};
				if (Math.abs(point.lat) > 90) return;
				const index = model.bands.findIndex(band => band.name === element('map-band').value);
				const current = state, currentMap = mapGeneration;
				try {
					const result = await engineRequest('point', {...current, point, date: selectedTime(), band: model.bands[index]});
					if (!result || current !== state || currentMap !== mapGeneration) return;
					const popup = document.createElement('span');
					popup.textContent = point.lat.toFixed(2) + ', ' + point.lng.toFixed(2) + ' | ' + result.band + ' | RX / TX: ' + marginLabel(result) + ' dB';
					L.popup().setLatLng(event.latlng).setContent(popup).openOn(map);
				} catch (error) {
					showError(error.message);
				}
			});
		}
		map.invalidateSize();
		drawDxMarker();
		let calculation = mapCache.get(key);
		if (calculation) {
			mapCache.delete(key);
			mapCache.set(key, calculation);
		} else {
			calculation = engineRequest('map', {...current, date: time, band});
			mapCache.set(key, calculation);
			while (mapCache.size > 4) mapCache.delete(mapCache.keys().next().value);
			calculation.then(result => {
				if (!result && mapCache.get(key) === calculation) mapCache.delete(key);
			}, () => {
				if (mapCache.get(key) === calculation) mapCache.delete(key);
			});
		}
		const predictions = await calculation;
		if (!predictions || version !== mapGeneration || current !== state) return;
		if (coverage) map.removeLayer(coverage);
		if (night) map.removeLayer(night);
		if (homeMarker) map.removeLayer(homeMarker);
		coverage = L.layerGroup().addTo(map);
		night = L.layerGroup().addTo(map);
		homeMarker = L.circleMarker([state.home.lat, state.home.lng], {radius: 6, color: '#ffffff', fillColor: '#2266ff', fillOpacity: 1})
			.bindTooltip(text.homeStation + ': ' + field('home').value.trim().toUpperCase()).addTo(map);
		drawDxMarker();
		for (const {point, bands: results} of predictions) {
			const {lat, lng} = point;
			const result = results[0];
			const margin = direction === 'rx' ? result.rxMargin : result.txMargin;
			const bounds = [[lat - 2.5, lng - 2.5], [lat + 2.5, lng + 2.5]];
			const rectangle = L.rectangle(bounds, {renderer, stroke: false, fillColor: model.signalColor(margin),
				fillOpacity: 0.45, interactive: false}).addTo(coverage);
			rectangle.propagationResult = result;
			if (model.solarCosine(point, time) < 0) {
				L.rectangle(bounds, {renderer, stroke: false, fillColor: '#000000', fillOpacity: 0.18, interactive: false}).addTo(night);
			}
		}
		homeMarker.bringToFront();
		if (dxMarker) dxMarker.bringToFront();
		renderedMapKey = key;
		} finally {
			if (version === mapGeneration) {
				element('map-loading').classList.add('d-none');
				element('map').setAttribute('aria-busy', 'false');
			}
		}
	}

	function drawDxMarker() {
		if (!map) return;
		if (dxMarker) { map.removeLayer(dxMarker); dxMarker = null; }
		if (!dx) return;
		const label = document.createElement('span');
		label.textContent = text.dxStation + ': ' + dx.target + ' / ' + dx.grid;
		dxMarker = L.circleMarker([dx.lat, dx.lng], {radius: 8, color: '#ffffff', weight: 2, fillColor: '#dc3545', fillOpacity: 1})
			.bindTooltip(label, {permanent: true, direction: 'top'}).addTo(map);
		dxMarker.bringToFront();
	}

	function clearResults() {
		state = null;
		dx = null;
		hours = [];
		worldGeneration++;
		mapGeneration++;
		renderedMapKey = null;
		element('map-loading').classList.add('d-none');
		element('map').setAttribute('aria-busy', 'false');
		drawDx();
		element('world-table').replaceChildren();
		element('hour-label').textContent = '';
		if (coverage) { map.removeLayer(coverage); coverage = null; }
		if (night) { map.removeLayer(night); night = null; }
		if (homeMarker) { map.removeLayer(homeMarker); homeMarker = null; }
		if (dxMarker) { map.removeLayer(dxMarker); dxMarker = null; }
		if (map) map.closePopup();
	}

	async function calculate(event) {
		if (event) event.preventDefault();
		const token = ++generation;
		if (request) request.abort();
		request = new AbortController();
		showError('');
		clearResults();
		element('submit').disabled = true;
		element('submit').textContent = text.loading;
		try {
			if (!config.dataReady) throw new Error(text.dataMissing);
			await loadSolar(request.signal);
			if (token !== generation) return;
			const next = readState();
			const target = field('target').value.trim();
			// World and coverage forecasts do not require a DX target.
			state = next;
			await Promise.all([drawWorld(), drawMap()]);
			if (token !== generation) return;
			saveSettings();
			if (target) {
				const url = new URL(config.lookupUrl, window.location.href);
				url.searchParams.set('target', target);
				const response = await fetch(url, {signal: request.signal, headers: {Accept: 'application/json'}});
				if (token !== generation) return;
				if (!(response.headers.get('content-type') || '').includes('application/json')) throw new Error(text.lookupError);
				const result = await response.json();
				if (token !== generation) return;
				if (!response.ok) throw new Error(result.error || text.lookupError);
				if (!Number.isFinite(result.lat) || !Number.isFinite(result.lng) || typeof result.grid !== 'string') throw new Error(text.lookupError);
				dx = result;
				drawDxMarker();
				await drawDx();
			}
		} catch (error) {
			if (error.name !== 'AbortError' && token === generation) {
				showError(error.message === 'Failed to fetch' ? text.lookupError : error.message || text.calculationError);
			}
		} finally {
			if (token === generation) {
				element('submit').disabled = false;
				element('submit').textContent = text.calculate;
			}
		}
	}

	for (const id of ['chart-band', 'map-band']) {
		for (const band of model.bands) element(id).add(new Option(band.name, band.name));
		element(id).value = '20m';
	}
	utcInput();
	try {
		const stored = JSON.parse(localStorage.getItem(config.settingsKey) || 'null');
		if (stored !== null) {
			if (typeof stored !== 'object' || Array.isArray(stored)) throw new Error('Invalid stored settings');
			for (const name of savedFields.filter(name => name !== 'ssn' || stored.ssnSource === 'manual')) {
				if (typeof stored[name] === 'string') field(name).value = stored[name];
			}
			element('solar-info').append(' ' + text.saved);
		}
	} catch (error) {
		storageProblem = true;
		showError('');
	}
	solarSource();
	form.addEventListener('submit', calculate);
	field('ssnSource').addEventListener('change', () => {
		solarSource();
		if (field('ssnSource').value === 'auto') form.requestSubmit();
		else {
			++generation;
			if (request) request.abort();
			clearResults();
			element('submit').disabled = false;
			element('submit').textContent = text.calculate;
			showError('');
		}
	});
	field('time').addEventListener('change', () => {
		if (field('ssnSource').value === 'auto') form.requestSubmit();
	});
	element('now').addEventListener('click', () => { utcInput(); form.requestSubmit(); });
	element('chart-band').addEventListener('change', drawChart);
	const handleMap = () => drawMap().catch(error => showError(error.message));
	element('map-band').addEventListener('change', handleMap);
	element('direction').addEventListener('change', handleMap);
	element('hour').addEventListener('input', () => {
		drawWorld().catch(error => showError(error.message));
		handleMap();
	});
	for (const name of ['dx', 'world', 'map']) {
		element('tab-' + name).addEventListener('shown.bs.tab', () => {
			history.replaceState(null, '', '#' + name);
			element('time-control').hidden = name === 'dx';
			if (state) solarInfo(name === 'dx' ? state.date : selectedTime());
			if (name === 'map') handleMap();
			if (name === 'dx' && chart) chart.resize();
		});
	}
	const tab = element('tab-' + window.location.hash.slice(1));
	if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
	if (config.dataReady && field('home').value.trim()) form.requestSubmit();
	else loadSolar(new AbortController().signal).catch(error => showError(error.message));
})();
