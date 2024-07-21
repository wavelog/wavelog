<?php

/**
 * Includes needed stuff
 */

require_once('includes/install_config/install_lib.php');
require_once('includes/install_config/install_config.php');

require_once('includes/gettext/gettext.php');
require_once('includes/gettext/gettext_conf.php');

require_once('includes/core/core_class.php');
require_once('includes/core/database_class.php');

$core = new Core();
$database = new Database();

include('includes/interface_assets/triggers.php');

/**
 * Gettext Implementation
 */

/**
 * save all available languages
 * @var array $languages
 */
$languages = $gt_conf['languages'];

// if we come with a get call we can switch the language cookie
if (isset($_GET['lang'])) {
	switch_lang($_GET['lang']);
	header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
	exit();
}

// get the browsers language if no cookie exists and set one
if (!isset($_COOKIE[$gt_conf['lang_cookie']])) {
	$browser_language = _get_client_language();
	setcookie($gt_conf['lang_cookie'], $browser_language['gettext']);
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
// get the language from the cookie
$language = $_COOKIE[$gt_conf['lang_cookie']];

// and set the locale for gettext
T_setlocale(LC_MESSAGES, $language);

?>
<!DOCTYPE html>
<html lang="<?= $language; ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<title><?= __("Install | Wavelog"); ?></title>
	<link rel="icon" type="image/x-icon" href="../favicon.ico">

	<link rel="stylesheet" href="../assets/css/darkly/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/installer.css">
	<link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
	<link rel="stylesheet" href="../assets/css/ldbtn.min.css">
	<link rel="stylesheet" href="../assets/css/loading.min.css">

	<script type="text/javascript" src="../assets/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="../assets/js/jquery-3.3.1.min.js"></script>
</head>