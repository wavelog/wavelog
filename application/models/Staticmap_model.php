<?php

class Staticmap_model extends CI_Model {

    /**
     * Render a static map image
     * 
     * @param $qsos  				Amount of QSOs to render
     * @param $uid  				User ID
     * @param $centerMap  			Center of the map
     * @param $station_coordinates  Coordinates of the station
     * @param $filepath  			Path to save the image to
     * @param $continent  			Continent to display
     * @param $thememode  			Theme mode ('light' or 'dark')
     * @param $hide_home  			Whether to hide the home station
     * 
     * @return bool  True if the image was rendered successfully, false if not
     */

    function render_static_map($qsos, $uid, $centerMap, $station_coordinates, $filepath, $continent = null, $thememode = null, $hide_home = false, $night_shadow = false, $pathlines = false) {

        $this->load->model('Stations');
        $this->load->model('user_model');
        $this->load->model('stationsetup_model');

        $this->load->library('Qra');
        $this->load->library('genfunctions');

        $requiredClasses = [
            './src/StaticMap/src/OpenStreetMap.php',
            './src/StaticMap/src/LatLng.php',
            './src/StaticMap/src/TileLayer.php',
            './src/StaticMap/src/Markers.php',
            './src/StaticMap/src/MapData.php',
            './src/StaticMap/src/XY.php',
            './src/StaticMap/src/Image.php',
            './src/StaticMap/src/Utils/Terminator.php',
            './src/StaticMap/src/Line.php',
            './src/StaticMap/src/Polygon.php'
        ];

        foreach ($requiredClasses as $class) {
            require_once($class);
        }

        // Set the tile layer
        if ($thememode != null) {
            $attribution = $this->optionslib->get_option('option_map_tile_server_copyright') ?? 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>';
            if ($thememode == 'light') {
                $server_url = $this->optionslib->get_option('option_map_tile_server') ?? '';
                if ($server_url == '') {
                    $server_url = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    $this->optionslib->update('map_tile_server', $server_url, 'yes');
                }
                $tileLayer = new \Wavelog\StaticMapImage\TileLayer($server_url, $attribution, $thememode);
            } elseif ($thememode == 'dark') {
                $server_url = $this->optionslib->get_option('option_map_tile_server_dark') ?? '';
                if ($server_url == '') {
                    $server_url = 'https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png';
                    $this->optionslib->update('map_tile_server_dark', $server_url, 'yes');
                }
                $tileLayer = new \Wavelog\StaticMapImage\TileLayer($server_url, $attribution, $thememode);
            } else {
                $tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
            }
        } else {
            $tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
        }

        // Map data and default values
        $centerMapLat = 25; // Needs to be a fix value as we can't wrap Latitude. Latitude of 25 is a good value to display all necessary places from north to south
        $centerMapLng = $centerMap[1];
        $centerMap = $centerMapLat . $centerMapLng; // used for cached tiles
        $zoom = 2;
        $width = 1024;
        $height = 768;
        $marker_size = 9;
        $fontSize = 12;
        $fontPosX = 758;
        $fontPosY = 178;
        $contFontPosX = 30;
        $contFontPosY = 20;
        $watermarkPosX = DantSu\PHPImageEditor\Image::ALIGN_RIGHT;
        $watermarkPosY = DantSu\PHPImageEditor\Image::ALIGN_BOTTOM;
        $continentEnabled = false;

        // Continent Option
        if ($continent != null) {
            if ($continent == 'AF') {
                $continentEnabled = true;
                $continentText = 'Africa';
                $centerMapLat = 2;
                $centerMapLng = 20;
                $zoom = 4;
                $height = 950;
                $fontPosX = 940;
                $watermarkPosY = 50;
            } elseif ($continent == 'AS') {
                $continentEnabled = true;
                $continentText = 'Asia';
                $centerMapLat = 45;
                $centerMapLng = 100;
                $zoom = 3;
                $contFontPosX = 24;
            } elseif ($continent == 'EU') {
                $continentEnabled = true;
                $continentText = 'Europe';
                $centerMapLat = 57;
                $centerMapLng = 15;
                $zoom = 4;
                $contFontPosX = 34;
            } elseif ($continent == 'NA') {
                $continentEnabled = true;
                $continentText = 'North America';
                $centerMapLat = 55;
                $centerMapLng = -100;
                $zoom = 3;
                $contFontPosX = 60;
            } elseif ($continent == 'OC') {
                $continentEnabled = true;
                $continentText = 'Oceania';
                $centerMapLat = -25;
                $centerMapLng = 140;
                $zoom = 4;
                $contFontPosX = 38;
            } elseif ($continent == 'SA') {
                $continentEnabled = true;
                $continentText = 'South America';
                $centerMapLat = -26;
                $centerMapLng = -60;
                $zoom = 4;
                $height = 990;
                $width = 700;
                $fontPosX = 980;
                $contFontPosX = 60;
                $watermarkPosY = 80;
                $watermarkPosX = -180;
            } elseif ($continent == 'AN') {
                $continentEnabled = true;
                $continentText = 'Antarctica';
                $centerMapLat = -73;
                $centerMapLng = 0;
                $zoom = 2;
                $width = 1024;
                $height = 400;
                $fontPosX = 390;
                $fontPosY = 178;
                $watermarkPosY = -180;
                $contFontPosX = 45;
            } else {
                // we don't want to change the default values in this case
            }
        }

        // Create the map
        $map = new \Wavelog\StaticMapImage\OpenStreetMap(new \Wavelog\StaticMapImage\LatLng($centerMapLat, $centerMapLng), $zoom, $width, $height, $tileLayer);

        // Get all QSOs with gridsquares and set markers for confirmed and unconfirmed QSOs
        // We also draw the pathlines here
        $markerQsos = [];
        $markerQsosConfirmed = [];
        $paths = [];
        $paths_cnfd = [];
        $user_default_confirmation = $this->visitor_model->get_user_default_confirmation($uid);
        foreach ($qsos->result('array') as $qso) {
            if (!empty($qso['COL_GRIDSQUARE'])) {
                $latlng = $this->qra->qra2latlong($qso['COL_GRIDSQUARE']);
                $lat = $latlng[0];
                $lng = $latlng[1];
            } else if (!empty($qso['COL_VUCC_GRIDS'])) {
                $latlng = $this->qra->qra2latlong($qso['COL_VUCC_GRIDS']);
                $lat = $latlng[0];
                $lng = $latlng[1];
            } else {
                continue;
            }

            // Check for continents
            if ($continentEnabled) {
                if ($qso['COL_CONT'] != $continent) {
                    continue;
                }
            }

            if ($this->visitor_model->qso_is_confirmed($qso, $user_default_confirmation) == true) {
                if ($pathlines) {
                    $station_grid = $this->stations->profile($qso['station_id'])->row()->station_gridsquare;
                    $station_latlng = $this->qra->qra2latlong($station_grid);
                    $paths_cnfd[] = $this->draw_pathline($station_latlng, $latlng, $continentEnabled, '04A90227'); // Green
                }
                $markerQsosConfirmed[] = new \Wavelog\StaticMapImage\LatLng($lat, $lng);
                continue;
            } else {
                if ($pathlines) {
                    $station_grid = $this->stations->profile($qso['station_id'])->row()->station_gridsquare;
                    $station_latlng = $this->qra->qra2latlong($station_grid);
                    $paths[] = $this->draw_pathline($station_latlng, $latlng, $continentEnabled, 'ff000027'); // Red
                }
                $markerQsos[] = new \Wavelog\StaticMapImage\LatLng($lat, $lng);
                continue;
            }
        }

        // Get user defined markers
        $options_object = $this->user_options_model->get_options('map_custom', null, $uid)->result();
        $user_icondata = array();
        if (count($options_object) > 0) {
            foreach ($options_object as $row) {
                if ($row->option_name == 'icon') {
                    $option_value = json_decode($row->option_value, true);
                    foreach ($option_value as $ktype => $vtype) {
                        if ($this->input->post('user_map_' . $row->option_key . '_icon')) {
                            $user_icondata['user_map_' . $row->option_key . '_' . $ktype] = $this->input->post('user_map_' . $row->option_key . '_' . $ktype, true);
                        } else {
                            $user_icondata['user_map_' . $row->option_key . '_' . $ktype] = $vtype;
                        }
                    }
                } else {
                    $user_icondata['user_map_' . $row->option_name . '_' . $row->option_key] = $row->option_value;
                }
            }
        } else {
            $user_icondata['user_map_qso_icon'] = "fas fa-dot-circle";
            $user_icondata['user_map_qso_color'] = "#FF0000";
            $user_icondata['user_map_station_icon'] = "fas fa-home";
            $user_icondata['user_map_station_color'] = "#0000FF";
            $user_icondata['user_map_qsoconfirm_icon'] = "fas fa-check-circle";
            $user_icondata['user_map_qsoconfirm_color'] = "#00AA00";
            $user_icondata['user_map_gridsquare_show'] = "0";
        }

        // Map all available icons to the unicode
        $unicode_map = array(
            '0' => 'f192', // dot-circle is default
            'fas fa-home' => 'f015',
            'fas fa-broadcast-tower' => 'f519',
            'fas fa-user' => 'f007',
            'fas fa-dot-circle' => 'f192',
            'fas fa-check-circle' => 'f058',
        );
        
        // Home Icon
        if (!$home_icon = $this->genfunctions->fas2png($unicode_map[$user_icondata['user_map_station_icon']], substr($user_icondata['user_map_station_color'], 1))) {
            log_message('error', "Failed to generate map icon. Exiting...");
            return false;
        }
        // QSO Icon
        if (!$qso_icon = $this->genfunctions->fas2png($unicode_map[$user_icondata['user_map_qso_icon']], substr($user_icondata['user_map_qso_color'], 1))) {
            log_message('error', "Failed to generate map icon. Exiting...");
            return false;
        }
        // QSO Confirm Icon
        if (!$qso_cfnm_icon = $this->genfunctions->fas2png($unicode_map[$user_icondata['user_map_qsoconfirm_icon']], substr($user_icondata['user_map_qsoconfirm_color'], 1))) {
            log_message('error', "Failed to generate map icon. Exiting...");
            return false;
        }

        // Set the markers for the station
        if (!$hide_home) {
            $wrapping = !$continentEnabled;
            $markersStation = new \Wavelog\StaticMapImage\Markers($home_icon, $wrapping);
            $markersStation->resizeMarker($marker_size, $marker_size);
            $markersStation->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);
            foreach ($station_coordinates as $station) {
                $markersStation->addMarker(new \Wavelog\StaticMapImage\LatLng($station[0], $station[1]));
            }
            $map->addMarkers($markersStation);
        }

