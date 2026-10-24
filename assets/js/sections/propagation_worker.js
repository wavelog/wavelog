'use strict';
let ready = null;
const latest = {};
const jobs = [];
let running = false;

async function initialize(config) {
	importScripts(config.modelUrl);
	importScripts(config.engineUrl);
	const engine = await createP533Engine({print: () => {}, printErr: message => console.error(message)});
	engine.FS.mkdir('/data');
	let currentMonth = null;
	async function writeFile(name) {
		const controller = new AbortController();
		const timeout = setTimeout(() => controller.abort(), 120000);
		try {
			const url = new URL(config.dataUrl, self.location.href);
			url.searchParams.set('file', name);
			const response = await fetch(url, {signal: controller.signal});
			if (!response.ok) throw new Error('Reference data download failed: ' + name + ' (HTTP ' + response.status + ')');
			const bytes = new Uint8Array(await response.arrayBuffer());
			if (!bytes.length || (name.endsWith('.bin') && bytes.length !== 11197844)) {
				throw new Error('Reference data has an invalid size: ' + name);
			}
			engine.FS.writeFile('/data/' + name, bytes);
		} finally {
			clearTimeout(timeout);
		}
	}
	await writeFile('P1239-3 Decile Factors.txt');
	PropagationModel.configure(engine, async month => {
		const suffix = String(month).padStart(2, '0');
		if (currentMonth !== null) {
			const old = String(currentMonth).padStart(2, '0');
			engine.FS.unlink('/data/ionos' + old + '.bin');
			engine.FS.unlink('/data/COEFF' + old + 'W.txt');
			currentMonth = null;
		}
		await Promise.all([writeFile('ionos' + suffix + '.bin'), writeFile('COEFF' + suffix + 'W.txt')]);
		currentMonth = month;
	});
}

async function execute(job) {
	const {id, type, payload} = job;
	const {home} = payload;
	const date = new Date(payload.date);
	const settings = PropagationModel.settingsAt(payload.settings, date);
	PropagationModel.validate(settings, date);
	await PropagationModel.prepare(date);
	if (type === 'forecast') return PropagationModel.forecast(home, payload.dx, date, settings);
	if (type === 'point') return PropagationModel.predict(home, payload.point, date, settings, [payload.band])[0];
	const points = type === 'world' ? payload.points : [];
	if (type === 'map') {
		for (let lat = -87.5; lat < 90; lat += 5) {
			for (let lng = -177.5; lng < 180; lng += 5) points.push({lat, lng});
		}
	}
	const results = [];
	if (type === 'map') postMessage({id, progress: 0});
	for (let i = 0; i < points.length; i++) {
		if (latest[type] !== id) return null;
		const point = points[i];
		const bands = type === 'map' ? [payload.band] : PropagationModel.bands;
		results.push({point, bands: PropagationModel.predict(home, point, date, settings, bands)});
		if (i % 8 === 7) {
			if (type === 'map') postMessage({id, progress: Math.round((i + 1) / points.length * 100)});
			await new Promise(resolve => setTimeout(resolve, 0));
		}
	}
	return results;
}

async function drain() {
	if (running) return;
	running = true;
	try {
		while (jobs.length) {
			const job = jobs.shift();
			if (latest[job.type] !== job.id) continue;
			try {
				await ready;
				const result = await execute(job);
				if (latest[job.type] === job.id) postMessage({id: job.id, result});
			} catch (error) {
				postMessage({id: job.id, error: error.message});
			}
		}
	} finally {
		running = false;
	}
}

self.onmessage = event => {
	if (event.data.type === 'initialize') {
		ready = initialize(event.data.config);
		ready.catch(error => postMessage({id: 0, error: error.message}));
		return;
	}
	latest[event.data.type] = event.data.id;
	jobs.push(event.data);
	drain();
};
