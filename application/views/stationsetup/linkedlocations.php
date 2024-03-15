
<div class="row">
		<div class="col-md">
			<div class="card">
				<div class="card-header"><?php echo lang('station_location_plural'); ?></div>
				<div class="card-body">
					<form method="post" action="<?php echo site_url('logbooks/edit/'); ?><?php echo $station_logbook_details->logbook_id; ?>" name="create_profile">
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
						<label for="StationLocationsSelect"><?php echo lang('station_logbooks_select_avail_loc'); ?></label>
						<select name="SelectedStationLocation" class="form-select" id="StationLocationSelect" aria-describedby="StationLocationSelectHelp">
							<?php foreach ($station_locations_list->result() as $row) {
								if (!in_array($row->station_id, $linked_stations)) { ?>
								<option value="<?php echo $row->station_id;?>"><?php echo $row->station_profile_name;?> (<?php echo lang('gen_hamradio_callsign'); ?>: <?php echo $row->station_callsign;?> <?php echo lang('gen_hamradio_dxcc'); ?>: <?php echo $row->station_country; if ($row->dxcc_end != NULL) { echo ' ('.lang('gen_hamradio_deleted_dxcc').')'; } ?>)</option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>

					<input type="hidden" class="form-control" name="station_logbook_id" value="<?php echo $station_logbook_details->logbook_id; ?>" required>

					<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-link"></i> <?php echo lang('station_logbooks_link_loc'); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>

<table id="station_logbooks_linked_table" class="table table-hover table-sm table-striped">
	<thead class="thead-light">
		<tr>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('station_location_name'); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('station_location_callsign'); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('gen_hamradio_dxcc'); ?></th>
			<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('station_logbooks_unlink_station_location'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			if ($station_locations_linked) {
				foreach ($station_locations_linked->result() as $row) {
		?>
		<tr>
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_profile_name;?></td>
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_callsign;?></td>
			<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_country; if ($row->end != NULL) { echo ' <span class="badge text-bg-danger">'.lang('gen_hamradio_deleted_dxcc').'</span>'; } ?></td>
			<td style="text-align: center; vertical-align: middle;"><a href="<?php echo site_url('logbooks/delete_relationship/'); ?><?php echo $station_logbook_details->logbook_id; ?>/<?php echo $row->station_id;?>" class="btn btn-sm btn-danger"><i class="fas fa-unlink"></i></a></td>
		</tr>
		<?php
				}
			} else {
		?>
		<tr>
			<td style="text-align: center; vertical-align: middle;" colspan="2"><?php echo lang('station_logbooks_no_linked_loc'); ?></td>
			<td style="text-align: center; vertical-align: middle;"></td>
			<td style="text-align: center; vertical-align: middle;"></td>
			<td style="text-align: center; vertical-align: middle;"></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
