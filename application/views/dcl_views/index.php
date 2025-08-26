<div class="container dcl">
<br>
	<h2><?= __("DCL"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<a class="btn btn-outline-danger btn-sm float-end" href="<?php echo site_url('dcl/delete_key'); ?>" role="button"><i class="far fa-trash-alt"></i> <?= __("Delete Keys"); ?></a>
			<a class="btn btn-outline-success btn-sm float-end" href="<?php echo site_url('/dcl/key_import'); ?>" role="button"><i class="fas fa-cloud-upload-alt"></i> <?= __("Request DCL Key"); ?></a><i class="fab fa-expeditedssl"></i> <?= __("Available DCL-Keys"); ?>
		</div>

		<div class="key-list">
			<?php if(isset($error)) { ?>
				<div class="alert alert-danger" role="alert">
			  	<?php echo $error; ?>
				</div>
	    	<?php } ?>	
		
			<?php $this->load->view('layout/messages'); ?>

	    	<?php if (count($dcl_keys ?? []) > 0) { ?>

	    	<div class="table-responsive">
				<table class="table table-hover">
					<thead class="thead-light">
						<tr>
				 			<th scope="col"><?= __("Callsign"); ?></th>
							<th scope="col"><?= __("Valid from"); ?></th>
							<th scope="col"><?= __("Valid till"); ?></th>
						</tr>
					</thead>
				 
					<tbody>

						<?php foreach ($dcl_keys as $row) { 
							usort($row->Callsigns, fn($a, $b) => $a->startDate <=> $b->startDate);
							foreach ($row->Callsigns as $dcl_call) { 
								if (($dcl_call->endDate ?? '') == '') {
									$dcl_call->endDate='-------';
								} else {
									$dcl_call->endDate=date($date_format,strtotime($dcl_call->endDate));
								}
						?>
							<tr>
					      		<td><?php echo $dcl_call->callsign; ?></td>
								<?php
									$vf = date($date_format,strtotime($dcl_call->startDate));
									$vt = $dcl_call->endDate;
								?>
								<td><?php echo $vf; ?></td>
								<td><?php echo $vt; ?></td>
							</tr>
						<?php }} ?>

					</tbody>
				</table>
			</div>

			<?php } else { ?>
			<div class="alert alert-info" role="alert">
				<?= __("You need to request DCL keys to use this function."); ?>
			</div>
			<?php } ?>

	    </div>
	</div>
	<!-- Card Ends -->

	<br>

	<!-- Card Starts -->
	<?php
	if (!($this->config->item('disable_manual_dcl'))) { ?>
	<div class="card">
		<div class="card-header">
			<?= __("Information"); ?>
		</div>

		<div class="card-body">
            		<?php if (($next_run ?? '') != '') { echo "<p>".__("The next automatic sync with DCL will happen at: ").$next_run."</p>"; } ?>
			<button class="btn btn-outline-success" hx-on:click="document.getElementById('dcl_manual_results').innerHTML = '';" hx-get="<?php echo site_url('dcl/dcl_upload'); ?>" hx-indicator="#lotw-sync-running" hx-target="#dcl_manual_results">
            <?= __("Manual Sync"); ?>
			</button>
			<span style="margin-left: 10px;" id="lotw-sync-running" class="htmx-indicator"> <?php echo __("running..."); ?></span>
			<br/>
			<div id="dcl_manual_results"></div>
		</div>
	</div>
	<?php } ?>

</div>
