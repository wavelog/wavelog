<script>
	// Global variables that don't require jQuery
	var dxcluster_provider = "<?php echo base_url(); ?>index.php/dxcluster";
	var cat_timeout_interval = "<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>";
	var dxcluster_maxage = <?php echo $this->optionslib->get_option('dxcluster_maxage') ?? 60; ?>;
	var custom_date_format = "<?php echo $custom_date_format ?>";

	// Detect OS for proper keyboard shortcuts
	var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	var modKey = isMac ? 'Cmd' : 'Ctrl';
	var lang_click_to_prepare_logging = "<?= __("Click to prepare logging."); ?> (" + modKey + "+Click <?= __("to tune frequency"); ?>)";

	// Bandmap toast messages
	var lang_bandmap_popup_blocked = "<?= __("Pop-up Blocked"); ?>";
	var lang_bandmap_popup_warning = "<?= __("Pop-up was blocked! Please allow pop-ups for this site permanently."); ?>";
	var lang_bandmap_cat_required = "<?= __("CAT Connection Required"); ?>";
	var lang_bandmap_enable_cat = "<?= __("Enable CAT connection to tune the radio"); ?>";
	var lang_bandmap_clear_filters = "<?= __("Clear Filters"); ?>";
	var lang_bandmap_band_preserved = "<?= __("Band filter preserved (CAT connection is active)"); ?>";
	var lang_bandmap_radio = "<?= __("Radio"); ?>";
	var lang_bandmap_radio_none = "<?= __("Radio set to None - CAT connection disabled"); ?>";
	var lang_bandmap_radio_tuned = "<?= __("Radio Tuned"); ?>";
	var lang_bandmap_tuned_to = "<?= __("Tuned to"); ?>";
	var lang_bandmap_tuning_failed = "<?= __("Tuning Failed"); ?>";
	var lang_bandmap_tune_failed_msg = "<?= __("Failed to tune radio to frequency"); ?>";
	var lang_bandmap_qso_prepared = "<?= __("QSO Prepared"); ?>";
	var lang_bandmap_callsign_sent = "<?= __("Callsign"); ?>";
	var lang_bandmap_sent_to_form = "<?= __("sent to logging form"); ?>";
	var lang_bandmap_cat_control = "<?= __("CAT Connection"); ?>";
	var lang_bandmap_freq_changed = "<?= __("Frequency filter changed to"); ?>";
	var lang_bandmap_by_transceiver = "<?= __("by transceiver"); ?>";
	var lang_bandmap_freq_filter_set = "<?= __("Frequency filter set to"); ?>";
	var lang_bandmap_freq_outside = "<?= __("Frequency outside known bands - showing all bands"); ?>";
	var lang_bandmap_waiting_radio = "<?= __("Waiting for radio data..."); ?>";
	var lang_bandmap_my_favorites = "<?= __("My Favorites"); ?>";
	var lang_bandmap_favorites_failed = "<?= __("Failed to load favorites"); ?>";
	var lang_bandmap_modes_applied = "<?= __("Modes applied. Band filter preserved (CAT connection is active)"); ?>";
	var lang_bandmap_favorites_applied = "<?= __("Applied your favorite bands and modes"); ?>";

	// Bandmap filter status messages
	var lang_bandmap_loading_data = "<?= __("Loading data from DX Cluster"); ?>";
	var lang_bandmap_last_fetched = "<?= __("Last fetched for"); ?>";
	var lang_bandmap_max_age = "<?= __("Max Age"); ?>";
	var lang_bandmap_fetched_at = "<?= __("Fetched at"); ?>";
	var lang_bandmap_next_update = "<?= __("Next update in"); ?>";
	var lang_bandmap_minutes = "<?= __("minutes"); ?>";
	var lang_bandmap_seconds = "<?= __("seconds"); ?>";

	// Bandmap filter labels
	var lang_bandmap_not_worked = "<?= __("Not worked"); ?>";
	var lang_bandmap_worked = "<?= __("Worked"); ?>";
	var lang_bandmap_confirmed = "<?= __("Confirmed"); ?>";
	var lang_bandmap_worked_not_confirmed = "<?= __("Worked, not Confirmed"); ?>";
	var lang_bandmap_lotw_user = "<?= __("LoTW User"); ?>";
	var lang_bandmap_new_callsign = "<?= __("New Callsign"); ?>";
	var lang_bandmap_new_continent = "<?= __("New Continent"); ?>";
	var lang_bandmap_new_country = "<?= __("New Country"); ?>";
	var lang_bandmap_worked_before = "<?= __("Worked Before"); ?>";

	// Bandmap filter prefixes
	var lang_bandmap_dxcc = "<?= __("DXCC"); ?>";
	var lang_bandmap_band = "<?= __("Band"); ?>";
	var lang_bandmap_mode = "<?= __("Mode"); ?>";
	var lang_bandmap_continent = "<?= __("Continent"); ?>";
	var lang_bandmap_all = "<?= __("All"); ?>";
	var lang_bandmap_de = "<?= __("de"); ?>";
	var lang_bandmap_spotted = "<?= __("spotted"); ?>";

	// Bandmap tooltip messages
	var lang_bandmap_fresh_spot = "<?= __("Fresh spot (< 5 minutes old)"); ?>";
	var lang_bandmap_contest = "<?= __("Contest"); ?>";
	var lang_bandmap_contest_name = "<?= __("Contest"); ?>"; // Same as above, for "Contest: NAME" format
	var lang_bandmap_click_view_qrz = "<?= __("Click to view"); ?>";
	var lang_bandmap_on_qrz = "<?= __("on QRZ.com"); ?>";
	var lang_bandmap_see_details = "<?= __("See details for"); ?>";
	var lang_bandmap_worked_on = "<?= __("Worked on"); ?>";
	var lang_bandmap_not_worked_band = "<?= __("Not worked on this band"); ?>";

	// Bandmap UI messages
	var lang_bandmap_exit_fullscreen = "<?= __("Exit Fullscreen"); ?>";
	var lang_bandmap_toggle_fullscreen = "<?= __("Toggle Fullscreen"); ?>";
	var lang_bandmap_cat_band_control = "<?= __("Band filtering is controlled by your radio when CAT connection is enabled"); ?>";
	var lang_bandmap_click_to_qso = "<?= __("Click to prepare logging"); ?>";
	var lang_bandmap_ctrl_click_tune = "<?= __("to tune frequency"); ?>";
	var lang_bandmap_requires_cat = "<?= __("(requires CAT connection)"); ?>";
	var lang_bandmap_spotter = "<?= __("Spotter"); ?>";
	var lang_bandmap_comment = "<?= __("Comment"); ?>";
	var lang_bandmap_age = "<?= __("Age"); ?>";
	var lang_bandmap_time = "<?= __("Time"); ?>";
	var lang_bandmap_incoming = "<?= __("Incoming"); ?>";
	var lang_bandmap_outgoing = "<?= __("Outgoing"); ?>";
	var lang_bandmap_spots = "<?= __("spots"); ?>";
	var lang_bandmap_spot = "<?= __("spot"); ?>";
	var lang_bandmap_spotters = "<?= __("spotters"); ?>";


	// DataTables messages
	var lang_bandmap_loading_spots = "<?= __("Loading spots..."); ?>";
	var lang_bandmap_no_spots_found = "<?= __("No spots found"); ?>";
	var lang_bandmap_no_data = "<?= __("No data available"); ?>";
	var lang_bandmap_no_spots_filters = "<?= __("No spots found for selected filters"); ?>";
	var lang_bandmap_error_loading = "<?= __("Error loading spots. Please try again."); ?>";

	// Offline radio status messages
	var lang_bandmap_show_all_modes = "<?= __("Show all modes"); ?>";
	var lang_bandmap_show_all_spots = "<?= __("Show all spots"); ?>";

	// DX Map Visualization

	// DX Map translation strings
	var lang_bandmap_draw_spotters = "<?= __("Draw Spotters"); ?>";
	var lang_bandmap_extend_map = "<?= __("Extend Map"); ?>";
	var lang_bandmap_show_daynight = "<?= __("Show Day/Night"); ?>";
	var lang_bandmap_your_qth = "<?= __("Your QTH"); ?>";
	var lang_bandmap_callsign = "<?= __("Callsign"); ?>";
	var lang_bandmap_frequency = "<?= __("Frequency"); ?>";
	var lang_bandmap_mode = "<?= __("Mode"); ?>";
	var lang_bandmap_band = "<?= __("Band"); ?>";

	// Enable ultra-compact radio status display for bandmap page (tooltip only)
	window.CAT_COMPACT_MODE = 'ultra-compact';

	// Map configuration (matches QSO map settings)
	var map_tile_server = '<?php echo $this->optionslib->get_option('option_map_tile_server');?>';
	var map_tile_server_copyright = '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>';
	var icon_dot_url = "<?php echo base_url();?>assets/images/dot.png";

	// User gridsquare for home position marker
	var user_gridsquare = '<?php
		if (($this->optionslib->get_option("station_gridsquare") ?? "") != "") {
			echo $this->optionslib->get_option("station_gridsquare");
		} else if (null !== $this->config->item("locator")) {
			echo $this->config->item("locator");
		} else {
			echo "IO91WM";
		}
	?>';
