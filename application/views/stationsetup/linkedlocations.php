<div class="card">
		<div class="card-header">
			<?php echo lang('station_logbooks_linked_loc'); ?>
		</div>

	    <div class="table-responsive m-4">
			<table id="station_logbooks_linked_table" class="table table-hover">
				<thead class="thead-light">
					<tr>
						<th scope="col"><?php echo lang('station_location_name'); ?></th>
						<th scope="col"><?php echo lang('station_location_callsign'); ?></th>
						<th scope="col"><?php echo lang('gen_hamradio_dxcc'); ?></th>
						<th scope="col"><?php echo lang('station_logbooks_unlink_station_location'); ?></th>
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
						<td style="text-align: center; vertical-align: middle;"><a href="<?php echo site_url('logbooks/delete_relationship/'); ?><?php echo $station_logbook_details->logbook_id; ?>/<?php echo $row->station_id;?>" class="btn btn-danger"><i class="fas fa-unlink"></i></a></td>
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
		</div>

	</div>
