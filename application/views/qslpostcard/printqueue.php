<div class="container mt-3">
    <h2><?= __("Print QSL Postcards") ?></h2>

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
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="dedupe_by_call" checked>
            <label class="form-check-label" for="dedupe_by_call">
                <?= __("One postcard per callsign") ?>
            </label>
        </div>
        <button id="btnPrintQueue" class="btn btn-success w-auto">
            <?= __("Generate Postcard PDF") ?>
        </button>
    </div>
</div>

<script>
    document.getElementById('btnPrintQueue').addEventListener('click', () => {
        const tpl = document.getElementById('template_id').value;

        const params = new URLSearchParams(<?= json_encode($filters) ?>);
        window.location.href = `<?= site_url('qslpostcard/pdfqueue') ?>/${tpl}?` + params.toString();
    });
</script>
