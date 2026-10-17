<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Award progress resource (read-only)
 *
 * Worked/confirmed progress for an award, computed server-side with the same
 * aggregate queries as the award pages (a few GROUP BY queries per request).
 * Lets a client answer "how many DXCC do I have confirmed on 20m CW?" without
 * paging through the whole log via /qso.
 *
 * Supported awards: dxcc, cq, itu, wac, vucc.
 *
 * Route:  /api/v2/award?type=<award>
 * Scope:  award:read
 *
 * Parameters:
 *   ?type=            required: dxcc|cq|itu|wac|vucc
 *   ?detail=          summary (default) - worked/confirmed counts per band
 *                     entities          - one row per entity with its bands
 *   ?status=          entities only: worked|confirmed|unconfirmed|not_worked
 *                     (unconfirmed = worked but not confirmed)
 *   ?band=            one band, or SAT for satellite QSOs (default: all bands)
 *   ?station_id=      comma-separated station ids; ownership-checked (default:
 *                     all owned by the token user)
 *   ?mode=            dxcc, cq, itu, wac: matched against mode or submode
 *   ?confirmed_by=    dxcc, cq, itu, wac: comma list of lotw|qsl|eqsl|qrz|clublog
 *                     (default: lotw,qsl - as on the award pages)
 *   ?date_from=       dxcc, cq, itu: YYYY-MM-DD floor on the QSO date
 *   ?date_to=         dxcc, cq, itu: YYYY-MM-DD ceiling on the QSO date
 *   ?include_deleted= dxcc: true|false, count deleted entities (default false)
 *
 * VUCC counts 4-character grid squares (gridsquare and vucc_grids). Its
 * confirmation is fixed to paper QSL or LoTW and it has no mode or date
 * filter, as on the VUCC award page; entities lists worked grids only.
 *
 * Unlike the award pages, bands are not filtered by the user's band settings:
 * every band with QSOs is returned. "total" counts distinct entities over all
 * returned bands including SAT, "total_excl_sat" matches the award page's
 * Total column (satellite QSOs are a separate column there).
 */
