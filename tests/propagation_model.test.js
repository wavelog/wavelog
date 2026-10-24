'use strict';
const assert = require('node:assert/strict');
const {test, before} = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const model = require('../assets/js/sections/propagation_model.js');
const home = model.gridPoint('JN45');
const dx = model.gridPoint('FN31');
const date = new Date('2026-10-24T12:00:00Z');
const defaults = {power: 100, dxPower: 100, gain: 0, dxGain: 0, noise: 'rural', dxNoise: 'rural',
	ssn: 100, mode: 'SSB', path: 'short'};
const predict = (settings = {}) => model.predict(home, dx, date, {...defaults, ...settings});
const close = (actual, expected, tolerance = 1e-8) => assert.ok(Math.abs(actual - expected) < tolerance, `${actual} != ${expected}`);
let engine;
before(async () => {
	const createEngine = require('../assets/js/propagation/p533-engine.js');
	const originalWebAssembly = global.WebAssembly;
	try {
		global.WebAssembly = undefined;
		engine = await createEngine({print: () => {}, printErr: message => console.error(message)});
	} finally {
		global.WebAssembly = originalWebAssembly;
	}
	engine.FS.mkdir('/data');
	const manifest = require('../application/config/propagation-data.json');
	const dataPath = process.env.WAVELOG_PROPAGATION_DATA ||
		path.join(__dirname, '..', 'updates', 'propagation', manifest.revision);
	engine.FS.writeFile('/data/P1239-3 Decile Factors.txt', fs.readFileSync(path.join(dataPath, 'P1239-3 Decile Factors.txt')));
	model.configure(engine, async month => {
		const suffix = String(month).padStart(2, '0');
		for (const name of ['ionos' + suffix + '.bin', 'COEFF' + suffix + 'W.txt']) {
			engine.FS.writeFile('/data/' + name, fs.readFileSync(path.join(dataPath, name)));
		}
	});

	await model.prepare(date);
});

test('official JavaScript engine initializes without WebAssembly', () => {
	assert.equal(typeof engine._wl_predict, 'function');
	assert.equal(typeof engine._wl_init, 'function');
	assert.ok(engine.HEAPF64 instanceof Float64Array);
});

test('Maidenhead centres at every supported precision', () => {
	assert.deepEqual(model.gridPoint('JJ00'), {lat: 0.5, lng: 1});
	assert.deepEqual(model.gridPoint('jj00aa'), {lat: 1 / 48, lng: 1 / 24});
	close(model.gridPoint('JJ00AA00').lat, 1 / 480);
	close(model.gridPoint('JJ00AA00AA').lat, 1 / 11520);
	for (const grid of ['', 'JN', 'SS00', 'JN45YY', 'JN45AA0', 'JN45,JN46', '<script>']) {
		assert.throws(() => model.gridPoint(grid));
	}
});

test('solar geometry distinguishes midday, midnight and polar seasons', () => {
	assert.ok(model.solarCosine({lat: 0, lng: 0}, new Date('2026-03-20T12:00:00Z')) > 0.99);
	assert.ok(model.solarCosine({lat: 0, lng: 0}, new Date('2026-03-20T00:00:00Z')) < -0.99);
	assert.ok(model.solarCosine({lat: 90, lng: 0}, new Date('2026-06-21T00:00:00Z')) > 0);
	assert.ok(model.solarCosine({lat: 90, lng: 0}, new Date('2026-12-21T12:00:00Z')) < 0);
});

test('great circles are stable across the dateline, poles, identical points and antipodes', () => {
	for (const [a, b] of [
		[home, dx], [home, home], [{lat: 90, lng: 0}, {lat: -90, lng: 0}],
		[{lat: 0, lng: 179}, {lat: 0, lng: -179}], [{lat: 0, lng: 0}, {lat: 0, lng: 180}]
	]) {
		const short = model.path(a, b, false), long = model.path(a, b, true);
		close(short.distance + long.distance, 2 * Math.PI * 6371);
		close(short.distance, model.path(b, a, false).distance);
		for (const point of [...short.points, ...long.points]) {
			assert.ok(Number.isFinite(point.lat) && Math.abs(point.lat) <= 90);
			assert.ok(Number.isFinite(point.lng) && Math.abs(point.lng) <= 180);
		}
	}
	assert.ok(model.path({lat: 0, lng: 179}, {lat: 0, lng: -179}, false).distance < 223);
});

