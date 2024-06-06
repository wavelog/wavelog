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
    <?php echo __("Results of DCL DOK Update");?>
  </div>
  <div class="card-body">
    <?php if($dcl_error_count[0] > 0) { ?>
       <h3 class="card-title">Yay, its updated!</h3>
       <p class="card-text"><?php echo __("DCL information for DOKs has been updated.")?></p>
    <?php } else { ?>
       <h3 class="card-title"><?php echo __("No QSOs found which could be updated.")?></h3>
    <?php } ?>
       <div class="alert alert-info" role="alert">
          <?php echo __("QSOs updated")?>: <?php echo $dcl_error_count[0] ?> / <?php echo __("QSOs ignored")?>: <?php echo $dcl_error_count[1] ?> / <?php echo __("QSOs unmatched")?>: <?php echo $dcl_error_count[2] ?>
       </div>
    <?php if($dcl_errors) { ?>
      <h3><?php echo __("DOK Errors")?></h3>
      <p><?php echo __("There is different data for DOK in your log compared to DCL")?></p>
      <table width="100%">
         <tr class="titles">
            <td>Date</td>
            <td>Time</td>
            <td>Call</td>
            <td>Band</td>
            <td>Mode</td>
            <td>DOK in Log</td>
            <td>DOK in DCL</td>
            <td>DCL QSL Status</td>
         </tr>
      <?php echo $dcl_errors; ?>
      </table>
    <?php } ?>
  </div>
</div>


</div>
