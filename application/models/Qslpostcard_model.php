<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Wavelog\Label\FPDF;

class Qslpostcard_model extends CI_Model {

    const MAX_BG_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB

    function __construct() {
        $this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);
    }

    public function list_templates() {
		$sql = "SELECT id, name FROM qsl_postcard_templates WHERE user_id = ? ORDER BY updated_at DESC";
		return $this->db->query($sql, [$this->session->userdata('user_id')])->result_array();
    }

    public function get_template($id) {
		$sql = "SELECT * FROM qsl_postcard_templates WHERE id = ? AND user_id = ?";
		return $this->db->query($sql, [$id, $this->session->userdata('user_id')])->row_array();
    }

    public function save_template($id, $name, $layout_json, $preview_image = null) {
        $row = [
            'name' => $name,
            'layout_json' => $layout_json,
			'user_id' => $this->session->userdata('user_id'),
        ];

        if ($preview_image !== null) {
            $row['preview_image'] = $preview_image;
        }

        if ($id > 0) {
            $this->db->where('id', $id)->where('user_id', $this->session->userdata('user_id'))->update('qsl_postcard_templates', $row);
            return $id;
        } else {
            $row['orientation'] = 'landscape';
            $row['width_in']  = 6.00;
            $row['height_in'] = 4.00;
            $this->db->insert('qsl_postcard_templates', $row);
            return (int)$this->db->insert_id();
        }
    }

    // TODO: Remove
    // v1 demo: fetch last N QSOs from the logbook table.
    // You will adjust table/column names based on your schema.
    public function get_sample_qsos($limit = 25) {
        $table = $this->config->item('table_name');

        // Scope to the logged-in user's stations only
        $sql = "SELECT " . $table . ".*
            FROM " . $table . "
            INNER JOIN station_profile ON station_profile.station_id = " . $table . ".station_id
            WHERE station_profile.user_id = ?
            ORDER BY " . $table . ".COL_TIME_ON DESC
            LIMIT " . (int)$limit;

        $q = $this->db->query($sql, [$this->session->userdata('user_id')]);

        if (!$q) {
            $db_error = $this->db->error();
            throw new Exception('DB query failed in get_sample_qsos(): ' . json_encode($db_error));
        }

        $rows = $q->result_array();

        if (!empty($rows)) {
            log_message('debug', 'QSLPOSTCARD sample row keys: ' . implode(', ', array_keys($rows[0])));
        } else {
            log_message('debug', 'QSLPOSTCARD get_sample_qsos() returned 0 rows');
        }

        return $rows;
    }

    public function resolve_address($callsign) {
		if (!$this->load->is_loaded('callbook')) {
			$this->load->library('callbook');
		}

        $callsign = strtoupper(trim($callsign));
        if (empty($callsign)) return null;

        // cache first
        $cache = $this->cache->get('callbook_cache_' . md5($callsign));
        if ($cache) {
			return json_decode($cache['address_json'], true);
		}

		$callbookData = $this->callbook->getCallbookData($callsign);

		$adr = $this->returnAddressFromXml($callbookData);

        if ($this->is_mailable_address($adr)) {
            $this->cache_address($callsign, 'callbook', $adr);
            return $adr;
        }

        return null;
    }

	private function returnAddressFromXml($callbookData) {
		$name = trim($callbookData['name'] ?? '');
		if ($name === '') $name = trim($callbookData['nickname'] ?? '');
		if ($name === '') $name = $callsign;

		return [
			'name'    => $name,
			'addr1'   => $callbookData['addr1'] ?? '',
			'addr2'   => $callbookData['addr2'] ?? '',
			'city'    => $callbookData['city'] ?? '',
			'state'   => $callbookData['state'] ?? '',
			'zip'     => $callbookData['zip'] ?? '',
			'country' => $callbookData['country'] ?? '',
		];
	}

    private function is_mailable_address($callbookData) {
        if (!is_array($callbookData)) return false;

        $name    = trim($callbookData['name'] ?? '');
        $addr1   = trim($callbookData['addr1'] ?? '');
        $city    = trim($callbookData['city'] ?? '');
        $state   = trim($callbookData['state'] ?? '');
        $zip     = trim($callbookData['zip'] ?? '');
        $country = trim($callbookData['country'] ?? '');

        // Must at least have street line plus either
        // city/state/zip info or country
        if (empty($addr1)) {
            return false;
        }

        $has_locality = !empty($city) || !empty($state) || !empty($zip);
        $has_country  = !empty($country);

        return $has_locality || $has_country;
    }

    private function cache_address($callsign, $source, $addr) {
        $addr['source'] = $source; // TODO: this is duped.. check if we can reduce this
        $data = [
            'callsign'     => $callsign,
            'source'       => $source,
            'address_json' => json_encode($addr, JSON_UNESCAPED_SLASHES),
        ];

        $this->cache->save('callbook_cache_' . md5($callsign), $data, 60 * 60 * 24 * 20); // cache for 20 days
    }

    private function normalize_qso_datetime($qso) {
        $rawDate = trim($qso['COL_QSO_DATE'] ?? $qso['qso_date'] ?? '');
        $rawTime = trim($qso['COL_TIME_ON'] ?? $qso['time_on'] ?? '');

        // Case 1: COL_TIME_ON already contains a full datetime
        if ($rawTime !== '') {
            $dt = $this->try_parse_datetime($rawTime);
            if ($dt !== null) {
                return $dt;
            }
        }

        // Case 2: Separate date + time fields
        if ($rawDate !== '') {
            $dateDigits = preg_replace('/[^0-9]/', '', $rawDate);
            $timeDigits = preg_replace('/[^0-9]/', '', $rawTime);

            if (strlen($dateDigits) >= 8) {
                $yyyy = substr($dateDigits, 0, 4);
                $mm   = substr($dateDigits, 4, 2);
                $dd   = substr($dateDigits, 6, 2);

                if (strlen($timeDigits) >= 6) {
                    $hh = substr($timeDigits, 0, 2);
                    $mi = substr($timeDigits, 2, 2);
                    $ss = substr($timeDigits, 4, 2);
                } elseif (strlen($timeDigits) >= 4) {
                    $hh = substr($timeDigits, 0, 2);
                    $mi = substr($timeDigits, 2, 2);
                    $ss = '00';
                } else {
                    $hh = '00';
                    $mi = '00';
                    $ss = '00';
                }

                $str = sprintf('%s-%s-%s %s:%s:%s', $yyyy, $mm, $dd, $hh, $mi, $ss);
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $str, new DateTimeZone('UTC'));
                if ($dt !== false) {
                    return $dt;
                }
            }
        }

        return null;
    }

    private function try_parse_datetime($raw) {
        $raw = trim($raw);
        if ($raw === '') return null;

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'YmdHis',
            'YmdHi',
            'Ymd',
        ];

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $raw, new DateTimeZone('UTC'));
            if ($dt !== false) {
                return $dt;
            }
        }

        // final fallback: strip to digits and try compact formats
        $digits = preg_replace('/[^0-9]/', '', $raw);

        foreach (['YmdHis', 'YmdHi', 'Ymd'] as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $digits, new DateTimeZone('UTC'));
            if ($dt !== false) {
                return $dt;
            }
        }

        return null;
    }

    private function http_get($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Wavelog-QSLPostcard/1.0',
        ]);
        $out = curl_exec($ch);
        return $out ?: null;
    }

    private function xml_tag($xml, $tag) {
        if (preg_match('/<' . $tag . '>(.*?)<\/' . $tag . '>/is', $xml, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    // --- PDF render (FPDF) ---
    public function render_pdf_from_layout($layout, $qsos, $mark_sent = false, $background = null, $noaddress = false) {
        $candidatePaths = [
            FCPATH . 'src/Label/fpdf.php',
            APPPATH . 'third_party/fpdf/fpdf.php',
            FCPATH . 'application/third_party/fpdf/fpdf.php',
        ];

        $fpdfPath = null;
        foreach ($candidatePaths as $path) {
            if (file_exists($path)) {
                $fpdfPath = $path;
                break;
            }
        }

        if (!$fpdfPath) {
            throw new Exception('FPDF not found. Checked: ' . implode(' | ', $candidatePaths));
        }

        require_once($fpdfPath);

        $w_mm = 152.4; // 6 in
        $h_mm = 101.6; // 4 in

        $pdf = new FPDF('L', 'mm', [$w_mm, $h_mm]);
        $pdf->SetAutoPageBreak(false);

        $cal = $layout['calibration'] ?? ['offset_x_in' => 0, 'offset_y_in' => 0];
        $ox = (float)($cal['offset_x_in'] ?? 0);
        $oy = (float)($cal['offset_y_in'] ?? 0);

        // Template options (see layout.options). When qsos_per_card > 1, several
        // QSOs share one card; "repeats per QSO" elements print once per QSO at a
        // vertical pitch, the rest print once per card.
        $opts    = $layout['options'] ?? [];
        $perCard = max(1, (int)($opts['qsos_per_card'] ?? 1));
        $perCall = !empty($opts['per_callsign']);
        $pitch   = (float)($opts['row_pitch_in'] ?? 0.3);

        // Background image (FPDF supports jpg/png/gif only)
        $bgPath = null;
        if (!empty($background)) {
            // preview_image is untrusted client input; basename() + the user's own dir make ../ impossible
            $dir = $this->paths->getUserdataPath('qslpostcard_images', 'p');
            $candidate = $dir . '/' . basename($background);
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if ($dir !== false && file_exists($candidate) && in_array($ext, ['jpg', 'jpeg', 'png']) && filesize($candidate) <= self::MAX_BG_IMAGE_BYTES && @getimagesize($candidate) !== false) {
                $bgPath = $candidate;
            } else {
                log_message('error', 'QSLPOSTCARD background image not usable: ' . $candidate);
            }
        }

        // Group QSOs (by callsign when "one postcard per callsign" is set, else one
        // group), then split each group into cards of $perCard QSOs. The first QSO
        // of each chunk drives the per-card address resolution below.
        $groups = [];
        foreach ($qsos as $qso) {
            $key = $perCall ? strtoupper(trim($qso['COL_CALL'] ?? '')) : '__all__';
            if ($key === '') {
                continue;
            }
            $groups[$key][] = $qso;
        }

        foreach ($groups as $groupQsos) {
        foreach (array_chunk($groupQsos, $perCard) as $chunk) {
            $qso = $chunk[0];
            $call = strtoupper(trim($qso['COL_CALL'] ?? ''));
			if ($noaddress) {
				$addr = null;
			} else {
				$addr = $call ? $this->resolve_address($call) : null;
				// Skip if no usable mailing address
				if (!$this->is_mailable_address($addr)) {
					log_message('debug', 'QSLPOSTCARD skipping ' . $call . ' because no usable address was found');
					continue;
				}
			}

			// Only create a page after address is confirmed
            $pdf->AddPage();

            if ($bgPath !== null) {
                $pdf->Image($bgPath, 0, 0, $w_mm, $h_mm);
            }

            foreach (($layout['elements'] ?? []) as $el) {
                $type  = $el['type'] ?? 'field';
                $field = $el['field'] ?? '';
                // addr.* fields are always static (one address per card); other
                // "repeats per QSO" fields print once per QSO in the chunk.
                $isAddr  = ($type !== 'text') && str_starts_with($field, 'addr.');
                $repeat  = !empty($el['repeat_per_qso']) && !$isAddr;
                $targets = $repeat ? $chunk : [$chunk[0]];

                $font      = $el['font'] ?? 'Helvetica';
                $pt        = (float)($el['font_pt'] ?? 11);
                $bold      = !empty($el['bold']) ? 'B' : '';
                $wrap_w_in = isset($el['wrap_w_in']) ? (float)$el['wrap_w_in'] : 0;
                [$cr, $cg, $cb] = $this->hex_to_rgb($el['color'] ?? '#000000');

                foreach ($targets as $rowIdx => $qso) {
                    if ($type === 'text') {
                        $val = $el['text'] ?? '';
                    } else {
                        $val = $this->resolve_field($field, $qso, $addr);
                    }

                    if ($val === '') {
                        continue;
                    }

                    $x_in = (float)($el['x_in'] ?? 0) + $ox;
                    $y_in = (float)($el['y_in'] ?? 0) + $oy + ($repeat ? $pitch * $rowIdx : 0);

                    $x_mm = $x_in * 25.4;
                    $y_mm = $y_in * 25.4;

                    $pdf->SetFont($font, $bold, $pt);
                    $pdf->SetTextColor($cr, $cg, $cb);

                    if ($wrap_w_in > 0) {
                        $w = $wrap_w_in * 25.4;
                        $pdf->SetXY($x_mm, $y_mm);
                        $pdf->MultiCell($w, 4.5, $val);
                    } else {
                        $pdf->Text($x_mm, $y_mm + ($pt * 0.30), $val);
                    }
                }
            }
        }
        }

        $tmp = sys_get_temp_dir() . '/qsl_postcards_' . uniqid() . '.pdf';
        $pdf->Output('F', $tmp);

        if (!file_exists($tmp)) {
            throw new Exception('FPDF completed but temp PDF was not created');
        }

        return $tmp;
    }

    // "#rrggbb" (or "#rgb") → [r, g, b]; falls back to black on anything malformed.
    private function hex_to_rgb($hex) {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }
        return [
            hexdec($hex[0] . $hex[1]),
            hexdec($hex[2] . $hex[3]),
            hexdec($hex[4] . $hex[5]),
        ];
    }

    public function dedupe_qsos_by_call(array $qsos) {
        $seen = [];
        $deduped = [];

        foreach ($qsos as $qso) {
            $call = strtoupper(trim($qso['COL_CALL'] ?? ''));
            if ($call === '') {
                continue;
            }

            // Keep the first one we see.
            // Since your query is already ordered DESC by time, this will keep the most recent QSO.
            if (!isset($seen[$call])) {
                $seen[$call] = true;
                $deduped[] = $qso;
            }
        }

        return $deduped;
    }

    // TODO: Unused, check if needed anymore
    private function extract_qso_date_parts($qso) {
        $raw = trim($qso['COL_QSO_DATE'] ?? $qso['qso_date'] ?? '');

        if ($raw === '') {
            return ['year' => '', 'month' => '', 'day' => ''];
        }

        // Already compact ADIF style: YYYYMMDD
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($digits) === 8) {
            return [
                'year' => substr($digits, 0, 4),
                'month' => substr($digits, 4, 2),
                'day' => substr($digits, 6, 2),
            ];
        }

        // Full datetime like 2026-03-05 19:55:00
        if (strlen($digits) >= 8) {
            return [
                'year' => substr($digits, 0, 4),
                'month' => substr($digits, 4, 2),
                'day' => substr($digits, 6, 2),
            ];
        }

        return ['year' => '', 'month' => '', 'day' => ''];
    }

    // TODO: Unused, check if needed anymore
    private function extract_qso_time_hm($qso) {
        $raw = trim($qso['COL_TIME_ON'] ?? $qso['time_on'] ?? '');

        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);

        // HHMMSS
        if (strlen($digits) === 6) {
            return substr($digits, 0, 2) . ':' . substr($digits, 2, 2);
        }

        // HHMM
        if (strlen($digits) === 4) {
            return substr($digits, 0, 2) . ':' . substr($digits, 2, 2);
        }

        // Full datetime, e.g. 20260305195500 -> use the last 6 digits as HHMMSS
        if (strlen($digits) >= 12) {
            $time = substr($digits, -6);
            return substr($time, 0, 2) . ':' . substr($time, 2, 2);
        }

        return $raw;
    }

    public function get_qsl_queue_qsos($filters = []) {
        $table = $this->config->item('table_name');

        // Scope to the logged-in user's stations only
        $binding = [$this->session->userdata('user_id')];

        // Most likely starting point for physical cards:
        // requested cards or unsent cards
        // Adjust after we confirm your queue logic.
        $sql = "SELECT " . $table . ".*
            FROM " . $table . "
            INNER JOIN station_profile ON station_profile.station_id = " . $table . ".station_id
            WHERE station_profile.user_id = ?
            AND (" . $table . ".COL_QSL_SENT IN ('R', 'Q', '') OR " . $table . ".COL_QSL_SENT IS NULL)";

        if (!empty($filters['station_id'])) {
            $sql .= " AND " . $table . ".station_id = ?";
            $binding[] = $filters['station_id'];
        }

        if (!empty($filters['band'])) {
            $sql .= " AND " . $table . ".COL_BAND = ?";
            $binding[] = $filters['band'];
        }

        if (!empty($filters['mode'])) {
            $sql .= " AND " . $table . ".COL_MODE = ?";
            $binding[] = $filters['mode'];
        }

        if (!empty($filters['call'])) {
            $sql .= " AND " . $table . ".COL_CALL LIKE ?";
            $binding[] = '%' . $filters['call'] . '%';
        }

        $sql .= " ORDER BY " . $table . ".COL_TIME_ON DESC";

        $q = $this->db->query($sql, $binding);
        if (!$q) {
            $db_error = $this->db->error();
            throw new Exception('DB query failed in get_qsl_queue_qsos(): ' . json_encode($db_error));
        }

        return $q->result_array();
    }

    public function get_qsos_by_ids(array $ids) {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        $table = $this->config->item('table_name');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Scope to the logged-in user's stations only
        $sql = "SELECT " . $table . ".*
            FROM " . $table . "
            INNER JOIN station_profile ON station_profile.station_id = " . $table . ".station_id
            WHERE station_profile.user_id = ?
            AND " . $table . ".COL_PRIMARY_KEY IN (" . $placeholders . ")
            ORDER BY " . $table . ".COL_TIME_ON DESC";

        $binding = array_merge([$this->session->userdata('user_id')], $ids);

        $q = $this->db->query($sql, $binding);

        if (!$q) {
            $db_error = $this->db->error();
            throw new Exception('DB query failed in get_qsos_by_ids(): ' . json_encode($db_error));
        }

        return $q->result_array();
    }

    private function resolve_field($field, $qso, $addr) {

        // Address computed fields
        if ($field === 'addr.source') {
            return $addr['source'] ?? '';
        }
        if ($field === 'addr.name') return $addr['name'] ?? '';
        if ($field === 'addr.addr1') return $addr['addr1'] ?? '';
        if ($field === 'addr.addr2') return $addr['addr2'] ?? '';
        if ($field === 'addr.city')  return $addr['city'] ?? '';
        if ($field === 'addr.state') return $addr['state'] ?? '';
        if ($field === 'addr.zip')   return $addr['zip'] ?? '';
        if ($field === 'addr.country') return $addr['country'] ?? '';
        if ($field === 'addr.city_state_zip') {
            $city = trim($addr['city'] ?? '');
            $state = trim($addr['state'] ?? '');
            $zip = trim($addr['zip'] ?? '');

            if ($city !== '' && $state !== '' && $zip !== '') {
                return $city . ', ' . $state . ' ' . $zip;
            }

            if ($city !== '' && $state !== '') {
                return $city . ', ' . $state;
            }

            if ($city !== '' && $zip !== '') {
                return $city . ' ' . $zip;
            }

            if ($city !== '') {
                return $city;
            }

            if ($state !== '' && $zip !== '') {
                return $state . ' ' . $zip;
            }

            return trim($state . ' ' . $zip);
        }
        // QSO fields (adjust keys to match your DB)
        if ($field === 'qso.call') return strtoupper($qso['COL_CALL'] ?? $qso['call'] ?? '');
        if ($field === 'qso.qso_date') return $qso['COL_QSO_DATE'] ?? $qso['qso_date'] ?? '';
        if ($field === 'qso.time_on') return $qso['COL_TIME_ON'] ?? $qso['time_on'] ?? '';
        if ($field === 'qso.band') return $qso['COL_BAND'] ?? $qso['band'] ?? '';
        if ($field === 'qso.mode') return $qso['COL_MODE'] ?? $qso['mode'] ?? '';
        if ($field === 'qso.freq') return $qso['COL_FREQ'] ?? $qso['freq'] ?? '';
        if ($field === 'qso.rst_sent') return $qso['COL_RST_SENT'] ?? $qso['rst_sent'] ?? '';
        if ($field === 'qso.rst_rcvd') return $qso['COL_RST_RCVD'] ?? $qso['rst_rcvd'] ?? '';
        if ($field === 'qso.qsl_message') {
            return $qso['COL_QSLMSG'] ?? '';
        }

        if ($field === 'qso.antenna') {
            return $qso['COL_MY_ANTENNA'] ?? $qso['COL_MY_ANTENNA_INTL'] ?? '';
        }

        if ($field === 'qso.tx_power') {
            $pwr = trim($qso['COL_TX_PWR'] ?? '');
            return $pwr !== '' ? $pwr . ' W' : '';
        }

        if ($field === 'qso.rx_power') {
            $pwr = trim($qso['COL_RX_PWR'] ?? '');
            return $pwr !== '' ? $pwr . ' W' : '';
        }

        if ($field === 'qso.my_rig') {
            return $qso['COL_MY_RIG'] ?? '';
        }

        if ($field === 'qso.rig') {
            return $qso['COL_RIG'] ?? $qso['station_profile_name'] ?? $qso['rig'] ?? '';
        }

        if ($field === 'qso.comment') {
            return $qso['COL_COMMENT'] ?? $qso['COL_NOTES'] ?? $qso['comment'] ?? '';
        }


        if ($field === 'qso.month_name') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? strtoupper($dt->format('M')) : '';
        }


        if ($field === 'qso.time_utc') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? $dt->format('H:i') . ' UTC' : '';
        }

		if ($field === 'qso.time') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? $dt->format('H:i') : '';
        }

        if ($field === 'qso.day') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? $dt->format('d') : '';
        }

        if ($field === 'qso.month') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? $dt->format('m') : '';
        }

        if ($field === 'qso.year') {
            $dt = $this->normalize_qso_datetime($qso);
            return $dt ? $dt->format('Y') : '';
        }
        if ($field === 'qso.pota_ref') return $qso['COL_POTA_REF'] ?? '';

        if ($field === 'qso.my_pota_ref') return $qso['COL_MY_POTA_REF'] ?? '';

        if ($field === 'qso.pota_line') {
            $ref = trim($qso['COL_MY_POTA_REF'] ?? '');
            return $ref !== '' ? 'From POTA: ' . $ref : '';
        }

		if ($field === 'qso.sota_ref') return $qso['COL_SOTA_REF'] ?? '';

        if ($field === 'qso.my_sota_ref') return $qso['COL_MY_SOTA_REF'] ?? '';

        if ($field === 'qso.sota_line') {
            $ref = trim($qso['COL_MY_SOTA_REF'] ?? '');
            return $ref !== '' ? 'From SOTA: ' . $ref : '';
		}

		if ($field === 'qso.iota_ref') return $qso['COL_IOTA'] ?? '';

        if ($field === 'qso.my_iota_ref') return $qso['COL_MY_IOTA'] ?? '';

        if ($field === 'qso.iota_line') {
            $ref = trim($qso['COL_MY_IOTA'] ?? '');
            return $ref !== '' ? 'From IOTA: ' . $ref : '';
		}

        if ($field === 'qso.qsl_via') return $qso['COL_QSL_VIA'] ?? $qso['qsl_via'] ?? '';

        if ($field === 'qso.summary') {
            $c = strtoupper($qso['COL_CALL'] ?? $qso['call'] ?? '');
            $d = $qso['COL_QSO_DATE'] ?? $qso['qso_date'] ?? '';
            $t = $qso['COL_TIME_ON'] ?? $qso['time_on'] ?? '';
            $b = $qso['COL_BAND'] ?? $qso['band'] ?? '';
            $m = $qso['COL_MODE'] ?? $qso['mode'] ?? '';
            $rs = $qso['COL_RST_SENT'] ?? $qso['rst_sent'] ?? '';
            $rr = $qso['COL_RST_RCVD'] ?? $qso['rst_rcvd'] ?? '';
            return trim("$c  $d $t  $b $m  $rs/$rr");
        }

        return '';
    }

	function delete_template($id) {
		try {
			$uid = $this->session->userdata('user_id');

			$sql = "SELECT preview_image FROM qsl_postcard_templates WHERE id = ? AND user_id = ?";
			$tpl = $this->db->query($sql, [$id, $uid])->row_array();

			$sql = "DELETE FROM qsl_postcard_templates WHERE id = ? AND user_id = ?";
			$this->db->query($sql, [$id, $uid]);

			if (!empty($tpl['preview_image'])) {
				$this->unlink_preview_image($uid, $tpl['preview_image']);
			}

			return true;
		} catch (Exception $e) {
			log_message('error', 'Error deleting QSL postcard template: ' . $e->getMessage());
			return false;
		}
	}

	private function unlink_preview_image($uid, $preview_image) {
		try {
			$file = basename($preview_image);
			$ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
				return;
			}

			$sql = "SELECT COUNT(1) AS cnt FROM qsl_postcard_templates WHERE user_id = ? AND preview_image LIKE ?";
			$row = $this->db->query($sql, [$uid, '%' . $file])->row_array();
			if (!empty($row['cnt'])) {
				return;
			}

			$dir = $this->paths->getUserdataPath('qslpostcard_images', 'p');
			if ($dir === false) {
				return;
			}
			$candidate = $dir . '/' . $file;
			if (file_exists($candidate) && !@unlink($candidate)) {
				log_message('error', 'QSLPOSTCARD could not unlink background image: ' . $candidate);
			}
		} catch (Exception $e) {
			log_message('error', 'QSLPOSTCARD error unlinking background image: ' . $e->getMessage());
		}
	}
}
