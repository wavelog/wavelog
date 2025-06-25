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
				if (in_array($row->mode.'~'.($row->submode ?? ''), $usermodes)) {
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
			if (count($usermodes)>0) {	// Something explicitly allowed, push it to array
				if (in_array($row->mode.'~'.($row->submode ?? ''), $usermodes)) {
					$row->active=1;
					$retmodes[]=$row;
				} else {
					$row->active=0;
				}
			} else {			// Default-Status? Nothing in array: So everything is allowed
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
		$clean_id = $this->security->xss_clean($id);
		$mode2act=$this->mode($clean_id)->result()[0]->mode.'~'.($this->mode($clean_id)->result()[0]->submode ?? '');

		$modes=[];
		$raw_modes=$this->all();
		foreach ($raw_modes as $row) {
			if (($row->id == $clean_id) || ($row->active == 1)) {
				$modes[]=$row->mode.'~'.($row->submode ?? '');
			}
		}
		$this->user_options_model->set_option('usermodes', 'enabled_usermodes',  array('json_modes' => json_encode($modes)));
		return true;
	}

	function deactivate($id) {
		$clean_id = $this->security->xss_clean($id);
		$mode2act=$this->mode($clean_id)->result()[0]->mode.'~'.($this->mode($clean_id)->result()[0]->submode ?? '');

		$modes=[];
		$raw_modes=$this->all();
		foreach ($raw_modes as $row) {
			if (($row->id != $clean_id) && ($row->active == 1)) {
				$modes[]=$row->mode.'~'.($row->submode ?? '');
			}
		}
		$this->user_options_model->set_option('usermodes', 'enabled_usermodes',  array('json_modes' => json_encode($modes)));
		return true;
	}

	function activateAll() {
		// Clean ID
		$this->user_options_model->set_option('usermodes', 'enabled_usermodes',  array('json_modes' => '[]'));	// Empty/Nothing is default (prevents migration)
		return true;
	}

	function deactivateAll() {
		$this->user_options_model->set_option('usermodes', 'enabled_usermodes',  array('json_modes' => '[""]')); // Put at least one senseless element to array, to deactivate all on userlevel
		return true;
	}
}

?>
