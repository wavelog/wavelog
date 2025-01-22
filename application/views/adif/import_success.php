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
    
    <!-- Success message -->
    <h3 class="card-title"><?= __("Yay, its imported!")?></h3>
    <p class="card-text"><?= __("The ADIF File has been imported.")?>
    
    <!-- Dupe information -->
    <?php if(isset($skip_dupes)) {
             echo " <b>" . __("Dupes were inserted!") . "</b>";
          } else {
             echo " ".__("Dupes were skipped.");
          } ?>
    </p>

    <!-- Display imported information for contest data fixing if contest data was imported -->
    <?php if(count($imported_contests) > 0) {?>
    <p style="color:red;"><?= __("You imported at least 1 QSO containing a contest ID.")?></p>
    <p><?= __("Sometimes, depending on your contest logging software, your exchanges will not be imported properly from that softwares ADIF. If you like to correct that, switch to the CBR Import Tab of the ADIF Import page.")?></p>
    <p><?= __("We found the following numbers of QSOs for the following contest IDs:")?></p>
    
    <!-- List imported contest data -->
    <ul>
    <?php foreach ($imported_contests as $contestid => $qsocount) { ?>
      <li><?php echo $contestid . ' (' . $qsocount . ' '. ($qsocount == 1 ? 'QSO' : 'QSOs')  .')'; ?></li>
    <?php } ?>
    </ul>
    <?php } ?>
   
    <!-- Display errors for ADIF import -->
    <?php if($adif_errors) { ?>
      <h3><?= __("Import details / possible problems")?></h3>
      <p><?= __("You might have ADIF errors, the QSOs have still been added. Please check the following information:")?></p>
      <p class="card-text"><?php echo $adif_errors; ?></p>
    <?php } ?>
  </div>
</div>


</div>
