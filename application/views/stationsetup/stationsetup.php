<div class="container">

    <br>
    <?php if($this->session->flashdata('message')) { ?>
    <!-- Display Message -->
    <div class="alert-message error">
        <p><?php echo $this->session->flashdata('message'); ?></p>
    </div>
    <?php } ?>

    <h2><?php echo $page_title; ?></h2>
    <div class="row">

        <div>

            <div class="card">
                <div class="card-header">
				<?php echo lang('station_logbooks')?>
					</div>
					<div class="card-body">
                    <p class="card-text"><?php echo lang('station_logbooks_description_text')?></p>
					<a class="btn btn-primary btn-sm" href="javascript:createStationLogbook();"><i class="fas fa-plus"></i> <?php echo lang('station_logbooks_create')?></a>



                    <div class="table-responsive">
                        <table id="station_logbooks_table" class="table-sm table table-hover table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo lang('general_word_name')?></th>
                                    <th scope="col"><?php echo lang('station_logbooks_status')?></th>
                                    <th scope="col">Linked locations</th>
                                    <th scope="col"><?php echo lang('admin_delete')?></th>
                                    <th scope="col">Visitor site</th>
                                    <th scope="col"><?php echo lang('station_logbooks_public_search')?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_logbooks->result() as $row) { ?>
                                <tr>
                                    <td><?php echo $row->logbook_name;?> <i id="<?php echo $row->logbook_id ?>" class="editContainerName fas fa-edit" role="button"></i></td>
                                    <td>
                                        <?php if($this->session->userdata('active_station_logbook') != $row->logbook_id) { ?>
                                        <button id="<?php echo $row->logbook_id; ?>" class="setActiveLogbook btn btn-outline-primary btn-sm"><?php echo lang('station_logbooks_set_active')?></button>
                                        <?php } else {
											echo "<span class='badge bg-success'>" . lang('station_logbooks_active_logbook') . "</span>";
											}?>
                                    </td>
                                    <td>
									<button class="btn btn-outline-primary btn-sm editLinkedLocations" id="<?php echo $row->logbook_id; ?>);"><i class="fas fa-edit"></i></button>
                                    </td>
                                    <td>
                                        <?php if($this->session->userdata('active_station_logbook') != $row->logbook_id) { ?>
                                        <button id="<?php echo $row->logbook_id; ?>" class="deleteLogbook btn btn-outline-danger btn-sm"
                                            cnftext="'<?php echo lang('station_logbooks_confirm_delete') . $row->logbook_name; ?>'"><i
                                                class="fas fa-trash-alt"></i></a>
                                        <?php } ?>
                                    </td>
                                    <td>
										<button class="btn btn-outline-primary btn-sm editVisitorLink" id="<?php echo $row->logbook_id; ?>"><i class="fas fa-edit"></i></button>
                                        <?php if($row->public_slug != '') { ?>
                                        <a target="_blank"
                                            href="<?php echo site_url('visitor')."/".$row->public_slug; ?>"
                                            class="btn btn-outline-primary btn-sm"><i class="fas fa-globe"
                                                title="<?php echo lang('station_logbooks_view_public') . $row->logbook_name;?>"></i>
                                        </a>
										<button id="<?php echo $row->logbook_id; ?>" class="deletePublicSlug btn btn-outline-danger btn-sm" cnftext="Are you sure you want to delete the public slug?"><i class="fas fa-trash-alt"></i></button>
										<button id="<?php echo $row->logbook_id; ?>" class="editExportmapOptions btn btn-outline-primary btn-sm"><i class="fas fa-globe-europe"></i></button>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($row->public_search == 1) {
											echo "<span class='badge bg-success'>" . lang('general_word_enabled') . "</span>";?>
											<div class="form-check" style="margin-top: -1.5em"><input id="<?php echo $row->logbook_id; ?>" class="form-check-input publicSearchCheckbox" type="checkbox" checked /></div>
										<?php } else {
											echo "<span class='badge bg-dark'>" . lang('general_word_disabled') . "</span>"; ?>
											<div class="form-check" style="margin-top: -1.5em"><input id="<?php echo $row->logbook_id; ?>" class="form-check-input publicSearchCheckbox" type="checkbox" /></div>
										<?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
							</table>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Station Locations
                </div>
                <div class="card-body">
				<p class="card-text"><?php echo lang('station_location_header_ln1'); ?></p>
				<p class="card-text"><?php echo lang('station_location_header_ln2'); ?></p>
				<p class="card-text"><?php echo lang('station_location_header_ln3'); ?></p>

						<p><a href="<?php echo site_url('station/create'); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?php echo lang('station_location_create'); ?></a></p>

<?php if($current_active == 0) { ?>
<div class="alert alert-danger" role="alert">
<?php echo lang('station_location_warning'); ?>
</div>
<?php } ?>

<?php if (($is_there_qsos_with_no_station_id >= 1) && ($is_admin)) { ?>
	<div class="alert alert-danger" role="alert">
		  <span class="badge badge-pill badge-warning"><?php echo lang('general_word_warning'); ?></span> <?php echo lang('station_location_warning_reassign'); ?>
		</br>
		<?php echo lang('station_location_reassign_at'); ?> <a href="<?php echo site_url('maintenance/'); ?>" class="btn btn-warning"><i class="fas fa-sync"></i><?php echo lang('account_word_admin') . "/" . lang('general_word_maintenance'); ?></a>
	</div>
<?php } ?>

<div class="table-responsive">
<table id="station_locations_table" class="table-sm table table-hover table-striped table-condensed">
	<thead>
		<tr>
			<th scope="col"><?php echo lang('station_location_id'); ?></th>
			<th scope="col"><?php echo lang('station_location_name'); ?></th>
			<th scope="col"><?php echo lang('station_location_callsign'); ?></th>
			<th scope="col"><?php echo lang('general_word_country'); ?></th>
			<th scope="col"><?php echo lang('gen_hamradio_gridsquare'); ?></th>
			<th></th>
			<th scope="col"><?php echo lang('admin_edit'); ?></th>
			<th scope="col"><?php echo lang('admin_copy'); ?></th>
			<?php
					$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name'=>'locations_quickswitch'))->row()->option_value ?? 'false');
					if ($quickswitch_enabled == 'true') {
					?>
						<th scope="col">Favorite</th>
					<?php } ?>
			<th scope="col"><?php echo lang('station_location_emptylog'); ?></th>
			<th scope="col"><?php echo lang('admin_delete'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($stations->result() as $row) { ?>
		<tr>
			<td>
				<?php echo $row->station_id;?><br>
			</td>
			<td>
				<?php echo $row->station_profile_name;?><br>
			</td>
			<td><?php echo $row->station_callsign;?></td>
			<td><?php echo $row->station_country == '' ? '- NONE -' : $row->station_country; if ($row->dxcc_end != NULL) { echo ' <span class="badge badge-danger">'.lang('gen_hamradio_deleted_dxcc').'</span>'; } ?></td>
			<td><?php echo $row->station_gridsquare;?></td>
			<td>
				<?php if($row->station_active != 1) { ?>
					<a href="<?php echo site_url('station/set_active/').$current_active."/".$row->station_id; ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('<?php echo lang('station_location_confirm_active'); ?> <?php echo $row->station_profile_name; ?>');"><?php echo lang('station_location_set_active'); ?></a>
				<?php } else { ?>
					<span class="badge bg-success"><?php echo lang('station_location_active'); ?></span>
				<?php } ?>

				<br>
				<span class="badge bg-info">ID: <?php echo $row->station_id;?></span>
				<span class="badge bg-light"><?php echo $row->qso_total;?> <?php echo lang('gen_hamradio_qso'); ?></span>
			</td>
			<td>
				<a href="<?php echo site_url('station/edit')."/".$row->station_id; ?>" title=<?php echo lang('admin_edit'); ?> class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
			</td>
				<td>
				<a href="<?php echo site_url('station/copy')."/".$row->station_id; ?>" title=<?php echo lang('admin_copy'); ?> class="btn btn-outline-primary btn-sm"><i class="fas fa-copy"></i></a>
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
			<td>
				<a href="<?php echo site_url('station/deletelog')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?php echo lang('station_location_emptylog'); ?> onclick="return confirm('<?php echo lang('station_location_confirm_del_qso'); ?>');"><i class="fas fa-trash-alt"></i></a></td>
			</td>
			<td>
				<?php if($row->station_active != 1) { ?>
					<a href="<?php echo site_url('station/delete')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?php echo lang('admin_delete'); ?> onclick="return confirm('<?php echo lang('station_location_confirm_del_stationlocation'); ?> <?php echo $row->station_profile_name; ?> <?php echo lang('station_location_confirm_del_stationlocation_qso'); ?>');"><i class="fas fa-trash-alt"></i></a>
				<?php } ?>
			</td>
		</tr>

		<?php } ?>
	</tbody>
				</table>
</div>

                </div>
            </div>
        </div>
    </div>
