<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Paths Library to return specific paths
 */
class Paths
{
    // generic function for return eQsl path //
    function getPathEqsl()
    {
        $CI = &get_instance();
        $CI->load->model('Eqsl_images');
        return $CI->Eqsl_images->get_imagePath();
    }

    // generic function for return Qsl path //
    function getPathQsl()
    {
        $CI = &get_instance();
        $CI->load->model('Qsl_model');
        return $CI->Qsl_model->get_imagePath();
    }

    function make_update_path($path) {

		$CI = & get_instance();

		$path = "updates/" . $path;
        $datadir = $CI->config->item('datadir');
        if(!$datadir) {
            return $path;
        }
        return $datadir . "/" . $path;
	}

    function cache_buster($filepath) {
        // make sure $filepath starts with a slash
        if (substr($filepath, 0, 1) !== '/') $filepath = '/' . $filepath;

        // These files are not existent on purpose and should not trigger error logs
        $err_exceptions = [
            '/assets/json/datatables_languages/en-US.json',
        ];

        $CI = & get_instance();
		$fullpath = empty($CI->config->item('directory')) ? $_SERVER['DOCUMENT_ROOT'] . $filepath : $_SERVER['DOCUMENT_ROOT'] . '/' . $CI->config->item('directory') . $filepath;

        // We comment out this line because latest teste at LA8AJA's XAMPP setup showed that it works even without it
        // So we will keep it simple and just use the $filepath as is, since it seems to work fine on both Linux and Windows setups
        // $fullpath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        if (file_exists($fullpath)) {
            return base_url($filepath) . '?v=' . filemtime($fullpath);
        } else {
            if (!in_array($filepath, $err_exceptions)) {
                log_message('error', 'CACHE BUSTER: File does not exist: ' . $fullpath);
            }
        }
        return base_url($filepath);
    }
}
