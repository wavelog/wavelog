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

    /*
     * Returns a result of worked/confirmed US Counties, grouped by STATE.
     * Satellite does not count.
     * No band split, as it only count the number of counties in the award.
     */
    function get_counties_summary($postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

        $this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$confirmed_condition = $this->genfunctions->addQslToQuery($postdata, true);

        // The where-clause is repeated in the subquery and the outer query,
        // so the bindings are passed twice.
		$binding = array();
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        $sql = "select count(distinct COL_CNTY) countycountworked, coalesce(x.countycountconfirmed, 0) countycountconfirmed, thcv.COL_STATE
                from " . $this->config->item('table_name') . " thcv
                 left outer join (
                        select count(distinct COL_CNTY) countycountconfirmed, COL_STATE
                        from " . $this->config->item('table_name') .
            " where station_id in (" . $location_list . ")" .
            " and col_band in (" . $bandslots_list . ")" .
            " and COL_DXCC in ('291', '6', '110')
                    and coalesce(COL_CNTY, '') <> ''
                    " . $band_condition . "
                    " . $mode_condition . "
                    and " . $confirmed_condition . "
                    group by COL_STATE
                    order by COL_STATE
                ) x on thcv.COL_STATE = x.COL_STATE
                 where station_id in (" . $location_list . ")" .
                 " and col_band in (" . $bandslots_list . ")" .
            " and COL_DXCC in ('291', '6', '110')
                and coalesce(COL_CNTY, '') <> ''
                " . $band_condition . "
                " . $mode_condition . "
                group by thcv.COL_STATE, countycountconfirmed
                order by thcv.COL_STATE";

        $query = $this->db->query($sql, array_merge($binding, $binding));
        return $query->result_array();
    }

    /*
    * Makes a list of all counties in given state
    */
    function counties_details($state, $type, $postdata) {
        if ($type == 'worked') {
            $counties = $this->get_counties($state, 'none', $postdata);
        } else if ($type == 'confirmed') {
            $counties = $this->get_counties($state, 'confirmed', $postdata);
        }
        if (!isset($counties)) {
            return 0;
        } else {
            ksort($counties);
            return $counties;
        }
    }

    function get_counties($state, $confirmationtype, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

        $this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$binding = array();
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        $sql = "select distinct COL_CNTY, COL_STATE
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_list . ")" .
		" and col_band in (" . $bandslots_list . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(COL_CNTY, '') <> ''
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
    * what counts toward the USA-CA award.
    */
    function get_county_counts($state, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$confirmed_condition = $this->genfunctions->addQslToQuery($postdata, true);

		$binding = array();
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        $sql = "select COL_CNTY,
			count(*) as worked,
			sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_list . ")" .
		" and col_band in (" . $bandslots_list . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(COL_CNTY, '') <> ''
		" . $band_condition . "
		" . $mode_condition;

		if ($state != 'All') {
			$sql .= " and COL_STATE = ?";
			$binding[] = $state;
		}

		$sql .= " group by COL_CNTY order by COL_CNTY";

		$query = $this->db->query($sql, $binding);
        return $query->result_array();
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

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$confirmed_condition = $this->genfunctions->addQslToQuery($postdata, true);

		$binding = array();
		$band_condition = $this->band_condition($postdata['band'] ?? 'All', $binding);
		$mode_condition = $this->genfunctions->addModeToQuery($postdata['mode'] ?? 'All', $binding);

        // COL_CNTY is stored as "STATE,COUNTY" for US QSOs (ADIF format),
        // but the map keys on the bare name.
        $cnty_name = "TRIM(SUBSTRING_INDEX(COL_CNTY, ',', -1))";

        $sql = "select COL_STATE, $cnty_name as COL_CNTY,
				count(*) as worked,
				sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
			from " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")" .
			" and col_band in (" . $bandslots_list . ")" .
			" and COL_DXCC in ('291', '6', '110')
			and coalesce(COL_CNTY, '') <> ''
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

    /*
     * Returns the total number of counties per state (the "target") taken from
     * assets/json/US_counties.csv, keyed by the 2-letter state code.
     */
    function get_counties_targets() {
	    $cache_key = 'UsCountiesTargets';

	    if (!$targets = $this->cache->get($cache_key)) {
		    $targets = array();
		    $file = 'assets/json/US_counties.csv';

		    if (is_readable($file) && ($handle = fopen($file, 'r')) !== false) {
			    while (($row = fgetcsv($handle, 1000, ",", '"', '\\')) !== false) {
				    if (count($row) < 1) {
					    continue;
				    }
				    $name = $row[0];
				    $code = isset($this->us_state_codes[$name]) ? $this->us_state_codes[$name] : null;
				    if ($code !== null) {
					    if (!isset($targets[$code])) {
						    $targets[$code] = 0;
					    }
					    $targets[$code]++;
				    }
			    }
			    fclose($handle);
		    }

		    ksort($targets);
		    $this->cache->save($cache_key, $targets, (60 * 60 * 24));
	    }

	    return $targets;
    }

    /*
     * Returns worked/confirmed/target progress per US state, keyed by the
     * 2-letter state code. Every state present in US_counties.csv is included,
     * even if nothing has been worked there yet.
     */
    function get_counties_progress($postdata) {
        $targets = $this->get_counties_targets();
        $worked = $this->get_counties_summary($postdata);

        $worked_map = array();
        if (isset($worked)) {
            foreach ($worked as $row) {
                $worked_map[$row['COL_STATE']] = array(
                    'worked'     => (int) $row['countycountworked'],
                    'confirmed'  => (int) $row['countycountconfirmed'],
                );
            }
        }

        $progress = array();
        foreach ($targets as $code => $target) {
            $progress[$code] = array(
                'worked'     => isset($worked_map[$code]) ? $worked_map[$code]['worked'] : 0,
                'confirmed'  => isset($worked_map[$code]) ? $worked_map[$code]['confirmed'] : 0,
                'target'     => $target,
            );
        }

        ksort($progress);
        return $progress;
    }

}
