<?php 

$message['subject'] = sprintf(__("New %s Membership on Wavelog!"), $club_callsign);

$message['body'] = sprintf(__("Dear %s

You have been added to the Clubstation %s. You can now access this callsign through your account on %s."), $user_callsign, $club_callsign, base_url()) . "\n\n" .

sprintf(__("Your permission level is: %s"), $permission_level) . "\n\n" .

__("Log in and check it out!

Regards,

Wavelog");

echo json_encode($message);