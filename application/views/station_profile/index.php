<div class="container">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<h2><?php echo $page_title; ?></h2>

<div class="card">
  <div class="card-body">
    <p class="card-text"><?= __("Station Locations define operating locations, such as your QTH, a friends QTH, or a portable station."); ?></p>
	<p class="card-text"><?= __("Similar to logbooks, a station profile keeps a set of QSOs together."); ?></p>
	<p class="card-text"><?= __("Only one station may be active at a time. In the table below this is shown with the -Active Station- badge."); ?></p>

	  <p><a href="<?php echo site_url('station/create'); ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __("Create a Station Location"); ?></a></p>

		<?php if ($stations->num_rows() > 0) { ?>

		<?php if($current_active == 0) { ?>
		<div class="alert alert-danger" role="alert">
		<?= __("Attention: You need to set an active station location. Go to Callsign->Station Location to select one."); ?>
		</div>
		<?php } ?>

		<?php if (($is_there_qsos_with_no_station_id >= 1) && ($is_admin)) { ?>
			<div class="alert alert-danger" role="alert">
		  		<span class="badge rounded-pill text-bg-warning"><?= __("Warning"); ?></span> <?= __("Due to recent changes within Wavelog you need to reassign QSOs to your station profiles."); ?>
				</br>
				<?= __("Please reassign them at "); ?> <a href="<?php echo site_url('debug'); ?>" class="btn btn-warning"><i class="fas fa-sync"></i> <?= __("Admin") . "/" . __("Maintenance"); ?></a>
			</div>
		<?php } ?>

		<div class="table-responsive">
		<table id="station_locations_table" class="table table-sm table-striped">
			<thead>
				<tr>
					<th scope="col"><?= __("Profile Name"); ?></th>
					<th scope="col"><?= __("Station Callsign"); ?></th>
					<th scope="col"><?= __("Country"); ?></th>
					<th scope="col"><?= __("Gridsquare"); ?></th>
					<th></th>
					<th scope="col"><?= __("Edit"); ?></th>
					<th scope="col"><?= __("Copy"); ?></th>
					<?php
					$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name'=>'locations_quickswitch'))->row()->option_value ?? 'false');
					if ($quickswitch_enabled == 'true') {
					?>
						<th scope="col">Favorite</th>
					<?php } ?>
					<th scope="col"><?= __("Empty Log"); ?></th>
                    <th scope="col"><?= __("Delete"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($stations->result() as $row) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;">
						<?php echo $row->station_profile_name;?><br>
					</td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_callsign;?></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_country == '' ? '- NONE -' : $row->station_country; if ($row->dxcc_end != NULL) { echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; } ?></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $row->station_gridsquare;?></td>
					<td style="text-align: center" data-order="<?php echo $row->station_id;?>">
						<?php if($row->station_active != 1) { ?>
							<a href="<?php echo site_url('station/set_active/').$current_active."/".$row->station_id; ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('<?= __("Are you sure you want to make the following station the active station: "); ?> <?php echo $row->station_profile_name; ?>');"><?= __("Set Active"); ?></a>
						<?php } else { ?>
							<span class="badge text-bg-success"><?= __("Active Station"); ?></span>
						<?php } ?>

						<br>
						<span class="badge text-bg-info">ID: <?php echo $row->station_id;?></span>
						<span class="badge text-bg-light"><?php echo $row->qso_total;?> <?= __("QSO"); ?></span>
					</td>
					<td style="text-align: center; vertical-align: middle;">
						<a href="<?php echo site_url('station/edit')."/".$row->station_id; ?>" title=<?= __("Edit"); ?> class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
					</td>
					<td style="text-align: center; vertical-align: middle;">
						<a href="<?php echo site_url('station/copy')."/".$row->station_id; ?>" title=<?= __("Copy"); ?> class="btn btn-outline-primary btn-sm"><i class="fas fa-copy"></i></a>
					</td>
					<?php
					if ($quickswitch_enabled == 'true') {
					?>
						<td style="text-align: center; vertical-align: middle;">
							<?php $locationFavorite = ($this->user_options_model->get_options('station_location', array('option_name'=>'is_favorite', 'option_key'=>$row->station_id))->row()->option_value ?? 'false');
							if ($locationFavorite == 'true') {
								$favStarClasses = 'class="fas fa-star" style="color: #ffc82b;"';
							} else {
								$favStarClasses = 'class="far fa-star" style="color: #a58118;"';
							} ?>
							<a href="<?php echo site_url('station/edit_favorite')."/".$row->station_id; ?>" title="mark/unmark as favorite" <?php echo $favStarClasses; ?>></a>
						</td>
					<?php } ?>
					<td style="text-align: center; vertical-align: middle;">
						<?php
						$cnfmsg = __("Are you sure you want to delete all QSOs within this station profile?");
						?>
                        <a href="<?php echo site_url('station/deletelog')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?= __("Empty Log"); ?> onclick="return confirm('<?php echo $cnfmsg; ?>');"><i class="fas fa-trash-alt"></i></a></td>
                    </td>
					<td style="text-align: center; vertical-align: middle;">
						<?php if($row->station_active != 1) { 
							$cnfmsg = sprintf(__("Are you sure you want delete station profile '%s'? This will delete all QSOs within this station profile."), $row->station_profile_name); ?>
							<a href="<?php echo site_url('station/delete')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?= __("Delete"); ?> onclick="return confirm('<?= $cnfmsg ?>')"><i class="fas fa-trash-alt"></i></a>
						<?php } ?>
					</td>
				</tr>

				<?php } ?>
			</tbody>
		</table>
		</div>
		<?php } ?>
  </div>
</div>


</div>
