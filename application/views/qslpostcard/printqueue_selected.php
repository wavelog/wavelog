<div class="container mt-3">
    <h2><?= __("Print Selected QSL Postcards") ?></h2>

	<?php if (!empty($templates)) { ?>

		<div class="card p-3">
			<form id="selectedPrintForm" method="post">

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

				<?php foreach ($selected_ids as $id): ?>
					<input type="hidden" name="selected_ids[]" value="<?= (int)$id ?>">
				<?php endforeach; ?>

			<div class="btn-group" role="group">
				<button type="button" id="btnPrintSelected" class="btn btn-success">
					<?= __("Generate Postcard PDF") ?>
				</button>
				<button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
				<ul class="dropdown-menu">
					<li><a class="dropdown-item" href="#" id="btnPrintSelectedSave"><i class="fas fa-download me-1"></i><?= __("Save PDF"); ?></a></li>
				</ul>
			</div>
			</form>
		</div>
		<?php } else { ?>
			<div class="alert alert-info">
				<?= __("No templates available for printing. Go to the QSL Postcard Designer and create a template first.") ?>
			</div>
		<?php } ?>
</div>

<script>
    document.getElementById('btnPrintSelected').addEventListener('click', () => {
        const tpl = document.getElementById('template_id').value;
        // Print options come from the template's layout.options, not this form.
        const form = document.getElementById('selectedPrintForm');
        form.action = `<?= site_url('qslpostcard/pdfselected') ?>/${tpl}`;
        form.target = '_blank';
        form.submit();
    });
    document.getElementById('btnPrintSelectedSave').addEventListener('click', () => {
        const tpl = document.getElementById('template_id').value;
        const form = document.getElementById('selectedPrintForm');
        form.action = `<?= site_url('qslpostcard/pdfselected') ?>/${tpl}?download=1`;
        form.target = '_blank';
        form.submit();
    });
</script>
