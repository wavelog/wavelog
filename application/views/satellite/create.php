
<div class="container" id="create_satellite">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

		<?php if($this->session->flashdata('notice')) { ?>
			<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
			</div>
		<?php } ?>

		<?php $this->load->helper('form'); ?>

		<form>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="displayNameInput"><?= __("Satellite Display Name"); ?></label>
				<input type="text" class="form-control" name="displayNameInput" id="displayNameInput" aria-describedby="displayNameInputHelp" required>
				<small id="displayNameInputHelp" class="form-text text-muted"><?= __("Display / cleartext name of the satellite"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="nameInput"><?= __("LoTW Name"); ?></label>
				<input type="text" class="form-control" name="nameInput" id="nameInput" aria-describedby="nameInputHelp">
				<small id="nameInputHelp" class="form-text text-muted"><?= __("Satellite name as accepted by LoTW. Not necessarily the same as the display name. Can be set/changed later when added to LoTW."); ?></small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="orbit"><?= __("Orbit"); ?></label>
				<select id="orbit" class="form-select" name="orbit" aria-describedby="orbitHelp">
					<option value="LEO" selected="selected">LEO</option>
					<option value="MEO">MEO</option>
					<option value="GEO">GEO</option>
				</select>
				<small id="orbitHelp" class="form-text text-muted"><?= __("Enter which orbit the satellite has (LEO, MEO, GEO)"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="mode"><?= __("Satellite mode name"); ?></label>
				<input type="text" class="form-control" name="mode" id="mode" aria-describedby="modeHelp" required>
				<small id="modeHelp" class="form-text text-muted"><?= __("Enter satellite mode"); ?></small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="uplinkMode"><?= __("Uplink mode"); ?></label>
				<input type="text" class="form-control" name="uplinkMode" id="uplinkMode" aria-describedby="uplinkModeHelp" required>
				<small id="uplinkModeHelp" class="form-text text-muted"><?= __("Enter modulation used for uplink"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="uplinkFrequency"><?= __("Uplink frequency"); ?></label>
				<input type="text" class="form-control" name="uplinkFrequency" id="uplinkFrequency" aria-describedby="uplinkFrequencyHelp" required>
				<small id="uplinkFrequencyHelp" class="form-text text-muted"><?= __("Enter frequency (in Hz) used for uplink"); ?></small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="downlinkMode"><?= __("Downlink mode"); ?></label>
				<input type="text" class="form-control" name="downlinkMode" id="downlinkMode" aria-describedby="downlinkModeHelp" required>
				<small id="downlinkModeHelp" class="form-text text-muted"><?= __("Enter modulation used for downlink"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="downlinkFrequency"><?= __("Downlink frequency"); ?></label>
				<input type="text" class="form-control" name="downlinkFrequency" id="downlinkFrequency" aria-describedby="downlinkFrequency" required>
				<small id="downlinkFrequencyHelp" class="form-text text-muted"><?= __("Enter frequency (in Hz) used for downlink"); ?></small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="lotwAccepted"><?= __("Accepted by LoTW"); ?></label>
				<select id="lotwAccepted" class="form-select" name="lotwAccepted">
					<option value="Y"><?= __("Yes"); ?></option>
					<option value="N" selected="selected"><?= __("No"); ?></option>
				</select>
				<small id="displayNameInputHelp" class="form-text text-muted"><?= __("Set to yes only if satellite is accepted my LoTW"); ?></small>
			</div>
		</div>

			<button type="button" onclick="createSatellite(this.form);" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i> <?= __("Save"); ?></button>

		</form>
</div>
