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

	function render_static_map($qsos, $centerMap, $filename, $cacheDir) {

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
		$centerMapLat = 0; // Needs to be 0 as we can't wrap Latitude
		$centerMapLng = $centerMap[1];
		$centerMap = $centerMapLat . $centerMapLng; // used for cached tiles
		$zoom = 2;
		$width = 1024;
		$height = 768;
		$tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
	
		// Create the map
		$map = new \Wavelog\StaticMapImage\OpenStreetMap(new \Wavelog\StaticMapImage\LatLng($centerMapLat, $centerMapLng), $zoom, $width, $height, $tileLayer); // TODO: Also allow dark map
	
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
		$markers->resizeMarker(10, 10);
		$markers->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);
	
		foreach ($markerPositions as $position) {
			$markers->addMarker($position);
		}
	
		$map->addMarkers($markers);
	
		// Generate the image
		$full_path = $cacheDir . $filename;

		if ($map->getImage($centerMap)->savePNG($full_path)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove outdated static map images from the cache directory
	 * 
	 * @param $station_id  The station ID to remove the static map image for
	 */

	function remove_static_map_image($station_id) {

		$cachepath = $this->config->item('cache_path') == '' ? APPPATH . 'cache/' : $this->config->item('cache_path');
		$cacheDir = $cachepath . "static_map_images/";

		if (!is_dir($cacheDir)) {
			log_message('debug', "Cache directory '" . $cacheDir . "' does not exist. Therefore no static map images to remove...");
			return true;
		}

		if (!is_numeric($station_id) || $station_id == '' || $station_id == null) {
			log_message('error', "Station ID is not valid. Exiting...");
			return false;
		}
		if (!$this->load->is_loaded('stationsetup_model')) {
			$this->load->model('stationsetup_model');
		}

		$linked_logbooks = $this->stationsetup_model->get_container_relations($station_id, true); // true means we do a reverse search
		
		if (!$linked_logbooks) {
			log_message('error', "No linked logbooks found for station ID " . $station_id . ". Exiting...");
			return false;
		}
		foreach ($linked_logbooks as $logbook_id) {
			$slug = $this->stationsetup_model->get_slug($logbook_id);
			if ($slug == false) {
				log_message('debug', "No slug found for logbook ID " . $logbook_id . ". Continue...");
				continue;
			}

			$prefix = 'static_map_' . $slug;
			$files = glob($cacheDir . $prefix . '*');

			if (!empty($files)) {
				foreach ($files as $file) {
					log_message('debug', "Found a outdated static map image: " . basename($file) . ". Deleting...");
					unlink($file);
				}
			} else {
				log_message('info', "Found no files with the prefix '" . $prefix . "' in the cache directory.");
			}
		}

		return true; // Success
	}
}