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
}
