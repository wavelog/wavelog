<?php
// Palette field groups. The keys (data-field) MUST stay exactly as the PDF
// renderer expects them (qso.*). Only the grouping/labels are cosmetic.
// Same set as the postcard designer minus the Address group (labels carry
// QSO data, not callbook addresses).
$qsl_field_groups = [
	__("QSO Core")            => ['qso.call', 'qso.band', 'qso.mode', 'qso.sat_name', 'qso.sat_mode', 'qso.freq', 'qso.rst_sent', 'qso.r_sent', 'qso.s_sent', 'qso.t_sent', 'qso.rst_rcvd', 'qso.summary'],
	__("Date & Time")         => ['qso.qso_date', 'qso.time_on', 'qso.time', 'qso.time_utc', 'qso.day', 'qso.month', 'qso.month_name', 'qso.year'],
	__("Station & Equipment") => ['qso.station_callsign', 'qso.operator', 'qso.tx_power'],
	__("My References")       => ['qso.my_pota_ref', 'qso.pota_line', 'qso.my_sota_ref', 'qso.sota_line', 'qso.my_iota_ref', 'qso.iota_line', 'qso.my_grid'],
	__("Markers")             => ['qso.pse_qsl', 'qso.tnx_qsl', 'qso.pse_qsl_tnx_text','qso.portable', 'qso.mobile'],
	__("Other")               => ['qso.comment', 'qso.qsl_message', 'qso.qsl_via'],
];

// User measurement preference drives the designer's display unit:
// kilometers → centimeters, everything else → inches. Internal layout
// values stay in inches; only labels/inputs/ruler switch units.
$_umb        = $this->session->userdata('user_measurement_base') ?? $this->config->item('measurement_base');
$_metric     = ($_umb === 'K');
$_disp_u     = $_metric ? 'cm' : 'in';
$_step_fine  = $_metric ? '0.1'  : '0.01';   // print offsets
$_step_pos   = $_metric ? '0.05' : '0.025';  // X / Y position (labels are small)
$_step_wrap  = $_metric ? '0.1'  : '0.05';   // wrap width
$_step_pitch = $_metric ? '0.05' : '0.025';  // row pitch

// Per-label-type geometry for the JS canvas (converted to inches). The live
// label type stays authoritative at render time; this only sizes the canvas.
$label_types_js = [];
foreach (($labels ?? []) as $l) {
	$_w = ($l->metric == 'in') ? (float)$l->width  : (float)$l->width  / 25.4;
	$_h = ($l->metric == 'in') ? (float)$l->height : (float)$l->height / 25.4;
	$label_types_js[(int)$l->id] = [
		'w_in'    => round($_w, 4),
		'h_in'    => round($_h, 4),
		'nx'      => (int)$l->nx,
		'ny'      => (int)$l->ny,
		'name'    => (string)$l->label_name,
		'has_paper' => (($l->paper_name ?? '') !== ''),
	];
}
?>
<script>
	// ===== Translatable strings (PHP → JS) =====
	const LANG = {
		customText: <?= json_encode(__("Custom Text")); ?>,
		line: <?= json_encode(__("Line")); ?>,
		table: <?= json_encode(__("Table")); ?>,
		untitled: <?= json_encode(__("Untitled")); ?>,
		saveFailed: <?= json_encode(__("Save failed")); ?>,
		saved: <?= json_encode(__("Template saved.")); ?>,
		deleteTemplate: <?= json_encode(__("Delete Template?")); ?>,
		deleteTemplateConfirm: <?= json_encode(__("Are you sure you want to delete this template? This action cannot be undone.")); ?>,
		deleteFailed: <?= json_encode(__("Delete failed")); ?>,
		deleteSuccess: <?= json_encode(__("Template deleted successfully!")); ?>,
		selectTemplateToDelete: <?= json_encode(__("Please select a template to delete.")); ?>,
		copyFailed: <?= json_encode(__("Copy failed")); ?>,
		copySuccess: <?= json_encode(__("Template copied.")); ?>,
		selectTemplateToCopy: <?= json_encode(__("Please select a template to copy.")); ?>,
		success: <?= json_encode(__("Success")); ?>,
		error: <?= json_encode(__("Error")); ?>,
		selected: <?= json_encode(__("selected")); ?>,
		selectLabelTypeFirst: <?= json_encode(__("Please select a label type first.")); ?>,
		labelTypeMissing: <?= json_encode(__("The label type of this template no longer exists. Please select another one.")); ?>,
		unsavedChangesTitle: <?= json_encode(__("Unsaved changes")); ?>,
		unsavedChangesConfirm: <?= json_encode(__("Your current design has unsaved changes. Loading a different template will replace it. Continue?")); ?>,
		unsavedLeaveConfirm: <?= json_encode(__("Your current design has unsaved changes. If you leave the page, those changes will be lost. Leave anyway?")); ?>,
		discardChanges: <?= json_encode(__("Discard changes")); ?>,
		keepEditing: <?= json_encode(__("Keep editing")); ?>,
		leavePage: <?= json_encode(__("Leave page")); ?>,
		importFailed: <?= json_encode(__("Import failed")); ?>,
		exportFailed: <?= json_encode(__("Export failed")); ?>,
		importSuccess: <?= json_encode(__("Template imported.")); ?>,
		selectTemplateToExport: <?= json_encode(__("Please select a template to export.")); ?>,
		fileTooLarge: <?= json_encode(__("File is too large.")); ?>,
		noPaperAssigned: <?= json_encode(__("No paper assigned")); ?>,
	};

	// Label type geometry: id → {w_in, h_in, nx, ny, name, has_paper}
	const LABEL_TYPES = <?= json_encode($label_types_js) ?>;
