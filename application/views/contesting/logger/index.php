<?php // Contest Logger Engine - Main Layout Body 
?>
<?php // We need to init some variables for the JS app 
?>
<script type="text/javascript">
    window.ContestLoggerConfig = {
        sessionInfo: <?php echo json_encode($session_info); ?>,
        storageKey: <?php echo json_encode($storage_key); ?>,
        layout: <?php echo json_encode($components ?? []); ?>,
        operator: <?php echo json_encode($operator); ?>,
        measurement_base: 'K', // In hamradio you usually use kilometers
        custom_date_format: <?php echo isset($custom_date_format) ? json_encode($custom_date_format) : 'null'; ?>,
        worker: <?php echo json_encode(isset($worker_client_url) ? [
            'url'   => $worker_client_url,
            'topic' => $worker_topic,
            'token' => $worker_token,
        ] : null); ?>
    };

    // Language
    let lang_really_end_contest = "<?= __("Are you sure you want to end the contest?"); ?>";
    let lang_layout_reset_default = "<?= __("Layout reset to default!"); ?>";
    let lang_layout_saved = "<?= __("Layout saved!"); ?>";
    let lang_layout_deleted = "<?= __("Layout deleted!"); ?>";
    let lang_layout_delete_confirm = "<?= __("Are you sure you want to delete the layout '%s'?"); ?>";
    let lang_layout_reset_prompt = "<?= __("Are you sure you want to reset the layout to default?"); ?>";
    let lang_layout_name_prompt = "<?= __("Enter a name for this layout:"); ?>";
    let lang_layout_no_layouts = "<?= __("No saved layouts found."); ?>";
    let lang_layout_default_name = "<?= __("Default"); ?>";
    let lang_layout_default_layout = "<?= __("Default layout"); ?>";
    let lang_layout_set_default = "<?= __("Set as default"); ?>";
    let lang_layout_save_error = "<?= __("Error saving layout"); ?>";
    let lang_layout_error_default = "<?= __("Error setting default layout"); ?>";
    let lang_layout_error_delete = "<?= __("Error deleting layout"); ?>";
    let lang_layout_error_reset = "<?= __("Error resetting layout"); ?>";
    let lang_app_load_error = "<?= __("Error loading application. Please refresh the page.") ?>";
    let lang_app_loading_component = "<?= __("Loading %s...") ?>";
    let lang_app_init_datastore = "<?= __("Initializing data store...") ?>";
    let lang_app_init_core = "<?= __("Initializing core systems...") ?>";
    let lang_heartbeat_warning = "<?= __("Heartbeat Warning") ?>";
    let lang_heartbeat_slow = "<?= __("Heartbeat request took %1 ms (threshold: %2 ms)") ?>";
    let lang_window_default_title = "<?= __("Window") ?>";
</script>

<div>
<!-- Loading Screen -->
<div id="contest-loading-screen" class="contest-loading-screen">
    <div class="contest-loading-content">
        <div class="contest-loading-spinner"></div>
        <h3><?= __("Loading Contest Engine"); ?></h3>
        <p class="text-muted" id="loading-status"><?= __("Initializing components..."); ?></p>
    </div>
</div>

<!-- Control Panel -->
<button class="btn btn-primary control-panel-toggle" id="controlPanelToggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#controlPanel" aria-controls="controlPanel">
    <i class="fas fa-sliders-h"></i>
</button>

<div class="offcanvas offcanvas-start" tabindex="-1" id="controlPanel" aria-labelledby="controlPanelLabel" data-bs-scroll="true">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="controlPanelLabel">
            <img src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo'); ?>.png" alt="Wavelog" class="control-panel-logo" /> <?= __("Control Panel"); ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4 text-center">
            <div class="control-panel-time font-monospace fw-bold" id="controlPanelTime">--:--:--</div>
            <small class="text-muted">UTC</small>
        </div>
        <div class="mb-4">
            <h6 class="text-muted mb-3"><i class="fas fa-trophy"></i> <?= __("Contest"); ?></h6>
            <button class="btn btn-danger w-100" id="btnEndContest">
                <i class="fas fa-times-circle"></i> <?= __("End Session"); ?>
            </button>
        </div>
        <div class="mb-4">
            <h6 class="text-muted mb-3"><i class="fas fa-window-restore"></i> <?= __("Components"); ?></h6>
            <div id="componentVisibilityList" class="d-grid gap-2"></div>
        </div>
        <div class="mb-4">
            <h6 class="text-muted mb-3"><i class="fas fa-palette"></i> <?= __("User Layouts"); ?></h6>
            <div class="list-group mb-2">
                <button class="list-group-item list-group-item-action bg-dark text-white border-secondary" id="saveNewLayoutBtn">
                    <i class="fas fa-save"></i> <?= __("Save New Layout"); ?>
                </button>
                <button class="list-group-item list-group-item-action bg-dark text-white border-secondary" id="resetLayoutBtn">
                    <i class="fas fa-undo"></i> <?= __("Reset to Default Layout"); ?>
                </button>
            </div>
            <div id="savedLayoutsList" class="list-group"></div>
        </div>
        <div class="mb-4">
            <h6 class="text-muted mb-3"><i class="fas fa-cog"></i> <?= __("Settings"); ?></h6>
            <div class="list-group">
                <div class="list-group-item bg-dark text-white border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= __("Panel Position"); ?></span>
                        <select class="form-select form-select-sm w-auto" id="panelPositionSelect">
                            <option value="start"><?= __("Left"); ?></option>
                            <option value="end"><?= __("Right"); ?></option>
                            <option value="top"><?= __("Top"); ?></option>
                            <option value="bottom"><?= __("Bottom"); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="logger-workspace" class="logger-workspace">
    <?php if (!empty($components)): ?>
        <?php foreach (array_keys($components) as $component): ?>
            <?php $this->load->view('contesting/logger/components/' . $component); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>