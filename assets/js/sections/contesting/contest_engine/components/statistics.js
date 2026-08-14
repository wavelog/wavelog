/**
 * StatisticsComponent - N1MM/Wintest-style QSO statistics panel
 * All calculations are performed client-side from the DataStore — no server requests.
 *
 * Internal storage: 288 five-minute buckets per day (index = floor(utcMinuteOfDay / 5)).
 * Chart granularity depends on the selected time window:
 *   1h  → 5 min/point  (12 points)
 *   2h  → 10 min/point (12 points)
 *   4h  → 15 min/point (16 points)
 *   8h  → 60 min/point  (8 points)
 *   12h → 60 min/point (12 points)
 *   24h → 60 min/point (24 points)
 *   0   → absolute view: auto-scaled from first to last QSO (bypasses bucket system)
 *
 * Club mode: own-operator bars are coloured, other operators stacked on top in gray.
 */
class StatisticsComponent {
	constructor(windowId, dataStore) {
		this.windowId  = windowId;
		this.ds        = dataStore;
		this.chart     = null;
		this.refreshInterval = null;
		this._lastChartKey   = null;

		this.isClubStation = !!(window.ContestLoggerConfig?.isClubStation);
		this.ownOperator   = (window.ContestLoggerConfig?.operator ?? '').toUpperCase();
		this.timeWindow    = 4; // hours shown in chart; matches the default active button

		// Resolution in minutes per chart point, keyed by time window (hours)
		this.RESOLUTION = { 1: 5, 2: 10, 4: 15, 8: 60, 12: 60, 24: 60 };

		// Consistent per-band colours (matches classic contest logger conventions)
		this.BAND_COLORS = {
			'160m':  'rgba(231, 76,  60,  0.85)',
			'80m':   'rgba(230,126,  34,  0.85)',
			'60m':   'rgba(241,196,  15,  0.85)',
			'40m':   'rgba( 46,204, 113,  0.85)',
			'30m':   'rgba( 26,188, 156,  0.85)',
			'20m':   'rgba( 52,152, 219,  0.85)',
			'17m':   'rgba(155, 89, 182,  0.85)',
			'15m':   'rgba(233, 30,  99,  0.85)',
			'12m':   'rgba(255, 87,  34,  0.85)',
			'10m':   'rgba(205,220,  57,  0.85)',
			'6m':    'rgba(  0,188, 212,  0.85)',
			'4m':    'rgba(139,195,  74,  0.85)',
			'2m':    'rgba(103, 58, 183,  0.85)',
			'1.25m': 'rgba(244, 67,  54,  0.85)',
			'70cm':  'rgba( 33,150,243,  0.85)',
			'??':    'rgba(108,117,125,  0.85)',
		};

		// Amateur band order for legend / sort
		this.bandOrder = [
			'160m','80m','60m','40m','30m','20m','17m','15m','12m',
			'10m','6m','4m','2m','1.25m','70cm','33cm','23cm','13cm','9cm','6cm','3cm','??'
		];
	}

	init() {
		this.container = document.getElementById(this.windowId);
		if (!this.container) {
			console.warn('StatisticsComponent: container #' + this.windowId + ' not found');
			return;
		}

		this._fillLabels();

		const refreshBtn = this.container.querySelector('#stats-refresh-btn');
		if (refreshBtn) {
			refreshBtn.addEventListener('click', () => this.refresh());
		}

		const stored = this.ds.get('config.stats_time_window');
		if (stored !== undefined) {
			this.timeWindow = stored;
			this.container.querySelectorAll('#stats-window-btns [data-window]')
				.forEach(b => b.classList.toggle('active', parseInt(b.dataset.window, 10) === stored));
		}

		this.container.querySelector('#stats-window-btns')?.addEventListener('click', (e) => {
			const btn = e.target.closest('[data-window]');
			if (!btn) return;
			this.timeWindow = parseInt(btn.dataset.window, 10);
			this.ds.setLocal('config.stats_time_window', this.timeWindow);
			this.container.querySelectorAll('#stats-window-btns [data-window]')
				.forEach(b => b.classList.toggle('active', b === btn));
			this._lastChartKey = null; // force chart rebuild
			this.refresh();
		});

		// React immediately to QSO events emitted by qso-form.js
		this.ds.on('qso_added',         () => this.refresh());
		this.ds.on('qso_state_changed', () => this.refresh());
		this.ds.on('qsos_resynced',     () => this.refresh());

		// Periodic fallback: catches time-window rollovers and deletions
		this.refreshInterval = setInterval(() => this.refresh(), 10_000);

		this.refresh();
	}

