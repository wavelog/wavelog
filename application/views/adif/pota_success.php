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
    <?= __("Results of POTA Update");?>
  </div>
  <div class="card-body">
    <?php if($pota_error_count[0] > 0) { ?>
       <h3 class="card-title"><?= __("Yay, its updated!"); ?></h3>
       <p class="card-text"><?= __("POTA references for existing QSOs has been updated.")?></p>
    <?php } else { ?>
       <h3 class="card-title"><?= __("No QSOs found which could be updated.")?></h3>
    <?php } ?>
       <div class="alert alert-info" role="alert">
          <?= __("QSOs updated")?>: <?php echo $pota_error_count[0] ?> / <?= __("QSOs ignored")?>: <?php echo $pota_error_count[1] ?> / <?= __("QSOs unmatched")?>: <?php echo $pota_error_count[2] ?>
       </div>
    <?php if($pota_errors) { ?>
      <h3><?= __("POTA Update Errors")?></h3>
      <p><?= __("There is different data for POTA references in your log compared to imported data or QSO data could not be matched")?></p>
      <table width="100%">
         <tr class="titles">
            <td><?= __("Date"); ?></td>
            <td><?= __("Time"); ?></td>
            <td><?= __("Call"); ?></td>
            <td><?= __("Band"); ?></td>
            <td><?= __("Mode"); ?></td>
            <td><?= __("POTA REF in Log"); ?></td>
            <td><?= __("POTA REF in ADIF"); ?></td>
            <td><?= __("Status"); ?></td>
         </tr>
      <?php echo $pota_errors; ?>
      </table>
    <?php } ?>
  </div>
</div>


</div>
