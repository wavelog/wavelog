<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Include php-gettext library provided by launchpad
 * See: https://launchpad.net/php-gettext/
 */
require_once __DIR__.'/vendor/php-gettext/gettext.php';

/**
 * Gettext Class
 *
 * This class is called before CodeIgniter loads so it enables the user of
 * php_gettext on your application
 *
 * @package 	CodeIgniter
 * @category 	None
 * @author 	Kader Bouyakoub <bkader@mail.com>
 * @link 	https://github.com/bkader
 * @link 	https://twitter.com/KaderBouyakoub
 */
class Gettext
{
	/**
	 * Configuration array
	 * @var array
	 */
	private $config = array(
		'enabled'   => TRUE,
		'default'   => NULL,
		'domain'    => 'messages',
		'languages' => array('english'),
		'cookie'    => 'language',
		'session'   => NULL,
	);

	/**
	 * List of all possible languages details.
	 * @var array
	 */
	private $languages;

	/**
	 * List of available languages
	 * @var array
	 */
	private $site_languages;

	/**
	 * Default language details
	 * @var array
	 */
	private $default;

	/**
	 * Client language details
	 * @var array
	 */
	private $client;

	/**
	 * Current language details
	 * @var array
	 */
	private $current;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $CFG;

		// Load configuration file
		$CFG->load('gettext', TRUE, TRUE);
		if ( ! empty($config = $CFG->item('gettext')))
		{
			foreach ($config as $key => $val)
			{
				$this->config[str_replace('gettext_', '', $key)] = $config[$key];
			}
		}

		// All languages details.
		$languages = $this->_get_languages();

		// Prepare our available languages array & add config item if not set.
		$site_languages = $this->_get_site_languages();
		if ( ! $CFG->item('languages')) {
			$CFG->set_item('languages', $site_languages);
		}

		// We abord the class if:
		// 1. Gettext is disabled
		// 2. There is only a single language available
		// 3. No cookie name or no session name are provided

		if ( ! $this->config['enabled'] 
			OR count($this->site_languages) <= 1 
			OR (empty($this->config['cookie']) 
						&& empty($this->config['session'])))
		{
			return;
		}
		
		if ( ! empty($this->config['cookie']))
		{
			$this->config['cookie'] = config_item('cookie_prefix').str_replace(config_item('cookie_prefix'), '', $this->config['cookie']);
		}

		// Set site default language and add the config item.
		$default = $this->_get_default_language();
		if ( ! $CFG->item('default_language')) {
			$CFG->set_item('default_language', $default);
		}

		// Set client language
		$client = $this->_get_client_language();
		if ( ! $CFG->item('client_language')) {
			$CFG->set_item('client_language', $client);
		}

		// Site site current language and config item if not set.
		$current = $this->_get_current_language();
		if ( ! $CFG->item('current_language')) {
			$CFG->set_item('current_language', $current);
		}

		// Make sure gettext domain is never empty
		empty($this->config['domain']) && $this->config['domain'] = 'messages';

		// Start using our gettext
        putenv('LANG='.$current['gettext']);
        T_setlocale(LC_MESSAGES, $current['gettext']);
        T_bindtextdomain($this->config['domain'], APPPATH.'locale');
        T_bind_textdomain_codeset($this->config['domain'], 'UTF-8');
        T_textdomain($this->config['domain']);
        // $this->set_textdomain($this->config['domain']);

        // Change language in APPPATH/config/config.php
        $CFG->set_item('language', $current['folder']);

