<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Map_model extends CI_Model {

    /**
     * Get all DXCC countries that have GeoJSON boundary data,
     * independent of whether they appear in the logbook.
     */
    public function get_available_countries($supported_country_codes) {
		$sql = "select DISTINCT dxcc_entities.name AS dxcc_name, dxcc_entities.prefix, dxcc_entities.adif AS COL_DXCC
		from dxcc_entities
		where dxcc_entities.adif IN (" . implode(',', array_fill(0, count($supported_country_codes), '?')) . ")
		order by prefix ASC";

        $query = $this->db->query($sql, $supported_country_codes);
        return $query->result_array();
    }

    /**
     * Get QSOs for a specific country with 6+ character grids
     */
    public function get_qsos_by_country($country, $station_id = null, $band = null) {
		if (!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		if (!$this->load->is_loaded('DxccFlag')) {
			$this->load->library('DxccFlag');
		}

		$sql = "select COL_PRIMARY_KEY, COL_CALL, COL_GRIDSQUARE, COL_COUNTRY, COL_DXCC, COL_MODE, COL_BAND, COL_PROP_MODE, COL_SAT_NAME, COL_SAT_MODE, COL_TIME_ON, COL_RST_SENT, COL_RST_RCVD, COL_QSL_RCVD, COL_LOTW_QSL_RCVD, COL_EQSL_QSL_RCVD, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS, station_profile.station_profile_name
		from " . $this->config->item('table_name') . "
		join station_profile ON station_profile.station_id = " . $this->config->item('table_name') . ".station_id
		where station_profile.user_id = ?
		and COL_COUNTRY = ?";

		$bindings[] = $this->session->userdata('user_id');
		$bindings[] = $country;

		// Add station filter if specified
		if ($station_id !== null && $station_id !== '') {
			$sql .=	" and station_profile.station_id = ?";
			$bindings[] = $station_id;
		}

		// Add band filter if specified
		if ($band == 'SAT') {
			$sql .= " and COL_PROP_MODE = ?";
			$bindings[] = $band;
		} elseif ($band !== null && $band !== '' && $band !== 'all') {
			$sql .= " and COL_BAND = ?";
			$bindings[] = $band;
		}

		$sql .= "and LENGTH(COL_GRIDSQUARE) >= 6
		order by COL_TIME_ON DESC";

		$query = $this->db->query($sql, $bindings);
        $qsos = $query->result_array();

        // Process QSOs and convert gridsquares to coordinates
        $result = [];
        $custom_date_format = $this->session->userdata('user_date_format') ?: $this->config->item('qso_date_format');
        foreach ($qsos as $qso) {
            $gridsquare = strtoupper(trim($qso['COL_GRIDSQUARE']));

            // Only include QSOs with 6+ character grids
            if (strlen($gridsquare) >= 6) {
                $coords = $this->qra->qra2latlong($gridsquare);

                if ($coords !== false && is_array($coords) && count($coords) >= 2) {
                    $result[] = [
                        'id' => $qso['COL_PRIMARY_KEY'],
                        'call' => $qso['COL_CALL'],
                        'gridsquare' => $gridsquare,
                        'country' => $qso['COL_COUNTRY'],
                        'dxcc' => $qso['COL_DXCC'],
                        'mode' => $qso['COL_MODE'],
                        'band' => $qso['COL_BAND'],
                        'time_on' => $qso['COL_TIME_ON'],
                        'time_formatted' => date($custom_date_format . ' H:i', strtotime($qso['COL_TIME_ON'])),
                        'prop_mode' => $qso['COL_PROP_MODE'] ?? '',
                        'sat_name' => $qso['COL_SAT_NAME'] ?? '',
                        'sat_mode' => $qso['COL_SAT_MODE'] ?? '',
                        'rst_sent' => $qso['COL_RST_SENT'],
                        'rst_rcvd' => $qso['COL_RST_RCVD'],
                        'confirmed' => ($qso['COL_QSL_RCVD'] ?? '') === 'Y'
                            || ($qso['COL_LOTW_QSL_RCVD'] ?? '') === 'Y'
                            || ($qso['COL_EQSL_QSL_RCVD'] ?? '') === 'Y'
                            || ($qso['COL_QRZCOM_QSO_DOWNLOAD_STATUS'] ?? '') === 'Y'
                            || ($qso['COL_CLUBLOG_QSO_DOWNLOAD_STATUS'] ?? '') === 'Y',
                        'lat' => $coords[0],
                        'lng' => $coords[1],
						'profile' => $qso['station_profile_name'],
                        'popup' => $this->createContentMessageDx($qso)
                    ];
                }
            }
        }

        return $result;
    }

	/**
	 * Generate HTML content for QSO popup display
	 */
	public function createContentMessageDx($qso) {
		$table = '<table><tbody>';

		// Callsign with flag
		$table .= '<tr>';
		$table .= '<td colspan="2"><div class="big-flag">';

		if (!empty($qso['COL_DXCC'])) {
			$dxccFlag = $this->dxccflag->get($qso['COL_DXCC']);
			$table .= '<div class="flag">' . htmlspecialchars($dxccFlag) . '</div>';
		}

		// Replace zeros with Ø in callsign
		$callsign = str_replace('0', 'Ø', $qso['COL_CALL']);
		$table .= '<a id="edit_qso" href="javascript:displayQso(' . $qso['COL_PRIMARY_KEY'] . ')">' . htmlspecialchars($callsign) . '</a></div>';
		$table .= '</td>';
		$table .= '</tr>';

		// Date/Time (user's custom date format, falling back to the configured QSO date format)
		$table .= '<tr>';
		$table .= '<td>Date/Time</td>';
		$custom_date_format = $this->session->userdata('user_date_format') ?: $this->config->item('qso_date_format');
		$datetime = date($custom_date_format . ' H:i', strtotime($qso['COL_TIME_ON']));
		$table .= '<td>' . htmlspecialchars($datetime) . '</td>';
		$table .= '</tr>';

		// Band/Satellite
		$table .= '<tr>';
		if (($qso['COL_PROP_MODE'] ?? '') === 'SAT') {
			$table .= '<td>Band</td>';
			$table .= '<td>SAT ' . htmlspecialchars($qso['COL_SAT_NAME']);
			if (!empty($qso['COL_SAT_MODE'])) {
				$table .= ' (' . htmlspecialchars($qso['COL_SAT_MODE']) . ')';
			}
			$table .= '</td>';
		} else {
			$table .= '<td>Band</td>';
			$table .= '<td>' . htmlspecialchars($qso['COL_BAND']) . '</td>';
		}
		$table .= '</tr>';

		// Mode
		$table .= '<tr>';
		$table .= '<td>Mode</td>';
		$table .= '<td>' . htmlspecialchars($qso['COL_MODE']) . '</td>';
		$table .= '</tr>';

		// Gridsquare
		if (!empty($qso['COL_GRIDSQUARE'])) {
			$table .= '<tr>';
			$table .= '<td>Gridsquare</td>';
			$table .= '<td>' . htmlspecialchars($qso['COL_GRIDSQUARE']) . '</td>';
			$table .= '</tr>';
		}

		// Distance (if available)
		if (isset($qso['distance'])) {
			$table .= '<tr>';
			$table .= '<td>Distance</td>';
			$table .= '<td>' . htmlspecialchars($qso['distance']) . '</td>';
			$table .= '</tr>';
		}

		// Bearing (if available)
		if (isset($qso['bearing'])) {
			$table .= '<tr>';
			$table .= '<td>Bearing</td>';
			$table .= '<td>' . htmlspecialchars($qso['bearing']) . '</td>';
			$table .= '</tr>';
		}

		// Station Profile
		if (!empty($qso['station_profile_name'])) {
			$table .= '<tr>';
			$table .= '<td>Station</td>';
			$table .= '<td>' . htmlspecialchars($qso['station_profile_name']) . '</td>';
			$table .= '</tr>';
		}

		$table .= '</tbody></table>';

		return $table;
	}
}
