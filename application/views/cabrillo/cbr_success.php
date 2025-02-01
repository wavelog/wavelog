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
    <?= __("Results of CBR Contest Data Update");?>
  </div>
  <div class="card-body">
    <?php if($cbr_update_count > 0) { ?>
       <h3 class="card-title"><?= __("Yay, its updated!"); ?></h3>
       <p class="card-text"><?= __("Your contest QSOs have been updated using the values of your Cabrillo file.")?></p>
    <?php } else { ?>
       <h3 class="card-title"><?= __("No QSOs were updated by your Cabrillo file.")?></h3>
    <?php } ?>
       <div class="alert alert-info" role="alert">
          <?= __("QSOs updated")?>: <?php echo $cbr_update_count ?> / <?= __("QSOs ignored")?>: <?php echo $cbr_error_count ?>
       </div>
    <?php if($cbr_error_count > 0) { ?>
      <h3><?= __("CBR errors")?></h3>
      <table width="100%">
         <?php foreach ($cbr_errors as $error) { ?>
         <tr>
            <td><?php echo $error; ?></td>
         </tr>
         <?php } ?>
      </table>
   <?php } ?>
  </div>
</div>


</div>
