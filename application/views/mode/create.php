
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
		    <label for="modeInput"><?= _pgettext("Name of mode in ADIF-specification", "ADIF Mode"); ?></label>
		    <input type="text" class="form-control" name="mode" id="modeInput" aria-describedby="modeInputHelp" required>
		    <small id="modeInputHelp" class="form-text text-muted"><?= __("Name of mode in ADIF-specification"); ?></small>
		  </div>
		  
		  <div class="mb-3">
		    <label for="submodeInput"><?= _pgettext("Name of sub-mode in ADIF-specification", "ADIF Sub-Mode"); ?></label>
		    <input type="text" class="form-control" name="submode" id="submodeInput" aria-describedby="submodeInputHelp">
		    <small id="submodeInputHelp" class="form-text text-muted"><?= __("Name of sub-mode in ADIF-specification"); ?></small>
		  </div>

			<div class="mb-3">
		    <label for="qrgmodeInput">SSB/CW/DATA</label>
			<select id="qrgmodeInput" class="form-select mode form-select-sm" name="qrgmode">
				<option value="CW">CW</option>
				<option value="SSB">SSB</option>
				<option value="DATA">DATA</option>
			</select>
		    <small id="qrgmodeInputHelp" class="form-text text-muted"><?= __("Defines the QRG-segment in bandplan."); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="activeInput"><?= __("Active"); ?></label>
			<select id="activeInput" class="form-select mode form-select-sm" name="active">
				<option value="1"><?= __("Active"); ?></option>
				<option value="0"><?= __("Not active"); ?></option>
			</select>
		    <small id="activeInputHelp" class="form-text text-muted"><?= __("Set to active if to be listed in Modes-list"); ?></small>
		  </div>

			<button type="button" onclick="createMode(this.form);" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Create mode"); ?></button>

		</form>
</div>