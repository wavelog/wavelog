<?php

class Clublog_model extends CI_Model {

	function get_clublog_users() {
		$this->db->select('user_clublog_name, user_clublog_password, user_id');
		$this->db->where('coalesce(user_clublog_name, "") != ""');
		$this->db->where('coalesce(user_clublog_password, "") != ""');
		$query = $this->db->get($this->config->item('auth_table'));
		return $query->result();
	}

	function mark_qsos_sent($station_id) {
		$data = array(
	        'COL_CLUBLOG_QSO_UPLOAD_DATE' => date('Y-m-d'),
	        'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "Y",
		);

		$this->db->where("station_id", $station_id);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
    		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
    		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->group_end();
		$this->db->update($this->config->item('table_name'), $data);
	}

	function mark_qso_sent($qso_id) {
		$data = array(
	        'COL_CLUBLOG_QSO_UPLOAD_DATE' => date('Y-m-d'),
	        'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "Y",
		);

		$this->db->where("COL_PRIMARY_KEY", $qso_id);
		$this->db->update($this->config->item('table_name'), $data);
	}

	function get_last_five($station_id) {
		$this->db->where('station_id', $station_id);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();
		$this->db->limit(5); 
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function mark_all_qsos_notsent($station_id) {
		$data = array(
	        'COL_CLUBLOG_QSO_UPLOAD_DATE' => null,
	        'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "M",
	        'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "N",
		);

		$this->db->where("station_id", $station_id);
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "Y");
		$this->db->update($this->config->item('table_name'), $data);
	}

	function get_clublog_qsos($station_id){
		$this->db->select('*, dxcc_entities.name as station_country');
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');
		$this->db->where($this->config->item('table_name').'.station_id', $station_id);
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();
	
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	  }

	function clublog_last_qsl_rcvd_date($callsign) {
		$qso_table_name = $this->config->item('table_name');
		$this->db->from($qso_table_name);

		$this->db->join('station_profile', 'station_profile.station_id = '.$qso_table_name.'.station_id');
		$this->db->where('station_profile.station_callsign', $callsign);

		$this->db->select("DATE_FORMAT(COL_CLUBLOG_QSO_DOWNLOAD_DATE,'%Y%m%d') AS COL_CLUBLOG_QSO_DOWNLOAD_DATE", FALSE);
		$this->db->where('COL_CLUBLOG_QSO_DOWNLOAD_DATE IS NOT NULL');
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->order_by("COL_CLUBLOG_QSO_DOWNLOAD_DATE", "desc");
		$this->db->limit(1);

		$query = $this->db->get();
		$row = $query->row();

		if (isset($row->COL_CLUBLOG_QSO_DOWNLOAD_DATE)){
			return $row->COL_CLUBLOG_QSO_DOWNLOAD_DATE;
		} else {
			// No previous date (first time import has run?), so choose UNIX EPOCH!
			// Note: date is yyyy/mm/dd format
			return '19700101';
		}
	}
	
	function all_enabled($userid) {
		$sql="select sp.station_callsign, group_concat(sp.station_id) as station_ids from station_profile sp 
			inner join users u on (u.user_id=sp.user_id)
			where u.user_clublog_name is not null and u.user_clublog_password is not null and sp.clublogignore=0 and u.user_id=?
			group by sp.station_callsign";
		$query = $this->db->query($sql,$userid);
		return $query;
	}

	function all_with_count($userid) {
		$this->db->select('station_profile.station_id, station_profile.station_callsign, count('.$this->config->item('table_name').'.station_id) as qso_total');
		$this->db->from('station_profile');
		$this->db->join($this->config->item('table_name'),'station_profile.station_id = '.$this->config->item('table_name').'.station_id','left');
		$this->db->group_by('station_profile.station_id');
		$this->db->where('station_profile.user_id', $userid);
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();

		return $this->db->get();
	}
}

?>
