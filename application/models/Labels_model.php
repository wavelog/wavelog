<?php

class Labels_model extends CI_Model {
    function addLabel() {
		$data = array(
			'user_id' 		=> $this->session->userdata('user_id'),
            'label_name' 	=> xss_clean($this->input->post('label_name', true)),
            'paper_type_id' 	=> xss_clean($this->input->post('paper_type_id', true)),
            'metric' 		=> xss_clean($this->input->post('measurementType', true)),
            'marginleft' 	=> str_replace(',', '.',( xss_clean($this->input->post('marginLeft', true)))),
            'margintop' 	=> str_replace(',', '.',( xss_clean($this->input->post('marginTop', true)))),
            'nx' 		    => xss_clean($this->input->post('NX', true)),
            'ny' 		    => xss_clean($this->input->post('NY', true)),
            'spacex' 		=> str_replace(',', '.', (xss_clean($this->input->post('SpaceX', true)))),
            'spacey' 		=> str_replace(',', '.', (xss_clean($this->input->post('SpaceY', true)))),
            'width' 		=> str_replace(',', '.', (xss_clean($this->input->post('width', true)))),
            'height' 		=> str_replace(',', '.', (xss_clean($this->input->post('height', true)))),
            'font_size' 	=> xss_clean($this->input->post('font_size', true)),
            'qsos' 		    => xss_clean($this->input->post('label_qsos', true)),
            'font' 		    => xss_clean($this->input->post('font', true)),
            'last_modified' => date('Y-m-d H:i:s'),
		);

	   $this->db->insert('label_types', $data);

	}

	function addPaper() {
		$data = array(
			'user_id' 		=> $this->session->userdata('user_id'),
            'paper_name' 	=> xss_clean($this->input->post('paper_name', true)),
            'metric' 		=> xss_clean($this->input->post('measurementType', true)),
            'width' 		=> str_replace(',', '.', (xss_clean($this->input->post('width', true)))),
            'height' 		=> str_replace(',', '.', (xss_clean($this->input->post('height', true)))),
            'orientation'	=> xss_clean($this->input->post('orientation', true)),
            'last_modified' => date('Y-m-d H:i:s'),
		);

	   $this->db->insert('paper_types', $data);

	}

    function getLabel($id,$user_id) {
	$sql="SELECT l.id, l.user_id,l.label_name, p.paper_name, p.paper_id,l.paper_type_id,l.metric, l.marginleft, l.margintop, l.nx, l.ny, l.spacex, l.spacey, l.width, l.height, l.font_size, l.font, l.qsos, l.useforprint, l.last_modified FROM label_types l left outer join paper_types p on (p.user_id=l.user_id and p.paper_id=l.paper_type_id) where l.user_id=? and l.id=?;";
        $query=$this->db->query($sql,array($user_id,$id));
        $result=$query->result();
        return $result[0];
	}


    function updateLabel($id) {
        $data = array(
			'user_id' 		=> $this->session->userdata('user_id'),
            'label_name' 	=> xss_clean($this->input->post('label_name', true)),
            'paper_type_id' 	=> xss_clean($this->input->post('paper_type_id', true)),
            'metric' 		=> xss_clean($this->input->post('measurementType', true)),
            'marginleft' 	=> str_replace(',', '.', (xss_clean($this->input->post('marginLeft', true)))),
            'margintop' 	=> str_replace(',', '.', (xss_clean($this->input->post('marginTop', true)))),
            'nx' 		    => xss_clean($this->input->post('NX', true)),
            'ny' 		    => xss_clean($this->input->post('NY', true)),
            'spacex' 		=> str_replace(',', '.', (xss_clean($this->input->post('SpaceX', true)))),
            'spacey' 		=> str_replace(',', '.', (xss_clean($this->input->post('SpaceY', true)))),
            'width' 		=> str_replace(',', '.', (xss_clean($this->input->post('width', true)))),
            'height' 		=> str_replace(',', '.', (xss_clean($this->input->post('height', true)))),
            'font_size' 	=> xss_clean($this->input->post('font_size', true)),
            'qsos' 		    => xss_clean($this->input->post('label_qsos', true)),
            'font' 		    => xss_clean($this->input->post('font', true)),
            'last_modified' => date('Y-m-d H:i:s'),
		);

        $cleanid = $this->security->xss_clean($id);

        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('id', $cleanid);
        $this->db->update('label_types', $data);
    }

    function deleteLabel($id) {
        $cleanid = xss_clean($id);

        $this->db->delete('label_types', array('id' => $cleanid, 'user_id' => $this->session->userdata('user_id')));
    }

    function fetchLabels($user_id) {
	$sql="SELECT l.id, l.user_id,l.label_name, p.paper_name, l.metric, l.marginleft, l.margintop, l.nx, l.ny, l.spacex, l.spacey, l.width, l.height, l.font_size, l.font, l.qsos, l.useforprint, l.last_modified FROM label_types l left outer join paper_types p on (p.user_id=l.user_id and p.paper_id=l.paper_type_id) where l.user_id=?;";
        $query=$this->db->query($sql,$user_id);
        return $query->result();
	}

	function fetchPapertypes($user_id) {
		$sql="SELECT p.paper_id,p.user_id,p.paper_name,p.metric,p.width,p.height,p.last_modified, p.orientation,COUNT(DISTINCT l.id) AS lbl_cnt FROM paper_types p  LEFT OUTER JOIN  label_types l ON (p.paper_id = l.paper_type_id and p.user_id=l.user_id) WHERE p.user_id = ? group by p.paper_id,p.user_id,p.paper_name,p.metric,p.width,p.height,p.last_modified;";
        	$query = $this->db->query($sql, $this->session->userdata('user_id'));
        	return $query->result();
	}

