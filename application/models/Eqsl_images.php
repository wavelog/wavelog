<?php

class Eqsl_images extends CI_Model {

	function get_image($qso_id) {
		$sql = "SELECT image_file 
				FROM `eQSL_images` 
				WHERE qso_id = ?";

		$query = $this->db->query($sql, [$qso_id]);

		$row = $query->row();

		if (isset($row)) {
			return $row->image_file;
		} else {
			return "No Image";
		}
	}

	function del_image($qso_id, $user_id = null) {
		$this->load->library('paths');
		// QSO belongs to station_profile. But since we have folders for Users (and therefore an extra indirect relation) we need to lookup user for station first...
		$table_name = $this->config->item('table_name');
		$sql = "SELECT e.image_file,e.id, qso.station_id, s.user_id 
				FROM `eQSL_images` e 
				INNER JOIN {$table_name} qso ON (e.qso_id = qso.COL_PRIMARY_KEY) 
				INNER JOIN station_profile s ON (s.station_id = qso.station_id) 
				WHERE qso.COL_PRIMARY_KEY = ?";

		$eqsl_img = $this->db->query($sql, [$qso_id]);
		
		if (!valid_uid($user_id)) {
			$user_id = $this->session->userdata('user_id');
		}
		foreach ($eqsl_img->result() as $row) {
			// Calling as User? Check if User-id matches User-id from QSO
			if ($row->user_id != $user_id) {
				return "No Image"; // Image doesn't belong to user, so return
			}
			$image = $this->paths->getUserdataPath('eqsl_card', 'p', $row->user_id) . '/' . $row->image_file;
			unlink($image);
			$this->db->delete('eQSL_images', [['id' => $row->id]]);
			return $image;
		}
	}

	function save_image($qso_id, $image_name) {
		$data = [
			'qso_id' => $qso_id,
			'image_file' => $image_name,
		];

		$this->db->insert('eQSL_images', $data);

	}

	function count_eqsl_qso_list() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		if ($logbooks_locations_array[0] === -1) {
			return;
		}
		$table_name = $this->config->item('table_name');
		$sql = "SELECT COUNT(*) AS cnt
				FROM `eQSL_images` e
				INNER JOIN {$table_name} qso ON (e.qso_id = qso.COL_PRIMARY_KEY)
				INNER JOIN station_profile s ON (s.station_id = qso.station_id)
				WHERE s.station_id IN ?";

		$query = $this->db->query($sql, [$logbooks_locations_array]);
		return (int) $query->row()->cnt;
	}

	function eqsl_qso_list($limit = null, $offset = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		if ($logbooks_locations_array[0] === -1) {
			return;
		}
		$table_name = $this->config->item('table_name');
		$sql = "SELECT qso.COL_PRIMARY_KEY, e.qso_id, qso.COL_CALL, qso.COL_MODE, qso.COL_SUBMODE, qso.COL_TIME_ON, qso.COL_BAND, qso.COL_PROP_MODE, qso.COL_SAT_NAME, qso.COL_QSLMSG_RCVD, qso.COL_EQSL_QSLRDATE, e.image_file
				FROM `eQSL_images` e
				INNER JOIN {$table_name} qso ON (e.qso_id = qso.COL_PRIMARY_KEY)
				INNER JOIN station_profile s ON (s.station_id = qso.station_id)
				WHERE s.station_id IN ?
				ORDER BY qso.COL_TIME_ON DESC";

		$binds = [$logbooks_locations_array];
		if ($limit !== null && $offset !== null) {
			$sql .= " LIMIT ? OFFSET ?";
			$binds[] = (int) $limit;
			$binds[] = (int) $offset;
		}
		return $this->db->query($sql, $binds);
	}
}
