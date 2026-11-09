<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
*
* Configuration file for all things relating to LoTW
*
*/

/*
|--------------------------------------------------------------------------
| Propagation modes that are not supported by LoTW
|--------------------------------------------------------------------------
|
| As per tqsl config the following propagation modes are not supported by
| LoTW and ignored. So Wavelog will not handle them during LoTW sync.
| As per tqsl v2.7.3 these modes are:
| - RPT
| - INTERNET
| Please do not edit!
|
*/
$config['lotw_unsupported_prop_modes'] = array('INTERNET', 'RPT');

$config['lotw_download_skip_date'] = '2100-01-01 00:00:00.000';
