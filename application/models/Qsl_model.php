<?php
class Qsl_model extends CI_Model {
	function getQsoWithQslList() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->db->select('*');
		$this->db->from($this->config->item('table_name'));
		$this->db->join('qsl_images', 'qsl_images.qsoid = ' . $this->config->item('table_name') . '.col_primary_key');
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->order_by("id", "desc");

		return $this->db->get();
	}

	function getQslForQsoId($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		if (!$this->logbook_model->check_qso_is_accessible($clean_id)) {
			return;
		}

		$this->db->select('*');
		$this->db->from('qsl_images');
		$this->db->where('qsoid', $clean_id);

		return $this->db->get()->result();
	}

	function saveQsl($qsoid, $filename) {
		// Clean ID
		$clean_id = $this->security->xss_clean($qsoid);

		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		if (!$this->logbook_model->check_qso_is_accessible($clean_id)) {
			return;
		}

		$data = array(
			'qsoid' => $clean_id,
			'filename' => $filename
		);

		$this->db->insert('qsl_images', $data);

		return $this->db->insert_id();
	}

	function del_image_for_qso($qso_id, $user_id = null) {
		// QSO belongs to station_profile. But since we have folders for Users (and therefore an extra indirect relation) we need to lookup user for station first...
		$qsl_img=$this->db->query('SELECT e.filename, e.id, qso.station_id, s.user_id FROM qsl_images e INNER JOIN '.$this->config->item('table_name').' qso ON (e.qsoid = qso.COL_PRIMARY_KEY) inner join station_profile s on (s.station_id=qso.station_id) where qso.COL_PRIMARY_KEY=?',$qso_id);
		foreach ($qsl_img->result() as $row) {
			if (($user_id ?? '') == '') {					// Calling as User? Check if User-id matches User-id from QSO
				$user_id = $this->session->userdata('user_id');
				if ($row->user_id != $user_id) {
					return "No Image";				// Image doesn't belong to user, so return
				}
			}
			$image = $this->paths->getUserdataPath('qsl_card', 'p',$row->user_id).'/'.$row->filename;
			unlink($image);
			$this->db->delete('qsl_images', array('id' => $row->id));
		}
	}

	function deleteQsl($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		$this->db->select('qsoid');
		$this->db->from('qsl_images');
		$this->db->where('id', $clean_id);
		$qsoid = $this->db->get()->row()->qsoid;
		if (!$this->logbook_model->check_qso_is_accessible($qsoid)) {
			return;
		}
		// We cannot call del_image_for_qso here, since this one only deletes ONE QSL-Card (Multiple QSL-Cards can belong to one QSO)
		$path = $this->paths->getUserdataPath('qsl_card', 'p');
		$file = $this->getFilename($clean_id)->row();
		$filename = basename($file->filename);
		unlink($path.'/'.$filename);
		// Delete Mode
		$this->db->delete('qsl_images', array('id' => $clean_id));
	}

	function getFilename($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		$this->db->select('qsoid');
		$this->db->from('qsl_images');
		$this->db->where('id', $clean_id);
		$qsoid = $this->db->get()->row()->qsoid;
		if (!$this->logbook_model->check_qso_is_accessible($qsoid)) {
			return;
		}

		$this->db->select('filename');
		$this->db->from('qsl_images');
		$this->db->where('id', $clean_id);

		return $this->db->get();
	}

	function searchQsos($callsign) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->db->select('*');
		$this->db->from($this->config->item('table_name'));
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->where('col_call', $callsign);

		return $this->db->get();
	}

	function addQsotoQsl($qsoid, $filename) {
		// xss_clean already done in controller
		$clean_filename = basename($filename);

		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		if (!$this->logbook_model->check_qso_is_accessible($qsoid)) {
			return;
		}

		$data = array(
			'qsoid' => $qsoid,
			'filename' => $clean_filename
		);

		$this->db->insert('qsl_images', $data);

		return $this->db->insert_id();
	}

	function getConfirmations($confirmationtype) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array[0] === -1) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";
		$table = $this->config->item('table_name');
		$sql_parts = array();

		if (in_array('qsl', $confirmationtype)) {
			$sql_parts[] = "
				SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_gridsquare, col_vucc_grids, col_sat_name, col_qslrdate AS rxdate, 'QSL Card' AS type,
					EXISTS (SELECT 1 FROM qsl_images WHERE qsoid = $table.COL_PRIMARY_KEY) AS qslcount
				FROM $table
				WHERE station_id IN ($location_list) AND col_qslrdate IS NOT NULL AND coalesce(col_qslrdate, '') <> '' AND col_qsl_rcvd = 'Y'
			";
		}
		if (in_array('lotw', $confirmationtype)) {
			$sql_parts[] = "
				SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_gridsquare, col_vucc_grids, col_sat_name, col_lotw_qslrdate AS rxdate, 'LoTW' AS type, 0 as qslcount
				FROM $table
				WHERE station_id IN ($location_list) AND col_lotw_qslrdate IS NOT NULL AND coalesce(col_lotw_qslrdate, '') <> '' AND col_lotw_qsl_rcvd = 'Y'
			";
		}
		if (in_array('eqsl', $confirmationtype)) {
			$sql_parts[] = "
				SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_gridsquare, col_vucc_grids, col_sat_name, col_eqsl_qslrdate AS rxdate, 'eQSL' AS type, 0 as qslcount
				FROM $table
				WHERE station_id IN ($location_list) AND col_eqsl_qslrdate IS NOT NULL AND coalesce(col_eqsl_qslrdate, '') <> '' AND col_eqsl_qsl_rcvd = 'Y'
			";
		}
		if (in_array('qrz', $confirmationtype)) {
			$sql_parts[] = "
				SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_gridsquare, col_vucc_grids, col_sat_name, col_qrzcom_qso_download_date AS rxdate, 'QRZ.com' AS type, 0 as qslcount
				FROM $table
				WHERE station_id IN ($location_list) AND col_qrzcom_qso_download_date IS NOT NULL AND coalesce(col_qrzcom_qso_download_date, '') <> '' AND col_qrzcom_qso_download_status = 'Y'
			";
		}
		if (in_array('clublog', $confirmationtype)) {
			$sql_parts[] = "
				SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_gridsquare, col_vucc_grids, col_sat_name, col_clublog_qso_download_date AS rxdate, 'Clublog' AS type, 0 as qslcount
				FROM $table
				WHERE station_id IN ($location_list) AND col_clublog_qso_download_date IS NOT NULL AND coalesce(col_clublog_qso_download_date, '') <> '' AND col_clublog_qso_download_status = 'Y'
			";
		}

		if (count($sql_parts) == 0) {
			return array();
		}

		$sql = implode(" UNION ALL ", $sql_parts);
		$sql = "SELECT * FROM ( $sql ) AS unioned_results ORDER BY rxdate DESC LIMIT 1000";

		$query = $this->db->query($sql);

		return $query->result();
	}
}

