<div class="modal fade bg-black bg-opacity-50" id="contestDeleteSessionModal" tabindex="-1" aria-labelledby="contestSessionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="contestSessionLabel"><i class="fas fa-plus-circle me-2"></i><?= __("Delete Contest Session"); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="flex-shrink: 0;"></button>
            </div>
            <form action="<?= site_url('contesting/delete_session'); ?>" method="post" id="contestSessionForm">
                <div class="modal-body">
                    <p class="text-muted mb-4"><?= __("Do you really want to delete this contest session?"); ?></p>
                    <br>
                    <p><strong><?= __("Contest: "); ?></strong><?php if (isset($session_info)) echo htmlspecialchars($session_info['contest_name']); ?></p>
                    <p><strong><?= __("Start Date/Time: "); ?></strong><?php if (isset($session_info)) echo htmlspecialchars($session_info['time_start']); ?></p>
                    <p><strong><?= __("End Date/Time: "); ?></strong><?php if (isset($session_info)) echo htmlspecialchars($session_info['time_end']); ?></p>
                    <p><strong><?= __("Station: "); ?></strong><?php if (isset($session_info)) echo htmlspecialchars($session_info['station_id']); ?></p>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i><?= __("Delete Session"); ?>
                    </button>
                </div>
                <input type="hidden" name="contest_session_id" value="<?php if (isset($session_info)) echo htmlspecialchars($session_info['contest_session_id']); ?>">
            </form>
        </div>
    </div>
</div>