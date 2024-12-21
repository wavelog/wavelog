<!-- First Layer - Actions Modal -->
<div class="modal fade bg-black bg-opacity-50" id="actionsModal" tabindex="-1" aria-labelledby="actionsLabel" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionsLabel"><?= __("Other Actions") ?></h5>
            </div>
            <div class="modal-body" style="text-align: left !important;">
                <p><?= __("Select an action to perform for the user:"); ?></p>
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
                </table>
                <hr>
                <div class="d-flex flex-column">
                    
                    <?php if (!$is_clubstation) { ?>
                    <button class="btn btn-primary mb-2" data-bs-target="#passwordResetModal" data-bs-toggle="modal">
                        <i class="fas fa-key"></i> <?= __("Send a Password Reset Link via Email"); ?>
                    </button>
                    <?php } ?>

                    <?php if ($this->config->item('special_callsign')) { ?>
                    <button class="btn btn-warning mb-2" data-bs-target="#userConvertModal" data-bs-toggle="modal">
                        <i class="fas fa-users"></i> <?php if ($is_clubstation) { echo __("Convert to User"); } else { echo __("Convert to Clubstation"); } ?>
                    </button>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close") ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Second Layer - Password Reset Modal -->
<script>
	var lang_admin_email_settings_incorrect = "<?= __("Email settings are incorrect."); ?>";
	var lang_admin_password_reset_processed = "<?= __("Password-reset email sent successfully to user. You can close this dialog now."); ?>";
    var lang_admin_password_reset_failed = "<?= __("Password-reset email could not be sent to user. Are the email settings in global options configured correctly?"); ?>";
</script>
<div class="modal fade bg-black bg-opacity-50" id="passwordResetModal" tabindex="-1" aria-labelledby="passwordResetLabel" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordResetLabel"><?= __("Send a Password Reset Link via Email"); ?></h5>
            </div>
            <div class="modal-body" style="text-align: left !important;">
                <p><?= __("You are about to send a password reset link to the user. The user will be able to reset their password by clicking on the link in the email."); ?></p>
                <p><?= __("Do you want to send the password reset email to this user?"); ?></p>
                <p>
                    <?= __("User:"); ?> <strong><?php echo $user_name; ?></strong><br>
                    <?= __("Name:"); ?> <strong><?php echo $user_firstname . ' ' . $user_lastname; ?></strong><br>
                    <?= __("Callsign:"); ?> <strong><?php echo $user_callsign; ?></strong><br>
                    <?= __("Language:"); ?> <strong><?= __($user_language); ?></strong><br>
                    <?= __("E-Mail:"); ?> <strong><a href="mailto:<?php echo $user_email; ?>"><?php echo $user_email; ?></a></strong>
                </p>
                <br>
                <button id="send_resetlink_btn" type="button" class="btn btn-primary ld-ext-right" onclick="send_passwort_reset(<?php echo $user_id; ?>)">
                    <?= __("Send the email") ?>
                    <div class="ld ld-ring ld-spin"></div>
                    <i id="passwordreset_sent" class="ms-2 fas" style="display: none;"></i>
                </button>
                <div class="alert mt-3" id="pwd_reset_message" style="display: none;" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close") ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Second Layer - Convert to Clubstation Modal -->
<?php if ($this->config->item('special_callsign')) { ?>
<script>
	var lang_account_conversion_processed = "<?= __("The account was successfully converted. You can now close this dialog."); ?>";
    var lang_account_conversion_failed = "<?= __("The account could not be converted. An error has occurred."); ?>";
</script>
<div class="modal fade bg-black bg-opacity-50" id="userConvertModal" tabindex="-1" aria-labelledby="userConvertLabel" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="userConvertLabel">
                    <?php if ($is_clubstation) { 
                        echo __("Convert this account into a normal user"); 
                    } else { 
                        echo __("Convert this account into a clubstation"); 
                    } ?>
                </h5>
            </div>
            <div class="modal-body" style="text-align: left !important;">
                <p><?php
                    if ($is_clubstation) {
                        echo __("You are about to convert this club station to a regular user account. The user will be able to log in again and all assigned club permissions will be removed. Use with caution!");
                    } else {
                        echo __("You are about to convert this user account to a club station. The user will no longer be able to log in and the account will be converted to a club station account. Use with caution!");
                    }
                ?></p>
                <p><?= __("Are you sure you want to convert this account?"); ?></p>
                <p>
                    <?= __("User:"); ?> <strong><?php echo $user_name; ?></strong><br>
                    <?= __("Name:"); ?> <strong><?php echo $user_firstname . ' ' . $user_lastname; ?></strong><br>
                    <?= __("Callsign:"); ?> <strong><?php echo $user_callsign; ?></strong><br>
                    <?= __("Language:"); ?> <strong><?= __($user_language); ?></strong><br>
                    <?= __("E-Mail:"); ?> <strong><a href="mailto:<?php echo $user_email; ?>"><?php echo $user_email; ?></a></strong>
                </p>
                <br>
                <button id="convert_user_btn" type="button" class="btn btn-danger ld-ext-right" onclick="convert_user(<?php echo $user_id; ?>, <?php if ($is_clubstation) { echo '0'; } else { echo '1'; } ?>)">
                    <?= __("Convert") ?>
                    <div class="ld ld-ring ld-spin"></div>
                    <i id="user_converted" class="ms-2 fas" style="display: none;"></i>
                </button>
                <div class="alert mt-3" id="user_converted_message" style="display: none;" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button id="conversion_close" type="button" class="btn btn-secondary" onclick="window.location.reload();"><?= __("Close") ?></button>
            </div>
        </div>
    </div>
</div>
<?php } ?>