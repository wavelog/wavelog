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
				<?= __("Station Logbooks")?>
					</div>
					<div class="card-body">
                    <p class="card-text"><?= __("Station Logbooks allow you to group Station Locations, this allows you to see all the locations across one session from the logbook areas to the analytics. Great for when your operating in multiple locations but they are part of the same DXCC or VUCC Circle.")?></p>
					<a class="btn btn-primary btn-sm" href="javascript:createStationLogbook();"><i class="fas fa-plus"></i> <?= __("Create Station Logbook")?></a>



                    <div class="table-responsive">
                        <table id="station_logbooks_table" class="table-sm table table-hover table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th scope="col"><?= __("Name")?></th>
                                    <th scope="col"><?= __("Status")?></th>
                                    <th scope="col"><?= __("Linked locations"); ?></th>
                                    <th scope="col"><?= __("Delete")?></th>
                                    <th scope="col"><?= __("Visitor site"); ?></th>
                                    <th scope="col"><?= __("Public Search")?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_logbooks->result() as $row) { ?>
                                <tr>
                                    <td><?php echo $row->logbook_name;?> <i id="<?php echo $row->logbook_id ?>" class="editContainerName fas fa-edit" role="button"></i></td>
                                    <td>
                                        <?php if($this->session->userdata('active_station_logbook') != $row->logbook_id) { ?>
                                        <button id="<?php echo $row->logbook_id; ?>" class="setActiveLogbook btn btn-outline-primary btn-sm"><?= __("Set as Active Logbook")?></button>
                                        <?php } else {
											echo "<span class='badge bg-success'>" . __("Active Logbook") . "</span>";
											}?>
                                    </td>
                                    <td>
									<button class="btn btn-outline-primary btn-sm editLinkedLocations" id="<?php echo $row->logbook_id; ?>);"><i class="fas fa-edit"></i></button>
                                    </td>
                                    <td>
                                        <?php if($this->session->userdata('active_station_logbook') != $row->logbook_id) { ?>
                                        <button id="<?php echo $row->logbook_id; ?>" class="deleteLogbook btn btn-outline-danger btn-sm"
                                            cnftext="'<?= __("Are you sure you want to delete the following station logbook? You must re-link any locations linked here to another logbook.: ") . $row->logbook_name; ?>'"><i
                                                class="fas fa-trash-alt"></i></a>
                                        <?php } ?>
                                    </td>
                                    <td>
										<button class="btn btn-outline-primary btn-sm editVisitorLink" id="<?php echo $row->logbook_id; ?>"><i class="fas fa-edit"></i></button>
                                        <?php if($row->public_slug != '') { ?>
                                        <a target="_blank"
                                            href="<?php echo site_url('visitor')."/".$row->public_slug; ?>"
                                            class="btn btn-outline-primary btn-sm"><i class="fas fa-globe"
                                                title="<?= __("View Public Page for Logbook: ") . $row->logbook_name;?>"></i>
                                        </a>
										<button id="<?php echo $row->logbook_id; ?>" class="deletePublicSlug btn btn-outline-danger btn-sm" cnftext="Are you sure you want to delete the public slug?"><i class="fas fa-trash-alt"></i></button>
										<button id="<?php echo $row->logbook_id; ?>" class="editExportmapOptions btn btn-outline-primary btn-sm"><i class="fas fa-globe-europe"></i></button>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($row->public_search == 1) {
											echo "<span class='badge bg-success'>" . __("Enabled") . "</span>";?>
											<div class="form-check" style="margin-top: -1.5em"><input id="<?php echo $row->logbook_id; ?>" class="form-check-input publicSearchCheckbox" type="checkbox" checked /></div>
										<?php } else {
											echo "<span class='badge bg-dark'>" . __("Disabled") . "</span>"; ?>
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
                    <?= __("Station Locations"); ?>
                </div>
                <div class="card-body">
				<p class="card-text">
					<?= __("Station Locations define operating locations, such as your QTH, a friends QTH, or a portable station."); ?><br>
					<?= __("Similar to logbooks, a station profile keeps a set of QSOs together."); ?><br>
					<?= __("Only one station may be active at a time. In the table below this is shown with the -Active Station- badge."); ?><br>
					<?= __("The 'Linked' column shows if the station location is linked with the Active Logbook selected above."); ?>
				</p>

						<p><a href="<?php echo site_url('station/create'); ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?= __("Create a Station Location"); ?></a></p>

<?php if($current_active == 0) { ?>
<div class="alert alert-danger" role="alert">
<?= __("Attention: You need to set an active station location. Go to Callsign->Station Location to select one."); ?>
</div>
<?php } ?>

<?php if (($is_there_qsos_with_no_station_id >= 1) && ($is_admin)) { ?>
	<div class="alert alert-danger" role="alert">
		  <span class="badge badge-pill badge-warning"><?= __("Warning"); ?></span> <?= __("Due to recent changes within Wavelog you need to reassign QSOs to your station profiles."); ?>
		</br>
		<?= __("Please reassign them at "); ?> <a href="<?php echo site_url('maintenance/'); ?>" class="btn btn-warning"><i class="fas fa-sync"></i><?= __("Admin") . "/" . __("Maintenance"); ?></a>
	</div>
<?php } ?>

<div class="table-responsive">
<table id="station_locations_table" class="table-sm table table-hover table-striped table-condensed">
	<thead>
		<tr>
			<th scope="col"><?= __("ID"); ?></th>
			<th scope="col"><?= __("Profile Name"); ?></th>
			<th scope="col"><?= __("Station Callsign"); ?></th>
			<th scope="col"><?= __("Country"); ?></th>
			<th scope="col"><?= __("Gridsquare"); ?></th>
			<th></th>
			<th scope="col"><?= __("Linked"); ?></th>
			<th scope="col"><?= __("Edit"); ?></th>
			<th scope="col"><?= __("Copy"); ?></th>
			<?php
					$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name'=>'locations_quickswitch'))->row()->option_value ?? 'false');
					if ($quickswitch_enabled == 'true') {
					?>
						<th scope="col"><?= __("Favorite"); ?></th>
					<?php } ?>
			<th scope="col"><?= __("Empty Log"); ?></th>
			<th scope="col"><?= __("Delete"); ?></th>
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
			<td><?php echo $row->station_country == '' ? __("Please select one") : $row->station_country; if ($row->dxcc_end != NULL) { echo ' <span class="badge bg-danger">'.__("Deleted DXCC").'</span>'; } ?></td>
			<td><?php echo $row->station_gridsquare;?></td>
			<td>
				<?php if($row->station_active != 1) { ?>
					<a href="<?php echo site_url('station/set_active/').$current_active."/".$row->station_id; ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('<?= __("Are you sure you want to make the following station the active station: "); ?> <?php echo $row->station_profile_name; ?>');"><?= __("Set Active"); ?></a>
				<?php } else { ?>
					<span class="badge bg-success"><?= __("Active Station"); ?></span>
				<?php } ?>

				<br>
				<span class="badge bg-info">ID: <?php echo $row->station_id;?></span>
				<span class="badge bg-light"><?php echo $row->qso_total;?> <?= __("QSO"); ?></span>
			</td>
			<td></td>
			<td>
				<a href="<?php echo site_url('station/edit')."/".$row->station_id; ?>" title=<?= __("Edit"); ?> class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
			</td>
				<td>
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
							<a href="<?php echo site_url('station/edit_favorite')."/".$row->station_id; ?>" title="<?= __("mark/unmark as favorite"); ?>" <?php echo $favStarClasses; ?>></a>
						</td>
					<?php } ?>
			<td>
				<?php
				$cnfmsg = __("Are you sure you want to delete all QSOs within this station profile?")
				?>
				<a href="<?php echo site_url('station/deletelog')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?= __("Empty Log"); ?> onclick="return confirm('<?php echo $cnfmsg; ?>');"><i class="fas fa-trash-alt"></i></a></td>
			</td>
			<td>
				<?php if($row->station_active != 1) {
					$cnfmsg = sprintf(__("Are you sure you want delete station profile '%s'? This will delete all QSOs within this station profile."), $row->station_profile_name); ?>?>
					<a href="<?php echo site_url('station/delete')."/".$row->station_id; ?>" class="btn btn-danger btn-sm" title=<?= __("Delete"); ?> onclick="return confirm('<?= $cnfmsg ?>');"><i class="fas fa-trash-alt"></i></a>
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
