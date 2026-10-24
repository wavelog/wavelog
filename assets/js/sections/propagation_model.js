(function (root) {
	'use strict';
	const radians = Math.PI / 180;
	const radius = 6371;
	const bands = [
		['160m', 1.85], ['80m', 3.65], ['60m', 5.35], ['40m', 7.1], ['30m', 10.12],
		['20m', 14.15], ['17m', 18.1], ['15m', 21.2], ['12m', 24.94], ['10m', 28.4]
	].map(([name, frequency]) => ({name, frequency}));
	const thresholds = {SSB: 6, CW: -10, FT8: -20, FT4: -17, RTTY: -5};
	const noiseLevels = {quiet: -125, rural: -118, residential: -108, city: -98};
	const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

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
		const ranges = {power: [1, 5000], dxPower: [1, 5000], gain: [-20, 30], dxGain: [-20, 30], sfi: [50, 400], k: [0, 9]};
		for (const [key, [min, max]] of Object.entries(ranges)) {
			if (!Number.isFinite(settings[key]) || settings[key] < min || settings[key] > max) {
				throw new Error('Invalid setting: ' + key);
			}
		}
		if (!Object.prototype.hasOwnProperty.call(thresholds, settings.mode) ||
			!Object.prototype.hasOwnProperty.call(noiseLevels, settings.noise) ||
			!Object.prototype.hasOwnProperty.call(noiseLevels, settings.dxNoise) ||
			!['short', 'long', 'auto'].includes(settings.path)) throw new Error('Invalid mode, noise or path');
	}

	function predictPath(from, to, date, settings, longPath) {
		const route = path(from, to, longPath);
		const hopLength = route.distance / route.hops;
		// Secant-law geometry at a virtual F-layer height of 300 km.
		const halfAngle = hopLength / (2 * radius);
		const incidence = Math.atan2(radius * Math.sin(halfAngle), radius + 300 - radius * Math.cos(halfAngle));
		const secant = 1 / Math.cos(incidence);
		let muf = Infinity, daylight = 0, polar = 0;
		const lagged = new Date(date.getTime() - 3600000);
		for (const point of route.points) {
			const sun = Math.max(0, solarCosine(point, lagged));
			const activity = Math.max(0, settings.sfi - 65);
			const critical = (2.5 + activity * 0.012 + (3 + activity * 0.022) * Math.sqrt(sun))
				* (1 - settings.k * 0.035);
			muf = Math.min(muf, critical * secant);
			daylight += Math.max(0, solarCosine(point, date));
			polar = Math.max(polar, clamp((Math.abs(point.lat) - 50) / 25, 0, 1));
		}
		return bands.map(band => {
			const f = band.frequency;
			const aboveMuf = 130 * Math.max(0, f / muf - 1) ** 2;
			const absorption = daylight * 8 * (10 / f) ** 2;
			const slant = Math.max(600, Math.hypot(hopLength, 600) * route.hops);
			const loss = 32.45 + 20 * Math.log10(f) + 20 * Math.log10(slant)
				+ 20 + 7 * route.hops + absorption + aboveMuf + settings.k * polar * 2;
			const common = settings.gain + settings.dxGain - loss;
			// All SNRs and mode thresholds use a common 2.5 kHz reference bandwidth.
			const noiseCorrection = 20 * Math.log10(14 / f);
			const tx = 30 + 10 * Math.log10(settings.power) + common - (noiseLevels[settings.dxNoise] + noiseCorrection);
			const rx = 30 + 10 * Math.log10(settings.dxPower) + common - (noiseLevels[settings.noise] + noiseCorrection);
			return {band: band.name, frequency: f, tx, rx, txMargin: tx - thresholds[settings.mode],
				rxMargin: rx - thresholds[settings.mode], muf, distance: route.distance, longPath};
		});
	}

	function predict(from, to, date, settings) {
		validate(settings, date);
		if (settings.path === 'long') return predictPath(from, to, date, settings, true);
		const short = predictPath(from, to, date, settings, false);
		if (settings.path !== 'auto' || short[0].distance < 6000) return short;
		const long = predictPath(from, to, date, settings, true);
		return short.map((result, i) => Math.min(long[i].txMargin, long[i].rxMargin) >
			Math.min(result.txMargin, result.rxMargin) ? long[i] : result);
	}

	function forecast(from, to, date, settings) {
		validate(settings, date);
		return Array.from({length: 24}, (_, hour) => {
			const time = new Date(date.getTime() + hour * 3600000);
			return {time, bands: predict(from, to, time, settings)};
		});
	}

	function quality(margin) {
		return margin >= 10 ? 'good' : margin >= 0 ? 'marginal' : 'unlikely';
	}

	function signalColor(margin) {
		if (!Number.isFinite(margin)) throw new Error('Invalid signal margin');
		const red = [248, 113, 113], yellow = [250, 204, 21], green = [74, 222, 128];
		const start = margin < 0 ? red : yellow;
		const end = margin < 0 ? yellow : green;
		const fraction = margin < 0 ? (clamp(margin, -20, 0) + 20) / 20 : clamp(margin, 0, 20) / 20;
		return 'rgb(' + start.map((value, i) => Math.round(value + (end[i] - value) * fraction)).join(', ') + ')';
	}

	const api = {bands, thresholds, gridPoint, solarCosine, path, predict, forecast, quality, signalColor};
	if (typeof module === 'object' && module.exports) module.exports = api;
	else root.PropagationModel = api;
})(typeof window !== 'undefined' ? window : globalThis);
