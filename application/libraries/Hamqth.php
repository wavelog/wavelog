<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the HamQTH Callbook API
*/


class Hamqth {

	// Return session key
	public function session($username, $password) {
		// URL to the XML Source
		$xml_feed_url = 'https://www.hamqth.com/xml.php?u='.$username.'&p='.urlencode($password);

		// CURL Functions
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$xml = curl_exec($ch);
		curl_close($ch);

		// Create XML object
		$xml = simplexml_load_string($xml);

		// Return Session Key
		return (string) $xml->session->session_id;
	}

	// Set Session Key session.
	public function set_session($username, $password) {

		$ci = & get_instance();

		// URL to the XML Source
		$xml_feed_url = 'https://www.hamqth.com/xml.php?u='.$username.'&p='.urlencode($password);

		// CURL Functions
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$xml = curl_exec($ch);
		curl_close($ch);

		// Create XML object
		$xml = simplexml_load_string($xml);

		$key = (string) $xml->session->session_id;

		$ci->session->set_userdata('hamqth_session_key', $key);

		return true;
	}


	public function search($callsign, $key, $reduced = false) {
	    $data = null;
        try {
            // URL to the XML Source
            $xml_feed_url = 'https://www.hamqth.com/xml.php?id=' . $key . '&callsign=' . $callsign . '&prg=wavelog';

            // CURL Functions
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $xml = curl_exec($ch);
            curl_close($ch);

            // Create XML object
            $xml = simplexml_load_string($xml);
            if (!empty($xml->session->error)) return $data['error'] = $xml->session->error;

			// we always want to return name and callsign
			$data['callsign'] 	= (string)$xml->search->callsign;
			$data['name'] 		= (string)$xml->search->nick;

			// only return certain data of a callsign which does not contain a pre- or suffix (see https://github.com/wavelog/wavelog/issues/452)
			if ($reduced == false) {

				$data['gridsquare'] = (string)$xml->search->grid;
				$data['city'] 		= (string)$xml->search->adr_city;
				$data['lat'] 		= (string)$xml->search->latitude;
				$data['long'] 		= (string)$xml->search->longitude;
				$data['dxcc'] 		= (string)$xml->search->adif;
				$data['iota'] 		= (string)$xml->search->iota;
				$data['image'] 		= (string)$xml->search->picture;
				$data['state'] 		= (string)$xml->search->us_state;
				$data['error'] 		= (string)$xml->session->error;
				$data['ituz'] 		= (string)$xml->search->itu;
				$data['cqz'] 		= (string)$xml->search->cq;

				if ($xml->search->country == "United States") {
					$data['us_county'] = (string)$xml->search->us_county;
				} else {
					$data['us_county'] = null;
				}

			} else {

				$data['gridsquare'] = '';
				$data['city'] 		= '';
				$data['lat'] 		= '';
				$data['long'] 		= '';
				$data['dxcc'] 		= '';
				$data['iota'] 		= '';
				$data['image'] 		= (string)$xml->search->picture;
				$data['state'] 		= '';
				$data['error'] 		= (string)$xml->session->error;
				$data['ituz'] 		= '';
				$data['cqz'] 		= '';

				$data['us_county'] 	= '';

			}
        } finally {
            return $data;
        }
	}
}
