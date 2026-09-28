<div class="container srr" id="srr_export">
<br>
	<h2><?php echo $page_title; ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<?php if ($srr_key != '') { ?>
			<a style="margin-left: 1em;" class="btn btn-outline-danger btn-sm float-end" href="<?php echo site_url('srr/delete_key'); ?>" role="button"><i class="far fa-trash-alt"></i> <?= __("Delete Key"); ?></a>
			<?php } ?>
			<a class="btn btn-outline-success btn-sm float-end" href="https://award.srr.ru/panel/" target="_blank" rel="noopener" role="button"><i class="fas fa-cloud-upload-alt"></i> <?= __("Request award.srr Key"); ?></a><i class="fab fa-expeditedssl"></i> <?= __("award.srr Key"); ?>
		</div>

		<div class="key-list">
			<?php if(isset($error)) { ?>
				<div class="alert alert-danger" role="alert">
				<?php echo $error; ?>
				</div>
			<?php } ?>

			<?php $this->load->view('layout/messages'); ?>

			<?php if (($srr_me ?? false) !== false && $srr_key != '') { ?>

			<div class="table-responsive">
				<table class="table table-hover">
					<thead class="thead-light">
						<tr>
							<th scope="col"><?= __("Callsign"); ?></th>
							<th scope="col"><?= __("Type"); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach (($srr_me->callsigns ?? array()) as $srr_call) { ?>
							<tr>
								<td><span class="callsign"><?php echo $srr_call->callsign; ?></span></td>
								<td><?= __("Own callsign"); ?></td>
							</tr>
						<?php } ?>
						<?php foreach (($srr_me->delegated_callsigns ?? array()) as $srr_call) { ?>
							<tr>
								<td><span class="callsign"><?php echo $srr_call->callsign ?? ''; ?></span></td>
								<td><?= __("Delegated callsign"); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<?php } else { ?>
			<div class="card-body">
				<div class="alert alert-info" role="alert">
					<?= __("You need to request an award.srr key to use this function. Copy the key from your award.srr panel and paste it here."); ?>
				</div>
				<form class="row g-2" method="post" action="<?php echo site_url('srr/store_key'); ?>">
					<div class="col-auto">
						<input type="text" class="form-control" name="srr_key" id="srr_key" placeholder="<?= __("award.srr API Key"); ?>" required>
					</div>
					<div class="col-auto">
						<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= __("Save"); ?></button>
					</div>
				</form>
			</div>
			<?php } ?>

		</div>
	</div>
	<!-- Card Ends -->

	<?php if (($srr_me ?? false) !== false && $srr_key != '') { ?>
	<br>

	<div class="card">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs pull-right" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="export-tab" data-bs-toggle="tab" href="#export" role="tab" aria-controls="export" aria-selected="true"><?= __("Upload Logbook"); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="import-tab" data-bs-toggle="tab" href="#import" role="tab" aria-controls="import" aria-selected="false"><?= __("Download QSLs"); ?></a>
				</li>
			</ul>
		</div>

		<div class="card-body">
			<div class="tab-content">
				<div class="tab-pane active" id="export" role="tabpanel" aria-labelledby="export-tab">
			<?php if (($next_run ?? '') != '') { echo "<p>".__("The next automatic sync with award.srr will happen at: ").$next_run." UTC</p>"; } ?>
			<p><?= __("Here you can see all QSOs which have not been previously uploaded to award.srr."); ?></p>
			<p><?= __("The RDA district of your station (e.g. SP-19) has to be entered in the field 'Station County' of the station location. It is stored in every QSO as MY_CNTY."); ?></p>
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
					echo '<td><span class="callsign">' . $station->station_callsign . '</span></td>';
					echo '<td id ="modcount'.$station->station_id.'">' . $station->modcount . '</td>';
					echo '<td id ="notcount'.$station->station_id.'">' . $station->notcount . '</td>';
					echo '<td id ="totcount'.$station->station_id.'">' . $station->totcount . '</td>';
					echo '<td><button id="srrUpload" type="button" name="srrUpload" class="btn btn-primary btn-sm ld-ext-right ld-ext-right-'.$station->station_id.'" onclick="ExportSrr('. $station->station_id .')"><i class="fas fa-cloud-upload-alt"></i> ' . __("Upload") . '<div class="ld ld-ring ld-spin"></div></button></td>';
					echo '</tr>';
				}
				echo '</tfoot></table>';

		}
		else {
		echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
		}
		?>

				</div>
				<div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="import-tab">

					<form class="form" action="<?php echo site_url('srr/download'); ?>" method="post" enctype="multipart/form-data">
						<p><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("If no startdate is given then the QSLs after last confirmation will be downloaded/updated!"); ?></p>
						<div class="row">
							<div class="col-md-2">
								<label for="from"><?= __("From date") . ": " ?></label>
								<input name="from" id="from" type="date" class="importdate form-control w-auto">
							</div>
						</div>
						<br>
						<button type="button" class="btn btn-sm btn-primary ld-ext-right ld-ext-right-import" onclick="importSrr();"><i class="fas fa-cloud-download-alt"></i> <?= __("Download QSLs from award.srr"); ?><div class="ld ld-ring ld-spin"></div></button>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

</div>

<script>
	var lang_srr_propmode_title = "<?= __("Propagation mode missing"); ?>";
	var lang_srr_propmode_message = "<?= __("QSOs on 2m and above must have a propagation mode (PROP_MODE). The following QSOs have none:"); ?>";
	var lang_srr_propmode_los = "<?= __("Mark as LOS. Line of Sight (includes transmission through obstacles such as walls)"); ?>";
	var lang_srr_propmode_cancel = "<?= __("Cancel"); ?>";
	var lang_srr_rda_title = "<?= __("RDA district missing"); ?>";
	var lang_srr_rda_warning = "<?= __("WARNING: If the QSOs were made from the territory of the Russian Federation, the RDA district must be specified for them to count towards awards."); ?>";
	var lang_srr_rda_message = "<?= __("The following QSOs have no valid RDA district (e.g. SP-19) in the field 'My County' (MY_CNTY):"); ?>";
	var lang_srr_rda = "<?= __("RDA"); ?>";
	var lang_srr_rda_upload = "<?= __("Upload without RDA"); ?>";
	var lang_srr_callsign = "<?= __("Callsign"); ?>";
	var lang_srr_date = "<?= __("Date"); ?>";
	var lang_srr_band = "<?= __("Band"); ?>";
	var lang_srr_mode = "<?= __("Mode"); ?>";
</script>
