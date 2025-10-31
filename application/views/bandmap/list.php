<script>
	var dxcluster_provider = "<?php echo base_url(); ?>index.php/dxcluster";
	var cat_timeout_interval = "<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>";
	var dxcluster_maxage = <?php echo $this->optionslib->get_option('dxcluster_maxage') ?? 60; ?>;
	var custom_date_format = "<?php echo $custom_date_format ?>";
	var popup_warning = "<?= __("Pop-up was blocked! Please allow pop-ups for this site permanently."); ?>";
	var lang_click_to_prepare_logging = "<?= __("Click to prepare logging."); ?>";

	// Handle "All" option for multi-select dropdowns
	$(document).ready(function() {
		// Disable Apply Filters button initially
		$('#applyFiltersButton').prop('disabled', true);

		// Function to enable Apply Filters button when changes are made
		function enableApplyButton() {
			$('#applyFiltersButton').prop('disabled', false);
		}

		// Function to handle All option selection
		function handleAllOption(selectId) {
			$('#' + selectId).on('change', function() {
				let selected = $(this).val() || [];

				// If "All" was just selected, deselect all others
				if (selected.includes('All') || selected.includes('Any')) {
					let allValue = selected.includes('All') ? 'All' : 'Any';
					if (selected.length > 1) {
						// All was just selected, keep only All
						$(this).val([allValue]);
					}
				} else if (selected.length === 0) {
					// Nothing selected, select All
					let allValue = selectId === 'decontSelect' ? 'Any' : 'All';
					$(this).val([allValue]);
				}

				// Enable Apply Filters button
				enableApplyButton();
			});
		}

		// Apply to all filter selects
		handleAllOption('cwnSelect');
		handleAllOption('decontSelect');
		handleAllOption('band');
		handleAllOption('mode');
	});
</script>

<style>
	.spotted_call {
		cursor: alias;
	}

	.kHz::after {
		content: " kHz";
	}

	.bandlist {
		-webkit-transition: all 15s ease;
		-moz-transition: all 15s ease;
		-o-transition: all 15s ease;
		transition: 15s;
	}

	.fresh {
		/* -webkit-transition: all 15s ease;
    -moz-transition: all 15s ease;
    -o-transition: all 15s ease; */
		transition: all 500ms ease;
		--bs-table-bg: #3981b2;
		--bs-table-accent-bg: #3981b2;
	}

	tbody a {
		color: inherit;
		text-decoration: none;
	}
	.dataTables_wrapper {
		margin: 10px;
	}

	/* Ensure table respects container width */
	.spottable {
		width: 100% !important;
		table-layout: auto;
	}

	.dataTables_wrapper {
		overflow-x: auto;
	}

	/* Style for multi-select dropdowns */
	select[multiple] {
		height: auto !important;
		min-height: 300px;
		max-height: 600px;
	}

	/* Make multi-select options look more interactive */
	select[multiple] option {
		padding: 8px 12px;
		border-radius: 3px;
		margin: 2px;
		cursor: pointer;
	}

	select[multiple] option:hover {
		background-color: #e9ecef !important;
	}

	select[multiple] option:checked {
		background-color: #0d6efd !important;
		color: white !important;
		font-weight: 500;
	}

	/* Add visual checkmark indicator for selected options */
	select[multiple] option:checked::before {
		content: "✓ ";
		font-weight: bold;
	}

	/* Responsive adjustments for filter dropdown */
	@media (max-width: 768px) {
		.dropdown-menu {
			min-width: 95vw !important;
			max-width: 95vw !important;
		}

		select[multiple] {
			min-height: 180px;
			max-height: 375px;
		}
	}

	@media (max-width: 576px) {
		.filterbody .row > div {
			margin-bottom: 0.5rem;
		}

		select[multiple] {
			min-height: 150px;
			max-height: 300px;
		}
	}
</style>


