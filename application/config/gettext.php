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
| We set a default language if no cookie or session is available.
|
*/
$config['gettext_default'] = 'english';

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
| used on the site and in the installer. 
|
| Checklist to add a new language:
|	- Add the new language to this array
|	- Add the language at the bottom of application/views/debug/index.php
|	- Add the language at the array in install/includes/gettext/gettext_conf.php
|	- Add the language at the bottom of install/includes/interface_assets/footer.php
|
*/
$config['languages'] = array(

	'albanian' => array(
		'name'      => 'Shqip',
		'name_en'   => 'Albanian',
		'folder'    => 'albanian',
		'locale'    => 'sq',
		'gettext'   => 'sq',
		'direction' => 'ltr',
		'code'      => 'sq',
		'flag'      => 'al',
	),
	'bosnian' => array(
		'name'      => 'Bosanski',
		'name_en'   => 'Bosnian',
		'folder'    => 'bosnian',
		'locale'    => 'bs',
		'gettext'   => 'bs',
		'direction' => 'ltr',
		'code'      => 'bs',
		'flag'      => 'ba',
	),
	'bulgarian' => array(
		'name'      => 'Български',
		'name_en'   => 'Bulgarian',
		'folder'    => 'bulgarian',
		'locale'    => 'bg-BG',
		'gettext'   => 'bg_BG',
		'direction' => 'ltr',
		'code'      => 'bg',
		'flag'      => 'bg',
	),
	'chinese_simplified' => array(
		'name'      => '中文（简体）',
		'name_en'   => 'Chinese (Simplified)',
		'folder'    => 'chinese_simplified',
		'locale'    => 'zh-CN',
		'gettext'   => 'zh_CN',
		'direction' => 'ltr',
		'code'      => 'zh-Hans',
		'flag'      => 'cn',
	),
	'croatian' => array(
		'name'      => 'Hrvatski',
		'name_en'   => 'Croatian',
		'folder'    => 'croatian',
		'locale'    => 'hr',
		'gettext'   => 'hr',
		'direction' => 'ltr',
		'code'      => 'hr',
		'flag'      => 'hr',
	),
	'czech' => array(
		'name'      => 'Čeština',
		'name_en'   => 'Czech',
		'folder'    => 'czech',
		'locale'    => 'cs-CZ',
		'gettext'   => 'cs_CZ',
		'direction' => 'ltr',
		'code'      => 'cs',
		'flag'      => 'cz',
	),
	'dutch' => array(
		'name'      => 'Nederlands',
		'name_en'   => 'Dutch',
		'folder'    => 'dutch',
		'locale'    => 'nl-NL',
		'gettext'   => 'nl_NL',
		'direction' => 'ltr',
		'code'      => 'nl',
		'flag'      => 'nl',
	),
	'english' => array(
		'name'      => 'English',
		'name_en'   => 'English',
		'folder'    => 'english',
		'locale'    => 'en-US',
		'gettext'   => 'en_US',
		'direction' => 'ltr',
		'code'      => 'en',
		'flag'      => 'us',
	),
	'estonian' => array(
		'name'      => 'Eesti',
		'name_en'   => 'Estonian',
		'folder'    => 'estonian',
		'locale'    => 'et',
		'gettext'   => 'et',
		'direction' => 'ltr',
		'code'      => 'et',
		'flag'      => 'ee',
	),
	'finnish' => array(
		'name'      => 'Suomi',
		'name_en'   => 'Finnish',
		'folder'    => 'finnish',
		'locale'    => 'fi-FI',
		'gettext'   => 'fi_FI',
		'direction' => 'ltr',
		'code'      => 'fi',
		'flag'      => 'fi',
	),
	'french' => array(
		'name'      => 'Français',
		'name_en'   => 'French',
		'folder'    => 'french',
		'locale'    => 'fr-FR',
		'gettext'   => 'fr_FR',
		'direction' => 'ltr',
		'code'      => 'fr',
		'flag'      => 'fr',
	),
	'german' => array(
		'name'      => 'Deutsch',
		'name_en'   => 'German',
		'folder'    => 'german',
		'locale'    => 'de-DE',
		'gettext'   => 'de_DE',
		'direction' => 'ltr',
		'code'      => 'de',
		'flag'      => 'de',
	),
	'greek' => array(
		'name'      => 'Ελληνικά',
		'name_en'   => 'Greek',
		'folder'    => 'greek',
		'locale'    => 'el-GR',
		'gettext'   => 'el_GR',
		'direction' => 'ltr',
		'code'      => 'el',
		'flag'      => 'gr',
	),
	'italian' => array(
		'name'      => 'Italiano',
		'name_en'   => 'Italian',
		'folder'    => 'italian',
		'locale'    => 'it-IT',
		'gettext'   => 'it_IT',
		'direction' => 'ltr',
		'code'      => 'it',
		'flag'      => 'it',
	),
	'latvian' => array(
		'name'      => 'Latviešu',
		'name_en'   => 'Latvian',
		'folder'    => 'latvian',
		'locale'    => 'lv',
		'gettext'   => 'lv',
		'direction' => 'ltr',
		'code'      => 'lv',
		'flag'      => 'lv',
	),
	'lithuanian' => array(
		'name'      => 'Lietuvių',
		'name_en'   => 'Lithuanian',
		'folder'    => 'lithuanian',
		'locale'    => 'lt',
		'gettext'   => 'lt',
		'direction' => 'ltr',
		'code'      => 'lt',
		'flag'      => 'lt',
	),
	'montenegrin' => array(
		'name'      => 'Crnogorski',
		'name_en'   => 'Montenegrin',
		'folder'    => 'montenegrin',
		'locale'    => 'cnr',
		'gettext'   => 'cnr',
		'direction' => 'ltr',
		'code'      => 'cnr',
		'flag'      => 'me',
	),
	'polish' => array(
		'name'      => 'Polski',
		'name_en'   => 'Polish',
		'folder'    => 'polish',
		'locale'    => 'pl-PL',
		'gettext'   => 'pl_PL',
		'direction' => 'ltr',
		'code'      => 'pl',
		'flag'      => 'pl',
	),
	'portuguese' => array(
		'name'      => 'Português',
		'name_en'   => 'Portuguese',
		'folder'    => 'portuguese',
		'locale'    => 'pt-PT',
		'gettext'   => 'pt_PT',
		'direction' => 'ltr',
		'code'      => 'pt',
		'flag'      => 'pt',
	),
	'russian' => array(
		'name'      => 'Русский',
		'name_en'   => 'Russian',
		'folder'    => 'russian',
		'locale'    => 'ru-RU',
		'gettext'   => 'ru_RU',
		'direction' => 'ltr',
		'code'      => 'ru',
		'flag'      => 'ru',
	),
	'serbian' => array(
		'name'      => 'Srpski',
		'name_en'   => 'Serbian',
		'folder'    => 'serbian',
		'locale'    => 'sr',
		'gettext'   => 'sr',
		'direction' => 'ltr',
		'code'      => 'sr',
		'flag'      => 'rs',
	),
	'spanish' => array(
		'name'      => 'Español',
		'name_en'   => 'Spanish',
		'folder'    => 'spanish',
		'locale'    => 'es-ES',
		'gettext'   => 'es_ES',
		'direction' => 'ltr',
		'code'      => 'es',
		'flag'      => 'es',
	),
	'swedish' => array(
		'name'      => 'Svenska',
		'name_en'   => 'Swedish',
		'folder'    => 'swedish',
		'locale'    => 'sv-SE',
		'gettext'   => 'sv_SE',
		'direction' => 'ltr',
		'code'      => 'sv',
		'flag'      => 'se',
	),
	'turkish' => array(
		'name'      => 'Türkçe',
		'name_en'   => 'Turkish',
		'folder'    => 'turkish',
		'locale'    => 'tr-TR',
		'gettext'   => 'tr_TR',
		'direction' => 'ltr',
		'code'      => 'tr',
		'flag'      => 'tr',
	),
	'armenian' => array(
		'name'      => 'Հայերեն',
		'name_en'   => 'Armenian',
		'folder'    => 'armenian',
		'locale'    => 'hy',
		'gettext'   => 'hy',
		'direction' => 'ltr',
		'code'      => 'hy',
		'flag'      => 'am',
	)
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
