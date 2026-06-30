<form>
	<div class="row g-2">
		<div class="col-6">
			<?php for ($i = 1; $i <= 5; $i++): ?>
			<div class="mb-2">
				<div class="input-group input-group-sm">
					<span class="input-group-text fw-bold" style="width:2.8rem">F<?= $i ?></span>
					<input name="function<?= $i ?>_name" id="function<?= $i ?>_name" type="text"
						class="form-control" style="max-width:5.5rem" maxlength="6"
						value="<?= htmlspecialchars(${'macro' . $i}['name'] ?? '') ?>"
						placeholder="<?= __('Name') ?>">
					<input name="function<?= $i ?>_macro" id="function<?= $i ?>_macro" type="text"
						class="form-control"
						value="<?= htmlspecialchars(${'macro' . $i}['macro'] ?? '') ?>"
						placeholder="<?= __('Macro') ?>">
				</div>
			</div>
			<?php endfor; ?>
		</div>
		<div class="col-6">
			<?php for ($i = 6; $i <= 10; $i++): ?>
			<div class="mb-2">
				<div class="input-group input-group-sm">
					<span class="input-group-text fw-bold" style="width:2.8rem">F<?= $i ?></span>
					<input name="function<?= $i ?>_name" id="function<?= $i ?>_name" type="text"
						class="form-control" style="max-width:5.5rem" maxlength="6"
						value="<?= htmlspecialchars(${'macro' . $i}['name'] ?? '') ?>"
						placeholder="<?= __('Name') ?>">
					<input name="function<?= $i ?>_macro" id="function<?= $i ?>_macro" type="text"
						class="form-control"
						value="<?= htmlspecialchars(${'macro' . $i}['macro'] ?? '') ?>"
						placeholder="<?= __('Macro') ?>">
				</div>
			</div>
			<?php endfor; ?>
		</div>
	</div>
	<div class="mt-2 text-muted" style="font-size:0.8rem">
		<?= __('Tokens') ?>: <code>[MYCALL]</code> <code>[CALL]</code> <code>[RST_S]</code> <code>[RST_R]</code><?php if ($contest_context ?? false): ?> <code>[SERIAL_S]</code> <code>[SERIAL_R]</code> <code>[EXCHANGE_S]</code> <code>[EXCHANGE_R]</code> <code>[GRID_S]</code> <code>[GRID_R]</code><?php endif; ?>
	</div>

	<?php if ($contest_context ?? false): ?>
	<hr class="my-2">
	<div class="form-check form-switch mb-2">
		<input class="form-check-input" type="checkbox" id="esm_enabled" name="esm_enabled" value="1" <?= !empty($esm['enabled']) ? 'checked' : '' ?>>
		<label class="form-check-label fw-bold" for="esm_enabled"><?= __("ESM (Enter Sends Message)") ?></label>
	</div>
	<div class="text-muted mb-2" style="font-size:0.8rem">
		<?= __("When enabled, the Enter key drives the QSO: empty call sends CQ, an unclear call (with \"?\") asks again, a full call without exchange sends the report, and a complete QSO is logged with a TU. In S&P mode the first Enter sends only your own call, and logging sends your report instead of a TU. Alt+Enter logs without sending.") ?>
	</div>
	<div class="row g-2">
		<?php
		$esm_actions = [
			'esm_cq'       => _pgettext("ESM mode", "Run") . ' / CQ',
			'esm_qrz'      => __("Unclear call (?)"),
			'esm_exchange' => __("Exchange / Report"),
			'esm_tu'       => __("Log QSO / TU"),
			'esm_sp'       => _pgettext("ESM mode", "S&P") . ' / ' . __("own call"),
			'esm_sp_exch'  => _pgettext("ESM mode", "S&P") . ' / ' . __("exchange on log"),
		];
		foreach ($esm_actions as $esm_key => $esm_label):
			$esm_selected = (int) ($esm[str_replace('esm_', '', $esm_key)] ?? 0);
		?>
		<div class="col-6">
			<div class="input-group input-group-sm">
				<span class="input-group-text" style="min-width:9rem"><?= $esm_label ?></span>
				<select name="<?= $esm_key ?>" id="<?= $esm_key ?>" class="form-select">
					<?php for ($f = 1; $f <= 10; $f++): ?>
					<option value="<?= $f ?>" <?= $esm_selected === $f ? 'selected' : '' ?>>F<?= $f ?></option>
					<?php endfor; ?>
				</select>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<hr class="my-2">
	<div class="fw-bold mb-1" style="font-size:0.85rem"><?= __("Keyboard shortcuts") ?></div>
	<div class="text-muted" style="font-size:0.8rem">
		<?php
		$esm_shortcuts = [
			'F1 - F10'         => __("Send the corresponding CW macro"),
			'Enter'            => __("ESM on: drive the QSO (CQ / report / log); ESM off: log the QSO"),
			'Alt + Enter'      => __("Log the QSO without sending anything"),
			'Esc'              => __("Clear the entry form"),
			'Space'       	   => __("In the callsign field: jump to the next empty field"),
		];
		foreach ($esm_shortcuts as $esm_keys => $esm_desc): ?>
		<div class="d-flex gap-2 mb-1">
			<span style="min-width:6rem"><kbd><?= $esm_keys ?></kbd></span>
			<span><?= $esm_desc ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</form>
