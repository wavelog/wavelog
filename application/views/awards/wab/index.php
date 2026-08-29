<div class="container">
<script>
	let tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
	let attributionInfo='<?php echo $this->optionslib->get_option('option_map_tile_server_copyright'); ?>';
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>

    <!-- Award Info Box -->
    <br>
    <div id="awardInfoButton">
        <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAB - Worked All Britain Award"); ?>";
            var lang_award_info_ln2 = "<?= __("WAB, Worked All Britain squares in Amateur Radio, encourages licensed ham radio operators to work all the squares in Great Britain."); ?>";
            var lang_award_info_ln3 = "<?= __("May be claimed for having contacted an amateur station located in the required amount of squares, described on the page linked below."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://wab.intermip.net/default.php' target='_blank'>https://wab.intermip.net/default.php</a>"); ?>";
            var lang_award_info_ln5 = "<?= __("Fields taken for this Award: SIG (Has to be 'WAB') and SIG_INFO (should contain valid WAB-Grid)"); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
    </div>

<form class="form">
	<div class="mb-1 text-center">
		<div class="dropdown" data-bs-auto-close="outside">
			<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
			<button id="plot" type="button" name="plot" class="btn btn-sm btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plotmap()"><?= __("Map"); ?><div class="ld ld-ring ld-spin"></div></button>
			<button id="list" type="button" name="list" class="btn btn-sm btn-primary me-1 ld-ext-right ld-ext-right-list" onclick="showlist()"><?= __("List"); ?><div class="ld ld-ring ld-spin"></div></button>

			<!-- Dropdown Menu with Filter Content -->
			<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown">
				<div class="card-body filterbody">
					<div class="d-flex justify-content-between align-items-center mb-1">
						<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
					</div>

					<div class="filter-section">
						<div class="mb-3 row">
							<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-tower-broadcast"></i><?= __("Band & Mode"); ?></div>
							<div class="col-sm-6 mb-2">
								<label class="form-label mb-1" for="band"><?= __("Band"); ?></label>
								<select class="form-select form-select-sm" id="band">
									<option value="All"><?= __("All")?></option>
									<?php foreach($bands as $band) {
										echo '<option value="'.$band.'"';
										if ($user_default_band == $band) {
											echo ' selected="selected"';
										}
										echo '>'.$band.'</option>'."\n";
									} ?>
								</select>
							</div>
							<div class="col-sm-6">
								<label class="form-label mb-1" for="mode"><?= __("Mode"); ?></label>
								<select class="form-select form-select-sm" id="mode">
									<option value="All"><?= __("All")?></option>
									<?php
									foreach($modes as $mode){
										if ($mode->submode ?? '' == '') {
											echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>'."\n";
										}
									}
									?>
								</select>
							</div>
						</div>
						<div class="mb-3 row">
							<div class="col-sm-6 mb-2">
								<?php if (count($sats_available) != 0) { ?>
									<label class="form-label mb-1" id="satslabel" for="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Satellite"); ?></label>
									<select class="form-select form-select-sm" id="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
										<option value="All"><?= __("All")?></option>
										<?php foreach($sats_available as $sat) {
											echo '<option value="' . html_escape($sat) . '"' . '>' . html_escape($sat) . '</option>'."\n";
										} ?>
									</select>
								<?php } else { ?>
									<input id="sats" type="hidden" value="All"></input>
								<?php } ?>
							</div>
							<div class="col-sm-6">
								<label class="form-label mb-1" id="orbitslabel" for="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Orbit"); ?></label>
								<select class="form-select form-select-sm" id="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
									<option value="All"><?= __("All")?></option>
									<?php
									foreach($orbits as $orbit){
										echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>'."\n";
									}
									?>
								</select>
							</div>
						</div>
					</div>

					<div class="filter-section">
						<div class="mb-3 row">
							<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-envelope-open-text"></i><?= __("Confirmation"); ?></div>
							<div class="col-md-10">
								<div class="form-check-inline">
								<?php echo '<input class="btn-check" value="1" type="checkbox" name="qsl" id="qsl"';
									if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
										echo ' checked' ;
									}
									echo '>'; ?>
									<label class="btn btn-outline-primary btn-sm" for="qsl"><?= __("QSL"); ?></label>
								</div>
								<div class="form-check-inline">
								<?php echo '<input class="btn-check" value="1" type="checkbox" name="lotw" id="lotw"';
									if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
										echo ' checked' ;
									}
									echo '>'; ?>
									<label class="btn btn-outline-primary btn-sm" for="lotw"><?= __("LoTW"); ?></label>
								</div>
								<div class="form-check-inline">
								<?php echo '<input class="btn-check" value="1" type="checkbox" name="eqsl" id="eqsl"';
									if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
										echo ' checked' ;
									}
									echo '>'; ?>
									<label class="btn btn-outline-primary btn-sm" for="eqsl"><?= __("eQSL"); ?></label>
								</div>
								<div class="form-check-inline">
								<?php echo '<input class="btn-check" value="1" type="checkbox" name="qrz" id="qrz"';
									if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
										echo ' checked' ;
									}
									echo '>'; ?>
									<label class="btn btn-outline-primary btn-sm" for="qrz"><?= __("QRZ.com"); ?></label>
								</div>
								<div class="form-check-inline">
								<?php echo '<input class="btn-check" value="1" type="checkbox" name="clublog" id="clublog"';
									if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
										echo ' checked' ;
									}
									echo '>'; ?>
									<label class="btn btn-outline-primary btn-sm" for="clublog"><?= __("Clublog"); ?></label>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</form>
</div>
<div id="mapcontainer">
    <div id="wabmap" style="width: 100%; height: 85vh;"></div>
</div>