        // Includes our functions
        include __DIR__.'/vendor/functions.php';
	}

	// ------------------------------------------------------------------------

	/**
	 * Dummy methods only so the hooks starts.
	 *
	 * @access 	public
	 * @param 	none
	 * @return 	void
	 */
	public function initialize() {}

	// ------------------------------------------------------------------------

	/**
	 * Returns a config item
	 * @access 	public
	 * @param 	string 	$item 	the item to return
	 * @return 	mixed
	 */
	public function config($item = NULL)
	{
		if ($item && isset($this->config[$item])) {
			return $this->config[$item];
		}

		return $this->config;
	}

	// ------------------------------------------------------------------------
	// !SETTERS
	// ------------------------------------------------------------------------

	/**
	 * Returns the list of all languages details
	 * @access 	protected
	 * @param 	none
	 * @return 	array
	 */
	private function _get_languages()
	{
		// Are they already cached? return them.
		if ( ! empty($this->languages))
		{
			return $this->languages;
		}

		$this->languages = $this->config['languages'];

		return $this->languages;
	}

	/**
	 * Returns a detailed list of enabled languages
	 * @access 	private
	 * @param 	none
	 * @return 	array
	 */
	private function _get_site_languages()
	{
		// Are they cached? Return theme.
		if ( ! empty($this->site_languages))
		{
			return $this->site_languages;
		}

		// Cache them before returning.
		$this->site_languages = $this->config['languages'];

		return $this->site_languages;

	}

	/**
	 * Set default language
	 * @access 	private
	 * @param 	none
	 * @return 	array
	 */
	private function _get_default_language()
	{
		// Is the language already cached? Return it.
		if ( ! empty($this->default))
		{
			return $this->default;
		}

		// Get the language from the config file.
		global $CFG;
		$default = $CFG->item('language');

		// Use default language set in APPPATH/config/config.php
		empty($this->config['default']) && $this->config['default'] = $default;

		// Make sure the selected default language is available
		if ( ! isset($this->site_languages[$this->config['default']]))
		{
			$this->config['default'] = $default;
		}

		$this->default = $this->_get_site_languages()[$this->config['default']];
		return $this->default;
	}

	/**
	 * Sets client language
	 * @access 	private
	 * @param 	none
	 * @return 	array
	 */
	private function _get_client_language()
	{
		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$code = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			$lang = $this->find_by('code', $code) ?: $this->default;
		} else {
			$code = 'en';
			$lang = $this->find_by('code', $code);
		}
		// $lang OR $lang = $this->default;
		return $lang;
	}

	/**
	 * Sets current language
	 * @access 	private
	 * @param 	none
	 * @return 	array
	 */
	private function _get_current_language()
	{
		global $IN;

		$folder = NULL;

		// Check if any cookie is set
		if ($cookie = $IN->cookie($this->config['cookie'], TRUE))
		{
			$folder = $cookie;
		}

		// Check session in case we are using it
		elseif (isset($_SESSION[$this->config['session']]))
		{
			$folder = $_SESSION[$this->config['session']];
		}

		// If neither cookie nor session are found, we use default one
		else
		{
			$folder = $this->_get_client_language()['folder'];
		}

		// Prepare our current language
		$current = $this->default;

		// We make sure the language is available
		if (isset($this->_get_site_languages()[$folder]))
		{
			$current = $this->languages[$folder];
		}

		// Use cookie if enabled
		if ( ! empty($this->config['cookie']))
		{
			$IN->set_cookie(str_replace(config_item('cookie_prefix'), '', $this->config['cookie']), $current['folder'], 2678400);
		}

		// In case we use session
		elseif ( ! empty($this->config['session']))
		{
			$_SESSION[$this->config['session']] = $current['folder'];
		}

		return $current;
	}

	// ------------------------------------------------------------------------
	// !FINDERS
	// ------------------------------------------------------------------------

	/**
	 * Search through languages array
	 * @access 	public
	 * @param 	string 	$field 	field to compare to
	 * @param 	string 	$match 	value to compare field to
	 * @return 	array
	 */
	public function find_by($field = 'folder', $match = 'english')
	{
		foreach($this->languages as $lang)
		{
			if (isset($lang[$field]) && $lang[$field] === $match)
			{
				return $lang;
			}
		}

		return NULL;
	}

	/**
	 * Unlike the method above, this one searches inside all languages
	 * even unavailable ones.
	 * @access 	public
	 * @param 	string 	$field 	field to compare to
	 * @param 	string 	$match 	value to compare field to
	 * @return 	array
	 */
	public function get_by($field = 'folder', $match = 'english')
	{
		// If PHP>=5.5.0
		if (version_compare(PHP_VERSION, '5.5.0') >= 0)
		{
			$languages = $this->_get_languages();
			$values = array_values($languages);
			$key = array_search($match, array_column($languages, $field));

			if ($key)
			{
				return $values[$key];
			}
		}

		foreach($this->_get_languages() as $lang)
		{
			if (isset($lang[$field]) && $lang[$field] === $match)
			{
				return $lang;
			}
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Change current language
	 * @access 	public
	 * @param 	string 	$code 	language code to use
	 * @return 	boolean
	 */
	public function change($code = 'en')
	{
		// We make sure the language is not the same as the current
		if ($code === $this->current['code'])
		{
			return TRUE;
		}

		// We make sure the language exists
		if ($lang = $this->find_by('code', $code))
		{
			global $IN;
			// If the use of cookies is ON
			if ($this->config['cookie'] !== NULL)
			{
				$IN->set_cookie(str_replace(config_item('cookie_prefix'), '', $this->config['cookie']), $lang['folder'], 2678400);
			}

			// In case COOKIE are off but SESSION is on
			elseif ($this->config['session'] !== NULL)
			{
				$_SESSION[$this->config['session']] = $lang['folder'];
			}

			// Change now current language
			$this->current = $lang;

			return TRUE;
		}

		return FALSE;
	}
}

/* End of file Gettext.php */
/* Location: ./application/hooks/gettext/Gettext.php */
