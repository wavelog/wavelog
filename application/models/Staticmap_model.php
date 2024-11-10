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
     * @param $night_shadow  		Whether to display the night shadow
     * @param $pathlines  			Whether to display pathlines
     * @param $cqzones  			Whether to display CQ zones
     * @param $ituzones  			Whether to display ITU zones
     * 
     * @return bool  True if the image was rendered successfully, false if not
     */

    function render_static_map($qsos, $uid, $centerMap, $station_coordinates, $filepath, $continent = null, $thememode = null, $hide_home = false, $night_shadow = false, $pathlines = false, $cqzones = false, $ituzones = false) {

        //===============================================================================================================================
        //=============================================== PREPARE AND LOAD DEPENDENCIES =================================================
        //===============================================================================================================================

        $this->load->model('Stations');
        $this->load->model('user_model');
        $this->load->model('stationsetup_model');

        if (!$this->load->is_loaded('Qra')) {
            $this->load->library('Qra');
        }
        if (!$this->load->is_loaded('genfunctions')) {
            $this->load->library('genfunctions');
        }

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

        $fontPath = 'src/StaticMap/src/resources/font.ttf';

        //===============================================================================================================================
        //===================================================== CONFIGURE GRAPHICS ======================================================
        //===============================================================================================================================

        // Map data and default values
        $centerMapLat = 25; // Needs to be a fix value as we can't wrap Latitude. Latitude of 25 is a good value to display all necessary places from north to south
        $centerMapLng = $centerMap[1];
        $centerMap = $centerMapLat . $centerMapLng; // used for cached tiles
        $zoom = 3;
        $width = 2048;
        $height = round(($width * 3.3) / 4);
        $line_pxsize = 1;
        $fontSize = 20;
        $fontPosX = $height - 20;
        $fontPosY = 300;
        $contFontPosX = $width - ($width - 50);
        $contFontPosY = $height - ($height - 30);
        $watermark_size_mutiplier = 1.5;
        $watermarkPosX = DantSu\PHPImageEditor\Image::ALIGN_CENTER;
        $watermarkPosY = DantSu\PHPImageEditor\Image::ALIGN_MIDDLE;
        $continentEnabled = false;
        $cqz_color = '195619'; // Green
        $ituz_color = '2c3e5f'; // Blue

        // Continent Option
        if ($continent != 'nC' || $continent != null || $continent != '') {
            if ($continent == 'AF') {
                $continentEnabled = true;
                $continentText = 'Africa';
                $centerMapLat = 2;
                $centerMapLng = 20;
                $zoom = 5;
                $height = round(($width * 4) / 4);
                $fontPosX = $height - 20;
            } elseif ($continent == 'AS') {
                $continentEnabled = true;
                $continentText = 'Asia';
                $centerMapLat = 45;
                $centerMapLng = 100;
                $zoom = 4;
                $contFontPosX = $width - ($width - 50);
            } elseif ($continent == 'EU') {
                $continentEnabled = true;
                $continentText = 'Europe';
                $centerMapLat = 65;
                $centerMapLng = 15;
                $height = round(($width * 5) / 4);
                $zoom = 5;
                $fontPosX = $height - 20;
                $contFontPosX = $width - ($width - 50);
            } elseif ($continent == 'NA') {
                $continentEnabled = true;
                $continentText = 'North America';
                $centerMapLat = 55;
                $centerMapLng = -100;
                $zoom = 4;
                $contFontPosX = $width - ($width - 110);
            } elseif ($continent == 'OC') {
                $continentEnabled = true;
                $continentText = 'Oceania';
                $centerMapLat = -25;
                $centerMapLng = 140;
                $zoom = 5;
                $contFontPosX = $width - ($width - 70);
            } elseif ($continent == 'SA') {
                $continentEnabled = true;
                $continentText = 'South America';
                $centerMapLat = -26;
                $centerMapLng = -60;
                $zoom = 5;
                $width = 1570;
                $height = round(($width * 5) / 4);
                $fontPosX = $height - 20;
                $contFontPosX = $width - ($width - 110);
            } elseif ($continent == 'AN') {
                $continentEnabled = true;
                $continentText = 'Antarctica';
                $centerMapLat = -73;
                $centerMapLng = 0;
                $zoom = 3;
                $watermark_size_mutiplier = 1;
                $height = round(($width * 1.5) / 4);
                $fontPosX = $height - 20;
                $contFontPosX = $width - ($width - 90);
            } else {
                // we don't want to change the default values in this case
            }
        }

        if ($zoom == 3) {
            $marker_size = 18;
        } elseif ($zoom == 4) {
            $marker_size = 24;
        } elseif ($zoom == 5) {
            $marker_size = 28;
        } else {
            $marker_size = 20;
        }

        //===============================================================================================================================
        //================================================ CREATE AN INSTANCE OF THE MAP ================================================
        //===============================================================================================================================

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
                    $server_url = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
                    $this->optionslib->update('map_tile_server_dark', $server_url, 'yes');
                }
                $tileLayer = new \Wavelog\StaticMapImage\TileLayer($server_url, $attribution, $thememode);
            } else {
                $tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
            }
        } else {
            $tileLayer = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
        }

        // Create the map
        $map = new \Wavelog\StaticMapImage\OpenStreetMap(new \Wavelog\StaticMapImage\LatLng($centerMapLat, $centerMapLng), $zoom, $width, $height, $tileLayer);

        //===============================================================================================================================
        //====================================================== RENDER THE ICONS =======================================================
        //===============================================================================================================================

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
            // Default values
            $user_icondata['user_map_qso_icon'] = "fas fa-dot-circle";
            $user_icondata['user_map_qso_color'] = "#FF0000";
            $user_icondata['user_map_station_icon'] = "fas fa-home";
            $user_icondata['user_map_station_color'] = "#0000FF";
            $user_icondata['user_map_qsoconfirm_icon'] = "fas fa-check-circle";
            $user_icondata['user_map_qsoconfirm_color'] = "#00AA00";
            $user_icondata['user_map_gridsquare_show'] = "0";
        }

        // free memory
        unset($options_object);

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

        //===============================================================================================================================
        //========================================= PROCESS THE QSOs AND PREPARE THE PATHLINES ==========================================
        //===============================================================================================================================

        // Get all QSOs with gridsquares and set markers for confirmed and unconfirmed QSOs
        // We also prepare the PATHLINES here ($paths and $paths_cnfd)

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
                    $paths_cnfd[] = $this->draw_pathline($station_latlng, $latlng, $continentEnabled, '04A902', $line_pxsize); // Green
                }
                $markerQsosConfirmed[] = new \Wavelog\StaticMapImage\LatLng($lat, $lng);
                continue;
            } else {
                if ($pathlines) {
                    $station_grid = $this->stations->profile($qso['station_id'])->row()->station_gridsquare;
                    $station_latlng = $this->qra->qra2latlong($station_grid);
                    $paths[] = $this->draw_pathline($station_latlng, $latlng, $continentEnabled, 'ff0000', $line_pxsize); // Red
                }
                $markerQsos[] = new \Wavelog\StaticMapImage\LatLng($lat, $lng);
                continue;
            }
        }

        //===============================================================================================================================
        //==================================================== PREPARE THE MARKERS ======================================================
        //===============================================================================================================================

        // Set the markers for the station
        if (!$hide_home) {
            $wrapping = !$continentEnabled;
            $markersStation = new \Wavelog\StaticMapImage\Markers($home_icon, $wrapping);
            $markersStation->resizeMarker($marker_size, $marker_size);
            $markersStation->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);
            foreach ($station_coordinates as $station) {
                $markersStation->addMarker(new \Wavelog\StaticMapImage\LatLng($station[0], $station[1]));
            }
        }

        // Set the markers for unconfirmed QSOs
        $markers = new \Wavelog\StaticMapImage\Markers($qso_icon, true);
        $markers->resizeMarker($marker_size, $marker_size);
        $markers->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);

        foreach ($markerQsos as $position) {
            $markers->addMarker($position);
        }

        // Set the markers for confirmed QSOs
        $markersConfirmed = new \Wavelog\StaticMapImage\Markers($qso_cfnm_icon, true);
        $markersConfirmed->resizeMarker($marker_size, $marker_size);
        $markersConfirmed->setAnchor(\Wavelog\StaticMapImage\Markers::ANCHOR_CENTER, \Wavelog\StaticMapImage\Markers::ANCHOR_BOTTOM);

        foreach ($markerQsosConfirmed as $position) {
            $markersConfirmed->addMarker($position);
        }

        //===============================================================================================================================
        //================================================== PREPARE THE NIGHTSHADOW ====================================================
        //===============================================================================================================================

        if ($night_shadow) {
            $terminator = new Terminator();
            $terminatorLine = $terminator->getTerminatorCoordinates();

            $lcolor = '000000'; // 000000 = black but we set lweight to 0, so the line won't be drawn anyway
            $lweight = 0;
            $pcolor = '000000AA'; // 000000 = black, AA = 66% opacity as hex

            $night_shadow_polygon = new Wavelog\StaticMapImage\Polygon($lcolor, $lweight, $pcolor);

            foreach ($terminatorLine as $coordinate) {
                $night_shadow_polygon->addPoint(new Wavelog\StaticMapImage\LatLng($coordinate[0], $coordinate[1]));
            }

            // free memory
            unset($terminator);
        }


        //===============================================================================================================================
        //============================================ PREPARE THE CQ ZONES OVERLAY =====================================================
        //===============================================================================================================================

        /**
         * Due to the long rendering times we cache the CQ Zones overlay and just paste it on the map
         */

        if ($cqzones) {

            $cqz_cachedir = dirname($filepath) . '/cqz_overlays';
            if (!is_dir($cqz_cachedir)) {
                mkdir($cqz_cachedir, 0777, true);
            }
            $cqz_filename = crc32($centerMap . $continent . $zoom . $width . $height . $cqz_color) . '.png';

            if (!file_exists($cqz_cachedir . '/' . $cqz_filename)) {

                log_message('info', "No cached CQ Zone Overlay found. Creating new CQ Zones overlay...");

                $geojsonFile = 'assets/json/geojson/cqzones.geojson';
                $geojsonData = file_get_contents($geojsonFile);

                $data = json_decode($geojsonData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message("error", "Failed to read geojson data for cqzones" . json_last_error_msg());
                }

                $lcolor = $cqz_color;
                $lweight = 1;
                $pcolor = $cqz_color . 'FF'; // FF = 100% opacity as hex

                if (isset($data['features'])) {
                    $cqzones_polygon_array = [];
                    foreach ($data['features'] as $feature) {
                        $one_cqzpolygon = new Wavelog\StaticMapImage\Polygon($lcolor, $lweight, $pcolor, !$continentEnabled);
                        $coordinates = $feature['geometry']['coordinates'];

                        foreach ($coordinates as $zone) {
                            foreach ($zone as $point) {
                                $one_cqzpolygon->addPoint(new Wavelog\StaticMapImage\LatLng($point[1], $point[0]));
                            }
                        }

                        $zone_number = $feature['properties']['cq_zone_number'];
                        $zone_name_loc = $feature['properties']['cq_zone_name_loc'];
                        $cqzones_polygon_array[$zone_number]['polygon'] = $one_cqzpolygon;
                        $cqzones_polygon_array[$zone_number]['number'] = $zone_number;
                        $cqzones_polygon_array[$zone_number]['name_loc'] = $zone_name_loc;
                    }
                } else {
                    log_message("error", "Failed to read geojson data for cqzones. No features found.");
                }

                $cqz_tl = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
                $cqz_tl = $cqz_tl->setOpacity(0);
                $cqz_map = new \Wavelog\StaticMapImage\OpenStreetMap(new \Wavelog\StaticMapImage\LatLng($centerMapLat, $centerMapLng), $zoom, $width, $height, $cqz_tl);
                $cqz_image = $cqz_map->getImage($centerMap);

                foreach ($cqzones_polygon_array as $cqzones_polygon) {
                    $polygon = $cqzones_polygon['polygon'];
                    $polygon->draw($cqz_image, $cqz_map->getMapData());
        
                    $zone_number = $cqzones_polygon['number'];
                    $cqz_fontsize = 33;
                    $position = new \Wavelog\StaticMapImage\LatLng($cqzones_polygon['name_loc'][0], $cqzones_polygon['name_loc'][1]);
                    $positionXY = $cqz_map->getMapData()->convertLatLngToPxPosition($position);
                    $cqz_image->writeText($zone_number, $fontPath, $cqz_fontsize, $lcolor, $positionXY->getX(), $positionXY->getY(), $cqz_image::ALIGN_CENTER, $cqz_image::ALIGN_MIDDLE, 0, 0, !$continentEnabled);
                }
        
                $cqz_image->savePNG($cqz_cachedir . '/' . $cqz_filename);

                // free memory
                unset($cqzones_polygon_array);

            } else {
                log_message('info', "Found cached CQ Zone Overlay. Using cached overlay...");
            }
        }


        //===============================================================================================================================
        //============================================ PREPARE THE ITU ZONES OVERLAY ====================================================
        //===============================================================================================================================

        if ($ituzones) {

            $ituz_cachedir = dirname($filepath) . '/ituz_overlays';
            if (!is_dir($ituz_cachedir)) {
                mkdir($ituz_cachedir, 0777, true);
            }
            $ituz_filename = crc32($centerMap . $continent . $zoom . $width . $height . $ituz_color) . '.png';

            if (!file_exists($ituz_cachedir . '/' . $ituz_filename)) {

                log_message('info', "No cached ITU Zone Overlay found. Creating new ITU Zones overlay...");

                $geojsonFile = 'assets/json/geojson/ituzones.geojson';
                $geojsonData = file_get_contents($geojsonFile);

                $data = json_decode($geojsonData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    log_message("error", "Failed to read geojson data for ituzones" . json_last_error_msg());
                }

                $lcolor = $ituz_color;
                $lweight = 1;
                $pcolor = $ituz_color . 'FF'; // FF = 100% opacity as hex

                if (isset($data['features'])) {
                    $ituzones_polygon_array = [];
                    foreach ($data['features'] as $feature) { // one zone
                        $one_ituzpolygon = new Wavelog\StaticMapImage\Polygon($lcolor, $lweight, $pcolor, !$continentEnabled);
                        $coordinates = $feature['geometry']['coordinates'];

                        foreach ($coordinates as $zone) {
                            $ituz_points = [];
                            foreach ($zone as $point) {
                                $ituz_points[] = [$point[1], $point[0]];
                                $one_ituzpolygon->addPoint(new Wavelog\StaticMapImage\LatLng($point[1], $point[0]));
                            }
                        }

                        $zone_number = $feature['properties']['itu_zone_number'];
                        $zone_name_loc = $feature['properties']['itu_zone_name_loc'];
                        $ituzones_polygon_array[$zone_number]['polygon'] = $one_ituzpolygon;
                        $ituzones_polygon_array[$zone_number]['number'] = $zone_number;
                        $ituzones_polygon_array[$zone_number]['name_loc'] = $zone_name_loc;
                    }
                } else {
                    log_message("error", "Failed to read geojson data for ituzones. No features found.");
                }

                $ituz_tl = \Wavelog\StaticMapImage\TileLayer::defaultTileLayer();
                $ituz_tl = $ituz_tl->setOpacity(0);
                $ituz_map = new \Wavelog\StaticMapImage\OpenStreetMap(new \Wavelog\StaticMapImage\LatLng($centerMapLat, $centerMapLng), $zoom, $width, $height, $ituz_tl);
                $ituz_image = $ituz_map->getImage($centerMap);

                foreach ($ituzones_polygon_array as $ituzones_polygon) {
                    $polygon = $ituzones_polygon['polygon'];
                    $polygon->draw($ituz_image, $ituz_map->getMapData());

                    $zone_number = $ituzones_polygon['number'];
                    $ituz_fontsize = 33;
                    $position = new \Wavelog\StaticMapImage\LatLng($ituzones_polygon['name_loc'][0], $ituzones_polygon['name_loc'][1]);
                    $positionXY = $ituz_map->getMapData()->convertLatLngToPxPosition($position);
                    $ituz_image->writeText($zone_number, $fontPath, $ituz_fontsize, $ituz_color, $positionXY->getX(), $positionXY->getY(), $ituz_image::ALIGN_CENTER, $ituz_image::ALIGN_MIDDLE, 0, 0, !$continentEnabled);
                }

                $ituz_image->savePNG($ituz_cachedir . '/' . $ituz_filename);

                // free memory
                unset($ituzones_polygon_array);
                
            } else {
                log_message('info', "Found cached ITU Zone Overlay. Using cached overlay...");
            }
        }


        //===============================================================================================================================
        //==================================================== CREATE THE IMAGE =========================================================
        //===============================================================================================================================

        /**
         * Finally we can create the image and add the elements
         */

        $image = $map->getImage($centerMap);

        // Add night shadow
        if ($night_shadow) {
            $night_shadow_polygon->draw($image, $map->getMapData());
        }

        // Pathlines
        if ($pathlines) {
            foreach ($paths as $path) {
                $path->draw($image, $map->getMapData());
            }
            foreach ($paths_cnfd as $path) {
                $path->draw($image, $map->getMapData());
            }
        }

        // CQ Zones
        if ($cqzones) {
            $cqz_image = DantSu\PHPImageEditor\Image::fromPath($cqz_cachedir . '/' . $cqz_filename);
            $image->pasteOn($cqz_image, 0, 0);
        }

        // ITU Zones
        if ($ituzones) {
            $ituz_image = DantSu\PHPImageEditor\Image::fromPath($ituz_cachedir . '/' . $ituz_filename);
            $image->pasteOn($ituz_image, 0, 0);
        }

        // Add markers
        if (!$hide_home) {
            $markersStation->draw($image, $map->getMapData());
        }
        $markers->draw($image, $map->getMapData());
        $markersConfirmed->draw($image, $map->getMapData());

        // Add Wavelog watermark
        $watermark = DantSu\PHPImageEditor\Image::fromPath('src/StaticMap/src/resources/watermark_static_map.png');
        $watermark->resize(round($width * $watermark_size_mutiplier), round((($width * 3) / 4) * $watermark_size_mutiplier));
        $image->pasteOn($watermark, $watermarkPosX, $watermarkPosY);

        // Add "Created with Wavelog" text
        $user = $this->user_model->get_by_id($uid)->row();
        $custom_date_format = $user->user_date_format;
        $dateTime = date($custom_date_format . ' - H:i');
        $text = "Created with Wavelog on " . $dateTime . " UTC";
        $color = 'ff0000'; // Red
        $image->writeText($text, $fontPath, $fontSize, $color, $fontPosY, $fontPosX);

        // Add continent text
        if ($continentEnabled) {
            $fontPath = 'src/StaticMap/src/resources/font.ttf';
            $color = 'ff0000'; // Red
            $image->writeText($continentText, $fontPath, $fontSize, $color, $contFontPosX, $contFontPosY);
        }

        // Save the image
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

        $start = new \Wavelog\StaticMapImage\LatLng($start[0], $start[1]);
        $end = new \Wavelog\StaticMapImage\LatLng($end[0], $end[1]);

        $path = new \Wavelog\StaticMapImage\Line($color, $weight, !$continent);

        foreach ($path->geodesicPoints($start, $end, $continent) as $point) {
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

        $this->load->model('stationsetup_model');

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
