<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Centralized definition of (valid) modes defined in ADIF specification */

$config['adif_propmodes'] = array(
   "AS" => _pgettext("Propagation Mode", "Aircraft Scatter"),
   "AUR" => _pgettext("Propagation Mode", "Aurora"),
   "AUE" => _pgettext("Propagation Mode", "Aurora-E"),
   "BS" => _pgettext("Propagation Mode", "Back scatter"),
   "ECH" => _pgettext("Propagation Mode", "EchoLink"),
   "EME" => _pgettext("Propagation Mode", "Earth-Moon-Earth"),
   "ES" => _pgettext("Propagation Mode", "Sporadic E"),
   "FAI" => _pgettext("Propagation Mode", "Field Aligned Irregularities"),
   "F2" => _pgettext("Propagation Mode", "F2 Reflection"),
   "GWAVE" => _pgettext("Propagation Mode", "Ground Wave"),
   "INTERNET" => _pgettext("Propagation Mode", "Internet-assisted"),
   "ION" => _pgettext("Propagation Mode", "Ionoscatter"),
   "IRL" => _pgettext("Propagation Mode", "IRLP"),
   "LOS" => _pgettext("Propagation Mode", "Line of Sight (includes transmission through obstacles such as walls)"),
   "MS" => _pgettext("Propagation Mode", "Meteor scatter"),
   "RPT" => _pgettext("Propagation Mode", "Terrestrial or atmospheric repeater or transponder"),
   "RS" => _pgettext("Propagation Mode", "Rain scatter"),
   "SAT" => _pgettext("Propagation Mode", "Satellite"),
   "TEP" => _pgettext("Propagation Mode", "Trans-equatorial"),
   "TR" => _pgettext("Propagation Mode", "Tropospheric ducting")
);

?>
