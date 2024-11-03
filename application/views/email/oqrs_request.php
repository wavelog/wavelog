<?php

$message['subject'] = sprintf(__("Wavelog OQRS from %s"), strtoupper($callsign));

if ($usermessage != '') {
    $um_formatted = __("The user entered the following message: ") . "\n\n";
    $um_formatted .= "------------" . "\n";
    $um_formatted .= $usermessage . "\n";
    $um_formatted .= "------------" . "\n\n";
} else {
    $um_formatted = __("The user did not enter any additional message.") . "\n\n";
}

$message['body'] = sprintf( __("Hi,

You got an OQRS request from %s."), strtoupper($callsign)) . "\n\n" .

$um_formatted .

__("Please log into your Wavelog and process it.

Regards,

Wavelog");

echo json_encode($message);