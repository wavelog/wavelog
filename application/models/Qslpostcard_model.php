<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Wavelog\Label\FPDF;

class Qslpostcard_model extends CI_Model {

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
        // Try the table name you used first
        $q = $this->db->order_by('COL_TIME_ON', 'DESC')->limit((int)$limit)->get($table);

        if (!$q) {
            $db_error = $this->db->error();
            throw new Exception('DB query failed in get_sample_qsos(): ' . json_encode($db_error));
        }

        $rows = $q->result_array();

        if (!empty($rows)) {
            log_message('error', 'QSLPOSTCARD sample row keys: ' . implode(', ', array_keys($rows[0])));
        } else {
            log_message('error', 'QSLPOSTCARD get_sample_qsos() returned 0 rows');
        }

        return $rows;
    }

    // --- Callbook cache (HamQTH) ---

    public function resolve_address($callsign) {
        $callsign = strtoupper(trim($callsign));
        if (empty($callsign)) return null;

        // cache first
        $cache = $this->cache->get('callbook_cache_' . md5($callsign));
        if ($cache) {
            return json_decode($cache['address_json'], true);
        }

        // TODO: use Callbook Lib here!

        // 1) HamQTH
        $addr = $this->hamqth_lookup($callsign);
        if ($this->is_mailable_address($addr)) {
            $this->cache_address($callsign, 'hamqth', $addr);
            return $addr;
        }

        // 2) QRZCQ
        $addr = $this->qrzcq_lookup($callsign);
        if ($this->is_mailable_address($addr)) {
            $this->cache_address($callsign, 'qrzcq', $addr);
            return $addr;
        }

        // 3) QRZ
        $addr = $this->qrz_lookup($callsign);
        if ($this->is_mailable_address($addr)) {
            $this->cache_address($callsign, 'qrz', $addr);
            return $addr;
        }

        return null;
    }

    private function is_mailable_address($addr) {
        if (!is_array($addr)) return false;

        $name    = trim($addr['name'] ?? '');
        $addr1   = trim($addr['addr1'] ?? '');
        $city    = trim($addr['city'] ?? '');
        $state   = trim($addr['state'] ?? '');
        $zip     = trim($addr['zip'] ?? '');
        $country = trim($addr['country'] ?? '');

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

    private function hamqth_lookup($callsign) {
        $u = $this->config->item('hamqth_username');
        $p = $this->config->item('hamqth_password');
        if (!$u || !$p) return null;

        $loginXml = $this->http_get('https://www.hamqth.com/xml.php?u=' . rawurlencode($u) . '&p=' . rawurlencode($p));
        if (!$loginXml) return null;

        $sid = $this->xml_tag($loginXml, 'session_id');
        if (!$sid) return null;

        $xml = $this->http_get('https://www.hamqth.com/xml.php?id=' . rawurlencode($sid) . '&callsign=' . rawurlencode($callsign));
        if (!$xml) return null;

        $name = trim($this->xml_tag($xml, 'adr_name'));
        if ($name === '') $name = trim($this->xml_tag($xml, 'nick'));
        if ($name === '') $name = $callsign;

        return [
            'name'    => $name,
            'addr1'   => $this->xml_tag($xml, 'adr_street1'),
            'addr2'   => $this->xml_tag($xml, 'adr_street2'),
            'city'    => $this->xml_tag($xml, 'adr_city'),
            'state'   => $this->xml_tag($xml, 'state') ?: $this->xml_tag($xml, 'us_state'),
            'zip'     => $this->xml_tag($xml, 'adr_zip'),
            'country' => $this->xml_tag($xml, 'adr_country'),
        ];
    }

    private function qrzcq_lookup($callsign) {
        $u = $this->config->item('qrzcq_username');
        $p = $this->config->item('qrzcq_password');
        if (!$u || !$p) return null;

        // login/token similar to QRZ
        $loginXml = $this->http_get('https://ssl.qrzcq.com/xml?username=' . rawurlencode($u) . ';password=' . rawurlencode($p));
        if (!$loginXml) return null;

        // QRZCQ docs say token works like QRZ; try Key first, then Session
        $key = $this->xml_tag($loginXml, 'Key');
        if ($key === '') $key = $this->xml_tag($loginXml, 'key');
        if ($key === '') $key = $this->xml_tag($loginXml, 'Session');
        if ($key === '') $key = $this->xml_tag($loginXml, 'session');

        if ($key === '') {
            // some installs allow direct user/pass+callsign query
            $xml = $this->http_get('https://ssl.qrzcq.com/xml?username=' . rawurlencode($u) . ';password=' . rawurlencode($p) . ';callsign=' . rawurlencode($callsign));
        } else {
            $xml = $this->http_get('https://ssl.qrzcq.com/xml?s=' . rawurlencode($key) . ';callsign=' . rawurlencode($callsign));
        }

        if (!$xml) return null;

        return [
            'name'    => trim($this->xml_tag($xml, 'name')) ?: $callsign,
            'addr1'   => $this->xml_tag($xml, 'address') ?: $this->xml_tag($xml, 'addr1'),
            'addr2'   => $this->xml_tag($xml, 'addr2'),
            'city'    => $this->xml_tag($xml, 'city'),
            'state'   => $this->xml_tag($xml, 'state'),
            'zip'     => $this->xml_tag($xml, 'zip'),
            'country' => $this->xml_tag($xml, 'country'),
        ];
    }
    private function qrz_lookup($callsign) {
        $u = $this->config->item('qrz_username');
        $p = $this->config->item('qrz_password');
        if (!$u || !$p) return null;

        $base = 'https://xmldata.qrz.com/xml/current/';

        $loginXml = $this->http_get($base . '?username=' . rawurlencode($u) . '&password=' . rawurlencode($p));
        if (!$loginXml) return null;

        $key = $this->xml_tag($loginXml, 'Key');
        if ($key === '') return null;

        $xml = $this->http_get($base . '?s=' . rawurlencode($key) . '&callsign=' . rawurlencode($callsign));
        if (!$xml) return null;

        $name = trim($this->xml_tag($xml, 'fname') . ' ' . $this->xml_tag($xml, 'name'));
        if ($name === '') $name = $callsign;

        return [
            'name'    => $name,
            'addr1'   => $this->xml_tag($xml, 'addr1'),
            'addr2'   => '',
            'city'    => $this->xml_tag($xml, 'addr2'),
            'state'   => $this->xml_tag($xml, 'state'),
            'zip'     => $this->xml_tag($xml, 'zip'),
            'country' => $this->xml_tag($xml, 'country'),
        ];
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

    public function render_pdf_from_layout($layout, $qsos, $mark_sent = false, $background = null) {
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

        // Background image (FPDF supports jpg/png/gif only)
        $bgPath = null;
        if (!empty($background)) {
            $candidate = FCPATH . ltrim($background, '/');
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (file_exists($candidate) && in_array($ext, ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'])) {
                $bgPath = $candidate;
            } else {
                log_message('error', 'QSLPOSTCARD background image not usable: ' . $candidate);
            }
        }

        foreach ($qsos as $qso) {

            $call = strtoupper(trim($qso['COL_CALL'] ?? ''));
            $addr = $call ? $this->resolve_address($call) : null;

            // Skip if no usable mailing address
            if (!$this->is_mailable_address($addr)) {
                log_message('error', 'QSLPOSTCARD skipping ' . $call . ' because no usable address was found');
                continue;
            }

            // Only create a page after address is confirmed
            $pdf->AddPage();

            if ($bgPath !== null) {
                $pdf->Image($bgPath, 0, 0, $w_mm, $h_mm);
            }

            foreach (($layout['elements'] ?? []) as $el) {
                $type = $el['type'] ?? 'field';

                if ($type === 'text') {
                    $val = $el['text'] ?? '';
                } else {
                    $field = $el['field'] ?? '';
                    $val = $this->resolve_field($field, $qso, $addr);
                }

                if ($val === '') {
                    continue;
                }

                $x_in = (float)($el['x_in'] ?? 0) + $ox;
                $y_in = (float)($el['y_in'] ?? 0) + $oy;

                $x_mm = $x_in * 25.4;
                $y_mm = $y_in * 25.4;

                $font = $el['font'] ?? 'Helvetica';
                $pt   = (float)($el['font_pt'] ?? 11);
                $bold = !empty($el['bold']) ? 'B' : '';

                $pdf->SetFont($font, $bold, $pt);

                $wrap_w_in = isset($el['wrap_w_in']) ? (float)$el['wrap_w_in'] : 0;
                if ($wrap_w_in > 0) {
                    $w = $wrap_w_in * 25.4;
                    $pdf->SetXY($x_mm, $y_mm);
                    $pdf->MultiCell($w, 4.5, $val);
                } else {
                    $pdf->Text($x_mm, $y_mm + ($pt * 0.30), $val);
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
        $this->db->from($this->config->item('table_name'));

        // Most likely starting point for physical cards:
        // requested cards or unsent cards
        // Adjust after we confirm your queue logic.
        $this->db->group_start();
        $this->db->where('COL_QSL_SENT', 'R');
        $this->db->or_where('COL_QSL_SENT', 'Q');
        $this->db->or_where('COL_QSL_SENT', '');
        $this->db->or_where('COL_QSL_SENT IS NULL', null, false);
        $this->db->group_end();

        if (!empty($filters['station_id'])) {
            $this->db->where('station_id', $filters['station_id']);
        }

        if (!empty($filters['band'])) {
            $this->db->where('COL_BAND', $filters['band']);
        }

        if (!empty($filters['mode'])) {
            $this->db->where('COL_MODE', $filters['mode']);
        }

        if (!empty($filters['call'])) {
            $this->db->like('COL_CALL', $filters['call']);
        }

        $this->db->order_by('COL_TIME_ON', 'DESC');

        $q = $this->db->get();
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

        $q = $this->db
            ->from($this->config->item('table_name'))
            ->where_in('COL_PRIMARY_KEY', $ids)
            ->order_by('COL_TIME_ON', 'DESC')
            ->get();

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
			$this->db->where('id', $id)->where('user_id', $this->session->userdata('user_id'))->delete('qsl_postcard_templates');
			return true;
		} catch (Exception $e) {
			log_message('error', 'Error deleting QSL postcard template: ' . $e->getMessage());
			return false;
		}
	}
}
