'use strict';
const assert = require('node:assert/strict');
const {test} = require('node:test');
const model = require('../assets/js/sections/propagation_model.js');
const home = model.gridPoint('JN45');
const dx = model.gridPoint('FN31');
const date = new Date('2026-10-24T12:00:00Z');
const defaults = {power: 100, dxPower: 100, gain: 0, dxGain: 0, noise: 'rural', dxNoise: 'rural',
	sfi: 140, k: 2, mode: 'SSB', path: 'short'};
const predict = (settings = {}) => model.predict(home, dx, date, {...defaults, ...settings});
const close = (actual, expected, tolerance = 1e-8) => assert.ok(Math.abs(actual - expected) < tolerance, `${actual} != ${expected}`);

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

test('forecast returns exactly 24 UTC hours by 10 HF bands with finite values', () => {
	const start = new Date('2026-12-31T23:30:00Z');
	const result = model.forecast(home, dx, start, defaults);
	assert.equal(result.length, 24);
	assert.equal(result[23].time.toISOString(), '2027-01-01T22:30:00.000Z');
	for (const hour of result) {
		assert.deepEqual(hour.bands.map(band => band.band), model.bands.map(band => band.name));
		for (const band of hour.bands) {
			for (const key of ['tx', 'rx', 'txMargin', 'rxMargin', 'muf', 'distance']) assert.ok(Number.isFinite(band[key]));
		}
	}
	assert.equal(start.toISOString(), '2026-12-31T23:30:00.000Z');
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
		close(noise[i].rx - band.rx, -20); close(noise[i].tx, band.tx);
		close(dxNoise[i].tx - band.tx, -20); close(dxNoise[i].rx, band.rx);
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

test('solar flux raises MUF and disturbed K lowers MUF', () => {
	const low = predict({sfi: 70}), high = predict({sfi: 220}), disturbed = predict({sfi: 220, k: 8});
	assert.ok(high[0].muf > low[0].muf);
	assert.ok(disturbed[0].muf < high[0].muf);
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
		{sfi: 49}, {k: 10}, {mode: 'AM'}, {mode: 'toString'}, {noise: '__proto__'}, {dxNoise: 'unknown'}, {path: 'invalid'}]) {
		assert.throws(() => predict(settings));
	}
	assert.throws(() => model.predict(home, dx, new Date('invalid'), defaults));
	assert.throws(() => model.predict({lat: 91, lng: 0}, dx, date, defaults));
	assert.throws(() => model.predict(home, {lat: 0, lng: NaN}, date, defaults));
});

test('coverage grid and all regions remain finite at extreme supported settings', () => {
	for (const settings of [
		{...defaults, power: 1, dxPower: 5000, sfi: 50, k: 9, path: 'auto'},
		{...defaults, power: 5000, dxPower: 1, sfi: 400, k: 0, path: 'long'}
	]) {
		for (let lat = -87.5; lat < 90; lat += 5) {
			for (let lng = -177.5; lng < 180; lng += 5) {
				for (const band of model.predict(home, {lat, lng}, date, settings)) {
					assert.ok(Number.isFinite(band.rx) && Number.isFinite(band.tx) && band.muf > 0);
				}
			}
		}
	}
});
