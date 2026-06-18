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
        isClubStation: <?php echo json_encode($is_club_station ?? false); ?>,
        measurement_base: 'K', // In hamradio you usually use kilometers
        custom_date_format: <?php echo isset($custom_date_format) ? json_encode($custom_date_format) : 'null'; ?>,
        worker: <?php echo json_encode(isset($worker_client_url) ? [
            'url'          => $worker_client_url,
            'topic'        => $worker_topic,
            'token'        => $worker_token,
            'radio_topics' => $radio_worker_topics ?? [],
        ] : null); ?>,
        mapPrefs: <?php echo json_encode($map_prefs ?? ['nightshadow' => true, 'pathline' => true, 'station' => true, 'autofit' => true, 'grid' => true]); ?>
    };

    // Decode HTML entities produced by the gettext encoder for use in JS string contexts
    function decodeHtml(html) {
        const txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    // Language
    let lang_really_end_contest = decodeHtml("<?= __("Are you sure you want to end the contest?"); ?>");
    let lang_layout_reset_default = decodeHtml("<?= __("Layout reset to default!"); ?>");
    let lang_layout_saved = decodeHtml("<?= __("Layout saved!"); ?>");
    let lang_layout_deleted = decodeHtml("<?= __("Layout deleted!"); ?>");
    let lang_layout_delete_confirm = decodeHtml("<?= __("Are you sure you want to delete the layout '%s'?"); ?>");
    let lang_layout_reset_prompt = decodeHtml("<?= __("Are you sure you want to reset the layout to default?"); ?>");
    let lang_layout_name_prompt = decodeHtml("<?= __("Enter a name for this layout:"); ?>");
    let lang_layout_no_layouts = decodeHtml("<?= __("No saved layouts found."); ?>");
    let lang_layout_default_name = decodeHtml("<?= __("Default"); ?>");
    let lang_layout_default_layout = decodeHtml("<?= __("Default layout"); ?>");
    let lang_layout_set_default = decodeHtml("<?= __("Set as default"); ?>");
    let lang_layout_save_error = decodeHtml("<?= __("Error saving layout"); ?>");
    let lang_layout_error_default = decodeHtml("<?= __("Error setting default layout"); ?>");
    let lang_layout_error_delete = decodeHtml("<?= __("Error deleting layout"); ?>");
    let lang_layout_error_reset = decodeHtml("<?= __("Error resetting layout"); ?>");
    let lang_app_load_error = decodeHtml("<?= __("Error loading application. Please refresh the page.") ?>");
    let lang_app_loading_component = decodeHtml("<?= __("Loading %s...") ?>");
    let lang_app_init_datastore = decodeHtml("<?= __("Initializing data store...") ?>");
    let lang_app_init_core = decodeHtml("<?= __("Initializing core systems...") ?>");
    let lang_heartbeat_warning = decodeHtml("<?= __("Heartbeat Warning") ?>");
    let lang_heartbeat_slow = decodeHtml("<?= __("Heartbeat request took %1 ms (threshold: %2 ms)") ?>");
    let lang_window_default_title = decodeHtml("<?= __("Window") ?>");
    let lang_map_nightshadow = decodeHtml("<?= __("Night") ?>");
    let lang_map_pathline = decodeHtml("<?= __("Path") ?>");
    let lang_map_station = decodeHtml("<?= __("Station") ?>");
    let lang_map_autofit = decodeHtml("<?= __("Auto-fit") ?>");
    let lang_map_grid = decodeHtml("<?= __("Gridsquares") ?>");
    let lang_unknown_error = decodeHtml("<?= __("Unknown error") ?>");
    let lang_warning = decodeHtml("<?= __("Warning") ?>");
    let lang_settings_changed = decodeHtml("<?= __("Contest settings have been changed. Please refresh the page to apply the new settings.") ?>");
    let lang_reload_now = decodeHtml("<?= __("Reload now") ?>");
    let lang_close = decodeHtml("<?= __("Close") ?>");
    let lang_switch_operator = decodeHtml("<?= __("Switch Operator") ?>");
    let lang_switch_op_pending = decodeHtml("<?= __("There are still unsynced QSOs. Please wait until they are saved before switching operator.") ?>");
    let lang_switch_op_failed = decodeHtml("<?= __("Operator switch failed. Please try again.") ?>");
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="<?= __("Close"); ?>"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4 text-center">
            <div class="control-panel-time font-monospace fw-bold" id="controlPanelTime">--:--:--</div>
            <small class="text-muted">UTC</small>
        </div>
        <div class="mb-4">
            <h6 class="text-muted mb-3"><i class="fas fa-trophy"></i> <?= __("Contest"); ?></h6>
            <?php if (!empty($switch_operator_mode)): ?>
            <button class="btn btn-secondary w-100 mb-2" id="btnSwitchOperator" data-bs-target="#switchOperatorModal">
                <i class="fas fa-user-friends"></i> <?= __("Switch Operator"); ?>
            </button>
            <?php endif; ?>
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

<?php if (!empty($switch_operator_mode)): ?>
<!-- Switch Operator modal. Both modes share one shell; only the body fields differ:
     - 'login'    (impersonation): re-authenticate another club operator's account.
     - 'callsign' (club_direct):   set the free-text operator callsign. -->
<div class="modal fade bg-black bg-opacity-50" id="switchOperatorModal" tabindex="-1" aria-labelledby="switchOperatorLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="switchOperatorLabel"><i class="fas fa-user-friends me-2"></i><?= __("Switch Operator"); ?></h5>
            </div>
            <form id="switchOperatorForm" autocomplete="off" data-mode="<?= $switch_operator_mode; ?>" data-club-callsign="<?= htmlspecialchars(strtoupper((string)($club_callsign ?? '')), ENT_QUOTES); ?>">
                <div class="modal-body">
                    <div id="switchOperatorError" class="alert alert-danger d-none" role="alert"></div>
                    <?php if ($switch_operator_mode === 'login'): ?>
                    <p class="text-muted"><?= __("Log in with another operator's account to continue this contest session under their callsign."); ?></p>
                    <div class="mb-3">
                        <label for="switchOperatorUser" class="form-label"><?= __("Username"); ?></label>
                        <input type="text" class="form-control" id="switchOperatorUser" name="user_name" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label for="switchOperatorPass" class="form-label"><?= __("Password"); ?></label>
                        <input type="password" class="form-control" id="switchOperatorPass" name="user_password" autocomplete="new-password" required>
                    </div>
                    <?php else: ?>
                    <p class="text-muted"><?= __("Please provide your personal call sign. This makes sure that QSOs are logged and exported with correct operator information."); ?></p>
                    <div class="mb-3">
                        <label for="switchOperatorCall" class="form-label"><?= __("Your personal Callsign:"); ?></label>
                        <input type="text" class="form-control uppercase" id="switchOperatorCall" name="operator_callsign" autocomplete="off" required>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                    <button type="submit" class="btn btn-success" id="switchOperatorSubmit">
                        <i class="fas fa-sign-in-alt me-2"></i><?= __("Switch Operator"); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>