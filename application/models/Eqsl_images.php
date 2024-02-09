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
		$this->db->select('COL_PRIMARY_KEY, qso_id, COL_CALL, COL_MODE, , COL_SUBMODE, COL_TIME_ON, COL_BAND, COL_SAT_NAME, image_file');
		$this->db->join($this->config->item('table_name'), 'qso_id = COL_PRIMARY_KEY', 'left outer');
		$this->db->join('station_profile', $this->config->item('table_name').'.station_id = station_profile.station_id', 'left outer');
		$this->db->where_in('station_profile.station_id', $logbooks_locations_array);
		$this->db->order_by('COL_TIME_ON', 'DESC');
		return $this->db->get('eQSL_images');
	}

	// return path of eQsl file : u=url / p=real path //
	function get_imagePath($pathorurl='u') {
		$eqsl_dir = "eqsl_card";
		// test if new folder directory exist // 
		$userdata_dir = $this->config->item('userdata');
		if (isset($userdata_dir)) {
			if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir)) {
				mkdir(realpath(APPPATH.'../').'/'.$userdata_dir, 0755, true);
			}
			if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$eqsl_dir)) {
				mkdir(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$eqsl_dir, 0755, true);
			}
			if ($pathorurl=='u') {
				return $userdata_dir.'/'.$eqsl_dir;
			} else {
				return realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$eqsl_dir;
			}
		} else {
			return 'images/eqsl_card_images';
		}
	}
}

?>
