<?php

/** 
 * Basic Configuration for the gettext feature in the Wavelog Installer.
 * Defines some basic parameters. Nothing fancy
 * 
 * Author: HB9HIL, 2024
 * 
 */

$gt_conf['default_domain'] = 'installer';

$gt_conf['default_lang'] = 'english';

$gt_conf['lang_cookie'] = 'install_lang';

$gt_conf['languages'] = array(

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
    )
);
