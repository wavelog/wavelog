<?php

$message['subject'] = __("Wavelog Account Password Reset");

$message['body'] = sprintf(__("Hello %s"), $user_firstname . ", " . $user_callsign) . "\n\n" .

__("An admin initiated a password reset for your Wavelog account.") . "\n\n" . 

sprintf(__("Your username is: %s"), $user_name) . "\n\n" . 

sprintf(__("Click here to reset your password: %s"), site_url('user/reset_password/') . $reset_code) . "\n\n" . 

__("If you didn't request any password reset, just ignore this email and talk to an admin of your Wavelog instance.

Regards,

Wavelog");

echo json_encode($message);