<?php

class Counties extends CI_Model
{

    function __construct() {
        $this->load->driver('cache', [
            'adapter' => $this->config->item('cache_adapter') ?? 'file',
            'backup'  => $this->config->item('cache_backup')  ?? 'file',
            'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
        ]);
        $this->load->library('Genfunctions');
    }

    /*
     * Band condition for this award: Genfunctions::addBandToQuery(), except
     * that satellite QSOs never count for USA-CA unless 'SAT' is selected.
     */
    function band_condition($bands, &$binding) {
        $condition = $this->genfunctions->addBandToQuery($bands, $binding);

        if ($condition == '' && !in_array('SAT', (array)$bands, true)) {
            $condition = " and (col_prop_mode != 'SAT' or col_prop_mode is NULL)";
        }

        return $condition;
    }

    /*
     * Returns the bare county name from a "STATE,COUNTY" COL_CNTY value;
     * bare names pass through unchanged.
     */
    function bare_county($county) {
        return trim(preg_replace('/^.*,/', '', $county));
    }

    private function placeholders($values) {
        if (empty($values)) {
            return "''";
        }
        return implode(',', array_fill(0, count($values), '?'));
    }


    /*
     * Worked/confirmed counties of a state for the detail dialogs. CSV
     * counties come first (canonical name, case variants merged), counties
     * only present in the log follow after and are flagged not_in_list.
     */
    function counties_details($state, $type, $postdata) {
        if ($type == 'worked') {
            $counties = $this->get_counties($state, 'none', $postdata);
        } else if ($type == 'confirmed') {
            $counties = $this->get_counties($state, 'confirmed', $postdata);
        }
        if (!isset($counties)) {
            return 0;
        }

        $canonical = array();
        foreach ($this->get_counties_list($state) as $name) {
            $canonical[strtoupper($name)] = $name;
        }

        $result = array();
        $extras = array();
        $seen = array();
        foreach ($counties as $row) {
            $bare = strtoupper($this->bare_county($row['COL_CNTY']));
            if (isset($canonical[$bare])) {
                if (!isset($seen[$bare])) {
                    $seen[$bare] = true;
                    $result[] = array('COL_CNTY' => $canonical[$bare], 'COL_STATE' => $row['COL_STATE']);
                }
            } else {
                $row['not_in_list'] = true;
                $extras[] = $row;
            }
        }

        $by_name = function ($a, $b) { return strcasecmp($a['COL_CNTY'], $b['COL_CNTY']); };
        usort($result, $by_name);
        usort($extras, $by_name);
        return array_merge($result, $extras);
    }

