<script>
	var lang_admin_confirm_pwd_reset = "<?php echo lang('admin_confirm_pwd_reset'); ?>";
	var lang_admin_user = "<?php echo lang('admin_user'); ?>";
	var lang_gen_hamradio_callsign = "<?php echo lang('gen_hamradio_callsign'); ?>";

	var lang_general_word_please_wait = "<?php echo lang ('general_word_please_wait'); ?>"

	var lang_admin_email_settings_incorrect = "<?php echo lang('admin_email_settings_incorrect'); ?>";
	var lang_admin_password_reset_processed = "<?php echo lang('admin_password_reset_processed'); ?>";
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
			<?php echo lang('admin_user_list'); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?php echo lang('admin_user_line1'); ?></p>
			<p class="card-text"><?php echo lang('admin_user_line2'); ?></p>
			<p class="card-text"><?php echo lang('admin_user_line3'); ?></p>
			<p class="card-text"><?php echo lang('admin_user_line4'); ?></p>
			<p><a class="btn btn-primary" href="<?php echo site_url('user/add'); ?>"><i class="fas fa-user-plus"></i> <?php echo lang('admin_create_user'); ?></a></p>

			<div class="table-responsive">
				<table class="table table-striped">
					<thead>
						<tr>
							<th scope="col"><?php echo lang('admin_user'); ?></th>
							<th scope="col"><?php echo lang('gen_hamradio_callsign'); ?></th>
							<th scope="col"><?php echo lang('admin_email'); ?></th>
							<th scope="col"><?php echo lang('admin_type'); ?></th>
							<th scope="col"><?php echo lang('admin_last_seen'); echo " <a href=" . site_url('user') . " data-bs-toggle=\"tooltip\" title=\"Refresh\"  class=\"btn btn-link btn-sm ms-0.5\"><i class=\"fas fa-sync\"></i></a>"; ?></th>
							<th></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('admin_edit'); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('admin_password_reset'); ?></th>
							<th style="text-align: center; vertical-align: middle;" scope="col"><?php echo lang('admin_delete'); ?></th>
						</tr>
					</thead>
					<tbody>

						<?php

						$i = 0;
						foreach ($results->result() as $row) { ?>
							<?php echo '<tr class="tr' . ($i & 1) . '">'; ?>
							<td style="text-align: left; vertical-align: middle;"><a href="<?php echo site_url('user/edit') . "/" . $row->user_id; ?>"><?php echo $row->user_name; ?></a></td>
							<td style="text-align: left; vertical-align: middle;"><?php echo $row->user_callsign; ?></td>
							<td style="text-align: left; vertical-align: middle;"><?php echo $row->user_email; ?></td>
							<td style="text-align: left; vertical-align: middle;"><?php $l = $this->config->item('auth_level');
								echo $l[$row->user_type]; ?></td>
							<td style="text-align: left; vertical-align: middle;"><?php 
								if ($row->last_seen != null) { // if the user never logged in before the value is null. We can show "never" then.
									$lastSeenTimestamp = strtotime($row->last_seen);
									$currentTimestamp = time();
									if (($currentTimestamp - $lastSeenTimestamp) < 120) {
										echo "<a><i style=\"color: green;\" class=\"fas fa-circle\"></i> " . $row->last_seen . "</a>";
									} else {
										echo "<a><i style=\"color: red;\" class=\"fas fa-circle\"></i> " . $row->last_seen . "</a>";
									}
								} else {
									echo lang('general_word_never');
								}?>
							</td>
							<td style="text-align: left; vertical-align: middle;">
								<span class="badge text-bg-success"><?php echo lang('admin_station_locations'); ?>: <?php echo $row->stationcount; ?></span>
								<br>
								<span class="badge text-bg-info"><?php echo lang('admin_station_logbooks'); ?>: <?php echo $row->logbookcount; ?></span>
								<?php if ($row->qsocount > 0) { ?>
									<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?php echo lang('admin_last_qso'); ?><br><?php echo $row->lastqso; ?>"><?php echo $row->qsocount; ?> <?php echo lang('gen_hamradio_qso'); ?></span>
								<?php } else { ?>
									<span class="badge text-bg-light" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?php echo lang('admin_no_qso_in_log'); ?>"><?php echo $row->qsocount; ?> <?php echo lang('gen_hamradio_qso'); ?></span>
								<?php } ?>
							</td>
							<td style="text-align: center; vertical-align: middle;"><a href="<?php echo site_url('user/edit') . "/" . $row->user_id; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-edit"></i></a>
							<td style="text-align: center; vertical-align: middle;">
								<?php
								if ($_SESSION['user_id'] != $row->user_id) {
									echo '<a class="btn btn-primary btn-sm ms-1 admin_pwd_reset" data-username="' . $row->user_name . '" data-callsign="' . $row->user_callsign . '" data-userid="' . $row->user_id . '" data-usermail="' . $row->user_email . '"><i class="fas fa-key"></i></a>';
								}
								?></td>
							<td style="text-align: center; vertical-align: middle;">
								<?php
								if ($_SESSION['user_id'] != $row->user_id) {
									echo "<a href=" . site_url('user/delete') . "/" . $row->user_id . " class=\"btn btn-danger btn-sm\"><i class=\"fas fa-user-minus\"></i></a>";
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
