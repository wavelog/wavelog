<?php

class Dcl_model extends CI_Model {

	/*
	|--------------------------------------------------------------------------
	| Function: lotw_certs
	|--------------------------------------------------------------------------
	|
	| Returns all lotw_certs for a selected user via the $user_id parameter
	|
	*/
	function dcl_keys($user_id) {
		$this->load->model('user_options_model');
		return $this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key'), $user_id)->result();
	}


	function find_key($callsign, $user_id) {
		$this->load->model('user_options_model');
		return $this->user_options_model->get_options('dcl', array('option_name'=>'dcl_key','option_key'=>$callsign), $user_id)->result();
	}

	function store_key($user_id, $callsign, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
	}

	function update_key($user_id, $callsign, $dxcc, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
	}

	function delete_key($call) {
		$this->user_options_model->del_option('dcl', 'dcl_key',array('option_key' => $call));
	}

	function last_upload($key, $message) {

		if ($message == "Success") {
			$data = array(
				'last_upload' => date("Y-m-d H:i:s"),
				'last_upload_status' => $message,
			);

			$this->db->where('lotw_cert_id', $certID);
			$this->db->update('lotw_certs', $data);
			return "Updated";
		}
		else if ($message == "Upload failed") {
			$data = array(
				'last_upload_fail' => date("Y-m-d H:i:s"),
				'last_upload_status' => $message,
			);

			$this->db->where('lotw_cert_id', $certID);
			$this->db->update('lotw_certs', $data);
			return "Updated";
		}
	}

   function lotw_cert_expired($user_id, $date) {
      $array = array('user_id' => $user_id, 'date_expires <' => $date);
      $this->db->where($array);
      $query = $this->db->get('lotw_certs');

      if ($query->num_rows() > 0) {
         return true;
      } else {
         return false;
      }
   }

   function lotw_cert_expiring($user_id, $date) {
      $array = array('user_id' => $user_id, 'DATE_SUB(date_expires, INTERVAL 30 DAY) <' => $date, 'date_expires >' => $date);
      $this->db->where($array);
      $query = $this->db->get('lotw_certs');

      if ($query->num_rows() > 0) {
         return true;
      } else {
         return false;
      }
   }

}
?>
