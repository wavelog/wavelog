<div class="container mt-3">
    <h3>Print Selected QSL Postcards</h3>

    <div class="card p-3">
        <form id="selectedPrintForm" method="post">

            <div class="mb-3">
                <label class="form-label">Template</label>
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
                    One postcard per callsign
                </label>
            </div>

            <?php foreach ($selected_ids as $id): ?>
                <input type="hidden" name="selected_ids[]" value="<?= (int)$id ?>">
            <?php endforeach; ?>

            <input type="hidden" name="dedupe_by_call" id="dedupe_by_call_hidden" value="1">

            <button type="button" id="btnPrintSelected" class="btn btn-success">
                Generate Postcard PDF
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('btnPrintSelected').addEventListener('click', () => {
        const tpl = document.getElementById('template_id').value;
        const dedupe = document.getElementById('dedupe_by_call').checked ? '1' : '0';

        document.getElementById('dedupe_by_call_hidden').value = dedupe;

        const form = document.getElementById('selectedPrintForm');
        form.action = `<?= site_url('qslpostcard/pdfselected') ?>/${tpl}`;
        form.submit();
    });
</script>