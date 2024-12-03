<script>
	var lang_admin_confirm_pwd_reset = "<?= __("Do you really want to send this user a password-reset link?"); ?>";
	var lang_admin_user = "<?= __("User"); ?>";
	var lang_gen_hamradio_callsign = "<?= __("Callsign"); ?>";

	var lang_admin_email_settings_incorrect = "<?= __("Email settings are incorrect."); ?>";
	var lang_admin_password_reset_processed = "<?= __("Password-reset e-mail sent to user:"); ?>";
</script>
<div class="container">

	<br>

	<h2><?php echo $page_title; ?></h2>

	<?php if ($this->session->flashdata('notice')) { ?>
		<!-- Display Message -->
		<div class="alert alert-info" role="alert">
			<?php echo $this->session->flashdata('notice'); ?>
		</div>

	<?php } ?>

	<!-- This Info will be shown by the admin password reset -->
	<div class="alert" id="pwd_reset_message" style="display: hide" role="alert"></div>

	<div class="card">
		<div class="card-header">
			<?= __("User List"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("Wavelog needs at least one user configured in order to operate."); ?></p>
			<p class="card-text"><?= __("Users can be assigned roles which give them different permissions, such as adding QSOs to the logbook and accessing Wavelog APIs."); ?></p>
			<p class="card-text"><?= __("The currently logged-in user is displayed at the upper-right of each page."); ?></p>
			<p class="card-text"><?= __("With the password reset button, you can send a user an email containing a link to reset their password. To achieve this, ensure that the email settings in the global options are configured correctly."); ?></p>
			<p>
				<a class="btn btn-primary" href="<?php echo site_url('user/add'); ?>"><i class="fas fa-user-plus"></i> <?= __("Create user"); ?></a>
				<a class="btn btn-secondary" style="float: right;" href="<?php echo site_url('user'); ?>"><i class="fas fa-sync"></i> <?= __("Refresh List"); ?></a>
			</p>

			<div class="table-responsive">
				<table class="table table-striped" id="adminusertable">
					<thead>
						<tr>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("User"); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Callsign"); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("E-mail"); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Type"); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Last seen"); ?></th>
							<th></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Edit"); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Password Reset"); ?></th>
							<?php if (!$disable_impersonate) { ?>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Impersonate"); ?></th>
							<?php } ?>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Delete"); ?></th>
						</tr>
					</thead>
					<tbody>

						<?php

						$i = 0;
						foreach ($results->result() as $row) { ?>
							<?php echo '<tr class="tr' . ($i & 1) . '">'; ?>
							<td style="text-align: left; vertical-align: middle;"><a href="<?php echo site_url('user/edit') . "/" . $row->user_id; ?>"><?php echo $row->user_name; ?></a></td>
							<td style="text-align: left; vertical-align: middle;"><?php echo $row->user_callsign; ?></td>
							<td style="text-align: left; vertical-align: middle;"><a href="mailto:<?php echo $row->user_email; ?>"><?php echo $row->user_email; ?></a></td>
							<td style="text-align: left; vertical-align: middle;"><?php $l = $this->config->item('auth_level');
																					echo $l[$row->user_type]; ?></td>
							<td style="text-align: left; vertical-align: middle;"><?php
								if ($row->last_seen != null) { // if the user never logged in before the value is null. We can show "never" then.
									$lastSeenTimestamp = strtotime($row->last_seen);
									$currentTimestamp = time();
									if (($currentTimestamp - $lastSeenTimestamp) < 120) {
										echo "<a><i style=\"color: green;\" class=\"fas fa-circle\"></i> " . date($custom_date_format . ' H:i:s', $lastSeenTimestamp) . "</a>";
									} else {
										echo "<a><i style=\"color: red;\" class=\"fas fa-circle\"></i> " . date($custom_date_format . ' H:i:s', $lastSeenTimestamp) . "</a>";
									}
								} else {
									echo __("Never");
								}?>
							</td>
							<td style="text-align: left; vertical-align: middle;">
								<span class="badge text-bg-success"><?= __("Locations"); ?>: <?php echo $row->stationcount; ?></span>
								<br>
								<span class="badge text-bg-info"><?= __("Logbooks"); ?>: <?php echo $row->logbookcount; ?></span>
								<?php if ($row->qsocount > 0) { ?>
									<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Last QSO:"); ?><br><?php echo $row->lastqso; ?>"><?php echo $row->qsocount; ?> <?= __("QSO"); ?></span>
								<?php } else { ?>
									<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("No QSOs in Log"); ?>"><?php echo $row->qsocount; ?> <?= __("QSO"); ?></span>
								<?php } ?>
							</td>
							<td style="text-align: center; vertical-align: middle;"><a href="<?php echo site_url('user/edit') . "/" . $row->user_id; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-edit"></i></a>
							<td style="text-align: center; vertical-align: middle;">
								<?php
								if ($session_uid != $row->user_id) {
									echo '<a class="btn btn-primary btn-sm ms-1 admin_pwd_reset" data-username="' . $row->user_name . '" data-callsign="' . $row->user_callsign . '" data-userid="' . $row->user_id . '" data-usermail="' . $row->user_email . '"><i class="fas fa-key"></i></a>';
								}
								?></td>
							<?php if (!$disable_impersonate) { ?>
							<td style="text-align: center; vertical-align: middle;">
								<?php
								if ($session_uid != $row->user_id) { ?>
									<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#impersonateModal_<?php echo $i; ?>"><i class="fas fa-people-arrows"></i></button>
									<div class="modal fade bg-black bg-opacity-50" id="impersonateModal_<?php echo $i; ?>" tabindex="-1" aria-labelledby="impersonateLabel_<?php echo $i; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
										<div class="modal-dialog modal-dialog-centered modal-md">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="impersonateLabel_<?php echo $i; ?>"><?= __("Impersonate User") ?></h5>
												</div>
												<div class="modal-body" style="text-align: left !important;">
													<div class="mb-3">
														<?php if(!$has_flossie) { ?>
														<p><?= __("You are about to impersonate another user. To return to your admin account, you'll need to logout and log back in as admin."); ?></p>
														<p><?= __("Do you want to impersonate this user?"); ?></p>
														<br>
														<table>
															<tr>
																<td class="pe-3"><?= __("Username:"); ?></td>
																<td><strong><?php echo $row->user_name; ?></strong></td>
															</tr>
															<tr>
																<td class="pe-3"><?= __("Name:"); ?></td>
																<td><strong><?php echo $row->user_firstname . ' ' . $row->user_lastname; ?></strong></td>
															</tr>
															<tr>
																<td class="pe-3"><?= __("Callsign:"); ?></td>
																<td><strong><?php echo $row->user_callsign; ?></strong></td>
															</tr>
															<tr>
																<td class="pe-3"><?= __("E-Mail:"); ?></td>
																<td><strong><a href="mailto:<?php echo $row->user_email; ?>"><?php echo $row->user_email; ?><a></strong></td>
															</tr>
															<tr>
																<td class="pe-3"><?= __("Last Seen:"); ?></td>
																<td><strong><?php if (isset($row->last_seen)) { echo date($custom_date_format . ' H:i:s', strtotime($row->last_seen)); } else { echo __("never"); }; ?></strong></td>
															</tr>
														</table>
														<?php } else { ?>
														<div class="alert alert-danger" role="alert">
															<?= __("You currently can't impersonate another user. Please change the encryption_key in your config.php file first!"); ?>
														</div>
														<?php } ?>
												</div>
												<div class="modal-footer">
													<form action="<?php echo site_url('user/impersonate'); ?>" method="post" style="display:inline;">
														<input type="hidden" name="hash" value="<?php echo $this->encryption->encrypt($this->session->userdata('user_id') . '/' . $row->user_id . '/' . time()); ?>">
														<button type="submit" class="btn btn-success" <?php if ($has_flossie) { echo 'disabled'; } ?>><?= __("Impersonate") ?></i></button>
													</form>
													<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?= __("Cancel") ?></button>
												</div>
											</div>
										</div>
									</div>
								<?php }
								?>
							</td>
							<?php } ?>
							<td style="text-align: center; vertical-align: middle;">
								<?php
								if ($session_uid != $row->user_id) {
									echo '<a href="' . site_url('user/delete') . '/' . $row->user_id . '" class="btn btn-danger btn-sm"><i class="fas fa-user-minus"></i></a>';
								}
								?></td>
							</td>
							</tr>
						<?php $i++;
						} ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