</script>

<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/bandmap_list.css" />

<div class="container" id="bandmapContainer">
	<!-- Messages -->
	<div class="messages my-1 mx-3"></div>

	<!-- DX Cluster Panel -->
	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<div class="d-flex align-items-center">
				<a href="<?php echo base_url(); ?>" title="<?= __("Return to Home"); ?>">
					<img class="headerLogo me-2 bandmap-logo-fullscreen" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo'); ?>.png" alt="Logo" style="height: 32px; width: auto; cursor: pointer;" />
				</a>
				<h5 class="mb-0"><?= __("DX Cluster"); ?></h5>
			</div>
		<div class="d-flex align-items-center gap-3">
			<a href="https://www.wavelog.org" target="_blank" class="fullscreen-wavelog-text" style="display: none; font-weight: 500; color: var(--bs-body-color); text-decoration: none;">www.wavelog.org</a>
			<a href="https://github.com/wavelog/wavelog/wiki/DXCluster" target="_blank" title="<?= __("DX Cluster Help"); ?>" style="cursor: pointer; padding: 0.5rem; margin: -0.5rem; color: var(--bs-body-color); text-decoration: none; display: inline-flex; align-items: center;">
				<i class="fas fa-question-circle" style="font-size: 1.2rem;"></i>
			</a>
			<div id="fullscreenToggleWrapper" style="cursor: pointer; padding: 0.5rem; margin: -0.5rem;">
				<button type="button" class="btn btn-sm" id="fullscreenToggle" title="<?= __("Toggle Fullscreen"); ?>" style="background: none; border: none; padding: 0.5rem;">
					<i class="fas fa-expand" id="fullscreenIcon" style="font-size: 1.2rem;"></i>
				</button>
			</div>
		</div>
		</div>
		<div class="card-body pt-1">

		<!-- Filters Section with darker background and rounded corners -->
		<div class="menu-bar">
	<!-- Row 1: CAT Connection, Radio Selector, Radio Status (left) | de Continents (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Left: CAT Connection Button -->
		<button class="btn btn-sm btn-secondary flex-shrink-0" type="button" id="toggleCatTracking" title="<?= __("When selected the filters will be set basing on your current radio status"); ?>">
			<i class="fas fa-radio"></i> <span class="d-none d-sm-inline"><?= __("CAT Connection"); ?></span>
		</button>

		<!-- Radio Selector Dropdown -->
		<small class="text-muted me-1 flex-shrink-0 d-none d-md-inline"><?= __("Radio:"); ?></small>
		<select class="form-select form-select-sm radios flex-shrink-0" id="radio" name="radio" style="width: auto; min-width: 150px;">
			<option value="0" selected="selected"><?= __("None"); ?></option>
			<option value="ws"<?php if ($this->session->userdata('radio') == 'ws') { echo ' selected="selected"'; } ?>><?= __("Live - ") . __("WebSocket (Requires WLGate>=1.1.10)"); ?></option>
			<?php foreach ($radios->result() as $row) { ?>
				<option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?= __("Polling - ") . $row->radio; ?><?php if ($radio_last_updated->id == $row->id) { echo "(".__("last updated").")"; } else { echo ''; } ?></option>
			<?php } ?>
		</select>

		<!-- Radio Status Panel (ultra-compact, dynamically populated by JavaScript) -->
		<div id="radio_status" class="d-flex align-items-center" style="flex: 1 1 auto; min-width: 0;"></div>

		<!-- Right: de Continent Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<small class="text-muted me-1 flex-shrink-0"><?= __("de:"); ?></small>
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-secondary" type="button" id="toggleAllContinentsFilter" title="<?= __("Select all continents"); ?>"><?= __("All"); ?></button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleAfricaFilter" title="<?= __("Toggle Africa continent filter"); ?>">AF</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleAntarcticaFilter" title="<?= __("Toggle Antarctica continent filter"); ?>">AN</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleAsiaFilter" title="<?= __("Toggle Asia continent filter"); ?>">AS</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleEuropeFilter" title="<?= __("Toggle Europe continent filter"); ?>">EU</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleNorthAmericaFilter" title="<?= __("Toggle North America continent filter"); ?>">NA</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleOceaniaFilter" title="<?= __("Toggle Oceania continent filter"); ?>">OC</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleSouthAmericaFilter" title="<?= __("Toggle South America continent filter"); ?>">SA</button>
			</div>
		</div>
	</div>

	<!-- Row 2: Advanced Filters, Favorites, Clear Filters | Band Filters (left) and Mode Filters (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Left: Advanced Filters, Favorites, Clear Filters, and Band Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<!-- Button Group: Advanced Filters + Favorites + Clear Filters -->
			<div class="btn-group flex-shrink-0" role="group">
				<div class="dropdown">
					<!-- Filter Dropdown Button -->
					<button class="btn btn-sm btn-secondary" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" title="<?= __("Advanced Filters"); ?>">
						<i class="fas fa-filter" id="filterIcon"></i>
					</button>
					<div class="dropdown-menu dropdown-menu-start p-3 mt-2" aria-labelledby="filterDropdown" style="min-width: 1264px; max-width: 95vw; max-height: 98vh; overflow-y: auto;">
					<!-- Filter tip -->
					<div class="filter-tip">
						<i class="fas fa-info-circle"></i>
						<span id="filterTipText"></span>
					</div>
					<script>
						// Set filter tip text based on OS
						document.addEventListener('DOMContentLoaded', function() {
							var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
							var modKey = isMac ? 'Cmd' : 'Ctrl';
							document.getElementById('filterTipText').textContent = '<?= __("Hold"); ?> ' + modKey + ' <?= __("and click to select multiple options"); ?>';
						});
					</script>
					<div class="row">
						<!-- Column 1: DXCC-Status and Mode -->
						<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg">
							<label class="form-label d-block filter-label-small" for="cwnSelect"><?= __("DXCC-Status"); ?></label>
							<select class="form-select form-select-sm filter-short" id="cwnSelect" name="dxcluster_cwn" multiple="multiple" aria-describedby="dxcluster_cwnHelp">
								<option value="All" selected><?= __("All"); ?></option>
								<option value="notwkd"><?= __("Not worked"); ?></option>
								<option value="wkd"><?= __("Worked"); ?></option>
								<option value="cnf"><?= __("Confirmed"); ?></option>
								<option value="ucnf"><?= __("Worked, not Confirmed"); ?></option>
							</select>
							<label class="form-label d-block filter-label-small mt-3" for="mode"><?= __("Mode"); ?></label>
							<select id="mode" class="form-select form-select-sm filter-short" name="mode" multiple="multiple">
								<option value="All" selected><?= __("All"); ?></option>
								<option value="phone"><?= __("Phone"); ?></option>
								<option value="cw"><?= __("CW"); ?></option>
								<option value="digi"><?= __("Digi"); ?></option>
							</select>
						</div>
						<!-- Column 2: Required Flags and Additional Flags -->
						<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg">
						<label class="form-label d-block filter-label-small" for="requiredFlags"><?= __("Required Flags"); ?></label>
						<select id="requiredFlags" class="form-select form-select-sm filter-short" name="required_flags" multiple="multiple">
							<option value="None" selected><?= __("None"); ?></option>
							<option value="lotw"><?= __("LoTW User"); ?></option>
							<option value="newcontinent"><?= __("New Continent"); ?></option>
							<option value="newcountry"><?= __("New Country"); ?></option>
							<option value="newcallsign"><?= __("New Callsign"); ?></option>
							<option value="workedcallsign"><?= __("Worked Callsign"); ?></option>
							<option value="Contest"><?= __("Contest"); ?></option>
							<option value="dxspot"><?= __("DX Spot"); ?></option>
						</select>
							<label class="form-label d-block filter-label-small mt-3" for="additionalFlags"><?= __("Additional Flags"); ?></label>
							<select id="additionalFlags" class="form-select form-select-sm filter-short" name="additional_flags" multiple="multiple">
								<option value="All" selected><?= __("All"); ?></option>
								<option value="SOTA"><?= __("SOTA"); ?></option>
								<option value="POTA"><?= __("POTA"); ?></option>
								<option value="WWFF"><?= __("WWFF"); ?></option>
								<option value="IOTA"><?= __("IOTA"); ?></option>
								<option value="Fresh"><?= __("Fresh (< 5 min)"); ?></option>
							</select>
						</div>
						<!-- Column 3: Spots de Continent -->
						<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg">
							<label class="form-label d-block filter-label-small" for="decontSelect"><?= __("Spots de Continent"); ?></label>
							<select class="form-select form-select-sm" id="decontSelect" name="dxcluster_decont" multiple="multiple" aria-describedby="dxcluster_decontHelp">
								<option value="Any"<?php if ($this->optionslib->get_option('dxcluster_decont') == '' || $this->optionslib->get_option('dxcluster_decont') == 'Any') {echo " selected";} ?>><?= __("All"); ?></option>
								<option value="AF" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AF') {echo " selected";} ?>><?= __("Africa"); ?></option>
								<option value="AN" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AN') {echo " selected";} ?>><?= __("Antarctica"); ?></option>
								<option value="AS" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AS') {echo " selected";} ?>><?= __("Asia"); ?></option>
								<option value="EU" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'EU') {echo " selected";} ?>><?= __("Europe"); ?></option>
								<option value="NA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'NA') {echo " selected";} ?>><?= __("North America"); ?></option>
								<option value="OC" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'OC') {echo " selected";} ?>><?= __("Oceania"); ?></option>
								<option value="SA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'SA') {echo " selected";} ?>><?= __("South America"); ?></option>
							</select>
						</div>
						<!-- Column 4: Spotted Station Continent -->
						<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg">
							<label class="form-label d-block filter-label-small" for="continentSelect"><?= __("Spotted Station Continent"); ?></label>
							<select id="continentSelect" class="form-select form-select-sm" name="continent" multiple="multiple">
								<option value="Any" selected><?= __("All"); ?></option>
								<option value="AF"><?= __("Africa"); ?></option>
								<option value="AN"><?= __("Antarctica"); ?></option>
								<option value="AS"><?= __("Asia"); ?></option>
								<option value="EU"><?= __("Europe"); ?></option>
								<option value="NA"><?= __("North America"); ?></option>
								<option value="OC"><?= __("Oceania"); ?></option>
								<option value="SA"><?= __("South America"); ?></option>
							</select>
						</div>
						<!-- Column 5: Band -->
						<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg">
							<label class="form-label d-block filter-label-small" for="band"><?= __("Band"); ?></label>
							<select id="band" class="form-select form-select-sm" name="band" multiple="multiple">
								<option value="All" selected><?= __("All"); ?></option>
								<optgroup label="MF">
									<option value="160m">160m</option>
								</optgroup>
								<optgroup label="HF">
									<option value="80m">80m</option>
									<option value="60m">60m</option>
									<option value="40m">40m</option>
									<option value="30m">30m</option>
									<option value="20m">20m</option>
									<option value="17m">17m</option>
									<option value="15m">15m</option>
									<option value="12m">12m</option>
									<option value="10m">10m</option>
								</optgroup>
								<optgroup label="VHF">
									<option value="6m">6m</option>
									<option value="4m">4m</option>
									<option value="2m">2m</option>
									<option value="1.25m">1.25m</option>
								</optgroup>
								<optgroup label="UHF">
									<option value="70cm">70cm</option>
									<option value="33cm">33cm</option>
									<option value="23cm">23cm</option>
								</optgroup>
								<optgroup label="SHF">
									<option value="13cm">13cm</option>
									<option value="9cm">9cm</option>
									<option value="6cm">6cm</option>
									<option value="3cm">3cm</option>
									<option value="1.25cm">1.25cm</option>
									<option value="6mm">6mm</option>
									<option value="4mm">4mm</option>
									<option value="2.5mm">2.5mm</option>
									<option value="2mm">2mm</option>
									<option value="1mm">1mm</option>
								</optgroup>
							</select>
						</div>
					</div>
					<!-- Buttons in popup -->
					<div class="text-center mt-3">
						<button type="button" class="btn btn-sm btn-success me-2" id="applyFiltersButtonPopup">
						<i class="fas fa-check"></i> <?= __("Apply Filters"); ?>
					</button>
					<button type="button" class="btn btn-sm btn-secondary" id="clearFiltersButton" title="<?= __("Clear Filters"); ?>">
						<i class="fas fa-filter-circle-xmark text-danger"></i>
					</button>
				</div>
			</div>
		</div>
				<!-- Favorites Button (part of button group) -->
				<button class="btn btn-sm btn-secondary" type="button" id="toggleFavoritesFilter" title="<?= __("Apply your favorite bands and modes (configured in Band and Mode settings)"); ?>">
					<i class="fas fa-star text-warning"></i>
				</button>
				<!-- Clear Filters Button (part of button group) -->
				<button class="btn btn-sm btn-secondary" type="button" id="clearFiltersButtonQuick" title="<?= __("Clear all filters except De Continent"); ?>">
					<i class="fas fa-filter-circle-xmark text-danger"></i>
				</button>
			</div>

			<!-- MF Band -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-secondary" type="button" id="toggle160mFilter" title="<?= __("Toggle 160m band filter"); ?>">160m</button>
			</div>
			<!-- HF Bands -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-secondary" type="button" id="toggle80mFilter" title="<?= __("Toggle 80m band filter"); ?>">80m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle60mFilter" title="<?= __("Toggle 60m band filter"); ?>">60m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle40mFilter" title="<?= __("Toggle 40m band filter"); ?>">40m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle30mFilter" title="<?= __("Toggle 30m band filter"); ?>">30m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle20mFilter" title="<?= __("Toggle 20m band filter"); ?>">20m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle17mFilter" title="<?= __("Toggle 17m band filter"); ?>">17m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle15mFilter" title="<?= __("Toggle 15m band filter"); ?>">15m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle12mFilter" title="<?= __("Toggle 12m band filter"); ?>">12m</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggle10mFilter" title="<?= __("Toggle 10m band filter"); ?>">10m</button>
			</div>
			<!-- VHF/UHF/SHF Bands -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-secondary" type="button" id="toggleVHFFilter" title="<?= __("Toggle VHF bands filter"); ?>">VHF</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleUHFFilter" title="<?= __("Toggle UHF bands filter"); ?>">UHF</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleSHFFilter" title="<?= __("Toggle SHF bands filter"); ?>">SHF</button>
			</div>
		</div>

		<!-- Spacer to push modes to the right -->
		<div class="flex-grow-1"></div>

		<!-- Right: Mode Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-secondary" type="button" id="toggleCwFilter" title="<?= __("Toggle CW mode filter"); ?>">CW</button>
				<button class="btn btn-sm btn-secondary" type="button" id="toggleDigiFilter" title="<?= __("Toggle Digital mode filter"); ?>">Digi</button>
				<button class="btn btn-sm btn-secondary" type="button" id="togglePhoneFilter" title="<?= __("Toggle Phone mode filter"); ?>">Phone</button>
			</div>
		</div>
	</div>

	<!-- Row 3: Quick Filters -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- LoTW Users Button (separate) -->
		<div class="btn-group flex-shrink-0" role="group">
			<button class="btn btn-sm btn-secondary" type="button" id="toggleLotwFilter" title="<?= __("Toggle LoTW User filter"); ?>">
				<i class="fas fa-upload"></i> <span class="d-none d-sm-inline"><?= __("LoTW users"); ?></span>
			</button>
		</div>

		<!-- DX Spot, Continent, Country, Callsign Group -->
		<div class="btn-group flex-shrink-0" role="group">
			<button class="btn btn-sm btn-secondary" type="button" id="toggleDxSpotFilter" title="<?= __("Toggle DX Spot filter (spotted continent ≠ spotter continent)"); ?>">
				<i class="fas fa-globe"></i> <span class="d-none d-sm-inline"><?= __("DX Spots"); ?></span>
			</button>
			<button class="btn btn-sm btn-secondary" type="button" id="toggleNewContinentFilter" title="<?= __("Toggle New Continent filter"); ?>">
				<i class="fas fa-medal" style="color: #FFD700;"></i> <span class="d-none d-sm-inline"><?= __("New Continents"); ?></span>
			</button>
			<button class="btn btn-sm btn-secondary" type="button" id="toggleDxccNeededFilter" title="<?= __("Toggle New Country filter"); ?>">
				<i class="fas fa-medal" style="color: #C0C0C0;"></i> <span class="d-none d-sm-inline"><?= __("New DXCCs"); ?></span>
			</button>
			<button class="btn btn-sm btn-secondary" type="button" id="toggleNewCallsignFilter" title="<?= __("Toggle New Callsign filter"); ?>">
				<i class="fas fa-medal" style="color: #CD7F32;"></i> <span class="d-none d-sm-inline"><?= __("New Callsigns"); ?></span>
			</button>
		</div>

		<!-- Fresh, Contest, Ref. Hunter Group -->
		<div class="btn-group flex-shrink-0" role="group">
			<button class="btn btn-sm btn-secondary" type="button" id="toggleFreshFilter" title="<?= __("Toggle Fresh spots filter (< 5 minutes old)"); ?>">
				<i class="fas fa-bolt"></i> <span class="d-none d-sm-inline"><?= __("Fresh Spots"); ?></span>
			</button>
			<button class="btn btn-sm btn-secondary" type="button" id="toggleContestFilter" title="<?= __("Toggle Contest filter"); ?>">
				<i class="fas fa-trophy"></i> <span class="d-none d-sm-inline"><?= __("Contest Spots"); ?></span>
			</button>
			<button class="btn btn-sm btn-secondary" type="button" id="toggleGeoHunterFilter" title="<?= __("Toggle Geo Hunter (POTA/SOTA/IOTA/WWFF)"); ?>">
				<i class="fas fa-hiking"></i> <span class="d-none d-sm-inline"><?= __("Referenced Spots"); ?></span>
			</button>
		</div>

		<!-- DX Map Button (right side) -->
		<div class="ms-auto">
			<button class="btn btn-sm btn-primary" type="button" id="dxMapButton" title="<?= __("Open DX Map view"); ?>">
				<i class="fas fa-map-marked-alt"></i> <span class="d-none d-sm-inline"><?= __("DX Map"); ?></span>
			</button>
		</div>
	</div>

	<!-- Row 5: Status Bar (70%) and Search (30%) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Status Bar - 70% -->
		<div style="flex: 1 1 0; min-width: 300px;">
			<div class="status-bar">
				<div class="status-bar-inner">
					<div class="status-bar-left">
						<span id="statusMessage"></span>
					</div>
					<div class="status-bar-right">
						<i class="fas fa-hourglass-half me-1" id="refreshIcon"></i>
						<span id="refreshTimer"></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Search Input - 30% -->
		<div class="input-group input-group-sm" style="flex: 0 0 auto; min-width: 200px; max-width: 400px; position: relative;">
			<input type="text" class="form-control" id="spotSearchInput" placeholder="<?= __("Search spots..."); ?>" aria-label="<?= __("Search"); ?>">
			<button class="btn btn-sm" id="clearSearchBtn" style="position: absolute; right: 40px; top: 50%; transform: translateY(-50%); z-index: 10; background: transparent; border: none; padding: 0 5px; display: none; cursor: pointer;">
				<i class="fas fa-times" style="color: #6c757d;"></i>
			</button>
			<span class="input-group-text search-icon-clickable" id="searchIcon"><i class="fas fa-search"></i></span>
		</div>
	</div>
