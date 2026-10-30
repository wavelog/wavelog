<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PSKReporter live reporting library.
 *
 * Builds and sends IPFIX (RFC 5101) datagrams as specified by
 * https://pskreporter.info/pskdev.html to report logged QSOs
 * (informationSource = 2, "From a Call Log") to report.pskreporter.info:4739.
 *
 * The packet is stateless: both record format descriptors are included in
 * every datagram, so no session cache is needed (one QSO = one datagram).
 */
class Pskreporter {
	const PSKREPORTER_HOST = 'report.pskreporter.info';
	const PSKREPORTER_PORT = 4739;
	const MAX_AGE_SECONDS = 3600; // don't report QSOs older than 1h

	/**
	 * Build a variable-length string field (1 length byte + ASCII data, max 254 bytes).
	 *
	 * @param string $s ASCII string to encode.
	 * @return string Binary field.
	 */
	private function varstring(string $s): string {
		$s = substr($s, 0, 254);
		return pack('C', strlen($s)) . $s;
	}

	/**
	 * Pad a binary chunk with NUL bytes to a multiple of 4 bytes.
	 *
	 * @param string $b Binary data.
	 * @return string Padded binary data.
	 */
	private function pad4(string $b): string {
		return str_pad($b, (4 * (int) ceil(strlen($b) / 4)), "\0");
	}

	/**
	 * Extract a single uppercase Maidenhead locator from a possibly
	 * comma-separated locator string ("JO30,JO31" -> "JO30").
	 *
	 * @param string|null $grid Locator string from the logbook.
	 * @return string|null Clean locator or NULL.
	 */
	private function clean_grid(?string $grid): ?string {
		$grid = strtoupper(trim(explode(',', (string) $grid)[0]));
		return ($grid !== '') ? $grid : null;
	}

	/**
	 * Build the complete IPFIX datagram for one QSO report.
	 *
	 * Layout: header, receiver template descriptor (3 fields), sender
	 * template descriptor (6 fields incl. senderLocator), receiver
	 * information record, one sender information record.
	 *
	 * @param object $qso QSO row as returned by Logbook_model::get_qso()
	 *                    (joins station_profile, so station_callsign /
	 *                    station_gridsquare / pskreporterrealtime included).
	 * @param string $software decodingSoftware string (e.g. "Wavelog 3.2.4").
	 * @return string Binary datagram.
	 */
	public function build_packet(object $qso, string $software): string {
		$receiver_callsign = strtoupper(trim((string) $qso->station_callsign));
		$receiver_locator = $this->clean_grid($qso->station_gridsquare ?? null);
		$sender_callsign = strtoupper(trim((string) $qso->COL_CALL));
		$sender_locator = $this->clean_grid($qso->COL_GRIDSQUARE ?? null);
		$freq_hz = (int) round((float) $qso->COL_FREQ); // COL_FREQ is stored in Hz
		$mode = strtoupper(trim((string) ($qso->COL_SUBMODE ?: $qso->COL_MODE)));
		$timestamp = strtotime((string) $qso->COL_TIME_ON);

		// Receiver information record: callsign, locator, decodingSoftware.
		// Length field includes header and padding (see spec example).
		$receiver_data = $this->varstring($receiver_callsign);
		$receiver_data .= $receiver_locator ? $this->varstring($receiver_locator) : "\x00";
		$receiver_data .= $this->varstring($software);
		$receiver_record = $this->pad4("\x99\x92\x00\x00" . $receiver_data);
		$receiver_record[2] = pack('n', strlen($receiver_record))[0];
		$receiver_record[3] = pack('n', strlen($receiver_record))[1];

		// Sender information record: callsign, freq (4B), mode,
		// informationSource (1B = 2, from call log), locator, flowStartSeconds (4B).
		// Length field includes header and padding (see spec example).
		$sender_data = $this->varstring($sender_callsign);
		$sender_data .= pack('N', $freq_hz);
		$sender_data .= $this->varstring($mode);
		$sender_data .= pack('C', 2);
		$sender_data .= $sender_locator ? $this->varstring($sender_locator) : "\x00";
		$sender_data .= pack('N', $timestamp);
		$sender_record = $this->pad4("\x99\x93\x00\x00" . $sender_data);
		$sender_record[2] = pack('n', strlen($sender_record))[0];
		$sender_record[3] = pack('n', strlen($sender_record))[1];

		// Receiver record format descriptor (callsign, locator, decodingSoftware)
		$template_receiver = hex2bin('00030024' . '999200030001'
			. '8002FFFF0000768F'
			. '8004FFFF0000768F'
			. '8008FFFF0000768F'
			. '0000');

		// Sender record format descriptor (callsign, freq, mode,
		// informationSource, senderLocator, flowStartSeconds)
		$template_sender = hex2bin('00020034' . '99930006'
			. '8001FFFF0000768F'
			. '800500040000768F'
			. '800AFFFF0000768F'
			. '800B00010000768F'
			. '8003FFFF0000768F'
			. '00960004');

		$body = $template_receiver . $template_sender . $receiver_record . $sender_record;

		// Header: version 10 (2B), total length (2B), transmission time (4B),
		// sequence number (4B, report count; one report per datagram, so use
		// time()), observation id (4B, stable per station).
		$seq = time();
		$obs_id = crc32($receiver_callsign) & 0xFFFFFFFF;
		return pack('n2N3', 10, strlen($body) + 16, time(), $seq, $obs_id) . $body;
	}

