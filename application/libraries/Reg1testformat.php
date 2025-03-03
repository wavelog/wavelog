<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Format according to https://www.ok2kkw.com/ediformat.htm
class Reg1testformat {

   public function header($contest_id, $from, $to, $callsign, $gridlocator, $contestaddress1, $contestaddress2, $categoryoperator, $band, $club, $name,
                            $responsible_operator, $address1, $address2, $addresspostalcode, $addresscity, $addresscountry, $operatorphone, $operators,
                            $soapbox, $qso_count, $sentexchange, $txequipment, $power, $rxequipment, $antenna, $antennaheight, $maxdistanceqso, $bandmultiplicator, $claimedpoints) {

      //build header
      $edi_header = "[REG1TEST;1]" . "\r\n";
      $edi_header .= "TName=" . $contest_id ."\r\n";   //Contestname
      $edi_header .= "TDate=" . str_replace("-", "", $from) . ";" . str_replace("-", "", $to) . "\r\n"; //from and to date of contest, Ymd format, with semicolon
      $edi_header .= "PCall=" . strtoupper($callsign) . "\r\n"; //Station callsign during contest
      $edi_header .= "PWWLo=" . strtoupper($gridlocator) . "\r\n"; //Gridlocator during contest
      $edi_header .= "PExch=" . strtoupper(substr($sentexchange, 0, 6)) . "\r\n"; //Sent exchange, max 6 characters uppercase
      $edi_header .= "PAdr1=" . $contestaddress1 . "\r\n"; //Contest Address Line 1
      $edi_header .= "PAdr2=" . $contestaddress2 . "\r\n"; //Contest Address Line 2
      $edi_header .= "PSect=" . $categoryoperator . "\r\n"; //Category / "Section"
      $edi_header .= "PBand=" . $this->reg1testbandstring($band) . "\r\n"; // Band in REG1TEST format
      $edi_header .= "PClub=" . strtoupper($club) . "\r\n"; //Club
      $edi_header .= "RName=" . $name. "\r\n"; //Name of responsible operator
      $edi_header .= "RCall=" . strtoupper($responsible_operator) . "\r\n"; //Callsign of responsible operator, if different from contest callsign
      $edi_header .= "RAdr1=" . $address1 . "\r\n"; //Operator Address Line 1
      $edi_header .= "RAdr2=" . $address2 . "\r\n"; //Operator Address Line 2
      $edi_header .= "RPoCo=" . $addresspostalcode . "\r\n";  //Operator Address Postal Code
      $edi_header .= "RCity=" . $addresscity . "\r\n";  //Operator Address City
      $edi_header .= "RCoun=" . $addresscountry . "\r\n";  //Operator Address Country
      $edi_header .= "RPhon=" . $operatorphone . "\r\n";   //Operator Address Phone number
      $edi_header .= "RHBBS=" . "\r\n"; //Bulletin board address of operator. Pretty safe to omit in past 2024
      $edi_header .= "MOpe1=" . strtoupper($operators) . "\r\n"; //Operators
      $edi_header .= "MOpe2=" . "\r\n"; //Operators line 2. Leave empty.
      $edi_header .= "STXEq=" . $txequipment . "\r\n"; //TX Equipment description
      $edi_header .= "SPowe=" . $power . "\r\n"; //Power in Watts
      $edi_header .= "SRXEq=" . $rxequipment . "\r\n"; //RX Equipment description
      $edi_header .= "SAnte=" . $antenna . "\r\n"; //Antenna description
      $edi_header .= "SAntH=" . $antennaheight . "\r\n"; //Antenna height above ground
      $edi_header .= "CQSOs=" . $qso_count . ';' . $bandmultiplicator . "\r\n"; //Arguments describe the claimed number of valid QSOs and the band multiplier.
      $edi_header .= "CQSOP=" . $claimedpoints . "\r\n"; //Argument describes the claimed total number of QSO-points.
      $edi_header .= "CWWLs=" . "\r\n"; //Arguments describe the claimed number of WWLs worked, the number of bonus points claimed for each new WWL and the WWL multiplier. Leave empty.
      $edi_header .= "CWWLB=" . "\r\n"; //Argument describes the claimed total number of WWL bonus points. Leave empty.
      $edi_header .= "CExcs=" . "\r\n"; //Arguments describe the claimed number of Exchanges worked, the number of bonus points claimed for each new Exchange and the Exchange multiplier. Leave empty.
      $edi_header .= "CExcB=" . "\r\n"; //Argument describes the claimed total number of Exchange bonus points. Leave empty.
      $edi_header .= "CDXCs=" . "\r\n"; //Arguments describe the claimed number of DXCCs worked, the number of bonus points claimed for each new DXCC and the DXCC multiplier. Leave empty.
      $edi_header .= "CDXCB=" . "\r\n"; //Argument describes the claimed total number of DXCC bonus points. Leave empty.
      $edi_header .= "CToSc=" . "\r\n"; //Argument describes the total claimed score. Leave empty.

      //set QSO info for QSO with max distance only if we can determine it
      if(!empty($maxdistanceqso['qso'])){
         $edi_header .= "CODXC=" . strtoupper($maxdistanceqso['qso']->COL_CALL) . ";" . substr(strtoupper($maxdistanceqso['qso']->COL_GRIDSQUARE), 0, 6) . ";" . intval($maxdistanceqso['distance']) . "\r\n"; //Arguments describe the claimed ODX contact call, WWL and distance.
      }else{
         $edi_header .= "CODXC=" . "\r\n"; //Arguments describe the claimed ODX contact call, WWL and distance. Leave empty.
      }

      $edi_header .= "[Remarks]" . "\r\n" . $soapbox . "\r\n"; //Remarks
      $edi_header .= "[QSORecords;" . $qso_count . "]" . "\r\n"; //QSO Header and QSO Count

      //return the header
      return $edi_header;

   }

