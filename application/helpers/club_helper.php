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
                    if ($qso->COL_OPERATOR == $CI->session->userdata('operator_callsign') || $CI->session->userdata('cd_p_level') >= 9) {
                        return true;
                    } else {
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