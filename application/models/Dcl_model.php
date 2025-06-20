<?php

class Dcl_model extends CI_Model {

	/*
	|--------------------------------------------------------------------------
	| Function: lotw_certs
	|--------------------------------------------------------------------------
	|
	| Returns all lotw_certs for a selected user via the $user_id parameter
	|
	*/
	function dcl_keys($user_id) {
		$this->load->model('user_options_model');
		return $this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key'), $user_id)->result();
	}


	function find_key($callsign, $user_id) {
		$this->load->model('user_options_model');
		return $this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key','option_key'=>$callsign), $user_id)->result();
	}

	function store_key($user_id, $callsign, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
	}

	function update_key($user_id, $callsign, $dxcc, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
	}

	function delete_key($call) {
		$this->user_options_model->del_option('dcl', 'dcl_key',array('option_key' => $call));
	}

	function get_dcl_info($token) {
		if (($token ?? '') != '') {
			try {
				$dclUrl = 'https://dings.dcl.darc.de/api/getuserinfo/'+$token;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $dclUrl);
				curl_setopt($ch, CURLOPT_HEADER, array('Content-Type: application/json' , "Authorization: Bearer ".$token));
				curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog DCL Connector');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				$rawdcldata = curl_exec($ch);
				curl_close($ch);
				if (strlen($dcldata)>100) {
					$dcldata=json_decode($rawdcldata);
					// todo: process Data from DCL (Contains valid call(s), valid date, DOK)
				}
			} catch (Exception $e) {
				return false;
			}
		} else {
			return false;
		}
	}

	function check_dcl_sig($string,$sig) {
		try {
			$sig = sodium_base642bin($sig, SODIUM_BASE64_VARIANT_URLSAFE);
			$public_key=sodium_base642bin('nV46R47wlLDOJAkSs5RT00wzgz3z98uZFxjo3FSkxeg=',SODIUM_BASE64_VARIANT_URLSAFE);
			return sodium_crypto_sign_verify_detached($sig, $string, $public_key);
		} catch (Exception $e) {
			log_message("Error","DCL: Error while checking signature: ".$e);
			return false;
		}
	}

	function last_upload($key, $message, $user_id) {
		$this->load->model('user_options_model');
		$dclrkey=$this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key'), $user_id)->result();
		$dclkey = json_decode($dclrkey[0]->option_value ?? '');
		$dclkey->call = $dclrkey[0]->option_key ?? '';
		$dclnewkey=$dclkey;

		if ($message == "Success") {
			$dclnewkey->last_sync=date("Y-m-d H:i:s");
			$this->user_options_model->set_option('dcl', 'dcl_key', array($dclkey->call => json_encode($dclnewkey)),  $user_id);
		}
		return "Updated";
	}
}
?>
