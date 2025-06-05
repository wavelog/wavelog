
<div class="container adif">

    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
			<ul class="nav nav-tabs card-header-tabs pull-right" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="export-tab" data-bs-toggle="tab" href="#export" role="tab" aria-controls="import" aria-selected="true"><?= __("Upload Logbook"); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="mark-tab" data-bs-toggle="tab" href="#mark" role="tab" aria-controls="export" aria-selected="false"><?= __("Mark QSOs"); ?></a>
				</li>
			</ul>

        </div>

        <div class="card-body">
			<div class="tab-content">
				<div class="tab-pane active" id="export" role="tabpanel" aria-labelledby="export-tab">
            	<p><?= __("Here you can see and upload all QSOs which have not been previously uploaded to upstream Wavelog instance."); ?></p>


<?php
            if ($station_profile->result()) {
               $queuedqsos = 0;
               foreach ($station_profile->result() as $count) {      // Fills the table with the data
                   $queuedqsos += $count->notcount;
               }
               if ($queuedqsos > 0) {
				?>
				<p><?= __("You need to set an API key in your station profile. Only station profiles with an API key are displayed."); ?></p>
				<p><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("This might take a while as QSO uploads are processed sequentially."); ?></p>
				<?php
            echo '

            <table class="table table-bordered table-hover table-striped table-condensed text-center">
                <thead>
                <tr>
                    <td>' . __("Profile name") . '</td>
                    <td>' . __("Station callsign") . '</td>
                    <td>' . __("Total QSOs not uploaded") . '</td>
                    <td>' . __("Total QSOs uploaded") . '</td>
                    <td>' . __("Actions") . '</td>
                </thead>
                <tbody>';
                foreach ($station_profile->result() as $station) {      // Fills the table with the data
                   if ($station->notcount != null) {
                       echo '<tr>';
                           echo '<td>' . $station->station_profile_name . '</td>';
                           echo '<td>' . $station->station_callsign . '</td>';
                           echo '<td id ="notcount'.$station->station_id.'">' . $station->notcount . '</td>';
                           echo '<td id ="totcount'.$station->station_id.'">' . $station->totcount . '</td>';
                           echo '<td><button id="wavelogUpload" type="button" name="wavelogUpload" class="btn btn-primary btn-sm ld-ext-right ld-ext-right-'.$station->station_id.'" onclick="ExportWavelog('. $station->station_id .')"><i class="fas fa-cloud-upload-alt"></i> ' . __("Upload") . '<div class="ld ld-ring ld-spin"></div></button></td>';
                       echo '</tr>';
                   }
                }
                echo '</tfoot></table>';
               } else {
                  ?>
                  <div class="alert alert-success">
                  <?= __("There are currently no outstanding QSOs that need to be uploaded to the upstream Wavelog API."); ?><br />
                  </div>
                  <?php
               }

        }
        else {
			?>
			<div class="alert alert-danger">
				<?= __("None of your Logbooks are configured to export data to the upstream Wavelog API."); ?><br />
			</div>
			<?php
        }
        ?>

        </div>
				<div class="tab-pane fade" id="mark" role="tabpanel" aria-labelledby="home-tab">
					<p><?= __("Here you can mark as uploaded your QSOs which have not been previously uploaded to upstream Wavelog."); ?></p>
				<?php
				if ($station_profiles->result()!==[]){
				?>
				<form class="form" action="<?php echo site_url('wavelog/mark_wavelog'); ?>" method="post" enctype="multipart/form-data">
					<select name="station_profile" class="form-select mb-4 me-sm-4" style="width: 30%;">
						<option value="0"><?= __("Select Station Location"); ?></option>
						<?php foreach ($station_profiles->result() as $station) { ?>
							<option value="<?php echo $station->station_id; ?>">Callsign: <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)</option>
						<?php } ?>
					</select>
					<p><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("If a date range is not selected then all QSOs will be marked!"); ?></p>
					<div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="from"><?= __("From date") . ": " ?></label>
                        <input name="from" id="from" type="date" class="form-control w-auto">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="to"><?= __("To date") . ": " ?></label>
                        <input name="to" id="to" type="date" class="form-control w-auto">
                    </div>
                </div>
					<br>
					<button type="button" id="markWavelogAsExported" class="btn btn-sm btn-primary" value="Export"><?= __("Mark QSOs as exported to upstream Wavelog"); ?></button>
				</form>
				<?php
				}else{
				?>
					<div class="alert alert-danger">
                        <?= __("None of your Logbooks are configured to export data to upstream Wavelog API."); ?><br />
					</div>
				<?php
				}
				?>
	</div>
			</div>
		</div>
	</div>
</div>
