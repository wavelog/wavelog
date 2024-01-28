<!--
	New Wavelog Installer

	This installer guides an user through the install process and all 
	necessary parameters for the new Wavelog Installation.

	Version 1.0 - January 2024
-->


<?php

$db_config_path = '../application/config/';
$db_file_path = $db_config_path . "database.php";

// Wanted Pre-Check Parameters
//
// PHP 
$max_execution_time = 600;		// Seconds
$max_upload_file_size = 20;  	// Megabyte
$post_max_size = 20;		// Megabyte

// MariaDB / MySQL
$mariadb_version = 10.1;
$mysql_version = 5.7;


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
	<link rel="icon" type="image/x-icon" href="assets/logo/favicon.ico">

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
		<div class="container" style="max-width: 1000px; margin-top: 5rem; ">

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
										<img src="assets/logo/wavelog_logo.png" alt="" style="max-width: 100%; height: auto;">
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
									<div class="col-md-5 mb-4 mx-auto"> <!-- PHP Modules -->
										<p class="border-bottom mb-2"><b>PHP Modules</b></p>
										<table width="100%">
											<tr>
												<td>Version (min. 7.4)</td>
												<td>
													<?php if (version_compare(PHP_VERSION, '7.4.0') <= 0) { ?>
														<span class="badge text-bg-danger"><?php echo PHP_VERSION; ?></span>
													<?php } else { ?>
														<span class="badge text-bg-success"><?php echo PHP_VERSION; ?></span>
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td>php-curl</td>
												<td>
													<?php if (in_array('curl', get_loaded_extensions())) { ?>
														<span class="badge text-bg-success">Installed</span>
													<?php } else { ?>
														<span class="badge text-bg-danger">Not Installed</span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>php-mysql</td>
												<td>
													<?php if (in_array('mysqli', get_loaded_extensions())) { ?>
														<span class="badge text-bg-success">Installed</span>
													<?php } else { ?>
														<span class="badge text-bg-danger">Not Installed</span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>php-mbstring</td>
												<td>
													<?php if (in_array('mbstring', get_loaded_extensions())) { ?>
														<span class="badge text-bg-success">Installed</span>
													<?php } else { ?>
														<span class="badge text-bg-danger">Not Installed</span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>php-xml</td>
												<td>
													<?php if (in_array('xml', get_loaded_extensions())) { ?>
														<span class="badge text-bg-success">Installed</span>
													<?php } else { ?>
														<span class="badge text-bg-danger">Not Installed</span>
													<?php } ?>
												</td>
											</tr>

											<tr>
												<td>php-zip</td>
												<td>
													<?php if (in_array('zip', get_loaded_extensions())) { ?>
														<span class="badge text-bg-success">Installed</span>
													<?php } else { ?>
														<span class="badge text-bg-danger">Not Installed</span>
													<?php } ?>
												</td>
											</tr>
										</table>
									</div>
									<div class="col-md-5 mb-4 mx-auto"> <!-- MySQL / MariaDB -->
										<p class="border-bottom mb-2"><b>MySQL / MariaDB</b></p>
										<table width="100%">
											<!-- TODO Logic to check MariaDB / MySQL Version and show it -->
											<tr>
												<td>MariaDB installed version</td>
												<td><?php echo 'min. ' . $mariadb_version; ?></td>
												<td>ToDo</td>
											</tr>
										</table>
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
														<span class="badge text-bg-success"><?php echo $maxExecutionTime; ?></span>
													<?php } else { ?>
														<span class="badge text-bg-danger"><?php echo $maxExecutionTime; ?></span>
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
													<?php } else { ?>
														<span class="badge text-bg-danger"><?php echo $maxUploadFileSize; ?></span>
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
													<?php } else { ?>
														<span class="badge text-bg-danger"><?php echo $maxUploadFileSize; ?></span>
													<?php } ?>
												</td>
											</tr>
										</table>
									</div>
									<div class="col-md-5 mx-auto border">
										<p>Note: Everything is good or not</p> <!-- TODO Show confirmation or tipps what to do if something is wrong -->
									</div>
								</div>
							</div>

							<!-- Tab 3: Configuration --> <!-- TODO Needs some Layout -->
							<div class="tab-pane fade" id="configuration" role="tabpanel" aria-labelledby="configuration-tab">

								<div class="mb-3">
									<label for="directory" class="form-label">Directory</label>
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
							</div>

							<!-- Tab 4: Database --> <!-- TODO Needs some Layout -->
							<div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
								<div class="mb-3">
									<label for="db_hostname" class="form-label">Hostname</label>
									<input type="text" id="db_hostname" value="localhost" class="form-control" name="db_hostname" />
								</div>
								<div class="mb-3">
									<label for="db_username" class="form-label">Username</label>
									<input type="text" id="db_username" class="form-control" name="db_username" />
								</div>
								<div class="mb-3">
									<label for="db_password" class="form-label">Password</label>
									<input type="password" id="db_password" class="form-control" name="db_password" />
								</div>
								<div class="mb-3">
									<label for="db_name" class="form-label">Database Name</label>
									<input type="text" id="db_name" class="form-control" name="db_name" />
								</div>
							</div>

							<!-- Tab 5: First User -->
							<div class="tab-pane fade" id="firstuser" role="tabpanel" aria-labelledby="firstuser-tab">
								<p>Create your first User.</p> <!-- TODO User Logic -->
								<div class="row">
									<div class="col mb-2">
										<label for="firstname" class="form-label">First Name</label>
										<input type="text" id="firstname" class="form-control" name="firstname" />
									</div>
									<div class="col mb-2">
										<label for="lastname" class="form-label">Last Name</label>
										<input type="text" id="lastname" class="form-control" name="lastname" />
									</div>
								</div>
								<div class="row">
									<div class="col mb-2">
										<label for="callsign" class="form-label">Callsign</label>
										<input type="text" id="callsign" class="form-control" name="callsign" />
									</div>
									<div class="col mb-2">
										<label for="username" class="form-label">Username</label>
										<input type="text" id="username" class="form-control" name="username" />
									</div>
								</div>
								<div class="row">
									<div class="col mb-2">
										<label for="userlocator" class="form-label">Locator</label>
										<input type="text" id="userlocator" class="form-control" name="userlocator" />
									</div>
									<div class="col mb-2">
										<label for="user_email" class="form-label">E-Mail Address</label>
										<input type="user_email" id="user_email" class="form-control" name="user_email" />
									</div>
								</div>
								<div class="row">
									<div class="col mb-2">
										<label for="password" class="form-label">Password</label>
										<input type="password" id="password" class="form-control" name="password" />
									</div>
									<div class="col mb-2">
										<label for="measurement" class="form-label">Measurement Preference</label>
										<select id="measurement" class="form-select" name="measurement">
											<option value="K" selected>Kilometers</option>
											<option value="M">Miles</option>
										</select>
									</div>
								</div>
								<div class="row">
									<div class="col mb-2">
										<label for="cnfm_password" class="form-label">Confirm Password</label> <!-- TODO Compare passwords before continue -->
										<input type="cnfm_password" id="cnfm_password" class="form-control" name="cnfm_password" />
									</div>
									<div class="col mb-2">
										<label for="language" class="form-label">Language</label>
										<select id="language" class="form-select" name="language">
											<option value="bulgarian">Bulgarian</option>
											<option value="chinese_simplified">Chinese</option>
											<option value="czech">Czech</option>
											<option value="dutch">Dutch</option>
											<option value="english" selected>English</option>
											<option value="finnish">Finnish</option>
											<option value="french">French</option>
											<option value="german">German</option>
											<option value="greek">Greek</option>
											<option value="italian">Italian</option>
											<option value="polish">Polish</option>
											<option value="russian">Russian</option>
											<option value="spanish">Spanish</option>
											<option value="swedish">Swedish</option>
											<option value="turkish">Turkish</option>
										</select>
									</div>
								</div>
							</div>

							<!-- Tab 6: Finish -->
							<div class="tab-pane fade" id="finish" role="tabpanel" aria-labelledby="finish-tab">
								<p>Here will be the Install Button</p> <!-- TODO Install Button -->
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