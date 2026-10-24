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
	const savedFields = ['home', 'target', 'mode', 'path', 'noise', 'dxNoise', ...numeric];
	let state = null, dx = null, hours = [], chart = null, map = null, generation = 0;
	let coverage = null, night = null, homeMarker = null, request = null, renderer = null;
	let storageProblem = false;
	let worker = null, nextId = 0, mapGeneration = 0, worldGeneration = 0;
	const pending = new Map();

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
		return new Date(state.date.getTime() + Number(element('hour').value) * 3600000);
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
		const version = ++mapGeneration;
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
		if (coverage) map.removeLayer(coverage);
		if (night) map.removeLayer(night);
		if (homeMarker) map.removeLayer(homeMarker);
		coverage = L.layerGroup().addTo(map);
		night = L.layerGroup().addTo(map);
		homeMarker = L.circleMarker([state.home.lat, state.home.lng], {radius: 6, color: '#ffffff', fillColor: '#2266ff', fillOpacity: 1})
			.bindTooltip(field('home').value.trim().toUpperCase()).addTo(map);
		const index = model.bands.findIndex(band => band.name === element('map-band').value);
		const direction = element('direction').value;
		const time = selectedTime();
		const current = state;
		const predictions = await engineRequest('map', {...current, date: time, band: model.bands[index]});
		if (!predictions || version !== mapGeneration || current !== state) return;
		for (const {point, bands: results} of predictions) {
			const {lat, lng} = point;
			const result = results[0];
			const margin = direction === 'rx' ? result.rxMargin : result.txMargin;
			const bounds = [[lat - 2.5, lng - 2.5], [lat + 2.5, lng + 2.5]];
			L.rectangle(bounds, {renderer, stroke: false, fillColor: model.signalColor(margin),
				fillOpacity: 0.45, interactive: false}).addTo(coverage);
			if (model.solarCosine(point, time) < 0) {
				L.rectangle(bounds, {renderer, stroke: false, fillColor: '#000000', fillOpacity: 0.18, interactive: false}).addTo(night);
			}
		}
		homeMarker.bringToFront();
	}

	function clearResults() {
		state = null;
		dx = null;
		hours = [];
		worldGeneration++;
		mapGeneration++;
		drawDx();
		element('world-table').replaceChildren();
		element('hour-label').textContent = '';
		if (coverage) { map.removeLayer(coverage); coverage = null; }
		if (night) { map.removeLayer(night); night = null; }
		if (homeMarker) { map.removeLayer(homeMarker); homeMarker = null; }
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
			for (const name of savedFields) {
				if (typeof stored[name] === 'string') field(name).value = stored[name];
			}
			element('solar-info').append(' ' + text.saved);
		}
	} catch (error) {
		storageProblem = true;
		showError('');
	}
	form.addEventListener('submit', calculate);
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
			if (name === 'map') handleMap();
			if (name === 'dx' && chart) chart.resize();
		});
	}
	const tab = element('tab-' + window.location.hash.slice(1));
	if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
	if (config.dataReady && field('home').value.trim()) form.requestSubmit();
})();
