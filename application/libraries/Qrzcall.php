<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controls the interaction with the QRZCALL.EU Premium XML API.

	QRZCALL.EU is a QRZ-compatible callsign database. Its XML feed
	(/v1/pub/callsign_xml.php) returns the same field names as QRZ.com so this
	library mirrors the QRZ.com integration almost identically. The only real
	difference is the authentication flow: QRZCALL.EU issues a JWT bearer
	token instead of a session key, obtained from a username/password login.

	Access tier:
		The /v1/pub/callsign_xml.php endpoint requires a Data or Extra
		subscription on QRZCALL.EU (ADMIN role also passes the gate).

	API docs / sign-up:
		https://qrzcall.eu/
*/

class Qrzcall {

	public $callbookname = 'QRZCALL';

	// ---- Endpoints ----------------------------------------------------------
	// Auth endpoint issues a JWT for the given callsign + password.
	// XML endpoint accepts the JWT as Authorization: Bearer <jwt>.
	const AUTH_URL = 'https://api.qrzcall.eu/v1/auth/login.php';
	const XML_URL  = 'https://api.qrzcall.eu/v1/pub/callsign_xml.php';

	// Return JWT bearer token (treated as a "session key" by the Callbook layer
	// so that the existing cache + retry logic for QRZ-style providers works
	// without modification).
	public function session($username, $password) {
		$ci = & get_instance();
		$payload = json_encode([
			'callsign' => strtoupper(trim($username)),
			'password' => $password,
		]);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::AUTH_URL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$ci->optionslib->get_option('version'));
		$raw = curl_exec($ch);
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http !== 200 || !$raw) {
			log_message('debug', 'QRZCALL.EU auth failed for '.$username.' (HTTP '.$http.')');
			return ''; // empty key — Callbook layer treats this as an auth failure
		}

		$json = json_decode($raw, true);
		return (string) ($json['token'] ?? '');
	}

	// Set session in CI's session store. Mirrors Qrz::set_session for parity.
	public function set_session($username, $password) {
		$ci = & get_instance();
		$key = $this->session($username, $password);
		$ci->session->set_userdata('qrzcall_session_key', $key);
		return true;
	}

	public function search($callsign, $key, $use_fullname = false, $reduced = false) {
		$data = null;
		$ci = & get_instance();
		try {
			$url = self::XML_URL . '?callsign=' . urlencode($callsign);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$key]);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$ci->optionslib->get_option('version'));
			$xml = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			// Treat expired/invalid JWT identically to QRZ.com's "Invalid session key"
			// so Callbook::_qrzcall() will re-auth and retry transparently.
			if ($httpcode == 401) {
				log_message('debug', 'QRZCALL.EU search 401 for '.$callsign.' — JWT likely expired');
				$data['error'] = 'Invalid session key';
				return $data;
			}

			if ($httpcode == 404) {
				$data['error'] = 'Callsign not found';
				return $data;
			}

			if ($httpcode != 200) {
				log_message('debug', 'QRZCALL.EU search for '.$callsign.' returned HTTP '.$httpcode);
				$data['error'] = 'Problems with qrzcall.eu communication';
				return $data;
			}

			$xml = simplexml_load_string($xml);
			if (!$xml || !isset($xml->Callsign)) {
				$data['error'] = isset($xml->Error) ? (string)$xml->Error : 'Empty response';
				return $data;
			}

			// QRZCALL.EU emits the same field names as QRZ.com's XML API.
			// We mirror the Qrz::search() field mapping exactly so downstream
			// Wavelog code (QSO entry form, etc.) needs no changes.
			$data['callsign']  = (string)$xml->Callsign->call;
			$data['xref']      = (string)$xml->Callsign->xref;
			$data['aliases']   = (string)$xml->Callsign->aliases;
			$data['dxcc']      = (string)$xml->Callsign->dxcc;
			$data['fname']     = (string)$xml->Callsign->fname;
			$data['name_last'] = (string)$xml->Callsign->name;
			$data['addr1']     = (string)$xml->Callsign->addr1;
			$data['addr2']     = (string)$xml->Callsign->addr2;
			$data['state']     = (string)$xml->Callsign->state;
			$data['zip']       = (string)$xml->Callsign->zip;
			$data['country']   = (string)$xml->Callsign->country;
			$data['ccode']     = (string)$xml->Callsign->ccode;
			$data['lat']       = (string)$xml->Callsign->lat;
			$data['lon']       = (string)$xml->Callsign->lon;
			$data['grid']      = (string)$xml->Callsign->grid;
			$data['county']    = (string)$xml->Callsign->county;
			$data['fips']      = (string)$xml->Callsign->fips;
			$data['land']      = (string)$xml->Callsign->land;
			$data['efdate']    = (string)$xml->Callsign->efdate;
			$data['expdate']   = (string)$xml->Callsign->expdate;
			$data['p_call']    = (string)$xml->Callsign->p_call;
			$data['class']     = (string)$xml->Callsign->class;
			$data['codes']     = (string)$xml->Callsign->codes;
			$data['qslmgr']    = (string)$xml->Callsign->qslmgr;
			$data['email']     = (string)$xml->Callsign->email;
			$data['url']       = (string)$xml->Callsign->url;
			$data['u_views']   = (string)$xml->Callsign->views;       // QRZCALL exposes as <views>
			$data['bio']       = (string)$xml->Callsign->bio;
			$data['biodate']   = (string)$xml->Callsign->biodate;
			$data['image']     = (string)$xml->Callsign->image;
			$data['imageinfo'] = (string)$xml->Callsign->imageinfo;
			$data['serial']    = (string)$xml->Callsign->serial;
			$data['moddate']   = (string)$xml->Callsign->moddate;
			$data['MSA']       = (string)$xml->Callsign->MSA;
			$data['AreaCode']  = (string)$xml->Callsign->AreaCode;
			$data['TimeZone']  = (string)$xml->Callsign->TimeZone;
			$data['GMTOffset'] = (string)$xml->Callsign->GMTOffset;
			$data['DST']       = (string)$xml->Callsign->DST;
			$data['eqsl']      = (string)$xml->Callsign->eqsl;
			$data['mqsl']      = (string)$xml->Callsign->mqsl;
			$data['cqzone']    = (string)$xml->Callsign->cqzone;
			$data['ituzone']   = (string)$xml->Callsign->ituzone;
			$data['born']      = (string)$xml->Callsign->born;
			$data['user']      = (string)$xml->Callsign->user;
			$data['lotw']      = (string)$xml->Callsign->lotw;
			$data['iota']      = (string)$xml->Callsign->iota;
			$data['geoloc']    = (string)$xml->Callsign->geoloc;
			$data['attn']      = (string)$xml->Callsign->attn;
			$data['nickname']  = (string)$xml->Callsign->nickname;
			$data['name_fmt']  = (string)$xml->Callsign->name_fmt;

			// Build legacy 'name' for backward compatibility (same as Qrz.php)
			if ($use_fullname === true) {
				$data['name'] = $data['fname'].' '.$data['name_last'];
			} else {
				$data['name'] = $data['fname'];
			}
			$data['name'] = trim($data['name']);

			// Trim grid to 8 chars (max useful precision) — same rule as QRZ provider
			$grid = $data['grid'];
			$data['gridsquare'] = strlen($grid) > 8 ? substr($grid, 0, 8) : $grid;

			// Backward-compat aliases — same as QRZ provider
			$data['city']  = $data['addr2'];
			$data['long']  = $data['lon'];
			$data['ituz']  = $data['ituzone'];
			$data['cqz']   = $data['cqzone'];

			$data['us_county'] = ($data['country'] === 'United States') ? $data['county'] : null;

			if ($reduced === true) {
				$data['gridsquare'] = '';
				$data['city']       = '';
				$data['lat']        = '';
				$data['long']       = '';
				$data['lon']        = '';
				$data['dxcc']       = '';
				$data['state']      = '';
				$data['iota']       = '';
				$data['us_county']  = '';
				$data['ituz']       = '';
				$data['cqz']        = '';
				$data['ituzone']    = '';
				$data['cqzone']     = '';
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
