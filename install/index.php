<!--
	New Wavelog Installer

	This installer guides an user through the install process and all 
	necessary parameters for the new Wavelog Installation.

	HB9HIL - January 2024
-->


<?php
// #########################################################
// PRECONFIGURATION
// #########################################################

// Config Paths
$db_config_path = '../application/config/';
$db_file_path = $db_config_path . "database.php";

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

// Only load the classes in case the user submitted the form
if ($_POST && isset($_POST['submit'])) {

	// Load the classes and create the new objects
	require_once('includes/core_class.php');
	require_once('includes/database_class.php');

	$core = new Core();
	$database = new Database();

	// Validate the post data
	if ($core->validate_post($_POST) == true) {

		// First create the database, then create tables, then write config file
		if ($database->create_database($_POST) == false) {
			$message = $core->show_message('error', "The database could not be created, please verify your settings.");
		} elseif ($database->create_tables($_POST) == false) {
			$message = $core->show_message('error', "The database tables could not be created, please verify your settings.");
		} elseif ($core->write_config($_POST) == false) {
			$message = $core->show_message('error', "The database configuration file could not be written, please chmod /application/config/database.php file to 777");
		}

		if ($core->write_configfile($_POST) == false) {
			$message = $core->show_message('error', "The config configuration file could not be written, please chmod /application/config/config.php file to 777");
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
			header('Location: ' . $protocol . "://" . $_SERVER['HTTP_HOST'] . $_POST['directory']);
			echo "<h1>Install successful</h1>";
			echo "<p>Please delete the install folder";
			exit;
		}
	} else {
		$message = $core->show_message('error', 'Not all fields have been filled in correctly. The host, username, password, and database name are required.');
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<title>Install | Wavelog</title>
	<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/css/overrides.css">

	<script type="text/javascript" src="assets/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="assets/js/jquery-3.6.0.min.js"></script>
</head>

<?php if (is_writable($db_config_path)) : ?>
	<?php if (isset($message)) {
		echo '<p class="error">' . $message . '</p>';
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
								<div class="row">
									<div class="col-md-6">
										<img src="assets/images/wavelog_logo.png" alt="" style="max-width: 100%; height: auto;">
									</div>

									<div class="col-md-6">
										<h4 style="margin-top: 50px;">Welcome to the Wavelog Installer</h4>
										<p style="margin-top: 50px;">This installer will guide you through the necessary steps for the installation of Wavelog. <br>Wavelog is a powerful web-based amateur radio logging software. Follow the steps in each tab to configure and install Wavelog on your server.</p>
										<p>If you encounter any issues or have questions, refer to the documentation (<a href="https://www.github.com/wavelog/wavelog/wiki" target="_blank">Wiki</a>) or community forum (<a href="https://www.github.com/wavelog/wavelog/discussions" target="_blank">Discussions</a>) on Github for assistance.</p>
										<p>Thank you for installing Wavelog!</p>
									</div>
								</div>
							</div>

							<!-- Tab 2: Pre-Checks --> <!-- TODO Needs some Layout and maybe check for other packages aswell-->
							<div class="tab-pane fade" id="precheck" role="tabpanel" aria-labelledby="precheck-tab">
								<div class="row justify-content-center mt-4">
									<div class="col-md-5 mb-4 mx-auto">
										<p class="border-bottom mb-2"><b>PHP Modules</b></p>
										<?php
										// Initialize the tracker
										$allChecksPassed = true;
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
												$allChecksPassed = $allChecksPassed && $condition;
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
									</div>

									<div class="col-md-5 mb-4 mx-auto"> <!-- MySQL / MariaDB -->
										<p class="border-bottom mb-2"><b>MySQL / MariaDB</b></p>
										<table width="100%">
											<tr>
												<td>Min. MySQL Version: </td>
												<td><span class="badge text-bg-info"><?php echo $mysql_version; ?></span></td>
											</tr>
											<tr>
												<td>Min. MariaDB Version: </td>
												<td><span class="badge text-bg-info"><?php echo $mariadb_version; ?></span></td>
											</tr>
										</table>
										<p style="margin-top: 10px;">You can test your MySQL/MariaDB Version in Step 4</p>
									</div>
								</div>
								<div class="row justify-content-center">
									<div class="col-md-5 mx-auto"> <!-- PHP Settings -->
										<p class="border-bottom mb-2"><b>PHP Settings</b></p>
										<table width="100%">
											<tr>
												<td>max_execution_time</td>
												<td><?php echo '> ' . $max_execution_time . ' s'; ?></td>
												<td>
													<?php
													$maxExecutionTime = ini_get('max_execution_time');
													if ($maxExecutionTime >= $max_execution_time) {
													?>
														<span class="badge text-bg-success"><?php echo $maxExecutionTime . ' s'; ?></span>
													<?php } else {
														?>
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
													if ($maxUploadFileSizeBytes > ($max_upload_file_size * 1024 * 1024)) { // compare with given value in bytes
													?>
														<span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
													<?php } else {
														?>
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
													if ($maxUploadFileSizeBytes > ($post_max_size * 1024 * 1024)) { // compare with given value in bytes
													?>
														<span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
													<?php } else {
														?>
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
														$allChecksPassed = false; ?>
														<span class="badge text-bg-danger">Off</span>
													<?php } ?>
												</td>
											</tr>
										</table>
									</div>
									<div class="col-md-5 mx-auto" style="margin-top: 50px;">
										<?php if ($allChecksPassed) { ?>
											<div class="alert alert-success d-flex align-items-center" role="alert">
												<p class="mb-0">All Checks are OK. You can continue.</p>
											</div>
										<?php } else { ?>
											<div class="alert alert-danger d-flex flex-column align-items-center" role="alert">
												<p class="mb-2 border-bottom">Some Checks have failed!</p>
												<p class="mb-2">Check your PHP settings and install missing modules if necessary.</p>
												<p class="mb-0">After that, you have to restart your webserver and start the installer again.</p>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>

							<!-- Tab 3: Configuration --> <!-- TODO Needs some Layout and maybe other config options (qrz/hamqth web lookup?) -->
							<div class="tab-pane fade" id="configuration" role="tabpanel" aria-labelledby="configuration-tab">
								<div class="row">
									<div class="col" style="margin-top: 50px;">
										<img src="assets/images/gears_icon.png" alt="" style="max-width: 80%; height: auto; margin-left: 20px;">
									</div>
									<div class="col">
										<p>Configure some basic parameters for your wavelog instance. You can change them later in 'application/config/config.php'</p>
										<div class="mb-3">
											<label for="directory" class="form-label">Directory</label> <!-- TODO Check with team if this is necessary -->
											<input type="text" id="directory" value="<?php echo str_replace("index.php", "", str_replace("/install/", "", $_SERVER['REQUEST_URI'])); ?>" class="form-control" name="directory" />
										</div>
										<div class="mb-3">
											<label for="websiteurl" class="form-label">Website URL</label>
											<input type="text" id="websiteurl" value="<?php echo $_SERVER['REQUEST_SCHEME']; ?>://<?php echo str_replace("index.php", "", $_SERVER['HTTP_HOST'] . str_replace("/install/", "", $_SERVER['REQUEST_URI'])); ?>" class="form-control" name="websiteurl" />
										</div>
										<div class="mb-3">
											<label for="locator" class="form-label">Default Gridsquare</label>
											<input type="text" id="locator" value="IO91JS" class="form-control" name="locator" />
										</div>
										<div class="mb-3">
											<label for="global_call_lookup" class="form-label">Global Callsign Lookup</label>
											<select id="global_call_lookup" class="form-select" name="global_call_lookup">
												<option value="qrz" selected>QRZ.com</option>
												<option value="hamqth">HamQTH</option>
											</select>
										</div>
									</div>
								</div>
							</div>

							<!-- Tab 4: Database --> <!-- TODO Perform a mysql_get_server_info() with the provided data before continue -->
							<div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
								<div class="row">
									<div class="col" style="margin-top: 50px;">
										<img src="assets/images/database_sign.png" alt="" style="max-width: 80%; height: auto; margin-left: 20px;">
									</div>
									<div class="col">
										<p>To properly install Wavelog you already should have setup a mariadb/mysql database. Provide the parameters here.</p>
										<div class="mb-3">
											<label for="db_hostname" class="form-label">Hostname</label>
											<input type="text" id="db_hostname" value="localhost" class="form-control" name="db_hostname" />
											<small class="text-muted">Usually 'localhost'. Optional with '...:[port]'. Default Port: 3306</small>
										</div>
										<div class="mb-3">
											<label for="db_username" class="form-label">Username</label>
											<input type="text" id="db_username" placeholder="waveloguser" class="form-control" name="db_username" />
											<small class="text-muted">Username of the DB User which has full access to the database.</small>
										</div>
										<div class="mb-3">
											<label for="db_password" class="form-label">Password</label>
											<input type="password" id="db_password" placeholder="supersecretpassword" class="form-control" name="db_password" />
											<small class="text-muted">Password of the DB User.</small>
										</div>
										<div class="mb-3">
											<label for="db_name" class="form-label">Database Name</label>
											<input type="text" id="db_name" placeholder="wavelog" class="form-control" name="db_name" />
											<small class="text-muted">Name of the Database.</small>
										</div>
									</div>
								</div>
							</div>

							<!-- Tab 5: First User --> <!-- TODO Layout finish and Logic -->
							<div class="tab-pane fade" id="firstuser" role="tabpanel" aria-labelledby="firstuser-tab">
								<p>Now you can create your first user in Wavelog. Fill out all fields and click continue.<br>Make sure you use a proper password.</p>
								<div class="row">
									<div class="col-md-6 mb-2">
										<div class="row">
											<div class="col">
												<label for="firstname" class="form-label">First Name</label>
												<input type="text" id="firstname" placeholder="Ham" class="form-control" name="firstname" />
											</div>
											<div class="col">
												<label for="lastname" class="form-label">Last Name</label>
												<input type="text" id="lastname" placeholder="Radio" class="form-control" name="lastname" />
											</div>
										</div>
									</div>
									<div class="col-md-6 mb-2">
										<div class="row">
											<div class="col">
												<label for="username" class="form-label">Username</label>
												<input type="text" id="username" placeholder="ham.radio" class="form-control" name="username" />
											</div>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="callsign" class="form-label">Callsign</label>
										<input type="text" id="callsign" placeholder="4W7EST" class="form-control" name="callsign" />
									</div>
									<div class="col-md-6 mb-2">
										<label for="user_email" class="form-label">E-Mail Address</label>
										<input type="email" id="user_email" placeholder="ham.radio@example.com" class="form-control" name="user_email" />
									</div>
								</div>

								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="userlocator" class="form-label">Locator</label>
										<input type="text" id="userlocator" placeholder="HA44AA" class="form-control" name="userlocator" />
									</div>
									<div class="col-md-6 mb-2">
										<label for="timezone" class="form-label">Timezone</label>
										<select id="timezone" class="form-select" name="timezone">
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
												['100', '0.0', '(GMT)-Casablanca, Monrovia'],
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
								<div class="row">
									<div class="col-md-6 mb-2">
										<label for="password" class="form-label">Password</label>
										<input type="password" id="password" placeholder="**********" class="form-control" name="password" />
									</div>
									<div class="col-md-6 mb-2">
										<div class="col-md-6 mb-2">
											<label for="cnfm_password" class="form-label">Confirm Password</label> <!-- TODO Compare passwords before continue -->
											<input type="cnfm_password" id="cnfm_password" placeholder="**********" class="form-control" name="cnfm_password" />
										</div>
									</div>
								</div>
							</div>

							<!-- Tab 6: Finish --> <!-- TODO Install Button -->
							<div class="tab-pane fade" id="finish" role="tabpanel" aria-labelledby="finish-tab">
								<p>Here will be the Install Button</p>
								<input type="submit" value="Install" id="submit" />
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
			$(document).ready(function() {
				$('#ContinueButton').css('display', 'block');
				console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
					"Check out our GitHub Repository and dive into the coding adventure:\n\n" +
					"🚀 https://www.github.com/wavelog/wavelog");

				const tabs = new bootstrap.Tab($('#welcome-tab')[0]);
				tabs.show();

				let firstTabId = 'welcome-tab';
				let lastTabId = 'finish-tab';
				let preCheckTabId = 'precheck-tab';

				function nextTab() {
					const activeTab = $('.nav-link.active');
					const nextTab = activeTab.parent().next().find('.nav-link');

					if (nextTab.length > 0) {
						const tab = new bootstrap.Tab(nextTab[0]);
						tab.show();
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
				}

				$('#ContinueButton').on('click', nextTab);
				$('#BackButton').on('click', prevTab);

				<?php if (!$allChecksPassed) { ?>
					// Check if the active tab is the precheck-tab and disable the ContinueButton if not all Checks passed
					$(document).on('shown.bs.tab', function(e) {
						const activeTabId = e.target.id;
						if (activeTabId === 'precheck-tab') {
							$('#ContinueButton').prop('disabled', true);
						} else {
							$('#ContinueButton').prop('disabled', false);
						}
					});
				<?php } ?>
			});

			// [PWD] button show/hide //
			function btn_pwd_showhide() {
				if ($(this).closest('div').find('input[type="password"]').length > 0) {
					$(this).closest('div').find('input[type="password"]').attr('type', 'text');
					$(this).closest('div').find('.fa-eye-slash').removeClass('fa-eye-slash').addClass('fa-eye');
				} else {
					$(this).closest('div').find('input[type="text"]').attr('type', 'password');
					$(this).closest('div').find('.fa-eye').removeClass('fa-eye').addClass('fa-eye-slash');
				}
			}
			$('.user_edit .btn-pwd-showhide').off('click').on('click', btn_pwd_showhide);
		</script>
	</body>

<?php else : ?>

	<body>
		<div class="container mt-4 p-2" style="max-width: 600px; ">
			<div class="card p-2 justify-content-center" style="min-height: 200px; margin-top: 200px;">
				<p class="error text-center">Please make the /application/config/ folder writable. <strong>Example</strong>:<br /><br /><code>chmod -R 777 application/config/</code><br /><br /><i>Don't forget to restore the permissions afterwards.</i></p>
			</div>
		</div>
	</body>

<?php endif; ?>

</html>