	destroy() {
		if (this.refreshInterval) {
			clearInterval(this.refreshInterval);
			this.refreshInterval = null;
		}
		if (this.chart) {
			this.chart.destroy();
			this.chart = null;
		}
	}

	// ── Data access ────────────────────────────────────────────────────────────

	getQsos() {
		const result = [];
		for (const qso of this.ds.getPattern('qso.*').values()) {
			if (qso && qso.callsign) result.push(qso);
		}
		return result;
	}

	getQsoTimestamp(qso) {
		// Build an unambiguous UTC timestamp; "YYYY-MM-DD HH:MM:SS" without a
		// timezone suffix is browser-implementation-defined, so we append Z.
		const d = qso.date || (qso.time_on ? qso.time_on.split(' ')[0] : null);
		const t = qso.time || (qso.time_on ? qso.time_on.split(' ')[1] : null);

		if (d && t) {
			const ts = Date.parse(`${d}T${t}Z`);
			if (Number.isFinite(ts)) return ts;
		}
		if (qso.created) {
			const ts = Date.parse(qso.created);
			if (Number.isFinite(ts)) return ts;
		}
		return 0;
	}

	resolveBand(qso) {
		const band = qso.band || this.convertQrgToBand(parseInt(qso.frequency));
		return band ? band.toLowerCase() : null;
	}

	// ── Statistics computation ─────────────────────────────────────────────────

	computeStats() {
		const qsos  = this.getQsos();
		const nowMs = Date.now();

		let rate60 = 0, rate10 = 0, ownTotal = 0;
		let firstTs = Infinity, lastTs = 0;

		for (const qso of qsos) {
			const ts    = this.getQsoTimestamp(qso);
			const ageMs = ts > 0 ? nowMs - ts : Infinity;
			const isOwn = !this.isClubStation ||
				(qso.operator ?? '').toUpperCase() === this.ownOperator;

			if (isOwn) {
				if (ageMs <= 3_600_000) rate60++;
				if (ageMs <=   600_000) rate10++;
				ownTotal++;
			}

			if (ts > 0) {
				if (ts < firstTs) firstTs = ts;
				if (ts > lastTs)  lastTs  = ts;
			}
		}

		return {
			total: qsos.length,
			ownTotal,
			rate60,
			rate10:  rate10 * 6,
			firstTs: firstTs === Infinity ? 0 : firstTs,
			lastTs,
		};
	}

	// ── Rendering ──────────────────────────────────────────────────────────────

	refresh() {
		const stats = this.computeStats();
		this.renderCounters(stats);
		this.renderCombinedChart(stats);
	}

	renderCounters(stats) {
		const set = (id, val) => {
			const el = this.container.querySelector('#' + id);
			if (el) el.textContent = val;
		};
		set('stats-total',  this.isClubStation ? stats.ownTotal : stats.total);
		set('stats-rate60', stats.rate60);
		set('stats-rate10', stats.rate10);
	}

	renderCombinedChart(stats) {
		if (typeof Chart === 'undefined') return;

		const canvas = this.container.querySelector('#stats-combined-chart');
		if (!canvas) return;

		// Both views build identical charts from {startMs, endMs, resMin} groups —
		// they differ only in how the time groups are derived.
		const groups = this.timeWindow === 0
			? this._getAbsoluteGroups(stats.firstTs, stats.lastTs)
			: this._getWindowGroups();
		this._renderChart(canvas, groups, this.timeWindow === 0 ? 'all' : String(this.timeWindow));
	}

	// ── Window helpers ──────────────────────────────────────────────────────────

	// Fixed sliding window [now − timeWindow h, now] at the configured resolution.
	// Returns the same {startMs, endMs, resMin} group format as _getAbsoluteGroups,
	// so it shares the band aggregation, labelling and rendering paths.
	_getWindowGroups() {
		const resMin  = this.RESOLUTION[this.timeWindow] ?? 60;
		const stepMs  = resMin * 60000;
		const endMs   = Math.ceil(Date.now() / stepMs) * stepMs; // align to grid
		const startMs = endMs - this.timeWindow * 3600000;
		const groups  = [];
		for (let t = startMs; t < endMs; t += stepMs) {
			groups.push({ startMs: t, endMs: t + stepMs, resMin });
		}
		return groups;
	}

