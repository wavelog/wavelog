<script>
const propagationHomegrid = "<?php echo strtoupper($homegrid[0]); ?>";
</script>

<div class="container-fluid px-3 px-lg-4 mt-3 mb-3">
    <h2><?= __("Propagation Map"); ?></h2>

    <div class="card">
        <div class="card-header">
            <?= __("Live propagation information"); ?>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input"
                           type="checkbox"
                           id="propTerminator"
                           checked>

                    <label class="form-check-label" for="propTerminator">
                        <?= __("Day / Night"); ?>
                    </label>
                </div>
            </div>

            <div id="propagationMap"></div>

        </div>
    </div>
</div>

<style>
#propagationMap {
    width: 100%;
    height: calc(100vh - 260px);
    min-height: 500px;
}
</style>
