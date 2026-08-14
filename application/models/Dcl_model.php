<?php

class Dcl_model extends CI_Model {

	/*
	|--------------------------------------------------------------------------
	| Function: dcl_keys
	|--------------------------------------------------------------------------
	|
	| Returns all dcl_keys for a selected user via the $user_id parameter
	|
	*/
	function dcl_keys($user_id) {
		$this->load->model('user_options_model');
		return $this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key'), $user_id)->result();
	}

	/*
	|--------------------------------------------------------------------------
	| Function: find_key
	|--------------------------------------------------------------------------
	|
	| Returns all dcl_keys for a selected user via the $user_id parameter which match also to the $callsign
	|
	*/

	function find_key($callsign, $user_id) {
		$this->load->model('user_options_model');
		$userkeys=$this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key','option_key'=>'key'), $user_id)->result();
		foreach ($userkeys as $raw_key) {
			$skey=json_decode($raw_key->option_value, true);
			if (isset($skey['Callsigns']) && is_array($skey['Callsigns'])) {
				foreach ($skey['Callsigns'] as $item) {
					if (isset($item['callsign']) && strtoupper($item['callsign']) === strtoupper($callsign)) {
						$key['token']=$skey['UserKeys']['token'];
						$key['vf']=strtotime($item['startDate']);
						$key['vt']=strtotime($item['endDate'] ?? '2099-12-31');
						return $key;
					}
				}
			}
		}
		return '';
	}

	function store_key($key) {
		$this->load->model('user_options_model');
		$this->user_options_model->set_option('dcl', 'dcl_key', array('key'=>$key));
	}

	function delete_key() {
		$this->user_options_model->del_option('dcl', 'dcl_key',array('option_key' => 'key'));
	}

	function get_dcl_info($token) {
		if (($token ?? '') != '') {
			try {
				$dclUrl = 'https://api.dcl.darc.de/api/v1/get-userinfo/'.$token;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $dclUrl);
				curl_setopt($ch, CURLOPT_HEADER, array('Content-Type: application/json' , "Authorization: Bearer ".$token));
				curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog DCL Connector');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				$rawdcldata = curl_exec($ch);
				if (strlen($rawdcldata)>100) {
					$dcldata=json_decode($rawdcldata);
					return $dcldata;
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
}
?>
