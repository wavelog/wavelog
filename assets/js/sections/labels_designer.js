/**
 * Label Designer — frontend editor.
 *
 * Adapted from the QSL Postcard Designer (assets/js/sections/qslpostcard.js).
 * Backend contract is unchanged: the layout JSON it builds/consumes uses the
 * same schema the PDF renderer expects
 * (page / calibration / elements[{id,type,field|text,x_in,y_in,font,font_pt,bold,wrap_w_in}]).
 *
 * Differences from the postcard editor:
 *  - The canvas (stage) is sized dynamically from the selected label type
 *    (LABEL_TYPES, injected by the view) instead of a fixed 5.5 × 3.5 in.
 *  - No background image; template options are qsos_per_label + row pitch.
 *  - Fonts are drawn at true WYSIWYG scale (pt → px via PX_PER_IN/72).
 */
(function () {
	'use strict';

	// ===== Constants =====
	const PX_PER_IN = 230;              // uniform canvas scale (66.675mm label → ~604px)
	const GRID_IN = 0.125;              // snap grid (eighth inch — labels are small)
	const SNAP_PX = 8;                  // snap threshold (internal px)
	const ZOOM_MIN = 0.5, ZOOM_MAX = 2.0, ZOOM_STEP = 0.1;
	const HISTORY_MAX = 100;

	// ===== Canvas geometry (dynamic — set by applyLabelType) =====
	// Defaults are a 5160-class cell (2.625 × 1 in) purely so the stage has a
	// sane shape before a label type is picked; nothing can be saved without
	// an explicit label type.
	let W_IN = 2.625, H_IN = 1.0;
	let stageWpx = Math.round(W_IN * PX_PER_IN);
	let stageHpx = Math.round(H_IN * PX_PER_IN);
	let GRID_PX = GRID_IN * PX_PER_IN;  // 28.75px

	// ===== Coordinate helpers =====
	const pxToInX = px => px / PX_PER_IN;
	const pxToInY = py => py / PX_PER_IN;
	const inToPxX = ix => ix * PX_PER_IN;
	const inToPxY = iy => iy * PX_PER_IN;
	const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
	const round2 = v => Math.round(v * 100) / 100;

	// ===== Display-unit helpers =====
	// Internal layout values are always inches (PDF converts in→mm). When the
	// user's measurement preference is kilometers we show centimeters instead;
	// otherwise inches. Only values crossing into/out of an <input> or the
	// ruler are converted — the stored geometry stays in inches.
	const METRIC      = (typeof measurement_base !== 'undefined' && measurement_base === 'K');
	const DISP_PER_IN = METRIC ? 2.54 : 1;          // display units per inch
	const inToDisp = v => round2(v * DISP_PER_IN);  // inch  → number shown to the user
	const dispToIn = v => v / DISP_PER_IN;          // typed number → inch (stored)

	// ===== State =====
	let elements = [];          // {id,type,field|text,x_in,y_in,font,font_pt,bold,wrap_w_in}
	let selectedIds = [];       // ids of currently selected elements
	let zoom = 1;

	// Template-wide options (persisted in layout.options; see buildLayout/loadTemplate).
	// Single source of truth for a fresh/blank template; loadTemplate overlays saved
	// values on top of this (with its own coercion for legacy/malformed JSON).
	const DEFAULT_TPL_OPTIONS = Object.freeze({
		qsos_per_label: 1,
		row_pitch_in: 0.3,
		row_separators: false,
		sep_thick_pt: 0.4,
	});
	let tplOptions = { ...DEFAULT_TPL_OPTIONS };
	const history = [];
	const future = [];
	let drag = null;            // active element drag
	let marquee = null;         // active rubber-band selection

	// ===== DOM refs =====
	const stage      = document.getElementById('stage');
	const rulerWrap  = document.getElementById('rulerWrap');
	const stageZoom  = document.getElementById('stageZoom');
	const stageScroll = document.getElementById('stageScroll');
	const ctxMenu    = document.getElementById('qslCtxMenu');
	const offXInput  = document.getElementById('offX');
	const offYInput  = document.getElementById('offY');
	const tplSelect  = document.getElementById('tplSelect');
	const labelTypeSelect = document.getElementById('labelTypeSelect');
	const designerRoot = document.getElementById('lblDesigner');

	const byId    = id => elements.find(e => e.id === id);
	const nodeById = id => stage.querySelector('.qsl_designer_placed[data-id="' + id + '"]');

	// ===== Persisted UI preferences (localStorage) =====
	const LS_PREFIX = 'wl_labeldesigner_';
	function prefGet(key, fallback) {
		try { const v = localStorage.getItem(LS_PREFIX + key); return v === null ? fallback : v; }
		catch (e) { return fallback; }
	}
	function prefSet(key, value) {
		try { localStorage.setItem(LS_PREFIX + key, value); } catch (e) { /* private mode / disabled */ }
	}

	// ===================================================================
	//  Label type (canvas geometry)
	// ===================================================================
	// Size the stage from a label type. `clampElements` moves out-of-bounds
	// elements back onto the canvas when the user switches to a smaller label
	// type; loading a saved template passes false so stored positions are kept.
	function applyLabelType(id, clampElements = true) {
		const geo = id ? LABEL_TYPES[id] : null;
		if (geo) {
			W_IN = Math.max(0.2, parseFloat(geo.w_in) || W_IN);
			H_IN = Math.max(0.2, parseFloat(geo.h_in) || H_IN);
		} else {
			W_IN = 2.625; H_IN = 1.0;   // placeholder shape until a type is picked
		}
		stageWpx = Math.round(W_IN * PX_PER_IN);
		stageHpx = Math.round(H_IN * PX_PER_IN);
		GRID_PX = GRID_IN * PX_PER_IN;

		designerRoot.style.setProperty('--lbl-stage-w', stageWpx + 'px');
		designerRoot.style.setProperty('--lbl-stage-h', stageHpx + 'px');
		designerRoot.style.setProperty('--lbl-grid', GRID_PX + 'px');

		if (clampElements) {
			elements.forEach(it => {
				it.x_in = clamp(it.x_in, 0, W_IN);
				it.y_in = clamp(it.y_in, 0, H_IN);
			});
		}

		updateDimsLabel();
		drawRulers();
		if (id) prefSet('labeltype', id);
		renderAll();
	}

	function updateDimsLabel() {
		const el = document.getElementById('lblDims');
		if (!el) return;
		if (!labelTypeSelect.value) { el.textContent = ''; return; }
		el.textContent = METRIC
			? round2(W_IN * 2.54) + ' × ' + round2(H_IN * 2.54) + ' cm'
			: round2(W_IN) + ' × ' + round2(H_IN) + ' in';
	}

	labelTypeSelect.addEventListener('change', e => {
		applyLabelType(e.target.value);
	});

	// ===================================================================
	//  History (undo / redo)
	// ===================================================================
	function serialize() {
		return JSON.stringify({
			elements: elements,
			offX: dispToIn(parseFloat(offXInput.value || '0')),
			offY: dispToIn(parseFloat(offYInput.value || '0')),
		});
	}

	function pushHistory() {
		history.push(serialize());
		if (history.length > HISTORY_MAX) history.shift();
		future.length = 0;
		updateHistoryButtons();
	}

	function restore(json) {
		const s = JSON.parse(json);
		elements = s.elements || [];
		offXInput.value = inToDisp(s.offX ?? 0);
		offYInput.value = inToDisp(s.offY ?? 0);
		selectedIds = [];
		renderAll();
		refreshProperties();
	}

	function undo() {
		if (!history.length) return;
		future.push(serialize());
		restore(history.pop());
		updateHistoryButtons();
	}

	function redo() {
		if (!future.length) return;
		history.push(serialize());
		restore(future.pop());
		updateHistoryButtons();
	}

	function updateHistoryButtons() {
		document.getElementById('btnUndo').disabled = history.length === 0;
		document.getElementById('btnRedo').disabled = future.length === 0;
	}

	// ===================================================================
	//  Rendering
	// ===================================================================
	function renderAll() {
		stage.querySelectorAll('.qsl_designer_placed').forEach(n => n.remove());
		elements.forEach(renderElement); // array order == z-order
		syncSelection();
	}

	// True WYSIWYG font scale: a pt is 1/72 in, the stage is PX_PER_IN px/in.
	const fontPx = pt => Math.max(4, Math.round((pt || 8) * PX_PER_IN / 72));

	// ===== Table element helpers =====
	// Effective per-column widths (inches). Normalizes the stored col_w list:
	// falls back to equal widths when missing/malformed.
	function tableCols(item) {
		const cols = clamp(parseInt(item.cols, 10) || 3, 1, 12);
		let w = Array.isArray(item.col_w) ? item.col_w.map(v => parseFloat(v) || 0) : [];
		if (w.length !== cols || w.reduce((a, b) => a + b, 0) <= 0) {
			w = Array.from({ length: cols }, () => (item.w_in || 1.8) / cols);
		}
		return w;
	}

	// (Re)build a table element's DOM: the container carries the outer border,
	// child divs are the internal column/row rules. Children have no listeners;
	// mouse events bubble to the container, which owns the drag binding.
	function buildTableNode(el, item) {
		el.classList.add('qsl-table');
		const tpx = Math.max(1, (item.thick_pt || 0.4) * PX_PER_IN / 72);
		const col = item.color || '#000000';
		el.style.width = inToPxX(item.w_in || 1.8) + 'px';
		el.style.height = inToPxY(item.h_in || 0.5) + 'px';
		el.style.borderColor = col;
		el.style.borderWidth = tpx + 'px';
		el.style.background = 'transparent';
		el.querySelectorAll('.qsl-tbl-v,.qsl-tbl-h').forEach(n => n.remove());
		const rows = clamp(parseInt(item.rows, 10) || 3, 1, 20);
		const hpx = inToPxY(item.h_in || 0.5);
		tableCols(item).forEach((cw, i, arr) => {
			if (i === arr.length - 1) return;
			const v = document.createElement('div');
			v.className = 'qsl-tbl-v';
			v.style.left = (arr.slice(0, i + 1).reduce((a, b) => a + b, 0) * PX_PER_IN - tpx / 2) + 'px';
			v.style.top = (-tpx / 2) + 'px';
			v.style.bottom = (-tpx / 2) + 'px';
			v.style.width = tpx + 'px';
			v.style.background = col;
			el.appendChild(v);
		});
		for (let i = 1; i < rows; i++) {
			const h = document.createElement('div');
			h.className = 'qsl-tbl-h';
			h.style.top = (hpx * i / rows - tpx / 2) + 'px';
			h.style.left = (-tpx / 2) + 'px';
			h.style.right = (-tpx / 2) + 'px';
			h.style.height = tpx + 'px';
			h.style.background = col;
			el.appendChild(h);
		}
	}

	function renderElement(item) {
		const el = document.createElement('div');
		el.className = 'qsl_designer_placed';
		el.dataset.id = item.id;
		el.dataset.type = item.type;
		el.style.left = inToPxX(item.x_in) + 'px';
		el.style.top = inToPxY(item.y_in) + 'px';
		if (item.type === 'line') {
			styleLineNode(el, item);
		} else if (item.type === 'table') {
			buildTableNode(el, item);
		} else {
			el.textContent = item.type === 'field' ? item.field : (item.text || LANG.customText);
			el.style.fontFamily = item.font || 'Helvetica';
			el.style.fontSize = fontPx(item.font_pt) + 'px';
			el.style.fontWeight = item.bold ? '700' : '600';
			el.style.color = item.color || '#000000';
		}

		el.addEventListener('mousedown', e => onElementMouseDown(e, item.id));

		stage.appendChild(el);
		renderGhosts(item);
		return el;
	}

	// Ruled line element: a thin filled bar (h or v) sized from len_in/thick_pt.
	// Minimum 2px so thin rules stay grabbable on screen; the PDF prints the
	// true thickness.
	function styleLineNode(node, item) {
		node.classList.add(item.orient === 'v' ? 'qsl-line-v' : 'qsl-line-h');
		const tpx = Math.max(2, (item.thick_pt || 0.5) * PX_PER_IN / 72);
		node.style.background = item.color || '#000000';
		if (item.orient === 'v') {
			node.style.width = tpx + 'px';
			node.style.height = inToPxY(item.len_in || 1) + 'px';
		} else {
			node.style.height = tpx + 'px';
			node.style.width = inToPxX(item.len_in || 1) + 'px';
		}
	}

	function positionNode(id) {
		const item = byId(id), node = nodeById(id);
		if (!item || !node) return;
		node.style.left = inToPxX(item.x_in) + 'px';
		node.style.top = inToPxY(item.y_in) + 'px';
		repositionGhosts(item);
		refreshLineHandleFor(id);
		refreshTableHandlesFor(id);
	}

	function styleNode(id) {
		const item = byId(id), node = nodeById(id);
		if (!item || !node) return;
		if (item.type === 'line') {
			node.classList.remove('qsl-line-h', 'qsl-line-v');
			node.textContent = '';
			node.style.fontFamily = node.style.fontSize = node.style.fontWeight = '';
			styleLineNode(node, item);
			refreshLineHandleFor(item.id);
		} else if (item.type === 'table') {
			node.textContent = '';
			node.style.fontFamily = node.style.fontSize = node.style.fontWeight = '';
			node.style.color = '';
			buildTableNode(node, item);
			refreshTableHandlesFor(item.id);
		} else {
			node.textContent = item.type === 'field' ? item.field : (item.text || LANG.customText);
			node.style.fontFamily = item.font || 'Helvetica';
			node.style.fontSize = fontPx(item.font_pt) + 'px';
			node.style.fontWeight = item.bold ? '700' : '600';
			node.style.color = item.color || '#000000';
		}
		// Keep WYSIWYG ghost rows in sync with the primary.
		stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + item.id + '"]').forEach(g => {
			g.textContent = node.textContent;
			g.style.fontFamily = node.style.fontFamily;
			g.style.fontSize = node.style.fontSize;
			g.style.fontWeight = node.style.fontWeight;
			g.style.color = node.style.color;
			if (item.type === 'line') { syncGhostLine(g, item); }
		});
	}

	// Ghost copies of a line carry the same geometry as the primary.
	function syncGhostLine(g, item) {
		g.classList.remove('qsl-line-h', 'qsl-line-v');
		const tpx = Math.max(2, (item.thick_pt || 0.5) * PX_PER_IN / 72);
		g.style.background = item.color || '#000000';
		g.style.color = '';
		if (item.orient === 'v') {
			g.classList.add('qsl-line-v');
			g.style.width = tpx + 'px';
			g.style.height = inToPxY(item.len_in || 1) + 'px';
		} else {
			g.classList.add('qsl-line-h');
			g.style.height = tpx + 'px';
			g.style.width = inToPxX(item.len_in || 1) + 'px';
		}
	}

	// WYSIWYG ghost rows: faded extra copies of a "repeats per QSO" field, stacked
	// below it by the row pitch. Ghosts have no data-id and no event listeners, so
	// every selection/drag/marquee path (keyed off data-id) ignores them.
	function renderGhosts(item) {
		stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + item.id + '"]').forEach(n => n.remove());
		if (!item.repeat_per_qso || tplOptions.qsos_per_label <= 1) return;
		const primary = nodeById(item.id);
		if (!primary) return;
		const pitchPx = inToPxY(tplOptions.row_pitch_in);
		const left = inToPxX(item.x_in);
		const top = inToPxY(item.y_in);
		for (let i = 1; i < tplOptions.qsos_per_label; i++) {
			const g = primary.cloneNode(true);   // cloneNode does not copy event listeners
			g.removeAttribute('data-id');
			g.removeAttribute('data-type');
			g.classList.remove('selected');
			g.classList.add('qsl_designer_ghost');
			g.dataset.ghostFor = item.id;
			g.style.left = left + 'px';
			g.style.top = (top + pitchPx * i) + 'px';
			stage.appendChild(g);
		}
	}

	function repositionGhosts(item) {
		const ghosts = stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + item.id + '"]');
		if (!ghosts.length) return;
		const pitchPx = inToPxY(tplOptions.row_pitch_in);
		const left = inToPxX(item.x_in), top = inToPxY(item.y_in);
		ghosts.forEach((g, i) => {
			g.style.left = left + 'px';
			g.style.top = (top + pitchPx * (i + 1)) + 'px';
		});
	}

	// ===================================================================
	//  Element creation
	// ===================================================================
	function newId() {
		return 'el_' + Math.random().toString(16).slice(2);
	}

	function addElement(type, value, x_in, y_in) {
		pushHistory();
		const item = {
			id: newId(),
			type: type,
			x_in: round2(x_in),
			y_in: round2(y_in),
			font: 'Helvetica',
			font_pt: 8,
			bold: false,
			color: '#000000',
			wrap_w_in: 2.0,
			repeat_per_qso: false,
			no_snap: false,
			freq_format: 'MHz',
			freq_no_unit: false,
		};
		if (type === 'field') item.field = value;
		else if (type === 'line') { item.orient = (value === 'v') ? 'v' : 'h'; item.len_in = 1.5; item.thick_pt = 0.5; }
		else if (type === 'table') {
			item.cols = 3;
			item.rows = Math.max(3, tplOptions.qsos_per_label);
			item.h_in = round2(item.rows * (tplOptions.row_pitch_in || 0.12));
			item.w_in = Math.min(1.8, round2(W_IN * 0.8));
			item.col_w = tableCols(item);
			item.thick_pt = 0.4;
		}
		else item.text = value;
		elements.push(item);
		renderElement(item);
		selectOne(item.id);
		return item;
	}

	// Find a free, non-overlapping spot for click-to-add.
	function freeSpot() {
		let x = 20, y = 12;
		const occupied = () => elements.some(e =>
			Math.abs(inToPxX(e.x_in) - x) < 12 && Math.abs(inToPxY(e.y_in) - y) < 12);
		let guard = 0;
		while (occupied() && guard++ < 40) {
			x += 22; y += 18;
			if (x > stageWpx - 80) { x = 20; y += 24; }
			if (y > stageHpx - 24) { y = 12; }
		}
		return { x: x, y: y };
	}

	// ===================================================================
	//  Selection (multi)
	// ===================================================================
	const isSelected = id => selectedIds.includes(id);
	// The "primary" (last added to the selection) drives the properties panel.
	const primaryId = () => selectedIds.length ? selectedIds[selectedIds.length - 1] : null;

	function setSelection(ids) {
		selectedIds = ids.slice();
		syncSelection();
		refreshProperties();
	}

	function selectOne(id) { setSelection([id]); }

	function toggleSelection(id) {
		const i = selectedIds.indexOf(id);
		if (i >= 0) selectedIds.splice(i, 1);
		else selectedIds.push(id);
		syncSelection();
		refreshProperties();
	}

	function deselect() { setSelection([]); }

	function syncSelection() {
		stage.querySelectorAll('.qsl_designer_placed').forEach(n =>
			n.classList.toggle('selected', isSelected(n.dataset.id)));
		syncLineHandle();
		syncTableHandles();
	}

	// ===================================================================
	//  Dragging placed elements (with snapping)
	// ===================================================================
	function clientToStagePx(clientX, clientY) {
		const rect = stage.getBoundingClientRect();
		return { x: (clientX - rect.left) / zoom, y: (clientY - rect.top) / zoom };
	}

	// The mousedown handlers below preventDefault() (to stop text selection
	// while dragging), which also stops the browser from moving focus. Without
	// this, focus lingers on the last-used control (toolbar dropdowns,
	// property inputs) and its keydown handler eats the arrow keys — or worse,
	// arrows change a focused dropdown. Give the canvas keyboard focus back.
	function blurActiveControl() {
		const ae = document.activeElement;
		if (ae && ae !== document.body && typeof ae.blur === 'function') ae.blur();
	}

	function onElementMouseDown(e, id) {
		if (e.button !== 0) return;
		e.preventDefault();
		blurActiveControl();

		const additive = e.shiftKey || e.ctrlKey || e.metaKey;
		if (additive) {
			toggleSelection(id);
			if (!isSelected(id)) return; // was removed → don't start a drag
		} else if (!isSelected(id)) {
			selectOne(id);               // clicking an unselected element selects only it
		}                                // (already in a multi-selection → keep it, drag the group)

		// Start a group drag: remember every selected element's origin position.
		const p = clientToStagePx(e.clientX, e.clientY);
		drag = { primary: id, ids: selectedIds.slice(), startX: p.x, startY: p.y, orig: {}, moved: false };
		drag.ids.forEach(sid => {
			const it = byId(sid);
			drag.orig[sid] = { x_in: it.x_in, y_in: it.y_in };
		});
	}

	function onElementDragMove(e) {
		if (!drag.moved) { pushHistory(); drag.moved = true; }

		const p = clientToStagePx(e.clientX, e.clientY);
		let dx = p.x - drag.startX;
		let dy = p.y - drag.startY;

		// Snap based on the grabbed (primary) element, then move the whole group by
		// the same (snap-corrected) delta.
		const po = drag.orig[drag.primary];
		const snapEnabled = !e.altKey && !byId(drag.primary).no_snap;
		const snapped = snapPosition(drag.primary, inToPxX(po.x_in) + dx, inToPxY(po.y_in) + dy, snapEnabled, drag.ids);
		dx += snapped.x - (inToPxX(po.x_in) + dx);
		dy += snapped.y - (inToPxY(po.y_in) + dy);

		// Clamp the delta so the group's bounding box stays on the stage.
		let minX = Infinity, minY = Infinity, maxR = -Infinity, maxB = -Infinity;
		drag.ids.forEach(sid => {
			const o = drag.orig[sid], n = nodeById(sid);
			const ox = inToPxX(o.x_in), oy = inToPxY(o.y_in);
			minX = Math.min(minX, ox); minY = Math.min(minY, oy);
			maxR = Math.max(maxR, ox + (n ? n.offsetWidth : 0));
			maxB = Math.max(maxB, oy + (n ? n.offsetHeight : 0));
		});
		dx = clamp(dx, -minX, stageWpx - maxR);
		dy = clamp(dy, -minY, stageHpx - maxB);

		drag.ids.forEach(sid => {
			const o = drag.orig[sid], it = byId(sid);
			it.x_in = pxToInX(inToPxX(o.x_in) + dx);
			it.y_in = pxToInY(inToPxY(o.y_in) + dy);
			positionNode(sid);
		});
		drawGuides(snapped.guides);
		syncPropsPosition();
	}

	window.addEventListener('mousemove', e => {
		if (tblDrag) { onTblDragMove(e); return; }
		if (lineResize) { onLineResizeMove(e); return; }
		if (drag) { onElementDragMove(e); return; }
		if (marquee) { onMarqueeMove(e); return; }
	});

	window.addEventListener('mouseup', () => {
		if (tblDrag) { tblDrag = null; return; }
		if (lineResize) { lineResize = null; return; }
		if (drag) { drag = null; clearGuides(); return; }
		if (marquee) { onMarqueeEnd(); return; }
	});

	// Snap the element's left/center/right & top/center/bottom to grid lines and
	// to other elements' edges/centers. Returns adjusted x/y + guide lines to draw.
	function snapPosition(id, nx, ny, enabled, ignoreIds) {
		if (!enabled) return { x: nx, y: ny, guides: [] };

		const ignore = ignoreIds || [id];
		const node = nodeById(id);
		const w = node ? node.offsetWidth : 0;
		const h = node ? node.offsetHeight : 0;
		const guides = [];

		const xLines = [], yLines = [];
		elements.forEach(o => {
			if (ignore.includes(o.id)) return;
			if (o.no_snap) return; // elements with snapping disabled are not snap targets
			const on = nodeById(o.id);
			const ox = inToPxX(o.x_in), oy = inToPxY(o.y_in);
			const ow = on ? on.offsetWidth : 0, oh = on ? on.offsetHeight : 0;
			xLines.push(ox, ox + ow / 2, ox + ow);
			yLines.push(oy, oy + oh / 2, oy + oh);
		});
		for (let g = 0; g <= stageWpx + 0.1; g += GRID_PX) xLines.push(g);
		for (let g = 0; g <= stageHpx + 0.1; g += GRID_PX) yLines.push(g);

		// X: try snapping left / center / right edges
		let bestX = null, bestXd = SNAP_PX + 1, nxSnap = nx;
		[[nx, 0], [nx + w / 2, w / 2], [nx + w, w]].forEach(([edge, ofs]) => {
			xLines.forEach(line => {
				const d = Math.abs(line - edge);
				if (d < bestXd) { bestXd = d; bestX = line; nxSnap = line - ofs; }
			});
		});
		if (bestX !== null && bestXd <= SNAP_PX) { nx = nxSnap; guides.push({ axis: 'v', pos: bestX }); }

		// Y: try snapping top / center / bottom edges
		let bestY = null, bestYd = SNAP_PX + 1, nySnap;
		[[ny, 0], [ny + h / 2, h / 2], [ny + h, h]].forEach(([edge, ofs]) => {
			yLines.forEach(line => {
				const d = Math.abs(line - edge);
				if (d < bestYd) { bestYd = d; bestY = line; nySnap = line - ofs; }
			});
		});
		if (bestY !== null && bestYd <= SNAP_PX) { ny = nySnap; guides.push({ axis: 'h', pos: bestY }); }

		return { x: nx, y: ny, guides: guides };
	}

	function drawGuides(guides) {
		clearGuides();
		guides.forEach(g => {
			const line = document.createElement('div');
			line.className = 'qsl-guide qsl-guide-' + g.axis;
			if (g.axis === 'v') line.style.left = g.pos + 'px';
			else line.style.top = g.pos + 'px';
			stage.appendChild(line);
		});
	}

	function clearGuides() {
		stage.querySelectorAll('.qsl-guide').forEach(n => n.remove());
	}

	// ===================================================================
	//  Line resize handle — drag the square at a selected line's end to
	//  change its length (lines are the only element with a meaningful
	//  user-editable extent; fields size to their text).
	// ===================================================================
	let lineResize = null;

	function clearLineHandle() {
		stage.querySelectorAll('.qsl-line-handle').forEach(n => n.remove());
	}

	// Show a handle when exactly one LINE element is selected.
	function syncLineHandle() {
		clearLineHandle();
		if (selectedIds.length !== 1) return;
		const item = byId(selectedIds[0]);
		if (!item || item.type !== 'line') return;
		const h = document.createElement('div');
		h.className = 'qsl-line-handle ' + (item.orient === 'v' ? 'ns' : 'ew');
		h.dataset.for = item.id;
		positionLineHandle(h, item);
		h.addEventListener('mousedown', e => onStartLineResize(e, item.id));
		stage.appendChild(h);
	}

	function positionLineHandle(h, item) {
		const len = item.len_in || 1;
		const cx = item.orient === 'v' ? 0 : inToPxX(len);
		const cy = item.orient === 'v' ? inToPxY(len) : 0;
		h.style.left = (inToPxX(item.x_in) + cx - 4) + 'px';
		h.style.top = (inToPxY(item.y_in) + cy - 4) + 'px';
	}

	// Keep the handle glued to its line when the line moves or restyles.
	function refreshLineHandleFor(id) {
		const h = stage.querySelector('.qsl-line-handle');
		if (h && h.dataset.for === id) positionLineHandle(h, byId(id));
	}

	function onStartLineResize(e, id) {
		if (e.button !== 0) return;
		e.preventDefault();
		e.stopPropagation();
		const item = byId(id);
		if (!item) return;
		lineResize = { id: id, orient: item.orient, startLen: item.len_in || 1, startX: e.clientX, startY: e.clientY, moved: false };
	}

	function onLineResizeMove(e) {
		const item = byId(lineResize.id);
		if (!item) { lineResize = null; return; }
		if (!lineResize.moved) { pushHistory(); lineResize.moved = true; }
		const d = (lineResize.orient === 'v') ? (e.clientY - lineResize.startY) : (e.clientX - lineResize.startX);
		item.len_in = clamp(round2(lineResize.startLen + pxToInX(d)), 0.05, 20);
		styleNode(item.id);
		refreshLineHandleFor(item.id);
		refreshProperties();
	}

	// ===== Table handles =====
	// Corner handle (bottom-right) resizes the whole table (column widths keep
	// their proportions); one handle per internal column boundary (top edge)
	// resizes that boundary, shifting width between the two adjacent columns.
	let tblDrag = null;

	function clearTableHandles() {
		stage.querySelectorAll('.qsl-tbl-handle').forEach(n => n.remove());
	}

	function syncTableHandles() {
		clearTableHandles();
		if (selectedIds.length !== 1) return;
		const item = byId(selectedIds[0]);
		if (!item || item.type !== 'table') return;

		// One resize handle per corner; each anchors the opposite corner.
		['nw', 'ne', 'sw', 'se'].forEach(dir => {
			const c = document.createElement('div');
			c.className = 'qsl-tbl-handle qsl-tbl-' + dir;
			c.dataset.for = item.id;
			c.dataset.kind = dir;
			c.addEventListener('mousedown', e => startTblDrag(e, item.id, 'corner', dir));
			stage.appendChild(c);
		});

		const cols = tableCols(item);
		let cum = 0;
		for (let i = 0; i < cols.length - 1; i++) {
			cum += cols[i];
			const h = document.createElement('div');
			h.className = 'qsl-tbl-handle qsl-tbl-col';
			h.dataset.for = item.id;
			h.dataset.kind = 'col';
			h.dataset.idx = i;
			h.addEventListener('mousedown', e => startTblDrag(e, item.id, 'col', i));
			stage.appendChild(h);
		}
		positionTableHandles(item);
	}

	function positionTableHandles(item) {
		stage.querySelectorAll('.qsl-tbl-handle[data-for="' + item.id + '"]').forEach(h => {
			if (h.dataset.kind === 'col') {
				const cols = tableCols(item);
				let cum = 0;
				for (let i = 0; i <= parseInt(h.dataset.idx, 10); i++) cum += cols[i];
				h.style.left = (inToPxX(item.x_in) + cum * PX_PER_IN - 4) + 'px';
				h.style.top = (inToPxY(item.y_in) + 4) + 'px';
			} else {
				// Corner handle ('nw' | 'ne' | 'sw' | 'se'), centered on its corner
				const kind = h.dataset.kind;
				const x = inToPxX(item.x_in), y = inToPxY(item.y_in);
				const w = inToPxX(item.w_in || 1.8), hh = inToPxY(item.h_in || 0.5);
				h.style.left = ((kind.indexOf('e') >= 0 ? x + w : x) - 4) + 'px';
				h.style.top = ((kind.indexOf('s') >= 0 ? y + hh : y) - 4) + 'px';
			}
		});
	}

	function refreshTableHandlesFor(id) {
		const item = byId(id);
		if (item && stage.querySelector('.qsl-tbl-handle[data-for="' + id + '"]')) {
			positionTableHandles(item);
		}
	}

	function startTblDrag(e, id, mode, idx) {
		if (e.button !== 0) return;
		e.preventDefault();
		e.stopPropagation();
		const item = byId(id);
		if (!item) return;
		tblDrag = {
			id: id, mode: mode, idx: idx,
			startX: e.clientX, startY: e.clientY,
			x0: item.x_in || 0, y0: item.y_in || 0,
			w0: item.w_in || 1.8, h0: item.h_in || 0.5,
			cols0: tableCols(item).slice(),
			moved: false,
		};
	}

	function onTblDragMove(e) {
		const item = byId(tblDrag.id);
		if (!item) { tblDrag = null; return; }
		if (!tblDrag.moved) { pushHistory(); tblDrag.moved = true; }

		if (tblDrag.mode === 'corner') {
			// idx is the corner being dragged ('nw'|'ne'|'sw'|'se'); the opposite
			// corner stays anchored. West/north drags move x/y and grow the
			// width/height toward the fixed opposite edge; everything is clamped
			// to minimum sizes and the label bounds so the table can't flip or
			// slide off the canvas.
			const dir = tblDrag.idx;
			const dx = pxToInX(e.clientX - tblDrag.startX);
			const dy = pxToInY(e.clientY - tblDrag.startY);
			const east = dir.indexOf('e') >= 0, south = dir.indexOf('s') >= 0;

			let w, h;
			if (east) {
				w = clamp(round2(tblDrag.w0 + dx), 0.2, Math.max(0.2, W_IN - tblDrag.x0));
				item.x_in = tblDrag.x0;
			} else {
				const x = clamp(round2(tblDrag.x0 + dx), 0, Math.max(0, tblDrag.x0 + tblDrag.w0 - 0.2));
				item.x_in = x;
				w = round2(tblDrag.x0 + tblDrag.w0 - x);
			}
			if (south) {
				h = clamp(round2(tblDrag.h0 + dy), 0.1, Math.max(0.1, H_IN - tblDrag.y0));
				item.y_in = tblDrag.y0;
			} else {
				const y = clamp(round2(tblDrag.y0 + dy), 0, Math.max(0, tblDrag.y0 + tblDrag.h0 - 0.1));
				item.y_in = y;
				h = round2(tblDrag.y0 + tblDrag.h0 - y);
			}
			item.w_in = w;
			item.h_in = h;
			// Keep the column proportions while resizing the whole table
			const ratio = w / tblDrag.w0;
			item.col_w = tblDrag.cols0.map(cw => round2(cw * ratio));
			positionNode(item.id);   // west/north drags moved the top-left corner
		} else {
			const d = pxToInX(e.clientX - tblDrag.startX);
			const pair = tblDrag.cols0[tblDrag.idx] + tblDrag.cols0[tblDrag.idx + 1];
			const cols = tblDrag.cols0.slice();
			cols[tblDrag.idx] = clamp(round2(tblDrag.cols0[tblDrag.idx] + d), 0.05, pair - 0.05);
			cols[tblDrag.idx + 1] = round2(pair - cols[tblDrag.idx]);
			item.col_w = cols;
		}
		styleNode(item.id);
		refreshProperties();
	}

	// ===================================================================
	//  Properties panel (live)
	// ===================================================================
	function refreshProperties() {
		const empty = document.getElementById('propEmpty');
		const panel = document.getElementById('propPanel');

		if (!selectedIds.length) { empty.style.display = 'block'; panel.style.display = 'none'; return; }
		empty.style.display = 'none';
		panel.style.display = 'block';

		const multi = selectedIds.length > 1;
		const item = byId(primaryId()); // primary drives the shown values
		const isText = item.type === 'text';
		const isLine = item.type === 'line';
		const isTable = item.type === 'table';
		const noFont = isLine || isTable;

		// Position & text only make sense for a single element.
		document.getElementById('propPosRow').style.display = multi ? 'none' : '';
		document.getElementById('propTextRow').style.display = (!multi && isText) ? 'block' : 'none';

		const isFreq = !multi && !isText && !noFont && item.field === 'qso.freq';
		document.getElementById('propFreqFormatRow').style.display = isFreq ? '' : 'none';
		if (isFreq) {
			document.getElementById('propFreqFormat').value = item.freq_format || 'MHz';
			document.getElementById('propFreqNoUnit').checked = !!item.freq_no_unit;
		}

		// Lines/tables have geometry controls instead of font properties.
		document.getElementById('propLineRow').style.display = (!multi && isLine) ? 'block' : 'none';
		document.getElementById('propTableRow').style.display = (!multi && isTable) ? 'block' : 'none';
		document.getElementById('propFontRow').style.display = noFont ? 'none' : '';
		document.getElementById('propFontMiscRow').style.display = noFont ? 'none' : '';
		document.getElementById('propWrapRow').style.display = noFont ? 'none' : '';
		// A table's row count is explicit — per-QSO repeating doesn't apply.
		document.getElementById('propRepeatRow').style.display = isTable ? 'none' : '';
		if (!multi && isLine) {
			document.getElementById('propLineOrient').value = item.orient === 'v' ? 'v' : 'h';
			document.getElementById('propLineLen').value = inToDisp(item.len_in ?? 1.5);
			document.getElementById('propLineThick').value = item.thick_pt ?? 0.5;
		}
		if (!multi && isTable) {
			document.getElementById('propTableRows').value = clamp(parseInt(item.rows, 10) || 3, 1, 20);
			document.getElementById('propTableCols').value = clamp(parseInt(item.cols, 10) || 3, 1, 12);
			document.getElementById('propTableW').value = inToDisp(item.w_in ?? 1.8);
			document.getElementById('propTableH').value = inToDisp(item.h_in ?? 0.5);
			document.getElementById('propTableThick').value = item.thick_pt ?? 0.4;
		}

		if (multi) {
			document.getElementById('propTypeBadge').textContent = selectedIds.length;
			document.getElementById('propTypeLabel').textContent = LANG.selected;
		} else {
			document.getElementById('propTypeBadge').textContent = isText ? LANG.customText : (isLine ? LANG.line : (isTable ? LANG.table : 'Field'));
			document.getElementById('propTypeLabel').textContent = (isText || isLine || isTable) ? '' : item.field;
			if (isText) document.getElementById('propText').value = item.text || '';
			document.getElementById('propX').value = inToDisp(item.x_in);
			document.getElementById('propY').value = inToDisp(item.y_in);
		}

		// Font / size / bold / wrap apply to all selected; show the primary's values.
		document.getElementById('propFont').value = item.font || 'Helvetica';
		document.getElementById('propFontSize').value = item.font_pt || 8;
		document.getElementById('propBold').checked = !!item.bold;
		document.getElementById('propColor').value = item.color || '#000000';
		document.getElementById('propWrap').value = inToDisp(item.wrap_w_in ?? 2.0);

		// "Repeats per QSO" applies to every selected element (like font/bold);
		// the checkbox reflects the primary's value.
		document.getElementById('propRepeatRow').style.display = '';
		document.getElementById('propRepeat').checked = !!item.repeat_per_qso;

		// "Disable snapping" applies to every selected element; reflect the primary.
		document.getElementById('propNoSnap').checked = !!item.no_snap;
	}

	// Keep X/Y inputs in sync while dragging a single element.
	function syncPropsPosition() {
		if (selectedIds.length !== 1) return;
		const item = byId(selectedIds[0]);
		if (!item) return;
		document.getElementById('propX').value = inToDisp(item.x_in);
		document.getElementById('propY').value = inToDisp(item.y_in);
	}

	// Wire a property input: `live` updates the element on each keystroke,
	// history is recorded once per committed change.
	function wireProp(elemId, apply) {
		const node = document.getElementById(elemId);
		let dirty = false;
		const run = commit => {
			if (!selectedIds.length) return;
			if (!dirty) { pushHistory(); dirty = true; }
			selectedIds.forEach(id => apply(byId(id), node));
			if (commit) dirty = false;
		};
		node.addEventListener('focus', () => { dirty = false; });
		node.addEventListener('input', () => run(false));
		node.addEventListener('change', () => run(true)); // checkboxes/selects commit here
	}

	wireProp('propText', (item, n) => { item.text = n.value; styleNode(item.id); });
	wireProp('propX', (item, n) => { item.x_in = clamp(dispToIn(parseFloat(n.value || '0')), 0, W_IN); positionNode(item.id); });
	wireProp('propY', (item, n) => { item.y_in = clamp(dispToIn(parseFloat(n.value || '0')), 0, H_IN); positionNode(item.id); });
	wireProp('propFont', (item, n) => { item.font = n.value; styleNode(item.id); });
	wireProp('propFontSize', (item, n) => { item.font_pt = clamp(parseInt(n.value || '8', 10), 4, 36); styleNode(item.id); });
	wireProp('propBold', (item, n) => { item.bold = n.checked; styleNode(item.id); });
	wireProp('propColor', (item, n) => { item.color = n.value; styleNode(item.id); });
	wireProp('propWrap', (item, n) => { item.wrap_w_in = Math.max(0.1, dispToIn(parseFloat(n.value || '2.0'))); });
	wireProp('propRepeat', (item, n) => { item.repeat_per_qso = n.checked; renderGhosts(item); });
	wireProp('propNoSnap', (item, n) => { item.no_snap = n.checked; });
	wireProp('propFreqFormat', (item, n) => { item.freq_format = n.value; });
	wireProp('propFreqNoUnit', (item, n) => { item.freq_no_unit = n.checked; });
	wireProp('propLineOrient', (item, n) => { item.orient = n.value; styleNode(item.id); });
	wireProp('propLineLen', (item, n) => { item.len_in = clamp(dispToIn(parseFloat(n.value || '1.5')), 0.05, 20); styleNode(item.id); });
	wireProp('propLineThick', (item, n) => { item.thick_pt = clamp(parseFloat(n.value || '0.5'), 0.1, 4); styleNode(item.id); });
	wireProp('propTableRows', (item, n) => { item.rows = clamp(parseInt(n.value || '3', 10), 1, 20); styleNode(item.id); });
	wireProp('propTableCols', (item, n) => {
		item.cols = clamp(parseInt(n.value || '3', 10), 1, 12);
		// Column count changed: redistribute the width equally
		item.col_w = tableCols({ ...item, col_w: null });
		styleNode(item.id);
		syncTableHandles();
	});
	wireProp('propTableW', (item, n) => {
		const w = clamp(dispToIn(parseFloat(n.value || '1.8')), 0.2, 20);
		const sum = tableCols(item).reduce((a, b) => a + b, 0);
		const ratio = sum > 0 ? w / sum : 1;   // keep the column proportions
		item.w_in = w;
		item.col_w = tableCols(item).map(cw => round2(cw * ratio));
		styleNode(item.id);
	});
	wireProp('propTableH', (item, n) => { item.h_in = clamp(dispToIn(parseFloat(n.value || '0.5')), 0.1, 20); styleNode(item.id); });
	wireProp('propTableThick', (item, n) => { item.thick_pt = clamp(parseFloat(n.value || '0.4'), 0.1, 4); styleNode(item.id); });

	document.getElementById('btnDuplicate').addEventListener('click', duplicateSelected);
	document.getElementById('btnDeleteElem').addEventListener('click', deleteSelected);

	// ===== Template options (not undo-tracked, like calibration offsets) =====
	function applyTplOptionsToControls() {
		document.getElementById('tplQsosPerLabel').value = tplOptions.qsos_per_label;
		document.getElementById('tplRowPitch').value = inToDisp(tplOptions.row_pitch_in);
		document.getElementById('tplRowSeparators').checked = !!tplOptions.row_separators;
		document.getElementById('tplSepThick').value = tplOptions.sep_thick_pt;
	}

	// Show "Row spacing" only for multi-QSO labels.
	function setPitchWrapVisibility() {
		document.getElementById('tplPitchWrap').style.display = tplOptions.qsos_per_label > 1 ? '' : 'none';
	}

	// Called when QSOs/label or row spacing change via the controls: rebuild ghost rows.
	function updateRepeatVisibility() {
		setPitchWrapVisibility();
		renderAll();
	}

	function wireTpl(elemId, key, cast, onChange) {
		const node = document.getElementById(elemId);
		const apply = () => { tplOptions[key] = cast(node); if (onChange) onChange(); };
		node.addEventListener('input', apply);
		node.addEventListener('change', apply);
	}

	wireTpl('tplQsosPerLabel', 'qsos_per_label', n => Math.max(1, parseInt(n.value, 10) || 1), updateRepeatVisibility);
	wireTpl('tplRowPitch',     'row_pitch_in',    n => Math.max(0.05, dispToIn(parseFloat(n.value) || 0.3)), updateRepeatVisibility);
	wireTpl('tplRowSeparators', 'row_separators', n => n.checked, () => {
		document.getElementById('tplSepThickWrap').style.display = tplOptions.row_separators ? '' : 'none';
	});
	wireTpl('tplSepThick', 'sep_thick_pt', n => Math.max(0.1, Math.min(4, parseFloat(n.value) || 0.4)));

	// ===================================================================
	//  Element actions
	// ===================================================================
	function duplicateSelected() {
		if (!selectedIds.length) return;
		pushHistory();
		const copies = [];
		selectedIds.forEach(id => {
			const item = byId(id);
			const copy = JSON.parse(JSON.stringify(item));
			copy.id = newId();
			copy.x_in = clamp(item.x_in + 0.1, 0, W_IN);
			copy.y_in = clamp(item.y_in + 0.1, 0, H_IN);
			elements.push(copy);
			renderElement(copy);
			copies.push(copy.id);
		});
		setSelection(copies);
	}

	function deleteSelected() {
		if (!selectedIds.length) return;
		pushHistory();
		selectedIds.forEach(id => {
			stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + id + '"]').forEach(g => g.remove());
			const n = nodeById(id); if (n) n.remove();
		});
		elements = elements.filter(e => !isSelected(e.id));
		deselect();
	}

	function moveToFront() {
		if (!selectedIds.length) return;
		pushHistory();
		const sel = elements.filter(e => isSelected(e.id));
		elements = elements.filter(e => !isSelected(e.id)).concat(sel);
		renderAll();
	}

	function moveToBack() {
		if (!selectedIds.length) return;
		pushHistory();
		const sel = elements.filter(e => isSelected(e.id));
		elements = sel.concat(elements.filter(e => !isSelected(e.id)));
		renderAll();
	}

	// ===================================================================
	//  Align & distribute (multi-selection)
	// ===================================================================
	function elemBox(id) {
		const it = byId(id), n = nodeById(id);
		return { it: it, x: inToPxX(it.x_in), y: inToPxY(it.y_in), w: n ? n.offsetWidth : 0, h: n ? n.offsetHeight : 0 };
	}

	function alignSelected(action) {
		if (selectedIds.length < 2) return;
		pushHistory();

		const boxes = selectedIds.map(elemBox);
		const minL = Math.min(...boxes.map(b => b.x));
		const maxR = Math.max(...boxes.map(b => b.x + b.w));
		const minT = Math.min(...boxes.map(b => b.y));
		const maxB = Math.max(...boxes.map(b => b.y + b.h));
		const cx = (minL + maxR) / 2, cy = (minT + maxB) / 2;

		const setX = (b, px) => { b.it.x_in = pxToInX(clamp(px, 0, stageWpx - b.w)); };
		const setY = (b, py) => { b.it.y_in = pxToInY(clamp(py, 0, stageHpx - b.h)); };

		switch (action) {
			case 'left':    boxes.forEach(b => setX(b, minL)); break;
			case 'right':   boxes.forEach(b => setX(b, maxR - b.w)); break;
			case 'hcenter': boxes.forEach(b => setX(b, cx - b.w / 2)); break;
			case 'top':     boxes.forEach(b => setY(b, minT)); break;
			case 'bottom':  boxes.forEach(b => setY(b, maxB - b.h)); break;
			case 'vcenter': boxes.forEach(b => setY(b, cy - b.h / 2)); break;
			case 'dist-h':  distribute(boxes, 'x', 'w', setX); break;
			case 'dist-v':  distribute(boxes, 'y', 'h', setY); break;
			case 'page-h': {
				const dx = clamp(stageWpx / 2 - cx, -minL, stageWpx - maxR);
				boxes.forEach(b => { b.it.x_in = pxToInX(b.x + dx); });
				break;
			}
			case 'page-v': {
				const dy = clamp(stageHpx / 2 - cy, -minT, stageHpx - maxB);
				boxes.forEach(b => { b.it.y_in = pxToInY(b.y + dy); });
				break;
			}
		}

		selectedIds.forEach(positionNode);
		syncPropsPosition();
	}

	// Evenly space the inner elements' centers between the two outermost ones.
	function distribute(boxes, posKey, sizeKey, setter) {
		if (boxes.length < 3) return;
		const sorted = boxes.slice().sort((a, b) => (a[posKey] + a[sizeKey] / 2) - (b[posKey] + b[sizeKey] / 2));
		const firstC = sorted[0][posKey] + sorted[0][sizeKey] / 2;
		const lastC = sorted[sorted.length - 1][posKey] + sorted[sorted.length - 1][sizeKey] / 2;
		const step = (lastC - firstC) / (sorted.length - 1);
		sorted.forEach((b, i) => {
			if (i === 0 || i === sorted.length - 1) return;
			setter(b, (firstC + step * i) - b[sizeKey] / 2);
		});
	}

	// ===================================================================
	//  Context menu
	// ===================================================================
	function openCtxMenu(clientX, clientY) {
		// Align/distribute only make sense with two or more elements selected.
		const multi = selectedIds.length > 1;
		ctxMenu.querySelectorAll('[data-multi-only]').forEach(el => el.style.display = multi ? '' : 'none');

		ctxMenu.style.display = 'block';
		const mw = ctxMenu.offsetWidth, mh = ctxMenu.offsetHeight;
		ctxMenu.style.left = Math.min(clientX, window.innerWidth - mw - 4) + 'px';
		ctxMenu.style.top = Math.min(clientY, window.innerHeight - mh - 4) + 'px';
	}

	function closeCtxMenu() {
		ctxMenu.style.display = 'none';
	}

	ctxMenu.querySelectorAll('.qsl-ctx-item').forEach(btn => {
		btn.addEventListener('click', e => {
			const action = btn.dataset.action;
			if (!action) { e.stopPropagation(); return; } // submenu opener, do nothing
			closeCtxMenu();
			if (action.startsWith('align:')) alignSelected(action.slice(6));
			else if (action === 'edit') document.getElementById('propFont').focus();
			else if (action === 'duplicate') duplicateSelected();
			else if (action === 'front') moveToFront();
			else if (action === 'back') moveToBack();
			else if (action === 'delete') deleteSelected();
		});
	});

	// Right-click on a field opens the menu; right-click on empty canvas does
	// nothing (and never shows the browser's default menu over the stage).
	stage.addEventListener('contextmenu', e => {
		e.preventDefault();
		const node = e.target.closest('.qsl_designer_placed');
		if (!node) { closeCtxMenu(); return; }
		if (!isSelected(node.dataset.id)) selectOne(node.dataset.id);
		openCtxMenu(e.clientX, e.clientY);
	});

	document.addEventListener('mousedown', e => {
		if (!ctxMenu.contains(e.target)) closeCtxMenu();
	});
	window.addEventListener('blur', closeCtxMenu);

	// ===================================================================
	//  Palette: click-to-add + drag & drop
	// ===================================================================
	document.querySelectorAll('.qsl_designer_field').forEach(f => {
		f.addEventListener('click', () => {
			const spot = freeSpot();
			addElement('field', f.dataset.field, pxToInX(spot.x), pxToInY(spot.y));
		});
		f.addEventListener('dragstart', e => {
			e.dataTransfer.setData('text/wl-field', f.dataset.field);
			e.dataTransfer.effectAllowed = 'copy';
		});
	});

	stage.addEventListener('dragover', e => {
		if (e.dataTransfer.types.includes('text/wl-field')) {
			e.preventDefault();
			e.dataTransfer.dropEffect = 'copy';
		}
	});

	stage.addEventListener('drop', e => {
		const field = e.dataTransfer.getData('text/wl-field');
		if (!field) return;
		e.preventDefault();
		const p = clientToStagePx(e.clientX, e.clientY);
		const x = clamp(Math.round(p.x / GRID_PX) * GRID_PX, 0, stageWpx - 6);
		const y = clamp(Math.round(p.y / GRID_PX) * GRID_PX, 0, stageHpx - 6);
		addElement('field', field, pxToInX(x), pxToInY(y));
	});

	document.getElementById('btnAddText').addEventListener('click', () => {
		const spot = freeSpot();
		addElement('text', LANG.customText, pxToInX(spot.x), pxToInY(spot.y));
		document.getElementById('propText').focus();
		document.getElementById('propText').select();
	});

	// Ruled lines (grid separators between QSO details)
	document.getElementById('btnAddLineH').addEventListener('click', () => {
		const spot = freeSpot();
		addElement('line', 'h', pxToInX(spot.x), pxToInY(spot.y));
	});
	document.getElementById('btnAddLineV').addEventListener('click', () => {
		const spot = freeSpot();
		addElement('line', 'v', pxToInX(spot.x), pxToInY(spot.y));
	});

	// Table grid (rows × columns, resizable whole and per column)
	document.getElementById('btnAddTable').addEventListener('click', () => {
		const spot = freeSpot();
		addElement('table', null, pxToInX(spot.x), pxToInY(spot.y));
	});

	// Field search filter
	document.getElementById('fieldSearch').addEventListener('input', e => {
		const q = e.target.value.trim().toLowerCase();
		let anyVisible = false;
		document.querySelectorAll('.qsl-cat').forEach(cat => {
			let catVisible = false;
			cat.querySelectorAll('.qsl_designer_field').forEach(f => {
				const match = f.dataset.field.toLowerCase().includes(q);
				f.style.display = match ? '' : 'none';
				if (match) { catVisible = true; anyVisible = true; }
			});
			cat.style.display = catVisible ? '' : 'none';
			if (q && catVisible) cat.open = true;
		});
		document.getElementById('fieldSearchEmpty').style.display = anyVisible ? 'none' : 'block';
	});

	// ===================================================================
	//  Marquee (rubber-band) multi-selection
	// ===================================================================
	stage.addEventListener('mousedown', e => {
		if (e.button !== 0 || e.target !== stage) return; // only the empty canvas area
		e.preventDefault();
		blurActiveControl();
		const p = clientToStagePx(e.clientX, e.clientY);
		const additive = e.shiftKey || e.ctrlKey || e.metaKey;
		if (!additive) setSelection([]);
		const node = document.createElement('div');
		node.className = 'qsl-marquee';
		stage.appendChild(node);
		marquee = { x0: p.x, y0: p.y, base: additive ? selectedIds.slice() : [], node: node };
	});

	function onMarqueeMove(e) {
		const p = clientToStagePx(e.clientX, e.clientY);
		const x = Math.min(p.x, marquee.x0), y = Math.min(p.y, marquee.y0);
		const w = Math.abs(p.x - marquee.x0), h = Math.abs(p.y - marquee.y0);
		marquee.node.style.left = x + 'px';
		marquee.node.style.top = y + 'px';
		marquee.node.style.width = w + 'px';
		marquee.node.style.height = h + 'px';

		const ids = marquee.base.slice();
		elementsInRect(x, y, w, h).forEach(id => { if (!ids.includes(id)) ids.push(id); });
		selectedIds = ids;
		syncSelection();
	}

	function onMarqueeEnd() {
		marquee.node.remove();
		marquee = null;
		refreshProperties();
	}

	// ids of elements whose box overlaps the given stage-px rectangle
	function elementsInRect(x, y, w, h) {
		const rx2 = x + w, ry2 = y + h;
		return elements.filter(it => {
			const n = nodeById(it.id);
			const ex = inToPxX(it.x_in), ey = inToPxY(it.y_in);
			const ex2 = ex + (n ? n.offsetWidth : 0), ey2 = ey + (n ? n.offsetHeight : 0);
			return ex < rx2 && ex2 > x && ey < ry2 && ey2 > y;
		}).map(it => it.id);
	}

	// ===================================================================
	//  Keyboard shortcuts
	// ===================================================================
	document.addEventListener('keydown', e => {
		const tag = (e.target.tagName || '').toLowerCase();
		const typing = tag === 'input' || tag === 'textarea' || tag === 'select';
		const meta = e.ctrlKey || e.metaKey;

		if (meta && e.key.toLowerCase() === 'z' && !typing) { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
		if (meta && e.key.toLowerCase() === 'y' && !typing) { e.preventDefault(); redo(); return; }
		if (typing) return;

		if (meta && e.key.toLowerCase() === 'a') { e.preventDefault(); setSelection(elements.map(el => el.id)); return; }
		if (e.key === 'Escape') { deselect(); closeCtxMenu(); return; }
		if (!selectedIds.length) return;

		if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); deleteSelected(); return; }
		if (meta && e.key.toLowerCase() === 'd') { e.preventDefault(); duplicateSelected(); return; }

		const arrows = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
		if (arrows[e.key]) {
			e.preventDefault();
			const [dx, dy] = arrows[e.key];
			const stepIn = e.shiftKey ? GRID_IN : pxToInX(2);
			pushHistory();
			selectedIds.forEach(id => {
				const item = byId(id);
				item.x_in = clamp(item.x_in + dx * stepIn, 0, W_IN);
				item.y_in = clamp(item.y_in + dy * stepIn, 0, H_IN);
				positionNode(id);
			});
			syncPropsPosition();
		}
	});

	document.getElementById('btnUndo').addEventListener('click', undo);
	document.getElementById('btnRedo').addEventListener('click', redo);

	// ===================================================================
	//  Zoom
	// ===================================================================
	function setZoom(z) {
		zoom = clamp(Math.round(z * 100) / 100, ZOOM_MIN, ZOOM_MAX);
		rulerWrap.style.transformOrigin = 'top left';
		rulerWrap.style.transform = 'scale(' + zoom + ')';
		stageZoom.style.width = (rulerWrap.offsetWidth * zoom) + 'px';
		stageZoom.style.height = (rulerWrap.offsetHeight * zoom) + 'px';
		document.getElementById('zoomLabel').textContent = Math.round(zoom * 100) + '%';
		prefSet('zoom', zoom);
	}

	document.getElementById('btnZoomIn').addEventListener('click', () => setZoom(zoom + ZOOM_STEP));
	document.getElementById('btnZoomOut').addEventListener('click', () => setZoom(zoom - ZOOM_STEP));
	document.getElementById('btnZoomReset').addEventListener('click', () => setZoom(1));

	stageScroll.addEventListener('wheel', e => {
		if (!e.ctrlKey && !e.metaKey) return;
		e.preventDefault();
		setZoom(zoom + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
	}, { passive: false });

	// ===================================================================
	//  Rulers
	// ===================================================================
	function drawRulers() {
		const top = document.getElementById('rulerTop');
		const left = document.getElementById('rulerLeft');
		top.innerHTML = '';
		left.innerHTML = '';

		const pxPerInX = stageWpx / W_IN;
		const pxPerInY = stageHpx / H_IN;

		if (METRIC) {
			// Centimeter ruler: major tick + number every 1 cm, minor every 0.5 cm.
			const pxPerCmX = pxPerInX / 2.54;
			const pxPerCmY = pxPerInY / 2.54;
			const wCm = W_IN * 2.54, hCm = H_IN * 2.54;

			for (let i = 0, n = Math.ceil(wCm * 2); i <= n; i++) {
				const cm = i * 0.5;
				const x = cm * pxPerCmX;
				const tick = document.createElement('div');
				tick.className = 'ruler-tick-top ' + (i % 2 === 0 ? 'major' : 'minor');
				tick.style.left = x + 'px';
				top.appendChild(tick);
				if (i % 2 === 0) {
					const label = document.createElement('div');
					label.className = 'ruler-label-top';
					label.style.left = (x + 3) + 'px';
					label.textContent = cm;
					top.appendChild(label);
				}
			}
			for (let i = 0, n = Math.ceil(hCm * 2); i <= n; i++) {
				const cm = i * 0.5;
				const y = cm * pxPerCmY;
				const tick = document.createElement('div');
				tick.className = 'ruler-tick-left ' + (i % 2 === 0 ? 'major' : 'minor');
				tick.style.top = y + 'px';
				left.appendChild(tick);
				if (i % 2 === 0) {
					const label = document.createElement('div');
					label.className = 'ruler-label-left';
					label.style.top = (y - 6) + 'px';
					label.textContent = cm;
					left.appendChild(label);
				}
			}
			return;
		}

		// Inch ruler: major tick + label every 1 in, minor every 0.125 in.
		const minorPerIn = Math.round(1 / GRID_IN); // grid and ruler share the same division
		for (let i = 0; i <= W_IN * minorPerIn; i++) {
			const x = i * (pxPerInX / minorPerIn);
			const tick = document.createElement('div');
			tick.className = 'ruler-tick-top ' + (i % minorPerIn === 0 ? 'major' : 'minor');
			tick.style.left = x + 'px';
			top.appendChild(tick);
			if (i % minorPerIn === 0) {
				const label = document.createElement('div');
				label.className = 'ruler-label-top';
				label.style.left = (x + 3) + 'px';
				label.textContent = (i / minorPerIn) + '"';
				top.appendChild(label);
			}
		}

		for (let i = 0; i <= H_IN * minorPerIn; i++) {
			const y = i * (pxPerInY / minorPerIn);
			const tick = document.createElement('div');
			tick.className = 'ruler-tick-left ' + (i % minorPerIn === 0 ? 'major' : 'minor');
			tick.style.top = y + 'px';
			left.appendChild(tick);
			if (i % minorPerIn === 0) {
				const label = document.createElement('div');
				label.className = 'ruler-label-left';
				label.style.top = (y - 6) + 'px';
				label.textContent = (i / minorPerIn) + '"';
				left.appendChild(label);
			}
		}
	}

	// ===================================================================
	//  Template load / save / delete
	// ===================================================================
	function buildLayout() {
		return {
			page: { w_in: W_IN, h_in: H_IN },   // informational; the label type is authoritative
			calibration: {
				offset_x_in: dispToIn(parseFloat(offXInput.value || '0')),
				offset_y_in: dispToIn(parseFloat(offYInput.value || '0')),
			},
			options: tplOptions,
			elements: elements,
		};
	}

	async function loadTemplate(id) {
		const r = await fetch(base_url + 'index.php/labeldesigner/get_template/' + id);
		const tpl = await r.json();
		const layout = tpl.layout || {};

		// Apply the template's label type (geometry) before the elements so the
		// canvas is already the right size when they land on it. A template whose
		// label type was deleted still loads (positions kept) for rescue/re-target.
		const ltid = String(tpl.label_type_id || '');
		if (ltid && labelTypeSelect.querySelector('option[value="' + ltid + '"]')) {
			labelTypeSelect.value = ltid;
			applyLabelType(ltid, false);
		} else {
			labelTypeSelect.value = '';
			applyLabelType('', false);
			showToast(LANG.error, LANG.labelTypeMissing, 'bg-warning text-dark', 6000);
		}

		offXInput.value = inToDisp(layout.calibration?.offset_x_in ?? 0);
		offYInput.value = inToDisp(layout.calibration?.offset_y_in ?? 0);

		const o = layout.options || {};
		tplOptions = {
			qsos_per_label: Math.max(1, parseInt(o.qsos_per_label, 10) || 1),
			row_pitch_in: parseFloat(o.row_pitch_in) || 0.3,
			row_separators: !!o.row_separators,
			sep_thick_pt: parseFloat(o.sep_thick_pt) || 0.4,
		};
		applyTplOptionsToControls();
		setPitchWrapVisibility();
		document.getElementById('tplSepThickWrap').style.display = tplOptions.row_separators ? '' : 'none';

		elements = (layout.elements || []).map(el => ({
			id: el.id || newId(),
			type: el.type || 'field',
			field: el.field,
			text: el.text,
			x_in: el.x_in || 0,
			y_in: el.y_in || 0,
			font: el.font || 'Helvetica',
			font_pt: el.font_pt || 8,
			bold: !!el.bold,
			color: el.color || '#000000',
			wrap_w_in: el.wrap_w_in ?? 2.0,
			repeat_per_qso: !!el.repeat_per_qso,
			no_snap: !!el.no_snap,
			freq_format: el.freq_format || 'MHz',
			freq_no_unit: !!el.freq_no_unit,
			orient: el.orient === 'v' ? 'v' : 'h',
			len_in: el.len_in ?? 1.5,
			thick_pt: el.thick_pt ?? 0.5,
			cols: parseInt(el.cols, 10) || 3,
			rows: parseInt(el.rows, 10) || 3,
			w_in: el.w_in ?? 1.8,
			h_in: el.h_in ?? 0.5,
			col_w: Array.isArray(el.col_w) ? el.col_w.map(v => parseFloat(v) || 0) : null,
		}));

		history.length = 0;
		future.length = 0;
		updateHistoryButtons();
		deselect();
		renderAll();
	}

	// ===== Work-in-progress (dirty) tracking =====
	// Snapshot of the layout in its last "clean" state — right after being loaded,
	// saved, or reset to blank. The canvas has unsaved changes whenever its current
	// state diverges from this snapshot. That stops an accidental template dropdown
	// selection from silently discarding in-progress work (see change handler below).
	function currentLayoutSig() {
		return JSON.stringify({
			elements: elements,
			offX: dispToIn(parseFloat(offXInput.value || '0')),
			offY: dispToIn(parseFloat(offYInput.value || '0')),
			options: tplOptions,
			label_type_id: labelTypeSelect.value,
		});
	}
	let cleanLayoutSig = currentLayoutSig();
	function markClean() { cleanLayoutSig = currentLayoutSig(); }
	function hasUnsavedChanges() { return currentLayoutSig() !== cleanLayoutSig; }

	// The template id currently reflected on the canvas. If the user cancels a
	// "discard unsaved changes?" prompt we revert the dropdown back to this value.
	let confirmedTplId = '';

	// Apply a dropdown selection (blank or a saved template), bypassing the
	// unsaved-WIP guard. The canvas ends in a clean state, so we markClean() here.
	async function applyTemplateSelection(id) {
		prefSet('tpl', id);
		if (!id) {
			// "(new)" — start a blank canvas; keep the last-used label type so the
			// stage keeps its shape.
			elements = [];
			document.getElementById('tplName').value = '';
			document.getElementById('btnPdf').href = '#';
			document.getElementById('btnPdfSave').href = '#';
			tplOptions = { ...DEFAULT_TPL_OPTIONS };
			applyTplOptionsToControls();
			setPitchWrapVisibility();
			history.length = 0; future.length = 0; updateHistoryButtons();
			deselect();
			renderAll();
		} else {
			document.getElementById('tplName').value = tplSelect.options[tplSelect.selectedIndex].text;
			document.getElementById('btnPdf').href = base_url + 'index.php/labeldesigner/pdf/' + id;
			document.getElementById('btnPdfSave').href = base_url + 'index.php/labeldesigner/pdf/' + id + '?download=1';
			await loadTemplate(id);
		}
		confirmedTplId = String(id);
		markClean();
	}

	tplSelect.addEventListener('change', e => {
		const id = e.target.value;
		// An accidental selection here would silently discard unsaved work — confirm first.
		if (hasUnsavedChanges()) {
			BootstrapDialog.confirm({
				title: LANG.unsavedChangesTitle,
				message: LANG.unsavedChangesConfirm,
				type: BootstrapDialog.TYPE_WARNING,
				closable: true,
				draggable: true,
				btnOKClass: 'btn-warning',
				btnOKLabel: LANG.discardChanges,
				btnCancelLabel: LANG.keepEditing,
				callback: result => {
					if (!result) { tplSelect.value = confirmedTplId; return; } // revert dropdown, keep WIP
					applyTemplateSelection(id);
				},
			});
			return;
		}
		applyTemplateSelection(id);
	});

	document.getElementById('btnSave').addEventListener('click', async () => {
		// The template's geometry comes from the label type — without one there is
		// nothing sensible to save or render.
		const ltid = labelTypeSelect.value;
		if (!ltid) {
			showToast(LANG.error, LANG.selectLabelTypeFirst, 'bg-danger text-white', 5000);
			return;
		}

		const id = parseInt(tplSelect.value || '0', 10);
		const name = document.getElementById('tplName').value || LANG.untitled;
		const payload = { id: id, name: name, label_type_id: parseInt(ltid, 10), layout: buildLayout() };

		const r = await fetch(base_url + 'index.php/labeldesigner/save_template', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload),
		});
		const out = await r.json();
		if (!out.ok) { showToast(LANG.error, out.error || LANG.saveFailed, 'bg-danger text-white', 5000); return; }

		// Reflect the saved template in the dropdown without a page reload.
		const newId = out.id || id;
		let opt = tplSelect.querySelector('option[value="' + newId + '"]');
		if (!opt) {
			opt = document.createElement('option');
			opt.value = newId;
			tplSelect.appendChild(opt);
		}
		opt.textContent = name;
		tplSelect.value = newId;
		document.getElementById('btnPdf').href = base_url + 'index.php/labeldesigner/pdf/' + newId;
		document.getElementById('btnPdfSave').href = base_url + 'index.php/labeldesigner/pdf/' + newId + '?download=1';
		confirmedTplId = String(newId);
		markClean(); // saved state matches the canvas
		showToast(LANG.success, LANG.saved, 'bg-success text-white', 4000);
	});

	document.getElementById('btnDelete').addEventListener('click', () => {
		const id = tplSelect.value || 0;
		if (!id) { showToast(LANG.error, LANG.selectTemplateToDelete, 'bg-danger text-white', 5000); return; }
		BootstrapDialog.confirm({
			title: LANG.deleteTemplate,
			message: LANG.deleteTemplateConfirm,
			type: BootstrapDialog.TYPE_DANGER,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-danger',
			callback: async function (result) {
				if (!result) return;
				const r = await fetch(base_url + 'index.php/labeldesigner/delete_template', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id: parseInt(id, 10) }),
				});
				const out = await r.json();
				if (!out.ok) { showToast(LANG.error, LANG.deleteFailed, 'bg-danger text-white', 5000); return; }
				showToast(LANG.success, LANG.deleteSuccess, 'bg-success text-white', 5000);
				tplSelect.querySelector('option[value="' + id + '"]')?.remove();
				tplSelect.value = '';
				confirmedTplId = '';
				markClean(); // deletion already confirmed — don't re-prompt on the blank reset
				tplSelect.dispatchEvent(new Event('change'));
			},
		});
	});

	document.getElementById('btnCopy').addEventListener('click', async () => {
		const id = parseInt(tplSelect.value || '0', 10);
		if (!id) { showToast(LANG.error, LANG.selectTemplateToCopy, 'bg-danger text-white', 5000); return; }

		const r = await fetch(base_url + 'index.php/labeldesigner/copy_template', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ id: id }),
		});
		const out = await r.json();
		if (!out.ok) { showToast(LANG.error, out.error || LANG.copyFailed, 'bg-danger text-white', 5000); return; }

		// Reflect the new copy in the dropdown (appended; list re-sorts by
		// updated_at on next page load).
		const opt = document.createElement('option');
		opt.value = out.id;
		opt.textContent = out.name || '(copy)';
		tplSelect.appendChild(opt);

		// If the canvas has no unsaved work, switch to the copy so the user can
		// edit it right away. With dirty work in progress, leave the canvas alone
		// — switching would silently drop those edits.
		if (!hasUnsavedChanges()) {
			tplSelect.value = out.id;
			await applyTemplateSelection(out.id);
		}
		showToast(LANG.success, LANG.copySuccess, 'bg-success text-white', 4000);
	});

	document.getElementById('btnExport').addEventListener('click', async () => {
		const id = parseInt(tplSelect.value || '0', 10);
		if (!id) { showToast(LANG.error, LANG.selectTemplateToExport, 'bg-danger text-white', 5000); return; }

		// Fetch as a blob so server-side errors surface as a toast instead of
		// navigating the browser to an error page.
		const r = await fetch(base_url + 'index.php/labeldesigner/export_template/' + id);
		if (!r.ok) { showToast(LANG.error, LANG.exportFailed, 'bg-danger text-white', 5000); return; }

		const blob = await r.blob();
		const cd = r.headers.get('Content-Disposition') || '';
		const m = cd.match(/filename="([^"]+)"/);
		const a = Object.assign(document.createElement('a'), {
			href: URL.createObjectURL(blob),
			download: m ? m[1] : 'label_template.json',
		});
		a.click();
		// Safari starts the download asynchronously — revoking immediately can abort it.
		setTimeout(() => URL.revokeObjectURL(a.href), 1000);
	});

	document.getElementById('btnImport').addEventListener('click', () => {
		document.getElementById('importFile').click();
	});

	document.getElementById('importFile').addEventListener('change', async () => {
		const input = document.getElementById('importFile');
		const f = input.files && input.files[0];
		input.value = ''; // allow re-selecting the same file later
		if (!f) return;

		if (f.size > 256 * 1024) {
			showToast(LANG.error, LANG.fileTooLarge, 'bg-danger text-white', 5000);
			return;
		}

		let out;
		try {
			const r = await fetch(base_url + 'index.php/labeldesigner/import_template', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: await f.text(),
			});
			out = await r.json();
		} catch (_) {
			showToast(LANG.error, LANG.importFailed, 'bg-danger text-white', 5000);
			return;
		}
		if (!out.ok) { showToast(LANG.error, out.error || LANG.importFailed, 'bg-danger text-white', 5000); return; }

		// Register the (possibly new) label type so loadTemplate() can select it.
		const lt = out.label_type;
		if (lt && !labelTypeSelect.querySelector('option[value="' + String(lt.id) + '"]')) {
			const o = document.createElement('option');
			o.value = lt.id;
			o.textContent = lt.name + (lt.has_paper ? '' : ' — ' + LANG.noPaperAssigned);
			labelTypeSelect.appendChild(o);
			LABEL_TYPES[lt.id] = {
				w_in: lt.w_in, h_in: lt.h_in, nx: lt.nx, ny: lt.ny,
				name: lt.name, has_paper: lt.has_paper,
			};
		}

		const opt = document.createElement('option');
		opt.value = out.id;
		opt.textContent = out.name || LANG.untitled;
		tplSelect.appendChild(opt);

		// Same behaviour as Copy: switch to the imported template unless the
		// canvas holds unsaved work.
		if (!hasUnsavedChanges()) {
			tplSelect.value = out.id;
			await applyTemplateSelection(out.id);
		}
		showToast(LANG.success, LANG.importSuccess, 'bg-success text-white', 4000);
	});

	// ===================================================================
	//  Init
	// ===================================================================
	// Size the stage (restores the last-used label type on a blank canvas).
	const savedLabelType = prefGet('labeltype', '');
	if (savedLabelType && labelTypeSelect.querySelector('option[value="' + savedLabelType + '"]')) {
		labelTypeSelect.value = savedLabelType;
	}
	applyLabelType(labelTypeSelect.value, false);
	applyTplOptionsToControls();
	setPitchWrapVisibility();
	updateHistoryButtons();

	// Restore persisted zoom.
	const savedZoom = parseFloat(prefGet('zoom', '1'));
	setZoom(isNaN(savedZoom) ? 1 : savedZoom);

	// Restore last-selected template (only if it still exists in the list).
	const savedTpl = prefGet('tpl', '');
	if (savedTpl && tplSelect.querySelector('option[value="' + savedTpl + '"]')) {
		tplSelect.value = savedTpl;
		// The label-type restore above changed the canvas state AFTER the
		// initial clean snapshot was taken (label_type_id now differs), which
		// would make the unsaved-changes guard misfire and block this
		// programmatic load. Re-snapshot so the baseline includes the restore.
		markClean();
		tplSelect.dispatchEvent(new Event('change'));
	} else {
		// Blank canvas with a restored label type — same baseline refresh.
		markClean();
	}

	// ===== Leave-with-unsaved-changes guard =====
	// Two layers, because the browser won't wait on an async modal during
	// beforeunload:
	//  1. A translated BootstrapDialog intercepts clicks on in-app links (the
	//     normal way people leave this page) — that we *can* block via
	//     preventDefault and resolve async.
	//  2. A native beforeunload fallback still catches tab close, refresh, and
	//     URL-bar edits, which a custom modal physically cannot intercept.
	let leaving = false;

	function confirmLeavePage(href) {
		BootstrapDialog.confirm({
			title: LANG.unsavedChangesTitle,
			message: LANG.unsavedLeaveConfirm,
			type: BootstrapDialog.TYPE_WARNING,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-warning',
			btnOKLabel: LANG.leavePage,
			btnCancelLabel: LANG.keepEditing,
			callback: result => {
				if (!result) return;
				leaving = true; // suppress the native fallback below
				window.location.href = href;
			},
		});
	}

	// Intercept clicks on links that leave the designer (header nav, logo, etc.).
	// Skip new-tab/modifier opens, downloads, target=_blank, and links that stay
	// on this page — none of those unload the canvas.
	document.addEventListener('click', e => {
		if (leaving || e.defaultPrevented || !hasUnsavedChanges()) return;
		if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
		const a = e.target.closest('a');
		if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download')) return;
		let url;
		try { url = new URL(a.href, window.location.href); } catch (_) { return; }
		if (url.pathname === window.location.pathname && url.search === window.location.search) return;
		e.preventDefault();
		confirmLeavePage(a.href);
	});

	// Fallback for tab close, refresh, and URL-bar navigation — the only unload
	// paths a custom modal can't intercept. Browsers show their own generic
	// "leave site?" wording and ignore custom text here.
	window.addEventListener('beforeunload', e => {
		if (leaving || !hasUnsavedChanges()) return;
		e.preventDefault();
		e.returnValue = '';
	});
})();
