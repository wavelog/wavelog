<?php 

$message['subject'] = sprintf(__("Your permission level for Clubstation %s has been changed"), $club_callsign);

$message['body'] = sprintf(__("Dear %s,

Your permission level for Clubstation %s has been changed. You can access this callsign through your account at %s."), $user_callsign, $club_callsign, base_url()) . "\n\n" .

sprintf(__("Your new permission level is: %s"), $permission_level) . "\n\n" .

__("Log in and check it out!

Regards,

Wavelog");

echo json_encode($message);