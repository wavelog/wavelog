<?php

if ($usermessage != '') {
    $usermessage = __("The user entered the following message:
    
    %s", $usermessage);
} else {
    $usermessage = __("The user did not enter any additional message.");
}

echo sprintf( __("Hi,

You got an OQRS request from %s."), strtoupper($callsign)) . "\n\n" .

$usermessage . "\n\n" .

__("Please log into your Wavelog and process it.

Regards,

Wavelog");
