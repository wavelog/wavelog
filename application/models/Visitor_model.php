<?php

class Visitor_model extends CI_Model {

	function get_qsos($num, $StationLocationsArray, $band = '') {
		$this->db->select($this->config->item('table_name').'.*, station_profile.*');
		$this->db->from($this->config->item('table_name'));

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');

		if ($band != '') {
			if ($band == 'SAT') {
				$this->db->where($this->config->item('table_name').'.col_prop_mode', 'SAT');
			} else {
				$this->db->where($this->config->item('table_name').'.col_prop_mode !="SAT"');
				$this->db->where($this->config->item('table_name').'.col_band', $band);
			}
		}

		$this->db->where_in($this->config->item('table_name').'.station_id', $StationLocationsArray);
		$this->db->order_by(''.$this->config->item('table_name').'.COL_TIME_ON', "desc");

		$this->db->limit($num);

		return $this->db->get();
	}

	function getlastqsodate ($slug) {
		$this->load->model('stationsetup_model');
        $logbook_id = $this->stationsetup_model->public_slug_exists_logbook_id($slug);
		$userid = $this->stationsetup_model->public_slug_exists_userid($slug);
		$band = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'band','option_key'=>$slug), $userid)->row()->option_value ?? '';

		$sql = "select max(col_time_on) lastqso from " . $this->config->item('table_name') .
		" join station_profile on station_profile.station_id = " . $this->config->item('table_name') . ".station_id where 1 = 1";

		if ($band != '') {
			if ($band == 'SAT') {
				$sql .= " and " . $this->config->item('table_name') . ".col_prop_mode = 'SAT'";
			} else {
				$sql .= " and " . $this->config->item('table_name') . ".col_prop_mode != 'SAT'";
				$sql .= " and " . $this->config->item('table_name') . ".col_band = '". $band . "'";
			}
		}

		return $this->db->query($sql);
	}

	public function render_static_map($qsos) {

		// TODO: Enable caching

		$requiredClasses = [
			'./src/StaticMap/src/OpenStreetMap.php',
			'./src/StaticMap/src/LatLng.php',
			'./src/StaticMap/src/TileLayer.php',
			'./src/StaticMap/src/Markers.php',
			'./src/StaticMap/src/MapData.php',
			'./src/StaticMap/src/XY.php',
			'./src/StaticMap/src/Image.php'
		];
	
		foreach ($requiredClasses as $class) {
			require_once($class);
		}
	
		// Map data and default values
		$centerMap = new \Wavelog\StaticMapImage\LatLng(51.5074, 0.1278); // TODO: Set to user's QTH
		$zoom = 2;
		$width = 1045;
		$height = 715;
		$tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
	
		// Create the map
		$map = new \Wavelog\StaticMapImage\OpenStreetMap($centerMap, $zoom, $width, $height, $tileLayer); // TODO: Also allow dark map
	
		if (!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}

		$markerPositions = [];
		foreach ($qsos->result('array') as $qso) {
			if (!empty($qso['COL_GRIDSQUARE'])  || !empty($qso['COL_VUCC_GRIDS'])) {
				$latlng = $this->qra->qra2latlong($qso['COL_GRIDSQUARE']);
				$lat = $latlng[0];
				$lng = $latlng[1];
				$markerPositions[] = new \Wavelog\StaticMapImage\LatLng($lat, $lng);
			} else {
				continue;
			}
		}
	
		$markers = new \Wavelog\StaticMapImage\Markers('src/StaticMap/src/resources/circle-dot-red.png'); // TODO: Use user defined markers
		$markers->resizeMarker(12, 12);
		$markers->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);
	
		foreach ($markerPositions as $position) {
			$markers->addMarker($position);
		}
	
		$map->addMarkers($markers);
	
		// Generate the image
		$filename = 'static_map_' . time() . '.png';
		$full_path = APPPATH . 'cache/' . $filename;

		if ($map->getImage()->savePNG($full_path)) {
			return $filename;
		} else {
			return false;
		}
	}
}