class Award_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'award';

	protected const TYPES    = ['dxcc', 'cq', 'itu', 'wac', 'vucc'];
	protected const DETAILS  = ['summary', 'entities'];
	protected const STATUSES = ['worked', 'confirmed', 'unconfirmed', 'not_worked'];

	/** Confirmation types counted when ?confirmed_by= is absent. */
	protected const DEFAULT_CONFIRMED_BY = ['lotw', 'qsl'];

	/** Optional filters each award supports; any other one is a 400. */
	protected const SUPPORTED_FILTERS = [
		'dxcc' => ['mode', 'confirmed_by', 'date_from', 'date_to', 'include_deleted'],
		'cq'   => ['mode', 'confirmed_by', 'date_from', 'date_to'],
		'itu'  => ['mode', 'confirmed_by', 'date_from', 'date_to'],
		'wac'  => ['mode', 'confirmed_by'],
		'vucc' => [],
	];

	protected const ALL_FILTERS = ['mode', 'confirmed_by', 'date_from', 'date_to', 'include_deleted'];

	protected const CONTINENTS = [
		'AF' => 'Africa',
		'AN' => 'Antarctica',
		'AS' => 'Asia',
		'EU' => 'Europe',
		'NA' => 'North America',
		'OC' => 'Oceania',
		'SA' => 'South America',
	];

	/**
	 * Translated label for the registry entry of this resource's read scope.
	 */
	protected static function scope_labels() {
		return ['read' => __("Read award progress")];
	}

	/**
	 * GET /api/v2/award
	 * Award progress of the token owner. Clubstation members below officer
	 * level are refused: the award queries cannot be limited to one operator,
	 * so they would see the progress of the whole clubstation.
	 */
	public function index() {
		if ($this->is_restricted_club_member()) {
			throw new Api_v2_exception('forbidden', 'Award progress requires clubstation officer permission', 403);
		}

		$type   = $this->parse_enum('type', self::TYPES, null);
		$detail = $this->parse_enum('detail', self::DETAILS, 'summary');
		$status = $this->parse_enum('status', self::STATUSES, '');

		if ($status !== '' && $detail !== 'entities') {
			throw new Api_v2_exception('validation_error', 'status requires detail=entities', 400);
		}
		if ($status === 'not_worked' && $type === 'vucc') {
			throw new Api_v2_exception('validation_error', 'status=not_worked is not supported for type=vucc', 400);
		}

		foreach (array_diff(self::ALL_FILTERS, self::SUPPORTED_FILTERS[$type]) as $unsupported) {
			if ($this->param($unsupported) !== null) {
				throw new Api_v2_exception(
					'validation_error',
					$unsupported . ' is not supported for type=' . $type,
					400,
					['supported' => self::SUPPORTED_FILTERS[$type]]
				);
			}
		}

		$filters = [
			'band'            => $this->normalize_band($this->param('band')),
			'mode'            => $this->normalize_mode($this->param('mode')),
			'confirmed_by'    => $this->parse_type_list('confirmed_by', self::CONFIRMATION_TYPES) ?: self::DEFAULT_CONFIRMED_BY,
			'date_from'       => $this->parse_date('date_from'),
			'date_to'         => $this->parse_date('date_to'),
			'include_deleted' => $this->parse_bool('include_deleted'),
		];
		$station_ids = $this->resolve_station_ids();

		$catalog = $this->catalog($type, $filters);
		$cells = ($type === 'vucc')
			? $this->vucc_cells($station_ids, $filters['band'])
			: $this->award_cells($type, $station_ids, $filters);

		// VUCC has no catalog (32,400 grids): its entities are the worked grids
		if ($catalog === null) {
			$keys = array_keys($cells);
			sort($keys, SORT_STRING);
			$catalog = array_fill_keys($keys, []);
		} else {
			$cells = array_intersect_key($cells, $catalog);
		}

		$bands = $this->ordered_bands($cells);

		$meta = ['filters' => [
			'type'            => $type,
			'detail'          => $detail,
			'status'          => $status ?: null,
			'band'            => $filters['band'] ?: null,
			'station_id'      => $station_ids,
		]];
		foreach (self::SUPPORTED_FILTERS[$type] as $key) {
			$meta['filters'][$key] = is_bool($filters[$key]) ? $filters[$key] : ($filters[$key] ?: null);
		}

		if ($detail === 'summary') {
			$data = $this->summary($type, $catalog, $cells, $bands);
		} else {
			$data = $this->entities($type, $catalog, $cells, $bands, $status);
			$meta['count'] = count($data);
		}

		$this->CI->api_v2_response->respond($data, 200, $meta);
	}

	// --- Parameters --------------------------------------------------------

	/**
	 * A single-value enum parameter. A null $default makes it required.
	 */
	protected function parse_enum($key, $allowed, $default) {
		$raw = $this->param($key);
		if ($raw === null) {
			if ($default === null) {
				throw new Api_v2_exception('validation_error', $key . ' is required', 400, ['allowed' => $allowed]);
			}
			return $default;
		}
		$value = strtolower(trim((string) $raw));
		if (!in_array($value, $allowed, true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Invalid ' . $key . ' "' . $value . '". Allowed: ' . implode(', ', $allowed),
				400,
				['allowed' => $allowed]
			);
		}
		return $value;
	}

	/**
	 * A true|false (or 1|0) parameter, false when absent.
	 */
	protected function parse_bool($key) {
		$raw = $this->param($key);
		if ($raw === null) {
			return false;
		}
		$value = strtolower(trim((string) $raw));
		if (in_array($value, ['1', 'true'], true)) {
			return true;
		}
		if (in_array($value, ['0', 'false'], true)) {
			return false;
		}
		throw new Api_v2_exception('validation_error', $key . ' must be true or false', 400);
	}

	// --- Data --------------------------------------------------------------

	/**
	 * All entities of the award as key => attributes, or null for VUCC.
	 */
	protected function catalog($type, $filters) {
		switch ($type) {
			case 'dxcc':
				$this->CI->load->model('dxcc');
				$postdata = $this->dxcc_postdata($filters);
				$postdata['notworked'] = 1;
				$catalog = [];
				foreach ($this->CI->dxcc->fetchDxcc($postdata, "'-1'") as $row) {
					if ((int) $row->adif === 0) {
						continue;
					}
					$catalog[(int) $row->adif] = [
						'name'    => $row->name,
						'prefix'  => $row->prefix,
						'deleted' => $row->Enddate !== null,
					];
				}
				return $catalog;
			case 'cq':
				return array_fill_keys(range(1, 40), []);
			case 'itu':
				return array_fill_keys(range(1, 90), []);
			case 'wac':
				return array_map(function ($name) {
					return ['name' => $name];
				}, self::CONTINENTS);
			default:
				return null;
		}
	}

	/**
	 * Worked/confirmed status per entity and band for dxcc, cq, itu and wac,
	 * from the models' aggregate queries: [key][band] => bool confirmed.
	 * The satellite query only runs when SAT is requested (or no band is).
	 */
	protected function award_cells($type, $station_ids, $filters) {
		$location_list = $this->location_list($station_ids);
		$band = $filters['band'];
		$postdata = [
			'mode'     => $filters['mode'] ?: 'All',
			'datefrom' => $filters['date_from'] ?: null,
			'dateto'   => $filters['date_to'] ?: null,
		];

		switch ($type) {
			case 'dxcc':
				$this->CI->load->model('dxcc');
				$model = $this->CI->dxcc;
				$methods = ['getDxccData', 'getDxccDataSat'];
				$key = 'dxcc';
				$postdata = $this->dxcc_postdata($filters);
				break;
			case 'cq':
				$this->CI->load->model('cq');
				$model = $this->CI->cq;
				$methods = ['getCqZoneData', 'getCqZoneDataSat'];
				$key = 'col_cqz';
				break;
			case 'itu':
				$this->CI->load->model('itu');
				$model = $this->CI->itu;
				$methods = ['getItuZoneData', 'getItuZoneDataSat'];
				$key = 'col_ituz';
				break;
			case 'wac':
				$this->CI->load->model('wac');
				$model = $this->CI->wac;
				$methods = ['getWacData', 'getWacDataSat'];
				$key = 'col_cont';
				$postdata += ['sat' => 'All', 'orbit' => 'All'];
				break;
		}

		$rows = [];
		if ($band !== 'SAT') {
			$rows = array_merge($rows, $model->{$methods[0]}($location_list, $postdata));
		}
		if ($band === '' || $band === 'SAT') {
			$rows = array_merge($rows, $model->{$methods[1]}($location_list, $postdata));
		}

		$cells = [];
		foreach ($rows as $row) {
			$row_band = $this->normalize_band($row->col_band);
			if ($band !== '' && $row_band !== $band) {
				continue;
			}
			$confirmed = false;
			foreach ($filters['confirmed_by'] as $qsl_type) {
				if ((int) $row->$qsl_type === 1) {
					$confirmed = true;
					break;
				}
			}
			$entity = ($type === 'wac') ? $row->$key : (int) $row->$key;
			$cells[$entity][$row_band] = ($cells[$entity][$row_band] ?? false) || $confirmed;
		}
		return $cells;
	}

	/**
	 * Worked/confirmed status per 4-character grid and band for VUCC, the
	 * same way VUCC::fetchVucc() builds the award page.
	 */
	protected function vucc_cells($station_ids, $band) {
		$this->CI->load->model('vucc');
		$this->CI->vucc->set_location_list($station_ids);

		$sets = [];
		if ($band !== 'SAT') {
			$sets[] = $this->CI->vucc->get_vucc_combined_data_all_bands();
		}
		if ($band === '' || $band === 'SAT') {
			$sets[] = $this->CI->vucc->get_vucc_combined_data_sat();
		}

		$cells = [];
		foreach ($sets as $set) {
			foreach ($set['gridsquare'] as $row) {
				$this->add_vucc_cell($cells, $row['gridsquare'], $row['col_band'], $row['confirmed'], $band);
			}
			foreach ($set['vucc_grids'] as $row) {
				foreach (explode(',', $row['col_vucc_grids']) as $grid) {
					$this->add_vucc_cell($cells, $grid, $row['col_band'], $row['confirmed'], $band);
				}
			}
		}
		return $cells;
	}

	/**
	 * Add one grid of a VUCC row, cut to 4 characters, to the cell map.
	 */
	protected function add_vucc_cell(&$cells, $grid, $row_band, $confirmed, $band) {
		$grid = strtoupper(substr(trim($grid), 0, 4));
		$row_band = $this->normalize_band($row_band);
		if ($grid === '' || ($band !== '' && $row_band !== $band)) {
			return;
		}
		$cells[$grid][$row_band] = ($cells[$grid][$row_band] ?? false) || (bool) $confirmed;
	}

	/**
	 * $postdata in the shape the DXCC model expects; filters it has no API
	 * parameter for (satellite, orbit, propagation mode, continents) are off.
	 */
	protected function dxcc_postdata($filters) {
		return [
			'mode'           => $filters['mode'] ?: 'All',
			'dateFrom'       => $filters['date_from'] ?: null,
			'dateTo'         => $filters['date_to'] ?: null,
			'includedeleted' => $filters['include_deleted'] ? 1 : null,
			'sat'            => 'All',
			'orbit'          => 'All',
			'prop_mode'      => 'All',
			'band'           => 'All',
			'notworked'      => null,
			'Africa'         => 1,
			'Antarctica'     => 1,
			'Asia'           => 1,
			'Europe'         => 1,
			'NorthAmerica'   => 1,
			'Oceania'        => 1,
			'SouthAmerica'   => 1,
		];
	}

	/**
	 * Station ids as the quoted SQL list the award models expect.
	 */
	protected function location_list($station_ids) {
		if (empty($station_ids)) {
			return "'-1'";
		}
		return implode(',', array_map(function ($id) {
			return "'" . (int) $id . "'";
		}, $station_ids));
	}

	/**
	 * Bands present in the result, in the order of the award pages.
	 */
	protected function ordered_bands($cells) {
		$present = [];
		foreach ($cells as $entity_bands) {
			foreach ($entity_bands as $band => $confirmed) {
				$present[$band] = true;
			}
		}

		$this->CI->load->model('bands');
		$order = array_keys($this->CI->bands->bandslots);
		$known = array_values(array_intersect($order, array_keys($present)));
		$other = array_values(array_diff(array_keys($present), $order));
		sort($other, SORT_NATURAL);
		return array_merge($known, $other);
	}

	// --- Output ------------------------------------------------------------

	/**
	 * Worked and confirmed entity counts, in total and per band.
	 */
	protected function summary($type, $catalog, $cells, $bands) {
		$out = [
			'type'           => $type,
			'entities_total' => ($type === 'vucc') ? null : count($catalog),
		];

		foreach (['worked' => false, 'confirmed' => true] as $state => $need_confirmed) {
			$per_band = array_fill_keys($bands, 0);
			$total = 0;
			$total_excl_sat = 0;
			foreach ($cells as $entity_bands) {
				$any = false;
				$any_terrestrial = false;
				foreach ($entity_bands as $band => $confirmed) {
					if ($need_confirmed && !$confirmed) {
						continue;
					}
					$per_band[$band]++;
					$any = true;
					if ($band !== 'SAT') {
						$any_terrestrial = true;
					}
				}
				$total += $any ? 1 : 0;
				$total_excl_sat += $any_terrestrial ? 1 : 0;
			}
			$out[$state] = [
				'total'          => $total,
				'total_excl_sat' => $total_excl_sat,
				'bands'          => (object) $per_band,
			];
		}
		return $out;
	}

	/**
	 * One row per entity with its per-band status, W (worked) or C (confirmed).
	 */
	protected function entities($type, $catalog, $cells, $bands, $status) {
		$out = [];
		foreach ($catalog as $key => $attributes) {
			$entity_bands = $cells[$key] ?? [];
			$worked = !empty($entity_bands);
			$confirmed = in_array(true, $entity_bands, true);

			if (($status === 'worked' && !$worked)
				|| ($status === 'confirmed' && !$confirmed)
				|| ($status === 'unconfirmed' && (!$worked || $confirmed))
				|| ($status === 'not_worked' && $worked)) {
				continue;
			}

			$band_status = [];
			foreach ($bands as $band) {
				if (isset($entity_bands[$band])) {
					$band_status[$band] = $entity_bands[$band] ? 'C' : 'W';
				}
			}

			$out[] = array_merge(
				['id' => $key],
				$attributes,
				['worked' => $worked, 'confirmed' => $confirmed, 'bands' => (object) $band_status]
			);
		}
		return $out;
	}
}
