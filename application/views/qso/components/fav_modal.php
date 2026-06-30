<div class="modal fade" id="fav_modal" tabindex="-1" aria-labelledby="fav_modal_label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="fav_modal_label"><i class="fas fa-star me-2"></i><?= __("Save Favourite"); ?></h5>
			</div>
			<div class="modal-body">
				<label for="fav_name_input" class="form-label"><?= __("Name"); ?></label>
				<input type="text" class="form-control" id="fav_name_input" maxlength="100" autocomplete="off" placeholder="<?= __("e.g. 20m SSB, SO-50, portable setup"); ?>">
				<div id="fav_name_error" class="text-danger small mt-2" style="display:none;"><?= __("Please enter a name."); ?></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
				<button type="button" class="btn btn-success" id="fav_modal_save"><i class="fas fa-check-circle me-1"></i><?= __("Save"); ?></button>
			</div>
		</div>
	</div>
</div>
