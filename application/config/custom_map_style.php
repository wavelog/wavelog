<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Custom Map Styles Configuration
|--------------------------------------------------------------------------
|
| These styles are used for customizing the map appearance.
| This page defines the mapping results for the `tile_styles` values ​
| ​within the database's custom user map and implements string translation 
| for the title via the layout page.
| Note: After adding the entry here using the template below, 
|       please go to `assets/css/custom_map_style`, create a CSS file 
|       with the name you just specified, and define your custom styles within it.
| Note: The `map_style_label` function in the `custom_map_style_helper.php` file is responsible for translating the title of the map style.
*/

$config['tile_styles'] = [

    '0' => [
        'css' => 'map-follow',
        'label' => 'map_follow_theme',
    ],

    '1' => [
        'css' => 'map-light',
        'label' => 'map_light',
    ],

    '2' => [
        'css' => 'map-gray',
        'label' => 'map_gray',
    ],

    '3' => [
        'css' => 'map-night',
        'label' => 'map_night',

    ],

    '4' => [
        'css' => 'map-high-contrast',
        'label' => 'map_high_contrast',
    ],

    '5' => [
        'css' => 'map-superhero',
        'label' => 'map_superhero',
    ],

];