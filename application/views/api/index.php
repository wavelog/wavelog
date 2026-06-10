<div class="container">
	<br>
	<?php $this->load->view('layout/messages'); ?>
	<h2><?php echo $page_title; ?></h2>

	<div class="card mb-4">
		<div class="card-header">
			<?= __("API Tokens (API v2)"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("API Tokens grant access to the REST API v2 with granular permissions (scopes). The token is shown only once when it is created, so copy it right away."); ?></p>
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
								<td><i class="fas fa-key"></i> <?php echo htmlspecialchars($row->token_name, ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<?php foreach (explode(',', $row->scopes) as $scope) { ?>
										<span class="badge bg-secondary"><?php echo htmlspecialchars($scope, ENT_QUOTES, 'UTF-8'); ?></span>
									<?php } ?>
								</td>
								<td><?php echo $row->last_used ?? __("Never"); ?></td>
								<td>
									<?php if ($row->expires_at === null) {
										echo '<span class="badge bg-success">' . __("Never") . '</span>';
									} elseif (strtotime($row->expires_at) < time()) {
										echo '<span class="badge bg-danger">' . __("Expired") . '</span> ' . $row->expires_at;
									} else {
										echo $row->expires_at;
									} ?>
								</td>
								<?php if ($clubmode) { ?>
									<td><?php echo $row->user_callsign ?? ''; ?></td>
								<?php } ?>
								<td>
									<?php
										$cfnm_delete_token = sprintf(__("Are you sure you want delete the API Token %s?"), '&quot;' . htmlspecialchars($row->token_name, ENT_QUOTES, 'UTF-8') . '&quot;');
									?>
									<form method="post" action="<?php echo site_url('api_token/delete'); ?>" style="display:inline;">
										<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
										<button type="submit" class="btn btn-danger btn-sm"
											onclick="return confirm('<?php echo $cfnm_delete_token; ?>');">
											<?= __("Delete"); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

			<?php } else { ?>
				<p><?= __("You have no API Tokens."); ?></p>
			<?php } ?>

			<hr>
			<h5><?= __("Create a new API Token"); ?></h5>
			<form method="post" action="<?php echo site_url('api_token/generate'); ?>">
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="tokenName" class="form-label"><?= __("Token Name"); ?></label>
						<input type="text" class="form-control" id="tokenName" name="token_name" maxlength="100" placeholder="<?= __("e.g. My logging tool"); ?>" required>
					</div>
					<div class="col-md-3">
						<label for="tokenExpiry" class="form-label"><?= __("Expiration"); ?></label>
						<select class="form-select" id="tokenExpiry" name="expiry">
							<option value="30"><?= __("30 days"); ?></option>
							<option value="90"><?= __("90 days"); ?></option>
							<option value="365"><?= __("1 year"); ?></option>
							<option value="never"><?= __("Never"); ?></option>
						</select>
					</div>
				</div>
				<div class="mb-3">
					<label class="form-label"><?= __("Scopes"); ?></label>
					<?php
						// Group scopes by their resource prefix ("qso:read" -> "qso")
						// so read/write pairs render indented under one heading.
						$scope_groups = [];
						foreach ($token_scopes as $scope => $label) {
							$scope_groups[strtok($scope, ':')][$scope] = $label;
						}
					?>
					<?php foreach ($scope_groups as $group => $scopes) { ?>
						<div class="mb-2">
							<!-- Group toggle: pure JS convenience, has no name and never submits -->
							<div class="form-check">
								<input class="form-check-input scope-group-toggle" type="checkbox" data-group="<?php echo $group; ?>" id="scopegroup_<?php echo $group; ?>">
								<label class="form-check-label" for="scopegroup_<?php echo $group; ?>">
									<strong><?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></strong>
								</label>
							</div>
							<?php foreach ($scopes as $scope => $label) { ?>
								<div class="form-check ms-4">
									<input class="form-check-input scope-checkbox" type="checkbox" name="scopes[]" value="<?php echo $scope; ?>" id="scope_<?php echo str_replace(':', '_', $scope); ?>">
									<label class="form-check-label" for="scope_<?php echo str_replace(':', '_', $scope); ?>">
										<code><?php echo $scope; ?></code> &mdash; <?php echo $label; ?>
									</label>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
				<button type="submit" class="btn btn-primary">
					<i class="fas fa-plus"></i> <?= __("Create Token"); ?>
				</button>
			</form>

		</div>
	</div>

	<?php if (!empty($new_api_token)) { ?>
		<!-- One-time reveal of a freshly created token; auto-opened by api.js -->
		<div class="modal fade" id="newTokenModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title"><?= __("New API Token"); ?></h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p><span class="badge text-bg-danger"><?= __("Important"); ?></span> <?= __("Copy this token now. For security reasons it will not be shown again."); ?></p>
						<p><code id="newTokenValue"><?php echo htmlspecialchars($new_api_token, ENT_QUOTES, 'UTF-8'); ?></code>
						<span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onclick='copyNewToken("<?php echo htmlspecialchars($new_api_token, ENT_QUOTES, 'UTF-8'); ?>")'><i class="copy-icon fas fa-copy"></i></span></p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="card">
		<div class="card-header">
			<?= __("Legacy API Keys (API v1)"); ?>
		</div>
		<div class="card-body">
			<p class="card-text"><?= __("The Wavelog API (Application Programming Interface) lets third party systems access Wavelog in a controlled way. Access to the API is managed via API keys."); ?> <?= __("New integrations should use the API v2 tokens above."); ?></p>
			<p class="card-text"><?= __("You will need to generate an API key for each tool you wish to use (e.g. WLgate). Generate a read-write key if the application needs to send data to Wavelog. Generate a read-only key if the application only needs to obtain data from Wavelog."); ?></p>
			<p class="card-text"><?= __("Links to 3rd-Party-Software which works with Wavelog:")?><ul>
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
										<form method="post" action="<?php echo site_url('api/delete'); ?>" style="display:inline;">
											<input type="hidden" name="key" value="<?php echo $api_key; ?>">
											<button type="submit" class="btn btn-danger btn-sm"
												onclick="return confirm('<?php echo $cfnm_delete; ?>');">
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

</div>
