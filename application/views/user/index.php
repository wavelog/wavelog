<div class="container">

	<br>

	<h2><?php echo $page_title; ?></h2>

	<?php $this->load->view('layout/messages'); ?>

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
							<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Actions"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $i = 0;
						foreach ($results->result() as $row) { ?>
							<tr class="tr<?php echo ($i & 1); ?>">
								<td style="text-align: left; vertical-align: middle;">
									<a href="<?php echo site_url('user/edit') . '/' . $row->user_id; ?>">
										<?php echo $row->user_name; ?>
									</a>
								</td>
								<td style="text-align: left; vertical-align: middle;">
									<?php echo str_replace("0", "&Oslash;", $row->user_callsign); ?>
								</td>
								<td style="text-align: left; vertical-align: middle;">
									<a href="mailto:<?php echo $row->user_email; ?>">
										<?php echo $row->user_email; ?>
									</a>
								</td>
								<td style="text-align: left; vertical-align: middle;">
									<?php
									$l = $this->config->item('auth_level');
									echo $l[$row->user_type];
									?>
								</td>
								<td style="text-align: left; vertical-align: middle;">
									<?php if ($row->last_seen != null) {
										$lastSeenTimestamp = strtotime($row->last_seen);
										$currentTimestamp = time();
										if (($currentTimestamp - $lastSeenTimestamp) < 120) { ?>
											<a><i style="color: green;" class="fas fa-circle"></i> <?php echo date($custom_date_format . ' H:i:s', $lastSeenTimestamp); ?></a>
										<?php } else { ?>
											<a><i style="color: red;" class="fas fa-circle"></i> <?php echo date($custom_date_format . ' H:i:s', $lastSeenTimestamp); ?></a>
										<?php }
									} else {
										echo __("Never");
									} ?>
								</td>
								<td style="text-align: left; vertical-align: middle;">
									<span class="badge text-bg-success"><?= __("Locations"); ?>: <?php echo $row->stationcount; ?></span>
									<br>
									<span class="badge text-bg-info"><?= __("Logbooks"); ?>: <?php echo $row->logbookcount; ?></span>
									<?php if ($row->qsocount > 0) { ?>
										<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Last QSO:"); ?><br><?php echo $row->lastqso; ?>">
											<?php echo $row->qsocount; ?> <?= __("QSO"); ?>
										</span>
									<?php } else { ?>
										<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("No QSOs in Log"); ?>">
											<?php echo $row->qsocount; ?> <?= __("QSO"); ?>
										</span>
									<?php } ?>
								</td>

								<!-- ### Actions ### -->
								<td style="text-align: center; vertical-align: middle;">

									<!-- Edit Button -->
									<a href="<?php echo site_url('user/edit') . '/' . $row->user_id; ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Edit"); ?>">
										<i class="fas fa-user-edit"></i>
									</a>

									<!-- Impersonate Button -->
									<?php if (!$disable_impersonate && $session_uid != $row->user_id) { ?>
										<button class="btn btn-info btn-sm btn-tooltip" onclick="actions_modal('<?php echo $row->user_id; ?>', 'admin_impersonate')" title="<?= __("Impersonate"); ?>">
											<i class="fas fa-people-arrows"></i>
										</button>
									<?php } ?>

									<!-- Other Actions Button -->
									<?php if ($session_uid != $row->user_id) { ?>
										<button class="btn btn-secondary btn-sm btn-tooltip" onclick="actions_modal('<?php echo $row->user_id; ?>', 'more_actions')" title="<?= __("Other Actions"); ?>">
											<i class="fas fa-bars"></i>
										</button>
									<?php } ?>
									
									
									<!-- Delete Button -->
									<?php if ($session_uid != $row->user_id) { ?>
										<div class="vr mx-2"></div>
										<a href="<?php echo site_url('user/delete') . '/' . $row->user_id; ?>" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Delete"); ?>">
											<i class="fas fa-user-minus"></i>
										</a>
									<?php } ?>

									<!-- End Actions -->
								</td>
							</tr>
						<?php $i++; } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>


	<?php if ($clubmode) { ?>
		<div class="card mt-3">
			<div class="card-header">
				<?= __("Clubstation List"); ?>
			</div>
			<div class="card-body">
				<p class="card-text"><?= __("Clubstations in Wavelog are a unique way for clubs and special callsign events to handle multiple operators. A clubstation is basically a normal user account with some special features and some restrictions."); ?></p>
				<p>
					<a class="btn btn-primary" href="<?php echo site_url('user/add?club=1'); ?>"><i class="fas fa-user-plus"></i> <?= __("Create Clubstation"); ?></a>
					<a class="btn btn-secondary" style="float: right;" href="<?php echo site_url('user'); ?>"><i class="fas fa-sync"></i> <?= __("Refresh List"); ?></a>
				</p>

				<?php if (!empty($clubs->result())) { ?>
				<div class="table-responsive">
					<table class="table table-striped" id="adminclubusertable">
						<thead>
							<tr>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Username"); ?></th>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Callsign"); ?></th>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("E-mail"); ?></th>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Last Operator"); ?></th>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Last seen"); ?></th>
								<th></th>
								<th style="text-align: center; vertical-align: middle;" scope="col"><?= __("Actions"); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 0;
							foreach ($clubs->result() as $row) { ?>
								<?php echo '<tr class="tr' . ($i & 1) . '">'; ?>
								<td style="text-align: left; vertical-align: middle;"><a href="<?php echo site_url('user/edit') . "/" . $row->user_id; ?>"><?php echo $row->user_name; ?></a></td>
								<td style="text-align: left; vertical-align: middle;"><?php echo str_replace("0", "&Oslash;", $row->user_callsign); ?></td>
								<td style="text-align: left; vertical-align: middle;"><a href="mailto:<?php echo $row->user_email; ?>"><?php echo $row->user_email; ?></a></td>
								<td style="text-align: left; vertical-align: middle;"><?php echo $row->lastoperator; ?></td>
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
								<!-- ### Actions ### -->
								<td style="text-align: center; vertical-align: middle;">

									<!-- Edit Button -->
									<a href="<?php echo site_url('user/edit') . '/' . $row->user_id; ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Edit"); ?>">
										<i class="fas fa-user-edit"></i>
									</a>

									<!-- Impersonate Button -->
									<?php if (!$disable_impersonate && $session_uid != $row->user_id) { ?>
										<button class="btn btn-info btn-sm btn-tooltip" onclick="actions_modal('<?php echo $row->user_id; ?>', 'admin_impersonate')" title="<?= __("Impersonate"); ?>">
											<i class="fas fa-people-arrows"></i>
										</button>
									<?php } ?>

									<!-- Club Permissions Button -->
									<a href="<?php echo site_url('club/permissions') . "/" . $row->user_id; ?>" class="btn btn-warning btn-sm btn-tooltip" title="<?= __("Club Permissions"); ?>"><i style="color: black;" class="fas fa-user-lock"></i></a>

									<!-- Other Actions Button -->
									<?php if ($session_uid != $row->user_id) { ?>
										<button class="btn btn-secondary btn-sm btn-tooltip" onclick="actions_modal('<?php echo $row->user_id; ?>', 'more_actions')" title="<?= __("Other Actions"); ?>">
											<i class="fas fa-bars"></i>
										</button>
									<?php } ?>
									
									
									<!-- Delete Button -->
									<?php if ($session_uid != $row->user_id) { ?>
										<div class="vr mx-2"></div>
										<a href="<?php echo site_url('user/delete') . '/' . $row->user_id; ?>" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Delete"); ?>">
											<i class="fas fa-user-minus"></i>
										</a>
									<?php } ?>

									<!-- End Actions -->
								</td>
							</tr>
							<?php $i++;
							} ?>
						</tbody>
					</table>
				</div>
				<?php } else { ?>
					<div class="text-center">
						<h5><?= __("No Clubstations configures yet."); ?></h5>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php } ?>
		</div>
	</div>
	<div id="actionsModal-container"></div>
</div>
