<?php
// Palette field groups. The keys (data-field) MUST stay exactly as the PDF
// renderer expects them (addr.* / qso.*). Only the grouping/labels are cosmetic.
$qsl_field_groups = [
	__("Address")             => ['addr.name', 'addr.addr1', 'addr.addr2', 'addr.city_state_zip', 'addr.country'],
	__("QSO Core")            => ['qso.call', 'qso.band', 'qso.mode', 'qso.sat_name', 'qso.sat_mode', 'qso.freq', 'qso.rst_sent', 'qso.rst_rcvd', 'qso.summary'],
	__("Date & Time")         => ['qso.qso_date', 'qso.time_on', 'qso.time', 'qso.time_utc', 'qso.day', 'qso.month', 'qso.month_name', 'qso.year'],
	__("Station & Equipment") => ['qso.tx_power'], //['qso.rig', 'qso.my_rig', 'qso.antenna', 'qso.rx_power'], Implement later if there's demand
	__("My References")       => ['qso.my_pota_ref', 'qso.pota_line', 'qso.my_sota_ref', 'qso.sota_line', 'qso.my_iota_ref', 'qso.iota_line', 'qso.my_grid'],
	__("Markers")             => ['qso.pse_qsl', 'qso.tnx_qsl', 'qso.portable'],
	__("Other")               => ['qso.comment', 'qso.qsl_message', 'qso.qsl_via'],
];
?>
<script>
	// ===== Translatable strings (PHP → JS) =====
	const LANG = {
		pleaseChooseImage: <?= json_encode(__("Please choose an image first.")); ?>,
		uploading: <?= json_encode(__("Uploading...")); ?>,
		uploadFailed: <?= json_encode(__("Upload failed")); ?>,
		unknownError: <?= json_encode(__("unknown error")); ?>,
		previewUploaded: <?= json_encode(__("Preview image uploaded.")); ?>,
		customText: <?= json_encode(__("Custom Text")); ?>,
		untitled: <?= json_encode(__("Untitled")); ?>,
		saveFailed: <?= json_encode(__("Save failed")); ?>,
		saved: <?= json_encode(__("Template saved.")); ?>,
		deleteTemplate: <?= json_encode(__("Delete Template?")); ?>,
		deleteTemplateConfirm: <?= json_encode(__("Are you sure you want to delete this template? This action cannot be undone.")); ?>,
		deleteFailed: <?= json_encode(__("Delete failed")); ?>,
		deleteSuccess: <?= json_encode(__("Template deleted successfully!")); ?>,
		selectTemplateToDelete: <?= json_encode(__("Please select a template to delete.")); ?>,
		success: <?= json_encode(__("Success")); ?>,
		error: <?= json_encode(__("Error")); ?>,
		selected: <?= json_encode(__("selected")); ?>,
	};
</script>

