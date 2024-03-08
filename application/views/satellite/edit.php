<div class="container" id="edit_satellite_dialog">
	<form>

	<input type="hidden" name="id" value="<?php echo $satellite->id; ?>">
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="nameInput">Satellite name</label>
				<input type="text" class="form-control" name="nameInput" id="nameInput" aria-describedby="nameInputHelp" value="<?php if(set_value('band') != "") { echo set_value('band'); } else { echo $satellite->name; } ?>" required>
				<small id="nameInputHelp" class="form-text text-muted">Satellite name</small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="exportNameInput">Export name</label>
				<input type="text" class="form-control" name="exportNameInput" id="exportNameInput" aria-describedby="exportNameInputHelp" value="<?php if(set_value('band') != "") { echo set_value('band'); } else { echo $satellite->exportname; } ?>" required>
				<small id="exportNameInputHelp" class="form-text text-muted">If external services uses another name for the satellite, like LoTW</small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="orbit">Orbit</label>
				<input type="text" class="form-control" name="orbit" id="orbit" aria-describedby="orbitHelp" value="<?php if(set_value('band') != "") { echo set_value('band'); } else { echo $satellite->orbit; } ?>" required>
				<small id="sorbitHelp" class="form-text text-muted">Enter which orbit the satellite has (LEO, MEO, GEO)</small>
			</div>
		</div>

		<button type="button" onclick="saveUpdatedSatellite(this.form);" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i> <?php echo lang('options_save'); ?></button>

		</form>
</div>
