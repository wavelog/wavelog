<?php

class Counties extends CI_Model
{

    /*
     * Maps the selectable QSL confirmation sources to their DB columns.
     * Used to build the "confirmed" condition based on user selection.
     */
    private $qsl_source_columns = array(
        'qsl'     => 'col_qsl_rcvd',
        'lotw'    => 'col_lotw_qsl_rcvd',
        'eqsl'    => 'col_eqsl_qsl_rcvd',
        'qrz'     => 'COL_QRZCOM_QSO_DOWNLOAD_STATUS',
        'clublog' => 'COL_CLUBLOG_QSO_DOWNLOAD_STATUS',
    );

    /*
     * Selectable bands for the counties award's Band filter (160m through
     * 70cm - the HF/VHF/UHF bands typically worked for USA-CA). Order here
     * is also the display order in the filter UI.
     */
    private $band_list = array(
        '160m', '80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m',
        '6m', '2m', '1.25m', '70cm',
    );

    /*
     * Modes counted toward the award's Phone endorsement, per ADIF 3.1.7
     * (https://www.adif.org/317/ADIF_317.htm): SSB, AM and FM are Mode
     * values (LSB/USB are legacy [import-only] Mode values for SSB); voice
     * counts as phone whether analog or digital, so DIGITALVOICE and all of
     * its Submode values (C4FM, DMR, DSTAR, FREEDV, M17 - same grouping
     * assets/js/radiohelpers.js's MODE_LISTS.DIGITAL_VOICE uses) are
     * included too. CW is its own group. Digital data is everything else
     * (defined by exclusion below rather than an enumerated list, so new
     * data modes are counted automatically without needing a code change
     * here).
     */
    private $phone_modes = array(
        'AM', 'SSB', 'LSB', 'USB', 'FM',
        'DIGITALVOICE', 'C4FM', 'DMR', 'DSTAR', 'FREEDV', 'M17',
    );

    function __construct() {
        $this->load->driver('cache', [
            'adapter' => $this->config->item('cache_adapter') ?? 'file',
            'backup'  => $this->config->item('cache_backup')  ?? 'file',
            'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
        ]);
    }

    /*
     * Builds the SQL "confirmed" condition from a list of selected QSL
     * sources (subset of keys of $qsl_source_columns). Defaults to all
     * sources when null (not just empty) is given, so existing callers keep
     * prior behaviour while an explicit empty selection ("nothing counts as
     * confirmed") is still honoured rather than silently falling back to all.
     */
    function build_confirmed_condition($qsl_sources) {
        if ($qsl_sources === null) {
            $qsl_sources = array_keys($this->qsl_source_columns);
        }

        $conditions = array();
        foreach ($qsl_sources as $source) {
            if (isset($this->qsl_source_columns[$source])) {
                $conditions[] = $this->qsl_source_columns[$source] . " = 'Y'";
            }
        }

        if (empty($conditions)) {
            return '1=0';
        }

        return '(' . implode(' or ', $conditions) . ')';
    }

    /*
     * Returns the selectable bands (160m through 70cm) for the counties
     * award's Band filter, in display order.
     */
    function get_band_list() {
        return $this->band_list;
    }

    /*
     * Returns the selectable mode-endorsement groups for the counties
     * award's Mode filter, in display order.
     */
    function get_mode_groups() {
        return array('phone', 'cw', 'digital');
    }

    /*
     * Builds the SQL "COL_BAND in (...)" condition from a list of selected
     * bands (subset of $band_list). Defaults to all bands when null (not
     * just empty) is given. Values are validated against $band_list before
     * being interpolated into the query, since they come from user input.
     */
    function build_band_condition($bands) {
        if ($bands === null) {
            $bands = $this->band_list;
        }

        $valid_bands = array_values(array_intersect($bands, $this->band_list));

        if (empty($valid_bands)) {
            return '1=0';
        }

        $escaped = array_map(function ($band) {
            return $this->db->escape($band);
        }, $valid_bands);

        return 'COL_BAND in (' . implode(',', $escaped) . ')';
    }

    /*
     * Builds the SQL condition for the selected mode-endorsement groups
     * (subset of 'phone', 'cw', 'digital'). Defaults to all three when null
     * (not just empty) is given. Checks both COL_MODE and COL_SUBMODE, since
     * logging software varies in which one it populates for a given mode
     * (mirrors Award_pl_polska's approach). 'digital' is everything that
     * isn't phone or CW, rather than an enumerated list.
     */
    function build_mode_condition($mode_groups) {
        if ($mode_groups === null) {
            $mode_groups = $this->get_mode_groups();
        }

        // COALESCE COL_SUBMODE to '' before comparing: a NULL submode (the
        // common case - most modes aren't logged with one) makes the "or
        // UPPER(COL_SUBMODE) in (...)" clause evaluate to NULL rather than
        // false, which poisons "not phone_condition and not cw_condition"
        // into NULL (i.e. excluded) for every plain-COL_MODE digital QSO.
        $phone_list = "'" . implode("','", $this->phone_modes) . "'";
        $phone_condition = "(UPPER(COL_MODE) in ($phone_list) or UPPER(COALESCE(COL_SUBMODE, '')) in ($phone_list))";
        $cw_condition = "(UPPER(COL_MODE) = 'CW' or UPPER(COALESCE(COL_SUBMODE, '')) = 'CW')";
        $digital_condition = "(not $phone_condition and not $cw_condition)";

        $conditions = array();
        if (in_array('phone', $mode_groups)) {
            $conditions[] = $phone_condition;
        }
        if (in_array('cw', $mode_groups)) {
            $conditions[] = $cw_condition;
        }
        if (in_array('digital', $mode_groups)) {
            $conditions[] = $digital_condition;
        }

        if (empty($conditions)) {
            return '1=0';
        }

        return '(' . implode(' or ', $conditions) . ')';
    }

