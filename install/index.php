<?php
/*
New Wavelog Installer

This installer guides a user through the install process and all
necessary parameters for the new Wavelog Installation.

HB9HIL - January 2024
DJ7NT - Docker Readiness - April 2024
*/

// #########################################################
// Unattended install
// #########################################################
if (file_exists('config_unattended.php')) {
	require_once('install_unattended.php');
	run_unattended_install();
	return;
}


// #########################################################
// PRECONFIGURATION
// #########################################################

// Config Paths
$db_config_path = '../application/config/';
if (isset($_ENV['CI_ENV'])) {
	$db_config_path ='../application/config/'.$_ENV['CI_ENV'].'/';
}
$db_file_path = $db_config_path . "database.php";

// if you need to disabled all button locks you can create a root_mode file in the /install directory
$root_mode_file = '.root_mode';

// Wanted Pre-Check Parameters
// PHP
$min_php_version = '7.4.0';
$max_execution_time = 600;		// Seconds
$max_upload_file_size = 8;  	// Megabyte
$post_max_size = 8;				// Megabyte
$req_allow_url_fopen = '1';		// 1 = on

// Array of PHP modules to check
global $required_php_modules;
$required_php_modules = [
	'php-curl' => ['condition' => isExtensionInstalled('curl')],
	'php-mysql' => ['condition' => isExtensionInstalled('mysqli')],
	'php-mbstring' => ['condition' => isExtensionInstalled('mbstring')],
	'php-xml' => ['condition' => isExtensionInstalled('xml')],
	'php-zip' => ['condition' => isExtensionInstalled('zip')],
];

// MariaDB / MySQL
$mariadb_version = 10.1;
$mysql_version = 5.7;

// ######################################################### END OF PRECONFIGURATION

// Function to check if a PHP extension is installed
function isExtensionInstalled($extensionName)
{
	return in_array($extensionName, get_loaded_extensions());
}