test('forecast returns exactly 24 UTC hours by 10 supported HF bands across a month boundary', async () => {
	const start = new Date('2026-12-31T23:30:00Z');
	const result = await model.forecast(home, dx, start, defaults);
	assert.equal(result.length, 24);
	assert.equal(result[23].time.toISOString(), '2027-01-01T22:30:00.000Z');
	for (const hour of result) {
		assert.deepEqual(hour.bands.map(band => band.band), model.bands.map(band => band.name));
		for (const band of hour.bands) {
			for (const key of ['tx', 'rx', 'txMargin', 'rxMargin']) assert.ok(band[key] === null || Number.isFinite(band[key]));
			for (const key of ['muf', 'distance']) assert.ok(Number.isFinite(band[key]));
		}
	}
	assert.equal(start.toISOString(), '2026-12-31T23:30:00.000Z');
	assert.equal(model.bands.length, 10);
	assert.equal(model.bands[0].name, '160m');
	await model.prepare(date);
});

test('tenfold home power adds exactly 10 dB to TX only', () => {
	const before = predict(), after = predict({power: 1000});
	before.forEach((band, i) => { close(after[i].tx - band.tx, 10); close(after[i].rx, band.rx); });
});

test('tenfold DX power adds exactly 10 dB to RX only', () => {
	const before = predict(), after = predict({dxPower: 1000});
	before.forEach((band, i) => { close(after[i].rx - band.rx, 10); close(after[i].tx, band.tx); });
});

test('antenna gains affect both directions and noise affects only reception at that end', () => {
	const before = predict(), gain = predict({gain: 6}), noise = predict({noise: 'city'}), dxNoise = predict({dxNoise: 'city'});
	before.forEach((band, i) => {
		close(gain[i].rx - band.rx, 6); close(gain[i].tx - band.tx, 6);
		assert.ok(noise[i].rx < band.rx); close(noise[i].tx, band.tx);
		assert.ok(dxNoise[i].tx < band.tx); close(dxNoise[i].rx, band.rx);
	});
});

test('FT8 margin is 26 dB greater than SSB with unchanged SNR', () => {
	const ssb = predict(), ft8 = predict({mode: 'FT8'});
	ssb.forEach((band, i) => {
		close(ft8[i].rx, band.rx); close(ft8[i].tx, band.tx);
		close(ft8[i].rxMargin - band.rxMargin, 26); close(ft8[i].txMargin - band.txMargin, 26);
	});
});

test('automatic path selection uses the best two-way margin per band', () => {
	const short = predict(), long = predict({path: 'long'}), auto = predict({path: 'auto'});
	short.forEach((band, i) => {
		close(Math.min(auto[i].rxMargin, auto[i].txMargin),
			Math.max(Math.min(band.rxMargin, band.txMargin), Math.min(long[i].rxMargin, long[i].txMargin)));
	});
	assert.ok(long.every(band => band.longPath));
	assert.ok(predict().every(band => !band.longPath));
});

test('smoothed solar activity changes reference MUF without an invented K correction', () => {
	const low = predict({ssn: 20}), high = predict({ssn: 140});
	assert.ok(high[0].muf > low[0].muf);
});

test('automatic solar input selects each forecast month and never substitutes defaults', async () => {
	const settings = {...defaults, solarMonths: {'2026-12': 80, '2027-01': 120}};
	const start = new Date('2026-12-31T23:00:00Z');
	const result = await model.forecast(home, dx, start, settings);
	await model.prepare(start);
	const december = model.predict(home, dx, start, {...defaults, ssn: 80});
	close(result[0].bands[4].muf, december[4].muf);
	const next = new Date('2027-01-01T00:00:00Z');
	await model.prepare(next);
	const january = model.predict(home, dx, next, {...defaults, ssn: 120});
	close(result[1].bands[4].muf, january[4].muf);
	for (const value of [undefined, 0, 312, 10.5, '100']) {
		assert.throws(() => model.settingsAt({...defaults, solarMonths: {'2027-01': value}}, next));
	}
	assert.equal(model.settingsAt(defaults, next), defaults);
	assert.equal(settings.ssn, 100);
	await model.prepare(date);
});

