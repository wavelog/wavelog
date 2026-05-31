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

    /**
     * Creates an HMAC-SHA256-signed token for worker WebSocket authentication.
     * The Go worker verifies this locally using the shared secret — no PHP callback needed.
     *
     * Token format: hex(json_payload) + "." + hex(hmac-sha256)
     * Claims: { user_id, session_id, expires }
     */
    /**
     * Verifies a worker HMAC token and returns the claims array, or null on failure.
     */
    function verify_worker_token(string $token): ?array {
        $CI = &get_instance();
        $CI->config->load('worker', TRUE, TRUE);
        $secret = (string) $CI->config->item('worker_secret', 'worker');
        if ($secret === '' || $token === '') {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$encoded, $sig] = $parts;

        if (!hash_equals(hash_hmac('sha256', $encoded, $secret), $sig)) {
            return null;
        }

        $claims = json_decode(hex2bin($encoded), true);
        if (!$claims || ($claims['expires'] ?? 0) < time()) {
            return null;
        }

        return $claims;
    }

    function create_worker_token(int $contest_session_id, int $ttl_seconds = 86400): string {
        $CI = &get_instance();
        $CI->config->load('worker', TRUE, TRUE);
        $secret = (string) $CI->config->item('worker_secret', 'worker');
        if ($secret === '') {
            return '';
        }

        $user_id = intval($CI->session->userdata('source_uid') ?: $CI->session->userdata('user_id'));

        $claims = [
            'user_id'    => $user_id,
            'session_id' => intval($contest_session_id),
            'expires'    => time() + $ttl_seconds,
        ];

        $encoded = bin2hex(json_encode($claims));
        $sig     = hash_hmac('sha256', $encoded, $secret);
        return $encoded . '.' . $sig;
    }
}
