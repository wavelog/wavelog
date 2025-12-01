<form method="post" class="col-md">
    <div class="mb-3">
		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="gridsquareaccuracycheck" id="gridsquareaccuracycheck">
			<label class="form-check-label" for="gridsquareaccuracycheck"><?= __("If a QSO has a 4‑char locator (e.g., JO90), try to refine it using callbook data."); ?>
		<i class="fa fa-info-circle" aria-hidden="true" data-bs-toggle="tooltip" title="<?= __("We’ll keep the original value and add a more precise locator (e.g., JO90AB or JO90AB12) when a match is confident."); ?>"></i>
		</label>
		</div>
    </div>
</form>