    function get_counties($state, $confirmationtype, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

        $this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$location_placeholders = $this->placeholders($logbooks_locations_array);
		$bandslots_placeholders = $this->placeholders($bandslots);

		$binding = array_merge($logbooks_locations_array, $bandslots);
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        $sql = "select distinct COL_CNTY, COL_STATE
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_placeholders . ")" .
		" and col_band in (" . $bandslots_placeholders . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1)), '') <> ''
		" . $band_condition . "
		" . $mode_condition;

        if ($state != 'All') {
			$sql .= " and COL_STATE = ?";
			$binding[] = $state;
        }

        if ($confirmationtype != 'none') {
            $sql .= $this->genfunctions->addQslToQuery($postdata);
        }

        $sql .= " order by thcv.COL_STATE";

        $query = $this->db->query($sql, $binding);
        return $query->result_array();
    }

    /*
    * Returns worked and confirmed QSO counts per county for a given state.
    * Uses the same band/DXCC/SAT rules as get_counties() so the counts match
    * what counts toward the USA-CA award. Every county of the state is listed
    * (worked or not); worked counties missing from US_counties.csv are
    * appended after them. Names are the bare county names, matched
    * case-insensitively.
    */
    function get_county_counts($state, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$location_placeholders = $this->placeholders($logbooks_locations_array);
		$bandslots_placeholders = $this->placeholders($bandslots);

		$confirmed_condition = $this->genfunctions->addQslToQuery($postdata, true);

		$binding = array_merge($logbooks_locations_array, $bandslots);
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        // COL_CNTY is stored as "STATE,COUNTY" for US QSOs (ADIF format),
        // but the counties are listed by their bare name.
        $cnty_name = "TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1))";

        $sql = "select $cnty_name as COL_CNTY,
			count(*) as worked,
			sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_placeholders . ")" .
		" and col_band in (" . $bandslots_placeholders . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1)), '') <> ''
		" . $band_condition . "
		" . $mode_condition;

		if ($state != 'All') {
			$sql .= " and COL_STATE = ?";
			$binding[] = $state;
		}

		$sql .= " group by $cnty_name order by $cnty_name";

		$query = $this->db->query($sql, $binding);

        $worked = array();
        foreach ($query->result_array() as $row) {
            $worked[strtoupper($row['COL_CNTY'])] = $row;
        }

        $result = array();
        foreach ($this->get_counties_list($state) as $county) {
            $row = $worked[strtoupper($county)] ?? null;
            $result[] = array(
                'COL_CNTY'   => $county,
                'worked'     => $row ? (int) $row['worked'] : 0,
                'confirmed'  => $row ? (int) $row['confirmed'] : 0,
            );
            unset($worked[strtoupper($county)]);
        }

        // Counties in the log but not in US_counties.csv keep their counts
        foreach ($worked as $row) {
            $result[] = array(
                'COL_CNTY'   => $row['COL_CNTY'],
                'worked'     => (int) $row['worked'],
                'confirmed'  => (int) $row['confirmed'],
                'not_in_list' => true,
            );
        }

        return $result;
    }

    /*
     * Returns worked/confirmed QSO counts per county across every state in
     * one query, for the counties map. Unlike get_county_counts(), this
     * keeps COL_STATE and COL_CNTY together so same-named counties from
     * different states don't collide.
     */
    function get_counties_map($postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$location_placeholders = $this->placeholders($logbooks_locations_array);
		$bandslots_placeholders = $this->placeholders($bandslots);

		$confirmed_condition = $this->genfunctions->addQslToQuery($postdata, true);

		$binding = array_merge($logbooks_locations_array, $bandslots);
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        // COL_CNTY is stored as "STATE,COUNTY" for US QSOs (ADIF format),
        // but the map keys on the bare name.
        $cnty_name = "TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1))";

        $sql = "select COL_STATE, $cnty_name as COL_CNTY,
				count(*) as worked,
				sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
			from " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_placeholders . ")" .
			" and col_band in (" . $bandslots_placeholders . ")" .
			" and COL_DXCC in ('291', '6', '110')
			and coalesce(TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1)), '') <> ''
			" . $band_condition . "
			" . $mode_condition . "
			group by COL_STATE, $cnty_name order by COL_STATE, $cnty_name";

		$query = $this->db->query($sql, $binding);
        return $query->result_array();
    }

    /*
     * Map of US state names (as written in assets/json/US_counties.csv) to their
     * 2-letter postal codes, which is what COL_STATE stores.
     */
    private $us_state_codes = array(
        'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
        'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE',
        'District of Columbia' => 'DC',
        'Florida' => 'FL', 'Georgia' => 'GA', 'Hawaii' => 'HI', 'Idaho' => 'ID',
        'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA', 'Kansas' => 'KS',
        'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME', 'Maryland' => 'MD',
        'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS',
        'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV',
        'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM', 'New York' => 'NY',
        'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH', 'Oklahoma' => 'OK',
        'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI', 'South Carolina' => 'SC',
        'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT',
        'Vermont' => 'VT', 'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV',
        'Wisconsin' => 'WI', 'Wyoming' => 'WY',
    );

    // Parsed US_counties.csv map: state code => county names. Cached 24h.
    private function parse_us_counties_csv() {
	    $cache_key = 'UsCountiesList';

	    if (!$counties = $this->cache->get($cache_key)) {
		    $counties = array();
		    $file = 'assets/json/US_counties.csv';

		    if (is_readable($file) && ($handle = fopen($file, 'r')) !== false) {
			    while (($row = fgetcsv($handle, 1000, ",", '"', '\\')) !== false) {
				    if (count($row) < 2) {
					    continue;
				    }
				    $code = isset($this->us_state_codes[$row[0]]) ? $this->us_state_codes[$row[0]] : null;
				    if ($code !== null) {
					    $counties[$code][] = $row[1];
				    }
			    }
			    fclose($handle);
		    }

		    $this->cache->save($cache_key, $counties, (60 * 60 * 24));
	    }

	    return $counties;
    }

    // All county names of a state (the "target" list)
    function get_counties_list($state) {
	    $counties = $this->parse_us_counties_csv();
	    return isset($counties[$state]) ? $counties[$state] : array();
    }

    /*
     * Returns the counties of a state that count toward the target but are not
     * worked yet: the US_counties.csv list minus the worked counties of the
     * log. Names are compared case-insensitively, as a QSO's county can be
     * typed or imported in any case.
     */
    function get_counties_needed($state, $postdata) {
        $needed = array();
        $worked = $this->get_counties($state, 'none', $postdata);

        $worked_map = array();
        if (isset($worked)) {
            foreach ($worked as $row) {
                $worked_map[strtoupper($this->bare_county($row['COL_CNTY']))] = true;
            }
        }

        foreach ($this->get_counties_list($state) as $county) {
            if (!isset($worked_map[strtoupper($county)])) {
                $needed[] = $county;
            }
        }

        return $needed;
    }

    /*
     * Returns worked/confirmed/target progress per US state, keyed by the
     * 2-letter state code. Only counties present in US_counties.csv count;
     * every such state is included, even if nothing has been worked there.
     */
    function get_counties_progress($postdata) {
        $counties = $this->parse_us_counties_csv();
        $county_counts = $this->get_counties_map($postdata);

        // Keyed by uppercase "STATE|COUNTY", like counties_map()
        $worked_map = array();
        if (isset($county_counts)) {
            foreach ($county_counts as $row) {
                $worked_map[strtoupper($row['COL_STATE'] . '|' . trim($row['COL_CNTY']))] = array(
                    'worked'    => (int) $row['worked'],
                    'confirmed' => (int) $row['confirmed'],
                );
            }
        }

        $progress = array();
        foreach ($counties as $code => $names) {
            $worked = 0;
            $confirmed = 0;
            foreach ($names as $name) {
                $entry = $worked_map[strtoupper($code . '|' . $name)] ?? null;
                if ($entry) {
                    $worked += ($entry['worked'] > 0) ? 1 : 0;
                    $confirmed += ($entry['confirmed'] > 0) ? 1 : 0;
                }
            }
            $progress[$code] = array(
                'worked'    => $worked,
                'confirmed' => $confirmed,
                'target'    => count($names),
            );
        }

        ksort($progress);
        return $progress;
    }

}
