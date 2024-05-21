<?php
use Wavelog\QSLManager\QSO;

class Logbookadvanced_model extends CI_Model {

	public function searchDb($searchCriteria) {
		$conditions = [];
		$binding = [$searchCriteria['user_id']];

		if ((isset($searchCriteria['dupes'])) && ($searchCriteria['dupes'] !== '')) {
			$id_sql="select GROUP_CONCAT(col_primary_key separator ',') as qsoids, COL_CALL, COL_MODE, COL_SUBMODE, station_callsign, COL_SAT_NAME, COL_BAND,  min(col_time_on) Mintime, max(col_time_on) Maxtime from " . $this->config->item('table_name') . "
				 join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id where station_profile.user_id=?
				group by col_call, col_mode, COL_SUBMODE, STATION_CALLSIGN, col_band, COL_SAT_NAME having count(*) > 1 and timediff(maxtime, mintime) < 1500";
			$id_query = $this->db->query($id_sql, $searchCriteria['user_id']);
			$ids2fetch = '';
			foreach ($id_query->result() as $id) {
				$ids2fetch .= ','.$id->qsoids;
			}
			$ids2fetch = ltrim($ids2fetch, ',');
			if ($ids2fetch ?? '' !== '') {
				$conditions[] = "qsos.COL_PRIMARY_KEY in (".$ids2fetch.")";
			} else {
				$conditions[] = "1=0";
			}
		}

        if ($searchCriteria['dateFrom'] !== '') {
            $from = $searchCriteria['dateFrom'];
			$conditions[] = "date(COL_TIME_ON) >= ?";
			$binding[] = $from;
		}
        if ($searchCriteria['dateTo'] !== '') {
            $to = $searchCriteria['dateTo'];
			$conditions[] = "date(COL_TIME_ON) <= ?";
			$binding[] = $to;
		}
		if ($searchCriteria['de'] !== 'All') {
			$conditions[] = "qsos.station_id = ?";
			$binding[] = trim($searchCriteria['de']);
		}
		if ($searchCriteria['dx'] !== '') {
			$conditions[] = "COL_CALL LIKE ?";
			$binding[] = '%' . trim($searchCriteria['dx']) . '%';
		}
		if ($searchCriteria['mode'] !== '') {
			$conditions[] = "(COL_MODE = ? or COL_SUBMODE = ?)";
			$binding[] = $searchCriteria['mode'];
			$binding[] = $searchCriteria['mode'];
		}
		if ($searchCriteria['band'] !== '') {
			if($searchCriteria['band'] != "SAT") {
				$conditions[] = "COL_BAND = ? and COL_PROP_MODE != 'SAT'";
				$binding[] = trim($searchCriteria['band']);
			} else {
				$conditions[] = "COL_PROP_MODE = 'SAT'";
				if ($searchCriteria['sats'] !== 'All') {
					$conditions[] = "COL_SAT_NAME = ?";
					$binding[] = trim($searchCriteria['sats']);
				}
			}
		}
		if ($searchCriteria['orbits'] !== 'All' && $searchCriteria['orbits'] !== '') {
			$conditions[] = "orbit = ?";
			$binding[] = $searchCriteria['orbits'];
		}
		if ($searchCriteria['qslSent'] !== '') {
			$condition = "COL_QSL_SENT = ?";
			if ($searchCriteria['qslSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_QSL_SENT IS NULL OR COL_QSL_SENT = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qslSent'];
		}
		if ($searchCriteria['qslReceived'] !== '') {
			$condition = "COL_QSL_RCVD = ?";
			if ($searchCriteria['qslReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_QSL_RCVD IS NULL OR COL_QSL_RCVD = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qslReceived'];
		}

		if ($searchCriteria['qslSentMethod'] !== '') {
			$condition = "COL_QSL_SENT_VIA = ?";
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qslSentMethod'];
		}

		if ($searchCriteria['qslReceivedMethod'] !== '') {
			$condition = "COL_QSL_RCVD_VIA = ?";
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qslReceivedMethod'];
		}

		if ($searchCriteria['lotwSent'] !== '') {
			$condition = "COL_LOTW_QSL_SENT = ?";
			if ($searchCriteria['lotwSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_LOTW_QSL_SENT IS NULL OR COL_LOTW_QSL_SENT = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['lotwSent'];
		}
		if ($searchCriteria['lotwReceived'] !== '') {
			$condition = "COL_LOTW_QSL_RCVD = ?";
			if ($searchCriteria['lotwReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_LOTW_QSL_RCVD IS NULL OR COL_LOTW_QSL_RCVD = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['lotwReceived'];
		}

		if ($searchCriteria['eqslSent'] !== '') {
			$condition = "COL_EQSL_QSL_SENT = ?";
			if ($searchCriteria['eqslSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_EQSL_QSL_SENT IS NULL OR COL_EQSL_QSL_SENT = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['eqslSent'];
		}
		if ($searchCriteria['eqslReceived'] !== '') {
			$condition = "COL_EQSL_QSL_RCVD = ?";
			if ($searchCriteria['eqslReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_EQSL_QSL_RCVD IS NULL OR COL_EQSL_QSL_RCVD = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['eqslReceived'];
		}

        if ($searchCriteria['iota'] !== '') {
			$conditions[] = "COL_IOTA = ?";
			$binding[] = $searchCriteria['iota'];
		}

        if ($searchCriteria['dxcc'] !== '') {
			$conditions[] = "COL_DXCC = ?";
			$binding[] = $searchCriteria['dxcc'];
		}

        if ($searchCriteria['state'] !== '') {
			$conditions[] = "COL_STATE = ?";
			$binding[] = $searchCriteria['state'];
		}

		if ($searchCriteria['cqzone'] !== '') {
			$conditions[] = "COL_CQZ = ?";
			$binding[] = $searchCriteria['cqzone'];
		}

		if ($searchCriteria['ituzone'] !== '') {
			$conditions[] = "COL_ITUZ = ?";
			$binding[] = $searchCriteria['ituzone'];
		}

		if ($searchCriteria['qslvia'] !== '') {
			$conditions[] = "COL_QSL_VIA like ?";
			$binding[] = $searchCriteria['qslvia'].'%';
		}

		if ($searchCriteria['sota'] !== '') {
			$conditions[] = "COL_SOTA_REF like ?";
			$binding[] = $searchCriteria['sota'].'%';
		}

		if ($searchCriteria['pota'] !== '') {
			$conditions[] = "COL_POTA_REF like ?";
			$binding[] = $searchCriteria['pota'].'%';
		}

		if ($searchCriteria['wwff'] !== '') {
			$conditions[] = "COL_WWFF_REF like ?";
			$binding[] = $searchCriteria['wwff'].'%';
		}

		if ($searchCriteria['operator'] !== '') {
			$conditions[] = "COL_OPERATOR like ?";
			$binding[] = $searchCriteria['operator'].'%';
		}

        if ($searchCriteria['gridsquare'] !== '') {
                $conditions[] = "(COL_GRIDSQUARE like ? or COL_VUCC_GRIDS like ?)";
                $binding[] = '%' . $searchCriteria['gridsquare'] . '%';
                $binding[] = '%' . $searchCriteria['gridsquare'] . '%';
        }

        if ($searchCriteria['propmode'] !== '') {
                $conditions[] = "COL_PROP_MODE = ?";
                $binding[] = $searchCriteria['propmode'];
                if($searchCriteria['propmode'] == "SAT") {
                        if ($searchCriteria['sats'] !== 'All') {
                                $conditions[] = "COL_SAT_NAME = ?";
                                $binding[] = trim($searchCriteria['sats']);
                        }
                }
        }

		if (($searchCriteria['ids'] ?? '') !== '') {
			$conditions[] = "qsos.COL_PRIMARY_KEY in (".implode(",",$searchCriteria['ids']).")";
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$limit = '';

		if ($searchCriteria['qsoresults'] != 'All') {
			$limit = 'limit ' . $searchCriteria['qsoresults'];
		}

		$where2 = '';

		if ($searchCriteria['qslimages'] !== '') {
			if ($searchCriteria['qslimages'] == 'Y') {
				$where2 .= ' and exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY)';
			}
			if ($searchCriteria['qslimages'] == 'N') {
				$where2 .= ' and not exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY)';
			}
		}

		$sql = "
			SELECT *, dxcc_entities.name as dxccname, mydxcc.name AS station_country, exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY) as qslcount, contest.name as contestname
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id=station_profile.station_id
			LEFT OUTER JOIN satellite ON qsos.COL_SAT_NAME = satellite.name
			LEFT OUTER JOIN dxcc_entities ON qsos.col_dxcc = dxcc_entities.adif
			left outer join dxcc_entities mydxcc on qsos.col_my_dxcc = mydxcc.adif
			LEFT OUTER JOIN lotw_users ON qsos.col_call = lotw_users.callsign
			LEFT OUTER JOIN contest ON qsos.col_contest_id = contest.adifname
			WHERE station_profile.user_id =  ?
			$where
			$where2
			ORDER BY qsos.COL_TIME_ON desc, qsos.COL_PRIMARY_KEY desc
			$limit
		";
		return $this->db->query($sql, $binding);

	}

	public function getSearchResult($searchCriteria) {
		return $this->searchDb($searchCriteria);
	}

	public function getSearchResultArray($searchCriteria) {
		$result = $this->searchDb($searchCriteria);
		return $result->result('array');
	}

  /*
   * @param array $searchCriteria
   * @return array
   */
  public function searchQsos($searchCriteria) : array {
		$results = $this->getSearchResultArray($searchCriteria);

        $qsos = [];
        foreach ($results as $data) {
            $qsos[] = new QSO($data);
        }

		return $qsos;
	}

    public function getQsosForAdif($ids, $user_id, $sortorder = null) : object {
		$binding = [$user_id];
        $conditions[] = "COL_PRIMARY_KEY in ?";
        $binding[] = json_decode($ids, true);

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$order = $this->getSortorder($sortorder);

        $sql = "
            SELECT qsos.*, d2.*, lotw_users.*, station_profile.*, x.qslcount, dxcc_entities.name AS station_country
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id = station_profile.station_id
			LEFT OUTER JOIN dxcc_entities ON qsos.COL_MY_DXCC = dxcc_entities.adif
			LEFT OUTER JOIN dxcc_entities d2 ON qsos.COL_DXCC = d2.adif
			LEFT OUTER JOIN lotw_users ON qsos.col_call=lotw_users.callsign
			LEFT OUTER JOIN (
				select count(*) as qslcount, qsoid
				from qsl_images
				group by qsoid
			) x on qsos.COL_PRIMARY_KEY = x.qsoid
			WHERE station_profile.user_id =  ?
			$where
			$order
		";

		return $this->db->query($sql, $binding);
    }

	public function getSortOrder($sortorder) {
		if ($sortorder == null) {
			return 'ORDER BY qsos.COL_TIME_ON desc';
		} else {
			$sortorder = explode(',', $sortorder);

			if ($this->session->userdata('user_lotw_name') != "" && $this->session->userdata('user_eqsl_name') != ""){
				switch($sortorder[0]) {
					case 1: return 'ORDER BY qsos.COL_TIME_ON ' . $sortorder[1];
					case 2: return 'ORDER BY station_profile.station_callsign ' . $sortorder[1];
					case 3: return 'ORDER BY qsos.COL_CALL ' . $sortorder[1];
					case 4: return 'ORDER BY qsos.COL_MODE' .  $sortorder[1] . ', qsos.COL_SUBMODE ' . $sortorder[1];
					case 7: return 'ORDER BY qsos.COL_BAND ' . $sortorder[1] . ', qsos.COL_SAT_NAME ' . $sortorder[1];
					case 16: return 'ORDER BY qsos.COL_COUNTRY ' . $sortorder[1];
					case 17: return 'ORDER BY qso.COL_STATE ' . $sortorder[1];
					case 18: return 'ORDER BY qsos.COL_CQZ ' . $sortorder[1];
					case 19: return 'ORDER BY qsos.COL_IOTA ' . $sortorder[1];
					default: return 'ORDER BY qsos.COL_TIME_ON desc';
				}
			}

			else if (($this->session->userdata('user_eqsl_name') != "" && $this->session->userdata('user_lotw_name') == "") || ($this->session->userdata('user_eqsl_name') == "" && $this->session->userdata('user_lotw_name') != "")) {
				switch($sortorder[0]) {
					case 1: return 'ORDER BY qsos.COL_TIME_ON ' . $sortorder[1];
					case 2: return 'ORDER BY station_profile.station_callsign ' . $sortorder[1];
					case 3: return 'ORDER BY qsos.COL_CALL ' . $sortorder[1];
					case 4: return 'ORDER BY qsos.COL_MODE' .  $sortorder[1] . ', qsos.COL_SUBMODE ' . $sortorder[1];
					case 7: return 'ORDER BY qsos.COL_BAND ' . $sortorder[1] . ', qsos.COL_SAT_NAME ' . $sortorder[1];
					case 15: return 'ORDER BY qsos.COL_COUNTRY ' . $sortorder[1];
					case 16: return 'ORDER BY qso.COL_STATE ' . $sortorder[1];
					case 17: return 'ORDER BY qsos.COL_CQZ ' . $sortorder[1];
					case 18: return 'ORDER BY qsos.COL_IOTA ' . $sortorder[1];
					default: return 'ORDER BY qsos.COL_TIME_ON desc';
				}
			}

			else if ($this->session->userdata('user_eqsl_name') == "" && $this->session->userdata('user_lotw_name') == ""){
				switch($sortorder[0]) {
					case 1: return 'ORDER BY qsos.COL_TIME_ON ' . $sortorder[1];
					case 2: return 'ORDER BY station_profile.station_callsign ' . $sortorder[1];
					case 3: return 'ORDER BY qsos.COL_CALL ' . $sortorder[1];
					case 4: return 'ORDER BY qsos.COL_MODE' .  $sortorder[1] . ', qsos.COL_SUBMODE ' . $sortorder[1];
					case 7: return 'ORDER BY qsos.COL_BAND ' . $sortorder[1] . ', qsos.COL_SAT_NAME ' . $sortorder[1];
					case 14: return 'ORDER BY qsos.COL_COUNTRY ' . $sortorder[1];
					case 15: return 'ORDER BY qso.COL_STATE ' . $sortorder[1];
					case 16: return 'ORDER BY qsos.COL_CQZ ' . $sortorder[1];
					case 17: return 'ORDER BY qsos.COL_IOTA ' . $sortorder[1];
					default: return 'ORDER BY qsos.COL_TIME_ON desc';
				}
			}
		}
	}

	public function updateQsl($ids, $user_id, $method, $sent) {
		$this->load->model('user_model');

		if(!$this->user_model->authorize(2)) {
			return array('message' => 'Error');
		} else {
			if ($method != '') {
				$data = array(
					'COL_QSLSDATE' => date('Y-m-d H:i:s'),
					'COL_QSL_SENT' => $sent,
					'COL_QSL_SENT_VIA' => $method
				);
			} else {
				$data = array(
					'COL_QSLSDATE' => date('Y-m-d H:i:s'),
					'COL_QSL_SENT' => $sent,
				);
			}
			$this->db->where_in('COL_PRIMARY_KEY', json_decode($ids, true));
			$this->db->update($this->config->item('table_name'), $data);

			return array('message' => 'OK');
		}
	}

	public function updateQslReceived($ids, $user_id, $method, $sent) {
        $this->load->model('user_model');

        if(!$this->user_model->authorize(2)) {
            return array('message' => 'Error');
        } else {
            $data = array(
                'COL_QSLRDATE' => date('Y-m-d H:i:s'),
                'COL_QSL_RCVD' => $sent,
                'COL_QSL_RCVD_VIA' => $method
            );
            $this->db->where_in('COL_PRIMARY_KEY', json_decode($ids, true));
            $this->db->update($this->config->item('table_name'), $data);

            return array('message' => 'OK');
        }
    }

	public function updateQsoWithCallbookInfo($qsoID, $qso, $callbook) {
		$updatedData = array();
		if (!empty($callbook['name']) && empty($qso['COL_NAME'])) {
			$updatedData['COL_NAME'] = $callbook['name'];
		}
		if (!empty($callbook['gridsquare']) && empty($qso['COL_GRIDSQUARE']) && empty($qso['COL_VUCC_GRIDS'] )) {
			if (strpos(trim($callbook['gridsquare']), ',') === false) {
				$updatedData['COL_GRIDSQUARE'] = strtoupper(trim($callbook['gridsquare']));
			} else {
				$updatedData['COL_VUCC_GRIDS'] = strtoupper(trim($callbook['gridsquare']));
			}
		}
		if (!empty($callbook['city']) && empty($qso['COL_QTH'])) {
			$updatedData['COL_QTH'] = $callbook['city'];
		}
		if (!empty($callbook['lat']) && empty($qso['COL_LAT'])) {
			$updatedData['COL_LAT'] = $callbook['lat'];
		}
		if (!empty($callbook['long']) && empty($qso['COL_LON'])) {
			$updatedData['COL_LON'] = $callbook['long'];
		}
		if (!empty($callbook['iota']) && empty($qso['COL_IOTA'])) {
			$updatedData['COL_IOTA'] = $callbook['iota'];
		}
		if (!empty($callbook['state']) && empty($qso['COL_STATE'])) {
			$updatedData['COL_STATE'] = $callbook['state'];
		}
		if (!empty($callbook['us_county']) && empty($qso['COL_CNTY'])) {
			$updatedData['COL_CNTY'] = $callbook['state'].','.$callbook['us_county'];
		}
		if (!empty($callbook['qslmgr']) && empty($qso['COL_QSL_VIA'])) {
			$updatedData['COL_QSL_VIA'] = $callbook['qslmgr'];
		}

		if (count($updatedData) > 0) {
			$this->db->where('COL_PRIMARY_KEY', $qsoID);
			$this->db->update($this->config->item('table_name'), $updatedData);
			return true;
		}

		return false;
    }

	function get_modes() {
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->order_by('col_mode, col_submode', 'ASC');

		$query = $this->db->get($this->config->item('table_name'));

		foreach($query->result() as $mode){
			if ($mode->col_submode == null || $mode->col_submode == "") {
				array_push($modes, $mode->col_mode);
			} else {
				// Make sure we don't add LSB or USB as submodes in the array list
				if ($mode->col_mode != "SSB") {
					array_push($modes, $mode->col_submode);
				}
			}
		}

		return $modes;
	}

	function getQslsForQsoIds($ids) {
		$CI =& get_instance();
        $CI->load->model('logbooks_model');
        $logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        $this->db->select('*');
		$this->db->from($this->config->item('table_name'));
        $this->db->join('qsl_images', 'qsl_images.qsoid = ' . $this->config->item('table_name') . '.col_primary_key');
        $this->db->where_in('qsoid', $ids);
		$this->db->where_in('station_id', $logbooks_locations_array);
        $this->db->order_by("id", "desc");

        return $this->db->get()->result();
    }

	function saveEditedQsos($ids, $column, $value, $value2) {
		switch($column) {
			case "cqz": $column = 'COL_CQZ'; break;
			case "ituz": $column = 'COL_ITUZ'; break;
			case "dxcc": $column = 'COL_DXCC'; break;
			case "iota": $column = 'COL_IOTA'; break;
			case "state": $column = 'COL_STATE'; break;
			case "propagation": $column = 'COL_PROP_MODE'; break;
			case "station": $column = 'station_id'; break;
			case "operator": $column = 'COL_OPERATOR'; break;
			case "comment": $column = 'COL_COMMENT'; break;
			case "band": $column = 'COL_BAND'; break;
			case "mode": $column = 'COL_MODE'; break;
			case "date": $column = 'COL_TIME_ON'; break;
			case "pota": $column = 'COL_POTA_REF'; break;
			case "sota": $column = 'COL_SOTA_REF'; break;
			case "wwff": $column = 'COL_WWFF_REF'; break;
			case "gridsquare": $column = 'COL_GRIDSQUARE'; break;
			case "qslvia": $column = 'COL_QSL_VIA'; break;
			case "satellite": $column = 'COL_SAT_NAME'; break;
			default: return;
		}

		$this->db->trans_start();

		if ($column == 'station_id') {

			$this->load->model('stations');
			// Need to copy over from station profile to my_columns
			$station_profile = $this->stations->profile_clean($value);
			$stationid = $value;
			$stationCallsign = $station_profile->station_callsign;
			$iotaRef = $station_profile->station_iota ?? '';
			$sotaRef = $station_profile->station_sota ?? '';
			$wwffRef = $station_profile->station_wwff ?? '';
			$potaRef = $station_profile->station_pota ?? '';
			$sig     = $station_profile->station_sig ?? '';
			$sigInfo = $station_profile->station_sig_info ?? '';

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".STATION_ID = ?" .
			", " . $this->config->item('table_name').".COL_MY_IOTA = ?" .
			", " . $this->config->item('table_name').".COL_MY_SOTA_REF = ?" .
			", " . $this->config->item('table_name').".COL_MY_WWFF_REF = ?" .
			", " . $this->config->item('table_name').".COL_MY_POTA_REF = ?" .
			", " . $this->config->item('table_name').".COL_MY_SIG = ?" .
			", " . $this->config->item('table_name').".COL_MY_SIG_INFO = ?" .
			", " . $this->config->item('table_name').".COL_STATION_CALLSIGN = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($stationid, $iotaRef, $sotaRef, $wwffRef, $potaRef, $sig, $sigInfo, $stationCallsign, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_BAND') {

			$bandrx = $value2 == '' ? '' : $value2;
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_BAND = ?" .
			", " . $this->config->item('table_name').".COL_BAND_RX = ?" .
			", " . $this->config->item('table_name').".COL_FREQ = ?" .
			", " . $this->config->item('table_name').".COL_FREQ_RX = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$this->load->library('frequency');
			$frequencyBand = $this->frequency->defaultFrequencies[$value]['CW'];
			$frequencyBandRx = $bandrx == '' ? null : $this->frequency->defaultFrequencies[$bandrx]['CW'];

			$query = $this->db->query($sql, array($value, $value2, $frequencyBand, $frequencyBandRx, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_GRIDSQUARE') {
			$this->load->library('Qra');
			$latlng=$this->qra->qra2latlong(trim(xss_clean($value) ?? ''));
			if ($latlng[1] ?? '--' != '--') {
				if (strpos(trim(xss_clean($value) ?? ''), ',') !== false) {
					$grid_value = null;
					$vucc_value = strtoupper(preg_replace('/\s+/', '', xss_clean($value) ?? ''));
				} else {
					$vucc_value = null;
					$grid_value = strtoupper(trim(xss_clean($value) ?? ''));
				}

				$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
					" SET " . $this->config->item('table_name').".COL_GRIDSQUARE = ?" .
", " . $this->config->item('table_name').".COL_VUCC_GRIDS = ?" .
					" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

				$query = $this->db->query($sql, array($grid_value, $vucc_value, json_decode($ids, true), $this->session->userdata('user_id')));
			}
		} else if ($column == 'COL_MODE') {

			$this->load->model('logbook_model');
			$mode = $this->logbook_model->get_main_mode_if_submode($value);
			if ($mode == null) {
				$col_mode = $value;
				$col_submode = null;
			} else {
				$col_mode = $mode;
				$col_submode = $value;
			}

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_MODE = ?" .
			", " . $this->config->item('table_name').".COL_SUBMODE = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($col_mode, $col_submode, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_QSL_VIA') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QSL_VIA = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_TIME_ON') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".col_time_on = TIMESTAMP(CONCAT(DATE_FORMAT(?, '%Y-%m-%d'), ' ', cast(col_time_on as time)))" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_SAT_NAME') {

			$propmode = $value == '' ? '' : 'SAT';
			$satmode = $value2 ?? '';

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_SAT_NAME = ?" .
			", " . $this->config->item('table_name').".COL_PROP_MODE = ?" .
			", " . $this->config->item('table_name').".COL_SAT_MODE = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, $propmode, $satmode, json_decode($ids, true), $this->session->userdata('user_id')));
		} else {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ".$this->config->item('table_name').".station_id = station_profile.station_id SET " . $this->config->item('table_name').".".$column . " = ? WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		}

		$this->db->trans_complete();

		return array('message' => 'OK');
    }

	function deleteQsos($ids) {
		$this->db->trans_start();

		$sql = "delete from " . $this->config->item('table_name') . " WHERE col_primary_key in ? and station_id in (select station_id from station_profile where user_id = ?)";

		$query = $this->db->query($sql, array(json_decode($ids, true), $this->session->userdata('user_id')));
		$this->db->trans_complete();
    }

	function getPrimarySubdivisonsDxccs() {
		$sql = "select distinct primary_subdivisions.adif, dxcc_entities.name, dxcc_entities.prefix
		from primary_subdivisions
		join dxcc_entities on primary_subdivisions.adif = dxcc_entities.adif
		order by prefix";

		$query = $this->db->query($sql);
		return $query->result();
    }

	function getSubdivisons($dxccid) {
		$sql = "select * from primary_subdivisions where adif = ? order by subdivision";

		$query = $this->db->query($sql, array($dxccid));
		return $query->result();
    }
}
