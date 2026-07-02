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
*/

$config['tile_styles'] = [

    '0' => [
        'css' => 'map-follow',
        'title' => 'Follow Theme',
    ],

    '1' => [
        'css' => 'map-light',
        'title' => 'Light',
    ],

    '2' => [
        'css' => 'map-gray',
        'title' => 'Gray',
    ],

    '3' => [
        'css' => 'map-night',
        'title' => 'Night',

    ],

    '4' => [
        'css' => 'map-high-contrast',
        'title' => 'High Contrast',
    ],

    '5' => [
        'css' => 'map-superhero',
        'title' => 'Superhero',
    ],

];