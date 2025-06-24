<?php

class Usermodes extends CI_Model {

	function all() {
		$options_object = $this->user_options_model->get_options('usermodes', array('option_name' => 'enabled_usermodes', 'option_key' => 'json_modes'))->result();
		$usermodes = json_decode($options_object[0]->option_value ?? '[]');
		$this->db->where('active', 1);	// Show only those which are not globally deactivated
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		$modes=$this->db->get('adif_modes');
		$retmodes=[];
		foreach ($modes->result() as $row) {
			if (count($usermodes)>0) {
				if (in_array($row->mode.'/'.($row_submode ?? ''), $usermodes)) {
					$row->active=1;
				} else {
					$row->active=0;
				}
			}
			$retmodes[]=$row;
		}
		return $retmodes;
	}

	function active() {
		$options_object = $this->user_options_model->get_options('usermodes', array('option_name' => 'enabled_usermodes', 'option_key' => 'json_modes'))->result();
		$usermodes = json_decode($options_object[0]->option_value ?? '[]');
		$this->db->where('active', 1);	// Show only those which are not globally deactivated
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		$modes=$this->db->get('adif_modes');
		$retmodes=[];
		foreach ($modes->result() as $row) {
			if (count($usermodes)>0) {
				if (in_array($row->mode.'/'.($row_submode ?? ''), $usermodes)) {
					$row->active=1;
					$retmodes[]=$row;
				} else {
					$row->active=0;
				}
			} else {
				$retmodes[]=$row;
			}
		}
		return $retmodes;
	}

	function mode($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('id', $clean_id);
		return $this->db->get('adif_modes');
	}

	function activate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);
		$options_object = $this->user_options_model->get_options('usermodes', array('option_name' => 'enabled_usermodes', 'option_key' => 'json_modes'))->result();
		$usermodes = json_decode($options_object[0]->option_value ?? '[]');
		$mode2act=$this->mode($id)->result()->mode.'/'.($this->mode($id)->result()->submode ?? '');
		return $mode2act;
	}

	function deactivate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);
		$data = array(
			'active' => '0',
		);
		$this->db->where('id', $clean_id);
		$this->db->update('adif_modes', $data);
		return true;
	}
}

?>
