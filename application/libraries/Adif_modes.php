<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Centralized definition of (valid) modes defined in ADIF specification */

class Adif_modes
{

   private $adif_modes = array(
      "AS" => "Aircraft Scatter",
      "AUR" => "Aurora",
      "AUE" => "Aurora-E",
      "BS" => "Back scatter",
      "ECH" => "EchoLink",
      "EME" => "Earth-Moon-Earth",
      "ES" => "Sporadic E",
      "FAI" => "Field Aligned Irregularities",
      "F2" => "F2 Reflection",
      "GWAVE" => "Ground Wave",
      "INTERNET" => "Internet-assisted",
      "ION" => "Ionoscatter",
      "IRL" => "IRLP",
      "LOS" => "Line of Sight (includes transmission through obstacles such as walls)",
      "MS" => "Meteor scatter",
      "RPT" => "Terrestrial or atmospheric repeater or transponder",
      "RS" => "Rain scatter",
      "SAT" => "Satellite",
      "TEP" => "Trans-equatorial",
      "TR" => "Tropospheric ducting"
   );

   public function get() {
      return $this->adif_modes;
   }
}

?>
