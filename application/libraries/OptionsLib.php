<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
	Controls the interaction with the QRZ.com Subscription based XML API.
*/


class OptionsLib {

    function __construct()
	{
        // Make Codeigniter functions available to library
        $CI =& get_instance();

	// Force Migration to run on every page load
    	$CI->load->library('Migration');
	    $CI->migration->current();

        //Load the options model
        $CI->load->model('options_model');

        // Store returned array of autoload options
        $options_result = $CI->options_model->get_autoloads();

        // If results are greater than one
        if($options_result->num_rows() > 0) {
            // Loop through the array
            foreach ($options_result->result() as $item)
            {
                /*
                * Add option to the config system dynamicly option_name is prefixed by option_
                * you can then call $this->config->item('option_<option_name>') to get the item.
                */
                if($item->option_name == "language") {
                    // language is a global internal config item there for we dont want to prefix it as an option
                    //$CI->config->set_item($item->option_name, $item->option_value);
                } else {
                    $CI->config->set_item('option_'.$item->option_name, $item->option_value);
                }
            }
        }
    }

    // This returns a options value based on its name
    function get_option($option_name) {
        // Make Codeigniter functions available to library
        $CI =& get_instance();
        if (strpos($option_name, 'option_') !== false) { 
            if(!$CI->config->item($option_name)) {
                 //Load the options model
                $CI->load->model('options_model');
                $removed_options_tag = trim($option_name, 'option_');
                // call library function to get options value
                $options_result = $CI->options_model->item($removed_options_tag);
                    
                // return option_value as a string
                return $options_result;
            } else {
                return $CI->config->item($option_name);
            }
        } else {
            if(!$CI->config->item($option_name)) {
                //Load the options model
               $CI->load->model('options_model');
               // call library function to get options value
               $options_result = $CI->options_model->item($option_name);
                   
               // return option_value as a string
               return $options_result;
           } else {
                return $CI->config->item($option_name);
           }
        }
    }

    // Function to save new option to options table
    function save($option_name, $option_value, $autoload) {
        // Make Codeigniter functions available to library
        $CI =& get_instance();

        //Load the options model
        $CI->load->model('options_model');
        
        // call library function to save update
        $result = $CI->options_model->save($option_name, $option_value, $autoload);

        // return True or False on whether its completed.
        return $result;
    }

    // Function to update options within the options table
    function update($option_name, $option_value, $auto_load = NULL) {
        // Make Codeigniter functions available to library
        $CI =& get_instance();

        //Load the options model
        $CI->load->model('options_model');
        
        // call library function to save update
        $result = $CI->options_model->update($option_name, $option_value, $auto_load);

        // return True or False on whether its completed.
        return $result;
    }


    // This returns the global theme or the theme stored in the logged in users session data.
    function get_theme() {
        // Make Codeigniter functions available to library
        $CI =& get_instance();

        // If session data for stylesheet is set return choice
        if($CI->session->userdata('user_stylesheet')) {
            return $CI->session->userdata('user_stylesheet');
        } else {
            // Return the global choice.
            return $CI->config->item('option_theme');
        }

    }
        
    function get_logo($logo_location, $theme = null) {

        $CI =& get_instance();

        // get the theme with the get_theme() function above
        if ($theme == null) {
            $theme = $this->get_theme();
        }
        
        // load the themes model and fetch the logo name from it
        $CI->load->model('Themes_model');

        $logo = $CI->Themes_model->get_logo_from_theme($theme, $logo_location);

        if ($logo != null) {
            return $logo;
        } else {
            return 'no_logo_found';
        }
    }

    function get_map_custom($visitor = false, $slug = null) {

        $CI =& get_instance();

        $jsonout = [];
        
        if ($visitor == false) {

            $result = $CI->user_options_model->get_options('map_custom');

            foreach($result->result() as $options) {
                if ($options->option_name == 'icon') {
                    $jsonout[$options->option_key] = json_decode($options->option_value,true);
                } else  {
                    $jsonout[$options->option_name.'_'.$options->option_key]=$options->option_value;
                }
            }

        } else {

            $CI->load->model('stationsetup_model');

            $slug = $CI->security->xss_clean($slug);
            $userid = $CI->stationsetup_model->public_slug_exists_userid($slug);

            $result = $CI->user_options_model->get_options('map_custom', null, $userid);

            foreach($result->result() as $options) {
                if ($options->option_name=='icon') {
                    $jsonout[$options->option_key] = json_decode($options->option_value,true);
                } else {
                    $jsonout[$options->option_name.'_'.$options->option_key] = $options->option_value;
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

            $jsonout['gridsquare_layer']    = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'gridsquare_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
            $jsonout['path_lines']          = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'path_lines','option_key'=>$slug), $userid)->row()->option_value ?? true;
            $jsonout['cqzone_layer']        = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'cqzone_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
            $jsonout['qsocount']            = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'qsocount','option_key'=>$slug), $userid)->row()->option_value ?? 250;
            $jsonout['nightshadow_layer']   = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'nightshadow_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
            $jsonout['band']                = $CI->user_options_model->get_options('ExportMapOptions',array('option_name'=>'band','option_key'=>$slug), $userid)->row()->option_value ?? '';
        }
        
        return json_encode($jsonout);
    }

    function get_wlid() {
        $wavelog_id = $this->get_option('wavelog_id');

        if ($wavelog_id == NULL) {
            $wavelog_id = $this->create_wlid();
        }

        return $wavelog_id;
    }

    function create_wlid() {
        $CI =& get_instance();

        $wavelog_id = bin2hex('wavelog') . md5(uniqid(rand(), true)) . bin2hex(substr($CI->config->item('locator'), 0, 4));

        $CI->session->set_userdata('wavelog_id', $wavelog_id);
        $this->update('wavelog_id', $wavelog_id, 'yes');

        return $wavelog_id;
    }

}
