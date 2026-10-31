<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the HamQTH Callbook API
*/


class Hamqth {

	public $callbookname = 'HamQTH';

	/**
	 * Get a HamQTH session key
	 *
	 * @param string $username HamQTH username
	 * @param string $password HamQTH password
	 * @return string|false Session key, empty string on login error, false on transport/parse error
	 */
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
		if(curl_errno($ch)) {
			log_message('error', 'Hamqth query failed: '.curl_strerror(curl_errno($ch))." (".curl_errno($ch).")");
			return false;
		}

		// Create XML object
		$xml = simplexml_load_string($xml);
		if ($xml === false) {
			log_message('error', 'Hamqth session: could not parse XML response');
			return false;
		}

		$root = $xml->children('https://www.hamqth.com');
		if ($root->count() == 0) {
			$root = $xml;
		}

		// Return Session Key
		return (string) $root->session->session_id;
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

		// Create XML object
		$xml = simplexml_load_string($xml);

		$key = (string) $xml->session->session_id;

		$ci->session->set_userdata('hamqth_session_key', $key);

		return true;
	}


	/**
	 * Search a callsign at HamQTH
	 *
	 * @param string $callsign Callsign to look up
	 * @param string $key      HamQTH session key
	 * @param bool   $reduced  Return reduced dataset (no location data) for prefix/suffix calls
	 * @return array|null Callbook data incl. 'source' key, null on fatal parse error
	 */
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

            // Create XML object
            $xml = simplexml_load_string($xml);
            if ($xml === false) {
                $data['error'] = 'Could not parse HamQTH response';
                return $data;
            }

            $root = $xml->children('https://www.hamqth.com');
            if ($root->count() == 0) {
                $root = $xml;
            }
            if (!empty($root->session->error)) return $data['error'] = (string) $root->session->error;

			// we always want to return name and callsign
			$data['callsign'] 	= (string)$root->search->callsign;
			$data['name'] 		= (string)$root->search->nick;
			$data['email'] 		= (string)$root->search->email;

			// only return certain data of a callsign which does not contain a pre- or suffix (see https://github.com/wavelog/wavelog/issues/452)
			if ($reduced == false) {

				$data['gridsquare'] = (string)$root->search->grid;
				$data['city'] 		= (string)$root->search->adr_city;
				$data['lat'] 		= (string)$root->search->latitude;
				$data['long'] 		= (string)$root->search->longitude;
				$data['dxcc'] 		= (string)$root->search->adif;
				$data['iota'] 		= (string)$root->search->iota;
				$data['image'] 		= (string)$root->search->picture;
				$data['state'] 		= (string)$root->search->us_state;
				$data['error'] 		= (string)$root->session->error;
				$data['ituz'] 		= (string)$root->search->itu;
				$data['cqz'] 		= (string)$root->search->cq;
				$data['darc_dok'] = (string)$root->search->dok;

				if ($root->search->country == "United States") {
					$data['us_county'] = (string)$root->search->us_county;
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
				$data['image'] 		= (string)$root->search->picture;
				$data['state'] 		= '';
				$data['error'] 		= (string)$root->session->error;
				$data['ituz'] 		= '';
				$data['cqz'] 		= '';
				$data['darc_dok'] = '';

				$data['us_county'] 	= '';

			}
		} finally {
			$data['source'] = $this->sourcename();
			return $data;
		}
	}

	public function sourcename() {
		return $this->callbookname;
	}
}
