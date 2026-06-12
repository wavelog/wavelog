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
            status.textContent = LANG.pleaseChooseImage;
            return;
        }

        const fd = new FormData();
        fd.append('preview_image', fileInput.files[0]);

        status.textContent = LANG.uploading;

        const r = await fetch(base_url + 'qslpostcard/upload_preview', {
            method: 'POST',
            body: fd
        });

        const out = await r.json();

        if (!out.ok) {
            status.textContent = LANG.uploadFailed + ': ' + (out.error || LANG.unknownError);
            return;
        }

        previewImagePath = out.path;
        previewImageUrl = out.url;

        stage.style.backgroundImage = `url('${previewImageUrl}')`;
        stage.style.backgroundSize = 'cover';
        stage.style.backgroundPosition = 'center';
        stage.style.backgroundRepeat = 'no-repeat';

        status.textContent = LANG.previewUploaded;
    });

    function makeElem(type, value, x = 50, y = 50, existing = null) {
        const el = document.createElement('div');

        const id = existing?.id || ('el_' + Math.random().toString(16).slice(2));

        el.className = 'qsl_designer_placed';
        el.dataset.id = id;
        el.dataset.type = type;

        if (type === 'field') {
            el.dataset.field = value;
            el.textContent = value;
        } else {
            el.dataset.text = value;
            el.textContent = value || LANG.customText;
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

        document.querySelectorAll('.qsl_designer_placed').forEach(el => {
            el.classList.toggle('qsl_designer_selected', el.dataset.id === id);
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
        document.querySelectorAll('.qsl_designer_placed').forEach(el => el.classList.remove('qsl_designer_selected'));
        refreshSelectionPanel();
    });

    document.querySelectorAll('.qsl_designer_field').forEach(f => {
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

        const dom = [...stage.querySelectorAll('.qsl_designer_placed')].find(d => d.dataset.id === selectedElementId);
        if (dom) {
            dom.style.fontFamily = item.font;
            dom.style.fontSize = item.font_pt + 'px';
            dom.style.fontWeight = item.bold ? '700' : '600';
        }
    });

    document.getElementById('btnAddText').addEventListener('click', () => {
        const txt = prompt(LANG.enterCustomText, LANG.comments);
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

        // left ruler
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
                makeElem('text', el.text || LANG.customText, x, y, el);
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
        const name = document.getElementById('tplName').value || LANG.untitled;
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
        if (!out.ok) return alert(out.error || LANG.saveFailed);

        alert(LANG.savedReload);
    });

	document.getElementById('btnDelete').addEventListener('click', async () => {
		BootstrapDialog.confirm({
			title: LANG.deleteTemplate,
			message: LANG.deleteTemplateConfirm,
			type: BootstrapDialog.TYPE_DANGER,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-danger',
			callback: async function(result) {
				if(result) {
					const id = document.getElementById('tplSelect').value || 0;
					const name = document.getElementById('tplName').value || LANG.untitled;
					const payload = {
						id: parseInt(id, 10)
					};
					const r = await fetch(base_url + 'index.php/qslpostcard/delete_template', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(payload)
					});
					const out = await r.json();
					if (!out.ok) return showToast('Error', LANG.deleteFailed, 'bg-danger text-white', 5000);
					showToast('Success', LANG.deleteSuccess, 'bg-success text-white', 5000);
					$("#tplSelect option[value='" + id + "']").remove();
				}
			}
		});


    });
