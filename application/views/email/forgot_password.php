<?php 

$message['subject'] = __("Wavelog Account Password Reset");

$message['body'] = __("Hi,

You or someone else has requested a password reset on your Wavelog account.") . "\n\n" .

// sprintf(__("Your password reset code is: %s"), $reset_code) . "\n\n" .

sprintf(__("Click here to reset your password: %s"), site_url('user/reset_password/') . $reset_code) . "\n\n" .

__("If you didn't request this just ignore.

Regards,

Wavelog");

echo json_encode($message);