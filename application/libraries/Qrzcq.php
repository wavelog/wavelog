<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the QRZcq.com Subscription based XML API.
*/


class Qrzcq {

	// Return session key
	public function session($username, $password) {
		// URL to the XML Source
		$ci = & get_instance();
		$xml_feed_url = 'https://ssl.qrzcq.com/xml/?username='.$username.';password='.urlencode($password).';agent=wavelog';

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

		if (isset($xml->Session->Key)) {
			$result = array( 0, (string) $xml->Session->Key);
		} else if (isset($xml->Session->Error)) {
			$result = array( 1, (string) $xml->Session->Error);
		} else {
			$result = array( 2, 'Unknown error');
		}

		return $result;
	}

	// Set Session Key session.
	public function set_session($username, $password) {

		$ci = & get_instance();

		// URL to the XML Source
		$xml_feed_url = 'https://ssl.qrzcq.com/xml/?username='.$username.';password='.urlencode($password).';agent=wavelog';

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

		$ci->session->set_userdata('qrzcq_session_key', $key);

		return true;
	}


	public function search($callsign, $key, $reduced = false) {
		$data = null;
		$ci = & get_instance();
		try {
			// URL to the XML Source
			$xml_feed_url = 'https://ssl.qrzcq.com/xml/?s=' . $key . ';callsign=' . $callsign . '';

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
			if ($httpcode != 200) return $data['error'] = 'Problems with qrzcq.com communication'; // Exit function if no 200. If request fails, 0 is returned

			// Create XML object
			$xml = simplexml_load_string($xml);
			if (!empty($xml->Session->Error)) {
				return $data['error'] = $xml->Session->Error;
			}

			// Return Required Fields
			$data['callsign'] = (string)$xml->Callsign->call;

			$data['name'] = (string)$xml->Callsign->name;

			// we always give back the name, no matter if reduced data or not
			$data['name'] = trim($data['name']);

			// Sanitize gridsquare to allow only up to 8 characters
			$unclean_gridsquare = (string)$xml->Callsign->locator; // Get the gridsquare from QRZ convert to string
			$clean_gridsquare = strlen($unclean_gridsquare) > 8 ? substr($unclean_gridsquare,0,8) : $unclean_gridsquare; // Trim gridsquare to 8 characters max

			if ($reduced == false) {

				$data['gridsquare'] = $clean_gridsquare;
				$data['city'] 		= (string)$xml->Callsign->city;
				$data['lat'] 		= (string)$xml->Callsign->latitude;
				$data['long'] 		= (string)$xml->Callsign->longitude;
				$data['dxcc'] 		= (string)$xml->Callsign->dxcc;
				$data['state'] 		= (string)$xml->Callsign->state;
				$data['iota'] 		= (string)$xml->Callsign->iota;
				$data['image'] 		= (string)$xml->Callsign->qslpic;
				$data['ituz'] 		= (string)$xml->Callsign->itu;
				$data['cqz'] 		= (string)$xml->Callsign->cq;
				$data['continent'] 	= (string)$xml->Callsign->continent;

				if ($xml->Callsign->country == "United States") {
					$data['us_county'] = (string)$xml->Callsign->county;
				} else {
					$data['us_county'] = null;
					$data['county']    = (string)$xml->Callsign->county;
				}

			} else {

				$data['gridsquare'] = '';
				$data['city'] 	= '';
				$data['lat'] 	= '';
				$data['long'] 	= '';
				$data['dxcc'] 	= '';
				$data['state'] 	= '';
				$data['iota'] 	= '';
				$data['image'] = (string)$xml->Callsign->qslpic;
				$data['county'] = '';
				$data['us_county'] = '';
				$data['ituz'] = '';
				$data['cqz'] = '';

			}
		} finally {

			return $data;
		}
	}
}
