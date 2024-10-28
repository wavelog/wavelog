<?= 

sprintf(__("Hello %s"), $user_firstname . ", " . $user_callsign);

__("An admin initiated a password reset for your Wavelog account.");

sprintf("Your username is: %s", '<b>' . $user_name . '<b>');

sprintf(__("Click here to reset your password: %s"), site_url('user/reset_password/') . $reset_code);

__("If you didn't request any password reset, just ignore this email and talk to an admin of your Wavelog instance.

Regards,

Wavelog");
