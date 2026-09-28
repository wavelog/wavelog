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

$qsoinfo = '';

if (isset($qsos) && is_array($qsos) && count($qsos) > 0) {

    $qsoinfo .= __("Requested QSOs").":\n\n";

    foreach ($qsos as $qso) {

        $qsoinfo .= sprintf(
            "%s %s UTC  %s  %s %s %s %s %s\n",
            $qso[0] ?? '',
            $qso[1] ?? '',
            $qso[2] ?? '',
            $qso[3] ?? '',
            $qso[4] ?? '',
            $qso[5] ?? '',
            $qso[6] ?? '',
            $qso[7] ?? ''
        );
    }

    $qsoinfo .= "\n";
}





$route = '';

if (!empty($qslroute)) {
    $route = ($qslroute == 'D') ? 'Direct' : (($qslroute == 'B') ? 'Bureau' : $qslroute);
}

$message['body'] =
sprintf(
    __("Hi,

You got an OQRS request from %s."),
    strtoupper($callsign)
) . "\n\n" .

__("Callsign").": " . strtoupper($callsign) . "\n" .
__("Email").": " . $email . "\n" .
__("QSL Route").": " . $route . "\n" .
__("DX Callsign").": "    .   $dxcallsigns    .   "\n\n" .

$qsoinfo .

$um_formatted .

__("Please log into your Wavelog and process it.

Regards,

Wavelog");

echo json_encode($message);
