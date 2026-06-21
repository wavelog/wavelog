<div class="container mt-3">
    <h2><?= __("Print QSL Postcards") ?></h2>

	<?php if (!empty($templates)) { ?>

		<div class="card p-3">
			<div class="mb-3">
				<label class="form-label"><?= __("Template") ?></label>
				<select id="template_id" class="form-control">
					<?php foreach ($templates as $t): ?>
						<option value="<?= (int)$t['id'] ?>">
							<?= htmlentities($t['name']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="mb-3 small text-muted">
				<?= __("Print options (QSOs per card, background image, address printing) are set per template in the QSL Postcard Designer."); ?>
			</div>
			<button id="btnPrintQueue" class="btn btn-success w-auto">
				<?= __("Generate Postcard PDF") ?>
			</button>
		</div>

		<?php } else { ?>
			<div class="alert alert-info">
				<?= __("No templates available for printing. Go to the QSL Postcard Designer and create a template first.") ?>
			</div>
		<?php } ?>
</div>

<script>
    document.getElementById('btnPrintQueue').addEventListener('click', () => {
        const tpl = document.getElementById('template_id').value;
        // Print options come from the template's layout.options, not this form.
        window.open(`<?= site_url('qslpostcard/pdfqueue') ?>/${tpl}`, '_blank');
    });
</script>
