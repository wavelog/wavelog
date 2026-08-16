<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class OptionsLib {

    private $CI;

    // Per-request cache: get_option() is called many times per page load
    // (footer alone reads some options 10+ times), so each option is only
    // fetched from the database once per request.
    private array $cache = [];

    function __construct() {

        // Make Codeigniter functions available to library
        $this->CI = &get_instance();

        // Force Migration to run on every page load
        $this->CI->load->library('Migration');
        $this->CI->migration->current();

        //Load the options model
        $this->CI->load->model('options_model');
    }

    /**
     * Get an option from the options table
     * 
     * @param string $option_name The name of the option to retrieve
     * @return mixed The value of the option, or null if not found
     */
    function get_option($option_name) {
        // We remove the legacy "option_" prefix to maintain backwards compatibility with older code that may still use it
        if (str_starts_with($option_name, 'option_')) {
            $option_name = substr($option_name, 7);
        }

        if (!array_key_exists($option_name, $this->cache)) {
            $this->cache[$option_name] = $this->CI->options_model->item($option_name);
        }

        return $this->cache[$option_name];
    }

    /**
     * Update an option in the options table
     * 
     * @param string $option_name The name of the option to update
     * @param mixed $option_value The new value of the option
     * @return bool True if the option was updated successfully, false otherwise
     */
    function update($option_name, $option_value) {
        unset($this->cache[$option_name]);

        return $this->CI->options_model->update($option_name, $option_value);
    }


    /**
     * Get the current theme for the user, either from session data or from the global configuration
     * 
     * @return string The name of the current theme
     */
    function get_theme() {
        if ($this->CI->session->userdata('user_stylesheet')) {
            return $this->CI->session->userdata('user_stylesheet');
        } else {
            return $this->get_option('theme');
        }
    }

    /**
     * Get the logo for the current theme, or a specific theme if provided
     * 
     * @param string $logo_location The location of the logo to retrieve (e.g. 'header_logo', 'main_logo')
     * @param string|null $theme The name of the theme to retrieve the logo for, or null to use the current theme
     * @return string The name of the logo file, or 'no_logo_found' if the logo is not found
     */
    function get_logo($logo_location, $theme = null) {
        if ($theme == null) {
            $theme = $this->get_theme();
        }

        // load the themes model and fetch the logo name from it
        $this->CI->load->model('Themes_model');

        $logo = $this->CI->Themes_model->get_logo_from_theme($theme, $logo_location);

        if ($logo != null) {
            return $logo;
        } else {
            // uses assets/logo/no_logo_found.png; don't change this string
            return 'no_logo_found';
        }
    }

    /**
     * Get the custom map options for the user, either from the database or from default values
     * 
     * @param bool $visitor Whether the user is a visitor (true) or a logged-in user (false)
     * @param string|null $slug The slug of the user to retrieve options for, or null to use the current user
     * @return string A JSON-encoded string of the custom map options
     */
    function get_map_custom($visitor = false, $slug = null) {
        $jsonout = [];

        if ($visitor == false) {

            $result = $this->CI->user_options_model->get_options('map_custom');

            foreach ($result->result() as $options) {
                if ($options->option_name == 'icon') {
                    $jsonout[$options->option_key] = json_decode($options->option_value, true);
                } else {
                    $jsonout[$options->option_name . '_' . $options->option_key] = $options->option_value;
                }
            }

            if (count($jsonout) == 0) {
                $jsonout['qso'] = array(
                    "icon" => "fas fa-dot-circle",
                    "color" => "#ff0000"
                );
                $jsonout['qsoconfirm'] = array(
                    "icon" => "fas fa-dot-circle",
                    "color" => "#00aa00"
                );
                $jsonout['station'] = array(
                    "icon" => "fas fa-broadcast-tower",
                    "color" => "#0000ff"
                );
            }
        } else {

            $this->CI->load->model('stationsetup_model');

            $slug = $this->CI->security->xss_clean($slug);
            $userid = $this->CI->stationsetup_model->public_slug_exists_userid($slug);

            $result = $this->CI->user_options_model->get_options('map_custom', null, $userid);

            foreach ($result->result() as $options) {
                if ($options->option_name == 'icon') {
                    $jsonout[$options->option_key] = json_decode($options->option_value, true);
                } else {
                    $jsonout[$options->option_name . '_' . $options->option_key] = $options->option_value;
                }
            }

            if (count($jsonout) == 0) {
                $jsonout['qso'] = array(
                    "icon" => "fas fa-dot-circle",
                    "color" => "#ff0000"
                );
                $jsonout['qsoconfirm'] = array(
                    "icon" => "fas fa-dot-circle",
                    "color" => "#00aa00"
                );
                $jsonout['station'] = array(
                    "icon" => "fas fa-broadcast-tower",
                    "color" => "#0000ff"
                );
            }

            $jsonout['gridsquare_layer']    = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'gridsquare_layer', 'option_key' => $slug), $userid)->row()->option_value ?? true;
            $jsonout['path_lines']          = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'path_lines', 'option_key' => $slug), $userid)->row()->option_value ?? true;
            $jsonout['cqzone_layer']        = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'cqzone_layer', 'option_key' => $slug), $userid)->row()->option_value ?? true;
            $jsonout['qsocount']            = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'qsocount', 'option_key' => $slug), $userid)->row()->option_value ?? 250;
            $jsonout['nightshadow_layer']   = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'nightshadow_layer', 'option_key' => $slug), $userid)->row()->option_value ?? true;
            $jsonout['band']                = $this->CI->user_options_model->get_options('ExportMapOptions', array('option_name' => 'band', 'option_key' => $slug), $userid)->row()->option_value ?? '';
        }

        return json_encode($jsonout, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
}
