<div class="modal fade bg-black bg-opacity-50" id="actionsModal" tabindex="-1" aria-labelledby="actionsLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionsLabel"><?= __("Impersonate User") . " " . __("(with Admin Rights)"); ?></h5>
            </div>
            <div class="modal-body" style="text-align: left !important;">
                <?php if (!$has_flossie) { ?>
                    <p><?= __("You are about to impersonate another user. To return to your admin account, you can use the switch back button in the header menu."); ?></p>
                    <p><?= __("Do you want to impersonate this user?"); ?></p>
                    <br>
                    <table>
                        <tr>
                            <td class="pe-3"><?= __("Username:"); ?></td>
                            <td><strong><?php echo $user_name; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="pe-3"><?= __("Name:"); ?></td>
                            <td><strong><?php echo $user_firstname . ' ' . $user_lastname; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="pe-3"><?= __("Callsign:"); ?></td>
                            <td><strong><?php echo $user_callsign; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="pe-3"><?= __("E-Mail:"); ?></td>
                            <td><strong><a href="mailto:<?php echo $user_email; ?>"><?php echo $user_email; ?></a></strong></td>
                        </tr>
                        <tr>
                            <td class="pe-3"><?= __("Last Seen:"); ?></td>
                            <td><strong><?php echo isset($last_seen) ? date($custom_date_format . ' H:i:s', strtotime($last_seen)) : __("Never"); ?></strong></td>
                        </tr>
                    </table>
                <?php } else { ?>
                    <div class="alert alert-danger" role="alert">
                        <?= __("You currently can't impersonate another user. Please change the encryption_key in your config.php file first!"); ?>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <form action="<?php echo site_url('user/impersonate'); ?>" method="post" style="display: inline;">
                    <input type="hidden" name="hash" value="<?php echo $this->encryption->encrypt($this->session->userdata('user_id') . '/' . $user_id . '/' . time()); ?>">
                    <button type="submit" class="btn btn-success" <?php if ($has_flossie) { echo 'disabled'; } ?>><?= __("Impersonate") ?></button>
                </form>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?= __("Cancel") ?></button>
            </div>
        </div>
    </div>
</div>