function delDir($dir)
{
	$files = glob($dir . '*', GLOB_MARK);
	foreach ($files as $file) {
		if (substr($file, -1) == '/') {
			if (file_exists($file)) {
				delDir($file);
			}
		} else {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}
}

if (file_exists($db_file_path)) {
	delDir(getcwd());
	header("../");
	exit;
}

if (file_exists($root_mode_file)) {
	$root_mode = true;
} else {
	$root_mode = false;
}

// Only load the classes in case the user submitted the form
if ($_POST) {

	// Load the classes and create the new objects
	require_once('includes/core_class.php');
	require_once('includes/database_class.php');

	$core = new Core();
	$database = new Database();

	if ($_POST['database_check'] ?? false == true) {

		$result = $database->database_check($_POST);
		echo $result;
		exit;
	} else {
		// Validate the post data
		if ($core->validate_post($_POST) == true) {

			// First create the database, then create tables, then write config file
			if ($database->create_database($_POST) == false) {
				$message = $core->show_message('error', "The database could not be created, please verify your settings.");
			} elseif ($database->create_tables($_POST) == false) {
				$message = $core->show_message('error', "The database tables could not be created, please verify your settings.");
			} elseif ($core->write_config($_POST) == false) {
				$message = $core->show_message('error', "The database configuration file could not be written, please chmod ".$db_config_path."/database.php file to 777");
			}

			if ($core->write_configfile($_POST) == false) {
				$message = $core->show_message('error', "The config configuration file could not be written, please chmod ".$db_config_path."/config.php file to 777");
			}

			// If no errors, redirect to registration page
			if (!isset($message)) {
				sleep(1);
				$ch = curl_init();
				$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
				list($realHost,) = explode(':', $_SERVER['HTTP_HOST']);
				$wavelog_url = $protocol . "://" . $realHost . ":" . $_SERVER['SERVER_PORT'];
				curl_setopt($ch, CURLOPT_URL, $wavelog_url);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_setopt($ch, CURLOPT_URL, $wavelog_url . "/index.php/update/dxcc");
				$result = curl_exec($ch);
				delDir(getcwd());
				header('Location: ' . $protocol . "://" . $_SERVER['HTTP_HOST'] . '/' . $_POST['directory']);
				exit;
			}
		} else {
			$message = $core->show_message('error', 'Not all fields have been filled in correctly. The host, username, password, and database name are required.');
		}
	}
}
global $wavelog_url;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<title>Install | Wavelog</title>
	<link rel="icon" type="image/x-icon" href="../favicon.ico">

	<link rel="stylesheet" href="../assets/css/darkly/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/overrides.css">
	<link rel="stylesheet" href="../assets/fontawesome/css/all.css">

	<script type="text/javascript" src="../assets/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="../assets/js/jquery-3.3.1.min.js"></script>
</head>

<?php if (is_writable($db_config_path)) : ?>
	<?php if (isset($message)) {
		echo '<p class="error">' . $message . '</p>'; // TODO Integrate Message into the design, Dialog???
	} ?>

	<body>
		<div class="container" style="max-width: 1000px; margin-top: 8rem; ">

			<div class="card mt-4" style="min-height: 650px; margin: 0 auto;">

				<div class="card-header">
					<ul class="nav nav-tabs nav-fill card-header-tabs">
						<li class="nav-item">
							<a class="nav-link active disabled" id="welcome-tab" data-bs-toggle="tab" href="#welcome" role="tab" aria-controls="welcome" aria-selected="true">1. Welcome</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" id="precheck-tab" data-bs-toggle="tab" href="#precheck" role="tab" aria-controls="precheck" aria-selected="false">2. Pre Checks</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" id="configuration-tab" data-bs-toggle="tab" href="#configuration" role="tab" aria-controls="configuration" aria-selected="false">3. Configuration</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" id="database-tab" data-bs-toggle="tab" href="#database" role="tab" aria-controls="database" aria-selected="false">4. Database</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" id="firstuser-tab" data-bs-toggle="tab" href="#firstuser" role="tab" aria-controls="firstuser" aria-selected="false">5. First User</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" id="finish-tab" data-bs-toggle="tab" href="#finish" role="tab" aria-controls="finish" aria-selected="false">6. Finish</a>
						</li>
					</ul>
				</div>

				<div class="card-body">
					<form id="install_form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<div class="tab-content" id="myTabContent">

							<!-- Tab 1: Welcome -->
							<div class="tab-pane fade show active p-3" id="welcome" role="tabpanel" aria-labelledby="welcome-tab">
								<div class="row" style="margin-top: 20px;">
									<div class="col-md-6">
										<img src="../assets/logo/wavelog_logo_darkly.png" alt="" style="max-width: 100%; height: auto;">
									</div>

									<div class="col-md-6">
										<h4 style="margin-top: 50px;">Welcome to the Wavelog Installer</h4>
										<p style="margin-top: 50px;">This installer will guide you through the necessary steps for the installation of Wavelog. <br>Wavelog is a powerful web-based amateur radio logging software. Follow the steps in each tab to configure and install Wavelog on your server.</p>
										<p>If you encounter any issues or have questions, refer to the documentation (<a href="https://www.github.com/wavelog/wavelog/wiki" target="_blank">Wiki</a>) or community forum (<a href="https://www.github.com/wavelog/wavelog/discussions" target="_blank">Discussions</a>) on Github for assistance.</p>
										<p>Thank you for installing Wavelog!</p>
									</div>
								</div>
							</div>

							<!-- Tab 2: Pre-Checks -->
							<div class="tab-pane fade" id="precheck" role="tabpanel" aria-labelledby="precheck-tab">
								<div class="row justify-content-center" style="margin-top: 3rem;">
									<div class="col-md-5 mx-auto">
										<p class="border-bottom mb-2"><b>PHP Modules</b></p>
										<?php
										// Initialize the tracker
										$allChecksPassed = 'ok';
										?>
										<table width="100%">
											<tr>
												<td>Version</td>
												<td><?php echo 'min. ' . $min_php_version; ?></td>
												<td>
													<?php if (version_compare(PHP_VERSION, $min_php_version) <= 0) { ?>
														<span class="badge text-bg-danger"><?php echo PHP_VERSION; ?></span>
													<?php } else { ?>
														<span class="badge text-bg-success"><?php echo PHP_VERSION; ?></span>
													<?php } ?>
												</td>
											</tr>
											<?php
											foreach ($required_php_modules as $moduleName => $moduleData) {
												$condition = $moduleData['condition'];
												if (!$condition) {
													$allChecksPassed = 'failed';
												}
											?>
												<tr>
													<td><?php echo $moduleName; ?></td>
													<td></td>
													<td>
														<span class="badge text-bg-<?php echo $condition ? 'success' : 'danger'; ?>">
															<?php echo $condition ? 'Installed' : 'Not Installed'; ?>
														</span>
													</td>
												</tr>
											<?php
											}
											?>
										</table>
										<p class="border-bottom mb-2" style="margin-top: 2rem;"><b>PHP Settings</b></p>
										<table width="100%">
											<tr>
												<td>max_execution_time</td>
												<td><?php echo '> ' . $max_execution_time . ' s'; ?></td>
												<td>
													<?php
													$maxExecutionTime = ini_get('max_execution_time');
													if ($maxExecutionTime >= $max_execution_time) { ?>
														<span class="badge text-bg-success"><?php echo $maxExecutionTime . ' s'; ?></span>
													<?php } else {
														if ($allChecksPassed != 'failed') {  // Check current value before changing to 'warning'
															$allChecksPassed = 'warning';
														} ?>
														<span class="badge text-bg-warning"><?php echo $maxExecutionTime; ?></span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>max_upload_file_size</td>
												<td><?php echo '> ' . $max_upload_file_size . 'M'; ?></td>
												<td>
													<?php
													$maxUploadFileSize = ini_get('upload_max_filesize');
													$maxUploadFileSizeBytes = (int)($maxUploadFileSize) * (1024 * 1024); // convert to bytes
													if ($maxUploadFileSizeBytes >= ($max_upload_file_size * 1024 * 1024)) { // compare with given value in bytes
													?>
														<span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
													<?php } else {
														if ($allChecksPassed != 'failed') {  // Check current value before changing to 'warning'
															$allChecksPassed = 'warning';
														} ?>
														<span class="badge text-bg-warning"><?php echo $maxUploadFileSize; ?></span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>post_max_size</td>
												<td><?php echo '> ' . $post_max_size . 'M'; ?></td>
												<td>
													<?php
													$maxUploadFileSize = ini_get('post_max_size');
													$maxUploadFileSizeBytes = (int)($maxUploadFileSize) * (1024 * 1024); // convert to bytes
													if ($maxUploadFileSizeBytes >= ($post_max_size * 1024 * 1024)) { // compare with given value in bytes
													?>
														<span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
													<?php } else {
														if ($allChecksPassed != 'failed') {  // Check current value before changing to 'warning'
															$allChecksPassed = 'warning';
														} ?>
														<span class="badge text-bg-warning"><?php echo $maxUploadFileSize; ?></span>
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td>allow_url_fopen</td>
												<td>On</td>
												<td>
													<?php
													$get_allow_url_fopen = ini_get('allow_url_fopen');
													if ($get_allow_url_fopen == $req_allow_url_fopen) {
													?>
														<span class="badge text-bg-success">On</span>
													<?php } else {
														$allChecksPassed = 'failed'; ?>
														<span class="badge text-bg-danger">Off</span>
													<?php } ?>
												</td>
											</tr>
										</table>
									</div>

									<div class="col-md-5 mb-4 mx-auto"> <!-- MySQL / MariaDB -->
										<p class="border-bottom mb-2"><b>MySQL / MariaDB</b></p>
										<table width="100%">
											<tr>
												<td>Min. MySQL Version: </td>
												<td><span class="badge text-bg-info"><?php echo $mysql_version; ?></span></td>
											</tr>
											<tr>
												<td>or</td>
												<td></td>
											</tr>
											<tr>
												<td>Min. MariaDB Version: </td>
												<td><span class="badge text-bg-info"><?php echo $mariadb_version; ?></span></td>
											</tr>
										</table>
										<p style="margin-top: 10px; margin-bottom: 100px;">You can test your MySQL/MariaDB Version in Step 4</p>

										<?php if ($allChecksPassed == 'failed') { ?>
											<div class="alert alert-danger d-flex flex-column align-items-center" role="alert">
												<p class="mb-2 border-bottom">Some Checks have failed!</p>
												<p class="mb-2">Check your PHP settings and install missing modules if necessary.</p>
												<p class="mb-0">After that, you have to restart your webserver and start the installer again.</p>
											</div>
										<?php } else if ($allChecksPassed == 'warning') { ?>
											<div class="alert alert-warning d-flex flex-column align-items-center" role="alert">
												<p class="mb-2 border-bottom">You have some warnings!</p>
												<p class="mb-2">Some of the settings are not optimal. You can proceed with the installer but be aware that you could run into problems while using Wavelog.</p>
											</div>
										<?php } else if ($allChecksPassed == 'ok') { ?>
											<div class="alert alert-success d-flex align-items-center" role="alert">
												<p class="mb-0">All Checks are OK. You can continue.</p>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>

							<!-- Tab 3: Configuration -->
							<div class="tab-pane fade" id="configuration" role="tabpanel" aria-labelledby="configuration-tab">
								<div class="row">
									<div class="col" style="margin-top: 70px;">
										<img src="assets/images/gears_icon.png" alt="" style="max-width: 80%; height: auto; margin-left: 20px;">
									</div>
									<div class="col">
										<p>Configure some basic parameters for your wavelog instance. You can change them later in 'application/config/config.php'</p>
										<div class="mb-3">
											<label for="directory" class="form-label">Directory<i id="directory_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="The 'Directory' is basically your subfolder of the webroot In normal conditions the prefilled value is doing it's job. It also can be empty."></i></label>
											<div class="input-group">
												<span class="input-group-text" id="main-url"><?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . "/"; ?></span>
												<input type="text" id="directory" value="<?php echo substr(str_replace("index.php", "", str_replace("/install/", "", $_SERVER['REQUEST_URI'])), 1); ?>" class="form-control" name="directory" aria-describedby="main-url" />
											</div>
										</div>
										<div class="mb-3 position-relative">
											<label for="websiteurl" class="form-label">Website URL<i id="websiteurl_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="This is the complete URL where your Wavelog Instance will be available. If you run this installer locally but want to place Wavelog behind a Reverse Proxy with SSL you should type in the new URL here (e.g. https://mywavelog.example.org/ instead of http://192.168.1.100/). Don't forget to include the directory from above."></i></label>
											<input type="text" id="websiteurl" value="<?php echo $_SERVER['REQUEST_SCHEME']; ?>://<?php echo str_replace("index.php", "", $_SERVER['HTTP_HOST'] . str_replace("/install/", "", $_SERVER['REQUEST_URI'])); ?>" class="form-control" name="websiteurl" />
											<div class="invalid-tooltip">
												This field can't be empty!
											</div>
										</div>
										<div class="mb-3 position-relative">
											<label for="locator" class="form-label">Default Gridsquare/Locator<i id="gridsquare_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="This is the default maidenhead locator which is used as falback. You can use the locator of your Home QTH."></i></label>
											<input type="text" id="locator" placeholder="HA44AA" class="form-control" name="locator" />
											<div class="invalid-tooltip">
												Type in a valid locator
											</div>
										</div>
										<div class="mb-3">
											<label for="global_call_lookup" class="form-label">Optional: Global Callbook Lookup<i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="This configuration is optional. The callsign lookup will be available for all users of this installation. You can choose between QRZ.com and HamQTH. While HamQTH also works without username and password, you will need credentials for QRZ.com. To also get the Call Locator in QRZ.com you'll need an XML subscription. HamQTH does not always provide the locator information."></i></label>
											<select id="global_call_lookup" class="form-select" name="global_call_lookup">
												<option value="hamqth" selected>HamQTH</option>
												<option value="qrz">QRZ.com</option>
											</select>
										</div>
										<div class="row">
											<div class="col-md-3">
												<div class="mb-3">
													<label for="callbook_username" class="form-label mt-2">Username</label>
												</div>
												<div>
													<label for="callbook_password" class="form-label mt-1">Password</label>
												</div>
											</div>
											<div class="col-md-9">
												<div class="mb-3">
													<input type="text" id="callbook_username" placeholder="Callbook Username" class="form-control" name="callbook_username" />
												</div>
												<div>
													<input type="password" id="callbook_password" placeholder="Callbook Password" class="form-control" name="callbook_password" />
												</div>
											</div>
										</div>

									</div>
								</div>
							</div>

							<!-- Tab 4: Database -->
							<div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
								<div class="row">
									<div class="col" style="margin-top: 70px;">
										<img src="assets/images/database_sign.png" alt="" style="max-width: 80%; height: auto; margin-left: 20px;">
									</div>
									<div class="col" style="margin-top: 40px;">
										<p>To properly install Wavelog you already should have setup a mariadb/mysql database. Provide the parameters here.</p>
										<div class="row">
											<div class="col">
												<div class="mb-3">
													<label for="db_hostname" class="form-label">Hostname or IP<i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="Usually 'localhost'. Optional with '...:[port]'. Default Port: 3306"></i></label>
													<input type="text" id="db_hostname" placeholder="localhost" class="form-control" name="db_hostname" />
												</div>
											</div>
											<div class="col">
												<div class="mb-3">
													<label for="db_name" class="form-label">Database Name<i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="Name of the Database"></i></label>
													<input type="text" id="db_name" placeholder="wavelog" class="form-control" name="db_name" />
												</div>
											</div>
										</div>
										<div class="mb-3">
											<label for="db_username" class="form-label">Username<i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="Username of the Database User which has full access to the database."></i></label>
											<input type="text" id="db_username" placeholder="waveloguser" class="form-control" name="db_username" />
										</div>
										<div class="mb-3">
											<label for="db_password" class="form-label">Password<i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="Password of the Database User"></i></label>
											<input type="password" id="db_password" placeholder="supersecretpassword" class="form-control" name="db_password" />
										</div>
										<div class="col">
											<button id="db_connection_test_button" type="button" class="btn btn-primary" onclick="db_connection_test()">Connection Test</button>
											<div class="mt-2 mb-2 alert" id="db_connection_testresult"></div>
										</div>
									</div>
								</div>
							</div>

							<!-- Tab 5: First User -->
							<div class="tab-pane fade" id="firstuser" role="tabpanel" aria-labelledby="firstuser-tab">
								<div class="row">
									<div class="col-md-8 mb-2">
										<p style="margin-top: 10px;">Now you can create your first user in Wavelog. Fill out all fields and click continue.<br>Make sure you use a proper password.</p>
									</div>
									<div class="col-md-4 mb-2">
										<div class="alert alert-danger" id="userform_warnings" style="display: none; margin-top: 10px;"></div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-6 mb-2">
										<div class="row">
											<div class="col">
												<label for="firstname" class="form-label">First Name</label>
												<input type="text" id="firstname" tabindex="1" placeholder="Ham" class="form-control" name="firstname" />
											</div>
											<div class="col">
												<label for="lastname" class="form-label">Last Name</label>
												<input type="text" id="lastname" tabindex="2" placeholder="Radio" class="form-control" name="lastname" />
											</div>
										</div>
									</div>
									<div class="col-md-6 mb-2">
										<div class="row">
											<div class="col">
												<label for="username" class="form-label">Username</label>
												<input type="text" id="username" tabindex="7" placeholder="ham.radio" class="form-control" name="username" />
											</div>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="callsign" class="form-label">Callsign</label>
										<input type="text" id="callsign" tabindex="3" placeholder="4W7EST" class="form-control" name="callsign" />
									</div>
									<div class="col-md-6 mb-2">
										<label for="password" class="form-label">Password</label>
										<input type="password" id="password" tabindex="8" placeholder="**********" class="form-control" name="password" />
									</div>
								</div>

								<div class="row">
									<div class="col-md-6 mb-2">
										<div class="row">
											<div class="col">
												<label for="city" class="form-label">City</label>
												<input type="text" id="city" tabindex="4" placeholder="City" class="form-control" name="city" />
											</div>
										</div>
									</div>
									<div class="col-md-6 mb-2">
										<label for="cnfm_password" class="form-label">Confirm Password</label>
										<input type="password" id="cnfm_password" tabindex="9" placeholder="**********" class="form-control" name="cnfm_password" />
									</div>
								</div>
								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="userlocator" class="form-label">Gridsquare/Locator</label>
										<input type="text" id="userlocator" tabindex="5" placeholder="HA44AA" class="form-control" name="userlocator" />
									</div>
									<div class="col-md-6 mb-2">
										<label for="user_email" class="form-label">E-Mail Address</label>
										<input type="email" id="user_email" tabindex="10" placeholder="ham.radio@example.com" class="form-control mb-2" name="user_email" />
									</div>
								</div>
								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="dxcc" class="form-label">DXCC</label>
										<select class="form-select" id="dxcc_id" name="dxcc" tabindex="6" aria-describedby="stationCallsignInputHelp">
											<option value="0" selected="">- None -</option>
											<option value="2">Abu Ail Is - A1 (Deleted DXCC)</option>
											<option value="3">Afghanistan - YA</option>
											<option value="4">Agalega &amp; St Brandon Islands - 3B7</option>
											<option value="5">Aland Islands - OH0</option>
											<option value="6">Alaska - KL7</option>
											<option value="7">Albania - ZA</option>
											<option value="8">Aldabra - VQ9/A (Deleted DXCC)</option>
											<option value="400">Algeria - 7X</option>
											<option value="9">American Samoa - KH8</option>
											<option value="10">Amsterdam &amp; St Paul Islands - FT5Z</option>
											<option value="11">Andaman &amp; Nicobar Islands - VU4</option>
											<option value="203">Andorra - C31</option>
											<option value="401">Angola - D2</option>
											<option value="12">Anguilla - VP2E</option>
											<option value="195">Annobon - 3C0</option>
											<option value="13">Antarctica - CE9</option>
											<option value="94">Antigua &amp; Barbuda - V2</option>
											<option value="100">Argentina - LU</option>
											<option value="14">Armenia - EK</option>
											<option value="91">Aruba - P4</option>
											<option value="205">Ascension Island - ZD8</option>
											<option value="15">Asiatic Russia - UA0</option>
											<option value="508">Austral Islands - FO/A</option>
											<option value="150">Australia - VK</option>
											<option value="206">Austria - OE</option>
											<option value="17">Aves Island - YV0</option>
											<option value="18">Azerbaijan - 4J</option>
											<option value="149">Azores - CU</option>
											<option value="60">Bahamas - C6A</option>
											<option value="304">Bahrain - A9</option>
											<option value="19">Bajo Nuevo - HK0 (Deleted DXCC)</option>
											<option value="20">Baker Howland Islands - KH1</option>
											<option value="21">Balearic Islands - EA6</option>
											<option value="490">Banaba Island - T33</option>
											<option value="305">Bangladesh - S2</option>
											<option value="62">Barbados - 8P</option>
											<option value="27">Belarus - EU</option>
											<option value="209">Belgium - ON</option>
											<option value="66">Belize - V3</option>
											<option value="416">Benin - TY</option>
											<option value="64">Bermuda - VP9</option>
											<option value="306">Bhutan - A5</option>
											<option value="23">Blenheim Reef - 1B (Deleted DXCC)</option>
											<option value="104">Bolivia - CP</option>
											<option value="520">Bonaire - PJ4</option>
											<option value="85">Bonaire, Curacao (neth Antilles) - PJ2/D (Deleted DXCC)</option>
											<option value="501">Bosnia-herzegovina - E7</option>
											<option value="402">Botswana - A2</option>
											<option value="24">Bouvet Island - 3Y/B</option>
											<option value="108">Brazil - PY</option>
											<option value="25">British North Borneo - ZC5 (Deleted DXCC)</option>
											<option value="26">British Somaliland - VQ6 (Deleted DXCC)</option>
											<option value="65">British Virgin Islands - VP2V</option>
											<option value="345">Brunei - V8</option>
											<option value="212">Bulgaria - LZ</option>
											<option value="480">Burkina Faso - XT</option>
											<option value="404">Burundi - 9U</option>
											<option value="312">Cambodia - XU</option>
											<option value="406">Cameroon - TJ</option>
											<option value="1">Canada - VE</option>
											<option value="28">Canal Zone - KZ5 (Deleted DXCC)</option>
											<option value="29">Canary Islands - EA8</option>
											<option value="409">Cape Verde - D4</option>
											<option value="69">Cayman Islands - ZF</option>
											<option value="30">Celebe &amp; Molucca Islands - PK6 (Deleted DXCC)</option>
											<option value="408">Central African Republic - TL</option>
											<option value="31">Central Kiribati - T31</option>
											<option value="32">Ceuta &amp; Melilla - EA9</option>
											<option value="410">Chad - TT</option>
											<option value="33">Chagos Islands - VQ9</option>
											<option value="34">Chatham Island - ZL7</option>
											<option value="512">Chesterfield Islands - FK/C</option>
											<option value="112">Chile - CE</option>
											<option value="318">China - BY</option>
											<option value="35">Christmas Island - VK9X</option>
											<option value="36">Clipperton Island - FO/C</option>
											<option value="38">Cocos (keeling) Island - VK9C</option>
											<option value="37">Cocos Island - TI9</option>
											<option value="116">Colombia - HK</option>
											<option value="39">Comoro Islands - FH8 (Deleted DXCC)</option>
											<option value="411">Comoros - D6</option>
											<option value="489">Conway Reef - 3D2/C</option>
											<option value="214">Corsica - TK</option>
											<option value="308">Costa Rica - TI</option>
											<option value="428">Cote D'ivoire - TU</option>
											<option value="40">Crete - SV9</option>
											<option value="497">Croatia - 9A</option>
											<option value="41">Crozet Island - FT5/W</option>
											<option value="70">Cuba - CO</option>
											<option value="517">Curacao - PJ2</option>
											<option value="215">Cyprus - 5B</option>
											<option value="503">Czech Republic - OK</option>
											<option value="218">Czechoslovakia - OK/D (Deleted DXCC)</option>
											<option value="42">Damao, Diu - CR8/D (Deleted DXCC)</option>
											<option value="414">Dem. Rep. Of The Congo - 9Q</option>
											<option value="221">Denmark - OZ</option>
											<option value="43">Desecheo Island - KP5</option>
											<option value="44">Desroches - VQ9/D (Deleted DXCC)</option>
											<option value="382">Djibouti - J2</option>
											<option value="45">Dodecanese - SV5</option>
											<option value="95">Dominica - J7</option>
											<option value="72">Dominican Republic - HI</option>
											<option value="344">Dprk (north Korea) - P5</option>
											<option value="513">Ducie Island - VP6/D</option>
											<option value="46">East Malaysia - 9M6</option>
											<option value="47">Easter Island - CE0Y</option>
											<option value="48">Eastern Kiribati - T32</option>
											<option value="120">Ecuador - HC</option>
											<option value="478">Egypt - SU</option>
											<option value="74">El Salvador - YS</option>
											<option value="223">England - G</option>
											<option value="49">Equatorial Guinea - 3C</option>
											<option value="51">Eritrea - E3</option>
											<option value="52">Estonia - ES</option>
											<option value="53">Ethiopia - ET</option>
											<option value="54">European Russia - UA</option>
											<option value="141">Falkland Islands - VP8</option>
											<option value="222">Faroe Islands - OY</option>
											<option value="55">Farquhar - VQ9/F (Deleted DXCC)</option>
											<option value="230">Federal Republic Of Germany - DL</option>
											<option value="56">Fernando De Noronha - PY0F</option>
											<option value="176">Fiji Islands - 3D2</option>
											<option value="224">Finland - OH</option>
											<option value="227">France - F</option>
											<option value="61">Franz Josef Land - R1F</option>
											<option value="57">French Equatorial Africa - FQ8 (Deleted DXCC)</option>
											<option value="63">French Guiana - FY</option>
											<option value="67">French India - FN8 (Deleted DXCC)</option>
											<option value="58">French Indo-china - FI8 (Deleted DXCC)</option>
											<option value="175">French Polynesia - FO</option>
											<option value="59">French West Africa - FF (Deleted DXCC)</option>
											<option value="420">Gabon - TR</option>
											<option value="71">Galapagos Islands - HC8</option>
											<option value="75">Georgia - 4L</option>
											<option value="229">German Democratic Republic - DM (Deleted DXCC)</option>
											<option value="81">Germany - DL/D (Deleted DXCC)</option>
											<option value="93">Geyser Reef - 1G (Deleted DXCC)</option>
											<option value="424">Ghana - 9G</option>
											<option value="233">Gibraltar - ZB2</option>
											<option value="99">Glorioso Island - FT/G</option>
											<option value="101">Goa - CR8/G (Deleted DXCC)</option>
											<option value="102">Gold Coast Togoland - ZD4 (Deleted DXCC)</option>
											<option value="236">Greece - SV</option>
											<option value="237">Greenland - OX</option>
											<option value="77">Grenada - J3</option>
											<option value="79">Guadeloupe - FG</option>
											<option value="103">Guam - KH2</option>
											<option value="105">Guantanamo Bay - KG4</option>
											<option value="76">Guatemala - TG</option>
											<option value="106">Guernsey - GU</option>
											<option value="107">Guinea - 3XA</option>
											<option value="109">Guinea-bissau - J5</option>
											<option value="129">Guyana - 8R</option>
											<option value="78">Haiti - HH</option>
											<option value="110">Hawaii - KH6</option>
											<option value="111">Heard Island - VK0H</option>
											<option value="80">Honduras - HR</option>
											<option value="321">Hong Kong - VR</option>
											<option value="239">Hungary - HA</option>
											<option value="242">Iceland - TF</option>
											<option value="113">Ifni - EA9/I (Deleted DXCC)</option>
											<option value="324">India - VU</option>
											<option value="327">Indonesia - YB</option>
											<option value="330">Iran - EP</option>
											<option value="333">Iraq - YI</option>
											<option value="245">Ireland - EI</option>
											<option value="114">Isle Of Man - GD</option>
											<option value="336">Israel - 4X</option>
											<option value="115">Italian Somali - I5 (Deleted DXCC)</option>
											<option value="248">Italy - I</option>
											<option value="117">Itu Hq - 4U1ITU</option>
											<option value="82">Jamaica - 6Y</option>
											<option value="118">Jan Mayen - JX</option>
											<option value="339">Japan - JA</option>
											<option value="119">Java - PK1 (Deleted DXCC)</option>
											<option value="122">Jersey - GJ</option>
											<option value="123">Johnston Island - KH3</option>
											<option value="342">Jordan - JY</option>
											<option value="124">Juan De Nova, Europa - FT/J</option>
											<option value="125">Juan Fernandez Islands - CE0Z</option>
											<option value="126">Kaliningrad - UA2</option>
											<option value="127">Kamaran Islands - VS9K (Deleted DXCC)</option>
											<option value="128">Karelo-finn Rep - UN1 (Deleted DXCC)</option>
											<option value="130">Kazakhstan - UN</option>
											<option value="430">Kenya - 5Z</option>
											<option value="131">Kerguelen Island - FT5/X</option>
											<option value="133">Kermadec Island - ZL8</option>
											<option value="468">Kingdom Of Eswatini - 3DA</option>
											<option value="134">Kingman Reef - KH5K (Deleted DXCC)</option>
											<option value="138">Kure Island - KH7K</option>
											<option value="139">Kuria Muria Island - VS9H (Deleted DXCC)</option>
											<option value="348">Kuwait - 9K</option>
											<option value="68">Kuwait/saudi Arabia Neut. Zone - 8Z5 (Deleted DXCC)</option>
											<option value="135">Kyrgyzstan - EX</option>
											<option value="142">Lakshadweep Islands - VU7</option>
											<option value="143">Laos - XW</option>
											<option value="145">Latvia - YL</option>
											<option value="354">Lebanon - OD</option>
											<option value="432">Lesotho - 7P</option>
											<option value="434">Liberia - EL</option>
											<option value="436">Libya - 5A</option>
											<option value="251">Liechtenstein - HB0</option>
											<option value="146">Lithuania - LY</option>
											<option value="147">Lord Howe Island - VK9L</option>
											<option value="254">Luxembourg - LX</option>
											<option value="152">Macao - XX9</option>
											<option value="153">Macquarie Island - VK0M</option>
											<option value="438">Madagascar - 5R</option>
											<option value="256">Madeira Islands - CT3</option>
											<option value="440">Malawi - 7Q</option>
											<option value="155">Malaya - VS2 (Deleted DXCC)</option>
											<option value="159">Maldives - 8Q</option>
											<option value="442">Mali - TZ</option>
											<option value="161">Malpelo Island - HK0/M</option>
											<option value="257">Malta - 9H</option>
											<option value="151">Malyj Vysotskij Island - R1M (Deleted DXCC)</option>
											<option value="164">Manchuria - C9 (Deleted DXCC)</option>
											<option value="166">Mariana Islands - KH0</option>
											<option value="167">Market Reef - OJ0</option>
											<option value="509">Marquesas Islands - FO/M</option>
											<option value="168">Marshall Islands - V7</option>
											<option value="84">Martinique - FM</option>
											<option value="444">Mauritania - 5T</option>
											<option value="165">Mauritius Island - 3B8</option>
											<option value="169">Mayotte - FH</option>
											<option value="171">Mellish Reef - VK9M</option>
											<option value="50">Mexico - XE</option>
											<option value="173">Micronesia - V6</option>
											<option value="174">Midway Island - KH4</option>
											<option value="177">Minami Torishima - JD/M</option>
											<option value="178">Minerva Reef - 1M (Deleted DXCC)</option>
											<option value="179">Moldova - ER</option>
											<option value="260">Monaco - 3A</option>
											<option value="363">Mongolia - JT</option>
											<option value="514">Montenegro - 4O</option>
											<option value="96">Montserrat - VP2M</option>
											<option value="446">Morocco - CN</option>
											<option value="180">Mount Athos - SV/A</option>
											<option value="181">Mozambique - C9</option>
											<option value="309">Myanmar - XZ</option>
											<option value="464">Namibia - V5</option>
											<option value="157">Nauru - C21</option>
											<option value="182">Navassa Island - KP1</option>
											<option value="369">Nepal - 9N</option>
											<option value="263">Netherlands - PA</option>
											<option value="183">Netherlands Borneo - PK5 (Deleted DXCC)</option>
											<option value="184">Netherlands New Guinea - JZ0 (Deleted DXCC)</option>
											<option value="162">New Caledonia - FK</option>
											<option value="170">New Zealand - ZL</option>
											<option value="16">New Zealand Subantarctic Islands - ZL9</option>
											<option value="186">Newfoundland Labrador - VO (Deleted DXCC)</option>
											<option value="86">Nicaragua - YN</option>
											<option value="187">Niger - 5U</option>
											<option value="450">Nigeria - 5N</option>
											<option value="188">Niue - E6</option>
											<option value="189">Norfolk Island - VK9N</option>
											<option value="191">North Cook Islands - E5/N</option>
											<option value="502">North Macedonia - Z3</option>
											<option value="265">Northern Ireland - GI</option>
											<option value="266">Norway - LA</option>
											<option value="192">Ogasawara - JD/O</option>
											<option value="193">Okinawa - KR6 (Deleted DXCC)</option>
											<option value="194">Okino Tori-shima - 7J1 (Deleted DXCC)</option>
											<option value="370">Oman - A4</option>
											<option value="372">Pakistan - AP</option>
											<option value="22">Palau - T8</option>
											<option value="510">Palestine - E4</option>
											<option value="196">Palestine (deleted) - ZC6 (Deleted DXCC)</option>
											<option value="197">Palmyra &amp; Jarvis Islands - KH5</option>
											<option value="88">Panama - HP</option>
											<option value="163">Papua New Guinea - P2</option>
											<option value="198">Papua Terr - VK9/P (Deleted DXCC)</option>
											<option value="132">Paraguay - ZP</option>
											<option value="493">Penguin Islands - ZS0 (Deleted DXCC)</option>
											<option value="243">People's Dem Rep Of Yemen - VS9A (Deleted DXCC)</option>
											<option value="136">Peru - OA</option>
											<option value="199">Peter 1 Island - 3Y/P</option>
											<option value="375">Philippines - DU</option>
											<option value="172">Pitcairn Island - VP6</option>
											<option value="269">Poland - SP</option>
											<option value="272">Portugal - CT</option>
											<option value="200">Portuguese Timor - CR8/T (Deleted DXCC)</option>
											<option value="505">Pratas Island - BV9P</option>
											<option value="201">Prince Edward &amp; Marion Islands - ZS8</option>
											<option value="202">Puerto Rico - KP4</option>
											<option value="376">Qatar - A7</option>
											<option value="137">Republic Of Korea - HL</option>
											<option value="522">Republic Of Kosovo - Z6</option>
											<option value="462">Republic Of South Africa - ZS</option>
											<option value="521">Republic Of South Sudan - Z8</option>
											<option value="412">Republic Of The Congo - TN</option>
											<option value="453">Reunion Island - FR</option>
											<option value="204">Revillagigedo - XF4</option>
											<option value="207">Rodriguez Island - 3B9</option>
											<option value="275">Romania - YO</option>
											<option value="460">Rotuma - 3D2/R</option>
											<option value="208">Ruanda-urundi - 9U (Deleted DXCC)</option>
											<option value="454">Rwanda - 9X</option>
											<option value="210">Saar - 9S4 (Deleted DXCC)</option>
											<option value="519">Saba &amp; St Eustatius - PJ5</option>
											<option value="211">Sable Island - CY0</option>
											<option value="516">Saint Barthelemy - FJ</option>
											<option value="250">Saint Helena - ZD7</option>
											<option value="249">Saint Kitts &amp; Nevis - V4</option>
											<option value="97">Saint Lucia - J6</option>
											<option value="213">Saint Martin - FS</option>
											<option value="252">Saint Paul Island - CY9</option>
											<option value="253">Saint Peter And Paul Rocks - PY0S</option>
											<option value="277">Saint Pierre &amp; Miquelon - FP</option>
											<option value="98">Saint Vincent - J8</option>
											<option value="190">Samoa - 5W</option>
											<option value="216">San Andres Island - HK0S</option>
											<option value="217">San Felix Islands - CE0X</option>
											<option value="278">San Marino - T7</option>
											<option value="219">Sao Tome &amp; Principe - S9</option>
											<option value="220">Sarawak - VS4 (Deleted DXCC)</option>
											<option value="225">Sardinia - IS0</option>
											<option value="378">Saudi Arabia - HZ</option>
											<option value="226">Saudi Arabia/iraq Neut Zone - 8Z4 (Deleted DXCC)</option>
											<option value="506">Scarborough Reef - BS7H</option>
											<option value="279">Scotland - GM</option>
											<option value="456">Senegal - 6W</option>
											<option value="296">Serbia - YT</option>
											<option value="228">Serrana Bank &amp; Roncador Cay - HK0/S (Deleted DXCC)</option>
											<option value="379">Seychelles Islands - S7</option>
											<option value="458">Sierra Leone - 9L</option>
											<option value="231">Sikkim - AC3 (Deleted DXCC)</option>
											<option value="381">Singapore - 9V</option>
											<option value="518">Sint Maarten - PJ7</option>
											<option value="255">Sint Maarten, Saba, St Eustatius - PJ7/D (Deleted DXCC)</option>
											<option value="504">Slovak Republic - OM</option>
											<option value="499">Slovenia - S5</option>
											<option value="185">Solomon Islands - H4</option>
											<option value="232">Somalia - T5</option>
											<option value="234">South Cook Islands - E5/S</option>
											<option value="235">South Georgia Island - VP0G</option>
											<option value="238">South Orkney Islands - VP8O</option>
											<option value="240">South Sandwich Islands - VP0S</option>
											<option value="241">South Shetland Islands - VP8H</option>
											<option value="244">Southern Sudan - ST0/D (Deleted DXCC)</option>
											<option value="246">Sov Military Order Of Malta - 1A0</option>
											<option value="281">Spain - EA</option>
											<option value="247">Spratly Islands - 1S</option>
											<option value="315">Sri Lanka - 4S</option>
											<option value="466">Sudan - ST</option>
											<option value="258">Sumatra - PK4 (Deleted DXCC)</option>
											<option value="140">Suriname - PZ</option>
											<option value="259">Svalbard - JW</option>
											<option value="515">Swains Island - KH8/S</option>
											<option value="261">Swan Island - KS4 (Deleted DXCC)</option>
											<option value="284">Sweden - SM</option>
											<option value="287">Switzerland - HB</option>
											<option value="384">Syria - YK</option>
											<option value="386">Taiwan - BU</option>
											<option value="262">Tajikistan - EY</option>
											<option value="264">Tangier - CN2 (Deleted DXCC)</option>
											<option value="470">Tanzania - 5H</option>
											<option value="507">Temotu Province - H40</option>
											<option value="267">Terr New Guinea - VK9/T (Deleted DXCC)</option>
											<option value="387">Thailand - HS</option>
											<option value="422">The Gambia - C5</option>
											<option value="268">Tibet - AC4 (Deleted DXCC)</option>
											<option value="511">Timor-leste - 4W</option>
											<option value="483">Togo - 5V7</option>
											<option value="270">Tokelau Islands - ZK3</option>
											<option value="160">Tonga - A3</option>
											<option value="271">Trieste - I1 (Deleted DXCC)</option>
											<option value="273">Trindade &amp; Martim Vaz Islands - PY0T</option>
											<option value="90">Trinidad &amp; Tobago - 9Y</option>
											<option value="274">Tristan Da Cunha &amp; Gough Islands - ZD9</option>
											<option value="276">Tromelin Island - FT/T</option>
											<option value="474">Tunisia - 3V</option>
											<option value="390">Turkey - TA</option>
											<option value="280">Turkmenistan - EZ</option>
											<option value="89">Turks &amp; Caicos Islands - VP5</option>
											<option value="282">Tuvalu - T2</option>
											<option value="286">Uganda - 5X</option>
											<option value="283">Uk Bases On Cyprus - ZC4</option>
											<option value="288">Ukraine - UR</option>
											<option value="391">United Arab Emirates - A6</option>
											<option value="289">United Nations Hq - 4U1UN</option>
											<option value="291">United States Of America - K</option>
											<option value="144">Uruguay - CX</option>
											<option value="285">Us Virgin Islands - KP2</option>
											<option value="292">Uzbekistan - UJ</option>
											<option value="158">Vanuatu - YJ</option>
											<option value="295">Vatican City - HV</option>
											<option value="148">Venezuela - YV</option>
											<option value="293">Viet Nam - 3W</option>
											<option value="297">Wake Island - KH9</option>
											<option value="294">Wales - GW</option>
											<option value="298">Wallis &amp; Futuna Islands - FW</option>
											<option value="488">Walvis Bay - ZS9 (Deleted DXCC)</option>
											<option value="299">West Malaysia - 9M2</option>
											<option value="301">Western Kiribati - T30</option>
											<option value="302">Western Sahara - S0</option>
											<option value="303">Willis Island - VK9W</option>
											<option value="492">Yemen - 7O</option>
											<option value="154">Yemen Arab Republic - 4W (Deleted DXCC)</option>
											<option value="482">Zambia - 9J</option>
											<option value="307">Zanzibar - VQ1 (Deleted DXCC)</option>
											<option value="452">Zimbabwe - Z2</option>
										</select>
									</div>
									<div class="col-md-6 mb-2">
										<label for="timezone" class="form-label">Timezone</label>
										<select id="timezone" tabindex="11" class="form-select" name="timezone">
											<?php
											// timezones
											$timezones = [
												['1', '-12.0', '(GMT-12:00)-International Date Line West'],
												['4', '-9.0', '(GMT-09:00)-Alaska'],
												['5', '-8.0', '(GMT-08:00)-Pacific Time (US & Canada); Tijuana'],
												['6', '-7.0', '(GMT-07:00)-Arizona'],
												['8', '-7.0', '(GMT-07:00)-Mountain Time (US & Canada)'],
												['13', '-5.0', '(GMT-05:00)-Bogota, Lima, Quito'],
												['15', '-5.0', '(GMT-05:00)-Indiana (East)'],
												['17', '-4.0', '(GMT-04:00)-La Paz'],
												['19', '-3.5', '(GMT-03:30)-Newfoundland'],
												['22', '-3.0', '(GMT-03:00)-Greenland'],
												['23', '-2.0', '(GMT-02:00)-Mid-Atlantic'],
												['0', '0.0', '(GMT+00:00)-Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London', true],
												['30', '1.0', '(GMT+01:00)-Brussels, Copenhagen, Madrid, Paris'],
												['31', '1.0', '(GMT+01:00)-Sarajevo, Skopje, Warsaw, Zagreb'],
												['35', '2.0', '(GMT+02:00)-Cairo'],
												['36', '2.0', '(GMT+02:00)-Harare, Pretoria'],
												['38', '2.0', '(GMT+02:00)-Jerusalem'],
												['39', '3.0', '(GMT+03:00)-Baghdad'],
												['41', '3.0', '(GMT+03:00)-Moscow, St. Petersburg, Volgograd'],
												['43', '3.5', '(GMT+03:30)-Tehran'],
												['44', '4.0', '(GMT+04:00)-Abu Dhabi, Muscat'],
												['45', '4.0', '(GMT+04:00)-Baku, Tbilisi, Yerevan'],
												['46', '4.5', '(GMT+04:30)-Kabul'],
												['51', '6.0', '(GMT+06:00)-Almaty, Novosibirsk'],
												['54', '6.5', '(GMT+06:30)-Rangoon'],
												['55', '7.0', '(GMT+07:00)-Bangkok, Hanoi, Jakarta'],
												['56', '7.0', '(GMT+07:00)-Krasnoyarsk'],
												['58', '8.0', '(GMT+08:00)-Irkutsk, Ulaan Bataar'],
												['59', '8.0', '(GMT+08:00)-Kuala Lumpur, Singapore'],
												['60', '8.0', '(GMT+08:00)-Perth'],
												['63', '9.0', '(GMT+09:00)-Seoul'],
												['64', '9.0', '(GMT+09:00)-Vakutsk'],
												['66', '9.5', '(GMT+09:30)-Darwin'],
												['69', '10.0', '(GMT+10:00)-Guam, Port Moresby'],
												['71', '10.0', '(GMT+10:00)-Vladivostok'],
												['74', '12.0', '(GMT+12:00)-Fiji, Kamchatka, Marshall Is.'],
												['76', '-11.0', '(GMT-11:00)-Midway Island, Samoa'],
												['77', '-10.0', '(GMT-10:00)-Hawaii'],
												['81', '-7.0', '(GMT-07:00)-Chihuahua, La Paz, Mazatlan'],
												['83', '-6.0', '(GMT-06:00)-Central America'],
												['84', '-6.0', '(GMT-06:00)-Central Time (US & Canada)'],
												['85', '-6.0', '(GMT-06:00)-Guadalajara, Mexico City, Monterrey'],
												['86', '-6.0', '(GMT-06:00)-Saskatchewan'],
												['88', '-5.0', '(GMT-05:00)-Eastern Time (US & Canada)'],
												['90', '-4.0', '(GMT-04:00)-Atlantic Time (Canada)'],
												['91', '-4.0', '(GMT-04:00)-Caracas, La Paz'],
												['92', '-4.0', '(GMT-04:00)-Santiago'],
												['94', '-3.0', '(GMT-03:00)-Brasilia'],
												['95', '-3.0', '(GMT-03:00)-Buenos Aires, Georgetown'],
												['98', '-1.0', '(GMT-01:00)-Azores'],
												['99', '-1.0', '(GMT-01:00)-Cape Verde Is.'],
												['100', '0.0', '(GMT+00:00)-Casablanca, Monrovia'],
												['102', '1.0', '(GMT+01:00)-Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna'],
												['103', '1.0', '(GMT+01:00)-Belgrade, Bratislava, Budapest, Ljubljana, Prague'],
												['106', '1.0', '(GMT+01:00)-West Central Africa'],
												['107', '2.0', '(GMT+02:00)-Athens, Beirut, Istanbul, Minsk'],
												['108', '2.0', '(GMT+02:00)-Bucharest'],
												['111', '2.0', '(GMT+02:00)-Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius'],
												['114', '3.0', '(GMT+03:00)-Kuwait, Riyadh'],
												['116', '3.0', '(GMT+03:00)-Nairobi'],
												['121', '5.0', '(GMT+05:00)-Ekaterinburg'],
												['122', '5.0', '(GMT+05:00)-Islamabad, Karachi, Tashkent'],
												['123', '5.5', '(GMT+05:30)-Chennai, Kolkata, Mumbai, New Delhi'],
												['124', '5.8', '(GMT+05:45)-Kathmandu'],
												['126', '6.0', '(GMT+06:00)-Astana, Dhaka'],
												['127', '6.0', '(GMT+06:00)-Sri Jayawardenepura'],
												['129', '7.0', '(GMT+07:00)-Bangkok, Hanoi, Jakarta'],
												['131', '8.0', '(GMT+08:00)-Beijing, Chongqing, Hong Kong, Urumqi'],
												['135', '8.0', '(GMT+08:00)-Taipei'],
												['136', '9.0', '(GMT+09:00)-Osaka, Sapporo, Tokyo'],
												['139', '9.5', '(GMT+09:30)-Adelaide'],
												['141', '10.0', '(GMT+10:00)-Brisbane'],
												['142', '10.0', '(GMT+10:00)-Canberra, Melbourne, Sydney'],
												['144', '10.0', '(GMT+10:00)-Hobart'],
												['146', '11.0', '(GMT+11:00)-Magadan, Solomon Is., New Caledonia'],
												['147', '12.0', '(GMT+12:00)-Auckland, Wellington'],
												['149', '13.0', '(GMT+13:00)-Nuku\'alofa'],
												['150', '-4.5', '(GMT-04:30)-Caracas'],
											];

											usort($timezones, function ($a, $b) {
												return strcmp($a[1], $b[1]);
											});

											// Loop through timezones to generate options
											foreach ($timezones as $timezone) {
												$value = $timezone[0];
												$label = $timezone[2];
												$selected = isset($timezone[3]) && $timezone[3] ? 'selected' : '';

												echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
											}
											?>
										</select>
									</div>

								</div>
							</div>

							<!-- Tab 6: Finish --> <!-- TODO Include Check Functions -->
							<div class="tab-pane fade" id="finish" role="tabpanel" aria-labelledby="finish-tab">
								<div class="row" style="margin-top: 80px;">
									<div class="col-md-5" style="margin-top: 50px;">
										<div class="d-flex justify-content-center">
											<div>
												<h4>Checklist</h4>
												<p class="ms-2"><i class="me-2 fas"></i>Configuration</p>
												<div class="ms-2 alert" id="configuration-warning" style="display: none;"></div>
												<p class="ms-2"><i class="me-2 fas"></i>Database</p>
												<div class="ms-2 alert" id="database-warning" style="display: none;"></div>
												<p class="ms-2"><i class="me-2 fas"></i>First User</p>
												<div class="ms-2 alert" id="firstuser-warning" style="display: none;"></div>
											</div>
										</div>
									</div>
									<div class="col">
										<div class="text-center">
											<h4 style="margin-top: 50px;">Nearly done!</h4>
											<p style="margin-top: 50px;">You prepared all neccessary steps.</p>
											<p>We now can install Wavelog. This process can take a few minutes.</p>
											<input class="btn btn-primary" type="submit" value="Install" id="submit" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="card-footer">
					<button type="button" id="BackButton" class="btn btn-primary float-start" style="display: none">Back</button>
					<button type="button" id="ContinueButton" class="btn btn-info float-end" style="display: none">Continue</button>
				</div>
			</div>
		</div>

		<script>
			// let continue_allowed = true;
			// console.log('initial continue_allowed');

			// We don't want to allow press Enter to trigger Events in the Installer
			// Too dangerous for an average $user
			document.addEventListener('keydown', function(event) {
				if (event.key === 'Enter') {
					event.preventDefault();
				}
			});

			function db_connection_test() {
				var db_hostname = $('#db_hostname').val();
				var db_username = $('#db_username').val();
				var db_password = $('#db_password').val();
				var db_name = $('#db_name').val();

				if (db_hostname === '' || db_username === '' || db_password === '' || db_name === '') {
					$('#db_connection_testresult').addClass('alert-danger');
					$('#db_connection_testresult').html('Error: All fields are required.');
					// continue_allowed = false;
					return;
				}

				var originalButtonText = $('#db_connection_test_button').html();
				$('#db_connection_test_button').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Connecting...').prop('disabled', true);

				clear_db_testresult();

				$.ajax({
					type: 'POST',
					url: 'index.php',
					data: {
						db_hostname: db_hostname,
						db_username: db_username,
						db_password: db_password,
						db_name: db_name,
						database_check: true
					},
					success: function(response) {
						$('#db_connection_testresult').html(response);
						if (response.indexOf('Error') !== -1) {
							$('#db_connection_testresult').addClass('alert-danger');
							$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
						} else {

							if (sql_version_checker(response) == true) {
								$('#db_connection_testresult').addClass('alert-success');
								$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
								// $('#ContinueButton').prop('disabled', false);
								$('#db_connection_testresult').html('Connection was successful and your database should be compatible <i class="fas fa-check-circle"></i>');
								continue_allowed = true;
							} else {
								$('#db_connection_testresult').addClass('alert-warning');
								$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
								// $('#ContinueButton').prop('disabled', false);
								$('#db_connection_testresult').html('Connection was successful but your database seems too old for Wavelog. You can try to continue but you could run into issues.</i>');
								continue_allowed = true;
							}
						}
					},
					error: function(error) {
						$('#db_connection_testresult').html('Error: ' + error.statusText);
						if ($('#db_connection_testresult').text().indexOf('Error') !== -1) {
							$('#db_connection_testresult').addClass('alert-danger');
						}
					}
				});
			}

			function clear_db_testresult() {
				$('#db_connection_testresult').html('');
				$('#db_connection_testresult').removeClass('alert-danger');
				$('#db_connection_testresult').removeClass('alert-success');
				$('#db_connection_testresult').removeClass('alert-warning');
			}

			function sql_version_checker(version_string) {
				let extracted_version = version_string.match(/^(\d+\.\d+)/);

				var min_mysql_version = <?php echo $mysql_version; ?>;
				var min_mariadb_version = <?php echo $mariadb_version; ?>;

				if (extracted_version[1] >= 10) { //probably MariaDB
					if (extracted_version[1] >= min_mariadb_version) {
						return true;
					}
				} else { // probably MySQL
					if (extracted_version[1] >= min_mysql_version) {
						return true;
					}
				}

				return false;

			}

			function isValidMaidenheadLocator(locator) {
				const maidenheadRegex = /^[A-R]{2}[0-9]{2}[A-X]{2}$/i;
				return maidenheadRegex.test(locator);
			}

			function isValidEmail(email) {
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailRegex.test(email);
			}

			// Check various user input in tab 3
			// websiteURL
			// const websiteUrlField = $('#websiteurl');
			// const LocatorField = $('#locator');

			// websiteUrlField.on('change', function() {
			// 	if (config_check()) {
			// 		continue_allowed = true;
			// 		console.log('continue allowed ln 758');
			// 	}
			// });

			// LocatorField.on('change', function() {
			// 	if (config_check()) {
			// 		continue_allowed = true;
			// 		console.log('continue allowed ln 765');
			// 	}
			// });

			// function config_check() {
			// 	var check1_ok = true;
			// 	var check2_ok = true;

			// 	if (websiteUrlField.val() == '') {
			// 		console.log('Websiteurl: ' + websiteUrlField.val());
			// 		websiteUrlField.addClass('is-invalid');
			// 		websiteUrlField.removeClass('is-valid');
			// 		if (root_mode == false) {
			// 			$('#ContinueButton').prop('disabled', true);
			// 			check1_ok = false;
			// 		} else {
			// 			// $('#ContinueButton').prop('disabled', false);
			// 			check1_ok = true;
			// 		}
			// 	} else {

			// 		websiteUrlField.addClass('is-valid');
			// 		websiteUrlField.removeClass('is-invalid');
			// 		// $('#ContinueButton').prop('disabled', false);
			// 		check1_ok = true;

			// 	}

			// 	if (!isValidMaidenheadLocator(LocatorField.val()) && LocatorField != '') {
			// 		console.log('Locator: ' + LocatorField.val());
			// 		LocatorField.addClass('is-invalid');
			// 		LocatorField.removeClass('is-valid');
			// 		if (root_mode == false) {
			// 			$('#ContinueButton').prop('disabled', true);
			// 			check2_ok = false;
			// 		} else {
			// 			// $('#ContinueButton').prop('disabled', false);
			// 			check2_ok = true;
			// 		}

			// 	} else {

			// 		LocatorField.removeClass('is-invalid');
			// 		LocatorField.addClass('is-valid');
			// 		// $('#ContinueButton').prop('disabled', false);
			// 		check2_ok = true;
			// 	}

			// 	if (check1_ok == true) {
			// 		if (check2_ok == true) {
			// 			continue_allowed = true;
			// 			console.log('continue allowed in 812');
			// 			return true;
			// 		}
			// 	} else {
			// 		return false;
			// 	}
			// }


			// Check various user input in tab 4
			// user password
			var passwordField = $('#password');
			var cnfmPasswordField = $('#cnfm_password');
			var minPasswordLenght = 8;

			cnfmPasswordField.on('change', function() {
				if (cnfmPasswordField.val() == passwordField.val() && cnfmPasswordField.val() != '') {

					if (cnfmPasswordField.val().length >= minPasswordLenght) {

						passwordField.removeClass('is-invalid');
						cnfmPasswordField.removeClass('is-invalid');

						passwordField.addClass('is-valid');
						cnfmPasswordField.addClass('is-valid');

						$('#userform_warnings').css('display', 'none');
						// // $('#ContinueButton').prop('disabled', false);

					} else {
						passwordField.addClass('is-invalid');
						cnfmPasswordField.addClass('is-invalid');

						passwordField.removeClass('is-valid');
						cnfmPasswordField.removeClass('is-valid');

						$('#userform_warnings').css('display', 'block');
						$('#userform_warnings').html('Password should be at least 8 characters long')

						// if (root_mode == false) {
						// 	$('#ContinueButton').prop('disabled', true);
						// }
					}

				} else {

					passwordField.addClass('is-invalid');
					cnfmPasswordField.addClass('is-invalid');

					passwordField.removeClass('is-valid');
					cnfmPasswordField.removeClass('is-valid');

					$('#userform_warnings').css('display', 'block');
					$('#userform_warnings').html('Passwords do not match');

					// if (root_mode == false) {
					// 	$('#ContinueButton').prop('disabled', true);
					// }

				}
			});

			// email verification
			const emailField = $('#user_email');

			emailField.on('change', function() {
				if (!isValidEmail(emailField.val()) && emailField != '') {

					emailField.addClass('is-invalid');
					emailField.removeClass('is-valid');
					$('#userform_warnings').css('display', 'block');
					$('#userform_warnings').html('The E-Mail Address is not valid');
					// if (root_mode == false) {
					// 	$('#ContinueButton').prop('disabled', true);
					// }

				} else {

					emailField.removeClass('is-invalid');
					emailField.addClass('is-valid');
					$('#userform_warnings').css('display', 'none');
					// // $('#ContinueButton').prop('disabled', false);

				}
			});

			// grid verification
			const userLocatorField = $('#userlocator');

			userLocatorField.on('change', function() {
				if (!isValidMaidenheadLocator(userLocatorField.val()) && userLocatorField != '') {

					userLocatorField.addClass('is-invalid');
					userLocatorField.removeClass('is-valid');
					$('#userform_warnings').css('display', 'block');
					$('#userform_warnings').html("The grid locator is not valid. Use a 6-character locator, e.g. HA44AA. If you don't know your grid square then <a href='https://zone-check.eu/?m=loc' target='_blank'>click here</a>!");
					// if (root_mode == false) {
					// 	$('#ContinueButton').prop('disabled', true);
					// }

				} else {

					userLocatorField.removeClass('is-invalid');
					userLocatorField.addClass('is-valid');
					$('#userform_warnings').css('display', 'none');
					// // $('#ContinueButton').prop('disabled', false);

				}
			});

			// root mode initializer for js
			// var root_mode = <?php echo json_encode($root_mode); ?>;

			$(document).ready(function() {
				const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
				const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

				$('#ContinueButton').css('display', 'block');
				console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
					"Check out our GitHub Repository and dive into the coding adventure:\n\n" +
					"🚀 https://www.github.com/wavelog/wavelog");

				const tabs = new bootstrap.Tab($('#welcome-tab')[0]);
				tabs.show();

				let firstTabId = 'welcome-tab';
				let secondTabId = 'precheck-tab';
				let thirdTabId = 'configuration-tab';
				let fourthTabId = 'database-tab';
				let lastTabId = 'finish-tab';

				const activeTab = $('.nav-link.active');

				var allChecksPassed = '<?php echo $allChecksPassed; ?>';

				function nextTab() {
					const activeTab = $('.nav-link.active');
					const nextTab = activeTab.parent().next().find('.nav-link');

					if (nextTab.length > 0) {
						const tab = new bootstrap.Tab(nextTab[0]);
						tab.show();
					}

					if (nextTab.attr('id') == secondTabId) { // prevent continue if a vital precheck failed (a php module or allow_url_fopen)
						if (allChecksPassed == 'failed') {
							continue_allowed = false;
						} else {
							// $('#ContinueButton').prop('disabled', false);
							continue_allowed = true;
						}
					}

					if (nextTab.attr('id') !== lastTabId) {
						$('#ContinueButton').css('display', 'block');
						$('#BackButton').css('display', 'block');
					} else {
						$('#ContinueButton').css('display', 'none');
					}

				}

				function prevTab() {
					const activeTab = $('.nav-link.active');
					const prevTab = activeTab.parent().prev().find('.nav-link');

					if (prevTab.length > 0) {
						const tab = new bootstrap.Tab(prevTab[0]);
						tab.show();
					}

					if (prevTab.attr('id') !== firstTabId) {
						$('#ContinueButton').css('display', 'block');
						$('#BackButton').css('display', 'block');
					} else {
						$('#BackButton').css('display', 'none');
					}
					clear_db_testresult();
					// $('#ContinueButton').prop('disabled', false);
				}


				$('#ContinueButton').on('click', function() {
					nextTab();
				});
				$('#BackButton').on('click', function() {
					prevTab();
				});

			});
		</script>
	</body>

<?php else : ?>

	<body>
		<div class="container mt-4 p-2" style="max-width: 600px; ">
			<div class="card p-2 justify-content-center" style="min-height: 200px; margin-top: 200px;">
				<p class="error text-center">Please make the <?php echo $db_config_path; ?> folder writable. <strong>Example</strong>:<br /><br /><code>chmod -R 777 <?php echo $db_config_path; ?></code><br /><br /><i>Don't forget to restore the permissions afterwards.</i></p>
			</div>
		</div>
	</body>

<?php endif; ?>

</html>
