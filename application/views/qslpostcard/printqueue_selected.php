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

				<div class="mb-3 form-check">
					<input type="checkbox" class="form-check-input" id="dedupe_by_call" checked>
					<label class="form-check-label" for="dedupe_by_call">
						<?= __("One postcard per callsign") ?>
					</label>
				</div>

				<div class="mb-3 form-check">
					<input type="checkbox" class="form-check-input" id="print_background">
					<label class="form-check-label" for="print_background">
						<?= __("Print background image (uncheck for pre-printed cards)") ?>
					</label>
				</div>

				<div class="mb-3 form-check">
					<input type="checkbox" class="form-check-input" id="print_no_address">
					<label class="form-check-label" for="print_no_address">
						<?= __("Skip address printing (for printing on regular QSL cards)") ?>
					</label>
				</div>

				<?php foreach ($selected_ids as $id): ?>
					<input type="hidden" name="selected_ids[]" value="<?= (int)$id ?>">
				<?php endforeach; ?>

				<input type="hidden" name="dedupe_by_call" id="dedupe_by_call_hidden" value="1">
				<input type="hidden" name="print_background" id="print_background_hidden" value="0">
				<input type="hidden" name="print_no_address" id="print_no_address_hidden" value="0">

				<button type="button" id="btnPrintSelected" class="btn btn-success">
					<?= __("Generate Postcard PDF") ?>
				</button>
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
        const dedupe = document.getElementById('dedupe_by_call').checked ? '1' : '0';
        const printBg = document.getElementById('print_background').checked ? '1' : '0';
        const printNoAddress = document.getElementById('print_no_address').checked ? '1' : '0';

        document.getElementById('dedupe_by_call_hidden').value = dedupe;
        document.getElementById('print_background_hidden').value = printBg;
        document.getElementById('print_no_address_hidden').value = printNoAddress;

        const form = document.getElementById('selectedPrintForm');
        form.action = `<?= site_url('qslpostcard/pdfselected') ?>/${tpl}`;
        form.submit();
    });
</script>
