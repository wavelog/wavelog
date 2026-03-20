<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

require_once APPPATH . '../src/jwt/src/JWT.php';
require_once APPPATH . '../src/jwt/src/Key.php';
require_once APPPATH . '../src/jwt/src/JWK.php';

/*

	Handles header based authentication

*/
class Header_auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if ($this->config->item('auth_header_enable')) {
            $this->config->load('sso', true, true);
        } else {
            $this->_sso_error(__("SSO Authentication is disabled."));
        }
    }

    /**
     * Authenticate using a JWT from a trusted request header. This endpoint is meant to be called by a reverse proxy that sits in front of Wavelog and handles the actual authentication (e.g. OAuth2 Proxy, Apache mod_auth_oidc, etc.). 
     * The reverse proxy validates the user's session and forwards a JWT access token containing the user's identity and claims in a trusted HTTP header. This method decodes the token, verifies it, extracts the user information 
     * and logs the user in. Depending on configuration, it can also automatically create a local user account if one does not exist, and update existing user data.
     * 
     * For more information check out the documentation: https://docs.wavelog.org/admin-guide/configuration/thirdparty-authentication/
     */
    public function login() {
        // Guard: feature must be enabled
        if (!$this->config->item('auth_header_enable')) {
            $this->_sso_error(__("SSO Authentication is disabled. Check your configuration."));
        }

        // Decode JWT access token forwarded by idp
        $accesstoken_path = $this->config->item('auth_header_accesstoken', 'sso') ?? false;
        if (!$accesstoken_path) {
            log_message('error', 'SSO Authentication: Access Token Path not configured in config.php.');
            $this->_sso_error();
        }
        $token = $this->input->server($accesstoken_path, true);
        if (empty($token)) {
            log_message('error', 'SSO Authentication: Missing access token header.');
            $this->_sso_error();
        }

        if ($this->config->item('auth_header_debug_jwt', 'sso')) {
            log_message('debug', 'Raw JWT: ' . $token);
        }

        $claims = $this->_verify_jwt($token);
        if (empty($claims)) {
            $this->_sso_error("SSO Authentication failed. Invalid token.");
        }

        if ($this->config->item('auth_header_debug_jwt', 'sso')) {
            log_message('debug', 'Decoded and validated JWT: ' . json_encode($claims, JSON_PRETTY_PRINT));
        }

        $claim_map = $this->config->item('auth_headers_claim_config', 'sso');

        // Extract all mapped claims dynamically — supports custom fields added by the admin
        $mapped = [];
        foreach ($claim_map as $db_field => $cfg) {
            $mapped[$db_field] = $claims[$cfg['claim']] ?? null;
        }

        // Build composite key: JSON {iss, sub} — uniquely identifies a user across IdP and user
        $iss = $claims['iss'] ?? '';
        $sub = $claims['sub'] ?? '';
        if (empty($iss) || empty($sub)) {
            log_message('error', 'SSO Authentication: Missing iss or sub claim in access token.');
            $this->_sso_error();
        }
        $external_identifier = json_encode(['iss' => $iss, 'sub' => $sub]);

        $this->load->model('user_model');
        $query = $this->user_model->get_by_external_account($external_identifier);

        if (!$query || $query->num_rows() !== 1) {
            if ($this->config->item('auth_header_create', 'sso')) {
                $this->_create_user($mapped, $external_identifier);
                $query = $this->user_model->get_by_external_account($external_identifier);
            } else {
                $this->_sso_error(__("User not found."));
                return;
            }
        }

        if (!$query || $query->num_rows() !== 1) {
            log_message('error', 'SSO Authentication: Something went terribly wrong. Check error log.');
            $this->_sso_error();
            return;
        }

        $user = $query->row();

        // Prevent clubstation direct login via header (mirrors User::login)  
        if (!empty($user->clubstation) && $user->clubstation == 1) {
            $this->_sso_error(__("You can't login to a clubstation directly. Use your personal account instead."));
        }

        // Maintenance mode check (admin only allowed)  
        if (ENVIRONMENT === 'maintenance' && (int)$user->user_type !== 99) {
            $this->_sso_error(__("Sorry. This instance is currently in maintenance mode. Only administrators are currently allowed to log in."));
        }

        // Check if club station before update
        // Don't update fields in maintenance mode
        if (ENVIRONMENT !== 'maintenance') {
            // Update fields from JWT claims where override_on_update is enabled
            $this->_update_user_from_claims($user->user_id, $mapped);
        }



        // Establish session  
        $this->user_model->update_session($user->user_id);
        $this->user_model->set_last_seen($user->user_id);

        // Set language cookie (mirrors User::login)  
        $cookie = [
            'name'   => $this->config->item('gettext_cookie', 'gettext'),
            'value'  => $user->user_language,
            'expire' => 1000,
            'secure' => $this->config->item('cookie_secure'),
        ];
        $this->input->set_cookie($cookie);

        log_message('info', "User ID [{$user->user_id}] logged in via SSO.");
        redirect('dashboard');
    }

    /**
     * Decode and verify a JWT token. Returns the claims array on success, null on failure.
     *
     * JWKS mode (auth_header_jwks_uri configured):
     *   Firebase JWT::decode() handles signature, exp, nbf and alg validation.
     *
     * Low Security mode (no JWKS URI):
     *   Payload is decoded without signature verification. exp, nbf and alg
     *   are checked manually.
     *
     * In both modes: iat age and typ are validated after decoding.
     *
     * @param string $token
     *
     * @return array|null
     */
    private function _verify_jwt(string $token): ?array {
        $jwksUri = $this->config->item('auth_header_jwks_uri', 'sso');

        if (!empty($jwksUri)) {
            try {
                $jwksJson = file_get_contents($jwksUri);
                if ($jwksJson === false) {
                    log_message('error', 'SSO Authentication: Failed to fetch JWKS from ' . $jwksUri);
                    return null;
                }
                $jwks = json_decode($jwksJson, true);
                $keys = JWK::parseKeySet($jwks);
                $claims = (array) JWT::decode($token, $keys);
            } catch (\Exception $e) {
                log_message('error', 'SSO Authentication: JWT decode/verify failed: ' . $e->getMessage());
                return null;
            }
        } else {
            // Low Security mode: decode without signature verification to provide a minimal level of security
            log_message('debug', 'SSO Authentication: JWKS URI not configured, skipping signature verification.');

            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                log_message('error', 'SSO Authentication: JWT does not have 3 parts.');
                return null;
            }

            $b64decode = function (string $part): ?array {
                $json = base64_decode(str_pad(strtr($part, '-_', '+/'), strlen($part) % 4, '=', STR_PAD_RIGHT));
                if ($json === false) return null;
                $data = json_decode($json, true);
                return is_array($data) ? $data : null;
            };

            $header  = $b64decode($parts[0]);
            $claims  = $b64decode($parts[1]);
            if ($claims === null) {
                log_message('error', 'SSO Authentication: Failed to decode JWT payload.');
                return null;
            }

            if (($claims['exp'] ?? 0) < time()) {
                log_message('error', 'SSO Authentication: JWT Token is expired.');
                return null;
            }

            if (isset($claims['nbf']) && $claims['nbf'] > time()) {
                log_message('error', 'SSO Authentication: JWT Token is not valid yet.');
                return null;
            }

            $alg = $header['alg'] ?? 'none';
            if ($alg == "none") {
                log_message('error', 'SSO Authentication: Algorithm "' . $alg . '" is not allowed.');
                return null;
            }
        }

        // Common checks (both modes)
        if (isset($claims['iat']) && $claims['iat'] < (time() - 86400)) {
            log_message('error', 'SSO Authentication: Token is older than 24 hours.');
            return null;
        }

        if (isset($claims['typ']) && $claims['typ'] !== 'Bearer') {
            log_message('error', 'SSO Authentication: JWT Token is no Bearer Token.');
            return null;
        }

        return $claims;
    }

    /**
     * Update user fields from JWT claims where override_on_update is enabled.
     *
     * @param int    $user_id
     * @param array  $claim_map
     * @param array  $values    Associative array of field => value from the JWT
     *
     * @return void
     */
    private function _update_user_from_claims(int $user_id, array $mapped): void {
        $updates = [];
        $claim_map = $this->config->item('auth_headers_claim_config', 'sso');
        foreach ($claim_map as $db_field => $cfg) {
            if (!empty($cfg['override_on_update']) && $mapped[$db_field] !== null) {
                $updates[$db_field] = $mapped[$db_field];
            }
        }

        if (!empty($updates)) {
            $this->user_model->update_sso_claims($user_id, $updates);
        }
    }

    /**
     * Helper to create a user if it does not exist.
     * 
     * @param array  $mapped       All DB field => value pairs extracted from JWT claims
     * @param string $external_identifier Composite key JSON {iss, sub} — stored once, never updated
     *
     * @return void
     */
    private function _create_user(array $mapped, string $external_identifier) {
        if (empty($mapped['user_email']) || empty($mapped['user_callsign'])) {
            log_message('error', 'SSO Authentication: Missing email or callsign claim in access token.');
            $this->_sso_error();
        }
        if (empty($mapped['user_name'])) {
            log_message('error', 'SSO Authentication: Missing username claim in access token.');
            $this->_sso_error();
        }

        // $club_id = $this->config->item('auth_header_club_id', 'sso') ?: ''; // TODO: Add support to add a user to a clubstation

        $this->load->model('user_model');
        $result = $this->user_model->add(
            $mapped['user_name']                              ?? '',
            bin2hex(random_bytes(64)),                           // password is always random
            $mapped['user_email']                             ?? '',
            3,                                                   // user_type: never admin via SSO
            $mapped['user_firstname']                         ?? '',
            $mapped['user_lastname']                          ?? '',
            $mapped['user_callsign']                          ?? '',
            $mapped['user_locator']                           ?? '',
            $mapped['user_timezone']                          ?? 24,
            $mapped['user_measurement_base']                  ?? 'M',
            $mapped['dashboard_map']                          ?? 'Y',
            $mapped['user_date_format']                       ?? 'Y-m-d',
            $mapped['user_stylesheet']                        ?? 'darkly',
            $mapped['user_qth_lookup']                        ?? '0',
            $mapped['user_sota_lookup']                       ?? '0',
            $mapped['user_wwff_lookup']                       ?? '0',
            $mapped['user_pota_lookup']                       ?? '0',
            $mapped['user_show_notes']                        ?? 1,
            $mapped['user_column1']                           ?? 'Mode',
            $mapped['user_column2']                           ?? 'RSTS',
            $mapped['user_column3']                           ?? 'RSTR',
            $mapped['user_column4']                           ?? 'Band',
            $mapped['user_column5']                           ?? 'Country',
            $mapped['user_show_profile_image']                ?? '0',
            $mapped['user_previous_qsl_type']                 ?? '0',
            $mapped['user_amsat_status_upload']               ?? '0',
            $mapped['user_mastodon_url']                      ?? '',
            $mapped['user_default_band']                      ?? 'ALL',
            $mapped['user_default_confirmation']              ?? 'QL',
            $mapped['user_qso_end_times']                     ?? '0',
            $mapped['user_qso_db_search_priority']            ?? 'Y',
            $mapped['user_quicklog']                          ?? '0',
            $mapped['user_quicklog_enter']                    ?? '0',
            $mapped['user_language']                          ?? 'english',
            $mapped['user_hamsat_key']                        ?? '',
            $mapped['user_hamsat_workable_only']              ?? '',
            $mapped['user_iota_to_qso_tab']                   ?? '',
            $mapped['user_sota_to_qso_tab']                   ?? '',
            $mapped['user_wwff_to_qso_tab']                   ?? '',
            $mapped['user_pota_to_qso_tab']                   ?? '',
            $mapped['user_sig_to_qso_tab']                    ?? '',
            $mapped['user_dok_to_qso_tab']                    ?? '',
            $mapped['user_station_to_qso_tab']                ?? 0,
            $mapped['user_lotw_name']                         ?? '',
            $mapped['user_lotw_password']                     ?? '',
            $mapped['user_eqsl_name']                         ?? '',
            $mapped['user_eqsl_password']                     ?? '',
            $mapped['user_clublog_name']                      ?? '',
            $mapped['user_clublog_password']                  ?? '',
            $mapped['user_winkey']                            ?? '0',
            $mapped['on_air_widget_enabled']                  ?? '',
            $mapped['on_air_widget_display_last_seen']        ?? '',
            $mapped['on_air_widget_show_only_most_recent_radio'] ?? '',
            $mapped['qso_widget_display_qso_time']            ?? '',
            $mapped['dashboard_banner']                       ?? '',
            $mapped['dashboard_solar']                        ?? '',
            $mapped['global_oqrs_text']                       ?? '',
            $mapped['oqrs_grouped_search']                    ?? '',
            $mapped['oqrs_grouped_search_show_station_name']  ?? '',
            $mapped['oqrs_auto_matching']                     ?? '',
            $mapped['oqrs_direct_auto_matching']              ?? '',
            $mapped['user_dxwaterfall_enable']                ?? '',
            $mapped['user_qso_show_map']                      ?? '',
            0,                                                   // clubstation
            $external_identifier,                                       // external_account
        );

        switch ($result) {
            case EUSERNAMEEXISTS:
                log_message('error', 'SSO Authentication: The SSO Integration tried to create a new User because the Username was not found. But the Username already exists. This should not happen as the user should be looked up by the same username before. Check your user provisioning and claims mapping configuration. Otherwise create an issue on https://github.com/wavelog/wavelog');
                $this->_sso_error(__("Something went terribly wrong. Check the error log."));
                break;
            case EEMAILEXISTS:
                log_message('error', 'SSO Authentication: The SSO Integration tried to create a new User because the Username was not found. But the E-mail for the new User already exists for an existing user. Check for existing Wavelog users with the same e-mail address as the one provided by your IdP.');
                $this->_sso_error(__("Something went terribly wrong. Check the error log."));
                break;
            case OK:
                return;
        }
    }

    /**
     * Helper to set flashdata and redirect to login with an error message. We use this a lot in the SSO login process, so we need a helper for this.
     * 
     * @param string|null $message
     * 
     * @return void
     */
    private function _sso_error($message = null) {
        if ($message === null) {
            $message = __("SSO Config Error. Check error log.");
        }
        $this->session->set_flashdata('error', $message);
        redirect('user/login');
        die;
    }
}
