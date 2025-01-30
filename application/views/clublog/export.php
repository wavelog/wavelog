
<div class="container adif" id="clublog_export">

    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
			<ul class="nav nav-tabs card-header-tabs pull-right" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="export-tab" data-bs-toggle="tab" href="#export" role="tab" aria-controls="import" aria-selected="true"><?= __("Upload Logbook"); ?></a>
				</li>
				<?php if (!($this->config->item('disable_manual_clublog'))) { ?>
				<li class="nav-item">
					<a class="nav-link" id="mark-tab" data-bs-toggle="tab" href="#import" role="tab" aria-controls="import" aria-selected="false"><?= __("Download QSOs"); ?></a>
				</li>
				<?php } ?>
			</ul>

        </div>

        <div class="card-body">
			<div class="tab-content">
				<div class="tab-pane active" id="export" role="tabpanel" aria-labelledby="export-tab">
            <?php if (($next_run_up ?? '') != '') { echo "<p>".__("The next automatic Upload to Clublog will happen at: ").$next_run_up."</p>"; } ?>
            <p><?= __("Here you can see all QSOs which have not been previously uploaded to a Clublog logbook."); ?></p>
            <p><?= __("You need to set a username and password in your user account. You will also need to enable upload for each station profile ."); ?></p>
<?php
            if ($station_profile->result()) {
            echo '

            <table class="table table-bordered table-hover table-striped table-condensed text-center">
                <thead>
                <tr>
                    <td>' . __("Profile name") . '</td>
                    <td>' . __("Station callsign") . '</td>
                    <td>' . __("Edited QSOs not uploaded") . '</td>
                    <td>' . __("Total QSOs not uploaded") . '</td>
                    <td>' . __("Total QSOs uploaded") . '</td>
                    <td>' . __("Actions") . '</td>
                </thead>
                <tbody>';
                foreach ($station_profile->result() as $station) {      // Fills the table with the data
                echo '<tr>';
                    echo '<td>' . $station->station_profile_name . '</td>';
                    echo '<td>' . $station->station_callsign . '</td>';
                    echo '<td id ="modcount'.$station->station_id.'">' . $station->modcount . '</td>';
                    echo '<td id ="notcount'.$station->station_id.'">' . $station->notcount . '</td>';
                    echo '<td id ="totcount'.$station->station_id.'">' . $station->totcount . '</td>';
			if (!($this->config->item('disable_manual_clublog'))) {
				echo '<td><button id="clublogUpload" type="button" name="clublogUpload" class="btn btn-primary btn-sm ld-ext-right ld-ext-right-'.$station->station_id.'" onclick="ExportClublog('. $station->station_id .')"><i class="fas fa-cloud-upload-alt"></i> ' . __("Upload") . '<div class="ld ld-ring ld-spin"></div></button></td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
                    echo '</tr>';
                }
                echo '</tfoot></table>';

        }
        else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        }
        ?>

        </div>
		<?php if (!($this->config->item('disable_manual_clublog'))) { ?>
			<div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="home-tab">

				<form class="form" action="<?php echo site_url('clublog/importlog'); ?>" method="post" enctype="multipart/form-data">
					<?php if (($next_run_down ?? '') != '') { echo "<p>".__("The next automatic Download from Clublog-QSLs will happen at: ").$next_run_down."</p>"; } ?>
					<p><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("If no startdate is given then all QSOs after last confirmation will be downloaded/updated!"); ?></p>
					<div class="row">
						<div class="col-md-2">
							<label for="from"><?= __("From date") . ": " ?></label>
							<input name="from" id="from" type="date" class="importdate form-control w-auto">
						</div>
					</div>
					<br>
					<button type="button" class="btn btn-sm btn-primary ld-ext-right ld-ext-right-import" onclick="importlog();"><i class="fas fa-cloud-download-alt"></i> <?= __("Download from Clublog"); ?><div class="ld ld-ring ld-spin"></div></button>
				</form>
			</div>
		<?php } ?>
			</div>
		</div>
	</div>
</div>