	/**
	 * Build and send the PSKReporter datagram for this QSO (caller has
	 * already checked the per-station enable flag in user_options).
	 * Fire-and-forget: any failure is logged and never surfaces in the
	 * QSO save flow.
	 *
	 * @param object|null $qso     QSO row as returned by Logbook_model::get_qso()->row()
	 *                             (joins station_profile, so station_callsign /
	 *                             station_gridsquare included).
	 * @param bool        $enabled Enable flag from user_options (option_type
	 *                             'pskreporter_realtime', option_name 'key_station_id',
	 *                             option_key = station id, value '1').
	 * @return bool True if a datagram was sent.
	 */
	public function maybe_send(?object $qso, bool $enabled): bool {
		if ($qso === null || !$enabled) {
			return false;
		}

		$timestamp = strtotime((string) $qso->COL_TIME_ON);
		if ($timestamp === false || abs(time() - $timestamp) > self::MAX_AGE_SECONDS) {
			return false;
		}

		if (empty($qso->COL_CALL) || empty($qso->COL_FREQ) || empty($qso->COL_MODE) || empty($qso->station_callsign)) {
			return false;
		}

		// IPFIX frequency field is 4 bytes -> max ~4.29 GHz; bands above can't be reported
		if ((float) $qso->COL_FREQ > 4294967295) {
			log_message('error', 'PSKReporter: frequency ' . $qso->COL_FREQ . ' Hz exceeds 4-byte IPFIX field, QSO not reported.');
			return false;
		}

		$software = 'Wavelog';
		$ci = &get_instance();
		if (isset($ci->optionslib)) {
			$version = $ci->optionslib->get_option('version');
			if ($version) {
				$software .= ' ' . $version;
			}
		}

		$packet = $this->build_packet($qso, $software);

		$sock = @stream_socket_client('udp://' . self::PSKREPORTER_HOST . ':' . self::PSKREPORTER_PORT, $errno, $errstr, 2);
		if ($sock === false) {
			log_message('error', 'PSKReporter: UDP connect failed: ' . $errstr . ' (' . $errno . ')');
			return false;
		}
		$sent = @fwrite($sock, $packet);
		fclose($sock);
		if ($sent === false || $sent !== strlen($packet)) {
			log_message('error', 'PSKReporter: UDP send failed.');
			return false;
		}
		return true;
	}
}
