<style>
    .option-item {
        display: grid;
        grid-template-columns: 32px 1fr auto;
        align-items: start;
        gap: 0.5rem;
    }
    .option-item .switch-container {
        margin-top: -0.25rem;
    }
</style>

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

                <div class="option-item mb-3">
                    <i class="fas fa-check-circle text-success mt-1"></i>
                    <div>
                        <label for="markprinted" class="form-label fw-bold mb-0">
                            <?= __("Mark QSL as printed"); ?>
                        </label>
                    </div>
                    <div class="form-check form-switch switch-container">
                        <input class="form-check-input" type="checkbox" name="markprinted" id="markprinted" style="width: 3em; height: 1.5em;">
                    </div>
                </div>

                <!-- selected_ids are injected by JS from the selected logbook rows -->
                <div id="qslcard_selected_ids" class="d-none"></div>

                <div class="btn-group" role="group">
                    <button type="button" id="btnPrintQslCard" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> <?= __("Generate Postcard PDF"); ?>
                    </button>
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="btnPrintQslCardSave"><i class="fas fa-download me-1"></i> <?= __("Save PDF"); ?></a></li>
                    </ul>
                </div>
            </form>
        <?php } else { ?>
            <div class="alert alert-info mb-0 d-flex align-items-start">
                <i class="fas fa-info-circle me-2 mt-1"></i>
                <span><?= __("No templates available for printing. Go to the QSL Postcard Designer and create a template first."); ?></span>
            </div>
        <?php } ?>
    </div>
</div>
