<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 *--------------------------------------------------------------------------
 * SSO / Header Authentication Settings
 *--------------------------------------------------------------------------
 *
 * Copy this file to sso.php and adjust the values to your environment.
 * This file is loaded automatically when auth_header_enable = true in config.php.
 *
 * Documentation: https://docs.wavelog.org/admin-guide/configuration/thirdparty-authentication/
 */

/**
 * --------------------------------------------------------------------------
 * Debug Mode
 * --------------------------------------------------------------------------
 * 
 * When enabled, the decoded JWT token will be logged on every login attempt.
 * This is useful for troubleshooting claim mapping and token forwarding issues.
 * Requires 'log_threshold' to be set to 2 (debug) or higher in config.php.
 * 
 * DON'T FORGET TO DISABLE THIS IN PRODUCTION ENVIRONMENTS, AS JWT TOKENS MAY CONTAIN SENSITIVE INFORMATION!
 */
$config['auth_header_debug_jwt'] = false;


/**
 *--------------------------------------------------------------------------
 * Automatic User Creation
 *--------------------------------------------------------------------------
 *
 * When enabled, Wavelog will automatically create a local user account the
 * first time a valid JWT token is received for an unknown user. The account
 * is created with a random password.
 *
 * Disable this if you want to provision users manually before they can log in.
 * It's recommended to keep this enabled in most cases and do access control 
 * management through your identity provider.
 *
 * Recommendation: true
 */
$config['auth_header_create'] = true;


/**
 *--------------------------------------------------------------------------
 * Allow Direct Login
 *--------------------------------------------------------------------------
 *
 * When SSO is enabled, the standard Wavelog login form can be hidden. Default
 * behavior is to show the login form alongside the SSO button, allowing users to choose.
 * Set this to false to hide the login form and force users to authenticate exclusively through SSO.
 *
 * Recommendation: depends on your needs. You can disable direct login for a more seamless SSO experience, but enable 
 * it if you want to allow admins or users without SSO access to log in through the standard form.
 */
$config['auth_header_allow_direct_login'] = false;


/**
 * --------------------------------------------------------------------------
 * Hide Password Field in User Profile
 * --------------------------------------------------------------------------
 * 
 * When enabled, the password field in the user profile edit page is hidden for users authenticated through SSO.
 * While there are legit use cases for allowing users to set a local password (e.g. as a backup login method), 
 * hiding the password field can help avoid confusion and reinforce the idea that the account is managed through the identity provider.
 */
$config['auth_header_hide_password_field'] = true;


/**
 * --------------------------------------------------------------------------
 * Locked Data Badge
 * --------------------------------------------------------------------------
 * 
 * HTML snippet for a badge indicating that a field is locked and managed through the Identity Provider. This is shown next to fields in the user profile that are mapped to JWT claims and not allowed to be changed manually.
 * You can customize the appearance and tooltip text as needed. Leave empty to use the default.
 */
$config['auth_header_locked_data_badge'] = "";
$config['auth_header_locked_data_tip'] = "";


/**
 *--------------------------------------------------------------------------
 * Access Token Header
 *--------------------------------------------------------------------------
 *
 * The name of the HTTP header that contains the JWT access token forwarded
 * by your reverse proxy or identity provider (e.g. OAuth2 Proxy, mod_auth_oidc).
 *
 * Note: PHP converts HTTP headers to uppercase and replaces hyphens with
 * underscores, prefixed with HTTP_. For example, the header
 * "X-Forwarded-Access-Token" becomes "HTTP_X_FORWARDED_ACCESS_TOKEN".
 */
$config['auth_header_accesstoken'] = "HTTP_X_FORWARDED_ACCESS_TOKEN";


/**
 *--------------------------------------------------------------------------
 * SSO Login Button Text
 *--------------------------------------------------------------------------
 *
 * The label shown on the SSO login button on the Wavelog login page.
 */
$config['auth_header_text'] = "Login with SSO";


/**
 *--------------------------------------------------------------------------
 * Logout URL
 *--------------------------------------------------------------------------
 *
 * URL to redirect the user to after logging out of Wavelog. Leave empty
 * to redirect to the standard Wavelog login page while keeping the SSO session.
 * This is default since logging out of Wavelog does not necessarily mean logging out of the identity provider.
 *
 * When using OAuth2 Proxy in front of Keycloak, the URL must first hit the
 * OAuth2 Proxy sign-out endpoint, which then redirects to the Keycloak
 * end-session endpoint. The whitelist_domains of OAuth2 Proxy must include
 * the Keycloak domain.
 *
 * Example (OAuth2 Proxy + Keycloak):
 * $config['auth_header_url_logout'] = 'https://log.example.org/oauth2/sign_out'
 *     . '?rd=https://auth.example.org/realms/example/protocol/openid-connect/logout';
 * 
 * Recommendation: Keep it empty
 */
$config['auth_header_url_logout'] = "";


/**
 *--------------------------------------------------------------------------
 * JWKS URI (Signature Verification)
 *--------------------------------------------------------------------------
 *
 * URL of the JWKS endpoint of your identity provider. Wavelog uses this to
 * fetch the public keys and cryptographically verify the JWT signature on
 * every login. This is strongly recommended in production.
 *
 * Leave empty to skip signature verification (legacy / trusted-proxy mode).
 * Only disable verification if your reverse proxy fully manages authentication
 * and you fully trust the forwarded token without additional validation.
 *
 * Example (Keycloak):
 * $config['auth_header_jwks_uri'] = 'https://auth.example.org/realms/example/protocol/openid-connect/certs';
 * 
 * Recommendation: Set this to the JWKS endpoint of your identity provider for enhanced security.
 */
$config['auth_header_jwks_uri'] = "";


/**
 *--------------------------------------------------------------------------
 * JWT Claim Mapping
 *--------------------------------------------------------------------------
 *
 * Maps Wavelog database fields to JWT claim names. Each key is a column
 * name in the users table. The value is an array with the following options:
 *
 *   'claim'              => The JWT claim name to read the value from.
 *
 *   'override_on_update' => If true, the field is updated from the JWT on
 *                           every login. If false, the value is only written
 *                           once when the account is created.
 *                           Set to false for fields the user should be able
 *                           to change independently (e.g. username).
 *
 *   'allow_manual_change'=> If true, the user can edit this field in their
 *                           Wavelog profile. If false, the field is locked
 *                           and managed exclusively by the identity provider.
 *
 * You can add any additional column from the users table here. Fields not
 * listed will use their default values on account creation and will not be
 * touched on subsequent logins.
 *
 * The following fields are required for account creation and must be present:
 *   - user_name
 *   - user_email
 *   - user_callsign
 */
$config['auth_headers_claim_config'] = [
    'user_name'      => [
        'claim' => 'preferred_username',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
    'user_email'     => [
        'claim' => 'email',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
    'user_callsign'  => [
        'claim' => 'callsign',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
    'user_locator'  => [
        'claim' => 'locator',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
    'user_firstname' => [
        'claim' => 'given_name',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
    'user_lastname'  => [
        'claim' => 'family_name',
        'override_on_update' => true,
        'allow_manual_change' => false
    ],
];