<div class="container-fluid px-3 px-lg-4 mt-3 mb-3" id="qslDesigner">

	<!-- ===== TOOLBAR ===== -->
	<div class="card qsl-toolbar mb-3">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end gap-3">

				<!-- Template group -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label"><?= __("Template"); ?></label>
					<div class="d-flex gap-2">
						<select id="tplSelect" class="form-select form-select-sm" style="min-width:160px;">
							<option value=""><?= __("(new)"); ?></option>
							<?php foreach ($templates as $t): ?>
								<option value="<?= (int)$t['id'] ?>"><?= htmlentities($t['name']) ?></option>
							<?php endforeach; ?>
						</select>
						<input id="tplName" class="form-control form-control-sm" style="min-width:140px;" placeholder="<?= __("Template name"); ?>">
						<button id="btnSave" class="btn btn-sm btn-success text-nowrap" title="<?= __("Save Template"); ?>">
							<i class="fas fa-save me-1"></i><?= __("Save"); ?>
						</button>
						<button id="btnDelete" class="btn btn-sm btn-outline-danger text-nowrap" title="<?= __("Delete Template"); ?>">
							<i class="fas fa-trash"></i>
						</button>
					</div>
				</div>

				<!-- Background group -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label"><?= __("Background image"); ?></label>
					<div class="d-flex gap-2 align-items-center">
						<input type="file" id="previewImageFile" class="form-control form-control-sm" style="max-width:275px;" accept=".jpg,.jpeg,.png,.JPG,.JPEG,.PNG">
						<button type="button" id="btnUploadPreview" class="btn btn-sm btn-primary" title="<?= __("Upload Preview Image"); ?>">
							<i class="fas fa-upload"></i>
						</button>
						<a id="btnPdf" class="btn btn-sm btn-primary" href="#" target="_blank" title="<?= __("Generate PDF (demo)"); ?>">
							<i class="fas fa-file-pdf me-1"></i><?= __("PDF"); ?>
						</a>
					</div>
				</div>

				<!-- History group -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label"><?= __("History"); ?></label>
					<div class="btn-group btn-group-sm" role="group">
						<button id="btnUndo" class="btn btn-primary" disabled title="<?= __("Undo"); ?> (Ctrl+Z)"><i class="fas fa-undo"></i></button>
						<button id="btnRedo" class="btn btn-primary" disabled title="<?= __("Redo"); ?> (Ctrl+Y)"><i class="fas fa-redo"></i></button>
					</div>
				</div>

				<!-- Zoom group -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label"><?= __("Zoom"); ?></label>
					<div class="btn-group btn-group-sm" role="group">
						<button id="btnZoomOut" class="btn btn-primary px-2"><i class="fas fa-minus"></i></button>
						<button id="btnZoomReset" class="btn btn-primary" style="min-width:56px;"><span id="zoomLabel">100%</span></button>
						<button id="btnZoomIn" class="btn btn-primary px-2"><i class="fas fa-plus"></i></button>
					</div>
				</div>

				<!-- Calibration offsets -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label" title="<?= __("Tip: After a test print, adjust global offsets rather than moving every field."); ?>">
						<?= __("Print offset (in)"); ?>
					</label>
					<div class="d-flex gap-2 align-items-center">
						<div class="input-group input-group-sm" style="width:96px;">
							<span class="input-group-text">X</span>
							<input id="offX" type="number" step="0.01" class="form-control" value="0">
						</div>
						<div class="input-group input-group-sm" style="width:96px;">
							<span class="input-group-text">Y</span>
							<input id="offY" type="number" step="0.01" class="form-control" value="0">
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- ===== THREE-PANE EDITOR ===== -->
	<div class="qsl-editor">

		<!-- PALETTE (left) -->
		<aside class="qsl-pane qsl-palette card">
			<div class="card-header py-2">
				<i class="fas fa-th-large me-2"></i><?= __("Fields"); ?>
			</div>
			<div class="card-body p-2">
				<div class="input-group input-group-sm mb-2">
					<span class="input-group-text"><i class="fas fa-search"></i></span>
					<input type="search" id="fieldSearch" class="form-control" placeholder="<?= __("Search fields…"); ?>">
				</div>

				<button type="button" id="btnAddText" class="btn btn-sm btn-primary w-100 mb-2">
					<i class="fas fa-font me-1"></i><?= __("Add Custom Text"); ?>
				</button>

				<?php $first = true; foreach ($qsl_field_groups as $group => $fields): ?>
					<details class="qsl-cat" <?= $first ? 'open' : '' ?>>
						<summary><?= $group ?></summary>
						<div class="qsl-cat-body">
							<?php foreach ($fields as $f): ?>
								<div class="qsl_designer_field" draggable="true" data-field="<?= $f ?>"><?= $f ?></div>
							<?php endforeach; ?>
						</div>
					</details>
				<?php $first = false; endforeach; ?>

				<div id="fieldSearchEmpty" class="small text-muted text-center mt-2" style="display:none;">
					<?= __("No fields match your search."); ?>
				</div>
			</div>
		</aside>

		<!-- CANVAS (center) -->
		<section class="qsl-pane qsl-canvas card">
			<div class="card-header py-2 d-flex justify-content-between align-items-center">
				<span><i class="fas fa-expand me-2"></i><?= __("Postcard Canvas"); ?></span>
				<span class="small text-muted d-none d-lg-inline"><?= __("6 × 4 inches · drag fields onto the postcard"); ?></span>
			</div>
			<div class="card-body p-0">
				<div id="stageScroll">
					<div id="stageZoom">
						<div id="rulerWrap">
							<div id="rulerTop"></div>
							<div id="rulerLeft"></div>
							<div id="stage"></div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- RIGHT COLUMN: Template options + Properties -->
		<div class="qsl-right-stack">

			<!-- TEMPLATE OPTIONS -->
			<aside class="card qsl-templateopts">
				<div class="card-header py-2">
					<i class="fas fa-cog me-2"></i><?= __("Template Options"); ?>
				</div>
				<div class="card-body">
					<div class="mb-2">
						<label class="form-label small mb-1" for="tplQsosPerCard"><?= __("Number of QSOs per QSL card"); ?></label>
						<input id="tplQsosPerCard" type="number" min="1" step="1" value="1" class="form-control form-control-sm">
					</div>
					<div class="mb-2" id="tplPitchWrap" style="display:none;">
						<label class="form-label small mb-1" for="tplRowPitch"><?= __("Row spacing (in)"); ?></label>
						<input id="tplRowPitch" type="number" min="0.05" step="0.05" value="0.3" class="form-control form-control-sm">
					</div>

					<div class="mb-2 form-check">
						<input type="checkbox" class="form-check-input" id="tplPerCallsign">
						<label class="form-check-label small" for="tplPerCallsign"><?= __("One postcard per callsign"); ?></label>
					</div>
					<div class="mb-2 form-check">
						<input type="checkbox" class="form-check-input" id="tplPrintBg" checked>
						<label class="form-check-label small" for="tplPrintBg"><?= __("Print background image (uncheck for pre-printed cards)"); ?></label>
					</div>
					<div class="form-check">
						<input type="checkbox" class="form-check-input" id="tplSkipAddr">
						<label class="form-check-label small" for="tplSkipAddr"><?= __("Skip address printing (for printing on regular QSL cards)"); ?></label>
					</div>
				</div>
			</aside>

		<!-- PROPERTIES (right) -->
		<aside class="qsl-pane qsl-props card">
			<div class="card-header py-2">
				<i class="fas fa-sliders-h me-2"></i><?= __("Properties"); ?>
			</div>
			<div class="card-body">
				<div id="propEmpty" class="text-muted small text-center py-4">
					<i class="fas fa-mouse-pointer fa-lg mb-2 d-block"></i>
					<?= __("Select a field on the canvas to edit it."); ?>
					<div class="mt-2"><?= __("Right-click a field for more actions."); ?></div>
				</div>

				<div id="propPanel" style="display:none;">
					<div class="mb-3">
						<span class="badge bg-secondary" id="propTypeBadge"><?= __("Field"); ?></span>
						<span class="fw-bold ms-1" id="propTypeLabel"></span>
					</div>

					<div class="mb-2" id="propTextRow" style="display:none;">
						<label class="form-label small mb-1"><?= __("Text"); ?></label>
						<input id="propText" class="form-control form-control-sm">
					</div>

					<div class="row g-2 mb-2" id="propPosRow">
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("X (in)"); ?></label>
							<input id="propX" type="number" step="0.05" class="form-control form-control-sm">
						</div>
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("Y (in)"); ?></label>
							<input id="propY" type="number" step="0.05" class="form-control form-control-sm">
						</div>
					</div>

					<div class="mb-2">
						<label class="form-label small mb-1"><?= __("Font"); ?></label>
						<select id="propFont" class="form-select form-select-sm">
							<option value="Helvetica">Helvetica</option>
							<option value="Times">Times</option>
							<option value="Courier">Courier</option>
						</select>
					</div>

					<div class="row g-2 mb-2 align-items-end">
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("Font Size"); ?></label>
							<input id="propFontSize" type="number" step="1" min="6" max="36" class="form-control form-control-sm" value="12">
						</div>
						<div class="col-6">
							<div class="form-check mt-2">
								<input class="form-check-input" type="checkbox" id="propBold">
								<label class="form-check-label small" for="propBold"><?= __("Bold"); ?></label>
							</div>
						</div>
					</div>

					<div class="mb-2">
						<label class="form-label small mb-1" for="propColor"><?= __("Color"); ?></label>
						<input type="color" id="propColor" class="form-control form-control-sm" value="#000000" style="max-width:80px;">
					</div>

					<div class="mb-3">
						<label class="form-label small mb-1"><?= __("Wrap width (in)"); ?></label>
						<input id="propWrap" type="number" step="0.1" min="0.2" class="form-control form-control-sm">
					</div>

					<div class="mb-3 form-check" id="propRepeatRow" style="display:none;">
						<input class="form-check-input" type="checkbox" id="propRepeat" title="<?= __("Print this field once per QSO when a card holds multiple QSOs"); ?>">
						<label class="form-check-label small" for="propRepeat"><?= __("Repeats per QSO"); ?></label>
					</div>

					<div class="mb-3 form-check" id="propNoSnapRow">
						<input class="form-check-input" type="checkbox" id="propNoSnap" title="<?= __("Move this element freely without snapping to the grid or other elements"); ?>">
						<label class="form-check-label small" for="propNoSnap"><?= __("Disable Auto-Snap"); ?></label>
					</div>

					<div class="d-flex gap-2">
						<button type="button" id="btnDuplicate" class="btn btn-sm btn-primary flex-fill">
							<i class="fas fa-clone me-1"></i><?= __("Duplicate"); ?>
						</button>
						<button type="button" id="btnDeleteElem" class="btn btn-sm btn-outline-danger flex-fill">
							<i class="fas fa-trash me-1"></i><?= __("Delete"); ?>
						</button>
					</div>
				</div>
			</div>
		</aside>

		</div>
	</div>
