<div class="container">
	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right"  id="myTab" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="dxcc-tab" data-bs-toggle="tab" href="#dxcc" role="tab" aria-controls="update" aria-selected="true"><?= __("DXCC Lookup Data"); ?></a>
				</li>

				<li class="nav-item">
					<a class="nav-link" id="distance-tab" data-bs-toggle="tab" href="#distanceTab" role="tab" aria-controls="update" aria-selected="false"><?= __("Distance Data"); ?></a>
				</li>
			</ul>
		</div>
		<div class="card-body">
			<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade show active" id="dxcc" role="tabpanel" aria-labelledby="dxcc-tab">
					<p class="card-text"><?= __("Here you can update the DXCC lookup data that is used for displaying callsign information."); ?></p>
					<p class="card-text"><?= __("This data is provided by"); ?> <a href="https://clublog.org/"><?= __("Clublog"); ?></a>.</p>

					<?php if(!extension_loaded('xml')) { ?>
						<div class="alert alert-danger" role="alert">
						<?= __("You must install php-xml for this to work."); ?>
						</div>
					<?php } else { ?>
						<h5><?= __("Check for DXCC Data Updates"); ?></h5>
						<button type="submit" class="btn btn-primary ld-ext-right" id="btn_update_dxcc"><div class="ld ld-ring ld-spin"></div><?= __("Update DXCC Data"); ?></button>

						<div id="dxcc_update_status"><?= __("Status"); ?>:</br></div>

						<br/>
						<br/>
						<h5><?= __("Apply DXCC Data to Logbook"); ?></h5>
						<p class="card-text">
							<?= __("After updating, Wavelog can fill in missing callsign information in the logbook using the newly-obtained DXCC data.
							You can choose to check just the QSOs in the logbook that are missing DXCC metadata or to re-check the entire logbook
							and update existing metadata as well, in case it has changed."); ?>
						</p>
						<p><a class="btn btn-primary" hx-get="<?php echo site_url('update/check_missing_dxcc');?>"hx-target="#missing_dxcc_results" href="<?php echo site_url('update/check_missing_dxcc');?>"><?= __("Check QSOs missing DXCC data"); ?></a></p>
						<div id="missing_dxcc_results"></div>
						<p><a class="btn btn-primary" hx-get="<?php echo site_url('update/check_missing_dxcc/all');?>" hx-target="#missing_dxcc_results_all" href="<?php echo site_url('update/check_missing_dxcc/all');?>"><?= __("Re-check all QSOs in logbook"); ?></a></p>
						<div id="missing_dxcc_results_all"></div>

						<h5><?= __("Apply Continent Data to Logbook"); ?></h5>
						<p class="card-text">
							<?= __("This function can be used to update QSO continent information for all QSOs in Wavelog missing that information."); ?>
						</p>
						<p><a class="btn btn-primary" hx-get="<?php echo site_url('update/check_missing_continent');?>" hx-target="#continent_results" href="<?php echo site_url('update/check_missing_continent');?>"><?= __("Check QSOs missing continent data"); ?></a></p>
						<div id="continent_results"></div>
						<style>
							#dxcc_update_status{
							display: None;
							}
						</style>
					<?php } ?>
				</div>
				<div class="tab-pane fade" id="distanceTab" role="tabpanel" aria-labelledby="distance-tab">
					<p class="card-text"><?= __("Here you can update QSOs with missing distance information."); ?></p>
					<p><a class="btn btn-primary" hx-get="<?php echo site_url('update/update_distances');?>"  hx-target="#distance_results" href="<?php echo site_url('update/update_distances');?>"><?= __("Update distance data"); ?></a></p>
					<p class="card-text"><?= __("Use the following button to update the distance information for all your QSOs. Depending on the number of QSOs this might take some time to execute. Please be patient."); ?></p>
					<p><a class="btn btn-primary" hx-get="<?php echo site_url('update/update_distances/all');?>" hx-target="#distance_results" href="<?php echo site_url('update/update_distances/all');?>"><?= __("Re-check all QSOs in logbook"); ?></a></p>
					<div id="distance_results"></div>
				</div>
			</div>
		</div>
	</div>
</div>


