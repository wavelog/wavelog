<?php
use Wavelog\QSLManager\QSO;
use Wavelog\Dxcc\Dxcc;

class Logbookadvanced_model extends CI_Model {

	public function dupeSearchQuery($searchCriteria, $binding) {
		$conditions = [];
		$group_by_append = '';
		$order_by = '';

		$order_by .= ' order by col_call';
		$id_sql = "select GROUP_CONCAT(col_primary_key separator ',') as qsoids, COL_CALL, station_callsign, min(col_time_on) Mintime, max(col_time_on) Maxtime";

		if (isset($searchCriteria['dupemode']) && $searchCriteria['dupemode'] === 'Y') {
			$id_sql .= ", COL_MODE, COL_SUBMODE";
			$group_by_append .= ", COL_MODE, COL_SUBMODE";
		}
		if (isset($searchCriteria['dupeband']) && $searchCriteria['dupeband'] === 'Y') {
			$id_sql .= ", COL_BAND";
			$group_by_append .= ", COL_BAND";
		}
		if (isset($searchCriteria['dupesat']) && $searchCriteria['dupesat'] === 'Y') {
			$id_sql .= ", COL_SAT_NAME";
			$group_by_append .= ", COL_SAT_NAME";
			$conditions[] = "COL_PROP_MODE = 'SAT' and COL_SAT_NAME <> '' and COL_SAT_NAME is not null";
		}

		$id_sql .= " from " . $this->config->item('table_name') . "
			join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id where station_profile.user_id = ?";

		$id_sql .= "group by COL_CALL, station_callsign";
		$id_sql .= $group_by_append;
		$id_sql .= " having count(*) > 1";
		if (isset($searchCriteria['dupedate']) && $searchCriteria['dupedate'] === 'Y') {
			$id_sql .= " AND TIMESTAMPDIFF(SECOND, Mintime, Maxtime) < 1800";
			$order_by .= ' , col_time_on desc';
		}

		$id_query = $this->db->query($id_sql, array($this->session->userdata('user_id')));
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

		if (($searchCriteria['ids'] ?? '') !== '') {
			// Sanitize IDs to prevent SQL injection
			if (is_array($searchCriteria['ids'])) {
				$sanitized_ids = array_map('intval', $searchCriteria['ids']);
				$sanitized_ids = array_filter($sanitized_ids, function($id) {
					return $id > 0;
				});
				if (!empty($sanitized_ids)) {
					$conditions[] = "qsos.COL_PRIMARY_KEY in (".implode(",",$sanitized_ids).")";
				}
			}
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$limit = '';

		if ($searchCriteria['qsoresults'] != 'All') {
			// Sanitize and enforce max limit to prevent DoS
			$max_results = 10000;
			$limit_value = max(1, min($max_results, intval($searchCriteria['qsoresults'])));
			$limit = ' limit ' . $limit_value;
		}

		$sql = "
		SELECT qsos.*, qsos.last_modified AS qso_last_modified, dxcc_entities.*, lotw_users.*, station_profile.*, satellite.*, dxcc_entities.name as dxccname, mydxcc.name AS station_country, exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY) as qslcount, coalesce(contest.name, qsos.col_contest_id) as contestname
		FROM " . $this->config->item('table_name') . " qsos
		INNER JOIN station_profile ON qsos.station_id=station_profile.station_id
		LEFT OUTER JOIN satellite ON qsos.col_prop_mode='SAT' and qsos.COL_SAT_NAME = COALESCE(NULLIF(satellite.name, ''), NULLIF(satellite.displayname, ''))
		LEFT OUTER JOIN dxcc_entities ON qsos.col_dxcc = dxcc_entities.adif
		left outer join dxcc_entities mydxcc on qsos.col_my_dxcc = mydxcc.adif
		LEFT OUTER JOIN lotw_users ON qsos.col_call = lotw_users.callsign
		LEFT OUTER JOIN contest ON qsos.col_contest_id = contest.adifname
		WHERE station_profile.user_id =  ?
		$where
		$order_by
		$limit
		";
		return $this->db->query($sql, $binding);
	}