 	function fetchQsos($user_id) {

		$qsl = "select count(*) count, station_profile.station_profile_name, station_profile.station_callsign, station_profile.station_id, station_profile.station_gridsquare
        from ". $this->config->item('table_name') . " as l
        join station_profile on l.station_id = station_profile.station_id
        where l.COL_QSL_SENT in ('R', 'Q')
        and station_profile.user_id = " . $user_id .
        " group by station_profile.station_profile_name, station_profile.station_callsign, station_profile.station_id, station_profile.station_gridsquare
        order by station_profile.station_callsign";

        $query = $this->db->query($qsl);

		return $query->result();
	}

    function getDefaultLabel() {
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('useforprint', '1');
		$query = $this->db->get('label_types');

        return $query->row();
    }

    function getPaperType($ptype_id) {
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('paper_id',$ptype_id);
		$query = $this->db->get('paper_types');

        return $query->row();
    }

    function saveDefaultLabel($id) {
        $sql = 'update label_types set useforprint = 0 where user_id = ' . $this->session->userdata('user_id');
        $this->db->query($sql);

        $cleanid = xss_clean($id);
        $sql = 'update label_types set useforprint = 1 where user_id = ' . $this->session->userdata('user_id') . ' and id = ' . $cleanid;
        $this->db->query($sql);
    }

    function export_printrequested($station_id = NULL) {
        $this->load->model('stations');
        $active_station_id = $this->stations->find_active();

        $table_name = $this->config->item('table_name');
        $user_id = $this->session->userdata('user_id');

        $sql = "SELECT " . $table_name . ".*, station_profile.*, dxcc_entities.name as station_country
                FROM " . $table_name . "
                JOIN station_profile ON station_profile.station_id = " . $table_name . ".station_id
                JOIN dxcc_entities ON station_profile.station_dxcc = dxcc_entities.adif
                JOIN dxcc_entities dxc2 ON ".$table_name.".COL_DXCC = dxc2.adif
                WHERE station_profile.user_id = ?
                AND COL_QSL_SENT IN ('R', 'Q')";

        $binding = array($user_id);

        if ($station_id == NULL) {
            $sql .= " AND " . $table_name . ".station_id = ?";
            $binding[] = $active_station_id;
        } else if ($station_id != 'All') {
            $sql .= " AND " . $table_name . ".station_id = ?";
            $binding[] = $station_id;
        }

        $sql .= " ORDER BY dxc2.prefix ASC, COL_CALL ASC, COL_SAT_NAME ASC, COL_SAT_MODE ASC, COL_BAND_RX ASC, COL_TIME_ON ASC, COL_MODE ASC";

        $query = $this->db->query($sql, $binding);

        return $query;
    }

    function export_printrequestedids($ids) {
        $table_name = $this->config->item('table_name');
        $user_id = $this->session->userdata('user_id');

        $sql = "SELECT " . $table_name . ".*, station_profile.*, dxcc_entities.name as station_country
                FROM " . $table_name . "
                JOIN station_profile ON station_profile.station_id = " . $table_name . ".station_id
                JOIN dxcc_entities ON station_profile.station_dxcc = dxcc_entities.adif
                JOIN dxcc_entities dxc2 ON ".$table_name.".COL_DXCC = dxc2.adif
                WHERE station_profile.user_id = ?
                AND COL_PRIMARY_KEY IN (";

        $binding = array($user_id);

        $placeholders = array_fill(0, count($ids), '?');
        $sql .= implode(',', $placeholders);
        $sql .= ") ORDER BY dxc2.prefix ASC, COL_CALL ASC, COL_SAT_NAME ASC, COL_SAT_MODE ASC, COL_BAND_RX ASC, COL_TIME_ON ASC, COL_MODE ASC";

        $binding = array_merge($binding, $ids);

        $query = $this->db->query($sql, $binding);

        return $query;
    }

	function updatePaper($id) {
        $data = array(
			'user_id' 		=> $this->session->userdata('user_id'),
            'paper_name' 	=> xss_clean($this->input->post('paper_name', true)),
            'metric' 		=> xss_clean($this->input->post('measurementType', true)),
            'width' 		=> str_replace(',', '.', (xss_clean($this->input->post('width', true)))),
            'height' 		=> str_replace(',', '.', (xss_clean($this->input->post('height', true)))),
            'orientation'	=> xss_clean($this->input->post('orientation', true)),
            'last_modified' => date('Y-m-d H:i:s'),
		);

        $cleanid = $this->security->xss_clean($id);

        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('paper_id', $cleanid);
        $this->db->update('paper_types', $data);
    }

    function label_cnt_with_paper($paper_id) {
	    $clean_paper_id=xss_clean($paper_id);
	    $sql="select count(distinct l.id) as CNT from label_types l inner join paper_types p on (p.paper_id=l.paper_type_id) where l.user_id=? and p.user_id=? and l.paper_type_id=?";
	    $query = $this->db->query($sql, array($this->session->userdata('user_id'), $this->session->userdata('user_id'), $clean_paper_id));
	    $row = $query->row();
	    if (isset($row)) {
		    return($row->CNT);
	    } else {
		    return 0;
	    }
    }

    function deletePaper($id) {
        $cleanid = xss_clean($id);

        $this->db->delete('paper_types', array('paper_id' => $cleanid, 'user_id' => $this->session->userdata('user_id')));
    }

	function getPaper($id) {
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('paper_id', $id);
		$query = $this->db->get('paper_types');

        return $query->row();
    }
}
