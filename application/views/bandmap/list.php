<script>
	// Global variables that don't require jQuery
	var dxcluster_provider = "<?php echo base_url(); ?>index.php/dxcluster";
	var cat_timeout_interval = "<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>";
	var dxcluster_maxage = <?php echo $this->optionslib->get_option('dxcluster_maxage') ?? 60; ?>;
	var custom_date_format = "<?php echo $custom_date_format ?>";
	var popup_warning = "<?= __("Pop-up was blocked! Please allow pop-ups for this site permanently."); ?>";

	// Detect OS for proper keyboard shortcuts
	var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	var modKey = isMac ? 'Cmd' : 'Ctrl';
	var lang_click_to_prepare_logging = "<?= __("Click to prepare logging."); ?> (" + modKey + "+Click <?= __("for new window"); ?>)";

	// Enable compact radio status display for bandmap page
	window.CAT_COMPACT_MODE = true;
</script>

<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/bandmap_list.css" />

<div class="container" id="bandmapContainer">
	<div id="errormessage" style="display: none;"></div>

	<!-- Messages -->
	<div class="messages my-1 mx-3"></div>

	<!-- DX Cluster Panel -->
	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<div class="d-flex align-items-center">
				<a href="<?php echo base_url(); ?>" title="<?= __("Return to Home"); ?>">
					<img class="headerLogo me-2 bandmap-logo-fullscreen" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo'); ?>.png" alt="Logo" style="height: 32px; width: auto; cursor: pointer;" />
				</a>
				<h5 class="mb-0">DX Cluster - spot list</h5>
			</div>
			<div class="d-flex align-items-center gap-3">
				<span class="fullscreen-wavelog-text" style="display: none; font-weight: 500; color: var(--bs-body-color);">www.wavelog.org</span>
				<button type="button" class="btn btn-sm" id="fullscreenToggle" title="<?= __("Toggle Fullscreen"); ?>" style="background: none; border: none; padding: 0;">
					<i class="fas fa-expand" id="fullscreenIcon" style="font-size: 1.2rem;"></i>
				</button>
			</div>
		</div>
		<div class="card-body pt-1">

		<!-- Filters Section with darker background and rounded corners -->
		<div class="menu-bar">
	<!-- Row 1: Radio Status (left) | Radio Selector & CAT Control (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Left: Radio Status Panel (dynamically populated by JavaScript) -->
		<div id="radio_status" class="flex-shrink-0"></div>

		<!-- Spacer to push right content to end -->
		<div class="flex-grow-1"></div>

		<!-- Right: Radio Selector and CAT Control -->
		<div class="d-flex gap-2 align-items-center">
			<small class="text-muted me-1 flex-shrink-0 d-none d-md-inline"><?= __("Radio:"); ?></small>
			<select class="form-select form-select-sm radios flex-shrink-0" id="radio" name="radio" style="width: auto; min-width: 150px;">
				<option value="0" selected="selected"><?= __("None"); ?></option>
				<option value="ws"<?php if ($this->session->userdata('radio') == 'ws') { echo ' selected="selected"'; } ?>><?= __("Live - ") . __("WebSocket (Requires WLGate>=1.1.10)"); ?></option>
				<?php foreach ($radios->result() as $row) { ?>
					<option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?= __("Polling - ") . $row->radio; ?><?php if ($radio_last_updated->id == $row->id) { echo "(".__("last updated").")"; } else { echo ''; } ?></option>
				<?php } ?>
			</select>
			<!-- CAT Control Button -->
			<button class="btn btn-sm btn-primary flex-shrink-0" type="button" id="toggleCatTracking" title="<?= __("When selected the filters will be set basing on your current radio status"); ?>">
				<i class="fas fa-radio"></i> <span class="d-none d-sm-inline">CAT Control</span>
			</button>
		</div>
	</div>

	<!-- Row 2: Advanced Filters, Clear Filters (left) | de Continents (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Left: Advanced Filters and Clear Filters -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<div class="dropdown">
				<!-- Filter Dropdown Button -->
				<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
					<i class="fas fa-filter" id="filterIcon"></i> <?= __("Advanced Filters"); ?>
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
							document.getElementById('filterTipText').textContent = 'Hold ' + modKey + ' and click to select multiple options';
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
								<option value="lotw"><?= __("LoTW User"); ?></option>
								<option value="notworked"><?= __("Not worked before"); ?></option>
								<option value="Contest"><?= __("Contest"); ?></option>
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
						<button type="button" class="btn btn-sm btn-secondary" id="clearFiltersButton">
							<i class="fas fa-eraser"></i> <?= __("Clear Filters"); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Clear Filters Button -->
			<button class="btn btn-sm btn-secondary" type="button" id="clearFiltersButtonQuick" title="<?= __("Clear all filters except De Continent"); ?>">
				<i class="fas fa-eraser"></i> <span class="d-none d-sm-inline"><?= __("Clear Filters"); ?></span>
			</button>
		</div>

		<!-- Spacer to push right content to end -->
		<div class="flex-grow-1"></div>

		<!-- Right: de Continent Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<small class="text-muted me-1 flex-shrink-0"><?= __("de:"); ?></small>
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggleAfricaFilter" title="<?= __("Toggle Africa continent filter"); ?>">AF</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleAsiaFilter" title="<?= __("Toggle Asia continent filter"); ?>">AS</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleEuropeFilter" title="<?= __("Toggle Europe continent filter"); ?>">EU</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleNorthAmericaFilter" title="<?= __("Toggle North America continent filter"); ?>">NA</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleSouthAmericaFilter" title="<?= __("Toggle South America continent filter"); ?>">SA</button>
			</div>
		</div>
	</div>

	<!-- Row 3: My Favorites & Band Filters (left) and Mode Filters (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
		<!-- Left: My Favorites and Band Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<!-- Favorites Button -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-success" type="button" id="toggleFavoritesFilter" title="<?= __("Apply your favorite bands and modes (configured in Band and Mode settings)"); ?>">
					<i class="fas fa-star"></i>
				</button>
			</div>

			<!-- MF Band -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggle160mFilter" title="<?= __("Toggle 160m band filter"); ?>">160m</button>
			</div>
			<!-- HF Bands -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggle80mFilter" title="<?= __("Toggle 80m band filter"); ?>">80m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle60mFilter" title="<?= __("Toggle 60m band filter"); ?>">60m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle40mFilter" title="<?= __("Toggle 40m band filter"); ?>">40m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle30mFilter" title="<?= __("Toggle 30m band filter"); ?>">30m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle20mFilter" title="<?= __("Toggle 20m band filter"); ?>">20m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle17mFilter" title="<?= __("Toggle 17m band filter"); ?>">17m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle15mFilter" title="<?= __("Toggle 15m band filter"); ?>">15m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle12mFilter" title="<?= __("Toggle 12m band filter"); ?>">12m</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggle10mFilter" title="<?= __("Toggle 10m band filter"); ?>">10m</button>
			</div>
			<!-- VHF/UHF/SHF Bands -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggleVHFFilter" title="<?= __("Toggle VHF bands filter"); ?>">VHF</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleUHFFilter" title="<?= __("Toggle UHF bands filter"); ?>">UHF</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleSHFFilter" title="<?= __("Toggle SHF bands filter"); ?>">SHF</button>
			</div>
		</div>

		<!-- Spacer to push modes to the right -->
		<div class="flex-grow-1"></div>

		<!-- Right: Mode Filter Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center">
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggleCwFilter" title="<?= __("Toggle CW mode filter"); ?>">CW</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleDigiFilter" title="<?= __("Toggle Digital mode filter"); ?>">Digi</button>
				<button class="btn btn-sm btn-primary" type="button" id="togglePhoneFilter" title="<?= __("Toggle Phone mode filter"); ?>">Phone</button>
			</div>
		</div>
	</div>

	<!-- Row 4: Quick Filters (left) and Search (right) -->
	<div class="d-flex flex-wrap align-items-center gap-2">
		<!-- Left: Quick Filter Toggle Buttons -->
		<div class="d-flex flex-wrap gap-2 align-items-center flex-grow-1">
			<!-- Quick Filter Toggle Buttons -->
			<div class="btn-group flex-shrink-0" role="group">
				<button class="btn btn-sm btn-primary" type="button" id="toggleLotwFilter" title="<?= __("Toggle LoTW User filter"); ?>">
					<span>L</span> <span class="d-none d-sm-inline">LoTW users</span>
				</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleNotWorkedFilter" title="<?= __("Toggle Not Worked Before filter"); ?>">
					<i class="fas fa-star"></i> <span class="d-none d-sm-inline">New callsign</span>
				</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleDxccNeededFilter" title="<?= __("Toggle DXCC Needed filter"); ?>">
					<i class="fas fa-globe"></i> <span class="d-none d-sm-inline">New DXCC</span>
				</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleContestFilter" title="<?= __("Toggle Contest filter"); ?>">
					<i class="fas fa-trophy"></i> <span class="d-none d-sm-inline">Contest Only</span>
				</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleGeoHunterFilter" title="<?= __("Toggle Geo Hunter (POTA/SOTA/IOTA/WWFF)"); ?>">
					<i class="fas fa-map-marked-alt"></i> <span class="d-none d-sm-inline">Ref. Hunter</span>
				</button>
				<button class="btn btn-sm btn-primary" type="button" id="toggleFreshFilter" title="<?= __("Toggle Fresh spots filter (< 5 minutes old)"); ?>">
					<i class="fas fa-bolt"></i> <span class="d-none d-sm-inline">Fresh spots</span>
				</button>
			</div>
		</div>

		<!-- Right: Search Input -->
		<div class="input-group input-group-sm" style="max-width: 400px; width: 300px;">
			<input type="text" class="form-control" id="spotSearchInput" placeholder="<?= __("Search spots..."); ?>" aria-label="<?= __("Search"); ?>">
			<span class="input-group-text search-icon-clickable" id="searchIcon"><i class="fas fa-search"></i></span>
		</div>
	</div>
</div>
			<!-- Status Bar showing filter info and refresh timer -->
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

