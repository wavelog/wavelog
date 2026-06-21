/**
 * QSL Postcard Designer — frontend editor.
 *
 * Vanilla JS, no framework. Backend contract is unchanged: the layout JSON it
 * builds/consumes uses the same schema the PDF renderer expects
 * (page / calibration / elements[{id,type,field|text,x_in,y_in,font,font_pt,bold,wrap_w_in}]).
 */
(function () {
	'use strict';

	// ===== Constants =====
	const STAGE_W_PX = 900;
	const STAGE_H_PX = 600;
	const W_IN = 5.5;
	const H_IN = 3.5;
	const GRID_IN = 0.25;                       // snap grid (quarter inch)
	const GRID_PX = (GRID_IN / W_IN) * STAGE_W_PX; // 37.5px
	const SNAP_PX = 8;                          // snap threshold (internal px)
	const ZOOM_MIN = 0.5, ZOOM_MAX = 2.0, ZOOM_STEP = 0.1;
	const HISTORY_MAX = 100;

	// ===== Coordinate helpers =====
	const pxToInX = px => (px / STAGE_W_PX) * W_IN;
	const pxToInY = py => (py / STAGE_H_PX) * H_IN;
	const inToPxX = ix => (ix / W_IN) * STAGE_W_PX;
	const inToPxY = iy => (iy / H_IN) * STAGE_H_PX;
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
	let previewImagePath = null;
	let previewImageUrl = null;

	// Template-wide options (persisted in layout.options; see buildLayout/loadTemplate).
	// Single source of truth for a fresh/blank template; loadTemplate overlays saved
	// values on top of this (with its own coercion for legacy/malformed JSON).
	const DEFAULT_TPL_OPTIONS = Object.freeze({
		qsos_per_card: 1,
		print_background: false,
		skip_address: true,
		row_pitch_in: 0.3,
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

	const byId    = id => elements.find(e => e.id === id);
	const nodeById = id => stage.querySelector('.qsl_designer_placed[data-id="' + id + '"]');

	// ===== Persisted UI preferences (localStorage) =====
	const LS_PREFIX = 'wl_qslpostcard_';
	function prefGet(key, fallback) {
		try { const v = localStorage.getItem(LS_PREFIX + key); return v === null ? fallback : v; }
		catch (e) { return fallback; }
	}
	function prefSet(key, value) {
		try { localStorage.setItem(LS_PREFIX + key, value); } catch (e) { /* private mode / disabled */ }
	}

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

	function renderElement(item) {
		const el = document.createElement('div');
		el.className = 'qsl_designer_placed';
		el.dataset.id = item.id;
		el.dataset.type = item.type;
		el.textContent = item.type === 'field' ? item.field : (item.text || LANG.customText);
		el.style.left = inToPxX(item.x_in) + 'px';
		el.style.top = inToPxY(item.y_in) + 'px';
		el.style.fontFamily = item.font || 'Helvetica';
		el.style.fontSize = (item.font_pt || 12) + 'px';
		el.style.fontWeight = item.bold ? '700' : '600';
		el.style.color = item.color || '#000000';

		el.addEventListener('mousedown', e => onElementMouseDown(e, item.id));

		stage.appendChild(el);
		renderGhosts(item);
		return el;
	}

	function positionNode(id) {
		const item = byId(id), node = nodeById(id);
		if (!item || !node) return;
		node.style.left = inToPxX(item.x_in) + 'px';
		node.style.top = inToPxY(item.y_in) + 'px';
		repositionGhosts(item);
	}

	function styleNode(id) {
		const item = byId(id), node = nodeById(id);
		if (!item || !node) return;
		node.textContent = item.type === 'field' ? item.field : (item.text || LANG.customText);
		node.style.fontFamily = item.font || 'Helvetica';
		node.style.fontSize = (item.font_pt || 12) + 'px';
		node.style.fontWeight = item.bold ? '700' : '600';
		node.style.color = item.color || '#000000';
		// Keep WYSIWYG ghost rows in sync with the primary.
		stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + item.id + '"]').forEach(g => {
			g.textContent = node.textContent;
			g.style.fontFamily = node.style.fontFamily;
			g.style.fontSize = node.style.fontSize;
			g.style.fontWeight = node.style.fontWeight;
			g.style.color = node.style.color;
		});
	}

	// WYSIWYG ghost rows: faded extra copies of a "repeats per QSO" field, stacked
	// below it by the row pitch. Ghosts have no data-id and no event listeners, so
	// every selection/drag/marquee path (keyed off data-id) ignores them.
	function renderGhosts(item) {
		stage.querySelectorAll('.qsl_designer_ghost[data-ghost-for="' + item.id + '"]').forEach(n => n.remove());
		if (!item.repeat_per_qso || tplOptions.qsos_per_card <= 1) return;
		const primary = nodeById(item.id);
		if (!primary) return;
		const pitchPx = inToPxY(tplOptions.row_pitch_in);
		const left = inToPxX(item.x_in);
		const top = inToPxY(item.y_in);
		for (let i = 1; i < tplOptions.qsos_per_card; i++) {
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
			font_pt: 12,
			bold: false,
			color: '#000000',
			wrap_w_in: 2.6,
			repeat_per_qso: false,
			no_snap: false,
		};
		if (type === 'field') item.field = value;
		else item.text = value;
		elements.push(item);
		renderElement(item);
		selectOne(item.id);
		return item;
	}

	// Find a free, non-overlapping spot for click-to-add.
	function freeSpot() {
		let x = 40, y = 40;
		const occupied = () => elements.some(e =>
			Math.abs(inToPxX(e.x_in) - x) < 12 && Math.abs(inToPxY(e.y_in) - y) < 12);
		let guard = 0;
		while (occupied() && guard++ < 40) {
			x += 22; y += 18;
			if (x > STAGE_W_PX - 120) { x = 40; y += 24; }
			if (y > STAGE_H_PX - 40) { y = 40; }
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
	}

	// ===================================================================
	//  Dragging placed elements (with snapping)
	// ===================================================================
	function clientToStagePx(clientX, clientY) {
		const rect = stage.getBoundingClientRect();
		return { x: (clientX - rect.left) / zoom, y: (clientY - rect.top) / zoom };
	}

	function onElementMouseDown(e, id) {
		if (e.button !== 0) return;
		e.preventDefault();

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
		dx = clamp(dx, -minX, STAGE_W_PX - maxR);
		dy = clamp(dy, -minY, STAGE_H_PX - maxB);

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
		if (drag) { onElementDragMove(e); return; }
		if (marquee) { onMarqueeMove(e); return; }
	});

	window.addEventListener('mouseup', () => {
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
		for (let g = 0; g <= STAGE_W_PX + 0.1; g += GRID_PX) xLines.push(g);
		for (let g = 0; g <= STAGE_H_PX + 0.1; g += GRID_PX) yLines.push(g);

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

		// Position & text only make sense for a single element.
		document.getElementById('propPosRow').style.display = multi ? 'none' : '';
		document.getElementById('propTextRow').style.display = (!multi && isText) ? 'block' : 'none';

		if (multi) {
			document.getElementById('propTypeBadge').textContent = selectedIds.length;
			document.getElementById('propTypeLabel').textContent = LANG.selected;
		} else {
			document.getElementById('propTypeBadge').textContent = isText ? LANG.customText : 'Field';
			document.getElementById('propTypeLabel').textContent = isText ? '' : item.field;
			if (isText) document.getElementById('propText').value = item.text || '';
			document.getElementById('propX').value = inToDisp(item.x_in);
			document.getElementById('propY').value = inToDisp(item.y_in);
		}

		// Font / size / bold / wrap apply to all selected; show the primary's values.
		document.getElementById('propFont').value = item.font || 'Helvetica';
		document.getElementById('propFontSize').value = item.font_pt || 12;
		document.getElementById('propBold').checked = !!item.bold;
		document.getElementById('propColor').value = item.color || '#000000';
		document.getElementById('propWrap').value = inToDisp(item.wrap_w_in ?? 2.6);

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
	wireProp('propFontSize', (item, n) => { item.font_pt = clamp(parseInt(n.value || '12', 10), 6, 36); styleNode(item.id); });
	wireProp('propBold', (item, n) => { item.bold = n.checked; styleNode(item.id); });
	wireProp('propColor', (item, n) => { item.color = n.value; styleNode(item.id); });
	wireProp('propWrap', (item, n) => { item.wrap_w_in = Math.max(0.2, dispToIn(parseFloat(n.value || '2.6'))); });
	wireProp('propRepeat', (item, n) => { item.repeat_per_qso = n.checked; renderGhosts(item); });
	wireProp('propNoSnap', (item, n) => { item.no_snap = n.checked; });

	document.getElementById('btnDuplicate').addEventListener('click', duplicateSelected);
	document.getElementById('btnDeleteElem').addEventListener('click', deleteSelected);

	// ===== Template options (not undo-tracked, like calibration offsets) =====
	function applyTplOptionsToControls() {
		document.getElementById('tplQsosPerCard').value = tplOptions.qsos_per_card;
		document.getElementById('tplRowPitch').value = inToDisp(tplOptions.row_pitch_in);
		document.getElementById('tplPrintBg').checked = tplOptions.print_background;
		document.getElementById('tplSkipAddr').checked = tplOptions.skip_address;
	}

	// Show "Row spacing" only for multi-QSO cards.
	function setPitchWrapVisibility() {
		document.getElementById('tplPitchWrap').style.display = tplOptions.qsos_per_card > 1 ? '' : 'none';
	}

	// Called when QSOs/card or row spacing change via the controls: rebuild ghost rows.
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

	wireTpl('tplQsosPerCard', 'qsos_per_card',     n => Math.max(1, parseInt(n.value, 10) || 1), updateRepeatVisibility);
	wireTpl('tplRowPitch',    'row_pitch_in',      n => Math.max(0.05, dispToIn(parseFloat(n.value) || 0.3)), updateRepeatVisibility);
	wireTpl('tplPrintBg',     'print_background',  n => n.checked);
	wireTpl('tplSkipAddr',    'skip_address',      n => n.checked);

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

		const setX = (b, px) => { b.it.x_in = pxToInX(clamp(px, 0, STAGE_W_PX - b.w)); };
		const setY = (b, py) => { b.it.y_in = pxToInY(clamp(py, 0, STAGE_H_PX - b.h)); };

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
				const dx = clamp(STAGE_W_PX / 2 - cx, -minL, STAGE_W_PX - maxR);
				boxes.forEach(b => { b.it.x_in = pxToInX(b.x + dx); });
				break;
			}
			case 'page-v': {
				const dy = clamp(STAGE_H_PX / 2 - cy, -minT, STAGE_H_PX - maxB);
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
		const x = clamp(Math.round(p.x / GRID_PX) * GRID_PX, 0, STAGE_W_PX - 6);
		const y = clamp(Math.round(p.y / GRID_PX) * GRID_PX, 0, STAGE_H_PX - 6);
		addElement('field', field, pxToInX(x), pxToInY(y));
	});

	document.getElementById('btnAddText').addEventListener('click', () => {
		const spot = freeSpot();
		addElement('text', LANG.customText, pxToInX(spot.x), pxToInY(spot.y));
		document.getElementById('propText').focus();
		document.getElementById('propText').select();
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

		const pxPerInX = STAGE_W_PX / W_IN;
		const pxPerInY = STAGE_H_PX / H_IN;

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

		// Inch ruler: major tick + label every 1 in, minor every 0.25 in.
		for (let i = 0; i <= W_IN * 4; i++) {
			const x = i * (pxPerInX / 4);
			const tick = document.createElement('div');
			tick.className = 'ruler-tick-top ' + (i % 4 === 0 ? 'major' : 'minor');
			tick.style.left = x + 'px';
			top.appendChild(tick);
			if (i % 4 === 0) {
				const label = document.createElement('div');
				label.className = 'ruler-label-top';
				label.style.left = (x + 3) + 'px';
				label.textContent = (i / 4) + '"';
				top.appendChild(label);
			}
		}

		for (let i = 0; i <= H_IN * 4; i++) {
			const y = i * (pxPerInY / 4);
			const tick = document.createElement('div');
			tick.className = 'ruler-tick-left ' + (i % 4 === 0 ? 'major' : 'minor');
			tick.style.top = y + 'px';
			left.appendChild(tick);
			if (i % 4 === 0) {
				const label = document.createElement('div');
				label.className = 'ruler-label-left';
				label.style.top = (y - 6) + 'px';
				label.textContent = (i / 4) + '"';
				left.appendChild(label);
			}
		}
	}

	// ===================================================================
	//  Background image
	// ===================================================================
	function setBackground(url) {
		if (url) {
			stage.style.backgroundImage = "url('" + url + "')";
			stage.style.backgroundSize = 'cover';
			stage.style.backgroundPosition = 'center';
			stage.style.backgroundRepeat = 'no-repeat';
		} else {
			stage.style.backgroundImage = '';
		}
	}

	document.getElementById('btnUploadPreview').addEventListener('click', async () => {
		const fileInput = document.getElementById('previewImageFile');
		if (!fileInput.files.length) {
			showToast(LANG.error, LANG.pleaseChooseImage, 'bg-warning text-dark', 4000);
			return;
		}
		const fd = new FormData();
		fd.append('preview_image', fileInput.files[0]);
		showToast('', LANG.uploading, 'bg-info text-white', 2000);

		const r = await fetch(base_url + 'qslpostcard/upload_preview', { method: 'POST', body: fd });
		const out = await r.json();
		if (!out.ok) {
			showToast(LANG.error, LANG.uploadFailed + ': ' + (out.error || LANG.unknownError), 'bg-danger text-white', 5000);
			return;
		}
		previewImagePath = out.path;
		previewImageUrl = out.url;
		setBackground(previewImageUrl);
		showToast(LANG.success, LANG.previewUploaded, 'bg-success text-white', 4000);
	});

	// ===================================================================
	//  Template load / save / delete
	// ===================================================================
	function buildLayout() {
		return {
			page: { w_in: W_IN, h_in: H_IN, orientation: 'landscape' },
			calibration: {
				offset_x_in: dispToIn(parseFloat(offXInput.value || '0')),
				offset_y_in: dispToIn(parseFloat(offYInput.value || '0')),
			},
			options: tplOptions,
			elements: elements,
		};
	}

	async function loadTemplate(id) {
		const r = await fetch(base_url + 'index.php/qslpostcard/get_template/' + id);
		const tpl = await r.json();
		const layout = tpl.layout || {};

		previewImagePath = tpl.preview_image || null;
		previewImageUrl = previewImagePath ? base_url + previewImagePath : null;
		setBackground(previewImageUrl);

		offXInput.value = inToDisp(layout.calibration?.offset_x_in ?? 0);
		offYInput.value = inToDisp(layout.calibration?.offset_y_in ?? 0);

		const o = layout.options || {};
		tplOptions = {
			qsos_per_card: Math.max(1, parseInt(o.qsos_per_card, 10) || 1),
			print_background: o.print_background !== false,
			skip_address: !!o.skip_address,
			row_pitch_in: parseFloat(o.row_pitch_in) || 0.3,
		};
		applyTplOptionsToControls();
		setPitchWrapVisibility();

		elements = (layout.elements || []).map(el => ({
			id: el.id || newId(),
			type: el.type || 'field',
			field: el.field,
			text: el.text,
			x_in: el.x_in || 0,
			y_in: el.y_in || 0,
			font: el.font || 'Helvetica',
			font_pt: el.font_pt || 12,
			bold: !!el.bold,
			color: el.color || '#000000',
			wrap_w_in: el.wrap_w_in ?? 2.6,
			repeat_per_qso: !!el.repeat_per_qso,
			no_snap: !!el.no_snap,
		}));

		history.length = 0;
		future.length = 0;
		updateHistoryButtons();
		deselect();
		renderAll();
	}

	tplSelect.addEventListener('change', async e => {
		const id = e.target.value;
		prefSet('tpl', id);
		if (!id) {
			// "(new)" — start a blank canvas
			elements = [];
			previewImagePath = null; previewImageUrl = null;
			setBackground(null);
			document.getElementById('tplName').value = '';
			document.getElementById('btnPdf').href = '#';
			tplOptions = { ...DEFAULT_TPL_OPTIONS };
			applyTplOptionsToControls();
			setPitchWrapVisibility();
			history.length = 0; future.length = 0; updateHistoryButtons();
			deselect();
			renderAll();
			return;
		}
		document.getElementById('tplName').value = e.target.options[e.target.selectedIndex].text;
		document.getElementById('btnPdf').href = base_url + 'index.php/qslpostcard/pdf/' + id;
		await loadTemplate(id);
	});

	document.getElementById('btnSave').addEventListener('click', async () => {
		const id = parseInt(tplSelect.value || '0', 10);
		const name = document.getElementById('tplName').value || LANG.untitled;
		const payload = { id: id, name: name, layout: buildLayout(), preview_image: previewImagePath };

		const r = await fetch(base_url + 'index.php/qslpostcard/save_template', {
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
		document.getElementById('btnPdf').href = base_url + 'index.php/qslpostcard/pdf/' + newId;
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
				const r = await fetch(base_url + 'index.php/qslpostcard/delete_template', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id: parseInt(id, 10) }),
				});
				const out = await r.json();
				if (!out.ok) { showToast(LANG.error, LANG.deleteFailed, 'bg-danger text-white', 5000); return; }
				showToast(LANG.success, LANG.deleteSuccess, 'bg-success text-white', 5000);
				tplSelect.querySelector('option[value="' + id + '"]')?.remove();
				tplSelect.value = '';
				tplSelect.dispatchEvent(new Event('change'));
			},
		});
	});

	// ===================================================================
	//  Init
	// ===================================================================
	drawRulers();
	updateHistoryButtons();

	// Restore persisted zoom.
	const savedZoom = parseFloat(prefGet('zoom', '1'));
	setZoom(isNaN(savedZoom) ? 1 : savedZoom);

	// Restore last-selected template (only if it still exists in the list).
	const savedTpl = prefGet('tpl', '');
	if (savedTpl && tplSelect.querySelector('option[value="' + savedTpl + '"]')) {
		tplSelect.value = savedTpl;
		tplSelect.dispatchEvent(new Event('change'));
	}
})();
