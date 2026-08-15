
<script>
	window.activationplannerConfig = {
		tileUrl:     <?php echo json_encode($layer); ?>,
		tileAttr:    <?php echo json_encode($attribution); ?>,
		overlays:    <?php echo json_encode(isset($overlays) ? $overlays : array()); ?>,
		geojsonBase: <?php echo json_encode(base_url('assets/json/geojson/')); ?>,
		invalidMsg:  <?php echo json_encode(__("Invalid gridsquare — use 2, 4, 6, 8 or 10 characters (e.g. FN, FN31, FN31pr).")); ?>,
		bearingLbl:  <?php echo json_encode(__("Bearing")); ?>,
		measurementBase: <?php echo json_encode($measurement_base); ?>,
		stateUrl:   <?php echo json_encode(site_url('activationplanner/state_for_point')); ?>,
		wwffUrl:    <?php echo json_encode(site_url('activationplanner/wwff_directory')); ?>,
		potaUrl:    <?php echo json_encode(site_url('activationplanner/pota_directory')); ?>,
		activatedPotaUrl: <?php echo json_encode(site_url('activationplanner/activated_pota')); ?>,
		potaBoundaryUrl: <?php echo json_encode(site_url('activationplanner/pota_boundary') . '/'); ?>,
		sotaUrl:    <?php echo json_encode(site_url('activationplanner/sota_directory')); ?>,
		iotaUrl:    <?php echo json_encode(site_url('activationplanner/iota_directory')); ?>,
		dxccGridUrl: <?php echo json_encode(site_url('activationplanner/dxcc_for_grid')); ?>,
		refsNearbyUrl: <?php echo json_encode(site_url('activationplanner/refs_nearby')); ?>,
		satPassUrl: <?php echo json_encode(site_url('satellite/pass')); ?>,
		satPassLbl: <?php echo json_encode(__("Satellite passes")); ?>,
		bordersLbl:    <?php echo json_encode(__("Gridsquare borders")); ?>,
		gridLbl:       <?php echo json_encode(__("Gridsquare")); ?>,
		nearbyRefsLbl: <?php echo json_encode(__("Nearby refs")); ?>,
		nearbyRefsRadiusLbl: <?php echo json_encode(__("References within %s of the gridsquare")); ?>,
		colTypeLbl:           <?php echo json_encode(__("Type")); ?>,
		colReferenceLbl:      <?php echo json_encode(__("Reference")); ?>,
		colNameLbl:           <?php echo json_encode(__("Name")); ?>,
		colDistanceLbl:       <?php echo json_encode(__("Distance")); ?>,
		closeLbl:      <?php echo json_encode(__("Close")); ?>,
		errorLbl:      <?php echo json_encode(__("Error")); ?>,
		trackingLbl:   <?php echo json_encode(__("Tracking")); ?>,
		fieldLbl:         <?php echo json_encode(__("Field")); ?>,
		squareLbl:        <?php echo json_encode(__("Square")); ?>,
		subsquareLbl:     <?php echo json_encode(__("Subsquare")); ?>,
		locatingMsg:    <?php echo json_encode(__("Locating…")); ?>,
		geoDenied:      <?php echo json_encode(__("Location access denied.")); ?>,
		geoUnavailable: <?php echo json_encode(__("Location unavailable.")); ?>,
		geoTimeout:     <?php echo json_encode(__("Location request timed out.")); ?>,
		createStationUrl:    <?php echo json_encode(site_url('station/create')); ?>,
		newStationLocLbl:    <?php echo json_encode(__("Create station location")); ?>,
		refsTitleLbl:        <?php echo json_encode(__("References in this grid")); ?>,
		activatedLbl:        <?php echo json_encode(__("QSOs")); ?>,
		lastLbl:             <?php echo json_encode(__("last")); ?>,
		inactiveLbl:         <?php echo json_encode(__("Inactive")); ?>,
		validRangeLbl:       <?php echo json_encode(__("valid")); ?>,
		userDxcc:            <?php echo json_encode(isset($user_dxcc) ? $user_dxcc : null); ?>,
		shareLbl:            <?php echo json_encode(__("Share")); ?>,
		shareActivationTitleLbl: <?php echo json_encode(__("Share activation")); ?>,
		planningActivationLbl:   <?php echo json_encode(__("📻 Planning an activation from %s")); ?>,
		searchPlaceholderLbl:    <?php echo json_encode(__("Search references…")); ?>,
		searchNoMatchesLbl:      <?php echo json_encode(__("No matches")); ?>,
		searchLoadingLbl:        <?php echo json_encode(__("Loading…")); ?>,
		searchRefsHeaderLbl:     <?php echo json_encode(__("References")); ?>,
		searchPlacesLbl:         <?php echo json_encode(__("Places")); ?>,
		searchEnterHintLbl:      <?php echo json_encode(__("Press Enter to search places")); ?>
	};
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#glTopBody" aria-expanded="true" aria-controls="glTopBody">
			<h6 class="mb-0"><?= __("Plan your activity here"); ?> <i class="fas fa-chevron-down float-end gl-top-chevron" style="font-size: 0.75rem; line-height: 1.5;"></i></h6>
		</div>
		<div class="collapse show" id="glTopBody">
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
					<button id="glNearby" class="btn btn-outline-primary btn-sm"><?= __("Nearby refs"); ?></button>
					<div class="gl-search-wrap">
						<i class="fas fa-magnifying-glass gl-search-icon"></i>
						<input type="text" id="glRefSearch" class="gl-search-input form-control form-control-sm"
							autocomplete="off" spellcheck="false"
							placeholder="<?= __("Search references…"); ?>" title="<?= __("Search WWFF / POTA / SOTA / IOTA by name or reference — Enter searches place names (OpenStreetMap)"); ?>">
						<div class="gl-search-results" id="glRefSearchResults" hidden></div>
					</div>
					<div class="gl-refsrow gl-secondary">
						<div class="gl-refs gl-secondary dropdown">
							<button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><?= __("Refs"); ?></button>
							<ul class="dropdown-menu p-2" style="max-height:60vh; overflow-y:auto;">
								<li><label class="dropdown-item d-flex align-items-center"><input type="checkbox" class="form-check-input me-2" id="glGridOverlay"><?= __("Gridsquare"); ?></label></li>
								<li><label class="dropdown-item d-flex align-items-center"><input type="checkbox" class="form-check-input me-2" id="glWwffDir"><?= __("WWFF"); ?><span class="ref-menu-dot ms-auto" style="background:#2b8cbe">W</span></label></li>
								<li><label class="dropdown-item d-flex align-items-center"><input type="checkbox" class="form-check-input me-2" id="glPotaDir"><?= __("POTA"); ?><span class="ref-menu-dot ms-auto" style="background:#238b45">P</span></label></li>
								<li><label class="dropdown-item d-flex align-items-center"><input type="checkbox" class="form-check-input me-2" id="glSotaDir"><?= __("SOTA"); ?><span class="ref-menu-dot ms-auto" style="background:#d95f0e">S</span></label></li>
								<li><label class="dropdown-item d-flex align-items-center"><input type="checkbox" class="form-check-input me-2" id="glIotaDir"><?= __("IOTA"); ?><span class="ref-menu-dot ms-auto" style="background:#17a2b8">I</span></label></li>
							</ul>
						</div>
						<div id="glOverlaysHost" class="gl-secondary"></div>
					</div>
					<button id="glMore" type="button" class="gl-more btn btn-link btn-sm d-sm-none w-100" aria-expanded="false">
						<span class="gl-chevron" aria-hidden="true"></span>
						<span class="gl-more-closed"><?= __("More options"); ?></span>
						<span class="gl-more-open"><?= __("Hide options"); ?></span>
					</button>
				</div>
			</div>
		</div>
		<div class="card-body p-0 mb-2 ms-2 me-2 mt-2">
			<div id="glInfo"></div>
		</div>
		<div id="glMap" style="width:100%;"></div>
		<div class="card-body gl-coords-body">
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
