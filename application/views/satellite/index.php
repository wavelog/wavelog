<script type="text/javascript">
	let lang_tle_saved = '<?= "TLE saved."; ?>';
	let lang_tle_validation_failed = '<?= "TLE Validation Failed:"; ?>';
	let lang_tle_edit_satellite_tle = '<?= "Edit satellite TLE"; ?>';
	let lang_tle_invalid_tle_format = '<?= "Invalid TLE format: Must have 2 or 3 lines."; ?>';
	let lang_tle_invalid_tle_line1 = '<?= "Invalid Line 1: Must start with 1 and be 69 characters long."; ?>';
	let lang_tle_invalid_tle_line2 = '<?= "Invalid Line 2: Must start with 2 and be 69 characters long."; ?>';
	let lang_tle_checksum_error_line1 = '<?= "Checksum error on Line 1."; ?>';
	let lang_tle_checksum_error_line2 = '<?= "Checksum error on Line 2."; ?>';
	let lang_tle_delete_warning = '<?= "Warning! Are you sure you want to delete TLE for this satellite?"; ?>';
	let lang_tle_deleted = '<?= "The TLE has been deleted!"; ?>';
	let lang_tle_could_not_delete = '<?= "The TLE could not be deleted. Please try again!"; ?>';
	let lang_tle_save_tle = '<?= "Save TLE"; ?>';
	let lang_tle_paste_tle = '<?= "Paste TLE here..."; ?>';
</script>
<div class="container">

<br>
<?php if ($this->session->flashdata('success')) { ?>
        <!-- Display Message -->
        <div class="alert alert-success">
            <p><?php echo $this->session->flashdata('success'); ?></p>
        </div>
    <?php } ?>

    <?php if ($this->session->flashdata('error')) { ?>
        <!-- Display Message -->
        <div class="alert alert-danger">
            <p><?php echo $this->session->flashdata('error'); ?></p>
        </div>
    <?php } ?>

	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<h2><?= __("Satellites"); ?></h2>

<div class="card">
  <div class="card-body">
  <button onclick="createSatelliteDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Add a satellite"); ?></button>
  <a class="btn btn-primary btn-sm" href="<?php echo site_url('/update/update_lotw_sats'); ?>" role="button"><i class="fas fa-sync-alt"></i> <?= __("Sync Satellites from LoTW"); ?></a>
  <a class="btn btn-primary btn-sm" href="<?php echo site_url('/update/update_tle/satellite'); ?>" role="button"><i class="fas fa-sync-alt"></i> <?= __("Update Satellite TLE"); ?></a>
    <div class="table-responsive">

    <table style="width:100%" class="sattable table table-sm table-striped">
			<thead>
				<tr>
					<th><?= __("LoTW Name"); ?></th>
					<th><?= __("Display Name"); ?></th>
					<th><?= __("Orbit"); ?></th>
					<th><?= __("SAT Mode"); ?></th>
					<th><?= __("LoTW"); ?></th>
					<th><?= __("TLE"); ?></th>
					<th><?= __("Edit"); ?></th>
					<th><?= __("Delete"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($satellites as $sat) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;" class="satellite_<?php echo $sat->id ?>"><?php echo htmlentities($sat->satname) ?></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $sat->displayname ? htmlentities($sat->displayname) : '' ?></td>
					<?php echo '<td style="text-align: center; vertical-align: middle;"><span class="badge ';
					switch (strtoupper($sat->orbit ?? '')) {
					case 'LEO':
						echo 'bg-primary';
						break;
					case 'MEO':
						echo 'bg-info';
						break;
					case 'GEO':
						echo 'bg-secondary';
						break;
					default:
						echo 'bg-warning';
						break;
					}
					echo '">'.($sat->orbit ?? __('unknown')).'</span></td>';
					?>
					<td style="text-align: center; vertical-align: middle;"><?php echo htmlentities($sat->modename ?? '') ?></td>
					<?php echo '<td style="text-align: center; vertical-align: middle;">';
					switch ($sat->lotw) {
					case 'Y':
						echo '<span class="badge bg-success">'.__("Yes").'</span>';
						break;
					case 'N':
						echo '<span class="badge bg-danger">'.__("No").'</span>';
						break;
					default:
						echo '<span class="badge bg-warning">'.__("Unknown").'</span>';
						break;
					}
					echo '</td>';
					?>
					<?php echo '<td style="text-align: center; vertical-align: middle;">';
					if ($sat->updated != null) {
						echo '<button class="btn btn-sm btn-success" onclick="editTle(' . $sat->id . ');" data-bs-toggle="tooltip" title="Last TLE updated was ' . date($custom_date_format . " H:i", strtotime($sat->updated)) . '">'.__("Yes").'</i></button>';
					} else {
						echo '<button class="btn btn-sm btn-danger" onclick="editTle(' . $sat->id . ');">'.__("No").'</button>';
					}

					echo '</td>';
					?>
					<td style="text-align: center; vertical-align: middle;"><button onclick="editSatelliteDialog(<?php echo $sat->id ?>)" class="btn btn-sm btn-success"><i class="fas fa-edit"></i></i></button></td>
					<td style="text-align: center; vertical-align: middle;"><button onclick="deleteSatellite('<?php echo $sat->id . '\',\'' . xss_clean(htmlentities(str_replace('\'',"\\'",str_replace('"','\"',str_replace('\\',' ',$sat->satname))))) ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button></td>
				</tr>

				<?php } ?>
			</tbody>
		<table>

	</div>
  <br/>
</div>
</div>