	// ── Absolute (all-QSO) view ────────────────────────────────────────────────

	_getAbsoluteGroups(firstTs, lastTs) {
		if (!firstTs || firstTs >= lastTs) return [];

		// Full view always uses fixed one-hour buckets aligned to the UTC hour
		// grid: one point per hour, so the hour label maps 1:1 to the data and a
		// 21:xx QSO shows under "21z" — not aggregated into a wider, mislabelled block.
		const resMin   = 60;
		const stepMs    = resMin * 60000;
		const startMs  = Math.floor(firstTs / stepMs) * stepMs;
		// End one step past the bucket that contains lastTs, so a QSO landing
		// exactly on a bucket boundary (e.g. 21:00:00) is still included instead
		// of being dropped and mislabelled to the previous bucket.
		const endMs    = Math.floor(lastTs / stepMs) * stepMs + stepMs;
		const groups   = [];
		for (let t = startMs; t < endMs; t += stepMs) {
			groups.push({ startMs: t, endMs: t + stepMs, resMin });
		}
		return groups;
	}

	_absGroupLabel(group) {
		const d = new Date(group.startMs);
		const h = String(d.getUTCHours()).padStart(2, '0');
		const m = String(d.getUTCMinutes()).padStart(2, '0');
		return group.resMin >= 60 ? h + 'z' : h + ':' + m + 'z';
	}

	_computeAbsoluteBands(groups) {
		const bandData    = new Map();
		const bandDataOwn = new Map();
		for (const qso of this.getQsos()) {
			const ts = this.getQsoTimestamp(qso);
			if (!ts) continue;
			const band  = this.resolveBand(qso) || '??';
			const isOwn = !this.isClubStation ||
				(qso.operator ?? '').toUpperCase() === this.ownOperator;
			const gi = groups.findIndex(g => ts >= g.startMs && ts < g.endMs);
			if (gi === -1) continue;
			if (!bandData.has(band)) {
				bandData.set(band,    new Array(groups.length).fill(0));
				bandDataOwn.set(band, new Array(groups.length).fill(0));
			}
			bandData.get(band)[gi]++;
			if (isOwn) bandDataOwn.get(band)[gi]++;
		}
		return { bandData, bandDataOwn };
	}

	// Renders the band-stacked line chart from {startMs, endMs, resMin} time groups.
	// Shared by both the sliding-window and absolute views; keyPrefix scopes the
	// rebuild-vs-update cache key (window size or 'all').
	_renderChart(canvas, groups, keyPrefix) {
		const windowLabels = groups.map(g => this._absGroupLabel(g));

		const { bandData, bandDataOwn } = this._computeAbsoluteBands(groups);
		const sortedBands = [...bandData.keys()].sort((a, b) => {
			const ai = this.bandOrder.indexOf(a);
			const bi = this.bandOrder.indexOf(b);
			if (ai === -1 && bi === -1) return a.localeCompare(b);
			if (ai === -1) return 1;
			if (bi === -1) return -1;
			return ai - bi;
		});

		const chartKey = groups.length === 0
			? `${keyPrefix}|empty`
			: `${sortedBands.join(',')}|${keyPrefix}|${groups[0].startMs}|${groups[groups.length - 1].endMs}`;

		const activeData = band => (this.isClubStation ? bandDataOwn : bandData).get(band);

		if (this.chart && this._lastChartKey === chartKey) {
			let dsIdx = 0;
			for (const band of sortedBands) { this.chart.data.datasets[dsIdx++].data = activeData(band); }
			if (this.isClubStation) {
				const others = new Array(groups.length).fill(0);
				for (const [band, counts] of bandData) {
					const own = bandDataOwn.get(band) ?? [];
					for (let i = 0; i < groups.length; i++) others[i] += (counts[i] ?? 0) - (own[i] ?? 0);
				}
				this.chart.data.datasets[dsIdx].data = others;
			}
			this.chart.update('none');
			return;
		}

		if (this.chart) { this.chart.destroy(); this.chart = null; }
		this._lastChartKey = chartKey;

		const tickColor = getComputedStyle(document.documentElement)
			.getPropertyValue('--bs-body-color').trim() || '#dee2e6';

		const datasets = sortedBands.map(band => {
			const color       = this.BAND_COLORS[band] ?? 'rgba(108,117,125,0.85)';
			const borderColor = color.replace(/[\d.]+\)$/, '1)');
			return {
				label: band, data: activeData(band),
				backgroundColor: color, borderColor, borderWidth: 1.5,
				fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 4,
			};
		});

