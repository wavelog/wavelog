<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_lotw_satupdate_mesat_sonate extends CI_Migration
{
	public function up() {
		$this->db->set('name', 'MO-122');
		$this->db->where('name', 'MESAT1');
		$this->db->or_where('name', 'MESAT-1');
		$this->db->update('satellite');

		$this->db->select('COUNT(name) AS count');
		$this->db->where('name', 'MO-122');
		$query = $this->db->get('satellite');
		$row = $query->row();
		if ($row->count == 0) {
			$data = array('name' => 'MO-122', 'exportname' => '', 'orbit' => 'LEO');
			$this->db->insert('satellite', $data);
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'LSB', '145925000', 'USB', '435825000' FROM satellite WHERE name = 'MO-122';");
		}

		$this->db->set('COL_SAT_NAME', 'MO-122');
		$this->db->set('COL_LOTW_QSL_SENT', 'N');
		$this->db->set('COL_LOTW_QSLSDATE', null);
		$this->db->where('COL_SAT_NAME', 'MESAT1');
		$this->db->or_where('COL_SAT_NAME', 'MESAT-1');
		$this->db->update($this->config->item('table_name'));

		$this->db->set('name', 'SONATE');
		$this->db->where('name', 'SONATE-2');
		$this->db->update('satellite');

		$this->db->select('COUNT(name) AS count');
		$this->db->where('name', 'SONATE');
		$query = $this->db->get('satellite');
		$row = $query->row();
		if ($row->count == 0) {
			$data = array('name' => 'SONATE', 'exportname' => '', 'orbit' => 'LEO');
			$this->db->insert('satellite', $data);
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/V', id, 'PKT', '145825000', 'PKT', '145825000' FROM satellite WHERE name = 'SONATE';");
		}

		$this->db->set('COL_SAT_NAME', 'SONATE');
		$this->db->set('COL_LOTW_QSL_SENT', 'N');
		$this->db->set('COL_LOTW_QSLSDATE', null);
		$this->db->where('COL_SAT_NAME', 'SONATE-2');
		$this->db->update($this->config->item('table_name'));

	}

	public function down()
	{
		// Not Possible
	}
}
