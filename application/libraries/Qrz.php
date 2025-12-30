<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the QRZ.com Subscription based XML API.
*/


class Qrz {

	// Return session key
	public function session($username, $password) {
		// URL to the XML Source
		$ci = & get_instance();
		$xml_feed_url = 'https://xmldata.qrz.com/xml/current/?username='.$username.';password='.urlencode($password).';agent=wavelog';

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
		return (string) $xml->Session->Key;
	}

	// Set Session Key session.
	public function set_session($username, $password) {

		$ci = & get_instance();

		// URL to the XML Source
		$xml_feed_url = 'https://xmldata.qrz.com/xml/current/?username='.$username.';password='.urlencode($password).';agent=wavelog';

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

		$ci->session->set_userdata('qrz_session_key', $key);

		return true;
	}


	public function search($callsign, $key, $use_fullname = false, $reduced = false) {
		$data = null;
		$ci = & get_instance();
		try {
			// URL to the XML Source
			$xml_feed_url = 'https://xmldata.qrz.com/xml/current/?s=' . $key . ';callsign=' . $callsign . '';

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
			if ($httpcode != 200) {
				$message = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				log_message('debug', 'QRZ.com search for callsign: ' . $callsign . ' returned message: ' . $message . ' HTTP code: ' . $httpcode);
				curl_close($ch);
				return $data['error'] = 'Problems with qrz.com communication'; // Exit function if no 200. If request fails, 0 is returned
			}
			curl_close($ch);
			// Create XML object
			$xml = simplexml_load_string($xml);
			if (!empty($xml->Session->Error)) {
				return $data['error'] = $xml->Session->Error;
			}

			// Map all QRZ XML fields according to API specification
			$data['callsign'] = (string)$xml->Callsign->call;
			$data['xref'] = (string)$xml->Callsign->xref;
			$data['aliases'] = (string)$xml->Callsign->aliases;
			$data['dxcc'] = (string)$xml->Callsign->dxcc;
			$data['fname'] = (string)$xml->Callsign->fname;
			$data['name_last'] = (string)$xml->Callsign->name;
			$data['addr1'] = (string)$xml->Callsign->addr1;
			$data['addr2'] = (string)$xml->Callsign->addr2;
			$data['state'] = (string)$xml->Callsign->state;
			$data['zip'] = (string)$xml->Callsign->zip;
			$data['country'] = (string)$xml->Callsign->country;
			$data['ccode'] = (string)$xml->Callsign->ccode;
			$data['lat'] = (string)$xml->Callsign->lat;
			$data['lon'] = (string)$xml->Callsign->lon;
			$data['grid'] = (string)$xml->Callsign->grid;
			$data['county'] = (string)$xml->Callsign->county;
			$data['fips'] = (string)$xml->Callsign->fips;
			$data['land'] = (string)$xml->Callsign->land;
			$data['efdate'] = (string)$xml->Callsign->efdate;
			$data['expdate'] = (string)$xml->Callsign->expdate;
			$data['p_call'] = (string)$xml->Callsign->p_call;
			$data['class'] = (string)$xml->Callsign->class;
			$data['codes'] = (string)$xml->Callsign->codes;
			$data['qslmgr'] = (string)$xml->Callsign->qslmgr;
			$data['email'] = (string)$xml->Callsign->email;
			$data['url'] = (string)$xml->Callsign->url;
			$data['u_views'] = (string)$xml->Callsign->u_views;
			$data['bio'] = (string)$xml->Callsign->bio;
			$data['biodate'] = (string)$xml->Callsign->biodate;
			$data['image'] = (string)$xml->Callsign->image;
			$data['imageinfo'] = (string)$xml->Callsign->imageinfo;
			$data['serial'] = (string)$xml->Callsign->serial;
			$data['moddate'] = (string)$xml->Callsign->moddate;
			$data['MSA'] = (string)$xml->Callsign->MSA;
			$data['AreaCode'] = (string)$xml->Callsign->AreaCode;
			$data['TimeZone'] = (string)$xml->Callsign->TimeZone;
			$data['GMTOffset'] = (string)$xml->Callsign->GMTOffset;
			$data['DST'] = (string)$xml->Callsign->DST;
			$data['eqsl'] = (string)$xml->Callsign->eqsl;
			$data['mqsl'] = (string)$xml->Callsign->mqsl;
			$data['cqzone'] = (string)$xml->Callsign->cqzone;
			$data['ituzone'] = (string)$xml->Callsign->ituzone;
			$data['born'] = (string)$xml->Callsign->born;
			$data['user'] = (string)$xml->Callsign->user;
			$data['lotw'] = (string)$xml->Callsign->lotw;
			$data['iota'] = (string)$xml->Callsign->iota;
			$data['geoloc'] = (string)$xml->Callsign->geoloc;
			$data['attn'] = (string)$xml->Callsign->attn;
			$data['nickname'] = (string)$xml->Callsign->nickname;
			$data['name_fmt'] = (string)$xml->Callsign->name_fmt;

			// Build legacy 'name' field for backward compatibility
			if ($use_fullname === true) {
				$data['name'] =  $data['fname']. ' ' . $data['name_last'];
			} else {
				$data['name'] = $data['fname'];
			}
			// we always give back the name, no matter if reduced data or not
			$data['name'] = trim($data['name']);

			// Sanitize gridsquare to allow only up to 8 characters
			$unclean_gridsquare = $data['grid'];
			$clean_gridsquare = strlen($unclean_gridsquare) > 8 ? substr($unclean_gridsquare,0,8) : $unclean_gridsquare;

			// Map fields for backward compatibility with existing code
			$data['gridsquare'] = $clean_gridsquare;
			$data['city'] = $data['addr2'];
			$data['long'] = $data['lon'];
			$data['ituz'] = $data['ituzone'];
			$data['cqz'] = $data['cqzone'];

			if ($data['country'] == "United States") {
				$data['us_county'] = $data['county'];
			} else {
				$data['us_county'] = null;
			}

			if ($reduced == true) {
				// Clear location-specific fields for reduced mode
				$data['gridsquare'] = '';
				$data['city'] = '';
				$data['lat'] = '';
				$data['long'] = '';
				$data['lon'] = '';
				$data['dxcc'] = '';
				$data['state'] = '';
				$data['iota'] = '';
				$data['us_county'] = '';
				$data['ituz'] = '';
				$data['cqz'] = '';
				$data['ituzone'] = '';
				$data['cqzone'] = '';
			}
		} finally {
			$data['source'] = 'QRZ';
			return $data;
		}
	}
}
