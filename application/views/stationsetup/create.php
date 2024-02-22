<div class="container" id="create_station_logbook">

<br>
		<!-- Display Message -->
		<div id="flashdata" class="alert-message error">
		</div>

<div class="card">
  <div class="card-header">
    <?php echo $page_title; ?>
  </div>
  <div class="card-body">
		<?php if($this->session->flashdata('notice')) { ?>
			<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
			</div>
		<?php } ?>

		<?php $this->load->helper('form'); ?>

		  <div class="mb-3">
		    <label for="stationLogbookNameInput"><?php echo lang('station_logbooks_create_name');?></label>
		    <input type="text" class="form-control" name="logbook_name" id="logbook_name" aria-describedby="stationLogbookNameHelp" placeholder="Home QTH" required>
		    <small id="stationLogbookNameHelp" class="form-text text-muted"><?php echo lang('station_logbooks_create_name_hint');?></small>
		  </div>

  </div>
</div>

<br>

</div>
