<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Api_mobile — Mobile App API controller
 *
 * Provides delete and update endpoints for the Wavelog Mobile app.
 * Keys with rights="rw" and api_type="mobile_app" are intended for
 * this controller, but any rw key is accepted.
 *
 * Endpoints:
 *   POST /index.php/api_mobile/generate_key  — create a Mobile App API key (web UI)
 *   POST /index.php/api_mobile/delete_qso    — delete a QSO by server ID
 *   POST /index.php/api_mobile/update_qso    — update editable fields of a QSO
 */
class Api_mobile extends CI_Controller {

    // ── Rate-limit helper ─────────────────────────────────────────────
    private function _check_rate_limit($endpoint, $identifier = null) {
        if (!$this->load->is_loaded('rate_limit')) {
            $this->load->library('rate_limit');
        }
        $result = $this->rate_limit->check($endpoint, $identifier);
        if (!$result['allowed']) {
            $this->rate_limit->send_limit_exceeded_response($result['retry_after']);
            return false;
        }
        return true;
    }

    // ── Authorize: r or rw keys ──────────────────────────────────────
    private function _authorize_any($key) {
        $this->load->model('api_model');
        return $this->api_model->authorize($key) >= 1;
    }

    // ── Authorize: returns true only for rw keys ──────────────────────
    private function _authorize_rw($key) {
        $this->load->model('api_model');
        return $this->api_model->authorize($key) >= 2;
    }

    // ── Get user_id that owns the API key ─────────────────────────────
    private function _key_userid($key) {
        $this->load->model('api_model');
        return $this->api_model->key_userid($key);
    }

    // ── Verify QSO belongs to user (no session dependency) ───────────
    private function _qso_owned_by($qso_id, $user_id) {
        $table = $this->config->item('table_name');
        $pk    = $this->config->item('table_primary_key') ?: 'COL_PRIMARY_KEY';

        $row = $this->db
            ->select('1')
            ->from($table . ' q')
            ->join('station_profile sp', 'q.station_id = sp.station_id')
            ->where("q.$pk", intval($qso_id))
            ->where('sp.user_id', intval($user_id))
            ->limit(1)
            ->get()
            ->row();

        return $row !== null;
    }

    // ══════════════════════════════════════════════════════════════════
    //  generate_key — creates a Mobile App API key (called from web UI)
    //  POST /index.php/api_mobile/generate_key
    // ══════════════════════════════════════════════════════════════════
    public function generate_key() {
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', __("Invalid request method"));
            redirect('api');
            return;
        }

        $this->load->model('user_model');
        if (!$this->user_model->authorize(3)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!"));
            redirect('dashboard');
            return;
        }

        if ($this->session->userdata('clubstation') == 1 && $this->session->userdata('impersonate') == 1) {
            $creator = $this->session->userdata('source_uid');
        } else {
            $creator = $this->session->userdata('user_id');
        }

        // Insert directly so we can set api_type without modifying api_model
        $data = [
            'key'      => 'wl' . substr(md5(uniqid(rand(), true)), 19),
            'rights'   => 'rw',
            'api_type' => 'mobile_app',
            'status'   => 'active',
            'user_id'  => $this->session->userdata('user_id'),
            'created_by' => $creator,
        ];

        if ($this->db->insert('api', $data)) {
            $this->session->set_flashdata('success', __("Mobile App API Key generated"));
        } else {
            $this->session->set_flashdata('error', __("API Key could not be generated"));
        }

