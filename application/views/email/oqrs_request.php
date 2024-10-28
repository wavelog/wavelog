<?= 

sprintf( __("Hi,

You got an OQRS request from %s."), strtoupper($callsign));

if ($usermessage != "") {
__("The user entered the following message:

%s", $usermessage);
}

__("Please log into your Wavelog and process it.

Regards,

Wavelog");
