<form>
	<?php for ($i = 1; $i <= 10; $i++): ?>
		<div class="mb-3 row">
			<label for="function<?= $i; ?>_name" class="col-sm-5 col-form-label">
				<?= sprintf(__("Function %d - Name"), $i); ?>
			</label>
			<div class="col-sm-7">
				<input
					name="function<?= $i; ?>_name"
					type="text"
					class="form-control"
					id="function<?= $i; ?>_name"
					maxlength="6"
					value="<?php echo ${'macro' . $i}['name'] ?? ''; ?>"
				>
			</div>
		</div>

		<div class="mb-3 row">
			<label for="function<?= $i; ?>_macro" class="col-sm-5 col-form-label">
				<?= sprintf(__("Function %d - Macro"), $i); ?>
			</label>
			<div class="col-sm-7">
				<input
					name="function<?= $i; ?>_macro"
					type="text"
					class="form-control"
					id="function<?= $i; ?>_macro"
					value="<?php echo ${'macro' . $i}['macro'] ?? ''; ?>"
				>
			</div>
		</div>

		<?php if ($i < 10): ?>
			<hr>
		<?php endif; ?>
	<?php endfor; ?>
</form>
