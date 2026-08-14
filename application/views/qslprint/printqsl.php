<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($templates)) { ?>
            <form id="printQslCardForm" method="post">

                <div class="mb-3">
                    <label for="qslcard_template_id" class="form-label fw-bold d-flex align-items-center">
                        <i class="fas fa-id-card text-primary me-2" style="width: 20px;"></i>
                        <?= __("Template"); ?>
                    </label>
                    <select id="qslcard_template_id" name="template_id" class="form-select">
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= (int)$t['id']; ?>">
                                <?= htmlentities($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 small text-muted d-flex align-items-start">
                    <i class="fas fa-info-circle text-info mt-1 me-2" style="width: 20px;"></i>
                    <span><?= __("Print options (QSOs per card, background image, address printing) are set per template in the QSL Postcard Designer."); ?></span>
                </div>

                <!-- selected_ids are injected by JS from the selected logbook rows -->
                <div id="qslcard_selected_ids" class="d-none"></div>

                <div class="btn-group" role="group">
                    <button type="button" id="btnPrintQslCard" onclick="printSelectedQsos(<?php echo $printAll; ?>)" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> <?= __("Generate Postcard PDF"); ?>
                    </button>
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                    <ul class="dropdown-menu">
                        <li><button type="button" onclick="saveAndPrintSelectedQsos(<?php echo $printAll; ?>)" class="dropdown-item" href="#" id="btnPrintQslCardSave"><i class="fas fa-download me-1"></i> <?= __("Save PDF"); ?></button></li>
                    </ul>
                </div>
				<button type="button" class="btn btn-secondary btn-sm me-3 ld-ext-right" id="button_markprint" onclick="markQslPrinted(<?php echo $printAll; ?>)"><i class="fas fa-check me-1"></i> <?= __("Mark QSL as printed"); ?></button>
            </form>
        <?php } else { ?>
            <div class="alert alert-info mb-0 d-flex align-items-start">
                <i class="fas fa-info-circle me-2 mt-1"></i>
                <span><?= __("No templates available for printing. Go to the QSL Postcard Designer and create a template first."); ?></span>
            </div>
        <?php } ?>
    </div>
</div>