        redirect('api');
    }

    // ══════════════════════════════════════════════════════════════════
    //  get_contacts — returns QSOs WITH APP_WAVELOG_QSO_ID (server ID)
    //  POST /index.php/api_mobile/get_contacts
    //  JSON body: { "key": "...", "station_id": 1, "fetchfromid": 0, "limit": 5000 }
    // ══════════════════════════════════════════════════════════════════
    public function get_contacts() {
        header('Content-Type: application/json');
        session_write_close();

        $body = file_get_contents('php://input');
        $obj  = json_decode($body, true);

        if (!is_array($obj)) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid JSON body']);
            return;
        }

        $key = isset($obj['key']) ? xss_clean($obj['key']) : '';

        if (!$this->_check_rate_limit('mobile_get_contacts', $key)) return;

        if (!$key || !$this->_authorize_any($key)) {
            http_response_code(401);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid or missing api key']);
            return;
        }

        $station_id  = isset($obj['station_id'])  ? intval($obj['station_id'])  : 0;
        $fetchfromid = isset($obj['fetchfromid']) ? intval($obj['fetchfromid']) : 0;
        $limit       = isset($obj['limit'])       ? min(intval($obj['limit']), 20000) : 5000;
        $band        = isset($obj['band'])        ? xss_clean($obj['band'])     : null;

        if ($station_id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'station_id is required']);
            return;
        }

        // Same station ownership check as get_contacts_adif in Api.php
        $user_id     = $this->_key_userid($key);
        $this->load->model('stations');
        $station_ids = [];
        foreach ($this->stations->all_of_user($user_id)->result() as $s) {
            $station_ids[] = $s->station_id;
        }
        if (!in_array($station_id, $station_ids)) {
            http_response_code(401);
            echo json_encode(['status' => 'failed', 'reason' => 'station not accessible for this API key']);
            return;
        }

        // Reuse the battle-tested model query (SELECT table.* — safe across all versions)
        $this->load->model('adif_data');
        $rows   = $this->adif_data->export_past_id_chunked(
            $station_id, $fetchfromid, $limit, null, 0, $limit, null, $band
        );

        $result = [];
        $lastid = $fetchfromid;

        foreach ($rows->result() as $row) {
            // Build QSO array dynamically from all COL_* columns
            $qso = ['APP_WAVELOG_QSO_ID' => (int) $row->COL_PRIMARY_KEY];

            foreach (get_object_vars($row) as $col => $val) {
                if (strpos($col, 'COL_') === 0) {
                    $qso[substr($col, 4)] = $val; // strip COL_ prefix → ADIF field name
                }
            }

            // Format datetime → QSO_DATE (Ymd) + TIME_ON (His)
            if (!empty($row->COL_TIME_ON)) {
                $ts = strtotime($row->COL_TIME_ON);
                $qso['QSO_DATE'] = date('Ymd', $ts);
                $qso['TIME_ON']  = date('His', $ts);
            }
            if (!empty($row->COL_TIME_OFF)) {
                $ts = strtotime($row->COL_TIME_OFF);
                $qso['QSO_DATE_OFF'] = date('Ymd', $ts);
                $qso['TIME_OFF']     = date('His', $ts);
            }

            // Frequency: Hz integer → MHz float
            if (!empty($row->COL_FREQ)) {
                $qso['FREQ'] = (float)($row->COL_FREQ / 1000000);
            }

            $qso['station_profile_id'] = (int) $row->station_id;

            $result[] = $qso;
            $lastid   = max($lastid, (int) $row->COL_PRIMARY_KEY);
        }

        $rows->free_result();

        $this->load->model('api_model');
        $this->api_model->update_last_used($key);

        http_response_code(200);
        echo json_encode([
            'status'           => 'successful',
            'lastfetchedid'    => $lastid,
            'exported_records' => count($result),
            'qsos'             => $result,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  delete_qso — deletes a QSO
    //  POST /index.php/api_mobile/delete_qso
    //  JSON body: { "key": "...", "qso_id": 123, "station_profile_id": 1 }
    // ══════════════════════════════════════════════════════════════════
    public function delete_qso() {
        header('Content-Type: application/json');
        session_write_close();

        $body = file_get_contents('php://input');
        $obj  = json_decode($body, true);

        if (!is_array($obj)) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid JSON body']);
            return;
        }

        $key = isset($obj['key']) ? xss_clean($obj['key']) : '';

        if (!$this->_check_rate_limit('mobile_delete_qso', $key)) return;

        if (!$key || !$this->_authorize_rw($key)) {
            http_response_code(401);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid or insufficient api key']);
            return;
        }

        $qso_id            = isset($obj['qso_id'])            ? intval($obj['qso_id'])            : 0;
        $station_profile_id = isset($obj['station_profile_id']) ? intval($obj['station_profile_id']) : 0;

        if ($qso_id <= 0 || $station_profile_id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'qso_id and station_profile_id are required and must be positive integers']);
            return;
        }

        $user_id = $this->_key_userid($key);

        // Verify the station belongs to the API key owner
        $this->load->model('stations');
        if (!$this->stations->check_station_against_user($station_profile_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'failed', 'reason' => 'station does not belong to this API key owner']);
            return;
        }

        // Verify the QSO belongs to the user
        if (!$this->_qso_owned_by($qso_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'failed', 'reason' => 'QSO not found or does not belong to you']);
            return;
        }

        $table = $this->config->item('table_name');
        $pk    = $this->config->item('table_primary_key') ?: 'COL_PRIMARY_KEY';

        $this->db->where($pk, $qso_id);
        $deleted = $this->db->delete($table);

        if ($deleted) {
            $this->load->model('api_model');
            $this->api_model->update_last_used($key);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'QSO deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'reason' => 'database error during delete']);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  update_qso — updates editable fields of a QSO
    //  POST /index.php/api_mobile/update_qso
    //  JSON body: {
    //    "key": "...",
    //    "qso_id": 123,
    //    "station_profile_id": 1,
    //    "fields": { "COL_RST_RCVD": "59", "COL_COMMENT": "Great signal" }
    //  }
    // ══════════════════════════════════════════════════════════════════
    public function update_qso() {
        header('Content-Type: application/json');
        session_write_close();

        $body = file_get_contents('php://input');
        $obj  = json_decode($body, true);

        if (!is_array($obj)) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid JSON body']);
            return;
        }

        $key = isset($obj['key']) ? xss_clean($obj['key']) : '';

        if (!$this->_check_rate_limit('mobile_update_qso', $key)) return;

        if (!$key || !$this->_authorize_rw($key)) {
            http_response_code(401);
            echo json_encode(['status' => 'failed', 'reason' => 'invalid or insufficient api key']);
            return;
        }

        $qso_id             = isset($obj['qso_id'])             ? intval($obj['qso_id'])             : 0;
        $station_profile_id = isset($obj['station_profile_id']) ? intval($obj['station_profile_id']) : 0;
        $fields             = isset($obj['fields'])             ? $obj['fields']                     : null;

        if ($qso_id <= 0 || $station_profile_id <= 0 || !is_array($fields) || empty($fields)) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'qso_id, station_profile_id, and fields (non-empty object) are required']);
            return;
        }

        $user_id = $this->_key_userid($key);

        $this->load->model('stations');
        if (!$this->stations->check_station_against_user($station_profile_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'failed', 'reason' => 'station does not belong to this API key owner']);
            return;
        }

        if (!$this->_qso_owned_by($qso_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'failed', 'reason' => 'QSO not found or does not belong to you']);
            return;
        }

        // Whitelist of columns the mobile app is allowed to update
        $allowed = [
            'COL_TIME_ON', 'COL_TIME_OFF',
            'COL_QSO_DATE', 'COL_QSO_DATE_OFF',
            'COL_CALL',
            'COL_BAND', 'COL_FREQ',
            'COL_MODE', 'COL_SUBMODE',
            'COL_RST_SENT', 'COL_RST_RCVD',
            'COL_NAME', 'COL_QTH', 'COL_LOCATOR',
            'COL_TX_PWR',
            'COL_QSL_SENT', 'COL_QSL_RCVD', 'COL_QSL_VIA',
            'COL_IOTA', 'COL_SOTA_REF', 'COL_WWFF_REF', 'COL_POTA_REF',
            'COL_COMMENT', 'COL_NOTES',
        ];

        $update = [];
        foreach ($fields as $col => $val) {
            if (in_array(strtoupper($col), $allowed, true)) {
                $update[strtoupper($col)] = xss_clean((string) $val);
            }
        }

        if (empty($update)) {
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'reason' => 'no valid updatable fields provided — see documentation for allowed field names']);
            return;
        }

        $table = $this->config->item('table_name');
        $pk    = $this->config->item('table_primary_key') ?: 'COL_PRIMARY_KEY';

        $this->db->where($pk, $qso_id);
        $ok = $this->db->update($table, $update);

        if ($ok) {
            $this->load->model('api_model');
            $this->api_model->update_last_used($key);
            http_response_code(200);
            echo json_encode([
                'status'         => 'success',
                'message'        => 'QSO updated',
                'updated_fields' => array_keys($update),
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'reason' => 'database error during update']);
        }
    }
}
