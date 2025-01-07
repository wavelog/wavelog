<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


class Staticmap extends CI_Controller {

    public function render($slug = '') {

        // set to true to remove cached imaged for debugging pruposes
        $debugging = false;
        if (ENVIRONMENT == 'development') {
            $debugging = true;
        }

        $this->load->model('staticmap_model');
        $this->load->model('stationsetup_model');
        $this->load->model('visitor_model');

        $slug = $this->security->xss_clean($slug);
        if (empty($slug)) {
            show_404(__("Unknown Public Page."));
        }
        // check if the public slug exists
        $logbook_id = $this->stationsetup_model->public_slug_exists_logbook_id($slug);
        if ($logbook_id == false) {
            show_404(__("Unknown Public Page."));
        }

        // Optional override-parameters
        $band = $this->input->get('band', TRUE) ?? 'nbf';
        $orbit = ($this->input->get('orbit', TRUE) ?? '') == '' ? 'nOrb' : strtoupper($this->input->get('orbit', TRUE));
        $continent = ($this->input->get('continent', TRUE) ?? '') == '' ? 'nC' : strtoupper($this->input->get('continent', TRUE));
        $thememode = ($this->input->get('theme', TRUE) ?? '') == '' ? '' : strtolower($this->input->get('theme', TRUE));
        $hide_home = $this->input->get('hide_home', TRUE) == 1 ? true : false;
        $contest = ($this->input->get('contest', TRUE) ?? '') == '' ? 'nContest' : strtoupper($this->input->get('contest', TRUE));

        $start_date = $this->input->get('start_date', TRUE) ?? 'noStart';   // Format YYYY-MM-DD
        $end_date = $this->input->get('end_date', TRUE) ?? 'noEnd';          // Format YYYY-MM-DD

        // if the user defines an Satellite Orbit, we need to set the band to SAT
        if ($orbit != 'nOrb') {
            $band = 'SAT';
        }

        /**
         * Based on Export Settings -> Overlays and QSO Count
         */
        // qsocount
        $qsocount = $this->input->get('qsocount', TRUE) ?? '';
        // if the qso count is not a number, set it to the user option or 250 per default (same as used in stationsetup)
        $uid = $this->stationsetup_model->getContainer($logbook_id, false)->row()->user_id;
        if ($qsocount == 0 || (!is_numeric($qsocount) && $qsocount != 'all')) {
            $qsocount = $this->user_options_model->get_options('ExportMapOptions', array('option_name' => 'qsocount', 'option_key' => $slug), $uid)->row()->option_value ?? 250;
        }

        // Night shadow
        $night_shadow = $this->input->get('ns', TRUE) ?? '';
        if ($night_shadow == '' || ($night_shadow != 1 && $night_shadow != 0)) {
            $r = $this->user_options_model->get_options('ExportMapOptions', array('option_name' => 'nightshadow_layer', 'option_key' => $slug), $uid)->row()->option_value ?? '';
            $night_shadow = $r == 'true' ? true : false;
        }

        // Pathlines
        $pathlines = $this->input->get('pl', TRUE) ?? '';
        if ($pathlines == '' || ($pathlines != 1 && $pathlines != 0)) {
            $r = $this->user_options_model->get_options('ExportMapOptions', array('option_name' => 'path_lines', 'option_key' => $slug), $uid)->row()->option_value ?? '';
            $pathlines = $r == 'true' ? true : false;
        }

        // CQ Zones
        $cqzones = $this->input->get('cqz', TRUE) ?? '';
        if ($cqzones == '' || ($cqzones != 1 && $cqzones != 0)) {
            $r = $this->user_options_model->get_options('ExportMapOptions', array('option_name' => 'cqzones_layer', 'option_key' => $slug), $uid)->row()->option_value ?? '';
            $cqzones = $r == 'true' ? true : false;
        }

        // ITU Zones
        $ituzones = $this->input->get('ituz', TRUE) ?? '';
        if ($ituzones == '' || ($ituzones != 1 && $ituzones != 0)) {
            $r = $this->user_options_model->get_options('ExportMapOptions', array('option_name' => 'ituzones_layer', 'option_key' => $slug), $uid)->row()->option_value ?? '';
            $ituzones = $r == 'true' ? true : false;
        }

        // Watermark
        $watermark = $this->input->get('wm', TRUE) ?? '';
        if ($watermark == '' || ($watermark != 1 && $watermark != 0)) {
            $watermark = true;
        }

        // handling the theme mode
        $this->load->model('themes_model');
        if ($thememode == null || $thememode == '' || ($thememode != 'dark' && $thememode != 'light')) {
            $r =  $this->themes_model->get_theme_mode($this->optionslib->get_option('option_theme'));
            $thememode = $r;
        }

        // prepare the cache directory
        $cachepath = $this->config->item('cache_path') == '' ? APPPATH . 'cache/' : $this->config->item('cache_path');
        $cacheDir = $cachepath . "staticmap_images/";
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // we need the realpath later for validation
        $cacheDir = realpath($cachepath . "staticmap_images/");

        // create a unique filename for the cacheund e
        $filenameRaw = $uid
                     . $logbook_id
                     . $qsocount
                     . $band
                     . $thememode
                     . $continent
                     . $hide_home
                     . ($night_shadow == false ? 0 : 1)
                     . ($pathlines == false ? 0 : 1)
                     . ($cqzones == false ? 0 : 1)
                     . ($ituzones == false ? 0 : 1)
                     . $orbit
                     . $contest
                     . $start_date
                     . $end_date
                     . $watermark;   

        $filename = crc32('staticmap_' . $slug) . '_' . substr(md5($filenameRaw), 0, 12) . '.png';
        $filepath = $cacheDir . '/' . $filename;

        // Set the cache time to 7 days
        $maxAge = 3600 * 24 * 7;

        // remove the cached image for debugging purposes
        if ($debugging) {
            if (is_file($filepath)) {
                unlink($filepath);
            }
        }

        if ($this->staticmap_model->validate_cached_image($filepath, $cacheDir, $maxAge, $slug)) {
            log_message('debug', 'Static map image found in cache: ' . $filename);
            header('Content-Type: image/png');
            readfile($filepath);
            return;
        } else {
            if (in_array('gd', get_loaded_extensions())) {

                if ($logbook_id != false) {
                    // Get associated station locations for mysql queries
                    $logbooks_locations_array = $this->stationsetup_model->get_container_relations($logbook_id);

                    if (!$logbooks_locations_array) {
                        show_404(__("Empty Logbook"));
                    }
                } else {
                    log_message('error', $slug . ' has no associated station locations');
                    show_404(__("Unknown Public Page."));
                }

                // we need to get an array of all coordinates of the stations
                if (!$this->load->is_loaded('logbook_model')) {
                    $this->load->model('logbook_model');
                }
                $grids = [];
                foreach ($logbooks_locations_array as $location) {
                    $station_info = $this->logbook_model->check_station($location);
                    if ($station_info) {
                        $grids[] = $station_info['station_gridsquare'];
                    }
                }
                if (!$this->load->is_loaded('Qra')) {
                    $this->load->library('Qra');
                }
                $coordinates = [];
                foreach ($grids as $grid) {
                    $coordinates[] = $this->qra->qra2latlong($grid);
                }
                $centerMap = $this->qra->getCenterLatLng($coordinates);

                $qsos = $this->visitor_model->get_qsos(
                    $qsocount, 
                    $logbooks_locations_array, 
                    $band == 'nbf' ? '' : $band, 
                    $continent == 'nC' ? '' : $continent, 
                    $orbit == 'nOrb' ? '' : $orbit, 
                    $contest == 'nContest' ? '' : $contest,
                    $start_date == 'noStart' ? '' : $start_date,
                    $end_date == 'noEnd' ? '' : $end_date
                );

                $image = $this->staticmap_model->render_static_map($qsos, $uid, $centerMap, $coordinates, $filepath, $continent, $thememode, $hide_home, $night_shadow, $pathlines, $cqzones, $ituzones, $watermark);

                header('Content-Type: image/png');

                if ($image == false) {
                    $msg = "Can't create static map image. Something went wrong.";
                    log_message('error', $msg);
                    show_404($msg);
                } else {
                    readfile($filepath);
                }
            } else {
                $msg = "Can't create static map image. Extention 'php-gd' is not installed. Install it and restart the webserver.";
                log_message('error', $msg);
                echo $msg;
            }
        }

        log_message('debug', 'Static map image generator took ' . round((memory_get_peak_usage() / 1024 / 1024)) . " MB of memory");
    }
}
