<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Paths Library to return specific paths
 */
class Paths {

    /**
     * Returns the userdata path (or legacy path if the userdata option is not set) for the given type and user_id.
     * 
     * @param string $type The type of path to return (e.g. 'eqsl_card', 'qsl_card')
     * @param string $pathorurl 'u' to return the web-relative path, 'p' to return the absolute filesystem path
     * @param int|null $user_id The user_id to return the path for. If null, will use the user_id from session data
     */
    function getUserdataPath($type, $pathorurl = 'u', $user_id = null) {
        // test if new folder directory option is enabled
        $CI = &get_instance();
        $userdata_dir = $CI->config->item('userdata');

        // make sure these are the same as in Debug_model.php function migrate_userdata()
        $allowed_types = [
            'eqsl_card', 
            'qsl_card'
        ];

        // validate path type
        if (!in_array($pathorurl, ['u', 'p'])) {
            log_message('error', 'Invalid pathorurl passed to getUserdataPath: ' . $pathorurl);
            return false; // invalid pathorurl
        }

        if (!in_array($type, $allowed_types)) {
            log_message('error', 'Invalid type passed to getUserdataPath: ' . $type);
            return false; // invalid type
        }

        if (isset($userdata_dir)) {

            if (!valid_uid($user_id)) {
                $user_id = $CI->session->userdata('user_id');
            }

            // check if there is a user_id in the session data and it's not empty
            if (valid_uid($user_id)) {

                // create the folder
                if (!file_exists(realpath(APPPATH . '../') . '/' . $userdata_dir . '/' . $user_id . '/' . $type)) {
                    mkdir(realpath(APPPATH . '../') . '/' . $userdata_dir . '/' . $user_id . '/' . $type, 0755, true);
                }

                // and return it
                if ($pathorurl == 'u') {
                    return $userdata_dir . '/' . $user_id . '/' . $type;
                } else {
                    return realpath(APPPATH . '../') . '/' . $userdata_dir . '/' . $user_id . '/' . $type;
                }
            } else {
                log_message('info', 'getUserdataPath(); Can not get ' . $type . ' path because no user_id in session data');
            }
        } else {
            // if the config option is not set we just return the old path
            return $this->legacyPaths($type, $pathorurl);
        }
    }

    /**
     * @deprecated Use getUserdataPath('eqsl_card') instead.
     * Kept as a fallback for the brief window during a git update where an
     * older view might still call this method before it gets removed.
     */
    function getPathEqsl($pathorurl = 'u', $user_id = null) {
        return $this->getUserdataPath('eqsl_card', $pathorurl, $user_id);
    }

    /**
     * @deprecated Use getUserdataPath('qsl_card') instead.
     * Kept as a fallback for the brief window during a git update where an
     * older view might still call this method before it gets removed.
     */
    function getPathQsl($pathorurl = 'u', $user_id = null) {
        return $this->getUserdataPath('qsl_card', $pathorurl, $user_id);
    }

    private function legacyPaths($type, $pathorurl = 'u') {
        switch ($type) {
            case 'eqsl_card':
                $path = 'images/eqsl_card_images';
                break;
            case 'qsl_card':
                $path = 'assets/qslcard';
                break;
            default:
                log_message('error', 'Invalid type passed to legacyPaths(): ' . $type);
                return false;
        }

        // 'u' returns the web-relative path, anything else the absolute filesystem path
        if ($pathorurl == 'u') {
            return $path;
        } else {
            return realpath(APPPATH . '../') . '/' . $path;
        }
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
}
