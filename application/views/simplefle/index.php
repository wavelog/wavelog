<script type="text/javascript">
	var Bands = <?php echo json_encode($bands); ?>;
	var Modes = <?php echo json_encode($modes); ?>;
	var user_id = <?php echo $this->session->userdata('user_id'); ?>;
</script>

<div class="container">

	<br>
	<div class="row">
		<div class="col" id="simpleFleInfo">
			<script>
				var lang_qso_simplefle_info_ln1 = "<?= __("Simple Fast Log Entry (FLE)"); ?>";
				var lang_qso_simplefle_info_ln2 = "<?= __("'Fast Log Entry', or simply 'FLE' is a system to log QSOs very quickly and efficiently. Due to its syntax, only a minimum of input is required to log many QSOs with as little effort as possible."); ?>";
				var lang_qso_simplefle_info_ln3 = "<?= __("FLE was originally written by DF3CB. He offers a program for Windows on his website. Simple FLE was written by OK2CQR based on DF3CB's FLE and provides a web interface to log QSOs."); ?>";
				var lang_qso_simplefle_info_ln4 = "<?= sprintf(__("A common use-case is if you have to import your paperlogs from an outdoor session and now SimpleFLE is also available in Wavelog. Information about the syntax and how FLE works can be found %s."), "<a href='https://df3cb.com/fle/documentation/' target='_blank'>" . __("here") . "</a>"); ?>";
				var lang_qso_simplefle_syntax_help = "<?= __("Syntax Help"); ?>";
				var lang_qso_simplefle_syntax_help_title = "<?= __("Syntax for FLE"); ?>";
				var lang_qso_simplefle_syntax_help_close_w_sample = "<?= __("Close and Load Sample Data"); ?>";
				var lang_qso_simplefle_options = "<?= __("Options"); ?>";
				var lang_admin_close = "<?= __("Close"); ?>";
				var lang_admin_save = "<?= __("Save"); ?>";
				var lang_qso_simplefle_error_band = "<?= __("Band is missing!"); ?>";
				var lang_qso_simplefle_error_mode = "<?= __("Mode is missing!"); ?>";
				var lang_qso_simplefle_error_time = "<?= __("Time is not set!"); ?>";
				var lang_qso_simplefle_error_date = "<?= __("Invalid date"); ?>";
				var lang_qso_simplefle_qso_list_total = "<?= __("Total"); ?>";
				var lang_gen_hamradio_qso = "<?= __("QSO"); ?>";
				var lang_qso_simplefle_error_stationcall = "<?= __("Station Call is not selected"); ?>";
				var lang_qso_simplefle_error_operator = "<?= __("'Operator' Field is empty"); ?>";
				var lang_qso_simplefle_warning_reset = "<?= __("Warning! Do you really want to reset everything?"); ?>";
				var lang_qso_simplefle_warning_missing_band_mode = "<?= __("Warning! You can't log the QSO List, because some QSO don't have band and/or mode defined!"); ?>";
				var lang_qso_simplefle_warning_missing_time = "<?= __("Warning! You can't log the QSO List, because some QSO don't have a time defined!"); ?>";
				var lang_qso_simplefle_warning_example_data = "<?= __("Attention! The Data Field containes example data. First Clear Logging Session!"); ?>";
				var lang_qso_simplefle_confirm_save_to_log = "<?= __("Are you sure that you want to add these QSO to the Log and clear the session?"); ?>";
				var lang_qso_simplefle_success_save_to_log_header = "<?= __("QSO Logged!"); ?>";
				var lang_qso_simplefle_success_save_to_log = "<?= __("The QSO were successfully logged in the logbook! Dupes were skipped."); ?>";
				var lang_qso_simplefle_error_save_to_log_header = "<?= __("Error"); ?>";
				var lang_qso_simplefle_error_save_to_log = "<?= __("An error occurred while saving the QSO to the logbook! Error: "); ?>";
			</script>
			<h2><?php echo $page_title; ?></h2>
			<button type="button" class="btn btn-sm btn-primary me-1" id="simpleFleInfoButton"><?= __("What is that?"); ?></button>
		</div>
		<div class="col-xs-12 col-lg-12 col-xl-6 text-end">
			<p><?= __("Current UTC Time"); ?></p>
			<h4 class="fw-bold" id="utc-time"></h4>
		</div>
	</div>
	<?php if ($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert alert-danger">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>
</div>
<div class="container-fluid">
	<header class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom">
		<div class="col-md-3 mb-2 mb-md-0">

		</div>

		<div class="col-md-3 justify-content-end d-flex">
		</div>
	</header>
	<div class="row mt-4">
		<!-- START BASIC QSO DATA -->
		<div class="card col-xs-12 col-md-4 simplefle" style="border: none">

			<div class="card-header">
				<?= __("QSO Data"); ?>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-xs-12 col-lg-12 col-xl-6">
						<div class="mb-3">
							<label for="qsodate"><?= __("QSO Date"); ?></label>
							<input type="date" class="form-control" id="qsodate">
							<small class="form-text text-muted"><?= __("If you don't choose a date, today's date will be used."); ?></small>
						</div>
					</div>
					<div class="col-xs-12 col-lg-12 col-xl-6">
						<label for="contest"><?= __("Contest") ?></label>
						<select name="contest" id="contest" class="form-select">
							<option value="" selected><?= __("No Contest"); ?></option>
							<?php
							foreach ($contests as $contest) {
								echo '<option value="' . $contest['adifname'] . '">' . $contest['name'] . '</option>';
							} ?>
						</select>
						<small class="form-text text-muted"><?= __("You can add the entered QSOs to a contest."); ?></small>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 col-lg-6">
						<div class="mb-3">
							<label for="stationProfile">
								<?= __("Station Call/Location"); ?>
							</label>
							<select name="station_profile" class="station_id form-select" id="stationProfile">
								<?php foreach ($station_profile->result() as $station) { ?>
									<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $this->stations->find_active()) {
																							echo 'selected';
																						} ?>>
										<?php echo $station->station_callsign . " (" . $station->station_profile_name . ")"; ?>
									</option>
								<?php } ?>
							</select>
							<div class="alert alert-danger" role="alert" id="warningStationCall" style="display: none"> </div>
							<small class="form-text text-muted"><?= sprintf(__("If you did operate from a new location, first create a new %sStation Location%s"), '<a href="'.site_url('station').'">', '</a>'); ?></small>
						</div>
					</div>
					<div class="col-xs-12 col-lg-6">
						<div class="mb-3">
							<label for="operator"><?= __("Operator"); ?> <span class="text-muted input-example"><?= __("e.g. OK2CQR"); ?></span></label>
							<input type="text" class="form-control text-uppercase" id="operator" value="<?php echo $this->session->userdata('operator_callsign'); ?>">
							<div class="alert alert-danger" role="alert" id="warningOperatorField" style="display: none"> </div>
							<small class="form-text text-muted"><?= __("This is callsign of the operator. Without any pre- or suffixes."); ?></small>
						</div>
					</div>
				</div>
			</div>

			<!-- END BASIC QSO DATA -->
			<div class="card-body">
				<div class="row">
					<div class="col">
						<p><?= __("Enter the Data"); ?></p>
						<textarea name="qso" class="form-control qso-area" cols="auto" rows="11" id="sfle_textarea" style="font-family: 'Courier New', sans-serif;"></textarea>
					</div>
				</div>
			<!-- Container for errors -->
				<div class="row" id="errorMessages">
					<div class="col">
						<div class="js-status mt-3"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="card col-xs-12 col-md-8 simplefle" style="border: none">
			<div class="card-header">
				<?= __("QSO List"); ?>
			</div>
			<div class="card-body">
				<div class="qsoList">
					<table class="table table-striped table-hover sfletable" id="qsoTable">
						<thead>
							<tr>
								<th><?= __("Date"); ?></th>
								<th><?= __("Time"); ?></th>
								<th><?= __("Callsign"); ?></th>
								<th><?= __("Band"); ?></th>
								<th><?= __("Mode"); ?></th>
								<th><?= __("RST (S)"); ?></th>
								<th><?= __("RST (R)"); ?></th>
								<th><?= __("Gridsquare"); ?></th>
								<th><?= __("Refs"); ?>*</th>
							</tr>
						</thead>
						<tbody id="qsoTableBody">
						</tbody>
					</table>
				</div>
				<div class="row mt-2">
					<div class="col-6 col-sm-6">
						<span class="js-qso-count"></span>
					</div>
					<div class="col-6 col-sm-6 text-end" id="refs_hint">
						<?= sprintf(__("The Refs can be either %sS%sOTA, %sI%sOTA, %sP%sOTA, or %sW%sWFF"), '<u>', '</u>', '<u>', '</u>', '<u>', '</u>', '<u>', '</u>'); ?>
					</div>
				</div>
			</div>
			<div class="row mt-2">
				<div class="btn-group" role="group" aria-label="sfle button group">

					<button class="btn btn-primary js-reload-qso"><?= __("Reload QSO List"); ?></button>
					<button class="btn btn-warning js-save-to-log"><?= __("Save in Wavelog"); ?></button>
					<button class="btn btn-danger js-empty-qso"><?= __("Clear Logging Session"); ?></button>
					<button class="btn btn-success" id="js-syntax"><?= __("Syntax Help"); ?></button>
					<button class="btn btn-secondary" id="js-options"><?= __("Options"); ?></button>

				</div>
			</div>
		</div>
	</div>
</div>