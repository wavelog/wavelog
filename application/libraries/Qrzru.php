<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the QRZ.ru based XML API.
*/


class Qrzru {

	// Return session key
	public function session($username, $password) {
		// URL to the XML Source
		$ci = & get_instance();
		$xml_feed_url = 'https://api.qrz.ru/login?u='.$username.'&p='.urlencode($password) . '&agent=wavelog';

		// CURL Functions
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$ci->optionslib->get_option('version'));
		$xml = curl_exec($ch);
		curl_close($ch);

		// Create XML object
		$xml = simplexml_load_string($xml);

		// Return Session Key
		return (string) $xml->Session->session_id;
	}

	// Set Session Key session.
	public function set_session($username, $password) {

		$ci = & get_instance();

		// URL to the XML Source
		$xml_feed_url = 'https://api.qrz.ru/login?u='.$username.'&p='.urlencode($password).';agent=wavelog';

		// CURL Functions
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$ci->optionslib->get_option('version'));
		$xml = curl_exec($ch);
		curl_close($ch);

		// Create XML object
		$xml = simplexml_load_string($xml);

		$key = (string) $xml->Session->Key;

		$ci->session->set_userdata('qrzru_session_key', $key);

		return true;
	}


	public function search($callsign, $key, $reduced = false) {
		$data = null;
		$ci = & get_instance();
		try {
			// URL to the XML Source
			$xml_feed_url = 'https://api.qrz.ru/callsign?id=' . $key . '&callsign=' . $callsign . '';

			// CURL Functions
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$ci->optionslib->get_option('version'));
			$xml = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// Create XML object
			$xml = simplexml_load_string($xml);
			if (!empty($xml->session->error)) {
				return $data['error'] = (string)$xml->session->error;
			}

			// Return Required Fields
			$data['callsign'] = (string)$xml->Callsign->call;

			$data['name'] = trim((string)($xml->Callsign->name ?: $xml->Callsign->ename) . ' ' . (string)($xml->Callsign->surname ?: $xml->Callsign->esurname));

			if ($reduced == false) {
				$data['city'] 		= (string)$xml->Callsign->city;
				$data['gridsquare'] = (string)$xml->Callsign->qthloc;
				$data['lat'] 	= (string)$xml->Callsign->latitude;
				$data['long'] 	= (string)$xml->Callsign->longitude;
				$data['dxcc'] 	= '';
				$data['state'] = !empty((string)$xml->Callsign->state) ? substr((string)$xml->Callsign->state, 0, 2) : '';
				$data['iota'] 	= '';
				$data['county'] = (string)$xml->Callsign->state;
				$data['ituz'] 	= (string)$xml->Callsign->itu_zone;
				$data['cqz'] 	= (string)$xml->Callsign->cq_zone;
			} else {
				$data['gridsquare'] = '';
				$data['city'] 	= '';
				$data['lat'] 	= '';
				$data['long'] 	= '';
				$data['dxcc'] 	= '';
				$data['state'] 	= '';
				$data['iota'] 	= '';
				$data['county'] = '';
				$data['ituz'] = '';
				$data['cqz'] = '';
			}
		} finally {
			return $data;
		}
	}
}
