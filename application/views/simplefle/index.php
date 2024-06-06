<script type="text/javascript">
	var Bands = <?php echo json_encode($bands); ?>;
	var Modes = <?php echo json_encode($modes); ?>;
	var user_id = <?php echo $this->session->userdata('user_id'); ?>;
</script>

<div class="container">

	<br>
	<div id="simpleFleInfo">
		<script>
			var lang_qso_simplefle_info_ln1 = "<?php echo __("Simple Fast Log Entry (FLE)"); ?>";
			var lang_qso_simplefle_info_ln2 = "<?php echo __("'Fast Log Entry', or simply 'FLE' is a system to log QSOs very quickly and efficiently. Due to its syntax, only a minimum of input is required to log many QSOs with as little effort as possible."); ?>";
			var lang_qso_simplefle_info_ln3 = "<?php echo __("FLE was originally written by DF3CB. He offers a program for Windows on his website. Simple FLE was written by OK2CQR based on DF3CB's FLE and provides a web interface to log QSOs."); ?>";
			var lang_qso_simplefle_info_ln4 = "<?php echo sprintf(__("A common use-case is if you have to import your paperlogs from an outdoor session and now SimpleFLE is also available in Wavelog. Information about the syntax and how FLE works can be found %s."), "<a href='https://df3cb.com/fle/documentation/' target='_blank'>here</a>"); ?>";
			var lang_qso_simplefle_syntax_help = "<?php echo __("Syntax Help"); ?>";
			var lang_qso_simplefle_syntax_help_title = "<?php echo __("Syntax for FLE"); ?>";
			var lang_qso_simplefle_syntax_help_close_w_sample = "<?php echo __("Close and Load Sample Data"); ?>";
			var lang_qso_simplefle_options = "<?php echo __("Options"); ?>";
			var lang_admin_close = "<?php echo __("Close"); ?>";
			var lang_admin_save = "<?php echo __("Save"); ?>";
			var lang_qso_simplefle_error_band = "<?php echo __("Band is missing!"); ?>";
			var lang_qso_simplefle_error_mode = "<?php echo __("Mode is missing!"); ?>";
			var lang_qso_simplefle_error_time = "<?php echo __("Time is not set!"); ?>";
			var lang_qso_simplefle_error_date = "<?php echo __("Invalid date"); ?>";
			var lang_qso_simplefle_qso_list_total = "<?php echo __("Total"); ?>";
			var lang_gen_hamradio_qso = "<?php echo __("QSO"); ?>";
			var lang_qso_simplefle_error_stationcall = "<?php echo __("Station Call is not selected"); ?>";
			var lang_qso_simplefle_error_operator = "<?php echo __("'Operator' Field is empty"); ?>";
			var lang_qso_simplefle_warning_reset = "<?php echo __("Warning! Do you really want to reset everything?"); ?>";
			var lang_qso_simplefle_warning_missing_band_mode = "<?php echo __("Warning! You can't log the QSO List, because some QSO don't have band and/or mode defined!"); ?>";
			var lang_qso_simplefle_warning_missing_time = "<?php echo __("Warning! You can't log the QSO List, because some QSO don't have a time defined!"); ?>";
			var lang_qso_simplefle_warning_example_data = "<?php echo __("Attention! The Data Field containes example data. First Clear Logging Session!"); ?>";
			var lang_qso_simplefle_confirm_save_to_log = "<?php echo __("Are you sure that you want to add these QSO to the Log and clear the session?"); ?>";
			var lang_qso_simplefle_success_save_to_log_header = "<?php echo __("QSO Logged!"); ?>";
			var lang_qso_simplefle_success_save_to_log = "<?php echo __("The QSO were successfully logged in the logbook!"); ?>";
		</script>
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="simpleFleInfoButton"><?php echo __("What is that?"); ?></button>
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
				<?php echo __("QSO Data"); ?>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-xs-12 col-lg-12 col-xl-6">
						<div class="mb-3">
							<label for="qsodate"><?php echo __("QSO Date"); ?></label>
							<input type="date" class="form-control" id="qsodate">
							<small class="form-text text-muted"><?php echo __("If you don't choose a date, today's date will be used."); ?></small>
						</div>
					</div>
					<div class="col-xs-12 col-lg-12 col-xl-6">
						<p><?php echo __("Current UTC Time"); ?></p>
						<h4 class="fw-bold" id="utc-time"></h4>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 col-lg-6">
						<div class="mb-3">
							<label for="stationProfile">
								<?php echo __("Station Call/Location"); ?>
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
							<small class="form-text text-muted"><?php echo __("If you did operate from a new location, first create a new <a href=". site_url('station') . ">Station Location</a>"); ?></small>
						</div>
					</div>
					<div class="col-xs-12 col-lg-6">
						<div class="mb-3">
							<label for="operator"><?php echo __("Operator"); ?> <span class="text-muted input-example"><?php echo __("e.g. OK2CQR"); ?></span></label>
							<input type="text" class="form-control text-uppercase" id="operator" value="<?php echo $this->session->userdata('operator_callsign'); ?>">
							<div class="alert alert-danger" role="alert" id="warningOperatorField" style="display: none"> </div>
						</div>
					</div>
				</div>
			</div>

			<!-- END BASIC QSO DATA -->
			<div class="card-body">
				<div class="row">
					<div class="col">
						<p><?php echo __("Enter the Data"); ?></p>
						<textarea name="qso" class="form-control qso-area" cols="auto" rows="11" id="sfle_textarea" style="font-family: 'Courier New', sans-serif;"></textarea>
					</div>
				</div>
			</div>
		</div>
		<div class="card col-xs-12 col-md-8 simplefle" style="border: none">
			<div class="card-header">
				<?php echo __("QSO List"); ?>
			</div>
			<div class="card-body">
				<div class="qsoList">
					<table class="table table-striped table-hover sfletable" id="qsoTable">
						<thead>
							<tr>
								<th><?php echo __("Date"); ?></th>
								<th><?php echo __("Time"); ?></th>
								<th><?php echo __("Callsign"); ?></th>
								<th><?php echo __("Band"); ?></th>
								<th><?php echo __("Mode"); ?></th>
								<th><?php echo __("RST (S)"); ?></th>
								<th><?php echo __("RST (R)"); ?></th>
								<th><?php echo __("Gridsquare"); ?></th>
								<th><?php echo __("Refs"); ?>*</th>
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
						<?php echo __("The Refs can be either <u>S</u>OTA, <u>I</u>OTA, <u>P</u>OTA or <u>W</u>WFF"); ?>
					</div>
				</div>
			</div>
			<div class="row mt-2">
				<div class="btn-group" role="group" aria-label="sfle button group">

					<button class="btn btn-primary js-reload-qso"><?php echo __("Reload QSO List"); ?></button>
					<button class="btn btn-warning js-save-to-log"><?php echo __("Save in Wavelog"); ?></button>
					<button class="btn btn-danger js-empty-qso"><?php echo __("Clear Logging Session"); ?></button>
					<button class="btn btn-success" id="js-syntax"><?php echo __("Syntax Help"); ?></button>
					<button class="btn btn-secondary" id="js-options"><?php echo __("Options"); ?></button>

				</div>
			</div>
		</div>
	</div>
</div>