<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Gettext library config
 * @package 	CodeIgniter\CI-Gettext
 * @category 	Configuration
 * @author 	Kader Bouyakoub <bkader@mail.com> 
 * 			MODIFIED BY Fabian Berg <mail@hb9hil.org>
 * @link 	http://www.bkader.com/
 */

/*
| -------------------------------------------------------------------
|  Enable/Disable Gettext Library
| -------------------------------------------------------------------
| Setting this to TRUE turns ON the use of gettext and makes your
| website multilingual. Which means that it will checks if current
| user's supported language is available and automatically sets the
| language in configuration.
| Setting this to FALSE will still let you use the Gettext library
| but the site will be in default language.
*/
$config['gettext_enabled'] = TRUE;

/*
| -------------------------------------------------------------------
|  Default Language
| -------------------------------------------------------------------
| Normally, you should set default language in config.php file but
| you can override this if you want.
| Set to NULL to use default one.
*/
$config['gettext_default'] = NULL;

/*
| -------------------------------------------------------------------
| Gettext default domain
| -------------------------------------------------------------------
| This allows you to set a custom domain to be used by gettext.
| Gettext *.MO files are located inside LC_MESSAGES folder like so:
| English: ./application/language/english/LC_MESSAGES/{$domain}.mo
| French: ./application/language/french/LC_MESSAGES/{$domain}.mo
|
| Note: by default, gettext_domain is set to 'messages' if this
| option is set to NULL below
*/
$config['gettext_domain'] = NULL;

/*
| -------------------------------------------------------------------
|  Site languages
| -------------------------------------------------------------------
| A list of enabled languages. These are the language that will be
| used on the site.
*/
$config['gettext_languages'] = array(
	'arabic',
	'english',
	'french',
	'german',
	'italian',
	'portuguese',
	'spanish',
);

/*
| -------------------------------------------------------------------
|  Gettext library Session & Cookie use
| -------------------------------------------------------------------
| If one of these configurations is enabled, the language name (folder
| name) will be stored in whether a session or a cookie BUT NOT BOTH
| You must know that only one is allowed, session OR cookie. If both
| are enabled, COOKIES are privileged.
*/
$config['gettext_session'] = NULL;
$config['gettext_cookie']  = 'language';

/* End of file gettext.php */
/* Location: ./application/config/gettext.php */