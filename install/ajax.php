<?php

// Target for Ajax Calls

require_once('includes/install_config/install_lib.php');
require_once('includes/install_config/install_config.php');

require_once('includes/gettext/gettext.php');
require_once('includes/gettext/gettext_conf.php');

require_once('includes/core/core_class.php');
require_once('includes/core/database_class.php');

$core = new Core();
$database = new Database();

require_once('includes/interface_assets/triggers.php');