<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('map_style_options')) {

	// Returns [slug => label]. Slug == stored value == body class.
	// Derived from the themes table (cached via user_model->getUserThemes(),
	// the same cache the theme-chooser uses) + a "Follow" default +
	// two synthetic (non-theme) looks.
	function map_style_options()
	{
		$CI =& get_instance();

		$themes = $CI->user_model->getUserThemes();

		$options = ['map-follow' => 'Follow active theme'];

		foreach ($themes['themes'] as $t) {
			$options['map-' . $t->foldername] = $t->name;
		}

		$options['map-gray']          = 'Gray';
		$options['map-high-contrast'] = 'High Contrast';

		return $options;
	}
}

if (!function_exists('map_style_class')) {

	// Returns the validated body-class slug for the current user's map style.
	// Missing / invalid / deleted-theme values self-heal to 'map-follow'.
	function map_style_class()
	{
		$CI =& get_instance();

		$map_custom = json_decode($CI->optionslib->get_map_custom(), true);

		$style = $map_custom['tile_style'] ?? 'map-follow';

		return array_key_exists($style, map_style_options()) ? $style : 'map-follow';
	}
}