<div class="container">
	<br>
	<center><button type="button" class="btn" id="menutoggle"><i class="fa fa-arrow-up" id="menutoggle_i"></i></button></center>

	<div id="errormessage" style="display: none;"></div>

	<h2 id="dxtitle"><?php echo $page_title; ?></h2>

	<div class="tab-content" id="myTabContent">
		<div class="messages my-1 me-2"></div>

		<!-- Radio Selector -->
		<div class="d-flex align-items-center mb-2">
			<label class="my-1 me-2" for="radio"><?= __("Radio"); ?></label>
			<select class="form-select form-select-sm radios my-1 me-sm-2 w-auto" id="radio" name="radio">
				<option value="0" selected="selected"><?= __("None"); ?></option>
				<option value="ws"<?php if ($this->session->userdata('radio') == 'ws') { echo ' selected="selected"'; } ?>><?= __("WebSocket (Requires WLGate>1.1.10)"); ?></option>
				<?php foreach ($radios->result() as $row) { ?>
					<option value="<?php echo $row->id; ?>" <?php if ($this->session->userdata('radio') == $row->id) {
																echo "selected=\"selected\"";
															} ?>><?php echo $row->radio; ?></option>
				<?php } ?>
			</select>
		</div>

		<!-- Filters Section -->
		<div class="row pt-2">
			<div class="col-12">
				<div class="btn-toolbar d-flex flex-wrap align-items-center gap-2" role="toolbar">
					<!-- Filter Button Group -->
					<div class="btn-group" role="group">
						<!-- Main Filters Dropdown -->
						<div class="dropdown" data-bs-auto-close="outside">
							<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="fas fa-filter"></i> <?= __("Filters"); ?>
							</button>
							<div class="dropdown-menu dropdown-menu-start p-3 mt-2" aria-labelledby="filterDropdown" style="min-width: 800px; max-width: 95vw; max-height: 80vh; overflow-y: auto;">
								<div class="alert alert-info mb-3 py-2" role="alert">
									<i class="fas fa-info-circle"></i>
									<small><strong><?= __("Tip:"); ?></strong> <?= __("Hold Ctrl (Windows) or Cmd (Mac) to select multiple options. Use 'All' to select everything."); ?></small>
								</div>
								<div class="card-body filterbody">
									<div class="row">
										<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg-3">
											<label class="form-label" for="cwnSelect"><?= __("DXCC-Status"); ?></label>
											<select class="form-select form-select-sm border border-secondary" id="cwnSelect" name="dxcluster_cwn" multiple="multiple" aria-describedby="dxcluster_cwnHelp">
												<option value="All" selected><?= __("All"); ?></option>
												<option value="notwkd"><?= __("Not worked"); ?></option>
												<option value="wkd"><?= __("Worked"); ?></option>
												<option value="cnf"><?= __("Confirmed"); ?></option>
												<option value="ucnf"><?= __("Worked, not Confirmed"); ?></option>
											</select>
										</div>
										<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg-3">
											<label class="form-label" for="decontSelect"><?= __("Spots de"); ?></label>
											<select class="form-select form-select-sm border border-secondary" id="decontSelect" name="dxcluster_decont" multiple="multiple" aria-describedby="dxcluster_decontHelp">
												<option value="Any" selected><?= __("All"); ?></option>
												<option value="AF" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AF') {echo " selected";} ?>><?= __("Africa"); ?></option>
												<option value="AN" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AN') {echo " selected";} ?>><?= __("Antarctica"); ?></option>
												<option value="AS" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AS') {echo " selected";} ?>><?= __("Asia"); ?></option>
												<option value="EU" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'EU') {echo " selected";} ?>><?= __("Europe"); ?></option>
												<option value="NA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'NA') {echo " selected";} ?>><?= __("North America"); ?></option>
												<option value="OC" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'OC') {echo " selected";} ?>><?= __("Oceania"); ?></option>
												<option value="SA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'SA') {echo " selected";} ?>><?= __("South America"); ?></option>
											</select>
										</div>
										<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg-3">
											<label class="form-label" for="band"><?= __("Band"); ?></label>
											<select id="band" class="form-select form-select-sm border border-secondary" name="band" multiple="multiple">
												<option value="All" selected><?= __("All"); ?></option>
												<?php foreach ($bands as $key => $bandgroup) {
													echo '<optgroup label="' . strtoupper($key) . '">';
													foreach ($bandgroup as $band) {
														echo '<option value="' . $band . '"';
														echo '>' . $band . '</option>' . "\n";
													}
													echo '</optgroup>';
												}
												?>
											</select>
										</div>
										<div class="mb-3 col-12 col-sm-6 col-md-4 col-lg-3">
											<label class="form-label" for="mode"><?= __("Mode"); ?></label>
											<select id="mode" class="form-select form-select-sm border border-secondary" name="mode" multiple="multiple">
												<option value="All" selected><?= __("All"); ?></option>
												<option value="phone"><?= __("Phone"); ?></option>
												<option value="cw"><?= __("CW"); ?></option>
												<option value="digi"><?= __("Digi"); ?></option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Apply Filters Button -->
						<button type="button" class="btn btn-sm btn-success" id="applyFiltersButton" disabled>
							<i class="fas fa-check"></i> <?= __("Apply Filters"); ?>
						</button>
					</div>

					<!-- Search Input -->
					<div class="input-group input-group-sm" style="max-width: 250px;">
						<span class="input-group-text"><i class="fas fa-search"></i></span>
						<input type="text" class="form-control" id="spotSearchInput" placeholder="<?= __("Search spots..."); ?>" aria-label="<?= __("Search"); ?>">
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

<div class="container">
	<table class="table-sm table spottable table-bordered table-hover table-striped table-condensed">
		<thead>
			<tr class="log_title titles">
				<th style="width:200px;"><?= __("Date"); ?>/<?= __("Time"); ?></th>
				<th style="width:150px;"><?= __("Frequency"); ?></th>
				<th><?= __("Call"); ?></th>
				<th><?= __("DXCC"); ?></th>
				<th style="width:30px;"><?= __("WAC"); ?></th>
				<th style="width:150px;"><?= __("Spotter"); ?></th>
				<th><?= __("Message"); ?></th>
				<th><?= __("Last Worked"); ?></th>
				<th><?= __("Mode"); ?></th>
			</tr>
		</thead>

		<tbody class="spots_table_contents">
		</tbody>
	</table>
</div>
	</div>