    /*
     * Returns a result of worked/confirmed US Counties, grouped by STATE.
     * The QSL sources counted as "confirmed" are selectable; see
     * build_confirmed_condition(). Satellite does not count.
     * No band split, as it only count the number of counties in the award.
     */
    function get_counties_summary($qsl_sources = null, $bands = null, $mode_groups = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

        $this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$confirmed_condition = $this->build_confirmed_condition($qsl_sources);
		$band_condition = $this->build_band_condition($bands);
		$mode_condition = $this->build_mode_condition($mode_groups);

        $sql = "select count(distinct COL_CNTY) countycountworked, coalesce(x.countycountconfirmed, 0) countycountconfirmed, thcv.COL_STATE
                from " . $this->config->item('table_name') . " thcv
                 left outer join (
                        select count(distinct COL_CNTY) countycountconfirmed, COL_STATE
                        from " . $this->config->item('table_name') .
            " where station_id in (" . $location_list . ")" .
            " and col_band in (" . $bandslots_list . ")" .
            " and COL_DXCC in ('291', '6', '110')
                    and coalesce(COL_CNTY, '') <> ''
                    and COL_BAND != 'SAT'
                    and " . $band_condition . "
                    and " . $mode_condition . "
                    and " . $confirmed_condition . "
                    group by COL_STATE
                    order by COL_STATE
                ) x on thcv.COL_STATE = x.COL_STATE
                 where station_id in (" . $location_list . ")" .
                 " and col_band in (" . $bandslots_list . ")" .
            " and COL_DXCC in ('291', '6', '110')
                and coalesce(COL_CNTY, '') <> ''
                and COL_BAND != 'SAT'
                and " . $band_condition . "
                and " . $mode_condition . "
                group by thcv.COL_STATE, countycountconfirmed
                order by thcv.COL_STATE";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /*
    * Makes a list of all counties in given state
    */
    function counties_details($state, $type, $qsl_sources = null, $bands = null, $mode_groups = null) {
        if ($type == 'worked') {
            $counties = $this->get_counties($state, 'none', $qsl_sources, $bands, $mode_groups);
        } else if ($type == 'confirmed') {
            $counties = $this->get_counties($state, 'confirmed', $qsl_sources, $bands, $mode_groups);
        }
        if (!isset($counties)) {
            return 0;
        } else {
            ksort($counties);
            return $counties;
        }
    }

    function get_counties($state, $confirmationtype, $qsl_sources = null, $bands = null, $mode_groups = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

        $this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$binding = [];

        $sql = "select distinct COL_CNTY, COL_STATE
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_list . ")" .
		" and col_band in (" . $bandslots_list . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(COL_CNTY, '') <> ''
		and COL_BAND != 'SAT'
		and " . $this->build_band_condition($bands) . "
		and " . $this->build_mode_condition($mode_groups);

        if ($state != 'All') {
			$sql .= " and COL_STATE = ?";
			$binding[] = $state;
        }

        if ($confirmationtype != 'none') {
            $sql .= " and " . $this->build_confirmed_condition($qsl_sources);
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
    function get_county_counts($state, $qsl_sources = null, $bands = null, $mode_groups = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$binding = [];

		$confirmed_condition = $this->build_confirmed_condition($qsl_sources);

        $sql = "select COL_CNTY,
			count(*) as worked,
			sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
		from " . $this->config->item('table_name') . " thcv
		where station_id in (" . $location_list . ")" .
		" and col_band in (" . $bandslots_list . ")" .
		" and COL_DXCC in ('291', '6', '110')
		and coalesce(COL_CNTY, '') <> ''
		and COL_BAND != 'SAT'
		and " . $this->build_band_condition($bands) . "
		and " . $this->build_mode_condition($mode_groups);

		if ($state != 'All') {
			$sql .= " and COL_STATE = ?";
			$binding[] = $state;
		}

		$sql .= " group by COL_CNTY order by COL_CNTY";

		$query = $this->db->query($sql, $binding);
        return $query->result_array();
    }

    /*
     * Returns worked/confirmed QSO counts per county across every state in one
     * query, for the "Show Counties Map" feature. Unlike get_county_counts(),
     * which is scoped to a single state (or 'All' counties merged together,
     * colliding same-named counties from different states), this keeps
     * COL_STATE and COL_CNTY together so the caller can key results per state.
     * Same band/DXCC/SAT rules as get_counties()/get_county_counts().
     */
    function get_counties_map($qsl_sources = null, $bands = null, $mode_groups = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('uscounties');

		$bandslots_list = "'".implode("','",$bandslots)."'";

		$confirmed_condition = $this->build_confirmed_condition($qsl_sources);

        $sql = "select COL_STATE, COL_CNTY,
				count(*) as worked,
				sum(case when " . $confirmed_condition . " then 1 else 0 end) as confirmed
			from " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")" .
			" and col_band in (" . $bandslots_list . ")" .
			" and COL_DXCC in ('291', '6', '110')
			and coalesce(COL_CNTY, '') <> ''
			and COL_BAND != 'SAT'
			and " . $this->build_band_condition($bands) . "
			and " . $this->build_mode_condition($mode_groups) . "
			group by COL_STATE, COL_CNTY order by COL_STATE, COL_CNTY";

		$query = $this->db->query($sql);
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
    function get_counties_progress($qsl_sources = null, $bands = null, $mode_groups = null) {
        $targets = $this->get_counties_targets();
        $worked = $this->get_counties_summary($qsl_sources, $bands, $mode_groups);

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
