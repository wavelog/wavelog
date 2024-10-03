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
		require_once('./src/StaticMap/src/OpenStreetMap.php');
		require_once('./src/StaticMap/src/LatLng.php');
		require_once('./src/StaticMap/src/TileLayer.php');
		require_once('./src/StaticMap/src/Markers.php');
		require_once('./src/StaticMap/src/MapData.php');
		require_once('./src/StaticMap/src/XY.php');
		require_once('./src/StaticMap/src/Image.php');
	
		// Erforderliche Parameter für den Konstruktor
		$centerMap = new \DantSu\OpenStreetMapStaticAPI\LatLng(51.5074, 0.1278); // London als Zentrum
		$zoom = 2; // Zoom Level
		$width = 1024; // Breite des Bildes
		$height = 768; // Höhe des Bildes
		$tileLayer = \DantSu\OpenStreetMapStaticAPI\TileLayer::defaultTileLayer(); // Optional, Standard-OSM-Server
	
		// Erstellen der Karte mit den richtigen Parametern
		$map = new \DantSu\OpenStreetMapStaticAPI\OpenStreetMap($centerMap, $zoom, $width, $height, $tileLayer);
	
		// Marker hinzufügen
		$map->addMarkers(
			(new \DantSu\OpenStreetMapStaticAPI\Markers('src/StaticMap/src/resources/marker.png'))
				->setAnchor(\DantSu\OpenStreetMapStaticAPI\Markers::ANCHOR_CENTER, \DantSu\OpenStreetMapStaticAPI\Markers::ANCHOR_BOTTOM)
				->addMarker(new \DantSu\OpenStreetMapStaticAPI\LatLng(51.5074, 0.1278)) // London
				->addMarker(new \DantSu\OpenStreetMapStaticAPI\LatLng(40.7128, -74.0060)) // New York
				->addMarker(new \DantSu\OpenStreetMapStaticAPI\LatLng(35.6895, 139.6917)) // Tokyo
				->addMarker(new \DantSu\OpenStreetMapStaticAPI\LatLng(37.7749, -122.4194)) // San Francisco
				->addMarker(new \DantSu\OpenStreetMapStaticAPI\LatLng(48.8566, 2.3522))  // Paris
		);

	
		// Generiere das Bild
		$image = $map->getImage();
	
		// Speichere das Bild in einer Datei
		$filename = 'static_map_' . time() . '.png'; // Einzigartiger Dateiname
		$image->saveJPG('./assets/maps/' . $filename, 100);
	
		// Gib den Dateinamen zurück
		return $filename;
	}
}