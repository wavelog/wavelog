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
    <?= __("ADIF Imported")?>
  </div>
  <div class="card-body">
    <h3 class="card-title"><?= __("Yay, its imported!")?></h3>
    <p class="card-text"><?= __("The ADIF File has been imported.")?>
    <?php if(isset($skip_dupes)) {
             echo " <b>" . __("Dupes were inserted!") . "</b>";
          } else {
             echo " ".__("Dupes were skipped.");
          } ?>
    </p>
    <?php if($adif_errors) { ?>
      <h3><?= __("Import details / possible problems")?></h3>
      <p><?= __("You might have ADIF errors, the QSOs have still been added. Please check the following information:")?></p>
      <p class="card-text"><?php echo $adif_errors; ?></p>
    <?php } ?>
  </div>
</div>


</div>
