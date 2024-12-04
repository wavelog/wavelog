<div class="container" id="edit_satellite_dialog">
	<form>

	<input type="hidden" id="satelliteid" name="id" value="<?php echo $satellite->id; ?>">
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="displayNameInput"><?= __("Satellite Display Name "); ?></label>
				<input type="text" class="form-control" name="displayNameInput" id="displayNameInput" aria-describedby="displayNameInputHelp" value="<?php if(set_value('band') != "") { echo set_value('band'); } else { echo $satellite->displayname; } ?>" required>
				<small id="displayNameInputHelp" class="form-text text-muted"><?= __("Display / cleartext name of the satellite"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="nameInput"><?= __("LoTW Name"); ?></label>
				<input type="text" class="form-control" name="nameInput" id="nameInput" aria-describedby="nameInputHelp" value="<?php if(set_value('band') != "") { echo set_value('band'); } else { echo $satellite->name; } ?>">
				<small id="nameInputHelp" class="form-text text-muted"><?= __("Satellite name as accepted by LoTW. Not necessarily the same as the display name. Can be set/changed later when added to LoTW."); ?></small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="orbit"><?= __("Orbit"); ?></label>
				<select id="orbit" class="form-select" name="orbit">
					<option value="LEO" <?php echo $satellite->orbit == "LEO" ? "selected=\"selected\"" : ''; ?>>LEO</option>
					<option value="MEO" <?php echo $satellite->orbit == "MEO" ? "selected=\"selected\"" : ''; ?>>MEO</option>
					<option value="GEO" <?php echo $satellite->orbit == "GEO" ? "selected=\"selected\"" : ''; ?>>GEO</option>
				</select>
				<small id="orbitHelp" class="form-text text-muted"><?= __("Enter which orbit the satellite has (LEO, MEO, GEO)"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="lotwAccepted"><?= __("Accepted by LoTW"); ?></label>
				<select id="lotwAccepted" class="form-select" name="lotwAccepted">
					<?php if ($satellite->lotw == 'Y') { ?>
						<option value="Y" selected="selected"><?= __("Yes"); ?></option>
						<option value="N"><?= __("No"); ?></option>
					<?php } else { ?>
						<option value="Y"><?= __("Yes"); ?></option>
						<option value="N" selected="selected"><?= __("No"); ?></option>
					<?php } ?>
				</select>
				<small id="displayNameInputHelp" class="form-text text-muted"><?= __("Set to yes only if satellite is accepted my LoTW"); ?></small>
			</div>
		</div>

		<button type="button" onclick="saveUpdatedSatellite(this.form);" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i> <?= __("Save satellite"); ?></button>

		</form>
<br />
<div class="table-responsive">

<table style="width:100%" class="satmodetable table table-sm table-striped">
		<thead>
			<tr>
				<th style="text-align: center; vertical-align: middle;"><?= __("Name"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Uplink mode"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Uplink frequency"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Downlink mode"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Downlink frequency"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Edit"); ?></th>
				<th style="text-align: center; vertical-align: middle;"><?= __("Delete"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($satmodes as $mode) { ?>
			<tr class="satmode_<?php echo $mode->id ?>">
				<td id="modename_<?php echo $mode->id ?>" class="row_data" style="text-align: center; vertical-align: middle;" ><?php echo htmlentities($mode->name) ?></td>
				<td id="uplink_mode_<?php echo $mode->id ?>" class="row_data" style="text-align: center; vertical-align: middle;"><?php echo $mode->uplink_mode ?></td>
				<td id="uplink_freq_<?php echo $mode->id ?>" class="row_data" style="text-align: center; vertical-align: middle;"><?php echo $mode->uplink_freq ?></td>
				<td id="downlink_mode_<?php echo $mode->id ?>" class="row_data" style="text-align: center; vertical-align: middle;"><?php echo $mode->downlink_mode ?></td>
				<td id="downlink_freq_<?php echo $mode->id ?>" class="row_data" style="text-align: center; vertical-align: middle;"><?php echo $mode->downlink_freq ?></td>
				<td id="editButton" style="text-align: center; vertical-align: middle;"><button id="<?php echo $mode->id ?>" class="btn btn-sm btn-success editSatmode"><i class="fas fa-edit"></i></button></td>
				<td id="deleteButton" style="text-align: center; vertical-align: middle;"><button id="<?php echo $mode->id.'" infotext="'.htmlentities($mode->name) ?>" class="deleteSatmode btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button></td>
			</tr>

			<?php } ?>
		</tbody>
	<table>

	<button type="button" onclick="addSatMode();" class="btn btn-sm btn-primary addsatmode"><i class="fas fa-plus-square"></i> <?= __("Add satellite mode"); ?></button>

</div>
</div>
