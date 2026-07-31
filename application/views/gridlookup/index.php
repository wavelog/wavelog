
<script>
	window.gridlookupConfig = {
		tileUrl:     <?php echo json_encode($layer); ?>,
		tileAttr:    <?php echo json_encode($attribution); ?>,
		overlays:    <?php echo json_encode(isset($overlays) ? $overlays : array()); ?>,
		geojsonBase: <?php echo json_encode(base_url('assets/json/geojson/')); ?>,
		invalidMsg:  <?php echo json_encode(__("Invalid gridsquare — use 2, 4, 6, 8 or 10 characters (e.g. FN, FN31, FN31pr).")); ?>,
		bearingLbl:  <?php echo json_encode(__("Bearing")); ?>
	};
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-body">
			<div class="d-flex align-items-center flex-wrap gap-2">
				<label class="form-label mb-0 fw-bold" for="glGrid"><?= __("Gridsquare"); ?></label>
				<input type="text" id="glGrid" class="form-control form-control-sm" style="max-width:200px;"
					autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="<?= __("e.g. FN31pr"); ?>" title="<?= __("2, 4, 6, 8 or 10 character Maidenhead locator"); ?>">
				<span class="text-muted small fw-bold"><?= __("to"); ?></span>
				<input type="text" id="glGrid2" class="form-control form-control-sm" style="max-width:200px;"
					autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="<?= __("e.g. JN58qm"); ?>" title="<?= __("Optional second gridsquare for distance and bearing"); ?>">
				<button id="glGo" class="btn btn-primary btn-sm"><?= __("Go"); ?></button>
				<button id="glClear" class="btn btn-outline-primary btn-sm"><?= __("Clear"); ?></button>
				<div class="form-check ms-2">
					<input type="checkbox" class="form-check-input" id="glGridOverlay" checked>
					<label class="form-check-label" for="glGridOverlay"><?= __("Maidenhead grid"); ?></label>
				</div>
				<div id="glOverlaysHost" class="ms-2"></div>
				<span id="glError" class="text-danger small ms-2" role="alert"></span>
			</div>
			<div id="glInfo" class="small text-muted mt-2" style="min-height:1.4rem;"></div>
		</div>
		<div id="glMap" style="width:100%; height:calc(100vh - 320px); min-height:400px;"></div>
		<div class="card-body">
			<div class="coordinates" id="glCoords" style="position: static;">
				<div class="cohidden coord-pair"><span><?= __("Latitude"); ?>:&nbsp;</span><span class="text-success fw-bold" id="latDeg"></span></div>
				<div class="cohidden coord-pair"><span><?= __("Longitude"); ?>:&nbsp;</span><span class="text-success fw-bold" id="lngDeg"></span></div>
				<div class="cohidden coord-pair"><span><?= __("Gridsquare"); ?>:&nbsp;</span><span class="text-success fw-bold" id="locator"></span></div>
			</div>
		</div>
	</div>

</div>