	public function searchDb($searchCriteria) {
		$conditions = [];
		$binding = [$searchCriteria['user_id']];

		if (isset($searchCriteria['qsoids']) && ($searchCriteria['qsoids'] !== '')) {
			// Sanitize qsoids to prevent SQL injection
			$qsoids = $searchCriteria['qsoids'];
			if (is_array($qsoids)) {
				$sanitized_ids = array_map('intval', $qsoids);
			} else {
				// Handle comma-separated string
				$ids_array = explode(',', $qsoids);
				$sanitized_ids = array_map('intval', $ids_array);
			}
			$sanitized_ids = array_filter($sanitized_ids, function($id) {
				return $id > 0;
			});
			if (!empty($sanitized_ids)) {
				$ids2fetch = implode(',', $sanitized_ids);
				$conditions[] = "qsos.COL_PRIMARY_KEY in (".$ids2fetch.")";
			}
		}

		if ((isset($searchCriteria['dupes'])) && ($searchCriteria['dupes'] !== '')) {
			return $this->dupeSearchQuery($searchCriteria, $binding);
		}

		if ((isset($searchCriteria['invalid'])) && ($searchCriteria['invalid'] !== '')) {
			$id_sql="
				select GROUP_CONCAT(col_primary_key separator ',') as qsoids from (
					select col_primary_key from " . $this->config->item('table_name') . "
					join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
					where station_profile.user_id = ?
					and (coalesce(col_mode, '') = '' or col_mode = '0')

					union all

					select col_primary_key from " . $this->config->item('table_name') . "
					join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
					where station_profile.user_id = ?
					and coalesce(col_band, '') = ''

					union all

					select col_primary_key from " . $this->config->item('table_name') . "
					join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
					where station_profile.user_id = ?
					and coalesce(col_call, '') = ''

					union all

					select col_primary_key from " . $this->config->item('table_name') . "
					join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
					where station_profile.user_id = ?
					and (col_time_on is null or cast(col_time_on as date) = '1970-01-01')

					union all

					select col_primary_key from " . $this->config->item('table_name') . "
					join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
					where station_profile.user_id = ?
					and coalesce(col_cont, '') <> ''
					and col_cont NOT IN ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA')
				) as x";

			$id_query = $this->db->query($id_sql, [$searchCriteria['user_id'], $searchCriteria['user_id'], $searchCriteria['user_id'], $searchCriteria['user_id'], $searchCriteria['user_id']]);

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
		if ($searchCriteria['de'] !== 'All' && $searchCriteria['qsoids'] === '') {
			if ($searchCriteria['de'] == '') {
				$stationids = 'null';
			} else {
				// Sanitize station IDs to prevent SQL injection
				$de_array = is_array($searchCriteria['de']) ? $searchCriteria['de'] : [$searchCriteria['de']];
				$sanitized_ids = array_map('intval', $de_array);
				$sanitized_ids = array_filter($sanitized_ids, function($id) {
					return $id > 0;
				});
				if (!empty($sanitized_ids)) {
					$stationids = implode(',', $sanitized_ids);
				} else {
					$stationids = 'null';
				}
			}
			$conditions[] = "qsos.station_id in (".$stationids.")";
		}
		if ($searchCriteria['dx'] !== '*' && $searchCriteria['dx'] !== '') {
			if (strtolower($searchCriteria['dx']) == '!empty') {
				$conditions[] = "COL_CALL <> ''";
			} else {
				$conditions[] = "COL_CALL like ?";
				$binding[] = '%' . trim($searchCriteria['dx']) . '%';
			}
		}
		if ($searchCriteria['dx'] == '') {
			$conditions[] = "coalesce(COL_CALL, '') = ''";
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

		if ($searchCriteria['clublogSent'] !== '') {
			$condition = "COL_CLUBLOG_QSO_UPLOAD_STATUS = ?";
			if ($searchCriteria['clublogSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_CLUBLOG_QSO_UPLOAD_STATUS IS NULL OR COL_CLUBLOG_QSO_UPLOAD_STATUS = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['clublogSent'];
		}
		if ($searchCriteria['clublogReceived'] !== '') {
			$condition = "COL_CLUBLOG_QSO_DOWNLOAD_STATUS = ?";
			if ($searchCriteria['clublogReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_CLUBLOG_QSO_DOWNLOAD_STATUS IS NULL OR COL_CLUBLOG_QSO_DOWNLOAD_STATUS = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['clublogReceived'];
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
		if ($searchCriteria['dclSent'] !== '') {
			$condition = "COL_DCL_QSL_SENT = ?";
			if ($searchCriteria['dclSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_DCL_QSL_SENT IS NULL OR COL_DCL_QSL_SENT = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['dclSent'];
		}
		if ($searchCriteria['dclReceived'] !== '') {
			$condition = "COL_DCL_QSL_RCVD = ?";
			if ($searchCriteria['dclReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_DCL_QSL_RCVD IS NULL OR COL_DCL_QSL_RCVD = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['dclReceived'];
		}

		if ($searchCriteria['qrzSent'] !== '') {
			$condition = "COL_QRZCOM_QSO_UPLOAD_STATUS = ?";
			if ($searchCriteria['qrzSent'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_QRZCOM_QSO_UPLOAD_STATUS IS NULL OR COL_QRZCOM_QSO_UPLOAD_STATUS = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qrzSent'];
		}
		if ($searchCriteria['qrzReceived'] !== '') {
			$condition = "COL_QRZCOM_QSO_DOWNLOAD_STATUS = ?";
			if ($searchCriteria['qrzReceived'] == 'N') {
				$condition = '('.$condition;
				$condition .= " OR COL_QRZCOM_QSO_DOWNLOAD_STATUS IS NULL OR COL_QRZCOM_QSO_DOWNLOAD_STATUS = '')";
			}
			$conditions[] = $condition;
			$binding[] = $searchCriteria['qrzReceived'];
		}

        if ($searchCriteria['iota'] !== '') {
			$conditions[] = "COL_IOTA = ?";
			$binding[] = $searchCriteria['iota'];
		}

        if (($searchCriteria['dxcc'] ?? '') !== '') {
			$conditions[] = "COL_DXCC = ?";
			$binding[] = $searchCriteria['dxcc'];
		}

        if ($searchCriteria['state'] !== '*' && $searchCriteria['state'] !== '') {
			if (strtolower($searchCriteria['state']) == '!empty') {
				$conditions[] = "COL_STATE <> ''";
			} else {
				$conditions[] = "COL_STATE like ?";
				$binding[] = $searchCriteria['state'];
			}
		}
		if ($searchCriteria['state'] == '') {
			$conditions[] = "coalesce(COL_STATE, '') = ''";
		}

		if ($searchCriteria['dok'] !== '*' && $searchCriteria['dok'] !== '') {
			if (strtolower($searchCriteria['dok']) == '!empty') {
				$conditions[] = "COL_DARC_DOK <> ''";
			} else {
				$conditions[] = "COL_DARC_DOK like ?";
				$binding[] = $searchCriteria['dok'];
			}
		}
		if ($searchCriteria['dok'] == '') {
			$conditions[] = "coalesce(COL_DARC_DOK, '') = ''";
		}

		if ($searchCriteria['county'] !== '*' && $searchCriteria['county'] !== '') {
			if (strtolower($searchCriteria['county']) == '!empty') {
				$conditions[] = "COL_CNTY <> ''";
			} else {
				$conditions[] = "COL_CNTY like ?";
				$binding[] = '%' . $searchCriteria['county'] . '%';
			}
		}
		if ($searchCriteria['county'] == '') {
			$conditions[] = "coalesce(COL_CNTY, '') = ''";
		}

		if ($searchCriteria['cqzone'] !== 'All') {
			if ($searchCriteria['cqzone'] == '') {
				$conditions[] = "(COL_CQZ = '' or COL_CQZ is null)";
			} else {
				$conditions[] = "COL_CQZ = ?";
				$binding[] = $searchCriteria['cqzone'];
			}
		}

		if ($searchCriteria['ituzone'] !== 'All') {
			if ($searchCriteria['ituzone'] == '') {
				$conditions[] = "(COL_ITUZ = '' or COL_ITUZ is null)";
			} else {
				$conditions[] = "COL_ITUZ = ?";
				$binding[] = $searchCriteria['ituzone'];
			}
		}

		if ($searchCriteria['qslvia'] !== '*' && $searchCriteria['qslvia'] !== '') {
			if (strtolower($searchCriteria['qslvia']) == '!empty') {
				$conditions[] = "COL_QSL_VIA <> ''";
			} else {
				$conditions[] = "COL_QSL_VIA like ?";
				$binding[] = $searchCriteria['qslvia'].'%';
			}
		}

		if ($searchCriteria['qslvia'] == '') {
			$conditions[] = "coalesce(COL_QSL_VIA, '') = ''";
		}

		if ($searchCriteria['sota'] !== '*' && $searchCriteria['sota'] !== '') {
			if (strtolower($searchCriteria['sota']) == '!empty') {
				$conditions[] = "COL_SOTA_REF <> ''";
			} else {
				$conditions[] = "COL_SOTA_REF like ?";
				$binding[] = $searchCriteria['sota'].'%';
			}
		}
		if ($searchCriteria['sota'] == '') {
			$conditions[] = "coalesce(COL_SOTA_REF, '') = ''";
		}


		if ($searchCriteria['comment'] !== '*' && $searchCriteria['comment'] !== '') {
			if (strtolower($searchCriteria['comment']) == '!empty') {
				$conditions[] = "COL_COMMENT <> ''";
			} else {
				$conditions[] = "COL_COMMENT like ?";
				$binding[] = '%' . $searchCriteria['comment'].'%';
			}
		}
		if ($searchCriteria['comment'] == '') {
			$conditions[] = "coalesce(COL_COMMENT, '') = ''";
		}

		if ($searchCriteria['pota'] !== '*' && $searchCriteria['pota'] !== '') {
			if (strtolower($searchCriteria['pota']) == '!empty') {
				$conditions[] = "COL_POTA_REF <> ''";
			} else {
				$conditions[] = "COL_POTA_REF like ?";
				$binding[] = $searchCriteria['pota'].'%';
			}
		}
		if ($searchCriteria['pota'] == '') {
			$conditions[] = "coalesce(COL_POTA_REF, '') = ''";
		}

		if ($searchCriteria['wwff'] !== '*' && $searchCriteria['wwff'] !== '') {
			if (strtolower($searchCriteria['wwff']) == '!empty') {
				$conditions[] = "COL_WWFF_REF <> ''";
			} else {
				$conditions[] = "COL_WWFF_REF like ?";
				$binding[] = $searchCriteria['wwff'].'%';
			}
		}
		if ($searchCriteria['wwff'] == '') {
			$conditions[] = "coalesce(COL_WWFF_REF, '') = ''";
		}

		if ($searchCriteria['operator'] !== '*' && $searchCriteria['operator'] !== '') {
			if (strtolower($searchCriteria['operator']) == '!empty') {
				$conditions[] = "COL_OPERATOR <> ''";
			} else {
				$conditions[] = "COL_OPERATOR like ?";
				$binding[] = $searchCriteria['operator'].'%';
			}
		}
		if ($searchCriteria['operator'] == '') {
			$conditions[] = "coalesce(COL_OPERATOR, '') = ''";
		}

        if ($searchCriteria['gridsquare'] !== '*' && $searchCriteria['gridsquare'] !== '') {
			if (strtolower($searchCriteria['gridsquare']) == '!empty') {
				$conditions[] = "(COL_GRIDSQUARE <> '' or COL_VUCC_GRIDS <> '')";
			} else {
				$conditions[] = "(COL_GRIDSQUARE like ? or COL_VUCC_GRIDS like ?)";
				$binding[] = '%' . $searchCriteria['gridsquare'] . '%';
				$binding[] = '%' . $searchCriteria['gridsquare'] . '%';
			}
        }

		if ($searchCriteria['gridsquare'] == '') {
			$conditions[] = "(coalesce(COL_GRIDSQUARE, '') = '' and coalesce(COL_VUCC_GRIDS, '') = '')";
		}


		if ($searchCriteria['distance'] !== '*' && $searchCriteria['distance'] !== '') {
			if (strtolower($searchCriteria['distance']) == '!empty') {
				$conditions[] = "COL_DISTANCE <> ''";
			} else {
				$conditions[] = "COL_DISTANCE >= ?";
				$binding[] = $searchCriteria['distance'];
			}
        }

		if ($searchCriteria['distance'] == '') {
			$conditions[] = "coalesce(COL_DISTANCE, '') = ''";
		}

		if (($searchCriteria['propmode'] ?? '') == 'None') {
			$conditions[] = "(trim(COL_PROP_MODE) = '' OR COL_PROP_MODE is null)";
		} elseif ($searchCriteria['propmode'] !== '') {
			$conditions[] = "COL_PROP_MODE = ?";
			$binding[] = $searchCriteria['propmode'];
			if($searchCriteria['propmode'] == "SAT") {
				if ($searchCriteria['sats'] !== 'All') {
					$conditions[] = "COL_SAT_NAME = ?";
					$binding[] = trim($searchCriteria['sats']);
				}
			}
		}

		if ($searchCriteria['contest'] !== '*' && $searchCriteria['contest'] !== '') {
			if (strtolower($searchCriteria['contest']) == '!empty') {
				$conditions[] = "(COL_CONTEST_ID <> '' OR contest.name <> '')";
			} else {
				$conditions[] = "(COL_CONTEST_ID <> '' OR contest.name <> '')";$conditions[] = "(COL_CONTEST_ID like ? OR contest.name like ?)";
				$binding[] = '%'.$searchCriteria['contest'].'%';
				$binding[] = '%'.$searchCriteria['contest'].'%';
			}
		}

		if ($searchCriteria['contest'] == '') {
			$conditions[] = "coalesce(COL_CONTEST_ID, '') = ''";
		}

		if ($searchCriteria['continent'] !== '') {
			if ($searchCriteria['continent'] == 'invalid') {
				$conditions[] = "COL_CONT NOT IN ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA')";
				$conditions[] = "coalesce(COL_CONT, '') <> ''";
			} else if ($searchCriteria['continent'] == 'blank') {
				$conditions[] = "coalesce(COL_CONT, '') = ''";
			}
			else {
				$conditions[] = "COL_CONT = ?";
				$binding[] = $searchCriteria['continent'];
			}
		}

		if (($searchCriteria['ids'] ?? '') !== '') {
			// Sanitize IDs to prevent SQL injection
			if (is_array($searchCriteria['ids'])) {
				$sanitized_ids = array_map('intval', $searchCriteria['ids']);
				$sanitized_ids = array_filter($sanitized_ids, function($id) {
					return $id > 0;
				});
				if (!empty($sanitized_ids)) {
					$conditions[] = "qsos.COL_PRIMARY_KEY in (".implode(",",$sanitized_ids).")";
				}
			}
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$limit = '';

		if ($searchCriteria['qsoresults'] != 'All') {
			// Sanitize and enforce max limit to prevent DoS
			$max_results = 10000;
			$limit_value = max(1, min($max_results, intval($searchCriteria['qsoresults'])));
			$limit = ' limit ' . $limit_value;
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

		$sortorder = '';

		$sortColumn = '';
		$sortDirection = isset($searchCriteria['sortdirection']) && strtolower($searchCriteria['sortdirection']) === 'asc' ? 'asc' : 'desc';

		if ($searchCriteria['sortcolumn'] !== '') {
			switch($searchCriteria['sortcolumn']) {
				case 'qsotime':
					$sortColumn = 'qsos.COL_TIME_ON';
					break;
				case 'band':
					$sortColumn = 'qsos.COL_BAND';
					break;
				case 'mode':
					$sortColumn = 'qsos.COL_MODE';
					break;
				case 'qsomodified':
					$sortColumn = 'qsos.last_modified';
					break;
				default:
					$sortColumn = 'qsos.COL_TIME_ON';
			}

			$secondarySort = $sortDirection === 'asc' ? 'asc' : 'desc';
			$sortorder .= " ORDER BY $sortColumn $sortDirection";

			// Add secondary sorts for mode column
			if ($searchCriteria['sortdirection'] === 'mode') {
				$sortorder .= ", qsos.COL_SUBMODE $sortDirection";
			}

			$sortorder .= ", qsos.COL_PRIMARY_KEY $secondarySort";
		}

		$sql = "
			SELECT qsos.*, qsos.last_modified AS qso_last_modified, dxcc_entities.*, lotw_users.*, station_profile.*, satellite.*, dxcc_entities.name as dxccname, mydxcc.name AS station_country, exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY) as qslcount, coalesce(contest.name, qsos.col_contest_id) as contestname
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id=station_profile.station_id
			LEFT OUTER JOIN satellite ON qsos.col_prop_mode='SAT' and qsos.COL_SAT_NAME = COALESCE(NULLIF(satellite.name, ''), NULLIF(satellite.displayname, ''))
			LEFT OUTER JOIN dxcc_entities ON qsos.col_dxcc = dxcc_entities.adif
			left outer join dxcc_entities mydxcc on qsos.col_my_dxcc = mydxcc.adif
			LEFT OUTER JOIN lotw_users ON qsos.col_call = lotw_users.callsign
			LEFT OUTER JOIN contest ON qsos.col_contest_id = contest.adifname
			WHERE station_profile.user_id =  ?
			$where
			$where2
			$sortorder
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

    public function getQsosForAdif($ids, $user_id, $sortColumnVar = 'qsotime', $sortDirection = 'desc') : object {
		$binding = [$user_id];
        $conditions[] = "COL_PRIMARY_KEY in ?";
        $binding[] = json_decode($ids, true);

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$sortorder = '';

		$sortColumnVar = '';
		$sortDirection = $sortDirection === 'asc' ? 'asc' : 'desc';

		if ($sortColumnVar !== '') {
			switch($sortColumnVar) {
				case 'qsotime':
					$sortColumn = 'qsos.COL_TIME_ON';
					break;
				case 'band':
					$sortColumn = 'qsos.COL_BAND';
					break;
				case 'mode':
					$sortColumn = 'qsos.COL_MODE';
					break;
				case 'qsomodified':
					$sortColumn = 'qsos.last_modified';
					break;
				default:
					$sortColumn = 'qsos.COL_TIME_ON';
			}

			$secondarySort = $sortDirection === 'asc' ? 'asc' : 'desc';
			$sortorder .= " ORDER BY $sortColumn $sortDirection";

			// Add secondary sorts for mode column
			if ($sortDirection === 'mode') {
				$sortorder .= ", qsos.COL_SUBMODE $sortDirection";
			}

			$sortorder .= ", qsos.COL_PRIMARY_KEY $secondarySort";
		}

		$sql = "
			SELECT qsos.*, qsos.last_modified AS qso_last_modified, lotw_users.*, station_profile.*, dxcc_entities.name AS station_country, d2.adif as adif, d2.name as dxccname, exists(select 1 from qsl_images where qsoid = qsos.COL_PRIMARY_KEY) as qslcount, coalesce(contest.name, qsos.col_contest_id) as contestname
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id = station_profile.station_id
			LEFT OUTER JOIN dxcc_entities ON qsos.COL_MY_DXCC = dxcc_entities.adif
			LEFT OUTER JOIN dxcc_entities d2 ON qsos.COL_DXCC = d2.adif
			LEFT OUTER JOIN lotw_users ON qsos.col_call=lotw_users.callsign
			LEFT OUTER JOIN contest ON qsos.col_contest_id = contest.adifname
			WHERE station_profile.user_id =  ?
			$where
			$sortorder
		";

		return $this->db->query($sql, $binding);
    }

	public function updateQsl($ids, $user_id, $method, $sent) {
		$this->load->model('user_model');

		if(!$this->user_model->authorize(2)) {
			return array('message' => 'Error');
		} else {
			// Sanitize IDs to prevent SQL injection
			$ids_array = json_decode($ids, true);
			if (is_array($ids_array)) {
				$sanitized_ids = array_map('intval', $ids_array);
				$sanitized_ids = array_filter($sanitized_ids, function($id) {
					return $id > 0;
				});
			} else {
				$sanitized_ids = [];
			}

			if (empty($sanitized_ids)) {
				return array('message' => 'Error');
			}

			$sql = "UPDATE " . $this->config->item('table_name') ."
				SET
				COL_QSLSDATE = CURRENT_TIMESTAMP,
				COL_QSL_SENT = ?,
				COL_QSL_SENT_VIA = ?,
				COL_QRZCOM_QSO_UPLOAD_STATUS = CASE
				WHEN COL_QRZCOM_QSO_UPLOAD_STATUS IN ('Y', 'I') THEN 'M'
				ELSE COL_QRZCOM_QSO_UPLOAD_STATUS
				END
				WHERE COL_PRIMARY_KEY IN (".implode(',', $sanitized_ids).")";
			$binding[] = $sent;
			$binding[] = $method;
			$this->db->query($sql, $binding);

			return array('message' => 'OK');
		}
	}

	public function updateQslReceived($ids, $user_id, $method, $sent) {
		$this->load->model('user_model');

		if(!$this->user_model->authorize(2)) {
			return array('message' => 'Error');
		} else {
			// Sanitize IDs to prevent SQL injection
			$ids_array = json_decode($ids, true);
			if (is_array($ids_array)) {
				$sanitized_ids = array_map('intval', $ids_array);
				$sanitized_ids = array_filter($sanitized_ids, function($id) {
					return $id > 0;
				});
			} else {
				$sanitized_ids = [];
			}

			if (empty($sanitized_ids)) {
				return array('message' => 'Error');
			}

			$sql = "UPDATE " . $this->config->item('table_name') ."
				SET
				COL_QSLRDATE = CURRENT_TIMESTAMP,
				COL_QSL_RCVD = ?,
				COL_QSL_RCVD_VIA = ?,
				COL_QRZCOM_QSO_UPLOAD_STATUS = CASE
				WHEN COL_QRZCOM_QSO_UPLOAD_STATUS IN ('Y', 'I') THEN 'M'
				ELSE COL_QRZCOM_QSO_UPLOAD_STATUS
				END
				WHERE COL_PRIMARY_KEY IN (".implode(',', $sanitized_ids).")";
			$binding[] = $sent;
			$binding[] = $method;
			$this->db->query($sql, $binding);
			return array('message' => 'OK');
		}
	}

	public function updateQsoWithCallbookInfo($qsoID, $qso, $callbook, $gridsquareAccuracyCheck, $station_gridsquare = null) {
		$updatedData = array();
		$updated = false;
		if (!empty($callbook['name']) && empty($qso['COL_NAME'])) {
			$updatedData['COL_NAME'] = $callbook['name'];
			$updated = true;
		}
		if (!empty($callbook['gridsquare']) && $callbook['geoloc'] != 'grid') {
			if (empty($qso['COL_GRIDSQUARE']) && empty($qso['COL_VUCC_GRIDS'] )) {
				if (strpos(trim($callbook['gridsquare']), ',') === false) {
					$updatedData['COL_GRIDSQUARE'] = strtoupper(trim($callbook['gridsquare']));
					if ($station_gridsquare != null && $station_gridsquare != '') {
						if (!$this->load->is_loaded('Qra')) {
							$this->load->library('Qra');
						}
						$updatedData['COL_DISTANCE'] = $this->qra->distance($station_gridsquare, strtoupper(trim($callbook['gridsquare'])), 'K');
					}
				} else {
					$updatedData['COL_VUCC_GRIDS'] = strtoupper(trim($callbook['gridsquare']));
					if ($station_gridsquare != null && $station_gridsquare != '') {
						if (!$this->load->is_loaded('Qra')) {
							$this->load->library('Qra');
						}
						$updatedData['COL_DISTANCE'] = $this->qra->distance($station_gridsquare, strtoupper(trim($callbook['gridsquare'])), 'K');
					}
				}
				$updated = true;
			} else if (!empty($qso['COL_GRIDSQUARE']) && $gridsquareAccuracyCheck == true) {
				$existingGridsquare = $qso['COL_GRIDSQUARE'];
				//Check if existing gridsquare is less accurate than callbook gridsquare
				if (strlen(trim($callbook['gridsquare'])) > strlen(trim($existingGridsquare))) {
					if ($existingGridsquare == substr($callbook['gridsquare'], 0, strlen($existingGridsquare))) {
						//Callbook gridsquare is more accurate, update it
						$updatedData['COL_GRIDSQUARE'] = strtoupper(trim($callbook['gridsquare']));
						if ($station_gridsquare != null && $station_gridsquare != '') {
							if (!$this->load->is_loaded('Qra')) {
								$this->load->library('Qra');
							}
							$updatedData['COL_DISTANCE'] = $this->qra->distance($station_gridsquare, strtoupper(trim($callbook['gridsquare'])), 'K');
						}
						$updated = true;
					}
				}
			}
		}
		if (!empty($callbook['city']) && empty($qso['COL_QTH'])) {
			$updatedData['COL_QTH'] = $callbook['city'];
			$updated = true;
		}
		if (!empty($callbook['lat']) && empty($qso['COL_LAT'])) {
			$updatedData['COL_LAT'] = substr(($callbook['lat'] ?? ''),0,11);
			$updated = true;
		}
		if (!empty($callbook['long']) && empty($qso['COL_LON'])) {
			$updatedData['COL_LON'] = substr(($callbook['long'] ?? ''),0,11);
			$updated = true;
		}
		if (!empty($callbook['iota']) && empty($qso['COL_IOTA'])) {
			$updatedData['COL_IOTA'] = $callbook['iota'];
			$updated = true;
		}
		if (!empty($callbook['state']) && empty($qso['COL_STATE'])) {
			$updatedData['COL_STATE'] = $callbook['state'];
			$updated = true;
		}
		if (!empty($callbook['us_county']) && empty($qso['COL_CNTY'])) {
			$updatedData['COL_CNTY'] = $callbook['state'].','.$callbook['us_county'];
			$updated = true;
		}
		if (!empty($callbook['county']) && empty($qso['COL_CNTY'])) {
			$updatedData['COL_CNTY'] = $callbook['county'];
			$updated = true;
		}
		if (!empty($callbook['qslmgr']) && empty($qso['COL_QSL_VIA'])) {
			$updatedData['COL_QSL_VIA'] = $callbook['qslmgr'];
			$updated = true;
		}
		if (!empty($callbook['ituz']) && empty($qso['COL_ITUZ'])) {
			$updatedData['COL_ITUZ'] = $callbook['ituz'];
			$updated = true;
		}
		if (!empty($callbook['cqz']) && empty($qso['COL_CQZ'])) {
			$updatedData['COL_CQZ'] = $callbook['cqz'];
			$updated = true;
		}
		if (empty($qso['COL_CONT'])) {
			$updatedData['COL_CONT'] = $this->logbook_model->getContinent($callbook['dxcc']);
			$updated = true;
		}
		if (!empty($callbook['email']) && empty($qso['COL_EMAIL'])) {
			$updatedData['COL_EMAIL'] = $callbook['email'];
			$updated = true;
		}

		//Also set QRZ.com status to modified
		if($updated == true && $qso['COL_QRZCOM_QSO_UPLOAD_STATUS'] == 'Y') {
			$updatedData['COL_QRZCOM_QSO_UPLOAD_STATUS'] = 'M';
		}

		if (count($updatedData) > 0) {
			$this->db->where('COL_PRIMARY_KEY', $qsoID);
			$this->db->update($this->config->item('table_name'), $updatedData);
			return true;
		}

		return false;
    }

	function get_modes() {

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->order_by('col_mode, col_submode', 'ASC');

		$query = $this->db->get($this->config->item('table_name'));

		foreach($query->result() as $mode){
			if ($mode->col_submode == null || $mode->col_submode == "") {
				array_push($modes, $mode->col_mode);
			} else {
				array_push($modes, $mode->col_submode);
			}
		}

		return $modes;
	}

	function get_worked_bands() {
		// get all worked slots from database
		$sql = "SELECT distinct LOWER(`COL_BAND`) as `COL_BAND` FROM `".$this->config->item('table_name')."` thcv
			JOIN station_profile on thcv.station_id = station_profile.station_id WHERE station_profile.user_id = ? AND COL_PROP_MODE != \"SAT\" ORDER BY col_band";

		$data = $this->db->query($sql, array($this->session->userdata('user_id')));

		$worked_slots = array();
		foreach($data->result() as $row){
			array_push($worked_slots, $row->COL_BAND);
		}

		$sql = "SELECT distinct LOWER(`COL_PROP_MODE`) as `COL_PROP_MODE` FROM `".$this->config->item('table_name')."` thcv
			JOIN station_profile on thcv.station_id = station_profile.station_id WHERE station_profile.user_id = ? AND COL_PROP_MODE = \"SAT\"";

		$SAT_data = $this->db->query($sql, array($this->session->userdata('user_id')));

		foreach($SAT_data->result() as $row){
			array_push($worked_slots, strtoupper($row->COL_PROP_MODE));
		}

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

		return $worked_slots;
	}

	function get_worked_sats() {
		// get all worked sats from database
		$sql = "SELECT distinct col_sat_name FROM ".$this->config->item('table_name')." thcv
		JOIN station_profile on thcv.station_id = station_profile.station_id WHERE station_profile.user_id = ? and coalesce(col_sat_name, '') <> '' ORDER BY col_sat_name";

		$data = $this->db->query($sql, array($this->session->userdata('user_id')));

		$worked_sats = array();
		foreach($data->result() as $row){
			array_push($worked_sats, $row->col_sat_name);
		}

		return $worked_sats;
	}

	function getQslsForQsoIds($ids) {
        $this->db->select('*');
		$this->db->from($this->config->item('table_name'));
        $this->db->join('qsl_images', 'qsl_images.qsoid = ' . $this->config->item('table_name') . '.col_primary_key');
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
        $this->db->where_in('qsoid', $ids);
        $this->db->order_by("id", "desc");

        return $this->db->get()->result();
    }

	function saveEditedQsos($ids, $column, $value, $value2, $value3, $value4) {
		$skipqrzupdate = false;
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
			case "contest": $column = 'COL_CONTEST_ID'; break;
			case "lotwsent": $column = 'COL_LOTW_QSL_SENT'; break;
			case "lotwreceived": $column = 'COL_LOTW_QSL_RCVD'; break;
			case "qslmsg": $column = 'COL_QSLMSG'; break;
			case "continent": $column = 'COL_CONT'; break;
			case "qslsent": $column = 'COL_QSL_SENT'; break;
			case "qslreceived": $column = 'COL_QSL_RCVD'; break;
			case "qrzsent": $column = 'COL_QRZCOM_QSO_UPLOAD_STATUS'; break;
			case "qrzreceived": $column = 'COL_QRZCOM_QSO_DOWNLOAD_STATUS'; break;
			case "eqslsent": $column = 'COL_EQSL_QSL_SENT'; break;
			case "eqslreceived": $column = 'COL_EQSL_QSL_RCVD'; break;
			case "dclsent": $column = 'COL_DCL_QSL_SENT'; break;
			case "dclreceived": $column = 'COL_DCL_QSL_RCVD'; break;
			case "stationpower": $column = 'COL_TX_PWR'; break;
			case "clublogsent": $column = 'COL_CLUBLOG_QSO_UPLOAD_STATUS'; break;
			case "clublogreceived": $column = 'COL_CLUBLOG_QSO_DOWNLOAD_STATUS'; break;
			case "region": $column = 'COL_REGION'; break;
			case "distance": $column = 'COL_DISTANCE'; break;
			case "dok": $column = 'COL_DARC_DOK'; break;
			case "stxstring": $column = 'COL_STX_STRING'; break;
			case "rstr": $column = 'COL_RST_RCVD'; break;
			case "rsts": $column = 'COL_RST_SENT'; break;
			case "qslsentmethod": $column = 'COL_QSL_SENT_VIA'; break;
			case "qslreceivedmethod": $column = 'COL_QSL_RCVD_VIA'; break;
			default: return;
		}

		$this->db->trans_start();

		if ($column == 'COL_DARC_DOK') {
			$value=strtoupper($value);
		}
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

			if ($value == '') return;

			$bandrx = $value2 == '' ? '' : $value2;
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_BAND = ?" .
			", " . $this->config->item('table_name').".COL_BAND_RX = ?" .
			", " . $this->config->item('table_name').".COL_FREQ = ?" .
			", " . $this->config->item('table_name').".COL_FREQ_RX = ?" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$frequencyBand = $this->frequency->defaultFrequencies[$value]['CW'];
			$frequencyBandRx = $bandrx == '' ? null : $this->frequency->defaultFrequencies[$bandrx]['CW'];

			$query = $this->db->query($sql, array($value, $value2, $frequencyBand, $frequencyBandRx, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_GRIDSQUARE') {
			if ($value == '') {
				$grid_value = null;
				$vucc_value = null;
			} else {
				if(!$this->load->is_loaded('Qra')) {
					 $this->load->library('Qra');
				 }
				$latlng=$this->qra->qra2latlong(trim(xss_clean($value) ?? ''));
				if ($latlng[1] ?? '--' != '--') {
					if (strpos(trim(xss_clean($value) ?? ''), ',') !== false) {
						$grid_value = null;
						$vucc_value = strtoupper(preg_replace('/\s+/', '', xss_clean($value) ?? ''));
					} else {
						$vucc_value = null;
						$grid_value = strtoupper(trim(xss_clean($value) ?? ''));
					}

				}
			}
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
				" SET " . $this->config->item('table_name').".COL_GRIDSQUARE = ?" .
				", " . $this->config->item('table_name').".COL_VUCC_GRIDS = ?" .
				" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";
			$query = $this->db->query($sql, array($grid_value, $vucc_value, json_decode($ids, true), $this->session->userdata('user_id')));
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
			$bindings=[];

			$propmode = $value == '' ? '' : 'SAT';
			$satmode = $value2 ?? '';
			$bandtx = $value3 == '' ? '' : $value3;
			$bandrx = $value4 == '' ? '' : $value4;

			$bindings[] = $value;
			$bindings[] = $propmode;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_SAT_NAME = ?" .
			", " . $this->config->item('table_name').".COL_PROP_MODE = ?";

			if ($satmode != '') {
				$sql .= ", " . $this->config->item('table_name').".COL_SAT_MODE = ?";
				$bindings[] = $satmode;
			}

			if ($bandtx != '') {
				$sql .= ", " . $this->config->item('table_name').".COL_BAND = ?";
				$bindings[] = $bandtx;
				$frequencyBand = $this->frequency->defaultFrequencies[$bandtx]['CW'];
				$sql .= ", " . $this->config->item('table_name').".COL_FREQ = ?";
				$bindings[] = $frequencyBand;
			}

			if ($bandrx != '') {
				$sql .= ", " . $this->config->item('table_name').".COL_BAND_RX = ?";
				$bindings[] = $bandrx;
				$frequencyBandRx = $bandrx == '' ? null : $this->frequency->defaultFrequencies[$bandrx]['CW'];
				$sql .= ", " . $this->config->item('table_name').".COL_FREQ_RX = ?";
				$bindings[] = $frequencyBandRx;
			}

			$sql .= " WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$bindings[] = json_decode($ids, true);
			$bindings[] = $this->session->userdata('user_id');

			$query = $this->db->query($sql, $bindings);
		} else if ($column == 'COL_LOTW_QSL_SENT') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_LOTW_QSL_SENT = ?, " . $this->config->item('table_name').".COL_LOTW_QSLSDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_LOTW_QSL_RCVD') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_LOTW_QSL_RCVD = ?, " . $this->config->item('table_name').".COL_LOTW_QSLRDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_DCL_QSL_SENT') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_DCL_QSL_SENT = ?, " . $this->config->item('table_name').".COL_DCL_QSLSDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_DCL_QSL_RCVD') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_DCL_QSL_RCVD = ?, " . $this->config->item('table_name').".COL_DCL_QSLRDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_QRZCOM_QSO_UPLOAD_STATUS') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QRZCOM_QSO_UPLOAD_STATUS = ?, " . $this->config->item('table_name').".COL_QRZCOM_QSO_UPLOAD_DATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_QRZCOM_QSO_DOWNLOAD_STATUS') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QRZCOM_QSO_DOWNLOAD_STATUS = ?, " . $this->config->item('table_name').".COL_QRZCOM_QSO_DOWNLOAD_DATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_EQSL_QSL_SENT') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_EQSL_QSL_SENT = ?, " . $this->config->item('table_name').".COL_EQSL_QSLSDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_EQSL_QSL_RCVD') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_EQSL_QSL_RCVD = ?, " . $this->config->item('table_name').".COL_EQSL_QSLRDATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_CLUBLOG_QSO_DOWNLOAD_STATUS') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_CLUBLOG_QSO_DOWNLOAD_STATUS = ?, " . $this->config->item('table_name').".COL_CLUBLOG_QSO_DOWNLOAD_DATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_CLUBLOG_QSO_UPLOAD_STATUS') {
			$skipqrzupdate = true;

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_CLUBLOG_QSO_UPLOAD_STATUS = ?, " . $this->config->item('table_name').".COL_CLUBLOG_QSO_UPLOAD_DATE = now()" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));

		} else if ($column == 'COL_QSLMSG') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QSLMSG = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_CONT') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_CONT = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_TX_PWR') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_TX_PWR = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_REGION') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_REGION = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_QSL_SENT_VIA') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QSL_SENT_VIA = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_QSL_RCVD_VIA') {

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_QSL_RCVD_VIA = ? " .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		} else if ($column == 'COL_DISTANCE' && $value == '') {
			$this->update_distances($ids);
			$skipqrzupdate = true;
		} else {

			if ($value == "null") {
				$value = null;
			}

			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ".$this->config->item('table_name').".station_id = station_profile.station_id SET " . $this->config->item('table_name').".".$column . " = ? WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

			$query = $this->db->query($sql, array($value, json_decode($ids, true), $this->session->userdata('user_id')));
		}

		if (!$skipqrzupdate) {
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN station_profile ON ".$this->config->item('table_name').".station_id = station_profile.station_id SET " . $this->config->item('table_name').".COL_QRZCOM_QSO_UPLOAD_STATUS = 'M' WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ? and col_qrzcom_qso_upload_status = 'Y'";
			$query = $this->db->query($sql, array(json_decode($ids, true), $this->session->userdata('user_id')));
		}

		$this->db->trans_complete();

		return array('message' => 'OK');
    }

	public function update_distances($ids) {
		$idarray = (json_decode($ids, true));
		ini_set('memory_limit', '-1');
		$this->db->trans_start();
		$this->db->select("COL_PRIMARY_KEY, COL_GRIDSQUARE, COL_ANT_PATH, station_gridsquare");
		$this->db->join('station_profile', 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id');

		$this->db->where("COL_GRIDSQUARE is NOT NULL");
		$this->db->where("COL_GRIDSQUARE != ''");
		$this->db->where("COL_GRIDSQUARE != station_gridsquare");
		$this->db->where_in("COL_PRIMARY_KEY", $idarray);
		$query = $this->db->get($this->config->item('table_name'));

		if ($query->num_rows() > 0) {
			if (!$this->load->is_loaded('Qra')) {
				$this->load->library('Qra');
			}
			foreach ($query->result() as $row) {
				$ant_path = $row->COL_ANT_PATH ?? null;
				$distance = $this->qra->distance($row->station_gridsquare, $row->COL_GRIDSQUARE, 'K', $ant_path);
				$data = array(
					'COL_DISTANCE' => $distance,
				);

				$this->db->where(array('COL_PRIMARY_KEY' => $row->COL_PRIMARY_KEY));
				$this->db->update($this->config->item('table_name'), $data);
			}
		}
		$this->db->trans_complete();
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

	function fixCqZones($ids = null) {
		if ($ids == null) {
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN dxcc_entities ON ". $this->config->item('table_name').".col_dxcc = dxcc_entities.adif JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
					" SET " . $this->config->item('table_name').".COL_CQZ = dxcc_entities.cqz" .
					" WHERE station_profile.user_id = ? and " . $this->config->item('table_name') . ".COL_CQZ IS NULL";

			$query = $this->db->query($sql, array($this->session->userdata('user_id')));
			return $this->db->affected_rows();
		}
		$sql = "UPDATE ".$this->config->item('table_name')." JOIN dxcc_entities ON ". $this->config->item('table_name').".col_dxcc = dxcc_entities.adif JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_CQZ = dxcc_entities.cqz" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

		$query = $this->db->query($sql, array(json_decode($ids, true), $this->session->userdata('user_id')));
		return $this->db->affected_rows();
	}


	function fixItuZones($ids = null) {
		if ($ids == null) {
			$sql = "UPDATE ".$this->config->item('table_name')." JOIN dxcc_entities ON ". $this->config->item('table_name').".col_dxcc = dxcc_entities.adif JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
					" SET " . $this->config->item('table_name').".COL_ITUZ = dxcc_entities.ituz" .
					" WHERE station_profile.user_id = ? and " . $this->config->item('table_name') . ".COL_ITUZ IS NULL";
			$query = $this->db->query($sql, array($this->session->userdata('user_id')));
			return $this->db->affected_rows();
		}
		$sql = "UPDATE ".$this->config->item('table_name')." JOIN dxcc_entities ON ". $this->config->item('table_name').".col_dxcc = dxcc_entities.adif JOIN station_profile ON ". $this->config->item('table_name').".station_id = station_profile.station_id" .
			" SET " . $this->config->item('table_name').".COL_ITUZ = dxcc_entities.ituz" .
			" WHERE " . $this->config->item('table_name').".col_primary_key in ? and station_profile.user_id = ?";

		$query = $this->db->query($sql, array(json_decode($ids, true), $this->session->userdata('user_id')));
		return $this->db->affected_rows();
    }

	/**
	 * Fix state for a single QSO using GeoJSON lookup
	 *
	 * @param int $qso_id QSO primary key
	 * @return array Result array with success, dxcc_name, dxcc_number, state_code, skipped
	 */
	function fixStateSingle($qso_id) {
		$this->load->library('Geojson');

		// Get QSO data
		$sql = "SELECT COL_PRIMARY_KEY, COL_CALL, COL_GRIDSQUARE, COL_DXCC, COL_STATE, d.name as dxcc_name
				FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				LEFT JOIN dxcc_entities d ON qsos.COL_DXCC = d.adif
				WHERE qsos.COL_PRIMARY_KEY = ? AND station_profile.user_id = ?";

		$query = $this->db->query($sql, [$qso_id, $this->session->userdata('user_id')]);

		if ($query->num_rows() === 0) {
			return [
				'success' => false,
				'skipped' => true,
				'reason' => 'QSO not found'
			];
		}

		$qso = $query->row();
		$callsign = $qso->COL_CALL ?? 'Unknown';
		$dxcc = (int)$qso->COL_DXCC;
		$gridsquare = $qso->COL_GRIDSQUARE;
		$state = $qso->COL_STATE ?? '';
		$dxcc_name = $qso->dxcc_name ?? 'Unknown';

		// Skip if state is already populated
		if (!empty($state)) {
			return [
				'success' => false,
				'skipped' => true,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'reason' => 'State already set'
			];
		}

		// Check if gridsquare exists
		if (empty($gridsquare)) {
			return [
				'success' => false,
				'skipped' => true,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'reason' => 'No gridsquare'
			];
		}

		// Check if gridsquare is precise enough (at least 6 characters)
		if (strlen($gridsquare) < 6) {
			return [
				'success' => false,
				'skipped' => true,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'reason' => 'Gridsquare not precise enough'
			];
		}

		// Check if state is supported for this DXCC
		if (!$this->geojson->isStateSupported($dxcc)) {
			return [
				'success' => false,
				'skipped' => true,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'reason' => 'DXCC not supported'
			];
		}

		// Find state from gridsquare
		$state = $this->geojson->findStateFromGridsquare($gridsquare, $dxcc);

		if ($state === null || !isset($state['code'])) {
			// Get coordinates for debugging
			$coords = $this->geojson->gridsquareToLatLng($gridsquare);
			return [
				'success' => false,
				'skipped' => false,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'gridsquare' => $gridsquare,
				'lat' => $coords['lat'] ?? null,
				'lng' => $coords['lng'] ?? null,
				'reason' => 'State not found in GeoJSON'
			];
		}

		// Update the state
		$update_sql = "UPDATE " . $this->config->item('table_name') . "
					   SET COL_STATE = ?
					   WHERE COL_PRIMARY_KEY = ?";

		$this->db->query($update_sql, [$state['code'], $qso_id]);

		return [
			'success' => true,
			'skipped' => false,
			'callsign' => $callsign,
			'dxcc_number' => $dxcc,
			'dxcc_name' => $dxcc_name,
			'state_code' => $state['code'],
			'state_name' => $state['name'] ?? null
		];
	}

	public function check_missing_continent() {
		// get all records with no COL_CONT
		$this->db->trans_start();
		$sql = "UPDATE " . $this->config->item('table_name') . "
			JOIN dxcc_entities ON " . $this->config->item('table_name') . ".col_dxcc = dxcc_entities.adif
			JOIN station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
			SET col_cont = dxcc_entities.cont
			WHERE (COALESCE(" . $this->config->item('table_name') . ".col_cont, '') = ''  or " . $this->config->item('table_name') . ".col_cont not in ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'))
			AND station_profile.user_id = ?
			AND col_dxcc != 0";

		$query = $this->db->query($sql, array($this->session->userdata('user_id')));
		$result = $this->db->affected_rows();
		$this->db->trans_complete();

		return $result;
	}

	public function update_distances_batch() {
		ini_set('memory_limit', '-1');

		$sql = "SELECT COL_ANT_PATH, COL_DISTANCE, COL_PRIMARY_KEY, station_profile.station_gridsquare, COL_GRIDSQUARE, COL_VUCC_GRIDS FROM " . $this->config->item('table_name') . "
			JOIN station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
			WHERE COL_GRIDSQUARE is NOT NULL
			AND COL_GRIDSQUARE != ''
			AND station_profile.user_id = ?
			AND (COL_DISTANCE = '' or COL_DISTANCE is NULL)
			and COL_GRIDSQUARE != station_gridsquare";

		$query = $this->db->query($sql, array($this->session->userdata('user_id')));

		$recordcount = $query->num_rows();

		$count = 0;

		if ($recordcount > 0) {
			$this->load->library('Qra');

			$updates = [];
			foreach ($query->result() as $row) {
				$distance = $this->qra->distance(
					$row->station_gridsquare,
					$row->COL_GRIDSQUARE,
					'K',
					$row->COL_ANT_PATH ?? null
				);

				if ($distance != 0) {
					$updates[] = [
						'COL_PRIMARY_KEY' => $row->COL_PRIMARY_KEY,
						'COL_DISTANCE' => $distance,
					];
					$count++;
				}

			}

			if (!empty($updates)) {
				$this->db->update_batch($this->config->item('table_name'), $updates, 'COL_PRIMARY_KEY');
			}
		}

		return $count;
	}

	public function runCheckDb($type) {
		switch ($type) {
			case 'checkdistance':
				return $this->check_missing_distance();
			case 'checkcontinent':
				return $this->check_qsos_missing_continent();
			case 'checkdxcc':
				return $this->check_dxcc();
			case 'checkstate':
				return $this->check_missing_state();
			case 'checkgrids':
				return $this->getMissingGridQsos();
			case 'checkincorrectgridsquares':
				return $this->getIncorrectGridsquares();
			case 'checkincorrectcqzones':
				return $this->getIncorrectCqZones();
			case 'checkincorrectituzones':
				return $this->getIncorrectItuZones();
			case 'checkiota':
				return $this->checkIota();
			default:
				return null;
		}
	}
	/*
	 * Get list of QSOs with gridsquares that do not match the gridsquares listed for the DXCC.
	 * The data comes from the TQSL published Gridsquare list for DXCCs.
	 */
	public function getIncorrectGridsquares() {
		$sqlcheck = "select count(*) as count from vuccgrids";;
		$querycheck = $this->db->query($sqlcheck);
		$rowcheck = $querycheck->row();
		if ($rowcheck->count == 0) {
			return ['status' => 'error', 'message' => __("VuccGrids table is empty. Please import the VUCC grids data first.")];
		}

		$sql = "select col_primary_key, col_sat_name, col_time_on, col_call, col_band, col_gridsquare, col_dxcc, col_country, station_profile_name, col_lotw_qsl_rcvd, col_mode, col_submode,
			(
			select group_concat(distinct gridsquare order by gridsquare separator ', ')
			from vuccgrids
			where adif = thcv.col_dxcc
				order by gridsquare asc
			) as correctgridsquare
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		join dxcc_entities on dxcc_entities.adif = thcv.COL_DXCC
		where station_profile.user_id = ?
		and thcv.col_dxcc > 0
		and not exists (
			select 1
			from vuccgrids
			where adif = thcv.col_dxcc
			and gridsquare = substr(thcv.col_gridsquare, 1, 4)
		)
		and exists (select 1 from vuccgrids where adif = thcv.col_dxcc)
		and thcv.col_dxcc > 0
		and thcv.col_gridsquare is not null
		and thcv.col_gridsquare <> ''
		order by station_profile_name, col_time_on desc";

		$bindings[] = [$this->session->userdata('user_id')];

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_qsos_missing_continent() {
		$sql = "select count(*) as count from " . $this->config->item('table_name') . "
			join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
			where user_id = ?
			and (coalesce(col_cont, '') = '' or col_cont not in ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'))
			and col_dxcc != 0";

		$bindings[] = [$this->session->userdata('user_id')];

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_missing_distance() {
		$sql = "select count(*) as count from " . $this->config->item('table_name') . "
		join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
		where user_id = ?
		AND (COL_DISTANCE = '' or COL_DISTANCE is NULL)
		and COL_GRIDSQUARE != station_gridsquare
		and COL_GRIDSQUARE is NOT NULL
		and COL_GRIDSQUARE != ''";

		$bindings[] = [$this->session->userdata('user_id')];

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_missing_state() {
		$this->load->library('Geojson');
		$supported_dxcc_list = $this->geojson->getSupportedDxccs();
		$supported_dxcc_array = array_keys($supported_dxcc_list);

		$sql = "select count(*) as count, col_dxcc, dxcc_entities.name as dxcc_name, dxcc_entities.prefix from " . $this->config->item('table_name') . "
		join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
		join dxcc_entities on " . $this->config->item('table_name') . ".col_dxcc = dxcc_entities.adif
		where user_id = ? and coalesce(col_state, '') = ''
		and col_dxcc in (" . implode(',', array_map('intval', $supported_dxcc_array)) . ")
		and length(col_gridsquare) >= 6
		group by col_dxcc, dxcc_entities.name, dxcc_entities.prefix
		order by dxcc_entities.prefix";

		$bindings[] = [$this->session->userdata('user_id')];

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/**
	 * Fix state for a batch of QSOs using GeoJSON lookup
	 *
	 * @param int $dxcc DXCC entity number for which to fix states
	 * @return array Result array with success, dxcc_name, dxcc_number, state_code, skipped
	 */
	function fixStateBatch($dxcc) {
		$this->load->library('Geojson', $dxcc);

		// Get QSO data
		$sql = "SELECT COL_PRIMARY_KEY, COL_CALL, COL_GRIDSQUARE, COL_DXCC, COL_STATE, d.name as dxcc_name, station_profile.station_profile_name
				FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				LEFT JOIN dxcc_entities d ON qsos.COL_DXCC = d.adif
				WHERE qsos.COL_DXCC = ? AND station_profile.user_id = ?
				AND (qsos.COL_STATE IS NULL OR qsos.COL_STATE = '')
				AND LENGTH(COALESCE(qsos.COL_GRIDSQUARE, '')) >= 6";

		$query = $this->db->query($sql, [$dxcc, $this->session->userdata('user_id')]);

		if ($query->num_rows() === 0) {
			return [
				'success' => false,
				'skipped' => true,
				'reason' => 'QSOs not found'
			];
		}

		$results = [];
		$batch_updates = [];

		foreach ($query->result() as $qso) {
			$result = $this->fixStateDxcc($qso);

			if ($result['success']) {
				// Prepare data for batch update
				$batch_updates[] = [
					'COL_PRIMARY_KEY' => $qso->COL_PRIMARY_KEY,
					'COL_STATE' => $result['state_code']
				];
			} else {
				$result['station_profile_name'] = $qso->station_profile_name;
				$result['id'] = $qso->COL_PRIMARY_KEY;
				$result['gridsquare'] = $qso->COL_GRIDSQUARE;
				$results[] = $result;
			}
		}

		// Perform batch update if there are any updates
		$count = 0;
		if (!empty($batch_updates)) {
			$this->db->update_batch($this->config->item('table_name'), $batch_updates, 'COL_PRIMARY_KEY');
			$count = count($batch_updates);
		}

		$results['count'] = $count;

		return $results;
	}

	/**
	 * Fix state for a batch of QSOs, based on the DXCC
	 * Note: This now only validates and prepares data
	 *
	 * @param object $qso QSO object
	 * @return array Result array with success, dxcc_name, dxcc_number, state_code, skipped
	 */
	function fixStateDxcc($qso) {
		$callsign = $qso->COL_CALL ?? 'Unknown';
		$dxcc = (int)$qso->COL_DXCC;
		$gridsquare = $qso->COL_GRIDSQUARE;
		$state = $qso->COL_STATE ?? '';
		$dxcc_name = $qso->dxcc_name ?? 'Unknown';

		// Find state from gridsquare
		$state = $this->geojson->findStateFromGridsquare($gridsquare, $dxcc);

		if ($state === null || !isset($state['code'])) {
			return [
				'success' => false,
				'skipped' => false,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'gridsquare' => $gridsquare,
				'reason' => 'State not found in GeoJSON'
			];
		}

		// Return success with state info
		return [
			'success' => true,
			'skipped' => false,
			'callsign' => $callsign,
			'dxcc_number' => $dxcc,
			'dxcc_name' => $dxcc_name,
			'state_code' => $state['code'],
			'state_name' => $state['name'] ?? null
		];
	}

	function getStateListQsos($dxcc) {
		$sql = "SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_state, col_gridsquare, d.name as dxcc_name, station_profile.station_profile_name FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				LEFT JOIN dxcc_entities d ON qsos.COL_DXCC = d.adif
				WHERE qsos.COL_DXCC = ? AND station_profile.user_id = ?
				AND (qsos.COL_STATE IS NULL OR qsos.COL_STATE = '')
				AND LENGTH(COALESCE(qsos.COL_GRIDSQUARE, '')) >= 6
				ORDER BY COL_TIME_ON DESC";

		$query = $this->db->query($sql, [$dxcc, $this->session->userdata('user_id')]);

		return $query->result();
	}

	/*
		Function to run batch fixes on the logbook.
		Used in dbtools section.
	*/
	function batchFix($type) {
		switch ($type) {
			case 'distance':
				return $this->update_distances_batch();
			case 'continent':
				return $this->check_missing_continent();
			case 'cqzones':
				return $this->fixCqZones();
			case 'ituzones':
				return $this->fixItuZones();
			case 'grids':
				return $this->check_missing_grid();
			default:
				return null;
		}
	}

	/*
		Another function moved from update to the advanced logbook, to be used in the dbtools section.
		It did not have filter on user or location.
		This function will check all QSOs with missing grid square and try to fill them using the callbook lookup.
	*/
	public function check_missing_grid() {
		$result = $this->getMissingGridQsos();

		$count = 0;
		$batch_updates = [];

		$this->db->trans_start();

		if (count($result) > 0) {
			if (!$this->load->is_loaded('callbook')) {
				$this->load->library('callbook');
			}

			foreach ($result as $row) {
				$callsign = $row->col_call;
				$callbook = $this->callbook->getCallbookData($callsign);

				if (isset($callbook)) {
					if (isset($callbook['error'])) {
						log_message('error', "Error: " . $callbook['error']);
					} else {
						if (isset($callbook['gridsquare']) && $callbook['gridsquare'] != '') {
							// Prepare data for batch update
							$batch_updates[] = [
								'COL_PRIMARY_KEY' => $row->col_primary_key,
								'COL_GRIDSQUARE' => $callbook['gridsquare']
							];
						}
					}
				}
			}

			// Perform batch update if there are any updates
			if (!empty($batch_updates)) {
				$this->db->update_batch($this->config->item('table_name'), $batch_updates, 'COL_PRIMARY_KEY');
				$count = count($batch_updates);
			}
		}

		$this->db->trans_complete();

		return $count;
	}

	public function getMissingGridQsos() {
		$sql = "SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_state, col_gridsquare, station_profile.station_profile_name FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				WHERE station_profile.user_id = ?
				AND (qsos.COL_GRIDSQUARE IS NULL OR qsos.COL_GRIDSQUARE = '')
				AND (qsos.COL_VUCC_GRIDS IS NULL OR qsos.COL_VUCC_GRIDS = '')
				ORDER BY COL_TIME_ON DESC limit 150";

		$query = $this->db->query($sql, [$this->session->userdata('user_id')]);

		return $query->result();
	}

	/*
		Check all QSOs DXCC against current DXCC database
	*/
	public function check_dxcc() {
		ini_set('memory_limit', '-1');

		$i = 0;
		$result = array();

		$callarray = $this->getQsos();

		// Starting clock time in seconds
		$start_time = microtime(true);
		$dxccobj = new Dxcc(null);

		foreach ($callarray->result() as $call) {

            $i++;
			$dxcc = $dxccobj->dxcc_lookup($call->col_call, $call->date);

            $dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
            $dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';

            if (($call->col_dxcc ?? 'Unset') != $dxcc['adif']) {
                $result[] = array(
                                'callsign'          => $call->col_call,
								'qso_date'          => $call->date,
								'mode'              => isset($call->col_mode) ? $call->col_mode : '',
								'submode'           => isset($call->col_submode) ? $call->col_submode : '',
								'band'              => isset($call->col_band) ? $call->col_band : '',
								'sat_name'          => isset($call->col_sat_name) ? $call->col_sat_name : '',
								'lotw_qsl_rcvd'     => isset($call->col_lotw_qsl_rcvd) ? $call->col_lotw_qsl_rcvd : '',
								'station_profile'   => $call->station_profile_name,
                                'existing_dxcc'     => $call->col_country,
                                'existing_adif'     => $call->col_dxcc,
                                'result_country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
                                'result_adif'       => $dxcc['adif'],
								'id' 			    => $call->col_primary_key,
                            );
            }
        }

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        $data['execution_time'] = $execution_time;
        $data['calls_tested'] = $i;
		$data['result'] = $result;

		return $data;
	}

	function getQsos() {
		$sql = 'select distinct col_country, col_sat_name, col_call, col_dxcc, date(col_time_on) date, col_mode, col_submode, col_band, col_lotw_qsl_rcvd, station_profile.station_profile_name, col_primary_key
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			where station_profile.user_id = ?';
		$params[] = $this->session->userdata('user_id');

		$sql .= ' order by station_profile.station_profile_name asc, date desc';

        $query = $this->db->query($sql, $params);

		return $query;
	}

	function fixDxccSelected($ids) {
		$sql = "select COL_PRIMARY_KEY, COL_CALL, COL_TIME_ON, COL_TIME_OFF, station_profile.station_profile_name from " . $this->config->item('table_name') .
		" join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
		where station_profile.user_id = ? and " . $this->config->item('table_name') . ".col_primary_key in ?";

		$r = $this->db->query($sql, array($this->session->userdata('user_id'), json_decode($ids, true)));

		$count = 0;
		$dxccobj = new Dxcc(null);

		if ($r->num_rows() > 0) { //query dxcc_prefixes
			$sql = "update " . $this->config->item('table_name') . " set COL_COUNTRY = ?, COL_DXCC = ? where COL_PRIMARY_KEY = ?";
			$q = $this->db->conn_id->prepare($sql);
			foreach ($r->result_array() as $row) {
				$qso_date = $row['COL_TIME_OFF'] == '' ? $row['COL_TIME_ON'] : $row['COL_TIME_OFF'];
				$qso_date = date("Y-m-d", strtotime($qso_date));
				$dxcc = $dxccobj->dxcc_lookup($row['COL_CALL'], $qso_date);
				$dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
				$dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';
				if ($dxcc['adif'] != 'Not Found') {
					$q->execute(array(addslashes(ucwords(strtolower($dxcc['entity']), "- (/")), $dxcc['adif'], $row['COL_PRIMARY_KEY']));
					$count++;
				}
			}
		}

		$result['count'] = $count;
		return $result;
	}

	function getIncorrectCqZones() {
		if(!clubaccess_check(9)) return;

		$sql = "select *, (select group_concat(distinct cqzone order by cqzone separator ', ') from dxcc_master where countrycode = thcv.col_dxcc and cqzone <> '' order by cqzone asc) as correctcqzone
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		where station_profile.user_id = ?
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and cqzone = col_cqz) and col_dxcc > 0
		";

		$params[] = $this->session->userdata('user_id');

		$sql .= " order by station_profile.station_profile_name, thcv.col_time_on desc
		limit 5000";

		$query = $this->db->query($sql, $params);

		return $query->result();
	}

	function getIncorrectItuZones() {
		if(!clubaccess_check(9)) return;

		$sql = "select *, (select group_concat(distinct ituzone order by ituzone separator ', ') from dxcc_master where countrycode = thcv.col_dxcc and ituzone <> '' order by ituzone asc) as correctituzone
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		where station_profile.user_id = ?
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and ituzone = col_ituz) and col_dxcc > 0
		";

		$params[] = $this->session->userdata('user_id');

		$sql .= " order by station_profile.station_profile_name, thcv.col_time_on desc
		limit 5000";

		$query = $this->db->query($sql, $params);

		return $query->result();
	}

	public function checkIota() {
		$result1 = $this->checkSingleIota();
		$result2 = $this->checkMultiDxccIota();

		$merged = array_merge($result1, $result2);

		// Sort merged results by station_profile_name, then col_time_on DESC
		usort($merged, function($a, $b) {
			$stationCompare = strcmp($a->station_profile_name, $b->station_profile_name);
			if ($stationCompare !== 0) {
				return $stationCompare;
			}
			// If same station, sort by time_on descending (newest first)
			return strtotime($b->col_time_on) - strtotime($a->col_time_on);
		});

		return $merged;
	}

	/*
	 * Get list of QSOs with IOTA that do not match the IOTAs listed for the DXCC.
	 * Some islands are excluded as they can be in multiple DXCCs.
	 *
	 * These are excluded by not having a dxccid or dxccid = 0
	 *
	 */
	public function checkSingleIota() {
		$sql = "select col_primary_key, col_time_on, col_call, col_sat_name, col_band, col_gridsquare, col_dxcc, col_country, station_profile_name, col_lotw_qsl_rcvd, col_mode, col_submode, col_iota, iotadxcc.name as correctdxcc
		from  " . $this->config->item('table_name') . "  thcv
		join station_profile on thcv.station_id = station_profile.station_id
		join dxcc_entities on dxcc_entities.adif = thcv.COL_DXCC
		join iota on thcv.col_iota = iota.tag
		join dxcc_entities iotadxcc on iota.dxccid = iotadxcc.adif
		where station_profile.user_id = ?
		and thcv.col_dxcc > 0
		and thcv.col_dxcc <> iota.dxccid
		and iota.dxccid > 0
		order by station_profile_name, col_time_on desc";

		$bindings[] = [$this->session->userdata('user_id')];

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/*
	 * Get list of QSOs with multi-DXCC IOTA tags where the DXCC prefix doesn't match
	 * any of the valid prefixes for that IOTA.
	 */
	public function checkMultiDxccIota() {
		// Define IOTA tags that span multiple DXCCs with their valid prefixes
		$multiDxccIotas = [
			'AS-004' => [215, 283], // 5B4, ZC4
			'EU-053' => [167, 284], // OJ0, SM
			'EU-115' => [245, 265], // EI, GI
			'EU-117' => [151, 224], // R1M, OH
			'EU-129' => [230, 269], // DL, SP
			'EU-191' => [275, 288], // YO, UR
			'EU-192' => [284, 224], // SM, OH
			'NA-015' => [70, 105], // CO, KG4
			'NA-096' => [72, 78], // HH, HI
			'NA-105' => [213, 518], // FS, PJ7
			'OC-034' => [163, 327], // P2, YB
			'OC-088' => [46, 327, 345], // 9M6, V8, YB
			'OC-148' => [327, 511], // YB, 4W
			'SA-008' => [100, 112] // LU, CE
		];

		$allResults = [];

		foreach ($multiDxccIotas as $iotaTag => $adifList) {
			// Build IN clause for SQL
			$adifListStr = implode(',', $adifList);

			$sql = "SELECT thcv.col_primary_key, thcv.col_sat_name, thcv.col_time_on, thcv.col_call, thcv.col_band, thcv.col_gridsquare,
					thcv.col_dxcc, thcv.col_country, station_profile.station_profile_name, thcv.col_lotw_qsl_rcvd,
					thcv.col_mode, thcv.col_submode, thcv.col_iota,
					(
						SELECT GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ')
						FROM dxcc_entities d
						WHERE d.adif IN ($adifListStr)
					) as correctdxcc
					FROM " . $this->config->item('table_name') . " thcv
					JOIN station_profile ON thcv.station_id = station_profile.station_id
					JOIN dxcc_entities ON dxcc_entities.adif = thcv.COL_DXCC
					JOIN iota ON thcv.col_iota = iota.tag
					WHERE station_profile.user_id = ?
					AND thcv.col_iota = ?
					AND dxcc_entities.adif NOT IN ($adifListStr)
					ORDER BY station_profile_name, col_time_on DESC";

			$bindings = [$this->session->userdata('user_id'), $iotaTag];
			$query = $this->db->query($sql, $bindings);
			$results = $query->result();

			if (!empty($results)) {
				$allResults = array_merge($allResults, $results);
			}
		}

		// Sort the merged results by station_profile_name, then col_time_on DESC
		usort($allResults, function($a, $b) {
			$stationCompare = strcmp($a->station_profile_name, $b->station_profile_name);
			if ($stationCompare !== 0) {
				return $stationCompare;
			}
			// If same station, sort by time_on descending (newest first)
			return strtotime($b->col_time_on) - strtotime($a->col_time_on);
		});

		return $allResults;
	}

	function getGridsForDxcc($dxcc) {
		$sql = "select group_concat(distinct gridsquare order by gridsquare separator ', ') grids
		from vuccgrids
		where adif = ?";

		$query = $this->db->query($sql, array($dxcc));
		$row = $query->row();

		return $row->grids;
	}
}
