<div class="modal fade bg-black bg-opacity-50" id="clubswitchModal" tabindex="-1" aria-labelledby="clubswitchLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clubswitchLabel"><?= __("Switch to a Clubstation") ?></h5>
            </div>
            <form action="<?= site_url('user/impersonate'); ?>" method="post">
                <div class="modal-body" style="text-align: center !important;">
                    <p><?= sprintf(__("Are you sure you want to switch to %s?"), $club_callsign); ?></p>
                    <input type="hidden" name="hash" value="<?php echo $impersonate_hash; ?>">
                    <input type="hidden" name="clubswitch" value="1">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><?= __("Yes, switch over!"); ?></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>