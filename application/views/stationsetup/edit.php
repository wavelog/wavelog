<div class="container" id="create_station_logbook">

<br>
		<!-- Display Message -->
		<div id="flashdata" class="alert-message error">
		</div>

		<?php if($this->session->flashdata('notice')) { ?>
			<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
			</div>
		<?php } ?>

		<?php $this->load->helper('form'); ?>
		<input type="hidden" id="logbookid" name="id" value="<?php echo $container->logbook_id; ?>">

		<div class="mb-3">
			<label for="stationLogbookNameInput"><?= __("Container name"); ?></label>
			<input type="text" class="form-control" name="logbook_name" id="logbook_name" aria-describedby="stationLogbookNameHelp" placeholder="Home QTH" required value="<?php echo $container->logbook_name; ?>">
			<small id="stationLogbookNameHelp" class="form-text text-muted"><?= __("You can call a container anything."); ?></small>
		</div>


</div>
