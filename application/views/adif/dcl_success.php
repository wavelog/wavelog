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
    <?= __("Results of DCL DOK Update");?>
  </div>
  <div class="card-body">
    <?php if($dcl_error_count[0] > 0) { ?>
       <h3 class="card-title"><?= __("Yay, its updated!"); ?></h3>
       <p class="card-text"><?= __("DCL information for DOKs has been updated.")?></p>
    <?php } else { ?>
       <h3 class="card-title"><?= __("No QSOs found which could be updated.")?></h3>
    <?php } ?>
       <div class="alert alert-info" role="alert">
          <?= __("QSOs updated")?>: <?php echo $dcl_error_count[0] ?> / <?= __("QSOs ignored")?>: <?php echo $dcl_error_count[1] ?> / <?= __("QSOs unmatched")?>: <?php echo $dcl_error_count[2] ?>
       </div>
    <?php if($dcl_errors) { ?>
      <h3><?= __("DOK Errors")?></h3>
      <p><?= __("There is different data for DOK in your log compared to DCL")?></p>
      <table width="100%">
         <tr class="titles">
            <td><?= __("Date"); ?></td>
            <td><?= __("Time"); ?></td>
            <td><?= __("Call"); ?></td>
            <td><?= __("Band"); ?></td>
            <td><?= __("Mode"); ?></td>
            <td><?= __("DOK in Log"); ?></td>
            <td><?= __("DOK in DCL"); ?></td>
            <td><?= __("DCL QSL Status"); ?></td>
         </tr>
      <?php echo $dcl_errors; ?>
      </table>
    <?php } ?>
  </div>
</div>


</div>
