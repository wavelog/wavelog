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
     * counties come first (canonical name, case variants merged), sorted by
     * scoring_group so a multi-member group (Alaska's judicial districts)
     * lists together instead of plain alphabetical; counties only present in
     * the log follow after and are flagged not_in_list.
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
        $group_map = $this->group_map($state);
        $group_sizes = $this->group_sizes($state);

        $result = array();
        $extras = array();
        $seen = array();
        foreach ($counties as $row) {
            $bare = strtoupper($this->bare_county($row['COL_CNTY']));
            if (isset($canonical[$bare])) {
                if (!isset($seen[$bare])) {
                    $seen[$bare] = true;
                    $group = $group_map[$bare] ?? null;
                    $result[] = array(
                        'COL_CNTY'  => $canonical[$bare],
                        'COL_STATE' => $row['COL_STATE'],
                        'group'     => ($group_sizes[$group] ?? 0) > 1 ? $group : null,
                    );
                }
            } else {
                $row['not_in_list'] = true;
                $extras[] = $row;
            }
        }

        usort($result, function ($a, $b) {
            if ($a['group'] !== $b['group']) {
                return $this->group_sort_key($a['group']) <=> $this->group_sort_key($b['group']);
            }
            return strcasecmp($a['COL_CNTY'], $b['COL_CNTY']);
        });
        $by_name = function ($a, $b) { return strcasecmp($a['COL_CNTY'], $b['COL_CNTY']); };
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

        // Ordered by scoring_group (group_sort_key()) so a multi-member group
        // (Alaska's judicial districts) lists together instead of plain
        // alphabetical; every other state is unaffected since it has no such
        // grouping (group_sizes()[group] is always 1 there).
        $group_sizes = $this->group_sizes($state);
        $rows_by_state = $this->parse_us_counties_csv()[$state] ?? array();
        usort($rows_by_state, function ($a, $b) {
            return $this->group_sort_key($a['group']) <=> $this->group_sort_key($b['group']);
        });

        $result = array();
        foreach ($rows_by_state as $csv_row) {
            $county = $csv_row['name'];
            $row = $worked[strtoupper($county)] ?? null;
            $result[] = array(
                'COL_CNTY'   => $county,
                'group'      => ($group_sizes[$csv_row['group']] ?? 0) > 1 ? $csv_row['group'] : null,
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

    // US_counties.csv rows that don't count toward any USA-CA target: independent
    // cities, DC, and Carson City NV. See assets/json/US_COUNTIES_SOURCE.md.
    const UNSCORED = 'UNSCORED';

    // Parsed US_counties.csv map: state code => array of ['name' => bare ARRL
    // name, 'group' => USA-CA scoring_group]. Cached 24h. Cache key bumped to
    // v2 for the added 'group' column (wavelog/wavelog#3782) so a stale
    // pre-upgrade cache entry can't be read with the old flat-string shape.
    private function parse_us_counties_csv() {
	    $cache_key = 'UsCountiesListV2';

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
					    $counties[$code][] = array(
						    'name'  => $row[1],
						    'group' => $row[3] ?? $row[1],
					    );
				    }
			    }
			    fclose($handle);
		    }

		    $this->cache->save($cache_key, $counties, (60 * 60 * 24));
	    }

	    return $counties;
    }

    // Raw bare county names of a state, as logged (matches COL_CNTY exactly).
    // Used by the worked/confirmed detail dialogs, which are unaffected by
    // USA-CA scoring grouping.
    function get_counties_list($state) {
	    $rows = $this->parse_us_counties_csv();
	    return array_column($rows[$state] ?? array(), 'name');
    }

    // Uppercase raw name => scoring_group map for a state.
    private function group_map($state) {
	    $map = array();
	    foreach ($this->parse_us_counties_csv()[$state] ?? array() as $row) {
		    $map[strtoupper($row['name'])] = $row['group'];
	    }
	    return $map;
    }

    // scoring_group => member count for a state, UNSCORED rows excluded -
    // used to tell an actual grouping (Alaska's judicial districts) apart
    // from every other state's trivial one-row-per-group case.
    private function group_sizes($state) {
	    $sizes = array();
	    foreach ($this->parse_us_counties_csv()[$state] ?? array() as $row) {
		    if ($row['group'] !== self::UNSCORED) {
			    $sizes[$row['group']] = ($sizes[$row['group']] ?? 0) + 1;
		    }
	    }
	    return $sizes;
    }

    // Sort key for a scoring_group name: Alaska's 4 judicial districts sort
    // in natural 1st-4th order; everything else (a plain county name) sorts
    // alphabetically after them, which is irrelevant since those groups never
    // sit next to each other in a mixed sort anyway.
    private function group_sort_key($group) {
	    static $ak_jd_order = array(
		    'First JD (SE)' => 1, 'Second JD (NW)' => 2, 'Third JD (SC)' => 3, 'Fourth JD (C)' => 4,
	    );
	    return $ak_jd_order[$group] ?? $group;
    }

    /*
     * The actual USA-CA award targets of a state: US_counties.csv rows deduped
     * by scoring_group ('name'), each with its underlying raw 'members' -
     * more than one only for Alaska's judicial districts, so callers can show
     * which boroughs count toward it. UNSCORED rows excluded. Matches MARAC's
     * 3,077-county list. Ordered by group_sort_key() (Alaska's JDs in 1st-4th
     * order; everywhere else, alphabetical - same as before this existed).
     */
    function get_scoring_targets($state) {
	    $groups = array();
	    foreach ($this->parse_us_counties_csv()[$state] ?? array() as $row) {
		    if ($row['group'] !== self::UNSCORED) {
			    $groups[$row['group']][] = $row['name'];
		    }
	    }
	    $targets = array();
	    foreach ($groups as $name => $members) {
		    $targets[] = array('name' => $name, 'members' => $members);
	    }
	    usort($targets, function ($a, $b) {
		    return $this->group_sort_key($a['name']) <=> $this->group_sort_key($b['name']);
	    });
	    return $targets;
    }

    /*
     * Returns the USA-CA targets of a state that are not worked yet: the
     * state's scoring targets minus the worked ones. A worked raw county
     * name counts toward whichever scoring_group it belongs to (e.g. any
     * Alaska borough worked satisfies its judicial district).
     */
    function get_counties_needed($state, $postdata) {
        $needed = array();
        $worked = $this->get_counties($state, 'none', $postdata);
        $group_map = $this->group_map($state);

        $worked_groups = array();
        if (isset($worked)) {
            foreach ($worked as $row) {
                $group = $group_map[strtoupper($this->bare_county($row['COL_CNTY']))] ?? null;
                if ($group !== null && $group !== self::UNSCORED) {
                    $worked_groups[$group] = true;
                }
            }
        }

        foreach ($this->get_scoring_targets($state) as $target) {
            if (!isset($worked_groups[$target['name']])) {
                $needed[] = $target;
            }
        }

        return $needed;
    }

    /*
     * Logged county names that don't count toward any USA-CA target -
     * independent cities, DC, Carson City NV (UNSCORED in US_counties.csv,
     * see US_COUNTIES_SOURCE.md) - surfaced instead of silently dropped from
     * Worked/Confirmed/Target (wavelog/wavelog#3782).
     */
    function get_counties_unmatched($state, $postdata) {
        $worked = $this->get_counties($state, 'none', $postdata);
        if (!isset($worked)) {
            return array();
        }

        $group_map = $this->group_map($state);
        $unmatched = array();
        foreach ($worked as $row) {
            $bare = $this->bare_county($row['COL_CNTY']);
            if (($group_map[strtoupper($bare)] ?? null) === self::UNSCORED) {
                $unmatched[$bare] = true;
            }
        }

        $result = array_keys($unmatched);
        sort($result, SORT_STRING | SORT_FLAG_CASE);
        return $result;
    }

    /*
     * Returns worked/confirmed/target/unmatched progress per US state, keyed
     * by the 2-letter state code. worked/confirmed/target count distinct
     * USA-CA scoring groups, not raw county rows (wavelog/wavelog#3782) -
     * see US_COUNTIES_SOURCE.md. unmatched lists logged UNSCORED county
     * names (independent cities etc.) instead of silently dropping them.
     */
    function get_counties_progress($postdata) {
        $counties = $this->parse_us_counties_csv();
        $county_counts = $this->get_counties_map($postdata);

        // Keyed by uppercase "STATE|COUNTY" raw name, like counties_map()
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
        foreach ($counties as $code => $rows) {
            $worked_groups = array();
            $confirmed_groups = array();
            $target_groups = array();
            $unmatched = array();

            foreach ($rows as $row) {
                if ($row['group'] !== self::UNSCORED) {
                    $target_groups[$row['group']] = true;
                }

                $entry = $worked_map[strtoupper($code . '|' . $row['name'])] ?? null;
                if (!$entry) {
                    continue;
                }

                if ($row['group'] === self::UNSCORED) {
                    if ($entry['worked'] > 0) {
                        $unmatched[] = $row['name'];
                    }
                    continue;
                }

                if ($entry['worked'] > 0) {
                    $worked_groups[$row['group']] = true;
                }
                if ($entry['confirmed'] > 0) {
                    $confirmed_groups[$row['group']] = true;
                }
            }

            $progress[$code] = array(
                'worked'    => count($worked_groups),
                'confirmed' => count($confirmed_groups),
                'target'    => count($target_groups),
                'unmatched' => $unmatched,
            );
        }

        ksort($progress);
        return $progress;
    }

}
