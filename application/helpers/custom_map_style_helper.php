<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Translate map style id
 */
if (!function_exists('map_style_label')) {

    function map_style_label($label)
    {
        switch ($label) {
            case 'map_follow_theme':
                return __('Follow Theme');

            case 'map_light':
                return __('Light');

            case 'map_gray':
                return __('Gray');

            case 'map_night':
                return __('Night');

            case 'map_high_contrast':
                return __('High Contrast');

            case 'map_superhero':
                return __('Superhero');

            default:
                return $label;
        }
    }
}

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