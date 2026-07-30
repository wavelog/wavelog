<div class="modal fade bg-black bg-opacity-50" id="createTokenModal" tabindex="-1" aria-labelledby="createTokenLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header border-bottom d-flex justify-content-between align-items-center">
				<h5 class="modal-title" id="createTokenLabel"><i class="fas fa-plus-circle me-2"></i><?= __("Create a new API Token"); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __("Close"); ?>" style="flex-shrink: 0;"></button>
			</div>
			<form method="post" action="<?php echo site_url('api_token/generate'); ?>" id="createTokenForm">
				<div class="modal-body">
					<p class="text-muted mb-4"><?= __("Create a token with granular permissions (scopes). The token is shown only once when it is created, so copy it right away."); ?></p>
					<div class="row g-3 mb-4">
						<div class="col-md-7">
							<label for="tokenName" class="form-label"><?= __("Token Name"); ?> <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="tokenName" name="token_name" maxlength="100" placeholder="<?= __("e.g. My logging tool"); ?>" required>
							<small class="text-muted d-block mt-2"><?= __("A name to help you recognise this token later."); ?></small>
						</div>
						<div class="col-md-5">
							<label for="tokenExpiry" class="form-label"><?= __("Expiration"); ?></label>
							<select class="form-select" id="tokenExpiry" name="expiry">
								<option value="30"><?= __("30 days"); ?></option>
								<option value="90"><?= __("90 days"); ?></option>
								<option value="365"><?= __("1 year"); ?></option>
								<option value="never"><?= __("Never"); ?></option>
							</select>
							<small class="text-muted d-block mt-2"><?= __("When the token should stop working."); ?></small>
							<div id="expiry-never-warning" class="text-warning small mt-2" style="display:none;">
								<i class="fas fa-exclamation-triangle me-1"></i><?= __("A token that never expires stays valid until you delete it manually. This is possible, but not recommended for security reasons."); ?>
							</div>
						</div>
					</div>
					<hr class="my-4">
					<div class="row g-4 mb-2">
						<div class="col-md-7">
							<label class="form-label"><?= __("Scopes"); ?> <span class="text-danger">*</span></label>
							<small class="text-muted d-block mb-2"><?= __("Select which parts of the API v2 this token may access."); ?></small>
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
							<div id="scopes-error" class="text-danger small mt-1" style="display:none;"><?= __("Please select at least one scope."); ?></div>
						</div>
						<?php if (!empty($token_presets)) { ?>
							<div class="col-md-5">
								<label class="form-label"><?= __("Presets"); ?></label>
								<small class="text-muted d-block mb-2"><?= __("Don't know which scopes you need? Pick the tool you want to use and we select the scopes for you."); ?></small>
								<div class="d-grid gap-2">
									<?php foreach ($token_presets as $key => $preset) { ?>
										<!-- Preset: only ticks checkboxes, the form still submits the individual scopes -->
										<button type="button" class="btn <?php echo html_escape($preset['class']); ?> text-start scope-preset" id="preset_<?php echo html_escape($key); ?>" data-scopes="<?php echo html_escape(implode(',', $preset['scopes'])); ?>">
											<i class="<?php echo html_escape($preset['icon']); ?> me-2"></i><strong><?php echo $preset['name']; ?></strong>
											<small class="d-block mt-1"><?php echo $preset['description']; ?></small>
										</button>
									<?php } ?>
									<button type="button" class="btn btn-secondary text-start" id="resetScopes">
										<i class="fas fa-eraser me-2"></i><strong><?= __("Reset"); ?></strong>
									</button>
								</div>
								<small class="text-muted d-block mt-2"><?= __("A preset replaces your current selection. You can still adjust the scopes afterwards."); ?></small>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="modal-footer border-top">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
					<button type="submit" class="btn btn-success">
						<i class="fas fa-check-circle me-2"></i><?= __("Create Token"); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
