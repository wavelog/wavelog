<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Distances_model extends CI_Model
{

	function get_distances($postdata, $measurement_base) {

		$clean_postdata = $this->security->xss_clean($postdata);

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array[0] === -1) {
			header('Content-Type: application/json');
			echo json_encode(array('Error' => 'No QSOs found to plot.'));
			return;
		}

		$result = array();

		foreach ($logbooks_locations_array as $station_id) {

			$station_gridsquare = $this->find_gridsquare($station_id);

			if ($station_gridsquare != null) {
				$gridsquare = explode(',', $station_gridsquare); // We need to convert to an array, since a user can enter several gridsquares

				$table = $this->config->item('table_name');
				$sql = "
					SELECT COL_PRIMARY_KEY, COL_DISTANCE, COL_ANT_PATH, col_call callsign, col_gridsquare grid
					FROM $table
					LEFT OUTER JOIN satellite
						ON $table.COL_PROP_MODE = 'SAT'
						AND ($table.COL_SAT_NAME = satellite.name
							OR (satellite.displayname != '' AND $table.COL_SAT_NAME = satellite.displayname))
					WHERE LENGTH(col_gridsquare) > 0 AND station_id = ?
				";

				$params = array($station_id);

				if ($clean_postdata['band'] != 'All') {
					if ($clean_postdata['band'] == 'sat') {
						$sql .= " AND col_prop_mode = ?";
						$params[] = $clean_postdata['band'];
						if ($clean_postdata['sat'] != 'All') {
							$sql .= " AND col_sat_name = ?";
							$params[] = $clean_postdata['sat'];
						}
					}
					else {
						$sql .= " AND col_band = ?";
						$params[] = $clean_postdata['band'];
					}
				}

				if ($clean_postdata['orbit'] != 'All') {
					$sql .= " AND satellite.orbit = ?";
					$params[] = $clean_postdata['orbit'];
				}

				if ( $clean_postdata['propagation'] == 'NoSAT' ) {		// All without SAT
					$sql .= " AND col_prop_mode != 'SAT'";
				} elseif ($clean_postdata['propagation'] == 'None') {	// Empty Propmode
					$sql .= " AND (TRIM(col_prop_mode) = '' OR col_prop_mode IS NULL)";
				} elseif ($clean_postdata['propagation'] == 'All') {		// Dont care for propmode
					; // No Prop-Filter
				} else {				// Propmode set, take care of it
					$sql .= " AND col_prop_mode = ?";
					$params[] = $clean_postdata['propagation'];
				}

				$queryresult = $this->db->query($sql, $params);

				if ($queryresult->result_array()) {
					$temp = $this->plot($queryresult->result_array(), $gridsquare, $measurement_base);

					$result = $this->mergeresult($result, $temp);

				}

			}

		}

		if ($result) {
			header('Content-Type: application/json');
			echo json_encode($result);
		}
		else {
			header('Content-Type: application/json');
			echo json_encode(array('Error' => 'No QSOs found to plot.'));
		}

	}

    /*
     * We merge the result from several station_id's. They can have different gridsquares, so we need to use the correct gridsquare to calculate the correct distance.
     */
	function mergeresult($result, $add) {
		if (sizeof($result) > 0) {
			if ($result['qrb']['Distance'] < $add['qrb']['Distance']) {
				$result['qrb']['Distance'] = $add['qrb']['Distance'];
				$result['qrb']['Grid'] 	   = $add['qrb']['Grid'];
				$result['qrb']['Callsign'] = $add['qrb']['Callsign'];
			}
			$result['qrb']['Qsos'] += $add['qrb']['Qsos'];

			for ($i = 0; $i <= 399; $i++) {

				if(isset($result['qsodata'][$i]['count'])) {
					$result['qsodata'][$i]['count'] += $add['qsodata'][$i]['count'];
				}

				if(isset($result['qsodata'][$i]['callcount'])) {
					if ($result['qsodata'][$i]['callcount'] < 5 && $add['qsodata'][$i]['callcount'] > 0) {
						$calls = explode(',', $add['qsodata'][$i]['calls']);
						foreach ($calls as $c) {
							if ($result['qsodata'][$i]['callcount'] < 5) {
								if ($result['qsodata'][$i]['callcount'] > 0) {
									$result['qsodata'][$i]['calls'] .= ', ';
								}
								$result['qsodata'][$i]['calls'] .= $c;
								$result['qsodata'][$i]['callcount']++;
							}
						}
					}
				}
			}
			return $result;
		}

		return $add;
	}

	/*
	 * Fetches the gridsquare from the station_id
	 */
	function find_gridsquare($station_id) {
		$this->db->where('station_id', $station_id);

		$result = $this->db->get('station_profile')->row_array();

		if ($result) {
			return $result['station_gridsquare'];
		}

		return null;
	}

    // This functions takes query result from the database and extracts grids from the qso,
    // then calculates distance between homelocator and locator given in qso.
    // It builds an array, which has 50km intervals, then inputs each length into the correct spot
    // The function returns a json-encoded array.
	function plot($qsoArray, $gridsquare, $measurement_base) {
		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$stationgrid = strtoupper($gridsquare[0]);              // We use only the first entered gridsquare from the active profile
		if (strlen($stationgrid) == 4) $stationgrid .= 'MM';    // adding center of grid if only 4 digits are specified

		switch ($measurement_base) {
		case 'M':
			$unit = "mi";
			$dist = '26000';
			break;
		case 'K':
			$unit = "km";
			$dist = '40050';
			break;
		case 'N':
			$unit = "nmi";
			$dist = '22000';
			break;
		default:
			$unit = "km";
			$dist = '40050';
		}

		if (!$this->valid_locator($stationgrid)) {
			header('Content-Type: application/json');
			echo json_encode(array('Error' => 'Error. There is a problem with the gridsquare ('.$stationgrid.') set in your profile!'));
			exit;
		} else {
			// Build the chart buckets in 50-unit steps up to the max chart distance ($dist).
			// Each bucket covers a 50-unit range, e.g. in km mode:
			//   $dataarray[0]   => "0km - 50km"
			//   $dataarray[1]   => "50km - 100km"
			//   ...
			//   till 40050 (longpath)
			$j = 0;
			for ($i = 0; $j < $dist; $i++) {
				$dataarray[$i]['dist'] =  $j . $unit . ' - ' . ($j + 50) . $unit;
				$dataarray[$i]['count'] = 0;
				$dataarray[$i]['calls'] = '';
				$dataarray[$i]['callcount'] = 0;
				$j += 50;
			}

			$qrb = array (					                                            // Used for storing the QSO with the longest QRB
				'Callsign' => '',
				'Grid' => '',
				'Distance' => '',
				'Qsos' => 0,
				'Grids' => ''
			);

			foreach ($qsoArray as $qso) {
				$qrb['Qsos']++;                                                        // Counts up number of qsos
				$bearingdistance = $this->qra->distance($stationgrid, $qso['grid'], $measurement_base, $qso['COL_ANT_PATH']);
				$bearingdistance_km = $this->qra->distance($stationgrid, $qso['grid'], 'K', $qso['COL_ANT_PATH']);
				if ($bearingdistance_km != $qso['COL_DISTANCE']) {
					$data = array('COL_DISTANCE' => $bearingdistance_km);
					$this->db->where('COL_PRIMARY_KEY', $qso['COL_PRIMARY_KEY']);
					$this->db->update($this->config->item('table_name'), $data);
				}
				$arrayplacement = (int)($bearingdistance / 50);                                // Resolution is 50, calculates where to put result in array
				if ($bearingdistance > $qrb['Distance']) {                              // Saves the longest QSO
					$qrb['Distance'] = $bearingdistance;
					$qrb['Callsign'] = $qso['callsign'];
					$qrb['Grid'] = $qso['grid'];
				}
				if (!isset($dataarray[$arrayplacement])) {                              // QSO distance exceeds chart range, skip plotting
					continue;
				}
				$dataarray[$arrayplacement]['count']++;                                               // Used for counting total qsos plotted
				if ($dataarray[$arrayplacement]['callcount'] < 5) {                     // Used for tooltip in graph, set limit to 5 calls shown
					if ($dataarray[$arrayplacement]['callcount'] > 0) {
						$dataarray[$arrayplacement]['calls'] .= ', ';
					}
					$dataarray[$arrayplacement]['calls'] .= $qso['callsign'];
					$dataarray[$arrayplacement]['callcount']++;
				}
			}

			$data['ok'] = 'OK';
			$data['qrb'] = $qrb;
			$data['qsodata'] = $dataarray;
			$data['unit'] = $unit;

			return $data;
		}
	}

    /*
     * Checks the validity of the locator
     * Input: locator
     * Returns: bool
     */
	function valid_locator ($loc) {
		$loc = strtoupper($loc);
		if (strlen($loc) == 4)  $loc .= "LL";	// Only 4 Chars? Fill with center "LL" as only A-R allowed
		if (strlen($loc) == 6)  $loc .= "55";	// Only 6 Chars? Fill with center "55"
		if (strlen($loc) == 8)  $loc .= "LL";	// Only 8 Chars? Fill with center "LL" as only A-R allowed
		if (preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/', $loc)) {
			return true;
		}
		else {
			return false;
		}
	}

    /*
	 * Used to fetch QSOs from the logbook in the awards
	 */
	public function qso_details($distance, $band, $sat, $propagation){
		$distarray = $this->getdistparams($distance);
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$table = $this->config->item('table_name');
		$sql = "
			SELECT dxcc_entities.adif, lotw_users.callsign, COL_BAND, COL_CALL, COL_CLUBLOG_QSO_DOWNLOAD_DATE,
				COL_CLUBLOG_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_UPLOAD_DATE, COL_CLUBLOG_QSO_UPLOAD_STATUS, COL_CONTEST_ID, COL_DISTANCE,
				COL_EQSL_QSL_RCVD, COL_EQSL_QSLRDATE, COL_EQSL_QSLSDATE, COL_EQSL_QSL_SENT, COL_FREQ, COL_GRIDSQUARE, COL_IOTA, COL_LOTW_QSL_RCVD,
				COL_LOTW_QSLRDATE, COL_LOTW_QSLSDATE, COL_LOTW_QSL_SENT, COL_MODE, COL_NAME, COL_OPERATOR, COL_POTA_REF, COL_PRIMARY_KEY,
				COL_QRZCOM_QSO_DOWNLOAD_DATE, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_QRZCOM_QSO_UPLOAD_DATE, COL_QRZCOM_QSO_UPLOAD_STATUS,
				COL_QSL_RCVD, COL_QSL_RCVD_VIA, COL_QSLRDATE, COL_QSLSDATE, COL_QSL_SENT, COL_QSL_SENT_VIA, COL_QSL_VIA, COL_RST_RCVD,
				COL_RST_SENT, COL_SAT_NAME, COL_SOTA_REF, COL_SRX, COL_SRX_STRING, COL_STATE, COL_STX, COL_STX_STRING, COL_SUBMODE, COL_TIME_ON,
				COL_VUCC_GRIDS, COL_WWFF_REF, COL_PROP_MODE, dxcc_entities.end, lotw_users.lastupload, dxcc_entities.name, satellite.displayname AS sat_displayname,
				station_profile.station_callsign, station_profile.station_gridsquare, station_profile.station_profile_name
			FROM $table
			INNER JOIN station_profile ON station_profile.station_id = $table.station_id
			LEFT OUTER JOIN dxcc_entities ON dxcc_entities.adif = $table.COL_DXCC
			LEFT OUTER JOIN lotw_users ON lotw_users.callsign = $table.col_call
			LEFT OUTER JOIN satellite
				ON $table.COL_PROP_MODE = 'SAT'
				AND ($table.COL_SAT_NAME = satellite.name
					OR (satellite.displayname != '' AND $table.COL_SAT_NAME = satellite.displayname))
			WHERE COL_DISTANCE >= ? AND COL_DISTANCE <= ? AND LENGTH(col_gridsquare) > 0
		";

		$params = array($distarray[0], $distarray[1]);

		$placeholders = '';
		foreach ($logbooks_locations_array as $idx => $station_id) {
			$placeholders .= $idx > 0 ? ',?' : '?';
			$params[] = $station_id;
		}
		$sql .= " AND $table.station_id IN ($placeholders)";

		if ($band != 'All') {
			if($band != "sat") {
				$sql .= " AND (COL_PROP_MODE != 'SAT' OR COL_PROP_MODE IS NULL)";
				$sql .= " AND COL_BAND = ?";
				$params[] = $band;
			} else {
				$sql .= " AND COL_PROP_MODE = 'SAT'";
				if ($sat != 'All') {
					$sql .= " AND COL_SAT_NAME = ?";
					$params[] = $sat;
				}
			}
		}

		if ($propagation == 'NoSAT' ) {		// All without SAT
			$sql .= " AND col_prop_mode != 'SAT'";
		} elseif ($propagation == 'None') {	// Empty Propmode
			$sql .= " AND (TRIM(col_prop_mode) = '' OR col_prop_mode IS NULL)";
		} elseif ($propagation == 'All') {		// Dont care for propmode
			; // No Prop-Filter
		} else {				// Propmode set, take care of it
			$sql .= " AND col_prop_mode = ?";
			$params[] = $propagation;
		}

		$sql .= " ORDER BY COL_TIME_ON DESC";

		return $this->db->query($sql, $params);
	}

	function getdistparams($distance) {
		$temp = explode('-', $distance);
		$regex = '[a-zA-Z]+';
		preg_match("%{$regex}%i", $temp[0], $unit);

		$result = [];
		$result[0] = filter_var($temp[0], FILTER_SANITIZE_NUMBER_INT);
		$result[1] = filter_var($temp[1], FILTER_SANITIZE_NUMBER_INT);

		if ($unit[0] == 'mi') {
			$result[0] *= 1.609344;
			$result[1] *= 1.609344;
		}
		if ($unit[0] == 'nmi') {
			$result[0] *= 1.852;
			$result[1] *= 1.852;
		}

		return $result;
	}
}
