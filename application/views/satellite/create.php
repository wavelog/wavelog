
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
				<label for="nameInput">Satellite name</label>
				<input type="text" class="form-control" name="band" id="nameInput" aria-describedby="nameInputHelp" required>
				<small id="nameInputHelp" class="form-text text-muted">Satellite name</small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="exportNameInput">Export name</label>
				<input type="text" class="form-control" name="exportNameInput" id="exportNameInput" aria-describedby="exportNameInputHelp" required>
				<small id="exportNameInputHelp" class="form-text text-muted">If external services uses another name for the satellite, like LoTW</small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="orbit">Orbit</label>
				<input type="text" class="form-control" name="orbit" id="orbit" aria-describedby="orbitHelp" required>
				<small id="sorbitHelp" class="form-text text-muted">Enter which orbit the satellite has (LEO, MEO, GEO(</small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="mode">Satellite mode name</label>
				<input type="text" class="form-control" name="mode" id="mode" aria-describedby="modeHelp" required>
				<small id="modeHelp" class="form-text text-muted">Enter satellite mode</small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="uplinkMode">Uplink mode</label>
				<input type="text" class="form-control" name="uplinkMode" id="uplinkMode" aria-describedby="uplinkModeHelp" required>
				<small id="uplinkModeHelp" class="form-text text-muted">Enter modulation used for uplink</small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="uplinkFrequency">Uplink frequency</label>
				<input type="text" class="form-control" name="uplinkFrequency" id="uplinkFrequency" aria-describedby="uplinkFrequencyHelp" required>
				<small id="uplinkFrequencyHelp" class="form-text text-muted">Enter frequency (in Hz) used for uplink</small>
			</div>
		</div>
		<div class = "row">
			<div class="mb-3 col-md-6">
				<label for="downlinkMode">Downlink mode</label>
				<input type="text" class="form-control" name="downlinkMode" id="downlinkMode" aria-describedby="downlinkModeHelp" required>
				<small id="downlinkModeHelp" class="form-text text-muted">Enter modulation used for downlink</small>
			</div>
			<div class="mb-3 col-md-6">
				<label for="downlinkFrequency">Downlink frequency</label>
				<input type="text" class="form-control" name="downlinkFrequency" id="downlinkFrequency" aria-describedby="downlinkFrequency" required>
				<small id="downlinkFrequencyHelp" class="form-text text-muted">Enter frequency (in Hz) used for downlink</small>
			</div>
		</div>

			<button type="button" onclick="createSatellite(this.form);" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i> <?php echo lang('options_save'); ?></button>

		</form>
</div>
