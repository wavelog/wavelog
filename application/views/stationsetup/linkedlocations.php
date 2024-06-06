<input type="hidden" name="logbook_id" value="<?php echo $station_logbook_details->logbook_id; ?>">

<?php
	$linked_stations = array();
	if ($station_locations_linked) {
		foreach ($station_locations_linked->result() as $row) {
			$linked_stations[] = $row->station_id;
		}
	}
?>

<div class="mb-3">
	<label for="StationLocationsSelect"><?= __("Select Available Station Locations"); ?></label>
	<select name="SelectedStationLocation" class="form-select" id="StationLocationSelect" aria-describedby="StationLocationSelectHelp">
		<?php foreach ($station_locations_list->result() as $row) {
			if (!in_array($row->station_id, $linked_stations)) { ?>
			<option value="<?php echo $row->station_id;?>"><?php echo $row->station_profile_name;?> (<?= __("Callsign"); ?>: <?php echo $row->station_callsign;?> <?= __("DXCC"); ?>: <?php echo $row->station_country; if ($row->dxcc_end != NULL) { echo ' ('.__("Deleted DXCC").')'; } ?>)</option>
			<?php } ?>
		<?php } ?>
	</select>
</div>

<input type="hidden" class="form-control" id="station_logbook_id" name="station_logbook_id" value="<?php echo $station_logbook_details->logbook_id; ?>" required>

<button class="btn btn-sm btn-primary linkLocationButton" onclick="linkLocations();"><i class="fas fa-link"></i> <?= __("Link Location"); ?></button>
<br /><br />

<table id="station_logbooks_linked_table" class="table table-hover table-sm table-striped">
	<thead class="thead-light">
		<tr>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Profile Name"); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Station Callsign"); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("DXCC"); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Unlink Station Location"); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			if ($station_locations_linked) {
				foreach ($station_locations_linked->result() as $row) {
		?>
		<tr id="locationid_<?php echo $row->station_id; ?>">
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_profile_name;?></td>
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_callsign;?></td>
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_country; if ($row->end != NULL) { echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; } ?></td>
			<td style="text-align: center; vertical-align: middle;"><button class="btn btn-sm btn-danger unlinkbutton" onclick="unLinkLocations('<?php echo $station_logbook_details->logbook_id; ?>','<?php echo $row->station_id;?>')"><i class="fas fa-unlink"></i></button>
		</tr>
		<?php
				}
			} ?>
	</tbody>
</table>
