<!-- One-time reveal of a freshly created token; auto-opened by api.js -->
<div class="modal fade bg-black bg-opacity-50" id="newTokenModal" tabindex="-1" aria-labelledby="newTokenLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header border-bottom d-flex justify-content-between align-items-center">
				<h5 class="modal-title" id="newTokenLabel"><i class="fas fa-key me-2"></i><?= __("New API Token"); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __("Close"); ?>" style="flex-shrink: 0;"></button>
			</div>
			<div class="modal-body">
				<div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
					<i class="fas fa-triangle-exclamation"></i>
					<div><?= __("Copy this token now. For security reasons it will not be shown again."); ?></div>
				</div>
				<label class="form-label"><?= __("Your API Token"); ?></label>
				<div class="input-group">
					<input type="text" class="form-control font-monospace" id="newTokenValue" value="<?php echo htmlspecialchars($new_api_token, ENT_QUOTES, 'UTF-8'); ?>" readonly onclick="this.select();">
					<button type="button" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onclick='copyNewToken("<?php echo htmlspecialchars($new_api_token, ENT_QUOTES, 'UTF-8'); ?>")'>
						<i class="fas fa-copy"></i>
					</button>
				</div>
			</div>
			<div class="modal-footer border-top">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
			</div>
		</div>
	</div>
</div>
