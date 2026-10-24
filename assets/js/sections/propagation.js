(function () {
	'use strict';
	const form = document.getElementById('prop-form');
	if (!form) return;
	const model = window.PropagationModel;
	const config = propagationConfig;
	const text = config.text;
	const element = id => document.getElementById('prop-' + id);
	const field = name => form.elements.namedItem(name);
	const numeric = ['power', 'dxPower', 'gain', 'dxGain', 'sfi', 'k'];
	const savedFields = ['home', 'target', 'mode', 'path', 'noise', 'dxNoise', ...numeric];
	let state = null, dx = null, hours = [], chart = null, map = null, generation = 0;
	let coverage = null, night = null, homeMarker = null, request = null, renderer = null;
	let storageProblem = false;

	function showError(message) {
		const messages = [message, storageProblem ? text.storageError : ''].filter(Boolean);
		element('error').textContent = messages.join(' ');
		element('error').classList.toggle('d-none', !messages.length);
	}

	function utcInput() {
		field('time').value = new Date().toISOString().slice(0, 16);
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
		try {
			model.predict(home, home, date, settings);
		} catch (error) {
			throw new Error(text.calculationError);
		}
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
		return Math.round(result.rxMargin) + ' / ' + Math.round(result.txMargin) + (result.longPath ? ' LP' : '');
	}

	function resultCell(row, result, onClick) {
		const margin = Math.min(result.rxMargin, result.txMargin);
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

	function drawDx() {
		element('dx-table').replaceChildren();
		if (chart) { chart.destroy(); chart = null; }
		if (!dx || !state) {
			element('dx-info').textContent = text.empty;
			return;
		}
		element('dx-info').textContent = dx.target + ' / ' + dx.grid + ' (' + dx.source + ')';
		hours = model.forecast(state.home, dx, state.date, state.settings);
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

	function drawWorld() {
		if (!state) return;
		const time = selectedTime();
		element('hour-label').textContent = timeLabel(time);
		const body = newTable(element('world-table'), [text.region, ...model.bands.map(band => band.name)], timeLabel(time) + ' (RX / TX dB)');
		for (const [name, lat, lng] of config.regions) {
			const results = model.predict(state.home, {lat, lng}, time, state.settings);
			const row = body.insertRow();
			cell(row, 'th', name).scope = 'row';
			results.forEach(result => resultCell(row, result, () => {
				element('map-band').value = result.band;
				bootstrap.Tab.getOrCreateInstance(element('tab-map')).show();
			}));
		}
	}

	function drawMap() {
		if (!state || !element('pane-map').classList.contains('active')) return;
		if (!map) {
			map = L.map(element('map'), {worldCopyJump: true}).setView([20, 0], 2);
			renderer = L.canvas({padding: 0.5});
			L.tileLayer(option_map_tile_server, {
				attribution: option_map_tile_server_copyright, subdomains: option_map_tile_subdomains
			}).addTo(map);
			map.on('click', event => {
				if (!state) return;
				const point = {lat: event.latlng.lat, lng: ((event.latlng.lng + 540) % 360) - 180};
				if (Math.abs(point.lat) > 90) return;
				const index = model.bands.findIndex(band => band.name === element('map-band').value);
				const result = model.predict(state.home, point, selectedTime(), state.settings)[index];
				const popup = document.createElement('span');
				popup.textContent = point.lat.toFixed(2) + ', ' + point.lng.toFixed(2) + ' | ' + result.band + ' | RX / TX: ' + marginLabel(result) + ' dB';
				L.popup().setLatLng(event.latlng).setContent(popup).openOn(map);
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
		for (let lat = -87.5; lat < 90; lat += 5) {
			for (let lng = -177.5; lng < 180; lng += 5) {
				const point = {lat, lng};
				const result = model.predict(state.home, point, time, state.settings)[index];
				const margin = direction === 'rx' ? result.rxMargin : result.txMargin;
				const bounds = [[lat - 2.5, lng - 2.5], [lat + 2.5, lng + 2.5]];
				L.rectangle(bounds, {renderer, stroke: false, fillColor: model.signalColor(margin),
					fillOpacity: 0.45, interactive: false}).addTo(coverage);
				if (model.solarCosine(point, time) < 0) {
					L.rectangle(bounds, {renderer, stroke: false, fillColor: '#000000', fillOpacity: 0.18, interactive: false}).addTo(night);
				}
			}
		}
		homeMarker.bringToFront();
	}

	function clearResults() {
		state = null;
		dx = null;
		hours = [];
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
			const next = readState();
			const target = field('target').value.trim();
			// World and coverage forecasts do not require a DX target.
			state = next;
			drawWorld();
			drawMap();
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
				drawDx();
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
	const solar = config.solar;
	const now = Date.now() / 1000;
	const recent = [solar.modified, solar.observed].every(value => Number.isFinite(value) && now - value >= -3600 && now - value < 6 * 3600);
	const usable = Number.isFinite(solar.sfi) && solar.sfi >= 50 && solar.sfi <= 400 &&
		Number.isFinite(solar.k) && solar.k >= 0 && solar.k <= 9;
	element('solar-warning').classList.toggle('d-none', recent && usable);
	if (usable) {
		field('sfi').value = solar.sfi;
		field('k').value = solar.k;
		element('solar-info').textContent = text.solar + ': ' + solar.updated;
	}
	try {
		const stored = JSON.parse(localStorage.getItem(config.settingsKey) || 'null');
		if (stored !== null) {
			if (typeof stored !== 'object' || Array.isArray(stored)) throw new Error('Invalid stored settings');
			for (const name of savedFields) {
				// Fresh cached solar indices take precedence over yesterday's assumptions.
				if ((name === 'sfi' || name === 'k') && recent && usable) continue;
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
	element('map-band').addEventListener('change', drawMap);
	element('direction').addEventListener('change', drawMap);
	element('hour').addEventListener('input', () => { drawWorld(); drawMap(); });
	for (const name of ['dx', 'world', 'map']) {
		element('tab-' + name).addEventListener('shown.bs.tab', () => {
			history.replaceState(null, '', '#' + name);
			element('time-control').hidden = name === 'dx';
			if (name === 'map') drawMap();
			if (name === 'dx' && chart) chart.resize();
		});
	}
	const tab = element('tab-' + window.location.hash.slice(1));
	if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
	if (field('home').value.trim()) form.requestSubmit();
})();
