<div class="row g-4 mb-3">
	<div class="col-12 col-md-5 col-lg-4">
		<form onsubmit="calculateQrb(); return false;" class="card card-body shadow-sm h-100">
			<div class="mb-1">
				<label for="qrbcalc_locator1" class="form-label small text-uppercase fw-semibold text-body-secondary mb-1 ps-1"><?= __("Locator 1"); ?></label>
				<input class="form-control form-control-sm qrb-input uppercase font-monospace text-center" id="qrbcalc_locator1" type="text" name="locator1" placeholder="JO01AB" value="<?php if ($station_locator != "0") echo $station_locator; ?>" aria-label="<?= __("Locator 1"); ?>" autocomplete="off" spellcheck="false">
			</div>
			<div class="mb-3">
				<label for="qrbcalc_locator2" class="form-label small text-uppercase fw-semibold text-body-secondary mb-1 ps-1"><?= __("Locator 2"); ?></label>
				<input class="form-control form-control-sm qrb-input uppercase font-monospace text-center" id="qrbcalc_locator2" type="text" name="locator2" placeholder="JO02CD" aria-label="<?= __("Locator 2"); ?>" autocomplete="off" spellcheck="false">
			</div>
			<div class="d-flex gap-2">
				<button type="button" class="btn btn-outline-primary btn-sm qrb-btn" onclick="resetQrbForm(this.form)"><i class="fas fa-undo me-1"></i><?= __("Reset"); ?></button>
				<button type="submit" class="btn btn-primary btn-sm qrb-btn flex-grow-1"><i class="fas fa-calculator me-1"></i><?= __("Calculate"); ?></button>
			</div>
		</form>
	</div>
	<div class="col-12 col-md-7 col-lg-8">
		<div class="card shadow-sm h-100 overflow-hidden">
			<div class="qrbResult h-100 d-flex flex-column"></div>
		</div>
	</div>
</div>
<div id="mapqrb"><div id="mapqrbcontainer"></div></div>
