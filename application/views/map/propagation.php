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

                <div class="form-check form-check-inline">
                    <input class="form-check-input"
                           type="checkbox"
                           id="propHeardMe"
                           checked>

                    <label class="form-check-label" for="propHeardMe">
                        <?= __("Heard Me"); ?>
                    </label>
                </div>

                <div class="form-check form-check-inline">
                    <input class="form-check-input"
                           type="checkbox"
                           id="propMuf"
                           checked>

                    <label class="form-check-label" for="propMuf">
                        <?= __("MUF"); ?>
                    </label>
                </div>

                <div class="form-group d-inline-flex align-items-center mb-0 ml-2">
                    <label class="mb-0 mr-2" for="propTimeRange">
                        <?= __("Time range"); ?>
                    </label>

                    <select class="form-control form-control-sm"
                            id="propTimeRange">
                        <option value="15" selected>15 <?= __("minutes"); ?></option>
                        <option value="30">30 <?= __("minutes"); ?></option>
                        <option value="60">60 <?= __("minutes"); ?></option>
                    </select>
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

.leaflet-tooltip.muf-tooltip {
    background: transparent;
    border: 0;
    box-shadow: none;
    padding: 0;
}

.muf-value {
    border-radius: 20px;
    color: #18202a;
    display: inline-block;
    font-weight: bold;
    padding: 5px 7px;
    white-space: nowrap;
}
</style>
