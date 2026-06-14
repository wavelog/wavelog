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
</form>
