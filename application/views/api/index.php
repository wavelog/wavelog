<div class="container">
	<br>
	<?php $this->load->view('layout/messages'); ?>
	<h2><?php echo $page_title; ?></h2>

	<div class="card mb-4">
		<div class="card-header">
			<?= __("Legacy API Keys (API v1)"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("The Wavelog API (Application Programming Interface) lets third party systems access Wavelog in a controlled way. Access to the API is managed via API keys."); ?> <?= __("New integrations should use the API v2 tokens below, if the third party system supports it."); ?></p>
			<p class="card-text"><?= __("You will need to generate an API key for each tool you wish to use (e.g. WLgate). Generate a read-write key if the application needs to send data to Wavelog. Generate a read-only key if the application only needs to obtain data from Wavelog."); ?></p>
			<p class="card-text"><?= __("Links to 3rd-Party-Software which works with Wavelog API v1:")?><ul>
			<li><a href="https://github.com/wavelog/WaveLogGate/releases" target="_thirdparty">WaveLogGate</a></li>
			<li><a href="https://sourceforge.net/projects/wsjt-x-improved/files/" target="_thirdparty">WSJT-X Improved Plus</a></li>
			<li><a href="https://docs.wavelog.org/user-guide/integrations/third-party-tools/" target="_thirdparty"><?=__("More Tools")?></a></li>
			</ul>
			</p>
			<p class="card-text"><span class="badge text-bg-warning"><?= __("API URL"); ?></span> <?= __("The API URL for this Wavelog instance is"); ?>: <span class="api-url" id="apiUrl"><code class="ms-3 me-3"><?php echo site_url(); ?></code></span><span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onClick='copyApiUrl(apiSiteUrl)'><i class="copy-icon fas fa-copy"></i></span></p>
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
									<i class="fas fa-key"></i> <span class="api-key" id="<?php echo html_escape($api_key); ?>"><?php echo html_escape($api_key); ?></span>
									<?php if (!$masked) { ?>
									<span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" data-apikey="<?php echo html_escape($api_key); ?>" onclick="copyApiKey(this.dataset.apikey)"><i class="copy-icon fas fa-copy"></i></span>
									<?php } ?>
								</td>
								<td><?php echo html_escape($row->description ?? ''); ?></td>
								<td><?php echo html_escape($row->last_used ?? ''); ?></td>
								<?php if ($clubmode) { ?>
									<td><?php echo html_escape($row->user_callsign ?? ''); ?></td>
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
								<td><span class="badge rounded-pill text-bg-success"><?php echo html_escape(ucfirst($row->status ?? '')); ?></span></td>
								<td>
									<?php if (!$masked) { ?>
										<a href="<?php echo site_url('api/edit'); ?>/<?php echo html_escape(rawurlencode($api_key)); ?>" class="btn btn-outline-primary btn-sm"><?= __("Edit"); ?></a>

										<a href="<?php echo html_escape(site_url('api/auth/' . rawurlencode($api_key))); ?>" target="_blank" class="btn btn-primary btn-sm"><?= __("Test"); ?></a>

										<?php
											$cfnm_delete = sprintf(__("Are you sure you want delete the API Key %s?"), '"' . ($row->description ?: __("<noname>")) . '"');
										?>
										<form method="post" action="<?php echo site_url('api/delete'); ?>" style="display:inline;">
											<input type="hidden" name="key" value="<?php echo html_escape($api_key); ?>">
											<button type="submit" class="btn btn-danger btn-sm" data-confirm="<?php echo html_escape($cfnm_delete); ?>">
												<?= __("Delete"); ?>
											</button>
										</form>
									<?php } ?>
								</td>

							</tr>

						<?php } ?>

				</table>

			<?php } else { ?>
				<p><?= __("You have no API Keys."); ?></p>
			<?php } ?>

			<p>
				<form method="post" action="<?php echo site_url('api/generate'); ?>" style="display:inline;">
					<input type="hidden" name="rights" value="rw">
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-plus"></i> <?= __("Create a read & write key"); ?>
					</button>
				</form>
				<form method="post" action="<?php echo site_url('api/generate'); ?>" style="display:inline;">
					<input type="hidden" name="rights" value="r">
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-plus"></i> <?= __("Create a read-only key"); ?>
					</button>
				</form>
			</p>

		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<?= __("API Tokens (API v2)"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("API Tokens grant access to the REST API v2 with granular permissions (scopes). The token is shown only once when it is created, so copy it right away."); ?></p>
			<p class="card-text"><?= sprintf(__("The API v2 documentation can be found %shere%s."), '<a target="_blank" href="https://docs.wavelog.org/developer/api-v2/">', '</a>'); ?></p>
			<p class="card-text"><span class="badge text-bg-warning"><?= __("API URL"); ?></span> <?= __("The API v2 base URL for this Wavelog instance is"); ?>: <span class="api-url" id="apiV2Url"><code class="ms-3 me-3"><?php echo site_url('api/v2'); ?></code></span><span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onClick='copyApiV2Url("<?php echo site_url('api/v2'); ?>")'><i class="copy-icon fas fa-copy"></i></span></p>
			<?php if ($clubmode) { ?>
				<p class="card-text"><span class="badge text-bg-danger"><?= __("Important"); ?></span> <?= __("On Clubstations the API Tokens are personal and not shared. Clubstation users can only see their own tokens."); ?></p>
			<?php } ?>

			<?php if ($api_tokens->num_rows() > 0) { ?>

				<table class="table table-striped">
					<thead>
						<tr>
							<th scope="col"><?= __("Name"); ?></th>
							<th scope="col"><?= __("Scopes"); ?></th>
							<th scope="col"><?= __("Last Used"); ?></th>
							<th scope="col"><?= __("Expires"); ?></th>
							<?php if ($clubmode) { ?>
								<th scope="col"><?= __("Created By"); ?></th>
							<?php } ?>
							<th scope="col"><?= __("Actions"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($api_tokens->result() as $row) { ?>
							<tr>
								<td><i class="fas fa-key"></i> <?php echo html_escape($row->token_name); ?></td>
								<td>
									<?php foreach (explode(',', $row->scopes) as $scope) { ?>
										<span class="badge bg-secondary"><?php echo html_escape($scope); ?></span>
									<?php } ?>
								</td>
								<td><?php echo html_escape($row->last_used ?? __("Never")); ?></td>
								<td>
									<?php if ($row->expires_at === null) {
										echo '<span class="badge bg-success">' . __("Never") . '</span>';
									} elseif (strtotime($row->expires_at) < time()) {
										echo '<span class="badge bg-danger">' . __("Expired") . '</span> ' . html_escape($row->expires_at);
									} else {
										echo html_escape($row->expires_at);
									} ?>
								</td>
								<?php if ($clubmode) { ?>
									<td><?php echo html_escape($row->user_callsign ?? ''); ?></td>
								<?php } ?>
								<td>
									<?php
										$cfnm_delete_token = sprintf(__("Are you sure you want delete the API Token %s?"), '"' . $row->token_name . '"');
									?>
									<form method="post" action="<?php echo site_url('api_token/delete'); ?>" style="display:inline;">
										<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
										<button type="submit" class="btn btn-danger btn-sm" data-confirm="<?php echo html_escape($cfnm_delete_token); ?>">
											<?= __("Delete"); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

			<?php } else { ?>
				<p class="alert alert-secondary"><?= __("You have no API Tokens yet. Click on the button below to create one."); ?></p>
			<?php } ?>

			<hr>
			<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTokenModal">
				<i class="fas fa-plus"></i> <?= __("Create a new API Token"); ?>
			</button>

		</div>
	</div>

	<?php $this->load->view('api/components/create_token_modal'); ?>

	<?php if (!empty($new_api_token)) { ?>
		<?php $this->load->view('api/components/new_token_modal'); ?>
	<?php } ?>

</div>
