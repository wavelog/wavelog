<?php

class Eqsl_images extends CI_Model {

	function get_image($qso_id) {
		$this->db->where('qso_id', $qso_id);
		$query = $this->db->get('eQSL_images'); 
		
		$row = $query->row();

		if(isset($row)) {
			return $row->image_file;
		} else {
			return "No Image";
		}
	}

	function del_image($qso_id, $user_id = null) {
		// QSO belongs to station_profile. But since we have folders for Users (and therefore an extra indirect relation) we need to lookup user for station first...
		$eqsl_img=$this->db->query('SELECT e.image_file,e.id, qso.station_id, s.user_id FROM `eQSL_images` e INNER JOIN '.$this->config->item('table_name').' qso ON (e.qso_id = qso.COL_PRIMARY_KEY) inner join station_profile s on (s.station_id=qso.station_id) where qso.COL_PRIMARY_KEY=?',$qso_id);
		foreach ($eqsl_img->result() as $row) {
			if (($user_id ?? '') == '') {					// Calling as User? Check if User-id matches User-id from QSO
				$user_id = $this->session->userdata('user_id');
				if ($row->user_id != $user_id) {
					return "No Image";				// Image doesn't belong to user, so return
				}
			}
			$image=$this->get_imagePath('p',$row->user_id).'/'.$row->image_file;
			unlink($image);
			$this->db->delete('eQSL_images', array('id' => $row->id));
			return $image;
		}
	}

	function save_image($qso_id, $image_name) {
		$data = array(
		        'qso_id' => $qso_id,
		        'image_file' => $image_name,
		);

		$this->db->insert('eQSL_images', $data);
	}

	function eqsl_qso_list() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->db->select('COL_PRIMARY_KEY, qso_id, COL_CALL, COL_MODE, , COL_SUBMODE, COL_TIME_ON, COL_BAND, COL_PROP_MODE, COL_SAT_NAME, COL_QSLMSG_RCVD, COL_EQSL_QSLRDATE, image_file');
		$this->db->join($this->config->item('table_name'), 'qso_id = COL_PRIMARY_KEY', 'left outer');
		$this->db->join('station_profile', $this->config->item('table_name').'.station_id = station_profile.station_id', 'left outer');
		$this->db->where_in('station_profile.station_id', $logbooks_locations_array);
		$this->db->order_by('COL_TIME_ON', 'DESC');
		return $this->db->get('eQSL_images');
	}

	// return path of eQsl file : u=url / p=real path 
	function get_imagePath($pathorurl='u', $user_id = null) {

		// test if new folder directory option is enabled
		$userdata_dir = $this->config->item('userdata');

		if (isset($userdata_dir)) {

			$eqsl_dir = "eqsl_card"; // make sure this is the same as in Debug_model.php function migrate_userdata()

			if (($user_id ?? '') == '') {
				$user_id = $this->session->userdata('user_id');
			}

			// check if there is a user_id in the session data and it's not empty
			if ($user_id != '') {

				// create the folder
				if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$user_id.'/'.$eqsl_dir)) {
					mkdir(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$user_id.'/'.$eqsl_dir, 0755, true);
				}

				// and return it
				if ($pathorurl=='u') {
					return $userdata_dir.'/'.$user_id.'/'.$eqsl_dir;
				} else {
					return realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$user_id.'/'.$eqsl_dir;
				}
			} else {
				log_message('info', 'Can not get eqsl image path because no user_id in session data');
			}
		} else {
			// if the config option is not set we just return the old path
			return 'images/eqsl_card_images';
		}
	}
}

?>
