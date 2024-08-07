
<div class="container" id="create_mode">

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

		<?php echo validation_errors(); ?>

		<form>
		<div class="mb-3">
		    <label for="bandInput"><?= __("Band"); ?></label>
		    <input type="text" class="form-control" name="band" id="bandInput" aria-describedby="bandInputHelp" required>
		    <small id="bandInputHelp" class="form-text text-muted"><?= __("Name of Band (E.g. 20m)"); ?></small>
		  </div>
		  <div class="mb-3">
			<label for="bandGroup"><?= __("Bandgroup"); ?></label>
			<input type="text" class="form-control" name="bandgroup" id="bandGroup" aria-describedby="bandgroupInputHelp" required>
			<small id="bandgroupInputHelp" class="form-text text-muted"><?= __("Name of bandgroup (E.g. hf, vhf, uhf, shf)"); ?></small>
		</div>
		<div class="mb-3">
			<label for="ssbqrg"><?= __("SSB QRG"); ?></label>
			<input type="text" class="form-control" name="ssbqrg" id="ssbqrg" aria-describedby="ssbqrgInputHelp" required>
			<small id="ssbqrgInputHelp" class="form-text text-muted"><?= __("Frequency for SSB QRG in band (must be in Hz)"); ?></small>
		</div>
		<div class="mb-3">
			<label for="dataqrg"><?= __("DATA QRG"); ?></label>
			<input type="text" class="form-control" name="dataqrg" id="dataqrg" aria-describedby="dataqrgInputHelp" required>
			<small id="dataqrgInputHelp" class="form-text text-muted"><?= __("Frequency for DATA QRG in band (must be in Hz)"); ?></small>
		</div>
		<div class="mb-3">
			<label for="cwqrg"><?= __("CW QRG"); ?></label>
			<input type="text" class="form-control" name="cwqrg" id="cwqrg" aria-describedby="cwqrgInputHelp" required>
			<small id="cwqrgInputHelp" class="form-text text-muted"><?= __("Frequency for CW QRG in band (must be in Hz)"); ?></small>
		</div>
		  
			<button type="button" onclick="createBand(this.form);" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Save"); ?></button>

		</form>
</div>