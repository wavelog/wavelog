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

	public function render_static_map($slug, $qsocount) {
		// Benötigte Klassen einmal laden
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
	
		// Kartendaten und Standardwerte
		$centerMap = new \Wavelog\StaticMapImage\LatLng(51.5074, 0.1278); // London als Zentrum
		$zoom = 2;
		$width = 1024;
		$height = 768;
		$tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
	
		// Karte erstellen
		$map = new \Wavelog\StaticMapImage\OpenStreetMap($centerMap, $zoom, $width, $height, $tileLayer);
	
		// Marker hinzufügen
		$markerPositions = [
			new \Wavelog\StaticMapImage\LatLng(51.5074, 0.1278),  // London
			new \Wavelog\StaticMapImage\LatLng(40.7128, -74.0060), // New York
			new \Wavelog\StaticMapImage\LatLng(35.6895, 139.6917), // Tokyo
			new \Wavelog\StaticMapImage\LatLng(37.7749, -122.4194), // San Francisco
			new \Wavelog\StaticMapImage\LatLng(48.8566, 2.3522)    // Paris
		];
	
		$markers = new \Wavelog\StaticMapImage\Markers('src/StaticMap/src/resources/circle-dot-red.png');
		$markers->resizeMarker(12, 12);
		$markers->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);
	
		foreach ($markerPositions as $position) {
			$markers->addMarker($position);
		}
	
		$map->addMarkers($markers);
	
		// Bild generieren und speichern
		$filename = 'static_map_' . time() . '.png';
		$map->getImage()->saveJPG('./assets/maps/' . $filename, 100);
	
		return $filename;
	}
}