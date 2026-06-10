<script>
	// ===== Translatable strings (PHP → JS) =====
	const LANG = {
		pleaseChooseImage: <?= json_encode(__("Please choose an image first.")); ?>,
		uploading: <?= json_encode(__("Uploading...")); ?>,
		uploadFailed: <?= json_encode(__("Upload failed")); ?>,
		unknownError: <?= json_encode(__("unknown error")); ?>,
		previewUploaded: <?= json_encode(__("Preview image uploaded.")); ?>,
		customText: <?= json_encode(__("Custom Text")); ?>,
		enterCustomText: <?= json_encode(__("Enter custom text:")); ?>,
		comments: <?= json_encode(__("Comments:")); ?>,
		untitled: <?= json_encode(__("Untitled")); ?>,
		saveFailed: <?= json_encode(__("Save failed")); ?>,
		savedReload: <?= json_encode(__("Saved. Reload page to see template in list.")); ?>
	};
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= __("QSL Postcard Designer (4x6 Landscape)"); ?></h2>
		<div class="row">

			<!-- ===== LEFT SIDEBAR ===== -->
			<div class="col-md-3">

				<!-- Card: Template -->
				<div class="card mb-3">
					<div class="card-header">
						<i class="fas fa-file-image me-2"></i> <?= __("Template"); ?>
					</div>
					<div class="card-body">
						<select id="tplSelect" class="form-control mb-2">
							<option value=""><?= __("(new)"); ?></option>
							<?php foreach ($templates as $t): ?>
								<option value="<?= (int)$t['id'] ?>"><?= htmlentities($t['name']) ?></option>
							<?php endforeach; ?>
						</select>

						<input id="tplName" class="form-control mb-2" placeholder="<?= __("Template name"); ?>">
						<label><?= __("Postcard preview image"); ?></label>
						<input type="file" id="previewImageFile" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp">

						<button type="button" id="btnUploadPreview" class="btn btn-secondary w-100 mb-2">
							<?= __("Upload Preview Image"); ?>
						</button>

						<div id="previewImageStatus" class="small mb-2"></div>
						<button id="btnSave" class="btn btn-primary w-100 mb-2"><?= __("Save Template"); ?></button>

						<div class="small text-muted mb-2">
							<?= __("Tip: After a test print, adjust global offsets rather than moving every field."); ?>
						</div>

						<label><?= __("Global offset X (inches)"); ?></label>
						<input id="offX" type="number" step="0.01" class="form-control mb-2" value="0">

						<label><?= __("Global offset Y (inches)"); ?></label>
						<input id="offY" type="number" step="0.01" class="form-control mb-3" value="0">

						<a id="btnPdf" class="btn btn-success w-100" href="#" target="_blank"><?= __("Generate PDF (demo)"); ?></a>
					</div>
				</div>

				<!-- Card: Properties -->
				<div class="card mb-3">
					<div class="card-header">
						<i class="fas fa-sliders-h me-2"></i> <?= __("Properties"); ?>
					</div>
					<div class="card-body">
						<div id="noSelection" class="small">
							<?= __("Click a placed field to edit its properties."); ?>
						</div>

						<div id="selectionPanel" style="display:none;">
							<label class="form-label"><?= __("Font"); ?></label>
							<select id="propFont" class="form-control mb-2">
								<option value="Helvetica">Helvetica</option>
								<option value="Times">Times</option>
								<option value="Courier">Courier</option>
							</select>

							<label class="form-label"><?= __("Font Size"); ?></label>
							<input id="propFontSize" type="number" step="1" min="6" max="36" class="form-control mb-2" value="12">

							<div class="form-check mb-2">
								<input class="form-check-input" type="checkbox" id="propBold">
								<label class="form-check-label" for="propBold">
									<?= __("Bold"); ?>
								</label>
							</div>

							<button type="button" id="btnApplyProps" class="btn btn-primary w-100 mb-2">
								<?= __("Apply"); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Card: Address Fields -->
				<div class="card mb-3">
					<div class="card-header">
						<i class="fas fa-address-card me-2"></i> <?= __("Address"); ?>
					</div>
					<div class="card-body">
						<div class="qsl_designer_field" data-field="addr.name">addr.name</div>
						<div class="qsl_designer_field" data-field="addr.addr1">addr.addr1</div>
						<div class="qsl_designer_field" data-field="addr.addr2">addr.addr2</div>
						<div class="qsl_designer_field" data-field="addr.city_state_zip">addr.city_state_zip</div>
						<div class="qsl_designer_field" data-field="addr.country">addr.country</div>
					</div>
				</div>

				<!-- Card: QSO Fields -->
				<div class="card mb-3">
					<div class="card-header">
						<i class="fas fa-broadcast-tower me-2"></i> <?= __("QSO"); ?>
					</div>
					<div class="card-body">
						<button type="button" id="btnAddText" class="btn btn-outline-primary w-100 mb-2">
							<?= __("Add Custom Text"); ?>
						</button>
						<div class="qsl_designer_field" data-field="qso.call">qso.call</div>
						<div class="qsl_designer_field" data-field="qso.qso_date">qso.qso_date</div>
						<div class="qsl_designer_field" data-field="qso.time_on">qso.time_on</div>
						<div class="qsl_designer_field" data-field="qso.band">qso.band</div>
						<div class="qsl_designer_field" data-field="qso.mode">qso.mode</div>
						<div class="qsl_designer_field" data-field="qso.freq">qso.freq</div>
						<div class="qsl_designer_field" data-field="qso.rst_sent">qso.rst_sent</div>
						<div class="qsl_designer_field" data-field="qso.rst_rcvd">qso.rst_rcvd</div>
						<div class="qsl_designer_field" data-field="qso.summary">qso.summary</div>
						<div class="qsl_designer_field" data-field="qso.rig">qso.rig</div>
						<div class="qsl_designer_field" data-field="qso.comment">qso.comment</div>
						<div class="qsl_designer_field" data-field="qso.time_utc">qso.time_utc</div>
						<div class="qsl_designer_field" data-field="qso.day">qso.day</div>
						<div class="qsl_designer_field" data-field="qso.month">qso.month</div>
						<div class="qsl_designer_field" data-field="qso.month_name">qso.month_name</div>
						<div class="qsl_designer_field" data-field="qso.year">qso.year</div>
						<div class="qsl_designer_field" data-field="qso.antenna">qso.antenna</div>
						<div class="qsl_designer_field" data-field="qso.tx_power">qso.tx_power</div>
						<div class="qsl_designer_field" data-field="qso.rx_power">qso.rx_power</div>
						<div class="qsl_designer_field" data-field="qso.my_rig">qso.my_rig</div>
						<div class="qsl_designer_field" data-field="qso.pota_ref">qso.pota_ref</div>
						<div class="qsl_designer_field" data-field="qso.my_pota_ref">qso.my_pota_ref</div>
						<div class="qsl_designer_field" data-field="qso.pota_line">qso.pota_line</div>
						<div class="qsl_designer_field" data-field="qso.qsl_message">qso.qsl_message</div>
					</div>
				</div>

			</div>

			<!-- ===== RIGHT STAGE ===== -->
			<div class="col-md-9">
				<div class="card">
					<div class="card-header">
						<i class="fas fa-expand me-2"></i> <?= __("Postcard Canvas"); ?>
					</div>
					<div class="card-body p-2">
						<div id="stageWrap">
							<div id="rulerWrap">
								<!-- TOP RULER -->
								<div id="rulerTop"></div>
								<!-- LEFT RULER -->
								<div id="rulerLeft"></div>
								<!-- POSTCARD STAGE -->
								<div id="stage"></div>
							</div>
						</div>
						<div class="small text-muted mt-2">
							<?= __("Stage = 6 × 4 inches (900 × 600 px). Drag fields onto the postcard."); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
