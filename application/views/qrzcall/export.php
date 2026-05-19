
<div class="container adif" id="qrzcall_export">

    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
			<ul class="nav nav-tabs card-header-tabs pull-right" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="export-tab" data-bs-toggle="tab" href="#export" role="tab" aria-controls="export" aria-selected="true"><?= __("Upload Logbook"); ?></a>
				</li>
			</ul>
        </div>

        <div class="card-body">
			<div class="tab-content">
				<div class="tab-pane active" id="export" role="tabpanel" aria-labelledby="export-tab">
            <?php if (($next_run_up ?? '') != '') { echo "<p>".__("The next automatic Upload to QRZCALL.EU will happen at: ").$next_run_up." UTC</p>"; } ?>
            <p><?= __("Here you can see all QSOs which have not been previously uploaded to a QRZCALL.EU logbook."); ?></p>
            <p><?= __("You need to set a QRZCALL.EU API token in your station profile. Only station profiles with an API token set are displayed."); ?></p>
            <p><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("This might take a while as QSO uploads are processed sequentially."); ?></p>

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
                    echo '<td><button id="qrzcallUpload" type="button" name="qrzcallUpload" class="btn btn-primary btn-sm ld-ext-right ld-ext-right-'.$station->station_id.'" onclick="ExportQrzcall('. $station->station_id .')"><i class="fas fa-cloud-upload-alt"></i> ' . __("Upload") . '<div class="ld ld-ring ld-spin"></div></button></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

        }
        else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        }
        ?>

        </div>
			</div>
		</div>
	</div>
</div>
