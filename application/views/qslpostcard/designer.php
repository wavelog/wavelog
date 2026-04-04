<div class="container mt-3">
    <h3>QSL Postcard Designer (4x6 Landscape)</h3>

    <div class="row">
        <div class="col-md-3">
            <div class="card p-2 mb-2">
                <label>Template</label>
                <select id="tplSelect" class="form-control mb-2">
                    <option value="">(new)</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= htmlentities($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <input id="tplName" class="form-control mb-2" placeholder="Template name">
                <label>Postcard preview image</label>
                <input type="file" id="previewImageFile" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp">

                <button type="button" id="btnUploadPreview" class="btn btn-secondary w-100 mb-2">
                    Upload Preview Image
                </button>

                <div id="previewImageStatus" class="small mb-2" style="color:#333;"></div>
                <button id="btnSave" class="btn btn-primary w-100 mb-2">Save Template</button>

                <div class="small text-muted mb-2">
                    Tip: After a test print, adjust global offsets rather than moving every field.
                </div>

                <label>Global offset X (inches)</label>
                <input id="offX" type="number" step="0.01" class="form-control mb-2" value="0">

                <label>Global offset Y (inches)</label>
                <input id="offY" type="number" step="0.01" class="form-control mb-3" value="0">

                <a id="btnPdf" class="btn btn-success w-100" href="#" target="_blank">Generate PDF (demo)</a>
            </div>
            <div class="card p-2 mt-3">
                <h5 class="mb-2">Selected Element</h5>

                <div id="noSelection" class="small" style="color:#666;">
                    Click a placed field to edit its properties.
                </div>

                <div id="selectionPanel" style="display:none;">
                    <label class="form-label">Font</label>
                    <select id="propFont" class="form-control mb-2">
                        <option value="Helvetica">Helvetica</option>
                        <option value="Times">Times</option>
                        <option value="Courier">Courier</option>
                    </select>

                    <label class="form-label">Font Size</label>
                    <input id="propFontSize" type="number" step="1" min="6" max="36" class="form-control mb-2" value="12">

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="propBold">
                        <label class="form-check-label" for="propBold">
                            Bold
                        </label>
                    </div>

                    <button type="button" id="btnApplyProps" class="btn btn-primary w-100 mb-2">
                        Apply
                    </button>
                </div>
            </div>
            <div class="card p-2">
                <h5 class="mb-2">Fields</h5>
                <div class="mb-2"><strong>Address</strong></div>
                <div class="field" data-field="addr.name">addr.name</div>
                <div class="field" data-field="addr.addr1">addr.addr1</div>
                <div class="field" data-field="addr.addr2">addr.addr2</div>
                <div class="field" data-field="addr.city_state_zip">addr.city_state_zip</div>
                <div class="field" data-field="addr.country">addr.country</div>

                <hr>

                <button type="button" id="btnAddText" class="btn btn-outline-secondary w-100 mb-2">
                    Add Custom Text
                </button>
                <div class="mb-2"><strong>QSO</strong></div>
                <div class="field" data-field="qso.call">qso.call</div>
                <div class="field" data-field="qso.qso_date">qso.qso_date</div>
                <div class="field" data-field="qso.time_on">qso.time_on</div>
                <div class="field" data-field="qso.band">qso.band</div>
                <div class="field" data-field="qso.mode">qso.mode</div>
                <div class="field" data-field="qso.freq">qso.freq</div>
                <div class="field" data-field="qso.rst_sent">qso.rst_sent</div>
                <div class="field" data-field="qso.rst_rcvd">qso.rst_rcvd</div>
                <div class="field" data-field="qso.summary">qso.summary</div>
                <div class="field" data-field="qso.rig">qso.rig</div>
                <div class="field" data-field="qso.comment">qso.comment</div>
                <div class="field" data-field="qso.time_utc">qso.time_utc</div>
                <div class="field" data-field="qso.day">qso.day</div>
                <div class="field" data-field="qso.month">qso.month</div>
                <div class="field" data-field="qso.month_name">qso.month_name</div>
                <div class="field" data-field="qso.year">qso.year</div>
                <div class="field" data-field="qso.antenna">qso.antenna</div>
                <div class="field" data-field="qso.tx_power">qso.tx_power</div>
                <div class="field" data-field="qso.rx_power">qso.rx_power</div>
                <div class="field" data-field="qso.my_rig">qso.my_rig</div>
                <div class="field" data-field="qso.pota_ref">qso.pota_ref</div>
                <div class="field" data-field="qso.my_pota_ref">qso.my_pota_ref</div>
                <div class="field" data-field="qso.pota_line">qso.pota_line</div>
                <div class="field" data-field="qso.qsl_message">qso.qsl_message</div>
                <style>
                    .field {
                        cursor: grab;
                        user-select: none;
                        padding: 6px 8px;
                        border: 1px solid #ddd;
                        margin-bottom: 6px;
                        border-radius: 6px;
                        background: #fafafa;
                    }
                </style>
                <style>
                    .field {
                        cursor: grab;
                        user-select: none;
                        padding: 6px 8px;
                        border: 1px solid #cfcfcf;
                        margin-bottom: 6px;
                        border-radius: 6px;
                        background: #ffffff;
                        color: #111111 !important;
                        font-weight: 600;
                    }

                    .field:hover {
                        background: #f3f7ff;
                        border-color: #8fb4ff;
                    }

                    .placed {
                        position: absolute;
                        padding: 4px 6px;
                        border: 2px solid #1f6feb;
                        border-radius: 6px;
                        background: #ffffff;
                        color: #111111 !important;
                        font-weight: 600;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
                        cursor: move;
                        white-space: nowrap;
                    }

                    .placed.selected {
                        border-color: #ff7a00;
                        box-shadow: 0 0 0 3px rgba(255, 122, 0, .25);
                    }

                    #stage {
                        background:
                            linear-gradient(to right, rgba(0, 0, 0, .06) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(0, 0, 0, .06) 1px, transparent 1px);
                        background-size: 37.5px 37.5px;
                        background-color: #ffffff;
                    }

                    #rulerTop,
                    #rulerLeft {
                        color: #111111 !important;
                        font-weight: 600;
                    }

                    #rulerTop div,
                    #rulerLeft div {
                        color: #111111 !important;
                    }
                </style>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card p-2">
                <div id="stageWrap" style="display:flex; justify-content:center;">

                    <div id="rulerWrap" style="position:relative; width:940px; height:640px;">

                        <!-- TOP RULER -->
                        <div id="rulerTop"
                            style="
        position:absolute;
        left:40px;
        top:0;
        width:900px;
        height:40px;
        background:#f6f6f6;
        border:1px solid #ddd;
      ">
                        </div>

                        <!-- LEFT RULER -->
                        <div id="rulerLeft"
                            style="
        position:absolute;
        left:0;
        top:40px;
        width:40px;
        height:600px;
        background:#f6f6f6;
        border:1px solid #ddd;
      ">
                        </div>

                        <!-- POSTCARD STAGE -->
                        <div id="stage"
                            style="
        position:absolute;
        left:40px;
        top:40px;
        width:900px;
        height:600px;
        border:1px solid #ccc;
        overflow:hidden;
        background:white;
      ">
                        </div>

                    </div>

                </div>

                <div class="small text-muted mt-2">
                    Stage = 6 × 4 inches (900 × 600 px). Drag fields onto the postcard.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== Stage sizing =====
    const STAGE_W_PX = 900;
    const STAGE_H_PX = 600;
    const W_IN = 6.0;
    const H_IN = 4.0;

    function pxToInX(px) {
        return (px / STAGE_W_PX) * W_IN;
    }

    function pxToInY(py) {
        return (py / STAGE_H_PX) * H_IN;
    }

    function inToPxX(ix) {
        return (ix / W_IN) * STAGE_W_PX;
    }

    function inToPxY(iy) {
        return (iy / H_IN) * STAGE_H_PX;
    }

    let previewImagePath = null;
    let previewImageUrl = null;
    let selectedElementId = null;
    const stage = document.getElementById('stage');
    let elements = []; // {id, field, x_in, y_in, font_pt, bold, wrap_w_in}

    document.getElementById('btnUploadPreview').addEventListener('click', async () => {
        const fileInput = document.getElementById('previewImageFile');
        const status = document.getElementById('previewImageStatus');

        if (!fileInput.files.length) {
            status.textContent = 'Please choose an image first.';
            return;
        }

        const fd = new FormData();
        fd.append('preview_image', fileInput.files[0]);

        status.textContent = 'Uploading...';

        const r = await fetch(`<?= site_url('qslpostcard/upload_preview') ?>`, {
            method: 'POST',
            body: fd
        });

        const out = await r.json();

        if (!out.ok) {
            status.textContent = 'Upload failed: ' + (out.error || 'unknown error');
            return;
        }

        previewImagePath = out.path;
        previewImageUrl = out.url;

        stage.style.backgroundImage = `url('${previewImageUrl}')`;
        stage.style.backgroundSize = 'cover';
        stage.style.backgroundPosition = 'center';
        stage.style.backgroundRepeat = 'no-repeat';

        status.textContent = 'Preview image uploaded.';
    });

    function makeElem(type, value, x = 50, y = 50, existing = null) {
        const el = document.createElement('div');

        const id = existing?.id || ('el_' + Math.random().toString(16).slice(2));

        el.className = 'placed';
        el.dataset.id = id;
        el.dataset.type = type;

        if (type === 'field') {
            el.dataset.field = value;
            el.textContent = value;
        } else {
            el.dataset.text = value;
            el.textContent = value || 'Custom Text';
        }

        el.style.left = x + 'px';
        el.style.top = y + 'px';
        el.style.fontSize = (existing?.font_pt || 12) + 'px';
        el.style.fontFamily = (existing?.font || 'Helvetica');
        el.style.fontWeight = (existing?.bold ? '700' : '600');

        stage.appendChild(el);

        const item = {
            id,
            type,
            x_in: existing?.x_in ?? pxToInX(x),
            y_in: existing?.y_in ?? pxToInY(y),
            font: existing?.font || 'Helvetica',
            font_pt: existing?.font_pt || 12,
            bold: existing?.bold || false,
            wrap_w_in: existing?.wrap_w_in ?? 2.6
        };

        if (type === 'field') {
            item.field = value;
        } else {
            item.text = value;
        }

        elements.push(item);

        let dragging = false,
            ox = 0,
            oy = 0;

        el.addEventListener('mousedown', (e) => {
            dragging = true;
            ox = e.offsetX;
            oy = e.offsetY;

            selectPlacedElement(id);
        });

        window.addEventListener('mousemove', (e) => {
            if (!dragging) return;

            const rect = stage.getBoundingClientRect();
            let nx = e.clientX - rect.left - ox;
            let ny = e.clientY - rect.top - oy;

            nx = Math.max(0, Math.min(STAGE_W_PX - 10, nx));
            ny = Math.max(0, Math.min(STAGE_H_PX - 10, ny));

            el.style.left = nx + 'px';
            el.style.top = ny + 'px';

            const found = elements.find(z => z.id === id);
            if (found) {
                found.x_in = pxToInX(nx);
                found.y_in = pxToInY(ny);
            }
        });

        window.addEventListener('mouseup', () => {
            dragging = false;
        });

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectPlacedElement(id);
        });

        el.addEventListener('dblclick', () => {
            if (stage.contains(el)) {
                stage.removeChild(el);
            }
            elements = elements.filter(z => z.id !== id);

            if (selectedElementId === id) {
                selectedElementId = null;
                refreshSelectionPanel();
            }
        });

        return item;
    }

    function selectPlacedElement(id) {
        selectedElementId = id;

        document.querySelectorAll('.placed').forEach(el => {
            el.classList.toggle('selected', el.dataset.id === id);
        });

        refreshSelectionPanel();
    }

    function refreshSelectionPanel() {
        const noSel = document.getElementById('noSelection');
        const panel = document.getElementById('selectionPanel');

        const item = elements.find(z => z.id === selectedElementId);

        if (!item) {
            noSel.style.display = 'block';
            panel.style.display = 'none';
            return;
        }

        noSel.style.display = 'none';
        panel.style.display = 'block';

        document.getElementById('propFont').value = item.font || 'Helvetica';
        document.getElementById('propFontSize').value = item.font_pt || 12;
        document.getElementById('propBold').checked = !!item.bold;
    }

    stage.addEventListener('click', () => {
        selectedElementId = null;
        document.querySelectorAll('.placed').forEach(el => el.classList.remove('selected'));
        refreshSelectionPanel();
    });

    document.querySelectorAll('.field').forEach(f => {
        f.addEventListener('click', () => {
            makeElem('field', f.dataset.field, 40, 40);
        });
    });

    document.getElementById('btnApplyProps').addEventListener('click', () => {
        const item = elements.find(z => z.id === selectedElementId);
        if (!item) return;

        item.font = document.getElementById('propFont').value;
        item.font_pt = parseInt(document.getElementById('propFontSize').value || '12', 10);
        item.bold = document.getElementById('propBold').checked;

        const dom = [...stage.querySelectorAll('.placed')].find(d => d.dataset.id === selectedElementId);
        if (dom) {
            dom.style.fontFamily = item.font;
            dom.style.fontSize = item.font_pt + 'px';
            dom.style.fontWeight = item.bold ? '700' : '600';
        }
    });

    document.getElementById('btnAddText').addEventListener('click', () => {
        const txt = prompt('Enter custom text:', 'Comments:');
        if (txt === null) return;
        makeElem('text', txt, 60, 60);
    });

    function buildLayout() {
        return {
            page: {
                w_in: W_IN,
                h_in: H_IN,
                orientation: 'landscape'
            },
            calibration: {
                offset_x_in: parseFloat(document.getElementById('offX').value || '0'),
                offset_y_in: parseFloat(document.getElementById('offY').value || '0')
            },
            elements: elements
        };
    }

    function drawRulers() {

        const top = document.getElementById('rulerTop');
        const left = document.getElementById('rulerLeft');

        top.innerHTML = '';
        left.innerHTML = '';

        const pxPerInX = STAGE_W_PX / W_IN;
        const pxPerInY = STAGE_H_PX / H_IN;

        // top ruler
        for (let i = 0; i <= W_IN * 4; i++) {

            const x = i * (pxPerInX / 4);

            const tick = document.createElement('div');
            tick.style.position = 'absolute';
            tick.style.left = x + 'px';
            tick.style.bottom = '0';
            tick.style.width = '1px';
            tick.style.background = '#666';
            tick.style.height = (i % 4 === 0) ? '18px' : '10px';

            top.appendChild(tick);

            if (i % 4 === 0) {

                const label = document.createElement('div');
                label.style.position = 'absolute';
                label.style.left = (x + 3) + 'px';
                label.style.top = '2px';
                label.style.fontSize = '11px';
                label.style.color = '#111111';
                label.style.fontWeight = '600';
                label.style.lineHeight = '1';
                label.style.background = 'transparent';
                label.textContent = (i / 4) + '"';
                top.appendChild(label);
            }
        }

        // left ruler
        for (let i = 0; i <= H_IN * 4; i++) {

            const y = i * (pxPerInY / 4);

            const tick = document.createElement('div');
            tick.style.position = 'absolute';
            tick.style.top = y + 'px';
            tick.style.right = '0';
            tick.style.height = '1px';
            tick.style.background = '#666';
            tick.style.width = (i % 4 === 0) ? '18px' : '10px';

            left.appendChild(tick);

            if (i % 4 === 0) {

                const label = document.createElement('div');
                label.style.position = 'absolute';
                label.style.right = '20px';
                label.style.top = (y - 6) + 'px';
                label.style.fontSize = '11px';
                label.style.color = '#111111';
                label.style.fontWeight = '600';
                label.style.lineHeight = '1';
                label.style.background = 'transparent';
                label.textContent = (i / 4) + '"';
                left.appendChild(label);
            }
        }
    }

    drawRulers();

    async function loadTemplate(id) {
        const r = await fetch(base_url + 'index.php/qslpostcard/get_template/' + id);
        const tpl = await r.json();

        const layout = tpl.layout || {};

        stage.innerHTML = '';
        elements = [];

        previewImagePath = tpl.preview_image || null;
        previewImageUrl = previewImagePath ? base_url + previewImagePath : null;

        if (previewImageUrl) {
            stage.style.backgroundImage = `url('${previewImageUrl}')`;
            stage.style.backgroundSize = 'cover';
            stage.style.backgroundPosition = 'center';
            stage.style.backgroundRepeat = 'no-repeat';
        } else {
            stage.style.backgroundImage = '';
        }

        document.getElementById('offX').value = layout.calibration?.offset_x_in ?? 0;
        document.getElementById('offY').value = layout.calibration?.offset_y_in ?? 0;

        (layout.elements || []).forEach(el => {
            const x = inToPxX(el.x_in || 0);
            const y = inToPxY(el.y_in || 0);

            if ((el.type || 'field') === 'text') {
                makeElem('text', el.text || 'Custom Text', x, y, el);
            } else {
                makeElem('field', el.field, x, y, el);
            }
        });
    }
    document.getElementById('tplSelect').addEventListener('change', async (e) => {
        const id = e.target.value;
        if (!id) return;
        // set name
        const selText = e.target.options[e.target.selectedIndex].text;
        document.getElementById('tplName').value = selText;
        document.getElementById('btnPdf').href = base_url + 'index.php/qslpostcard/pdf/' + id;
        await loadTemplate(id);
    });

    document.getElementById('btnSave').addEventListener('click', async () => {
        const id = document.getElementById('tplSelect').value || 0;
        const name = document.getElementById('tplName').value || 'Untitled';
        const payload = {
            id: parseInt(id, 10),
            name,
            layout: buildLayout(),
            preview_image: previewImagePath
        };
        const r = await fetch(base_url + 'index.php/qslpostcard/save_template', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const out = await r.json();
        if (!out.ok) return alert(out.error || 'Save failed');

        alert('Saved. Reload page to see template in list.');
    });
</script>
