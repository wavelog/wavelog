<script>
const propagationHomegrid = <?= json_encode(
    strtoupper($homegrid[0] ?? ''),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;
const propagationMessages = <?= json_encode([
    'heard_loading' => __("Loading PSK Reporter data..."),
    'heard_empty' => __("No PSK Reporter reports found for the selected time range."),
    'heard_loaded' => __("PSK Reporter reports loaded: %d"),
    'heard_limited' => __("PSK Reporter returned 500 reports. The result may be truncated."),
    'heard_error' => __("PSK Reporter data could not be loaded."),
    'muf_loading' => __("Loading MUF data..."),
    'muf_empty' => __("No current MUF values are available."),
    'muf_loaded' => __("MUF stations loaded: %d"),
    'muf_error' => __("MUF data could not be loaded.")
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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

            <div id="propagationStatus" aria-live="polite">
                <div id="heardMeStatus"
                     class="alert py-2 mb-2 d-none"
                     role="status"></div>
                <div id="mufStatus"
                     class="alert py-2 mb-2 d-none"
                     role="status"></div>
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

.propagation-band-legend {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 4px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.35);
    color: #18202a;
    font-size: 11px;
    line-height: 18px;
    padding: 6px 8px;
}

.propagation-band-legend span {
    border-radius: 50%;
    display: inline-block;
    height: 9px;
    margin-right: 4px;
    width: 9px;
}
</style>
