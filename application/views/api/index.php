<div class="container">
	<br>
	<?php $this->load->view('layout/messages'); ?>
	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("API Keys"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("The Wavelog API (Application Programming Interface) lets third party systems access Wavelog in a controlled way. Access to the API is managed via API keys."); ?></p>
			<p class="card-text"><?= __("You will need to generate an API key for each tool you wish to use (e.g. WLgate). Generate a read-write key if the application needs to send data to Wavelog. Generate a read-only key if the application only needs to obtain data from Wavelog."); ?></p>
			<p class="card-text"><span class="badge text-bg-warning"><?= __("API URL"); ?></span> <?= __("The API URL for this Wavelog instance is"); ?>: <span class="api-url" id="apiUrl"><code class="ms-3 me-3"><?php echo base_url(); ?></code></span><span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onClick='copyApiUrl()'><i class="copy-icon fas fa-copy"></i></span></p>
			<p class="card-text"><span class="badge text-bg-info"><?= __("Info"); ?></span> <?= __("It's good practice to delete a key if you are no longer using the associated application."); ?></p>
			<?php if ($clubmode) { ?>
				<p class="card-text"><span class="badge text-bg-danger"><?= __("Important"); ?></span> <?= __("On Clubstations the API Keys are personal and not shared. Clubstation users can only see their own keys."); ?></p>
			<?php } ?>

			<?php if ($api_keys->num_rows() > 0) { ?>

				<table class="table table-striped">
					<thead>
						<tr>
							<th scope="col"><?= __("API Key"); ?></th>
							<th scope="col"><?= __("Description"); ?></th>
							<th scope="col"><?= __("Last Used"); ?></th>
							<?php if ($clubmode) { ?>
								<th scope="col"><?= __("Created By"); ?></th>
							<?php } ?>
							<th scope="col"><?= __("Permissions"); ?></th>
							<th scope="col"><?= __("Status"); ?></th>
							<th scope="col"><?= __("Actions"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($api_keys->result() as $row) { ?>
							<tr>
								<?php if ($clubmode && $row->user_callsign !== $this->session->userdata('cd_src_call')) { 
									$api_key = substr($row->key, 0, 2) . str_repeat('*', strlen($row->key) - 6) . substr($row->key, -4);
									$masked = true;
								} else {
									$api_key = $row->key;
									$masked = false;
								} ?>
								<td>
									<i class="fas fa-key"></i> <span class="api-key" id="<?php echo $api_key; ?>"><?php echo $api_key; ?></span>
									<?php if (!$masked) { ?>
									<span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onclick='copyApiKey("<?php echo $api_key; ?>")'><i class="copy-icon fas fa-copy"></i></span>
									<?php } ?>
								</td>
								<td><?php echo $row->description; ?></td>
								<td><?php echo $row->last_used; ?></td>
								<?php if ($clubmode) { ?>
									<td><?php echo $row->user_callsign; ?></td>
								<?php } ?>
								<td>
									<?php if ($row->rights == "rw") {
										echo "<span class=\"badge bg-warning\">" . __("Read & Write") . "</span>";
									} elseif ($row->rights == "r") {
										echo "<span class=\"badge bg-success\">" . __("Read-Only") . "</span>";
									} else {
										echo "<span class=\"badge bg-dark\">" . __("Unknown") . "</span>";
									} ?>
								</td>
								<td><span class="badge rounded-pill text-bg-success"><?php echo ucfirst($row->status); ?></span></td>
								<td>
									<?php if (!$masked) { ?>
										<a href="<?php echo site_url('api/edit'); ?>/<?php echo $api_key; ?>" class="btn btn-outline-primary btn-sm"><?= __("Edit"); ?></a>
										
										<a href="<?php echo site_url('api/auth/' . $api_key); ?>" target="_blank" class="btn btn-primary btn-sm"><?= __("Test"); ?></a>
										
										<?php 
											$cfnm_delete = sprintf(__("Are you sure you want delete the API Key %s?"), '&quot;'.($row->description ?? '<noname>').'&quot;');
										?>
										<a href="<?php echo site_url('api/delete/' . $api_key); ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo $cfnm_delete; ?>');"><?= __("Delete"); ?></a>
									<?php } ?>
								</td>

							</tr>

						<?php } ?>

				</table>

			<?php } else { ?>
				<p><?= __("You have no API Keys."); ?></p>
			<?php } ?>

			<p>
				<a href="<?php echo site_url('api/generate/rw'); ?>" class="btn btn-primary "><i class="fas fa-plus"></i> <?= __("Create a read & write key"); ?></a>
				<a href="<?php echo site_url('api/generate/r'); ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __("Create a read-only key"); ?></a>
			</p>

		</div>
	</div>

</div>