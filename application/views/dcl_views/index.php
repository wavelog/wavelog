<div class="container dcl">
<br>
	<a class="btn btn-outline-primary btn-sm float-end" href="<?php echo site_url('/dcl/import'); ?>" role="button"><i class="fas fa-cloud-download-alt"></i> <?= __("DCL Import"); ?></a>
	<h2><?= __("DCL"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
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
							<th scope="col"><?= __("Last Upload"); ?></th>
							<th scope="col"><?= __("Key"); ?></th>
							<th scope="col"><?= __("Options"); ?></th>
						</tr>
					</thead>
				 
					<tbody>

						<?php foreach ($dcl_keys as $row) { ?>
							<tr>
					      		<td><?php echo $row->call; ?></td>
								<td>
									<?php
										$last_upload_ts = strtotime($row->last_sync ?? '1970-01-01');
										$last_upload = date($this->config->item('qso_date_format').' H:i:s', $last_upload_ts);
										if ($last_upload_ts == strtotime('1970-01-01')) { ?>
											<span data-bs-toggle="tooltip" <?= sprintf(__("Last success: %s"), $last_upload); ?>" class="badge text-bg-danger"><?= __("Never"); ?></span>
										<?php } else { ?>
												<span class="badge text-bg-success"><?php echo $last_upload; ?></span>
											<?php } ?>
								</td>
								<td><?php echo $row->key; ?>
								<td>
									<a class="btn btn-outline-danger btn-sm" href="<?php echo site_url('dcl/delete_key/'.str_replace('/','_',$row->call)); ?>" role="button"><i class="far fa-trash-alt"></i> <?= __("Delete"); ?></a>
								</td>
							</tr>
						<?php } ?>

					</tbody>
				</table>
			</div>

			<?php } else { ?>
			<div class="alert alert-info" role="alert">
				<?= __("You need to request some DCL Keys to use this area."); ?>
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

			<div id="dcl_manual_results"></div>
		</div>
	</div>
	<?php } ?>

</div>
