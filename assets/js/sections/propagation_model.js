(function (root) {
	'use strict';
	const radians = Math.PI / 180;
	const radius = 6371;
	const bands = [
		['160m', 1.85], ['80m', 3.65], ['60m', 5.35], ['40m', 7.1], ['30m', 10.12],
		['20m', 14.15], ['17m', 18.1], ['15m', 21.2], ['12m', 24.94], ['10m', 28.4]
	].map(([name, frequency]) => ({name, frequency}));
	const thresholds = {SSB: 6, CW: -10, FT8: -20, FT4: -17, RTTY: -5};
	const noiseCategories = {quiet: 3, rural: 2, residential: 1, city: 0};
	const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
	let engine = null, loadMonth = null, loadedMonth = null;

	function configure(module, loader) {
		engine = module;
		loadMonth = loader;
		loadedMonth = null;
	}

	async function prepare(date) {
		if (!engine || !loadMonth) throw new Error('P.533 engine is not initialized');
		const month = date.getUTCMonth() + 1;
		if (month !== loadedMonth) {
			await loadMonth(month);
			const code = engine._wl_init(month);
			if (code !== 0) throw new Error('P.533 initialization error ' + code);
			loadedMonth = month;
		}
	}

	function coordinates(point) {
		if (!point || !Number.isFinite(point.lat) || !Number.isFinite(point.lng) ||
			Math.abs(point.lat) > 90 || Math.abs(point.lng) > 180) {
			throw new Error('Invalid coordinates');
		}
	}

	function gridPoint(grid) {
		if (typeof grid !== 'string' || !/^[A-R]{2}[0-9]{2}(?:[A-X]{2}(?:[0-9]{2}(?:[A-X]{2})?)?)?$/i.test(grid)) {
			throw new Error('Invalid Maidenhead locator');
		}
		grid = grid.toUpperCase();
		let lng = -180, lat = -90, width = 20, height = 10;
		for (let i = 0; i < grid.length; i += 2) {
			const letters = i === 0 || i === 4 || i === 8;
			if (i > 0) {
				width /= letters ? 24 : 10;
				height /= letters ? 24 : 10;
			}
			lng += (letters ? grid.charCodeAt(i) - 65 : Number(grid[i])) * width;
			lat += (letters ? grid.charCodeAt(i + 1) - 65 : Number(grid[i + 1])) * height;
		}
		return {lat: lat + height / 2, lng: lng + width / 2};
	}

	function solarCosine(point, date) {
		const day = (date.getTime() - Date.UTC(date.getUTCFullYear(), 0, 1)) / 86400000;
		const gamma = 2 * Math.PI / 365.2422 * day;
		const declination = 0.006918 - 0.399912 * Math.cos(gamma) + 0.070257 * Math.sin(gamma)
			- 0.006758 * Math.cos(2 * gamma) + 0.000907 * Math.sin(2 * gamma)
			- 0.002697 * Math.cos(3 * gamma) + 0.00148 * Math.sin(3 * gamma);
		const equation = 229.18 * (0.000075 + 0.001868 * Math.cos(gamma) - 0.032077 * Math.sin(gamma)
			- 0.014615 * Math.cos(2 * gamma) - 0.040849 * Math.sin(2 * gamma));
		const minutes = date.getUTCHours() * 60 + date.getUTCMinutes() + date.getUTCSeconds() / 60;
		const angle = (minutes / 4 + equation / 4 + point.lng - 180) * radians;
		const latitude = point.lat * radians;
		return Math.sin(latitude) * Math.sin(declination) + Math.cos(latitude) * Math.cos(declination) * Math.cos(angle);
	}

	function path(from, to, longPath) {
		coordinates(from);
		coordinates(to);
		const a = from.lat * radians, b = to.lat * radians, delta = (to.lng - from.lng) * radians;
		const haversine = Math.sin((b - a) / 2) ** 2 + Math.cos(a) * Math.cos(b) * Math.sin(delta / 2) ** 2;
		const shortAngle = 2 * Math.asin(Math.sqrt(clamp(haversine, 0, 1)));
		const angle = longPath ? 2 * Math.PI - shortAngle : shortAngle;
		const bearing = Math.atan2(Math.sin(delta) * Math.cos(b),
			Math.cos(a) * Math.sin(b) - Math.sin(a) * Math.cos(b) * Math.cos(delta)) + (longPath ? Math.PI : 0);
		const distance = angle * radius;
		const hops = Math.max(1, Math.ceil(distance / 3000));
		const points = [];
		for (let i = 0; i < hops; i++) {
			const step = angle * (i + 0.5) / hops;
			const lat = Math.asin(clamp(Math.sin(a) * Math.cos(step) + Math.cos(a) * Math.sin(step) * Math.cos(bearing), -1, 1));
			const lng = from.lng * radians + Math.atan2(Math.sin(bearing) * Math.sin(step) * Math.cos(a),
				Math.cos(step) - Math.sin(a) * Math.sin(lat));
			points.push({lat: lat / radians, lng: ((lng / radians + 540) % 360) - 180});
		}
		return {distance, hops, points};
	}

	function validate(settings, date) {
		if (!(date instanceof Date) || !Number.isFinite(date.getTime())) throw new Error('Invalid UTC date');
		if (date.getUTCFullYear() < 1900 || date.getUTCFullYear() > 2100) throw new Error('Unsupported prediction year');
		const ranges = {power: [1, 5000], dxPower: [1, 5000], gain: [-20, 30], dxGain: [-20, 30], ssn: [1, 311]};
		for (const [key, [min, max]] of Object.entries(ranges)) {
			if (!Number.isFinite(settings[key]) || settings[key] < min || settings[key] > max) {
				throw new Error('Invalid setting: ' + key);
			}
			if (!Number.isInteger(settings.ssn)) throw new Error('Sunspot number must be an integer');
		}
		if (!Object.prototype.hasOwnProperty.call(thresholds, settings.mode) ||
			!Object.prototype.hasOwnProperty.call(noiseCategories, settings.noise) ||
			!Object.prototype.hasOwnProperty.call(noiseCategories, settings.dxNoise) ||
			!['short', 'long', 'auto'].includes(settings.path)) throw new Error('Invalid mode, noise or path');
	}

	function settingsAt(settings, date) {
		if (!settings.solarMonths) return settings;
		const month = date.toISOString().slice(0, 7);
		const ssn = settings.solarMonths[month];
		if (!Number.isInteger(ssn) || ssn < 1 || ssn > 311) {
			throw new Error('No supported smoothed sunspot value for ' + month);
		}
		return {...settings, ssn};
	}

	function reference(from, to, date, settings, band, longPath, noise) {
		if (!engine || loadedMonth !== date.getUTCMonth() + 1) throw new Error('P.533 monthly data is not loaded');
		const code = engine._wl_predict(from.lat, from.lng, to.lat, to.lng, date.getUTCFullYear(),
			date.getUTCMonth() + 1, date.getUTCHours(), settings.ssn, band.frequency, longPath ? 1 : 0, noiseCategories[noise]);
		if (code !== 0 && code !== 3004) throw new Error('P.533 prediction error ' + code);
		const offset = engine._wl_result() / 8;
		const result = Array.from(engine.HEAPF64.subarray(offset, offset + 5));
		if (code === 3004 && Number.isFinite(result[3]) && Number.isFinite(result[4])) {
			return {snr: null, muf: result[3], distance: result[4]};
		}
		if (result.some(value => !Number.isFinite(value))) throw new Error('P.533 returned a non-finite result');
		return {power: result[0], noise: result[1], snr: result[2], muf: result[3], distance: result[4]};
	}

	function predictPath(from, to, date, settings, longPath, selectedBands) {
		return selectedBands.map(band => {
			const outgoing = reference(from, to, date, settings, band, longPath, settings.dxNoise);
			const incoming = reference(to, from, date, settings, band, longPath, settings.noise);
			const gain = settings.gain + settings.dxGain;
			const tx = outgoing.snr === null ? null : outgoing.snr + 10 * Math.log10(settings.power / 100) + gain;
			const rx = incoming.snr === null ? null : incoming.snr + 10 * Math.log10(settings.dxPower / 100) + gain;
			return {band: band.name, frequency: band.frequency, tx, rx, txMargin: tx === null ? null : tx - thresholds[settings.mode],
				rxMargin: rx === null ? null : rx - thresholds[settings.mode], muf: outgoing.muf, distance: outgoing.distance, longPath};
		});
	}

	function predict(from, to, date, settings, selectedBands = bands) {
		settings = settingsAt(settings, date);
		validate(settings, date);
		coordinates(from);
		coordinates(to);
		if (settings.path === 'long') return predictPath(from, to, date, settings, true, selectedBands);
		const short = predictPath(from, to, date, settings, false, selectedBands);
		if (settings.path !== 'auto' || short[0].distance < 6000) return short;
		const long = predictPath(from, to, date, settings, true, selectedBands);
		return short.map((result, i) => twoWayMargin(long[i]) > twoWayMargin(result) ? long[i] : result);
	}

	function twoWayMargin(result) {
		return result.txMargin === null || result.rxMargin === null ? -Infinity : Math.min(result.txMargin, result.rxMargin);
	}

	async function forecast(from, to, date, settings) {
		validate(settings, date);
		const result = [];
		for (let hour = 0; hour < 24; hour++) {
			const time = new Date(date.getTime() + hour * 3600000);
			await prepare(time);
			result.push({time, bands: predict(from, to, time, settings)});
		}
		return result;
	}

	function quality(margin) {
		if (margin === null) return 'noMode';
		return margin >= 10 ? 'good' : margin >= 0 ? 'marginal' : 'unlikely';
	}

	function signalColor(margin) {
		if (margin === null) return 'rgb(248, 113, 113)';
		if (!Number.isFinite(margin)) throw new Error('Invalid signal margin');
		const red = [248, 113, 113], yellow = [250, 204, 21], green = [74, 222, 128];
		const start = margin < 0 ? red : yellow;
		const end = margin < 0 ? yellow : green;
		const fraction = margin < 0 ? (clamp(margin, -20, 0) + 20) / 20 : clamp(margin, 0, 20) / 20;
		return 'rgb(' + start.map((value, i) => Math.round(value + (end[i] - value) * fraction)).join(', ') + ')';
	}

	const api = {bands, thresholds, gridPoint, solarCosine, path, validate, configure, prepare, predict, forecast, quality, signalColor, twoWayMargin, settingsAt};
	if (typeof module === 'object' && module.exports) module.exports = api;
	else root.PropagationModel = api;
})(typeof window !== 'undefined' ? window : globalThis);
