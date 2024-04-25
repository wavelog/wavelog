<div class="modal fade" id="editCronModal" tabindex="-1" aria-labelledby="editCronModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cronEditLabel">Edit Cronjob</h5>
            </div>
            <div class="modal-body">
                <p>We want to edit "<?php echo $crondetails->id; ?>" here</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo lang('admin_save'); ?></button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('general_word_cancel'); ?></button>
            </div>
        </div>
    </div>
</div>