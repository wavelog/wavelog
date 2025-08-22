<?php

class Modes extends CI_Model {

	function all() {
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		return $this->db->get('adif_modes');
	}
	
	function active() {
		$this->db->where('active', 1);
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		return $this->db->get('adif_modes');
	}

	function get_modes_from_qrgmode($qrgmode = '', $translate_from_ui = false) {
		// Clean ID
		$bindings=[];
		if ($translate_from_ui) {
			if ($qrgmode == 'digi') {
				$bindings[] = 'DATA';
			} elseif ($qrgmode == 'cw') {
				$bindings[] = 'CW';
			} elseif ($qrgmode == 'phone') {
				$bindings[] = 'SSB';
			} else {
				$bindings[]='';
			}
		} else {
			$bindings[]=$this->security->xss_clean($qrgmode);
		}

		$query = $this->db->query('select distinct mode from adif_modes where qrgmode = ?', $bindings);
		if ($query->num_rows() > 0) {
			$modes = [];
			foreach ($query->result() as $row) {
				$modes[] = "'".$this->security->xss_clean($row->mode)."'";
			}
			return '('.implode(',', $modes).')';
		} else {
			return '()';
		}
	}

	function get_qrgmode_from_mode($mode = '') {
		// Clean ID
		$bindings=[];
		$bindings[] = ($this->security->xss_clean($mode) ?? '');

		$query = $this->db->query('select qrgmode from adif_modes where mode = ?', $bindings);
		if ($query->num_rows() > 0) {
			return $query->row()->QRGMODE;
		} else {
			return '';
		}
	}

	function mode($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);


		$this->db->where('id', $clean_id);
		return $this->db->get('adif_modes');
	}


	function add() {
		if ($this->input->post('submode', true) == "")
			$submode = null;
		else
			$submode = xss_clean($this->input->post('submode', true));
		
		$data = array(
			'mode' => xss_clean($this->input->post('mode', true)),
			'submode' => $submode,
			'qrgmode' =>  xss_clean(strtoupper($this->input->post('qrgmode', true))),
			'active' =>  xss_clean($this->input->post('active', true)),
		);

		$this->db->insert('adif_modes', $data); 
	}

	function edit() {
		if ($this->input->post('submode', true) == "")
			$submode = null;
		else
			$submode = xss_clean($this->input->post('submode', true));
		
		$data = array(
			'mode' => xss_clean($this->input->post('mode', true)),
			'submode' => $submode,
			'qrgmode' =>  xss_clean(strtoupper($this->input->post('qrgmode', true))),
			'active' =>  xss_clean($this->input->post('active', true)),
		);

		$this->db->where('id', xss_clean($this->input->post('id', true)));
		$this->db->update('adif_modes', $data); 
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Mode
		$this->db->delete('adif_modes', array('id' => $clean_id)); 
	}

    function activate($id) {
        // Clean ID
        $clean_id = $this->security->xss_clean($id);

        $data = array(
            'active' => '1',
        );

        $this->db->where('id', $clean_id);

        $this->db->update('adif_modes', $data);

        return true;
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

	function activateall() {
        $data = array(
            'active' => '1',
        );

        $this->db->update('adif_modes', $data);

        return true;
    }

    function deactivateall() {
        $data = array(
            'active' => '0',
        );

        $this->db->update('adif_modes', $data);

        return true;
    }

}

?>
