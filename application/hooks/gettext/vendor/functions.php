<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * functions.php
 *
 * This files contains all needed functions that you need for your application.
 *
 * @package 	CodeIgniter
 * @category 	Hooks\Helpers
 * @author 	Kader Bouyakoub <bkader@mail.com>
 * @link 	https://github.com/bkader
 * @link 	https://twitter.com/KaderBouyakoub
 */

if ( ! function_exists('current_language')) {
	/**
	 * Returns current used language
	 * @param 	mixed 	one argument, one return, multiple argument, array returned.
	 * @return 	mixed 	the key of language array or an array.
	 */
	function current_language()
	{
		global $CFG;

		$return = $CFG->item('current_language');

		// In case no arguments are passed.
		if (empty(func_get_args()))
		{
			return $return;
		}

		$args = func_get_args();
		is_array($args[0]) && $args = $arg[0];

		if (count($args) == 1)
		{
			return $return[$args[0]];
		}

		return array_intersect_key($return, array_flip($args));
	}
}

if ( ! function_exists('default_language')) {
	/**
	 * Returns default used language
	 * @param 	mixed 	one argument, one return, multiple argument, array returned.
	 * @return 	mixed 	the key of language array or an array.
	 */
	function default_language()
	{
		global $CFG;
		$return = $CFG->item('default_language');

		// In case no arguments are passed.
		if (empty(func_get_args()))
		{
			return $return;
		}

		$args = func_get_args();
		is_array($args[0]) && $args = $arg[0];

		if (count($args) == 1)
		{
			return $return[$args[0]];
		}

		return array_intersect_key($return, array_flip($args));
	}
}

if ( ! function_exists('client_language')) {
	/**
	 * Returns client used language
	 * @param 	mixed 	one argument, one return, multiple argument, array returned.
	 * @return 	mixed 	the key of language array or an array.
	 */
	function client_language($key = NULL)
	{
		global $CFG;
		$return = $CFG->item('client_language');

		// In case no arguments are passed.
		if (empty(func_get_args()))
		{
			return $return;
		}

		$args = func_get_args();
		is_array($args[0]) && $args = $arg[0];

		if (count($args) == 1)
		{
			return $return[$args[0]];
		}

		return array_intersect_key($return, array_flip($args));
	}
}

if ( ! function_exists('languages')) {
	/**
	 * Returns an array of available languages
	 * @param 	mixed 	string, strings or array
	 * @return 	mixed
	 */
	function languages()
	{
		global $CFG;
		$return = $CFG->item('languages');

		// In case no arguments are passed.
		if ( ! empty($args = func_get_args()))
		{
			$args = func_get_args();
			is_array($args[0]) && $args = $arg[0];

			$_return = array();
			foreach ($return as $folder => $lang)
			{
				if (count($args) == 1)
				{
					$_return[$folder] = $lang[$args[0]];
				}
				else
				{
					$_return[$folder] = array_intersect_key($lang, array_flip($args));
				}
			}

			empty($_return) OR $return = $_return;
		}

		return $return;
	}
}

if ( ! function_exists('valid_language')) {
	/**
	 * Checks whether the language is available or not
	 * @param 	string 	$folder 	language folder name
	 * @return 	boolean
	 */
	function valid_language($folder = 'english')
	{
		return (isset(languages()[$folder]));
	}
}

if ( ! function_exists('switch_language')) {
	/**
	 * Change site's language
	 * @param 	strong 	$code 	language's code to use
	 * @return 	bool
	 */
	function switch_language($code = 'en')
	{
		$gtx = new Gettext;
		return $gtx->change($code);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('line')) {
	/**
	 * Returns the requested string with optional domain and arguments
	 * @param 	string 	$msgid 	text to translate
	 * @param 	mixed 	$domain optional text domain to use
	 * @param 	mixed 	$args 	arguments to parse
	 * @return 	string
	 */
	function line($msgid, $domain = NULL, $args = NULL)
	{
		$msgstr = $domain ? T_dgettext($domain, $msgid) : T_gettext($msgid);
		return ($args) ? vsprintf($msgstr, (array) $args) : $msgstr;
	}
}

if ( ! function_exists('_t')) {
	/**
	 * Alias of the function above
	 */
	function _t($msgid, $domain = NULL, $args = NULL)
	{
		return line($msgid, $domain, $args);
	}
}

if ( ! function_exists('_e')) {
	/**
	 * Does the same job as line() function except that it echoes it
	 * instead of simply returning it.
	 */
	function _e($msgid, $domain = NULL, $args = NULL)
	{
		echo line($msgid, $domain, $args);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('nline')) {
	/**
	 * Use the plural form of gettext
	 * @param 	string 	$singular 	singular form of the string
	 * @param 	string 	$plural 	plural form of the string
	 * @param 	string 	$number 	the number to compare against
	 * @param 	string 	$domain 	optional text domain name
	 * @return 	string
	 */
	function nline($singular, $plural, $number, $domain = NULL)
	{
		if ($domain !== NULL) {
			$msgstr = T_dngettext($domain, $singular, $plural, $number);
		} else {
			$msgstr = T_ngettext($singular, $plural, $number);
		}

		return sprintf($msgstr, (int) $number);
	}
}

if ( ! function_exists('_n')) {
	/**
	 * Alias of the function above
	 */
	function _n($singular, $plural, $number, $domain = NULL)
	{
		return nline($singular, $plural, $number, $domain);
	}
}

if ( ! function_exists('_en')) {
	/**
	 * Does the same job as nline() function except that it echoes it
	 * instead of simply returning it.
	 */

	function _en($singular, $plural, $number, $domain = NULL)
	{
		echo nline($singular, $plural, $number, $domain);
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists('xline')) {
	/**
	 * Retrieves translated string with gettext context
	 * @param 	string 	$msgid 		text to translate
	 * @param 	string 	$context 	context information for the translators
	 * @param 	string 	$domain 	optional text domain name
	 * @return 	string
	 */
	function xline($msgid, $context, $domain = NULL)
	{
		if ($domain !== NULL) {
			return T_dpgettext($domain, $context, $msgid);
		} else {
			return T_pgettext($domain, $msgid);
		}
	}
}

if ( ! function_exists('_x')) {
	/**
	 * Alias of the function above
	 */
	function _x($msgid, $context, $domain = NULL)
	{
		return xline($msgid, $context, $domain);
	}
}

if ( ! function_exists('_ex')) {
	/**
	 * Does the same job as xline() function except that it echoes it
	 * instead of simply returning it.
	 */
	function _ex($msgid, $context, $domain = NULL)
	{
		echo xline($msgid, $context, $domain);
	}
}

/* End of file functions.php */
/* Location: ./application/hooks/gettext/vendor/functions.php */