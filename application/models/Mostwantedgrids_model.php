<?php

class Mostwantedgrids_model extends CI_Model {

	function get_grids() {

		$sql = 'SELECT grid, perc FROM most_wanted_grids';

		return $this->db->query($sql);
	}

	/**
	 * Get the "most wanted" percentage for a 4-character gridsquare.
	 *
	 * @param string $grid 4-character Maidenhead gridsquare (e.g. "JO01").
	 * @return int|null Percentage of satellite operators needing this grid, or null when the grid is not listed.
	 */
	function get_perc($grid) {
		$query = $this->db->query('SELECT perc FROM most_wanted_grids WHERE grid = ?', array($grid));
		$row = $query->row();

		return $row ? (int) $row->perc : null;
	}

	function get_stats() {
		$sql = 'SELECT COUNT(*) AS number, MAX(perc) AS maxperc FROM most_wanted_grids;';
		return $this->db->query($sql);
	}

	function get_grid_details($gridsquare) {
		$url = "https://gridmaster.fr/api/grid.json?grid=".$gridsquare."&award=sat";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog DCL Connector');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		$rawdata = curl_exec($ch);
		$data = json_decode($rawdata);
		if (!is_object($data) || !isset($data->last) || !is_array($data->last)) {
				return null;
		}
		return $data;
		$data=json_decode($rawdata);
		return $data;
	}
}
