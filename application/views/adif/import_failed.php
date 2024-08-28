<div class="container">
<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<div class="card">
  <div class="card-header">
    <?= __("ADIF Import failed!")?>
  </div>
  <div class="card-body">
    <h3 class="card-title"><?= __("The ADIF file could not be parsed correctly.")?></h3>
    <p class="card-text"><?= __("At least one of the ADIF fields could not be parsed and/or inserted into the database. Please check the imported ADIF file. You can use an online ADIF file checker. For example:")?></p>
    <p class="card-text"><a target="_blank" href="https://www.rickmurphy.net/adifvalidator.html">https://www.rickmurphy.net/adifvalidator.html</a></p>
  </div>
</div>


</div>
