<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Centralized definition of (valid) modes defined in ADIF specification */

$config['adif_propmodes'] = array(
   "AS" => __("Aircraft Scatter"),
   "AUR" => __("Aurora"),
   "AUE" => __("Aurora-E"),
   "BS" => __("Back scatter"),
   "ECH" => __("EchoLink"),
   "EME" => __("Earth-Moon-Earth"),
   "ES" => __("Sporadic E"),
   "FAI" => __("Field Aligned Irregularities"),
   "F2" => __("F2 Reflection"),
   "GWAVE" => __("Ground Wave"),
   "INTERNET" => __("Internet-assisted"),
   "ION" => __("Ionoscatter"),
   "IRL" => __("IRLP"),
   "LOS" => __("Line of Sight (includes transmission through obstacles such as walls)"),
   "MS" => __("Meteor scatter"),
   "RPT" => __("Terrestrial or atmospheric repeater or transponder"),
   "RS" => __("Rain scatter"),
   "SAT" => __("Satellite"),
   "TEP" => __("Trans-equatorial"),
   "TR" => __("Tropospheric ducting")
);

?>