</div>

<!-- ===== CONTEXT MENU ===== -->
<div id="qslCtxMenu" class="qsl-ctx-menu" style="display:none;">
	<button type="button" class="qsl-ctx-item" data-action="duplicate"><i class="fas fa-clone fa-fw me-2"></i><?= __("Duplicate"); ?></button>

	<div class="qsl-ctx-sep" data-multi-only></div>
	<div class="qsl-ctx-sub" data-multi-only>
		<button type="button" class="qsl-ctx-item">
			<i class="fas fa-object-group fa-fw me-2"></i><?= __("Align & distribute"); ?>
			<i class="fas fa-chevron-right ms-auto ps-3"></i>
		</button>
		<div class="qsl-ctx-submenu">
			<button type="button" class="qsl-ctx-item" data-action="align:left"><i class="fas fa-align-left fa-fw me-2"></i><?= __("Align left"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:hcenter"><i class="fas fa-align-center fa-fw me-2"></i><?= __("Align horizontal centers"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:right"><i class="fas fa-align-right fa-fw me-2"></i><?= __("Align right"); ?></button>
			<div class="qsl-ctx-sep"></div>
			<button type="button" class="qsl-ctx-item" data-action="align:top"><i class="fas fa-long-arrow-alt-up fa-fw me-2"></i><?= __("Align top"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:vcenter"><i class="fas fa-arrows-alt-v fa-fw me-2"></i><?= __("Align vertical centers"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:bottom"><i class="fas fa-long-arrow-alt-down fa-fw me-2"></i><?= __("Align bottom"); ?></button>
			<div class="qsl-ctx-sep"></div>
			<button type="button" class="qsl-ctx-item" data-action="align:dist-h"><i class="fas fa-arrows-alt-h fa-fw me-2"></i><?= __("Distribute horizontally"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:dist-v"><i class="fas fa-arrows-alt-v fa-fw me-2"></i><?= __("Distribute vertically"); ?></button>
			<div class="qsl-ctx-sep"></div>
			<button type="button" class="qsl-ctx-item" data-action="align:page-h"><i class="fas fa-ruler-horizontal fa-fw me-2"></i><?= __("Center on page (horizontal)"); ?></button>
			<button type="button" class="qsl-ctx-item" data-action="align:page-v"><i class="fas fa-ruler-vertical fa-fw me-2"></i><?= __("Center on page (vertical)"); ?></button>
		</div>
	</div>

	<div class="qsl-ctx-sep"></div>
	<button type="button" class="qsl-ctx-item text-danger" data-action="delete"><i class="fas fa-trash fa-fw me-2"></i><?= __("Delete"); ?></button>
</div>