test('quality classification has exact 0 dB and 10 dB boundaries', () => {
	assert.equal(model.quality(-0.001), 'unlikely');
	assert.equal(model.quality(0), 'marginal');
	assert.equal(model.quality(9.999), 'marginal');
	assert.equal(model.quality(10), 'good');
});

test('signal gradient interpolates continuously and saturates at +/-20 dB', () => {
	assert.equal(model.signalColor(-100), 'rgb(248, 113, 113)');
	assert.equal(model.signalColor(-20), 'rgb(248, 113, 113)');
	assert.equal(model.signalColor(-10), 'rgb(249, 159, 67)');
	assert.equal(model.signalColor(0), 'rgb(250, 204, 21)');
	assert.equal(model.signalColor(10), 'rgb(162, 213, 75)');
	assert.equal(model.signalColor(20), 'rgb(74, 222, 128)');
	assert.equal(model.signalColor(100), 'rgb(74, 222, 128)');
	assert.notEqual(model.signalColor(4), model.signalColor(5));
	for (const margin of [NaN, Infinity, undefined]) assert.throws(() => model.signalColor(margin));
});

test('invalid settings, dates and coordinates are rejected explicitly', () => {
	for (const settings of [{power: 0}, {power: NaN}, {dxPower: Infinity}, {gain: 31}, {dxGain: -21},
		{ssn: 0}, {ssn: 312}, {ssn: 1.5}, {mode: 'AM'}, {mode: 'toString'}, {noise: '__proto__'}, {dxNoise: 'unknown'}, {path: 'invalid'}]) {
		assert.throws(() => predict(settings));
	}
	assert.throws(() => model.predict(home, dx, new Date('invalid'), defaults));
	assert.throws(() => model.validate(defaults, new Date('2101-01-01T00:00:00Z')));
	assert.throws(() => model.validate(defaults, new Date('1899-12-31T23:00:00Z')));
	assert.throws(() => model.predict({lat: 91, lng: 0}, dx, date, defaults));
	assert.throws(() => model.predict(home, {lat: 0, lng: NaN}, date, defaults));
});

test('reference predictions are finite or explicitly unsupported for polar and dateline paths', () => {
	for (const settings of [
		{...defaults, power: 1, dxPower: 5000, ssn: 1, path: 'auto'},
		{...defaults, power: 5000, dxPower: 1, ssn: 311, path: 'long'}
	]) {
		for (const lat of [-87.5, 0, 87.5]) {
			for (const lng of [-177.5, 0, 177.5]) {
				for (const band of model.predict(home, {lat, lng}, date, settings)) {
					assert.ok(band.rx === null || Number.isFinite(band.rx));
					assert.ok(band.tx === null || Number.isFinite(band.tx));
					assert.ok(Number.isFinite(band.muf) && Number.isFinite(band.distance));
				}
			}
		}
	}
});

test('no-supported-mode results remain explicit rather than fabricated signal levels', () => {
	const realPredict = engine._wl_predict;
	try {
		engine._wl_predict = (...args) => {
			realPredict(...args);
			const offset = engine._wl_result() / 8;
			engine.HEAPF64.fill(NaN, offset, offset + 3);
			return 3004;
		};
		for (const band of predict({path: 'auto'})) {
			assert.equal(band.rx, null);
			assert.equal(band.tx, null);
			assert.equal(band.rxMargin, null);
			assert.equal(band.txMargin, null);
			assert.equal(model.twoWayMargin(band), -Infinity);
		}
		assert.equal(model.quality(null), 'noMode');
		assert.equal(model.signalColor(null), model.signalColor(-20));
	} finally {
		engine._wl_predict = realPredict;
	}
});