</div>

	<!-- DX Map Container (initially hidden) -->
	<div id="dxMapContainer" style="display: none; margin-bottom: 15px;">
		<div id="dxMap" style="height: 345px; width: 100%; border: 1px solid #dee2e6; border-radius: 4px;"></div>
		<div style="font-size: 11px; color: #6c757d; text-align: center; margin-top: 5px; font-style: italic;">
			<i class="fas fa-info-circle"></i> <?= __("Note: Map shows DXCC entity locations, not actual spot locations"); ?>
		</div>
	</div>

			<div class="table-responsive">
				<table class="table table-striped table-hover spottable">
					<thead>
						<tr class="log_title titles">
							<th title="<?= __("Age in minutes"); ?>"><i class="fas fa-clock"></i></th>
							<th title="<?= __("Band"); ?>"><i class="fas fa-wave-square"></i></th>
							<th title="<?= __("Frequency"); ?> [MHz]"><?= __("Freq"); ?></th>
							<th title="<?= __("Mode"); ?>"><i class="fas fa-broadcast-tower"></i></th>
							<th title="<?= __("Spotted Callsign"); ?>"><?= __("Spotted"); ?></th>
							<th title="<?= __("Continent"); ?>"><i class="fas fa-globe-americas"></i></th>
							<th title="<?= __("CQ Zone"); ?>"><i class="fas fa-map-marked"></i></th>
							<th title="<?= __("Flag"); ?>"><i class="fas fa-flag"></i></th>
							<th title="<?= __("DXCC Entity"); ?>"><?= __("Entity"); ?></th>
							<th title="<?= __("DXCC Number"); ?>"><i class="fas fa-hashtag"></i></th>
							<th title="<?= __("Spotter Callsign"); ?>"><?= __("Spotter"); ?></th>
							<th title="<?= __("Spotter Continent"); ?>"><i class="fas fa-globe-americas"></i></th>
							<th title="<?= __("Spotter CQ Zone"); ?>"><i class="fas fa-map-marked"></i></th>
							<th title="<?= __("Special Flags"); ?>"><?= __("Special"); ?></th>
							<th title="<?= __("Message"); ?>"><?= __("Message"); ?></th>
						</tr>
					</thead>

					<tbody class="spots_table_contents">
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

</div>

