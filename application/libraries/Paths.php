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
}
