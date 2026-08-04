
<script>
	window.gridlookupConfig = {
		tileUrl:     <?php echo json_encode($layer); ?>,
		tileAttr:    <?php echo json_encode($attribution); ?>,
		overlays:    <?php echo json_encode(isset($overlays) ? $overlays : array()); ?>,
		geojsonBase: <?php echo json_encode(base_url('assets/json/geojson/')); ?>,
		invalidMsg:  <?php echo json_encode(__("Invalid gridsquare — use 2, 4, 6, 8 or 10 characters (e.g. FN, FN31, FN31pr).")); ?>,
		bearingLbl:  <?php echo json_encode(__("Bearing")); ?>,
		measurementBase: <?php echo json_encode($measurement_base); ?>,
		stateUrl:   <?php echo json_encode(site_url('gridlookup/state_for_point')); ?>,
		wwffUrl:    <?php echo json_encode(site_url('gridlookup/wwff_directory')); ?>,
		potaUrl:    <?php echo json_encode(site_url('gridlookup/pota_directory')); ?>,
		sotaUrl:    <?php echo json_encode(site_url('gridlookup/sota_directory')); ?>,
		locatingMsg:    <?php echo json_encode(__("Locating…")); ?>,
		geoUnsupported: <?php echo json_encode(__("Location is not supported by this browser (requires HTTPS).")); ?>,
		geoDenied:      <?php echo json_encode(__("Location access denied.")); ?>,
		geoUnavailable: <?php echo json_encode(__("Location unavailable.")); ?>,
		geoTimeout:     <?php echo json_encode(__("Location request timed out.")); ?>
	};
</script>

<style>
	/*
	 * Mobile toolbar: on phones the secondary controls (second gridsquare +
	 * every map overlay) hide behind a "More options" toggle until tapped.
	 * sm+ keeps the full inline toolbar exactly as-is — the toggle is hidden
	 * there, and the .gl-secondary hiding rule only applies below sm.
	 */
	#glControls .gl-input { max-width: 200px; }
	.gl-chevron { transition: transform .15s ease; }
	#glControls.gl-expanded .gl-chevron { transform: rotate(90deg); }
	#glControls:not(.gl-expanded) .gl-more-open,
	#glControls.gl-expanded .gl-more-closed { display: none; }
	@media (max-width: 575.98px) {
		#glControls:not(.gl-expanded) .gl-secondary { display: none !important; }
		#glControls .gl-input { flex: 0 0 100%; max-width: 100%; }
		.gl-more {
			flex: 0 0 100%;
			margin-top: .25rem;
			padding-top: .5rem;
			border-top: 1px solid var(--bs-border-color, rgba(150,150,150,.45));
			border-radius: 0;
			text-align: left;
		}
	}
</style>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-body">
			<div id="glControls" class="d-flex align-items-center flex-wrap gap-2">
				<label class="form-label mb-0" for="glGrid"><?= __("Gridsquare"); ?></label>
				<input type="text" id="glGrid" class="gl-input form-control form-control-sm"
					autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="<?= __("e.g. FN31pr"); ?>" title="<?= __("2, 4, 6, 8 or 10 character Maidenhead locator"); ?>">
				<span class="gl-secondary"><?= __("to"); ?></span>
				<input type="text" id="glGrid2" class="gl-secondary gl-input form-control form-control-sm"
					autocomplete="off" autocapitalize="characters" spellcheck="false"
					placeholder="<?= __("e.g. JN58qm"); ?>" title="<?= __("Optional second gridsquare for distance and bearing"); ?>">
				<button id="glGo" class="btn btn-primary btn-sm"><?= __("Go"); ?></button>
				<button id="glClear" class="btn btn-outline-primary btn-sm"><?= __("Clear"); ?></button>
				<button id="glLocate" class="btn btn-outline-primary btn-sm" title="<?= __("Find my location and gridsquare"); ?>"><i class="fa fa-location-crosshairs"></i> <?= __("Locate me"); ?></button>
				<label class="gl-secondary form-label mb-0"><?= __("Refs"); ?></label>
				<div class="gl-secondary form-check ms-2">
					<input type="checkbox" class="form-check-input" id="glGridOverlay" checked>
					<label class="form-check-label" for="glGridOverlay"><?= __("Gridsquare"); ?></label>
				</div>
				<div class="gl-secondary form-check ms-2">
					<input type="checkbox" class="form-check-input" id="glWwffDir">
					<label class="form-check-label" for="glWwffDir"><?= __("WWFF"); ?></label>
				</div>
				<div class="gl-secondary form-check ms-2">
					<input type="checkbox" class="form-check-input" id="glPotaDir">
					<label class="form-check-label" for="glPotaDir"><?= __("POTA"); ?></label>
				</div>
				<div class="gl-secondary form-check ms-2">
					<input type="checkbox" class="form-check-input" id="glSotaDir">
					<label class="form-check-label" for="glSotaDir"><?= __("SOTA"); ?></label>
				</div>
				<div id="glOverlaysHost" class="gl-secondary ms-2"></div>
				<span id="glError" class="text-danger small ms-2" role="alert"></span>
				<button id="glMore" type="button" class="gl-more btn btn-link btn-sm d-sm-none w-100" aria-expanded="false">
					<i class="fa fa-chevron-right gl-chevron"></i>
					<span class="gl-more-closed"><?= __("More options"); ?></span>
					<span class="gl-more-open"><?= __("Hide options"); ?></span>
				</button>
			</div>
			<div id="glInfo" class="small text-muted mt-2" style="min-height:1.4rem;"></div>
		</div>
		<div id="glMap" style="width:100%; height:calc(100vh - 320px); min-height:400px;"></div>
		<div class="card-body">
			<div class="coordinates" id="glCoords" style="position: static;">
				<div class="cohidden coord-pair"><span><?= __("Latitude"); ?>:&nbsp;</span><span class="text-success fw-bold" id="latDeg"></span></div>
				<div class="cohidden coord-pair"><span><?= __("Longitude"); ?>:&nbsp;</span><span class="text-success fw-bold" id="lngDeg"></span></div>
				<div class="cohidden coord-pair"><span><?= __("Gridsquare"); ?>:&nbsp;</span><span class="text-success fw-bold" id="locator"></span></div>
				<div class="cohidden coord-pair"><span><?= __("CQ Zone"); ?>:&nbsp;</span><span class="text-success fw-bold" id="cqzonedisplay"></span></div>
				<div class="cohidden coord-pair"><span><?= __("ITU Zone"); ?>:&nbsp;</span><span class="text-success fw-bold" id="ituzonedisplay"></span></div>
			</div>
		</div>
	</div>

</div>