   public function footer() {
      //return a newline as the last line for good measure
      return "\r\n";
   }

   public function qsos($qsodata, $mylocator, $bandmultiplicator){
      //get codeigniter instance
      $CI = &get_instance();

      //load QRA library
      if(!$CI->load->is_loaded('Qra')) {
			$CI->load->library('Qra');
		}

      //define helper variables
      $locators = [];
      $dxccs = [];
      $exchanges = [];

      //define result
      $result = [];
      $result['formatted_qso'] = "";
      $result['claimedpoints'] = 0;

      //iterate through every QSO and construct detail format
      foreach ($qsodata->result() as $row) {

         //result string
         $qsorow = "";

         $qsorow .= date('ymd', strtotime($row->COL_TIME_ON)) . ';';  //Date in YYMMDD format
         $qsorow .= date('Hi', strtotime($row->COL_TIME_ON)) . ';'; //Time in HHMM format
         $qsorow .= substr($row->COL_CALL, 0, 14) . ';'; //Callsign, maximum 14 characters
         $qsorow .= $this->reg1testmodecode($row->COL_MODE) . ';'; //Mode-Code in REG1TEST format
         $qsorow .= substr($row->COL_RST_SENT, 0, 3) . ';'; //Sent RST, max 3 characters
         $qsorow .= substr(str_pad($row->COL_STX ?? "", 3, '0', STR_PAD_LEFT), 0, 4) . ';';; //Sent Number of QSO with 3 digits with leading zeros. If number gets greater than 999, 4 characters are used at maximum
         $qsorow .= substr($row->COL_RST_RCVD, 0, 3) . ';'; //Received RST, max 3 characters
         $qsorow .= substr(str_pad($row->COL_SRX ?? "", 3, '0', STR_PAD_LEFT), 0, 4) . ';';; //Received Number of QSO with 3 digits with leading zeros. If number gets greater than 999, 4 characters are used at maximum
         $qsorow .= substr($row->COL_SRX_STRING ?? "", 0, 6) . ';'; //Received Exchange, max 6 characters
         $qsorow .= strtoupper(substr($row->COL_GRIDSQUARE ?? "" , 0, 6)) . ';'; //Gridsquare max 6 characters

         //calculate or get distance in whole kilometers while determening if this is a new locator or not
         if (!empty($row->COL_GRIDSQUARE)) {
            if(!array_key_exists($row->COL_GRIDSQUARE, $locators)){
               $newlocator = true;
               $distance = intval($CI->qra->distance($mylocator, $row->COL_GRIDSQUARE, "K", $row->COL_ANT_PATH));
               $locators[$row->COL_GRIDSQUARE] = $distance;
            }else{
               $newlocator = false;
               $distance = $locators[$row->COL_GRIDSQUARE];
            }
         } else {
            $distance = 0;
            $newlocator = false;
         }

         //determine QSO points and add those to the total
         $qsopoints = intval(round($distance * $bandmultiplicator, 0));
         $result['claimedpoints'] += $qsopoints;

         $qsorow .= $qsopoints . ";"; //qso points = distance * bandmultiplicator

         //determine if the exchange is new or not
         if(!in_array($row->COL_SRX_STRING, $exchanges)){
            $newexchange = true;
            array_push($exchanges, $row->COL_SRX_STRING);
         }else{
            $newexchange = false;
         }

         $qsorow .= ($newexchange ? 'N' : '') . ';'; //flag if exchange is new
         $qsorow .= ($newlocator ? 'N' : '') . ';'; //flag if locator is new

         //determine if DXCC is new or not
         if(!in_array($row->COL_DXCC, $dxccs)){
            $newdxcc = true;
            array_push($dxccs, $row->COL_DXCC);
         }else{
            $newdxcc = false;
         }

         $qsorow .= ($newdxcc ? 'N' : '') . ';'; //flag if DXCC is new

         $qsorow .= "\r\n"; //flag for duplicate QSO. Leave empty as Wavelog does not have this. Do not include a semicolon at the end as this is optional

         //add row to overall result
         $result['formatted_qso'] .= $qsorow;

      }

      //return QSO detail
      return $result;
   }

   public function reg1testbandstring($band){

      //translate wavelogs bands to REG1TEST format
      switch ($band) {
         case '6m':
            return "50 MHz";
         case "4m":
            return "70 MHz";
         case "2m":
            return "144 MHz";
         case "70cm":
            return "432 MHz";
         case "23cm":
            return "1,3 GHz";
         case "13cm";
            return "2,3 GHz";
         case "9cm":
            return "3,4 GHz";
         case "6cm":
            return "5,7 GHz";
         case "3cm":
            return "10 GHz";
         case "1.25cm":
            return "24 GHz";
        default:
            return "invalid";
      }
   }

   public function reg1testmodecode($mode){
      switch ($mode) {
         case 'SSB':
            return 1;
         case 'CW':
            return 2;
         case 'AM':
            return 5;
         case 'FM':
            return 6;
         case 'RTTY':
            return 7;
         case 'SSTV':
            return 8;
         case 'ATV':
            return 9;
         default:
            return 0;
      }
   }
}
