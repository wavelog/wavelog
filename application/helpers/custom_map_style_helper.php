<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('map_css_file')) {

    function map_css_file()
    {
        $CI =& get_instance();

        $CI->config->load('custom_map_style');

        $styles = $CI->config->item('tile_styles');

        $map_custom = json_decode($CI->optionslib->get_map_custom(), true);

        $style = $map_custom['tile_style'] ?? '0';

        if (isset($styles[$style])) {
            return $styles[$style]['css'];
        }

        return $styles['0']['css'];
    }
}

if (!function_exists('map_style_options')) {

    function map_style_options()
    {
        $CI =& get_instance();

        $CI->config->load('custom_map_style');

        return $CI->config->item('tile_styles');
    }
}