</script>

<div class="container-fluid px-3 px-lg-4 mt-3 mb-3" id="lblDesigner">

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
						<input id="tplName" class="form-control form-control-sm" maxlength="100" style="min-width:140px;" placeholder="<?= __("Template name"); ?>">
						<button id="btnSave" class="btn btn-sm btn-success text-nowrap" title="<?= __("Save Template"); ?>">
							<i class="fas fa-save me-1"></i><?= __("Save"); ?>
						</button>
						<button id="btnCopy" class="btn btn-sm btn-outline-primary text-nowrap" title="<?= __("Copy Template"); ?>">
							<i class="fas fa-copy"></i>
						</button>
						<button id="btnDelete" class="btn btn-sm btn-outline-danger text-nowrap" title="<?= __("Delete Template"); ?>">
							<i class="fas fa-trash"></i>
						</button>
						<button id="btnExport" class="btn btn-sm btn-outline-primary text-nowrap" title="<?= __("Export Template"); ?>">
							<i class="fas fa-file-export"></i>
						</button>
						<button id="btnImport" class="btn btn-sm btn-outline-primary text-nowrap" title="<?= __("Import Template"); ?>">
							<i class="fas fa-file-import"></i>
						</button>
						<input type="file" id="importFile" accept=".json,application/json" hidden>
					</div>
				</div>

				<!-- Label type group -->
				<div class="qsl-tb-group">
					<label class="qsl-tb-label"><?= __("Label type"); ?></label>
					<div class="d-flex gap-2 align-items-center">
						<select id="labelTypeSelect" class="form-select form-select-sm" style="min-width:200px;">
							<option value=""><?= __("(select label type)"); ?></option>
							<?php foreach (($labels ?? []) as $l): ?>
								<option value="<?= (int)$l->id ?>"><?= htmlentities($l->label_name) ?><?= ($l->paper_name ?? '') == '' ? ' — ' . __("No paper assigned") : '' ?></option>
							<?php endforeach; ?>
						</select>
						<div class="btn-group btn-group-sm" role="group">
							<a id="btnPdf" class="text-nowrap btn btn-primary" href="#" target="_blank" title="<?= __("Generate sample PDF with your latest QSOs"); ?>">
								<i class="fas fa-file-pdf me-1"></i><?= __("PDF"); ?>
							</a>
							<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
							<ul class="dropdown-menu">
								<li><a class="dropdown-item" href="#" id="btnPdfSave" target="_blank"><i class="fas fa-download me-1"></i><?= __("Save PDF"); ?></a></li>
							</ul>
						</div>
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
						<?= __("Print offset"); ?> (<?= $_disp_u ?>)
					</label>
					<div class="d-flex gap-2 align-items-center">
						<div class="input-group input-group-sm" style="width:96px;">
							<span class="input-group-text">X</span>
							<input id="offX" type="number" step="<?= $_step_fine ?>" class="form-control" value="0">
						</div>
						<div class="input-group input-group-sm" style="width:96px;">
							<span class="input-group-text">Y</span>
							<input id="offY" type="number" step="<?= $_step_fine ?>" class="form-control" value="0">
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

				<div class="d-flex gap-2 mb-2">
					<button type="button" id="btnAddLineH" class="btn btn-sm btn-outline-primary w-100" title="<?= __("Horizontal rule — enable 'Repeats per QSO' to draw a separator under every QSO row"); ?>">
						<i class="fas fa-grip-lines me-1"></i><?= __("Horizontal Line"); ?>
					</button>
					<button type="button" id="btnAddLineV" class="btn btn-sm btn-outline-primary w-100" title="<?= __("Vertical rule — place at column edges to build a table grid"); ?>">
						<i class="fas fa-grip-lines-vertical me-1"></i><?= __("Vertical Line"); ?>
					</button>
				</div>

				<button type="button" id="btnAddTable" class="btn btn-sm btn-outline-primary w-100 mb-2" title="<?= __("Table grid — drag the corner to resize, drag the column markers to adjust column widths"); ?>">
					<i class="fas fa-table me-1"></i><?= __("Add Table"); ?>
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
				<span><i class="fas fa-expand me-2"></i><?= __("Label Canvas"); ?></span>
				<span class="small text-muted d-none d-lg-inline"><span id="lblDims"></span> · <?= __("drag fields onto the label"); ?></span>
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
						<label class="form-label small mb-1" for="tplQsosPerLabel"><?= __("Number of QSOs per label"); ?></label>
						<input id="tplQsosPerLabel" type="number" min="1" step="1" value="1" class="form-control form-control-sm">
					</div>
					<div class="mb-2" id="tplPitchWrap" style="display:none;">
						<label class="form-label small mb-1" for="tplRowPitch"><?= __("Row spacing"); ?> (<?= $_disp_u ?>)</label>
						<input id="tplRowPitch" type="number" min="0.05" step="<?= $_step_pitch ?>" value="0.3" class="form-control form-control-sm">
					</div>
					<div class="mb-2 form-check">
						<input type="checkbox" class="form-check-input" id="tplRowSeparators">
						<label class="form-check-label small" for="tplRowSeparators" title="<?= __("Draws a rule between the QSO rows automatically, like the classic label layout"); ?>"><?= __("Separator lines between QSO rows"); ?></label>
					</div>
					<div class="mb-2" id="tplSepThickWrap" style="display:none;">
						<label class="form-label small mb-1" for="tplSepThick"><?= __("Separator thickness"); ?> (pt)</label>
						<input id="tplSepThick" type="number" min="0.1" max="4" step="0.1" value="0.4" class="form-control form-control-sm">
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

				<div class="mb-2" id="propFreqFormatRow" style="display:none;">
					<label class="form-label small mb-1"><?= __("Frequency format"); ?></label>
					<select id="propFreqFormat" class="form-select form-select-sm">
						<option value="MHz">MHz</option>
						<option value="kHz">kHz</option>
						<option value="Hz">Hz</option>
					</select>
					<div class="form-check mt-1">
						<input class="form-check-input" type="checkbox" id="propFreqNoUnit">
						<label class="form-check-label small" for="propFreqNoUnit"><?= __("Omit unit"); ?></label>
					</div>
				</div>

					<div class="row g-2 mb-2" id="propPosRow">
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("X"); ?> (<?= $_disp_u ?>)</label>
							<input id="propX" type="number" step="<?= $_step_pos ?>" class="form-control form-control-sm">
						</div>
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("Y"); ?> (<?= $_disp_u ?>)</label>
							<input id="propY" type="number" step="<?= $_step_pos ?>" class="form-control form-control-sm">
						</div>
					</div>

					<div class="mb-2" id="propTableRow" style="display:none;">
						<div class="row g-2 mb-2">
							<div class="col-6">
								<label class="form-label small mb-1" for="propTableRows"><?= __("Rows"); ?></label>
								<input id="propTableRows" type="number" min="1" max="20" step="1" class="form-control form-control-sm">
							</div>
							<div class="col-6">
								<label class="form-label small mb-1" for="propTableCols"><?= __("Columns"); ?></label>
								<input id="propTableCols" type="number" min="1" max="12" step="1" class="form-control form-control-sm">
							</div>
						</div>
						<div class="row g-2 mb-2">
							<div class="col-6">
								<label class="form-label small mb-1"><?= __("Width"); ?> (<?= $_disp_u ?>)</label>
								<input id="propTableW" type="number" step="<?= $_step_pos ?>" min="0.1" class="form-control form-control-sm">
							</div>
							<div class="col-6">
								<label class="form-label small mb-1"><?= __("Height"); ?> (<?= $_disp_u ?>)</label>
								<input id="propTableH" type="number" step="<?= $_step_pos ?>" min="0.05" class="form-control form-control-sm">
							</div>
						</div>
						<label class="form-label small mb-1" for="propTableThick"><?= __("Thickness"); ?> (pt)</label>
						<input id="propTableThick" type="number" step="0.1" min="0.1" max="4" value="0.4" class="form-control form-control-sm">
					</div>

					<div class="mb-2" id="propLineRow" style="display:none;">
						<label class="form-label small mb-1"><?= __("Orientation"); ?></label>
						<select id="propLineOrient" class="form-select form-select-sm">
							<option value="h"><?= __("Horizontal"); ?></option>
							<option value="v"><?= __("Vertical"); ?></option>
						</select>
						<div class="row g-2 mt-1">
							<div class="col-6">
								<label class="form-label small mb-1"><?= __("Length"); ?> (<?= $_disp_u ?>)</label>
								<input id="propLineLen" type="number" step="<?= $_step_pos ?>" min="0.05" class="form-control form-control-sm">
							</div>
							<div class="col-6">
								<label class="form-label small mb-1"><?= __("Thickness"); ?> (pt)</label>
								<input id="propLineThick" type="number" step="0.1" min="0.1" max="4" class="form-control form-control-sm">
							</div>
						</div>
					</div>

					<div class="mb-2" id="propFontRow">
						<label class="form-label small mb-1"><?= __("Font"); ?></label>
						<select id="propFont" class="form-select form-select-sm">
							<option value="Helvetica">Helvetica</option>
							<option value="Times">Times</option>
							<option value="Courier">Courier</option>
						</select>
					</div>

					<div class="row g-2 mb-2 align-items-end" id="propFontMiscRow">
						<div class="col-6">
							<label class="form-label small mb-1"><?= __("Font Size"); ?></label>
							<input id="propFontSize" type="number" step="1" min="4" max="36" class="form-control form-control-sm" value="8">
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

					<div class="mb-3" id="propWrapRow">
						<label class="form-label small mb-1"><?= __("Wrap width"); ?> (<?= $_disp_u ?>)</label>
						<input id="propWrap" type="number" step="<?= $_step_wrap ?>" min="0.1" class="form-control form-control-sm">
					</div>

					<div class="mb-3 form-check" id="propRepeatRow" style="display:none;">
						<input class="form-check-input" type="checkbox" id="propRepeat" title="<?= __("Print this field once per QSO if a label holds multiple QSOs"); ?>">
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
