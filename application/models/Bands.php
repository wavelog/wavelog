<?php

class Bands extends CI_Model {

	private $logbooks_locations_array;
	public function __construct()
	{
		$this->load->model('logbooks_model');
		$this->logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
	}


	public $bandslots = array(
		"160m"=>0,
		"80m"=>0,
		"60m"=>0,
		"40m"=>0,
		"30m"=>0,
		"20m"=>0,
		"17m"=>0,
		"15m"=>0,
		"12m"=>0,
		"10m"=>0,
		"6m"=>0,
		"4m"=>0,
		"2m"=>0,
		"1.25m"=>0,
		"70cm"=>0,
		"33cm"=>0,
		"23cm"=>0,
		"13cm"=>0,
		"9cm"=>0,
		"6cm"=>0,
		"3cm"=>0,
		"1.25cm"=>0,
		"SAT"=>0,
	);

	function get_user_bands($award = 'None') {
		$this->db->from('bands');
		$this->db->join('bandxuser', 'bandxuser.bandid = bands.id');
		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));
		$this->db->where('bandxuser.active', 1);

		if ($award != 'None') {
			$this->db->where('bandxuser.'.$award, 1);
		}

		$result = $this->db->get()->result();

		$results = array();

		foreach($result as $band) {
			array_push($results, $band->band);
		}

		return $results;
	}

	function get_all_bands() {
		$this->db->from('bands');

		$result = $this->db->get()->result();

		$results = array();

		foreach($result as $band) {
			$results['b'.strtoupper($band->band)] = array('CW' => $band->cw, 'SSB' => $band->ssb, 'DATA' => $band->data);
		}

		return $results;
	}

	function get_user_bands_for_qso_entry($includeall = false) {
		$this->db->from('bands');
		$this->db->join('bandxuser', 'bandxuser.bandid = bands.id');
		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));
		if (!$includeall) {
			$this->db->where('bandxuser.active', 1);
		}
		$this->db->where('bands.bandgroup != "sat"');

		$result = $this->db->get()->result();

		$results = array();

		foreach($result as $band) {
			$results[$band->bandgroup][] = $band->band;
		}

		return $results;
	}

	function get_all_bands_for_user() {
		$this->db->from('bands');
		$this->db->join('bandxuser', 'bandxuser.bandid = bands.id');
		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));

		return $this->db->get()->result();
	}

	function get_all_bandedges_for_user() {
		$this->db->from('bandedges');
		$this->db->where('bandedges.userid', $this->session->userdata('user_id'));
		$this->db->order_by('frequencyfrom', 'ASC');

		$result = $this->db->get()->result();

		if ($result) {
			return $result;
		}

		$this->insert_band_edges_for_user();

		$this->db->from('bandedges');
		$this->db->where('bandedges.userid', -1);
		$this->db->order_by('frequencyfrom', 'ASC');

		return $this->db->get()->result();
	}

	function insert_band_edges_for_user() {
		// Get band edges from default user
		$this->db->from('bandedges');
		$this->db->where('bandedges.userid', -1);
		$result = $this->db->get()->result();

		if ($result) {
			foreach($result as $edge) {
				$data = array(
					'frequencyfrom' => $edge->frequencyfrom,
					'frequencyto' => $edge->frequencyto,
					'mode' => $edge->mode,
					'userid' => $this->session->userdata('user_id')
				);
				$this->db->insert('bandedges', $data);
			}
		}
		return true;
	}

	function all() {
		return $this->bandslots;
	}

	function get_worked_bands($award = 'None') {
		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

		// get all worked slots from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_BAND`) as `COL_BAND` FROM `".$this->config->item('table_name')."` WHERE station_id in (" . $location_list . ") AND COL_PROP_MODE != \"SAT\""
		);
		$worked_slots = array();
		foreach($data->result() as $row){
			array_push($worked_slots, $row->COL_BAND);
		}

		$SAT_data = $this->db->query(
			"SELECT distinct LOWER(`COL_PROP_MODE`) as `COL_PROP_MODE` FROM `".$this->config->item('table_name')."` WHERE station_id in (" . $location_list . ") AND COL_PROP_MODE = \"SAT\""
		);

		foreach($SAT_data->result() as $row){
			array_push($worked_slots, strtoupper($row->COL_PROP_MODE));
		}

		// bring worked-slots in order of defined $bandslots
		$bandslots = $this->get_user_bands($award);

		$results = array();
		foreach($bandslots as $slot) {
			if(in_array($slot, $worked_slots)) {
				array_push($results, $slot);
			}
		}

		return $results;
	}

	function get_worked_bands_distances() {
		if (!$this->logbooks_locations_array) {
			return array();
		}
		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

        // get all worked slots from database
        $sql = "SELECT distinct LOWER(COL_BAND) as COL_BAND FROM ".$this->config->item('table_name')." WHERE station_id in (" . $location_list . ")";

        $data = $this->db->query($sql);
        $worked_slots = array();
        foreach($data->result() as $row){
            array_push($worked_slots, $row->COL_BAND);
        }

        // bring worked-slots in order of defined $bandslots
		$bandslots = $this->get_user_bands();

		$results = array();
		foreach($bandslots as $slot) {
			if(in_array($slot, $worked_slots)) {
				array_push($results, $slot);
			}
		}
        return $results;
    }

	function get_worked_sats() {
		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

		// get all worked sats from database
		$sql = "SELECT distinct col_sat_name FROM ".$this->config->item('table_name')." WHERE station_id in (" . $location_list . ") and coalesce(col_sat_name, '') <> '' ORDER BY col_sat_name";

		$data = $this->db->query($sql);

		$worked_sats = array();
		foreach($data->result() as $row){
			array_push($worked_sats, $row->col_sat_name);
		}

		return $worked_sats;
	}

	function get_worked_orbits() {
		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

		// get all worked orbit types from database
		$sql = "SELECT DISTINCT satellite.orbit AS orbit FROM ".$this->config->item('table_name')." LEFT JOIN satellite ON COL_SAT_NAME = satellite.name WHERE station_id in (" . $location_list . ") AND COL_PROP_MODE = 'SAT' AND satellite.orbit IS NOT NULL ORDER BY orbit ASC";

		$data = $this->db->query($sql);

		$worked_orbits = array();
		foreach($data->result() as $row){
			array_push($worked_orbits, $row->orbit);
		}

		return $worked_orbits;
	}

	function get_worked_bands_dok() {
		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

		// get all worked slots from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_BAND`) as `COL_BAND` FROM `".$this->config->item('table_name')."` WHERE station_id in (" . $location_list . ") AND COL_DARC_DOK IS NOT NULL AND COL_DARC_DOK != ''  AND COL_DXCC = 230 "
		);
		$worked_slots = array();
		foreach($data->result() as $row){
			array_push($worked_slots, $row->COL_BAND);
		}

		// bring worked-slots in order of defined $bandslots
		$bandslots = $this->get_user_bands('dok');

		$results = array();
		foreach($bandslots as $slot) {
			if(in_array($slot, $worked_slots)) {
				array_push($results, $slot);
			}
		}
		return $results;
	}

	function activateall() {
        $data = array(
            'active' => '1',
        );
		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));

        $this->db->update('bandxuser', $data);

        return true;
    }

    function deactivateall() {
        $data = array(
            'active' => '0',
        );
		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));

        $this->db->update('bandxuser', $data);

        return true;
    }

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Mode
		$this->db->delete('bandxuser', array('id' => $clean_id));
	}

	function saveBand($id, $band) {
        $data = array(
			'active' 	 => $band['status']		== "true" ? '1' : '0',
			'cq' 		 => $band['cq'] 		== "true" ? '1' : '0',
			'dok' 		 => $band['dok'] 		== "true" ? '1' : '0',
			'dxcc' 		 => $band['dxcc'] 		== "true" ? '1' : '0',
			'helvetia' 	 => $band['helvetia'] 	== "true" ? '1' : '0',
			'iota' 		 => $band['iota'] 		== "true" ? '1' : '0',
			'jcc' 		 => $band['jcc'] 		== "true" ? '1' : '0',
			'pota' 		 => $band['pota'] 		== "true" ? '1' : '0',
			'rac' 		 => $band['rac'] 		== "true" ? '1' : '0',
			'sig' 		 => $band['sig'] 		== "true" ? '1' : '0',
			'sota'		 => $band['sota'] 		== "true" ? '1' : '0',
			'uscounties' => $band['uscounties'] == "true" ? '1' : '0',
			'wap' 	 	 => $band['wap'] 		== "true" ? '1' : '0',
			'waja' 		 => $band['waja'] 		== "true" ? '1' : '0',
			'was' 		 => $band['was'] 		== "true" ? '1' : '0',
			'wwff' 		 => $band['wwff'] 		== "true" ? '1' : '0',
			'vucc'		 => $band['vucc'] 		== "true" ? '1' : '0'
        );

		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));
        $this->db->where('bandxuser.id', $id);

        $this->db->update('bandxuser', $data);

        return true;
    }

	function saveBandAward($award, $status) {
		$data = array(
			$award 	 => $status == "true" ? '1' : '0',
        );

		$this->db->where('bandxuser.userid', $this->session->userdata('user_id'));

        $this->db->update('bandxuser', $data);

        return true;
    }

	function add($band_data) {

		$this->db->where('band', $band_data['band']);
		$result = $this->db->get('bands');

		if ($result->num_rows() == 0) {
		   $this->db->insert('bands', $band_data);
		}

		$binding = [];
		$sql = "insert into bandxuser (bandid, userid) select bands.id, "
			. $this->session->userdata('user_id')
			. " from bands where band = ?
			and not exists (select 1 from bandxuser where userid = " . $this->session->userdata('user_id') . "
			and bandid = bands.id);";
		$binding[] = $band_data['band'];

		$this->db->query($sql, $binding);
	}

	function getband($id) {
		$this->db->where('id', $id);
		return $this->db->get('bands');
	}

	function saveupdatedband($id, $band) {
		$data = array(
			'band' 		=> $band['band'],
			'bandgroup' => $band['bandgroup'],
			'ssb'	 	=> $band['ssbqrg'],
			'data' 		=> $band['dataqrg'],
			'cw' 		=> $band['cwqrg'],
        );

        $this->db->where('bands.id', $id);

        $this->db->update('bands', $data);

        return true;
	}

	function get_worked_bands_oqrs($station_id) {

		// get all worked slots from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_BAND`) as `COL_BAND` FROM `".$this->config->item('table_name')."` WHERE station_id = ? AND COL_PROP_MODE != \"SAT\"", $station_id
		);
		$worked_slots = array();
		foreach($data->result() as $row){
			array_push($worked_slots, $row->COL_BAND);
		}

		$SAT_data = $this->db->query(
			"SELECT distinct LOWER(`COL_PROP_MODE`) as `COL_PROP_MODE` FROM `".$this->config->item('table_name')."` WHERE station_id = ? AND COL_PROP_MODE = \"SAT\"", $station_id
		);

		foreach($SAT_data->result() as $row){
			array_push($worked_slots, strtoupper($row->COL_PROP_MODE));
		}

		// php5
		usort(
			$worked_slots,
			function($b, $a) {
				sscanf($a, '%f%s', $ac, $ar);
				sscanf($b, '%f%s', $bc, $br);
				if ($ar == $br) {
					return ($ac < $bc) ? -1 : 1;
				}
				return ($ar < $br) ? -1 : 1;
			}
		);

		// Only for php7+
		// usort(
		// 	$worked_slots,
		// 	function($b, $a) {
		// 		sscanf($a, '%f%s', $ac, $ar);
		// 		sscanf($b, '%f%s', $bc, $br);
		// 		return ($ar == $br) ? $ac <=> $bc : $ar <=> $br;
		// 	}
		// );

		return $worked_slots;
	}

	function get_worked_bands_eme() {
		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'".implode("','",$this->logbooks_locations_array)."'";

		// get all worked slots from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_BAND`) as `COL_BAND` FROM `".$this->config->item('table_name')."` WHERE station_id in (" . $location_list . ") AND COL_PROP_MODE = 'EME'"
		);
		$worked_slots = array();
		foreach($data->result() as $row){
			array_push($worked_slots, $row->COL_BAND);
		}

		return $worked_slots;
	}

	function deletebandedge($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Bandedge
		$this->db->delete('bandedges', array('id' => $clean_id, 'userid' => $this->session->userdata('user_id')));
	}

	function check4overlapEdges($id, $frequencyfrom, $frequencyto, $mode) {
		$edges = $this->bands->get_all_bandedges_for_user();
		foreach ($edges as $item) {
			if ($item->id == ($id ?? -1000)) {
				continue;
			}
			$from = (int)$item->frequencyfrom;
			$to = (int)$item->frequencyto;
			if (!($frequencyto < $from || $frequencyfrom > $to)) {
				return true;
			}
		}
		return false;
	}

	function saveBandEdge($id, $frequencyfrom, $frequencyto, $mode) {
		$data = array(
			'frequencyfrom' => $frequencyfrom,
			'frequencyto' => $frequencyto,
			'mode' => $mode,
			'userid' => $this->session->userdata('user_id')
		);

		if ($id > 0) {
			$this->db->where('id', $id);
			$this->db->where('userid', $this->session->userdata('user_id'));
			return $this->db->update('bandedges', $data);
		} else {
			return $this->db->insert('bandedges', $data);
		}
	}
}

?>
