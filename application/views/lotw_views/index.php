<div class="container lotw">
<?php if($uploads_folder != true) { ?>
	<div class="alert alert-warning" role="alert" style="margin-top: 1rem;">
		<span class="badge text-bg-info"><?= __("Important"); ?></span> <i class="fas fa-ban"></i> <?= __("Upload folder is not writable. Please contact your admin."); ?>
	</div>
<?php } ?>
<br>
	<?php
	if (!($this->config->item('disable_manual_lotw'))) { ?>
	<a class="btn btn-outline-primary btn-sm float-end" href="<?php echo site_url('/lotw/import'); ?>" role="button"><i class="fas fa-cloud-download-alt"></i> <?= __("LoTW Import"); ?></a>
	<?php } ?>
	<h2><?= __("Logbook of the World"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<a class="btn btn-outline-success btn-sm float-end" href="<?php echo site_url('/lotw/cert_upload'); ?>" role="button"><i class="fas fa-cloud-upload-alt"></i> <?= __("Upload Certificate"); ?></a><i class="fab fa-expeditedssl"></i> <?= __("Available Certificates"); ?>
		</div>

		<div class="lotw-cert-list">
			<?php if(isset($error)) { ?>
				<div class="alert alert-danger" role="alert">
			  	<?php echo $error; ?>
				</div>
	    	<?php } ?>	
		
			<?php $this->load->view('layout/messages'); ?>

	    	<?php if ($lotw_cert_results->num_rows() > 0) { ?>

	    	<div class="table-responsive">
				<table class="table table-hover">
					<thead class="thead-light">
						<tr>
				 			<th scope="col"><?= __("Callsign"); ?></th>
							<th scope="col"><?= __("DXCC"); ?></th>
							<th scope="col"><?= __("QSO Start Date"); ?></th>
							<th scope="col"><?= __("QSO End Date"); ?></th>
							<th scope="col"><?= __("Date Created"); ?></th>
							<th scope="col"><?= __("Date Expires"); ?></th>
							<th scope="col"><?= __("Status"); ?></th>
							<th scope="col"><?= __("Last Upload"); ?></th>
							<th scope="col"><?= __("Options"); ?></th>
						</tr>
					</thead>
				 
					<tbody>

						<?php foreach ($lotw_cert_results->result() as $row) { ?>
							<tr>
					      		<td><?php echo $row->callsign; ?></td>
                           <td><?php echo $row->cert_dxcc == '' ? '- NONE -' : ucfirst($row->cert_dxcc); if ($row->cert_dxcc_end != NULL) { echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; } ?></td>
								<td><?php
									if (isset($row->qso_start_date)) {
										$valid_qso_start = strtotime( $row->qso_start_date );
										$new_valid_qso_start = date($this->config->item('qso_date_format'), $valid_qso_start );
										echo $new_valid_qso_start;
									} else {
										echo "n/a";
									} ?>
								</td>
								<td><?php
									if (isset($row->qso_end_date)) {
										$valid_qso_end = strtotime( $row->qso_end_date );
										$new_valid_qso_end = date($this->config->item('qso_date_format'), $valid_qso_end );
										echo $new_valid_qso_end;
									} else {
										echo "n/a";
									} ?>
								</td>
								<td><?php
									$valid_from = strtotime( $row->date_created );
									$new_valid_from = date($this->config->item('qso_date_format'), $valid_from );
									echo $new_valid_from; ?>
								</td>
								<td>
									<?php
									$valid_to = strtotime( $row->date_expires );
									$new_valid_to = date($this->config->item('qso_date_format'), $valid_to );
									echo $new_valid_to; ?>
								</td>
								<td>
									<?php $current_date = date('Y-m-d H:i:s'); ?>
									<?php $warning_date = date('Y-m-d H:i:s', strtotime($row->date_expires.'-30 days')); ?>

									<?php if ($current_date > $row->date_expires) { ?>
										<span class="badge text-bg-danger"><?= __("Expired"); ?></span>
									<?php } else if ($current_date <= $row->date_expires && $current_date > $warning_date) { ?>
										<span class="badge text-bg-warning"><?= __("Expiring"); ?></span>
									<?php } else { ?>
										<span class="badge text-bg-success"><?= __("Valid"); ?></span>
									<?php } ?>
								</td>
								<td>
									<?php if ($row->last_upload) {
										$last_upload_ts = strtotime($row->last_upload ?? '1970-01-01');
										$last_upload = date($this->config->item('qso_date_format').' H:i:s', $last_upload_ts);
										$last_upload_fail_ts = strtotime($row->last_upload_fail ?? '1970-01-01');
										$last_upload_fail = date($this->config->item('qso_date_format').' H:i:s', $last_upload_fail_ts);
										if ($last_upload_fail_ts > $last_upload_ts) { ?>
											<span data-bs-toggle="tooltip" data-bs-original-title="<?php echo $row->last_upload_status;?>. <?= sprintf(__("Last success: %s"), $last_upload); ?>" class="badge text-bg-danger"><?php echo $last_upload_fail; ?></span>
										<?php } else {
											if ($row->last_upload_fail && $last_upload_fail && $row->last_upload_status)  { ?>
												<span data-bs-toggle="tooltip" data-bs-original-title="<?php echo $row->last_upload_status;?>. <?= sprintf(__("Last fail: %s"), $last_upload_fail); ?>" class="badge text-bg-success"><?php echo $last_upload; ?></span>
											<?php } else { ?>
												<span class="badge text-bg-success"><?php echo $last_upload; ?></span>
											<?php } ?>
										<?php } ?>
									<?php } else { ?>
										<span class="badge text-bg-warning"><?= __("Not Synced"); ?></span>
									<?php } ?>
								</td>
								<td>
									<a class="btn btn-outline-danger btn-sm" href="<?php echo site_url('lotw/delete_cert/'.$row->lotw_cert_id); ?>" role="button"><i class="far fa-trash-alt"></i> <?= __("Delete"); ?></a>
								</td>
							</tr>
						<?php } ?>

					</tbody>
				</table>
			</div>

			<?php } else { ?>
			<div class="alert alert-info" role="alert">
				<?= __("You need to upload some LoTW p12 certificates to use this area."); ?>
			</div>
			<?php } ?>

	    </div>
	</div>
	<!-- Card Ends -->

	<br>

	<!-- Card Starts -->
	<?php
	if (!($this->config->item('disable_manual_lotw'))) { ?>
	<div class="card">
		<div class="card-header">
			<?= __("Information"); ?>
		</div>

		<div class="card-body">
            		<?php if (($next_run ?? '') != '') { echo "<p>".__("The next automatic sync with LoTW will happen at: ").$next_run."</p>"; } ?>
			<button class="btn btn-outline-success" hx-get="<?php echo site_url('lotw/lotw_upload'); ?>"  hx-target="#lotw_manual_results">
				<?= __("Manual Sync"); ?>
			</button>

			<div id="lotw_manual_results"></div>
		</div>
	</div>
	<?php } ?>

</div>
