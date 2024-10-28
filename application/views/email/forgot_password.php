<?= 

__("Hi,

You or someone else has requested a password reset on your Wavelog account.");

sprintf(__("Your password reset code is: %s"), $reset_code);

sprintf(__("Click here to reset your password: %s"), site_url('user/reset_password/') . $reset_code);

__("If you didn't request this just ignore.

Regards,

Wavelog");