		if (this.isClubStation) {
			const others = new Array(groups.length).fill(0);
			for (const [band, counts] of bandData) {
				const own = bandDataOwn.get(band) ?? [];
				for (let i = 0; i < groups.length; i++) others[i] += (counts[i] ?? 0) - (own[i] ?? 0);
			}
			datasets.push({
				label: lang_stats_others_col, data: others,
				backgroundColor: 'rgba(108,117,125,0.35)', borderColor: 'rgba(108,117,125,0.7)',
				borderWidth: 1.5, fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 4,
			});
		}

		const tickStep = windowLabels.length > 16 ? 4 : (windowLabels.length > 8 ? 2 : 1);
		this.chart = new Chart(canvas, {
			type: 'line',
			data: { labels: windowLabels, datasets },
			options: {
				responsive: true, maintainAspectRatio: false, animation: false,
				plugins: {
					legend: {
						display:  sortedBands.length > 0,
						position: 'bottom',
						labels: { color: tickColor, boxWidth: 10, padding: 6, font: { size: 10 } },
					},
					tooltip: {
						mode: 'index',
						callbacks: {
							title: items => items[0].label + ' UTC',
							label: item  => item.dataset.label + ': ' + item.raw,
						},
					},
				},
				scales: {
					x: {
						ticks: { color: tickColor, maxRotation: 0,
							callback: (val, idx) => idx % tickStep === 0 ? windowLabels[idx] : '' },
						grid: { color: 'rgba(128,128,128,0.15)' },
					},
					y: {
						stacked: true, beginAtZero: true,
						ticks: { color: tickColor, precision: 0 },
						grid: { color: 'rgba(128,128,128,0.15)' },
					},
				},
			},
		});
	}

	// ── Misc helpers ───────────────────────────────────────────────────────────

	_fillLabels() {
		const set = (id, text) => {
			const el = this.container.querySelector('#' + id);
			if (el) el.textContent = text;
		};
		set('lbl-stats-total',  lang_stats_total);
		set('lbl-stats-rate60', lang_stats_rate60);
		set('lbl-stats-rate10', lang_stats_rate10);
	}

	convertQrgToBand(frequency) {
		if (!frequency) return null;
		if (frequency >= 1800000   && frequency < 2000000)   return '160m';
		if (frequency >= 3500000   && frequency < 4000000)   return '80m';
		if (frequency >= 5300000   && frequency < 5400000)   return '60m';
		if (frequency >= 7000000   && frequency < 7300000)   return '40m';
		if (frequency >= 10000000  && frequency < 10150000)  return '30m';
		if (frequency >= 14000000  && frequency < 14350000)  return '20m';
		if (frequency >= 18000000  && frequency < 18200000)  return '17m';
		if (frequency >= 21000000  && frequency < 21450000)  return '15m';
		if (frequency >= 24000000  && frequency < 24990000)  return '12m';
		if (frequency >= 28000000  && frequency < 29700000)  return '10m';
		if (frequency >= 50000000  && frequency < 54000000)  return '6m';
		if (frequency >= 70000000  && frequency < 70500000)  return '4m';
		if (frequency >= 144000000 && frequency < 148000000) return '2m';
		if (frequency >= 222000000 && frequency < 225000000) return '1.25m';
		if (frequency >= 420000000 && frequency < 450000000) return '70cm';
		return '??';
	}
}

// Self-register when app is ready
window.addEventListener('contestAppReady', (event) => {
	const ds = event.detail?.ds ?? window.contestApp?.ds;
	if (!ds) {
		console.warn('StatisticsComponent: DataStore not available');
		return;
	}

	const component = new StatisticsComponent('statistics', ds);
	component.init();

	if (window.contestApp) {
		window.contestApp.statisticsComponent = component;
	}
});
