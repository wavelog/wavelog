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

    /**
     * Generate a CSRF token, store it in the session under $key, and return it
     * for injection into view data.
     */
    function csrf_generate($key) {
        $CI = &get_instance();
        $token = bin2hex(random_bytes(32));
        $CI->session->set_userdata($key, $token);
        return $token;
    }

    /**
     * Verify the submitted csrf_token POST field against the session value for
     * $key. Rotates the token on success. Returns true on success, false on failure.
     */
    function csrf_verify($key) {
        $CI = &get_instance();
        $submitted = $CI->input->post('csrf_token', TRUE);
        $stored    = $CI->session->userdata($key);
        if (empty($submitted) || empty($stored) || !hash_equals($stored, $submitted)) {
            return false;
        }
        $CI->session->set_userdata($key, bin2hex(random_bytes(32)));
        return true;
    }

    function cache_buster($filepath) {
        // make sure $filepath starts with a slash
        if (substr($filepath, 0, 1) !== '/') $filepath = '/' . $filepath;

        $CI = & get_instance();
		$fullpath = empty($CI->config->item('directory')) ? $_SERVER['DOCUMENT_ROOT'] . $filepath : $_SERVER['DOCUMENT_ROOT'] . '/' . $CI->config->item('directory') . $filepath;

        // We comment out this line because latest teste at LA8AJA's XAMPP setup showed that it works even without it
        // So we will keep it simple and just use the $filepath as is, since it seems to work fine on both Linux and Windows setups
        // $fullpath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        if (file_exists($fullpath)) {
            return base_url($filepath) . '?v=' . filemtime($fullpath);
        } else {
            log_message('error', 'CACHE BUSTER: File does not exist: ' . $fullpath);
        }
        return base_url($filepath);
    }

    // Creates contesting logging token
    function create_contesting_logging_token($contest_session_id) {
        $CI = &get_instance();
        
        // In case of clubstation, we need the source_uid so we can determine the actual operator
        // Is there no source_uid, we either clubstation support is disabled or the user is not operating in it's own account and we can use the user_id
        $user_id = $CI->session->userdata('source_uid') ?: $CI->session->userdata('user_id');

        $logging_token_payload = [
            'user_id' => intval($user_id),
            'timestamp' => time(),
            'contest_session_id' => intval($contest_session_id)
        ];

        return urlencode(base64_encode(json_encode($logging_token_payload)));
    }

    function decode_contesting_logging_token($logging_token) {
        $CI = &get_instance();
        $decoded_token = $CI->security->xss_clean(json_decode(base64_decode(urldecode($logging_token)), true));
        return $decoded_token;
    }
}
