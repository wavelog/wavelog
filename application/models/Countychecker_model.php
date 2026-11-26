<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Countychecker_model extends CI_Model {

	/**
	 * Streams the QSOs to check one row at a time: the current user's QSOs with
	 * a logged gridsquare of 6+ characters against one of the county DXCCs
	 *
	 * @param string $station_id Station location to restrict to, or 'all' for every location
	 * @param array $county_dxccs DXCC entity numbers with county polygons (see Countychecker::$countyFiles)
	 * @return \Generator Yields one stdClass per QSO (see db_unbuffered_rows())
	 */
	public function getQsos($station_id, $county_dxccs) {
		$sql = 'select col_call, col_dxcc, col_state, col_cnty, col_time_on, station_profile.station_profile_name, col_primary_key, col_gridsquare, col_band,
				col_qsl_rcvd, col_qslrdate, col_lotw_qsl_rcvd, col_lotw_qslrdate, lotw_users.lastupload
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			left outer join lotw_users on lotw_users.callsign = ' . $this->config->item('table_name') . '.col_call
			where station_profile.user_id = ?
			and length(col_gridsquare) >= 6
			and col_dxcc in (' . implode(',', $county_dxccs) . ')';
		$params[] = $this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= ' and ' . $this->config->item('table_name') . '.station_id = ?';
			$params[] = $station_id;
		}

		$sql .= ' order by station_profile.station_profile_name asc, col_time_on desc';

		$this->load->helper('db');
		return db_unbuffered_rows($sql, $params);
	}

	/**
	 * Writes a "STATE,COUNTY" pair (ADIF notation) into COL_CNTY of a single
	 * QSO belonging to the current user, and the pair's state into COL_STATE
	 *
	 * @param int $qso_id COL_PRIMARY_KEY of the QSO
	 * @param string $state_county "STATE,COUNTY" pair to write
	 * @return int Number of updated rows (0 when the QSO was not found)
	 */
	public function updateCounty($qso_id, $state_county) {
		$sql = 'update ' . $this->config->item('table_name') . ' qsos
			join station_profile on station_profile.station_id = qsos.station_id
			set qsos.COL_CNTY = ?, qsos.COL_STATE = ?, qsos.last_modified = now()
			where qsos.COL_PRIMARY_KEY = ?
			and station_profile.user_id = ?';
		$this->db->query($sql, array($state_county, explode(',', $state_county)[0], $qso_id, $this->session->userdata('user_id')));

		return $this->db->affected_rows();
	}

}
