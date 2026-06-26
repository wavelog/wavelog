<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Small Helper functions for the Clubstations feature
 */

 /**
  * Access Check in the UI for impersonated clubstations
  * 
  * To see available permission levels, check out 'application/controllers/Club.php'
  */

if (!function_exists('clubaccess_check')) {
    function clubaccess_check($required_level, $qso_id = 0) {

        $CI =& get_instance();
        if (!$CI->load->is_loaded('session')) {
            $CI->load->library('session');
        }

        $clubmode = $CI->config->item('special_callsign') ?? false;
        $clubstation = $CI->session->userdata('clubstation') ?? 0;

        if ($clubmode && $clubstation == 1) {
            // check if the user has the required level
            if ($CI->session->userdata('cd_p_level') >= $required_level) {
                if ($qso_id != 0) {
                    // check if the QSO belongs to the user
                    $CI->load->model('logbook_model');
                    $qso = $CI->logbook_model->get_qso($qso_id)->row();
                    $user_level = $CI->session->userdata('cd_p_level');
                    $operator_callsign = $CI->session->userdata('operator_callsign');

                    // Enhanced logic for ClubMemberADIF (Level 6)
                    if ($user_level >= 9) {
                        // Officers can access any QSO
                        return true;
                    } elseif ($user_level >= $required_level) {
                        // ClubMemberADIF and regular members can only access their own QSOs
                        return $qso->COL_OPERATOR == $operator_callsign;
                    } else {
                        // Lower levels (shouldn't reach here for ADIF access)
                        return false;
                    }
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            // return always true if the special callsign mode is disabled, so there is no change in behaviour
            return true;
        }
    }
}

/**
 * Batch counterpart to clubaccess_check().
 *
 * Given a list of QSO primary keys, returns only those the current user is
 * allowed to modify. Used by the LogbookAdvanced mass-edit / batch-delete
 * flows so a ClubMember (Level 6) can only touch QSOs he made himself
 * (COL_OPERATOR == his operator_callsign).
 *
 *  - not impersonating a clubstation / special_callsign off: returns $ids as-is
 *  - Officer (cd_p_level >= 9): returns $ids as-is
 *  - Club Member (3) / Club Member ADIF (6): returns only IDs owned by the user
 *
 * The ownership query is scoped by BOTH station ownership and operator, so it
 * is safe against arbitrary/foreign IDs 
 */
if (!function_exists('clubaccess_filter_qso_ids')) {
	function clubaccess_filter_qso_ids(array $ids): array {

		$CI =& get_instance();
		if (!$CI->load->is_loaded('session')) {
			$CI->load->library('session');
		}

		// empty in, empty out
		if (empty($ids)) {
			return [];
		}

		$clubmode = $CI->config->item('special_callsign') ?? false;
		$clubstation = $CI->session->userdata('clubstation') ?? 0;

		// Not impersonating a clubstation -> normal user, no restriction
		if (!$clubmode || $clubstation != 1) {
			return array_values($ids);
		}

		$user_level = $CI->session->userdata('cd_p_level') ?? 0;

		// Officer: may touch anything
		if ($user_level >= 9) {
			return array_values($ids);
		}

		// Below Club Member: no write access at all
		if ($user_level < 3) {
			return [];
		}

		// Club Member (3) / Club Member ADIF (6): keep only own QSOs
		$operator_callsign = $CI->session->userdata('operator_callsign');
		$user_id = $CI->session->userdata('user_id');

		$sql = "SELECT t.col_primary_key AS id"
			. " FROM " . $CI->config->item('table_name') . " t"
			. " JOIN station_profile s ON t.station_id = s.station_id"
			. " WHERE t.col_primary_key IN ? AND s.user_id = ? AND t.COL_OPERATOR = ?";

		$result = $CI->db->query($sql, [array_values($ids), $user_id, $operator_callsign])->result();

		$allowed = [];
		foreach ($result as $row) {
			$allowed[] = $row->id;
		}
		return $allowed;
	}
}