        // Set the markers for unconfirmed QSOs
        $markers = new \Wavelog\StaticMapImage\Markers($qso_icon, true);
        $markers->resizeMarker($marker_size, $marker_size);
        $markers->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);

        foreach ($markerQsos as $position) {
            $markers->addMarker($position);
        }
        $map->addMarkers($markers);

        // Set the markers for confirmed QSOs
        $markersConfirmed = new \Wavelog\StaticMapImage\Markers($qso_cfnm_icon, true);
        $markersConfirmed->resizeMarker($marker_size, $marker_size);
        $markersConfirmed->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);

        foreach ($markerQsosConfirmed as $position) {
            $markersConfirmed->addMarker($position);
        }
        $map->addMarkers($markersConfirmed);

        $image = $map->getImage($centerMap);

        // Add day/night overlay
        if ($night_shadow) {
            $terminator = new Terminator();
            $terminatorLine = $terminator->getTerminatorCoordinates();

            $lcolor = '000000'; // 000000 = black but we set lweight to 0, so the line won't be drawn anyway
            $lweight = 0;
            $pcolor = '000000AA'; // 000000 = black, AA = 66% opacity as hex

            $polygon = new Wavelog\StaticMapImage\Polygon($lcolor, $lweight, $pcolor);

            foreach ($terminatorLine as $coordinate) {
                $polygon->addPoint(new Wavelog\StaticMapImage\LatLng($coordinate[0], $coordinate[1]));
            }

            $polygon->draw($image, $map->getMapData());
        }

        if ($pathlines) {
            foreach ($paths as $path) {
                $path->draw($image, $map->getMapData());
            }
            foreach ($paths_cnfd as $path) {
                $path->draw($image, $map->getMapData());
            }
        }

        // Add Wavelog watermark
        $watermark = DantSu\PHPImageEditor\Image::fromPath('src/StaticMap/src/resources/watermark_static_map.png');
        $image->pasteOn($watermark, $watermarkPosX, $watermarkPosY);

        // Add "Created with Wavelog" text
        $user = $this->user_model->get_by_id($uid)->row();
        $custom_date_format = $user->user_date_format;
        $dateTime = date($custom_date_format . ' - H:i');
        $text = "Created with Wavelog on " . $dateTime . " UTC";
        $fontPath = 'src/StaticMap/src/resources/font.ttf';
        $color = 'ff0000'; // Red
        $image->writeText($text, $fontPath, $fontSize, $color, $fontPosY, $fontPosX);

        // Add continent text
        if ($continentEnabled) {
            $fontPath = 'src/StaticMap/src/resources/font.ttf';
            $color = 'ff0000'; // Red
            $image->writeText($continentText, $fontPath, $fontSize, $color, $contFontPosX, $contFontPosY);
        }

        if ($image->savePNG($filepath)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create a geodesic pathline between two points
     * 
     * @param $start  		Start point
     * @param $end  		End point
     * @param $continent  	If continent view is enabled (affects wrapping)
     * @param $color  		Color of the pathline
     * @param $weight  		Weight of the pathline (px)
     * 
     * @return Line         An instance of Wavelog\StaticMapImage\Line
     */

    function draw_pathline($start, $end, $continent, $color = 'ffffff', $weight = 1) {
        // Start in Berlin 
        $start = new \Wavelog\StaticMapImage\LatLng($start[0], $start[1]);

        // End in honkong
        $end = new \Wavelog\StaticMapImage\LatLng($end[0], $end[1]);

        $path = new \Wavelog\StaticMapImage\Line($color, $weight, !$continent);
        $points = $path->geodesicPoints($start, $end);

        foreach ($points as $point) {
            $path->addPoint($point);
        }

        return $path;
    }

    /**
     * Remove outdated static map images from the cache directory
     * Based on station_id because is handled and used during qso creation
     * 
     * @param  int $station_id  The station ID to remove the static map image for
     * @param  int $logbook_id  The logbook ID to remove the static map image for
     * 
     * @return bool  True if the image was removed successfully, false if not
     */

    function remove_static_map_image($station_id = null, $logbook_id = null) {

        if ($station_id == null && $logbook_id == null) {
            log_message('error', "Can't remove static map image cache. Neither a station ID nor a logbook ID was provided. Exiting...");
            return false;
        }
        $cachepath = $this->config->item('cache_path') == '' ? APPPATH . 'cache/' : $this->config->item('cache_path');
        $cacheDir = $cachepath . "staticmap_images/";

        if (!is_dir($cacheDir)) {
            log_message('debug', "Cache directory '" . $cacheDir . "' does not exist. Therefore no static map images to remove...");
            return true;
        }

        if ($station_id != null) {
            if (!is_numeric($station_id) || $station_id == '' || $station_id == null) {
                log_message('error', "Station ID is not valid. Exiting...");
                return false;
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

                $prefix = crc32('staticmap_' . $slug);
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
        if ($logbook_id != null) {

            if (!is_numeric($logbook_id) || $logbook_id == '' || $logbook_id == null) {
                log_message('error', "Logbook ID is not valid. Exiting...");
                return false;
            }

            $slug = $this->stationsetup_model->get_slug($logbook_id);
            if ($slug == false) {
                log_message('debug', "No slug found for logbook ID " . $logbook_id . ". Exiting...");
                return false;
            }

            $prefix = crc32('staticmap_' . $slug);
            $files = glob($cacheDir . $prefix . '*');

            if (!empty($files)) {
                foreach ($files as $file) {
                    log_message('debug', "Found a outdated static map image: " . basename($file) . ". Deleting...");
                    unlink($file);
                }
            } else {
                log_message('info', "Found no files with the prefix '" . $prefix . "' in the cache directory.");
            }

            return true; // Success
        }
    }

    /**
     * Validate a cached static map image
     * 
     * @param $file  		File to validate (realpath)
     * @param $cacheDir  	Cache directory itself
     * @param $maxAge  		Maximum age of the file in seconds
     * 
     * @return bool  True if the file is valid, false if not
     */

    function validate_cached_image($file, $cacheDir, $maxAge, $slug) {

        $realPath = realpath($file);
        $filename = basename($file);

        // get the slug
        $parts = explode('_', $filename);
        $validation_hash = $parts[0];

        if (!file_exists($file)) {
            log_message('debug', "Cached static map image file does not exist. Creating a new one...");
            return false;
        }

        if ($validation_hash !== (string) crc32('staticmap_' . $slug)) {
            log_message('error', "Static_map: Invalid validation hash. Deleting the file and exiting...");
            if (!unlink($file)) {
                log_message('error', "Failed to delete invalid cached static map image file: " . $file);
            }
            return false;
        }

        if ($realPath === false || strpos($realPath, $cacheDir) !== 0) {
            log_message('error', "Invalid Filepath. Possible traversal attack detected. Deleting the file and exiting...");
            if (file_exists($file)) {
                if (!unlink($file)) {
                    log_message('error', "Failed to delete invalid cached static map image file: " . $file);
                }
            }
            return false;
        }

        if (filesize($file) < 1024) { // 1 kB
            log_message('error', "Cached static map image file is unusually small, possible corruption detected. Deleting the file and exiting...");
            if (!unlink($file)) {
                log_message('error', "Failed to delete invalid cached static map image file: " . $file);
            }
            return false;
        }

        if (mime_content_type($file) !== 'image/png') {
            log_message('error', "Cached static map image file is no PNG. Deleting the file and exiting...");
            if (!unlink($file)) {
                log_message('error', "Failed to delete invalid cached static map image file: " . $file);
            }
            return false;
        }

        if (time() - filemtime($file) > $maxAge) {
            log_message('debug', "Cached static map image has expired. Deleting old cache file...");
            if (!unlink($file)) {
                log_message('error', "Failed to delete invalid cached static map image file: " . $file);
            }
            return false;
        }

        return true;
    }
}
