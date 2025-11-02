<?php

class Lotw_model extends CI_Model {

	/*
	|--------------------------------------------------------------------------
	| Function: lotw_certs
	|--------------------------------------------------------------------------
	|
	| Returns all lotw_certs for a selected user via the $user_id parameter
	|
	*/
	function lotw_certs($user_id) {

		$this->db->select('lotw_certs.lotw_cert_id as lotw_cert_id, lotw_certs.callsign as callsign, dxcc_entities.name as cert_dxcc, dxcc_entities.end as cert_dxcc_end, lotw_certs.qso_start_date as qso_start_date, lotw_certs.qso_end_date as qso_end_date, lotw_certs.date_created as date_created, lotw_certs.date_expires as date_expires, lotw_certs.last_upload as last_upload, lotw_certs.last_upload_fail as last_upload_fail, lotw_certs.last_upload_status as last_upload_status');
		$this->db->where('user_id', $user_id);
		$this->db->join('dxcc_entities','lotw_certs.cert_dxcc_id = dxcc_entities.adif','left');
		$this->db->order_by('callsign', 'ASC');
		$query = $this->db->get('lotw_certs');

		return $query;
	}


	function lotw_cert_details($callsign, $user_id) {
		$this->db->where('user_id', $user_id);
		$this->db->where('callsign', $callsign);
		$query = $this->db->get('lotw_certs');

		return $query;
	}

	function find_cert($callsign, $dxcc, $user_id) {
		$this->db->where('user_id', $user_id);
		$this->db->where('cert_dxcc_id', $dxcc);
		$this->db->where('callsign', $callsign);
		$query = $this->db->get('lotw_certs');

		return $query->num_rows();
	}

	function store_certificate($user_id, $callsign, $dxcc, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
		$data = array(
			'user_id' => $user_id,
			'callsign' => $callsign,
			'cert_dxcc_id' => $dxcc,
			'date_created' => $date_created,
			'date_expires' => $date_expires,
			'qso_start_date' => $qso_start_date,
			'qso_end_date' => $qso_end_date . ' 23:59:59',
			'cert_key' => $cert_key,
			'cert' => $general_cert,
		);

		$this->db->insert('lotw_certs', $data);
	}

	function update_certificate($user_id, $callsign, $dxcc, $date_created, $date_expires, $qso_start_date, $qso_end_date, $cert_key, $general_cert) {
		$data = array(
			'cert_dxcc_id' => $dxcc,
			'date_created' => $date_created,
			'date_expires' => $date_expires,
			'qso_start_date' => $qso_start_date,
			'qso_end_date' => $qso_end_date . ' 23:59:59',
			'cert_key' => $cert_key,
			'cert' => $general_cert
		);

		$this->db->where('user_id', $user_id);
		$this->db->where('callsign', $callsign);
		$this->db->where('cert_dxcc_id', $dxcc);
		$this->db->update('lotw_certs', $data);
	}

	function delete_certificate($user_id, $lotw_cert_id) {
		$this->db->where('lotw_cert_id', $lotw_cert_id);
		$this->db->where('user_id', $user_id);
		$this->db->delete('lotw_certs');
	}

	function last_upload($certID, $message) {

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
		$sql = "SELECT * FROM `lotw_certs` WHERE `user_id` = ? AND `date_expires` < ?;";
		$query = $this->db->query($sql, array($user_id, $date));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function lotw_cert_expiring($user_id, $date) {
		$sql = "SELECT * FROM `lotw_certs` WHERE `user_id` = ? AND DATE_SUB(date_expires, INTERVAL 30 DAY) < ? AND `date_expires` > ?;";
		$query = $this->db->query($sql, array($user_id, $date, $date));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function lotw_cert_qsoenddate_expired($user_id, $date) {
		$sql = "SELECT * FROM `lotw_certs` WHERE `user_id` = ? AND `qso_end_date` < ?;";
		$query = $this->db->query($sql, array($user_id, $date));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function lotw_cert_qsoenddate_expiring($user_id, $date) {
		$sql = "SELECT * FROM `lotw_certs` WHERE `user_id` = ? AND DATE_SUB(qso_end_date, INTERVAL 30 DAY) < ? AND `qso_end_date` > ?;";
		$query = $this->db->query($sql, array($user_id, $date, $date));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function remove_lotw_credentials($user_id = null) {
		$sql = "UPDATE ".$this->config->item('auth_table')." SET user_lotw_password = '' WHERE user_id = ?;";
		$query = $this->db->query($sql, array($user_id));
		if ($this->db->affected_rows() > 0) {
			return true;
		} else {
			return false;
		}

	}

}
?>
