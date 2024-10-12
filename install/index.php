<?php
/*
Wavelog Installer

This installer guides an user through the install process and all
necessary parameters for the new Wavelog Installation.

HB9HIL - First refactoring - January 2024
DJ7NT - Docker Readiness - April 2024
HB9HIL - Big UX and backend upgrade - July 2024
*/

if (!file_exists('.lock')) {

	include 'includes/interface_assets/header.php';

	// php-mbstring has to be installed for the installer to work properly!!
	// The other prechecks can be run within the installer.
	if ($required_php_modules['php-mbstring']['condition'] && $required_php_modules['php-curl']['condition']) { ?>


		<body>
			<div class="container" style="max-width: 1200px; margin-top: 8rem; ">

				<div class="card mt-4" style="min-height: 750px; margin: 0 auto;">

					<div class="card-header">
						<ul class="nav nav-tabs nav-fill card-header-tabs">
							<li class="nav-item">
								<a class="nav-link active disabled" id="welcome-tab" data-bs-toggle="tab" href="#welcome" role="tab" aria-controls="welcome" aria-selected="true"><?= __("1. Welcome"); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link disabled" id="precheck-tab" data-bs-toggle="tab" href="#precheck" role="tab" aria-controls="precheck" aria-selected="false"><?= __("2. Pre Checks"); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link disabled" id="configuration-tab" data-bs-toggle="tab" href="#configuration" role="tab" aria-controls="configuration" aria-selected="false"><?= __("3. Configuration"); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link disabled" id="database-tab" data-bs-toggle="tab" href="#database" role="tab" aria-controls="database" aria-selected="false"><?= __("4. Database"); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link disabled" id="firstuser-tab" data-bs-toggle="tab" href="#firstuser" role="tab" aria-controls="firstuser" aria-selected="false"><?= __("5. First User"); ?></a>
							</li>
							<li class="nav-item">
								<a class="nav-link disabled" id="finish-tab" data-bs-toggle="tab" href="#finish" role="tab" aria-controls="finish" aria-selected="false"><?= __("6. Finish"); ?></a>
							</li>
						</ul>
					</div>

					<div class="card-body">
						<form id="install_form" method="post" action="run.php">
							<div class="tab-content" id="myTabContent">

								<!-- Tab 1: Welcome -->
								<div class="tab-pane fade show active p-3" id="welcome" role="tabpanel" aria-labelledby="welcome-tab">
									<div class="row" style="margin-top: 20px;">
										<div class="col-md-6" id="logo-container">
											<img src="../assets/logo/wavelog_logo_darkly.png" alt="" style="max-width: 100%; height: auto;">
										</div>

										<div class="col-md-6">
											<h4 style="margin-top: 50px;"><?= __("Welcome to the Wavelog Installer"); ?></h4>
											<p style="margin-top: 50px;"><?= __("This installer will guide you through the necessary steps for the installation of Wavelog. <br>Wavelog is a powerful web-based amateur radio logging software. Follow the steps in each tab to configure and install Wavelog on your server."); ?></p>
											<p><?= sprintf(__("If you encounter any issues or have questions, refer to the documentation (%s) or community forum (%s) on Github for assistance."), "<a href='https://www.github.com/wavelog/wavelog/wiki' target='_blank'>" . __("Wiki") . "</a>", "<a href='https://www.github.com/wavelog/wavelog/discussions' target='_blank'>" . __("Discussions") . "</a>"); ?></p>
											<p><?= __("Thank you for installing Wavelog!"); ?></p>
											<?php if (__("Language") == "Language") {
												$lang_html = "Language";
											} else {
												$lang_html = __("Language") . " / Language";
											} ?>
											<a class="btn btn-sm btn-secondary" id="languageButton"><?= $lang_html; ?></a>
										</div>
									</div>
								</div>
								<div class="modal fade" id="languageModal" tabindex="-1" aria-labelledby="languageModalLabel" aria-hidden="true">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="languageModalLabel"><?= __("Select a language"); ?></h5>
											</div>
											<div class="modal-body">
												<ul style="list-style-type: none;">
													<?php foreach ($languages as $lang) { ?>
														<li>
															<?php echo country2flag($lang['flag']); ?> <a href="?lang=<?php echo $lang['gettext']; ?>"><?php echo $lang['name_en']; ?></a>
														</li>
													<?php } ?>
												</ul>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
											</div>
										</div>
									</div>
								</div>

								<!-- Tab 2: Pre-Checks -->
								<div class="tab-pane fade" id="precheck" role="tabpanel" aria-labelledby="precheck-tab">
									<div class="row justify-content-center" style="margin-top: 1rem;">
										<div class="col-md-5 mx-auto">
											<p class="border-bottom mb-2"><b><?= __("PHP Modules"); ?></b></p>
											<?php
											// Initialize the tracker
											$prechecks_passed = 'ok';
											?>
											<table width="100%">
												<tr>
													<td><?= __("Version"); ?></td>
													<td><?= sprintf(_pgettext("PHP Version", "min. %s (recommended %s+)"), $min_php_version, $min_php_version_warning); ?></td>
													<td>
														<?php if (version_compare(PHP_VERSION, $min_php_version) <= 0) {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?php echo PHP_VERSION; ?></span>
															<?php } else {
															if (version_compare(PHP_VERSION, $min_php_version_warning) <= 0) {
																$prechecks_passed = 'warning'; ?>
																<span class="badge text-bg-warning"><?php echo PHP_VERSION; ?></span>
															<?php } else { ?>
																<span class="badge text-bg-success"><?php echo PHP_VERSION; ?></span>
														<?php }
														} ?>
													</td>
												</tr>
												<?php
												foreach ($required_php_modules as $moduleName => $moduleData) {
													$condition = $moduleData['condition'];
													if (!$condition) {
														$prechecks_passed = 'failed';
													}
												?>
													<tr>
														<td><?php echo $moduleName; ?></td>
														<td></td>
														<td>
															<span class="badge text-bg-<?php echo $condition ? 'success' : 'danger'; ?>">
																<?php echo $condition ? __("Installed") : __("Not Installed"); ?>
															</span>
														</td>
													</tr>
												<?php
												}
												?>
											</table>
											<p class="border-bottom mb-2" style="margin-top: 2rem;"><b><?= __("PHP Settings"); ?></b></p>
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
															if ($prechecks_passed != 'failed') {  // Check current value before changing to 'warning'
																$prechecks_passed = 'warning';
															} ?>
															<span class="badge text-bg-warning"><?php echo $maxExecutionTime; ?></span>
														<?php } ?>
													</td>
												</tr>

												<tr>
													<td>upload_max_filesize</td>
													<td><?php echo '> ' . $upload_max_filesize . 'M'; ?></td>
													<td>
														<?php
														$maxUploadFileSize = ini_get('upload_max_filesize');
														$maxUploadFileSizeBytes = (int)($maxUploadFileSize) * (1024 * 1024); // convert to bytes
														if ($maxUploadFileSizeBytes >= ($upload_max_filesize * 1024 * 1024)) { // compare with given value in bytes
														?>
															<span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
														<?php } else {
															if ($prechecks_passed != 'failed') {  // Check current value before changing to 'warning'
																$prechecks_passed = 'warning';
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
															if ($prechecks_passed != 'failed') {  // Check current value before changing to 'warning'
																$prechecks_passed = 'warning';
															} ?>
															<span class="badge text-bg-warning"><?php echo $maxUploadFileSize; ?></span>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>memory_limit</td>
													<td><?php echo '> ' . $memory_limit . 'M'; ?></td>
													<td>
														<?php
														$memoryLimit = ini_get('memory_limit');
														$memoryLimitBytes = (int)($memoryLimit) * (1024 * 1024); // convert to bytes
														if ($memoryLimitBytes >= ($memory_limit * 1024 * 1024)) { // compare with given value in bytes
														?>
															<span class="badge text-bg-success"><?php echo $memoryLimit; ?></span>
														<?php } else {
															if ($prechecks_passed != 'failed') {  // Check current value before changing to 'warning'
																$prechecks_passed = 'warning';
															} ?>
															<span class="badge text-bg-warning"><?php echo $memoryLimit; ?></span>
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
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger">Off</span>
														<?php } ?>
													</td>
												</tr>
											</table>
											<p class="border-bottom mb-2" style="margin-top: 2rem;"><b><?= __("Folder Write Permissions"); ?></b></p>
											<table width="100%">
												<tr>
													<td>/application</td>
													<td>
														<?php if (is_really_writable('../application/cache') == true && is_really_writable('../application/config') == true && is_really_writable('../application/logs') == true) { ?>
															<span class="badge text-bg-success"><?= __("Success"); ?></span>
														<?php } else {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?= __("Failed"); ?></span>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>/backup</td>
													<td>
														<?php if (is_really_writable('../backup') == true) { ?>
															<span class="badge text-bg-success"><?= __("Success"); ?></span>
														<?php } else {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?= __("Failed"); ?></span>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>/updates</td>
													<td>
														<?php if (is_really_writable('../updates') == true) { ?>
															<span class="badge text-bg-success"><?= __("Success"); ?></span>
														<?php } else {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?= __("Failed"); ?></span>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>/uploads</td>
													<td>
														<?php if (is_really_writable('../uploads') == true) { ?>
															<span class="badge text-bg-success"><?= __("Success"); ?></span>
														<?php } else {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?= __("Failed"); ?></span>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>/userdata</td>
													<td>
														<?php if (is_really_writable('../userdata') == true) { ?>
															<span class="badge text-bg-success"><?= __("Success"); ?></span>
														<?php } else {
															$prechecks_passed = 'failed'; ?>
															<span class="badge text-bg-danger"><?= __("Failed"); ?></span>
														<?php } ?>
													</td>
												</tr>
											</table>
										</div>
										<div class="col-md-5 mb-4 mx-auto">
											<p class="border-bottom mb-2"><b><?= __("Web Server"); ?></b></p>
											<table width="100%" style="margin-bottom: 25px;">
												<tr>
													<td><?= __("Version:"); ?> </td>
													<td><span class="badge text-bg-info"><?php echo detect_webserver(); ?></span></td>
												</tr>
											</table>
											<?php if (strpos(strtolower(detect_webserver()), 'nginx') !== false) {
												if (detect_nginx_php_setting($http_scheme) != 200) { ?>
													<div class="alert alert-warning d-flex flex-column align-items-center" role="alert">
														<p class="mb-2 border-bottom"><?= __("Important note for nginx users!"); ?></p>
														<p class="mb-0"><?= __("Since you are using nginx as web server please make sure that you have made the changes described in the Wiki before continuing."); ?></p><br>
														<p class="mb-0"><a target="_blank" href="https://github.com/wavelog/Wavelog/wiki/Installation#nginx-configuration">https://github.com/wavelog/Wavelog/wiki/Installation#nginx-configuration</a></p>
													</div>
												<?php } ?>
											<?php } ?>
											<?php if ($prechecks_passed == 'failed') {
												$prechecks_icon = "fa-times-circle";
												$prechecks_color = "red"; ?>
												<div id="precheck_testresults" class="alert alert-danger d-flex flex-column align-items-center" role="alert">
													<p class="mb-2 border-bottom"><?= __("Some Checks have failed!"); ?></p>
													<p class="mb-2"><?= __("Check your PHP settings and install missing modules if necessary."); ?></p>
													<p class="mb-2"><?= __("After that, you have to restart your webserver and start the installer again."); ?></p>
													<p class="mb-2"><?= sprintf(__("In case of failed 'Folder Write Permissions' check out our Wiki <a href='%s' target='_blank'>here</a>."), "https://github.com/wavelog/Wavelog/wiki/Installation#3-set-directory-ownership-and-permissions"); ?></p>
												</div>
											<?php } else if ($prechecks_passed == 'warning') {
												$prechecks_icon = "fa-exclamation-triangle";
												$prechecks_color = "#ffc107"; ?>
												<div id="precheck_testresults" class="alert alert-warning d-flex flex-column align-items-center" role="alert">
													<p class="mb-2 border-bottom"><?= __("You have some warnings!"); ?></p>
													<p class="mb-2"><?= __("Some of the settings are not optimal. You can proceed with the installer but be aware that you could run into problems while using Wavelog."); ?></p>
												</div>
											<?php } else if ($prechecks_passed == 'ok') {
												$prechecks_icon = "fa-check-circle";
												$prechecks_color = "#04a004"; ?>
												<div id="precheck_testresults" class="alert alert-success d-flex align-items-center" role="alert">
													<i class="me-2 fas fa-check-circle"></i>
													<p class="mb-0"><?= __("All Checks are OK. You can continue."); ?></p>
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
											<p><?= __("Configure some basic parameters for your wavelog instance. You can change them later in 'application/config/config.php'"); ?></p>
											<div class="mb-3">
												<label for="directory" class="form-label"><?= __("Directory"); ?><i id="directory_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("The 'Directory' is basically your subfolder of the webroot In normal conditions the prefilled value is doing it's job. It also can be empty."); ?>"></i></label>
												<div class="input-group">
													<span class="input-group-text" id="main-url"><?php echo $http_scheme . '://' . $_SERVER['HTTP_HOST'] . "/"; ?></span>
													<input type="text" id="directory" value="<?php echo substr(str_replace("index.php", "", str_replace("/install/", "", $_SERVER['REQUEST_URI'])), 1); ?>" class="form-control" name="directory" aria-describedby="main-url" />
													<div class="invalid-tooltip">
														<?= __("No slash before or after the directory. Just the name of the folder."); ?>
													</div>
												</div>
											</div>
											<div class="mb-3 position-relative">
												<label for="websiteurl" class="form-label required"><?= __("Website URL"); ?></label><i id="websiteurl_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= sprintf(__("This is the complete URL where your Wavelog Instance will be available. If you run this installer locally but want to place Wavelog behind a Reverse Proxy with SSL you should type in the new URL here (e.g. %s instead of %s). Don't forget to include the directory from above."), "https://mywavelog.example.org/", "http://192.168.1.100/"); ?>"></i>
												<input type="text" id="websiteurl" value="<?php echo $websiteurl; ?>" class="form-control" name="websiteurl" />
												<div class="invalid-tooltip">
													<?= __("This field<br>
													- can't be empty<br>
													- have to end with a slash 'example/'<br>
													- have to start with 'http'"); ?>
												</div>
											</div>
											<div class="mb-3">
												<label for="global_call_lookup" class="form-label"><?= __("Optional: Global Callbook Lookup"); ?><i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("This configuration is optional. The callsign lookup will be available for all users of this installation. You can choose between QRZ.com and HamQTH. While HamQTH also works without username and password, you will need credentials for QRZ.com. To also get the Call Locator in QRZ.com you'll need an XML subscription. HamQTH does not always provide the locator information."); ?>"></i></label>
												<select id="global_call_lookup" class="form-select" name="global_call_lookup">
													<option value="hamqth" selected>HamQTH</option>
													<option value="qrz">QRZ.com</option>
												</select>
											</div>
											<div class="row">
												<div class="col-md-3">
													<div class="mb-3">
														<label for="callbook_username" class="form-label mt-2"><?= __("Username"); ?></label>
													</div>
													<div>
														<label for="callbook_password" class="form-label mt-1"><?= __("Password"); ?></label>
													</div>
												</div>
												<div class="col-md-9">
													<div class="mb-3">
														<input type="text" id="callbook_username" placeholder="<?= __("Callbook Username"); ?>" class="form-control" name="callbook_username" />
													</div>
													<div class="position-relative">
														<input type="password" id="callbook_password" placeholder="<?= __("Callbook Password"); ?>" class="form-control" name="callbook_password" />
														<div class="invalid-tooltip">
															<?= sprintf(__("Password can't contain %s or be empty"), "' \" / \ < >"); ?>
														</div>
													</div>
												</div>
											</div>
											<a class="btn btn-sm btn-secondary" id="advancedSettingsButton"><?= __("Advanced Settings"); ?></a>
											<div class="modal fade" id="advancedSettingsModal" tabindex="-1" aria-labelledby="advancedSettingsModalLabel" aria-hidden="true">
												<div class="modal-dialog">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title" id="advancedSettingsModalLabel"><?= __("Advanced Settings"); ?></h5>
														</div>
														<div class="modal-body">
															<p><?= __("These settings should only be set if you know what you're doing.") ?></p>
															<div class="row mb-3">
																<div class="col-6">
																	<label for="log_threshold" class="form-label"><?= __("Error Logs"); ?>
																		<i id="logging_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Optional: Enable Error Logging by setting the log threshold bigger then 0. Only enable this if you really need it."); ?>"></i>
																	</label>
																</div>
																<div class="col-6">
																	<select id="log_threshold" class="form-select" name="log_threshold">
																		<option value="0" selected><?= __("0 - No logs"); ?></option>
																		<option value="1"><?= __("1 - Error messages"); ?></option>
																		<option value="2"><?= __("2 - Debug messages"); ?></option>
																		<option value="3"><?= __("3 - Info messages"); ?></option>
																		<option value="4"><?= __("4 - All messages"); ?></option>
																	</select>
																</div>
															</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
														</div>
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
											<p><?= __("To properly install Wavelog you already should have setup a mariadb/mysql database. Provide the parameters here."); ?></p>
											<div class="row">
												<div class="col">
													<div class="mb-3">
														<label for="db_hostname" class="form-label required"><?= __("Hostname or IP"); ?></label><i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Usually 'localhost'.<br>Optional with '[host]:[port]'. Default port: 3306.<br>In a docker compose install type 'wavelog-db'."); ?>"></i>
														<input type="text" id="db_hostname" placeholder="localhost" class="form-control" name="db_hostname" />
													</div>
												</div>
												<div class="col">
													<div class="mb-3">
														<label for="db_name" class="form-label required"><?= __("Database Name"); ?></label><i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Name of the Database"); ?>"></i>
														<input type="text" id="db_name" placeholder="wavelog" class="form-control" name="db_name" />
													</div>
												</div>
											</div>
											<div class="mb-3">
												<label for="db_username" class="form-label required"><?= __("Username"); ?></label><i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Username of the Database User which has full access to the database."); ?>"></i>
												<input type="text" id="db_username" placeholder="waveloguser" class="form-control" name="db_username" />
											</div>
											<div class="mb-3">
												<label for="db_password" class="form-label"><?= __("Password"); ?></label><i id="callbook_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Directory Hint" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Password of the Database User"); ?>"></i>
												<input type="password" id="db_password" placeholder="supersecretpassword" class="form-control" name="db_password" />
											</div>
											<div class="col">
												<button id="db_connection_test_button" type="button" class="btn btn-primary" onclick="db_connection_test()"><?= __("Connection Test"); ?></button>
												<div class="mt-2 mb-2 alert" id="db_connection_testresult"></div>
											</div>
										</div>
									</div>
								</div>

								<!-- Tab 5: First User -->
								<div class="tab-pane fade" id="firstuser" role="tabpanel" aria-labelledby="firstuser-tab">
									<div class="row">
										<div class="col-md-6 mb-2">
											<p style="margin-top: 10px;"><?= __("Now you can create your first user in Wavelog. Fill out all fields and click continue. Make sure you use a safe password."); ?></p>
											<p class="required-prefix"><?= __("All fields are required!"); ?></p>
										</div>
										<div class="col-md-6 mb-2">
											<div class="alert" id="userform_warnings" style="display: none; margin-top: 10px;"></div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-2">
											<label for="firstname" class="form-label"><?= __("First Name"); ?></label>
											<input type="text" id="firstname" tabindex="1" placeholder="Ham" class="form-control" name="firstname" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="dxcc" class="form-label"><?= __("DXCC"); ?></label>
											<select class="form-control" id="dxcc_id" name="dxcc" tabindex="7" aria-describedby="stationCallsignInputHelp">
												<option value="" selected><?= __("Please select one"); ?></option>
												<option value="2">Abu Ail Is - A1 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="3">Afghanistan - YA</option>
												<option value="4">Agalega &amp; St Brandon Islands - 3B7</option>
												<option value="5">Aland Islands - OH0</option>
												<option value="6">Alaska - KL7</option>
												<option value="7">Albania - ZA</option>
												<option value="8">Aldabra - VQ9/A (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="19">Bajo Nuevo - HK0 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="23">Blenheim Reef - 1B (<?= __("Deleted DXCC"); ?>)</option>
												<option value="104">Bolivia - CP</option>
												<option value="520">Bonaire - PJ4</option>
												<option value="85">Bonaire, Curacao (neth Antilles) - PJ2/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="501">Bosnia-herzegovina - E7</option>
												<option value="402">Botswana - A2</option>
												<option value="24">Bouvet Island - 3Y/B</option>
												<option value="108">Brazil - PY</option>
												<option value="25">British North Borneo - ZC5 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="26">British Somaliland - VQ6 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="65">British Virgin Islands - VP2V</option>
												<option value="345">Brunei - V8</option>
												<option value="212">Bulgaria - LZ</option>
												<option value="480">Burkina Faso - XT</option>
												<option value="404">Burundi - 9U</option>
												<option value="312">Cambodia - XU</option>
												<option value="406">Cameroon - TJ</option>
												<option value="1">Canada - VE</option>
												<option value="28">Canal Zone - KZ5 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="29">Canary Islands - EA8</option>
												<option value="409">Cape Verde - D4</option>
												<option value="69">Cayman Islands - ZF</option>
												<option value="30">Celebe &amp; Molucca Islands - PK6 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="39">Comoro Islands - FH8 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="218">Czechoslovakia - OK/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="42">Damao, Diu - CR8/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="414">Dem. Rep. Of The Congo - 9Q</option>
												<option value="221">Denmark - OZ</option>
												<option value="43">Desecheo Island - KP5</option>
												<option value="44">Desroches - VQ9/D (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="55">Farquhar - VQ9/F (<?= __("Deleted DXCC"); ?>)</option>
												<option value="230">Federal Republic Of Germany - DL</option>
												<option value="56">Fernando De Noronha - PY0F</option>
												<option value="176">Fiji Islands - 3D2</option>
												<option value="224">Finland - OH</option>
												<option value="227">France - F</option>
												<option value="61">Franz Josef Land - R1F</option>
												<option value="57">French Equatorial Africa - FQ8 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="63">French Guiana - FY</option>
												<option value="67">French India - FN8 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="58">French Indo-china - FI8 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="175">French Polynesia - FO</option>
												<option value="59">French West Africa - FF (<?= __("Deleted DXCC"); ?>)</option>
												<option value="420">Gabon - TR</option>
												<option value="71">Galapagos Islands - HC8</option>
												<option value="75">Georgia - 4L</option>
												<option value="229">German Democratic Republic - DM (<?= __("Deleted DXCC"); ?>)</option>
												<option value="81">Germany - DL/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="93">Geyser Reef - 1G (<?= __("Deleted DXCC"); ?>)</option>
												<option value="424">Ghana - 9G</option>
												<option value="233">Gibraltar - ZB2</option>
												<option value="99">Glorioso Island - FT/G</option>
												<option value="101">Goa - CR8/G (<?= __("Deleted DXCC"); ?>)</option>
												<option value="102">Gold Coast Togoland - ZD4 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="113">Ifni - EA9/I (<?= __("Deleted DXCC"); ?>)</option>
												<option value="324">India - VU</option>
												<option value="327">Indonesia - YB</option>
												<option value="330">Iran - EP</option>
												<option value="333">Iraq - YI</option>
												<option value="245">Ireland - EI</option>
												<option value="114">Isle Of Man - GD</option>
												<option value="336">Israel - 4X</option>
												<option value="115">Italian Somali - I5 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="248">Italy - I</option>
												<option value="117">Itu Hq - 4U1ITU</option>
												<option value="82">Jamaica - 6Y</option>
												<option value="118">Jan Mayen - JX</option>
												<option value="339">Japan - JA</option>
												<option value="119">Java - PK1 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="122">Jersey - GJ</option>
												<option value="123">Johnston Island - KH3</option>
												<option value="342">Jordan - JY</option>
												<option value="124">Juan De Nova, Europa - FT/J</option>
												<option value="125">Juan Fernandez Islands - CE0Z</option>
												<option value="126">Kaliningrad - UA2</option>
												<option value="127">Kamaran Islands - VS9K (<?= __("Deleted DXCC"); ?>)</option>
												<option value="128">Karelo-finn Rep - UN1 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="130">Kazakhstan - UN</option>
												<option value="430">Kenya - 5Z</option>
												<option value="131">Kerguelen Island - FT5/X</option>
												<option value="133">Kermadec Island - ZL8</option>
												<option value="468">Kingdom Of Eswatini - 3DA</option>
												<option value="134">Kingman Reef - KH5K (<?= __("Deleted DXCC"); ?>)</option>
												<option value="138">Kure Island - KH7K</option>
												<option value="139">Kuria Muria Island - VS9H (<?= __("Deleted DXCC"); ?>)</option>
												<option value="348">Kuwait - 9K</option>
												<option value="68">Kuwait/saudi Arabia Neut. Zone - 8Z5 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="155">Malaya - VS2 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="159">Maldives - 8Q</option>
												<option value="442">Mali - TZ</option>
												<option value="161">Malpelo Island - HK0/M</option>
												<option value="257">Malta - 9H</option>
												<option value="151">Malyj Vysotskij Island - R1M (<?= __("Deleted DXCC"); ?>)</option>
												<option value="164">Manchuria - C9 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="178">Minerva Reef - 1M (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="183">Netherlands Borneo - PK5 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="184">Netherlands New Guinea - JZ0 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="162">New Caledonia - FK</option>
												<option value="170">New Zealand - ZL</option>
												<option value="16">New Zealand Subantarctic Islands - ZL9</option>
												<option value="186">Newfoundland Labrador - VO (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="193">Okinawa - KR6 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="194">Okino Tori-shima - 7J1 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="370">Oman - A4</option>
												<option value="372">Pakistan - AP</option>
												<option value="22">Palau - T8</option>
												<option value="510">Palestine - E4</option>
												<option value="196">Palestine (deleted) - ZC6 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="197">Palmyra &amp; Jarvis Islands - KH5</option>
												<option value="88">Panama - HP</option>
												<option value="163">Papua New Guinea - P2</option>
												<option value="198">Papua Terr - VK9/P (<?= __("Deleted DXCC"); ?>)</option>
												<option value="132">Paraguay - ZP</option>
												<option value="493">Penguin Islands - ZS0 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="243">People's Dem Rep Of Yemen - VS9A (<?= __("Deleted DXCC"); ?>)</option>
												<option value="136">Peru - OA</option>
												<option value="199">Peter 1 Island - 3Y/P</option>
												<option value="375">Philippines - DU</option>
												<option value="172">Pitcairn Island - VP6</option>
												<option value="269">Poland - SP</option>
												<option value="272">Portugal - CT</option>
												<option value="200">Portuguese Timor - CR8/T (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="208">Ruanda-urundi - 9U (<?= __("Deleted DXCC"); ?>)</option>
												<option value="454">Rwanda - 9X</option>
												<option value="210">Saar - 9S4 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="220">Sarawak - VS4 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="225">Sardinia - IS0</option>
												<option value="378">Saudi Arabia - HZ</option>
												<option value="226">Saudi Arabia/iraq Neut Zone - 8Z4 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="506">Scarborough Reef - BS7H</option>
												<option value="279">Scotland - GM</option>
												<option value="456">Senegal - 6W</option>
												<option value="296">Serbia - YT</option>
												<option value="228">Serrana Bank &amp; Roncador Cay - HK0/S (<?= __("Deleted DXCC"); ?>)</option>
												<option value="379">Seychelles Islands - S7</option>
												<option value="458">Sierra Leone - 9L</option>
												<option value="231">Sikkim - AC3 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="381">Singapore - 9V</option>
												<option value="518">Sint Maarten - PJ7</option>
												<option value="255">Sint Maarten, Saba, St Eustatius - PJ7/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="504">Slovak Republic - OM</option>
												<option value="499">Slovenia - S5</option>
												<option value="185">Solomon Islands - H4</option>
												<option value="232">Somalia - T5</option>
												<option value="234">South Cook Islands - E5/S</option>
												<option value="235">South Georgia Island - VP0G</option>
												<option value="238">South Orkney Islands - VP8O</option>
												<option value="240">South Sandwich Islands - VP0S</option>
												<option value="241">South Shetland Islands - VP8H</option>
												<option value="244">Southern Sudan - ST0/D (<?= __("Deleted DXCC"); ?>)</option>
												<option value="246">Sov Military Order Of Malta - 1A0</option>
												<option value="281">Spain - EA</option>
												<option value="247">Spratly Islands - 1S</option>
												<option value="315">Sri Lanka - 4S</option>
												<option value="466">Sudan - ST</option>
												<option value="258">Sumatra - PK4 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="140">Suriname - PZ</option>
												<option value="259">Svalbard - JW</option>
												<option value="515">Swains Island - KH8/S</option>
												<option value="261">Swan Island - KS4 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="284">Sweden - SM</option>
												<option value="287">Switzerland - HB</option>
												<option value="384">Syria - YK</option>
												<option value="386">Taiwan - BU</option>
												<option value="262">Tajikistan - EY</option>
												<option value="264">Tangier - CN2 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="470">Tanzania - 5H</option>
												<option value="507">Temotu Province - H40</option>
												<option value="267">Terr New Guinea - VK9/T (<?= __("Deleted DXCC"); ?>)</option>
												<option value="387">Thailand - HS</option>
												<option value="422">The Gambia - C5</option>
												<option value="268">Tibet - AC4 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="511">Timor-leste - 4W</option>
												<option value="483">Togo - 5V7</option>
												<option value="270">Tokelau Islands - ZK3</option>
												<option value="160">Tonga - A3</option>
												<option value="271">Trieste - I1 (<?= __("Deleted DXCC"); ?>)</option>
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
												<option value="488">Walvis Bay - ZS9 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="299">West Malaysia - 9M2</option>
												<option value="301">Western Kiribati - T30</option>
												<option value="302">Western Sahara - S0</option>
												<option value="303">Willis Island - VK9W</option>
												<option value="492">Yemen - 7O</option>
												<option value="154">Yemen Arab Republic - 4W (<?= __("Deleted DXCC"); ?>)</option>
												<option value="482">Zambia - 9J</option>
												<option value="307">Zanzibar - VQ1 (<?= __("Deleted DXCC"); ?>)</option>
												<option value="452">Zimbabwe - Z2</option>
											</select>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-2">
											<label for="lastname" class="form-label"><?= __("Last Name"); ?></label>
											<input type="text" id="lastname" tabindex="2" placeholder="Radio" class="form-control" name="lastname" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="callsign" class="form-label"><?= __("Callsign"); ?></label>
											<input type="text" id="callsign" tabindex="8" placeholder="4W7EST" class="form-control uppercase" name="callsign" />
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-2">
											<label for="username" class="form-label"><?= __("Username"); ?></label>
											<input type="text" id="username" tabindex="3" placeholder="ham.radio" class="form-control" name="username" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="userlocator" class="form-label"><?= __("Gridsquare/Locator"); ?></label>
											<input type="text" id="userlocator" tabindex="9" placeholder="HA44AA" class="form-control uppercase" name="userlocator" />
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-2 position-relative">
											<label for="password" class="form-label"><?= __("Password"); ?></label>
											<input type="password" id="password" tabindex="4" placeholder="**********" class="form-control" name="password" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="city" class="form-label"><?= __("City"); ?></label>
											<input type="text" id="city" tabindex="10" placeholder="City" class="form-control" name="city" />
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-2">
											<label for="cnfm_password" class="form-label"><?= __("Confirm Password"); ?></label>
											<input type="password" id="cnfm_password" tabindex="5" placeholder="**********" class="form-control" name="cnfm_password" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="timezone" class="form-label"><?= __("Timezone"); ?></label>
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
									<div class="row">
										<div class="col-md-6 mb-2">
											<label for="user_email" class="form-label"><?= __("E-Mail Address"); ?></label>
											<input type="email" id="user_email" tabindex="6" placeholder="ham.radio@example.com" class="form-control mb-2" name="user_email" />
										</div>
										<div class="col-md-6 mb-2">
											<label for="userlanguage" class="form-label"><?= __("Language"); ?></label>
											<select class="form-select" id="userlanguage" name="userlanguage" tabindex="12">
												<?php foreach ($languages as $lang) { ?>
													<option value="<?php echo $lang['folder']; ?>" <?php if ($lang['gettext'] == $language) {
																										echo 'selected';
																									} ?>><?= __($lang['name_en']); ?></option>
												<?php } ?>
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
													<h4><?= __("Checklist"); ?></h4>
													<div class="row">
														<div class="col">
															<p class="ms-2">
																<a href="javascript:void(0);" class="text-decoration-none" onclick="openTab('precheck-tab')" style="color: inherit;">
																	<i id="checklist_prechecks" class="me-2 fas <?php echo $prechecks_icon; ?>" style="color: <?php echo $prechecks_color; ?>"></i><?= __("Pre-Checks"); ?>
																</a>
															</p>
														</div>
													</div>
													<div class="row">
														<div class="col">
															<p class="ms-2">
																<a href="javascript:void(0);" class="text-decoration-none" onclick="openTab('configuration-tab')" style="color: inherit;">
																	<i id="checklist_configuration" class="me-2 fas"></i><?= __("Configuration"); ?>
																</a>
															</p>
														</div>
													</div>
													<div class="row">
														<div class="col">
															<p class="ms-2">
																<a href="javascript:void(0);" class="text-decoration-none" onclick="openTab('database-tab')" style="color: inherit;">
																	<i id="checklist_database" class="me-2 fas"></i><?= __("Database"); ?>
																</a>
															</p>
														</div>
													</div>
													<div class="row">
														<div class="col">
															<p class="ms-2">
																<a href="javascript:void(0);" class="text-decoration-none" onclick="openTab('firstuser-tab')" style="color: inherit;">
																	<i id="checklist_firstuser" class="me-2 fas"></i><?= __("First User"); ?>
																</a>
															</p>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col">
											<div class="text-center">
												<h4 style="margin-top: 50px;"><?= __("Nearly done!"); ?></h4>
												<div id="install_is_ready_msg">
													<p style="margin-top: 50px;"><?= __("You prepared all neccessary steps."); ?></p>
													<p><?= __("We now can install Wavelog. This process can take a few minutes."); ?></p>
												</div>
												<button class="btn btn-primary" type="submit" id="submit"></button>
											</div>
										</div>
									</div>
									<a class="btn btn-sm btn-secondary" id="resetButton"><?= __("Reset"); ?></a>
									<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title" id="resetModalLabel"><?= __("Installer Reset"); ?></h5>
												</div>
												<div class="modal-body">
													<p><?= __("Do you really want to reset all data and start from scratch?"); ?></p>
												</div>
												<div class="modal-footer">
													<button type="button" id="resetInstaller" class="btn btn-success"><?= __("Yes"); ?></button>
													<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?= __("No"); ?></button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="card-footer">
						<button type="button" id="BackButton" class="btn btn-primary float-start" style="display: none"><?= __("Back"); ?></button>
						<button type="button" id="ContinueButton" class="btn btn-info float-end" style="display: none"><?= __("Continue"); ?></button>
					</div>
				</div>
			</div>

			<script>
				// We don't want to allow press Enter to trigger Events in the Installer
				// Too dangerous for an average $user
				document.addEventListener('keydown', function(event) {
					if (event.key === 'Enter') {
						event.preventDefault();
					}
				});

				// General stuff on page load
				$(document).ready(function() {

					// tooltip trigger
					const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
					const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

					//fancy stuff
					$("#logo-container img").hide().fadeIn(2000);

					$('#languageButton').click(function() {
						$('#languageModal').modal('show');
					});

					continueButton.css('display', 'block');
					console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
						"Check out our GitHub Repository and dive into the coding adventure:\n\n" +
						"🚀 https://www.github.com/wavelog/wavelog");

				});

				/*
				 *
				 * Tabs Structure and Footer Buttons
				 * 
				 */
				const firstTabId = 'welcome-tab';
				const secondTabId = 'precheck-tab';
				const thirdTabId = 'configuration-tab';
				const fourthTabId = 'database-tab';
				const fifthTabId = 'firstuser-tab';
				const lastTabId = 'finish-tab';

				const continueButton = $('#ContinueButton');
				const backButton = $('#BackButton');

				const tabs = new bootstrap.Tab($('#welcome-tab')[0]);

				// On Page Load
				$(document).ready(function() {
					tabs.show();

					continueButton.on('click', function() {
						openNextTab();
					});
					backButton.on('click', function() {
						openPrevTab();
					});
				});

				async function openNextTab() {
					const activeTab = $('.nav-link.active');
					const nextTab = activeTab.parent().next().find('.nav-link');

					// Open Tab 2 - Prechecks
					if (nextTab.attr('id') == secondTabId) {
						if ($('#precheck_testresults').hasClass('alert-danger')) {
							if (nextTab.length > 0) {
								let tab = new bootstrap.Tab(nextTab[0]);
								tab.show();
							}
							continueButton.prop('disabled', true);
							continueButton.removeClass('btn-info');
							continueButton.addClass('btn-secondary');
							continueButton.html("<?= __("You can't continue. Solve the red marked issues, restart the webserver and reload this page."); ?>");
							backButton.css('display', 'block');
							return;
							// This is a dead end. The user have to solve the issues, restart the webserver and reload the page. 
							// There is no reason to continue if PHP modules are missing or the webserver has not write access to some folders within Wavelog.
							// The checks for PHP Settings are triggering just warnings (except 'allow_url_fopen', this one triggers a failure as it's needed for a lot of different functions (unfortunately)).
						}
					}

					// Exit Tab 3 - Configuration
					if (nextTab.attr('id') == fourthTabId) {
						if (!directory_check() || !websiteurl_check()) {
							return;
						}
						if (!callbook_combination()) {
							return;
						}
						stringForbiddenChars($('#callbook_password'));
						if ($('#callbook_password').hasClass('is-invalid') && $('#callbook_password').val() != '') {
							return;
						}
					}

					// Exit Tab 4 - Database
					if (nextTab.attr('id') == fifthTabId) {
						await db_connection_test();
						if (db_connection_results.hasClass('alert-danger')) {
							return;
						}
					}

					// Exit Tab 5 - User Form
					if (nextTab.attr('id') == lastTabId) {
						if (!check_for_empty_fields()) {
							return;
						}
						if (!isValidMaidenheadLocator(userLocatorField)) {
							return;
						}
						if (!isValidEmail(emailField)) {
							return;
						}
						if (!user_pwd_check()) {
							return;
						}
						if (!callsign_check()) {
							return;
						}
						checklist_configuration();
						checklist_database();
						checklist_firstuser();
						enable_installbutton();
					}

					if (nextTab.length > 0) {
						let tab = new bootstrap.Tab(nextTab[0]);
						tab.show();
					}

					if (nextTab.attr('id') !== lastTabId) {
						continueButton.css('display', 'block');
						backButton.css('display', 'block');
					} else {
						continueButton.css('display', 'none');
					}
				}

				function openPrevTab() {
					const activeTab = $('.nav-link.active');
					const prevTab = activeTab.parent().prev().find('.nav-link');

					if (prevTab.length > 0) {
						let tab = new bootstrap.Tab(prevTab[0]);
						tab.show();
					}

					if (prevTab.attr('id') !== firstTabId) {
						continueButton.css('display', 'block');
						backButton.css('display', 'block');
					} else {
						backButton.css('display', 'none');
					}
				}

				function openTab(tabId) {
					let tab = $('#' + tabId);
					if (tab.length > 0) {
						const tabInstance = new bootstrap.Tab(tab[0]);
						tabInstance.show();

						if (tabId === firstTabId) {
							backButton.css('display', 'none');
							continueButton.css('display', 'block');
						} else if (tabId === lastTabId) {
							continueButton.css('display', 'none');
							backButton.css('display', 'block');
						} else {
							backButton.css('display', 'block');
							continueButton.css('display', 'block');
						}
					}
				}

				function input_is_valid(field, status) {
					if (status == 'is-invalid') {
						field.removeClass('is-valid has-warning');
						field.addClass('is-invalid');
						return;
					}
					if (status == 'has-warning') {
						field.removeClass('is-valid is-invalid');
						field.addClass('has-warning');
						return;
					}
					if (status == 'is-valid') {
						field.removeClass('is-invalid has-warning');
						field.addClass('is-valid');
						return;
					}
					console.error('input_is_valid(): Unknown status: ' + status);
				}

				function stringForbiddenChars(field) {
					let string = field.val();
					let specialChars = /['"\/\\<>]/;

					if (string != '') {
						if (specialChars.test(string)) {
							input_is_valid(field, 'is-invalid');
							if (field == passwordField) {
								show_userformwarnings('danger', "<?= __("Password can't contain ' / \ < >"); ?>");
								return;
							}
							if (field == userCallsignField) {
								show_userformwarnings('danger', "<?= __("The callsign should not contain any pre- or suffixes as it is used as personal operator callsign."); ?>");
								return;
							}
						} else {
							input_is_valid(field, 'is-valid');
							hide_userformwarnings();
						}

						// we also test the userCallsignField for any special characters
						if (field == userCallsignField) {
							if (!/^[a-zA-Z0-9]+$/.test(string)) {
								input_is_valid(field, 'is-invalid');
								show_userformwarnings('danger', "<?= __("The callsign can not contain any special characters. It's your personal callsign without any pre- or suffixes."); ?>");
								return;
							} else {
								input_is_valid(field, 'is-valid');
								hide_userformwarnings();
								return;
							}
						}
					} else {
						field.removeClass('is-invalid');
						field.removeClass('is-valid');
					}
				}

				/*
				 *
				 *	General Requirement Levels
				 * 		hard = No continue allowed, no install possible.
				 * 		soft = Shows yellow warning, but install and continue allowed.
				 * 
				 */


				/*
				 * Tab 2 - Prechecks
				 * 
				 * 		Pre-Check Verification is handled in PHP, see $prechecks_passed.
				 * 		So nothing to see here, just a placeholder for structure.
				 * 
				 */

				// empty


				/*
				 * Tab 3 - Configuration
				 * 
				 * 		Rules:
				 * 		Website-URL and Directory have to be green. No checks needed 'Advanced Settings'.
				 * 
				 * 		Callbook Password:
				 * 			- do not allow specialchars defined in stringForbiddenChars() (hard)
				 * 
				 * 		Directory:
				 * 			- no slash allowed (hard)
				 * 
				 * 		Website-URL:
				 *			- can't be empty (hard)
				 *			- has to have a trailing slash (hard)
				 *			- have to start with http (hard)
				 * 
				 */

				let directory = $('#directory');
				let websiteurl = $('#websiteurl');
				let callbook_username = $('#callbook_username');
				let callbook_password = $('#callbook_password');

				// On Page Load
				$(document).ready(function() {

					$('#advancedSettingsButton').click(function() {
						$('#advancedSettingsModal').modal('show');
					});

					directory.on('change', function() {
						directory_check();
					});

					websiteurl.on('change', function() {
						websiteurl_check();
					});

					callbook_username.on('change', function() {
						if (callbook_username.val() == '') {
							callbook_username.removeClass('is-valid is-invalid');
						}
						if (callbook_password.val() == '') {
							callbook_password.removeClass('is-valid is-invalid');
						}
					});

					callbook_password.on('change', function() {
						if (callbook_password.val() == '') {
							callbook_password.removeClass('is-valid is-invalid');
						}
						if (callbook_username.val() == '') {
							callbook_username.removeClass('is-valid is-invalid');
						}
						stringForbiddenChars(callbook_password);
					});
				});

				function directory_check() {
					var check = true;

					if (directory.val().startsWith('/') || directory.val().endsWith('/')) {
						check = false;
					}

					input_is_valid(directory, check ? 'is-valid' : 'is-invalid');

					return check;
				}

				function websiteurl_check() {
					var check = true;

					if (websiteurl.val() == '') {
						check = false;
					} else if (!websiteurl.val().endsWith('/')) {
						check = false;
					} else if (!websiteurl.val().startsWith('http')) {
						check = false;
					}

					if (check) {
						input_is_valid(websiteurl, 'is-valid');
					} else {
						input_is_valid(websiteurl, 'is-invalid');
					}

					return check;
				}

				function callbook_combination() {
					let check = true;
					let a = callbook_username.val();
					let b = callbook_password.val();
					if ((a == '' && b !== '') || (a !== '' && b == '')) {
						check = false;
						if (a == '') {
							input_is_valid(callbook_username, 'is-invalid');
						} else {
							input_is_valid(callbook_password, 'is-invalid');
						}

					} else if (a !== '' && b !== '') {
						input_is_valid(callbook_username, 'is-valid');
						stringForbiddenChars(callbook_password);
					}

					return check;
				}

				/*
				 * Tab 4 - Database
				 * 
				 * 		Rules:
				 * 			- Password can be empty
				 * 			- All other inputs can't be empty (hard)
				 * 			- Connection have to be successful (hard) 
				 * 			- Database itself have to be empty (hard)
				 * 			- MySQL/MariaDB Version below Minimum (soft) -> defined in install/includes/install_config/install_config.php
				 * 
				 */
				let db_connection_results = $('#db_connection_testresult');

				let db_hostname = $('#db_hostname');
				let db_username = $('#db_username');
				let db_password = $('#db_password');
				let db_name = $('#db_name');

				function db_connection_test() {
					return new Promise((resolve, reject) => {


						if (db_hostname.val() === '' || db_username.val() === '' || db_name.val() === '') {
							db_connection_results.addClass('alert-danger');
							db_connection_results.html("<?= __("Error: At least Hostname/IP, Database Name and Username are required."); ?>");
							resolve(false);
							return;
						}

						if (db_hostname.val() === '%') {
							db_hostname.val('localhost');
						}

						var originalButtonText = $('#db_connection_test_button').html();
						$('#db_connection_test_button').html("<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> <?= __("Connecting..."); ?>").prop('disabled', true);

						clear_db_testresult();

						$.ajax({
							type: 'POST',
							url: 'index.php',
							data: {
								db_hostname: db_hostname.val(),
								db_username: db_username.val(),
								db_password: db_password.val(),
								db_name: db_name.val(),
								database_check: 1
							},
							success: function(response) {
								db_connection_results.html(response);
								if (response.indexOf('Error') !== -1) {
									db_connection_results.addClass('alert-danger');
									$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
									resolve(false);
								} else {
									if (sql_version_checker(response) == true) {
										db_connection_results.addClass('alert-success');
										$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
										db_connection_results.html("<?= __("Connection was successful and your database should be compatible."); ?> <i class=\"fas fa-check-circle\"></i>");
									} else {
										db_connection_results.addClass('alert-warning');
										$('#db_connection_test_button').html(originalButtonText).prop('disabled', false);
										db_connection_results.html("<?= __('Connection was successful but your database seems too old for Wavelog. You can try to continue but you could run into issues.'); ?> <i class=\"fas fa-circle-exclamation\"></i> " + "</br></br><?= sprintf(__("The min. version for MySQL is %s, for MariaDB it's %s."), '<b>' . $mysql_version . '</b>', '<b>' . $mariadb_version . '</b>'); ?>");
									}
									resolve(true);
								}
								checklist_database();
							},
							error: function(error) {
								db_connection_results.html('Error: ' + error.statusText);
								if (db_connection_results.text().indexOf('Error') !== -1) {
									db_connection_results.addClass('alert-danger');
								}
								checklist_database();
								resolve(false);
							}
						});
					});
				}

				function clear_db_testresult() {
					db_connection_results.html('');
					db_connection_results.removeClass('alert-danger');
					db_connection_results.removeClass('alert-success');
					db_connection_results.removeClass('alert-warning');
					checklist_database();
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

				/*
				 * Tab 5 - First User
				 * 
				 * 		Rules:
				 * 			- do not allow specialchars in userpassword defined in stringForbiddenChars() (hard)
				 * 			- No input can be empty (hard)
				 * 			- Locator have to match regex (hard)
				 * 			- E-Mail have to match regex (hard)
				 * 			- Password should have at least 8 charachters (soft)
				 * 			- Password and Password-Confirmation have to match (hard)
				 * 
				 */

				let passwordField = $('#password');
				let cnfmPasswordField = $('#cnfm_password');
				let minPasswordLenght = 8;

				const firstUserInputIDs = [
					'#firstname',
					'#lastname',
					'#username',
					'#password',
					'#cnfm_password',
					'#callsign',
					'#city',
					'#user_email',
					'#dxcc_id',
					'#userlocator'
				];

				let emailField = $('#user_email');
				let userLocatorField = $('#userlocator');
				let userCallsignField = $('#callsign');

				let userFormWarnings = $('#userform_warnings');

				// On Page Load
				$(document).ready(function() {
					firstUserInputIDs.forEach(function(inputID) {
						$(inputID).on('change', function() {
							if ($(inputID).hasClass('is-invalid')) {
								input_is_valid($(inputID), 'is-valid');
								hide_userformwarnings();
							}
						});
					});
					userLocatorField.on('change', function() {
						isValidMaidenheadLocator(userLocatorField);
					});
					emailField.on('change', function() {
						isValidEmail(emailField);
					});
					passwordField.on('change', function() {
						stringForbiddenChars(passwordField);
					});
					if (passwordField !== '') {
						stringForbiddenChars(passwordField);
					}
					userCallsignField.on('change', function() {
						stringForbiddenChars(userCallsignField);
					});
					cnfmPasswordField.on('change', function() {
						user_pwd_check();
					});

					$('#dxcc_id').multiselect({
						// initialize multiselect dropdown for locations
						// Documentation: https://davidstutz.github.io/bootstrap-multiselect/index.html
						// template is needed for bs5 support
						templates: {
							button: '<button id="dxcc_button" type="button" style="text-align: left !important;" class="multiselect dropdown-toggle btn btn-secondary" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
							filter: '<div class="multiselect-filter d-flex align-items-center pb-1 border-bottom"><i class="fas fa-search text-muted ps-2 me-2"></i><input type="search" class="multiselect-search form-control" /></div>',
						},
						enableFiltering: true,
						filterPlaceholder: "<?= __("Search"); ?>",
						enableFullValueFiltering: false,
						enableCaseInsensitiveFiltering: true,
						widthSynchronizationMode: 'always',
						numberDisplayed: 1,
						inheritClass: true,
						buttonWidth: '100%',
						maxHeight: 300,
						dropUp: false
					});
					$('#dxcc_id').hide();
					$('.multiselect-container .multiselect-filter', $('#dxcc_id').parent()).css({
						'position': 'sticky',
						'margin-top': '3px',
						'top': '0px',
						'z-index': 1,
						'background-color': 'inherit',
						'width': '100%',
						'height': '39px',
						'padding-left': '1px'
					});

					$('#dxcc_id').on('change', function() {
						DXCC_Warning();
					});
					DXCC_Warning();
				});

				function check_for_empty_fields() {
					let check = true;
					firstUserInputIDs.forEach(function(inputID) {
						if ($(inputID).val() == '') {
							if (inputID == '#dxcc_id') {
								input_is_valid($('#dxcc_button'), 'is-invalid');
							} else {
								input_is_valid($(inputID), 'is-invalid');
							}
							show_userformwarnings('danger', "<?= __("At least one field is empty."); ?>");
							return check = false;
						} else {
							if ($(inputID).hasClass('is-invalid')) {
								hide_userformwarnings();
								input_is_valid($(inputID), 'is-valid');
							}
							if (inputID == '#dxcc_id') {
								input_is_valid($('#dxcc_button'), 'is-valid');
							}
						}
					});
					return check;

				}

				function DXCC_Warning() {
					if ($("#dxcc_id option:selected").text().includes("<?= __("Deleted DXCC"); ?>")) {
						$('#dxcc_button').addClass('has-warning');
						show_userformwarnings("warning", "<?= __("Stop here for a Moment. Your chosen DXCC is outdated and not valid anymore. Check which DXCC for this particular location is the correct one. If you are sure, ignore this warning."); ?>");
					} else {
						$('#dxcc_button').removeClass('has-warning');
						hide_userformwarnings();
					}
				}

				function show_userformwarnings(status, message) {
					userFormWarnings.show();
					userFormWarnings.removeClass('alert-warning alert-danger');
					userFormWarnings.addClass('alert-' + status);
					userFormWarnings.html(message);
				}

				function hide_userformwarnings() {
					userFormWarnings.hide();
					userFormWarnings.removeClass('alert-warning alert-danger');
				}

				function isValidMaidenheadLocator(field) {
					let locator = field.val();
					const maidenheadRegex = /^[A-R]{2}[0-9]{2}[A-X]{2}$/i;
					let check = maidenheadRegex.test(locator);
					if (!check) {
						show_userformwarnings('danger', "<?= __("The locator seems to be not in the correct format. Should look like AA11AA (6-char grid locator).") ?>")
						input_is_valid(field, 'is-invalid');
					} else {
						hide_userformwarnings();
						input_is_valid(field, 'is-valid');
					}
					return check;
				}

				function isValidEmail(field) {
					let email = field.val();
					const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					let check = emailRegex.test(email);
					if (!check) {
						show_userformwarnings('danger', "<?= __("The e-mail adress does not look correct. Make sure it's a valid e-mail address") ?>")
						input_is_valid(field, 'is-invalid');
					} else {
						hide_userformwarnings();
						input_is_valid(field, 'is-valid');
					}
					return check;
				}

				function user_pwd_check() {
					stringForbiddenChars(passwordField);
					if (passwordField.hasClass('is-invalid')) {
						return false;
					}
					if (cnfmPasswordField.val() == passwordField.val() && cnfmPasswordField.val() != '') {

						if (cnfmPasswordField.val().length >= minPasswordLenght) {

							input_is_valid(passwordField, 'is-valid');
							input_is_valid(cnfmPasswordField, 'is-valid');

							hide_userformwarnings();

						} else {
							input_is_valid(passwordField, 'has-warning');
							input_is_valid(cnfmPasswordField, 'has-warning');

							show_userformwarnings('warning', "<?= __("Password should be at least 8 characters long"); ?>");
						}
						return true;

					} else {

						input_is_valid(passwordField, 'is-invalid');
						input_is_valid(cnfmPasswordField, 'is-invalid');

						show_userformwarnings('danger', "<?= __("Passwords do not match"); ?>");

						return false;

					}
				}

				function callsign_check() {
					stringForbiddenChars(userCallsignField);
					if (userCallsignField.hasClass('is-invalid')) {
						return false;
					} else {
						return true;
					}
				}

				/*
				 * Tab 6 - Install
				 * 
				 * 	Rules:
				 * 		The checklist have te fully green, but can contain yellow warnings.
				 * 		If one of the items has an error warning, the install button is disabled and 
				 * 		an install is not possible. 
				 * 
				 */

				let resetButton = $('#resetButton');
				let resetModal = $('#resetModal');

				let checklistPrechecks = $('#checklist_prechecks');
				let checklistConfiguration = $('#checklist_configuration');
				let checklistDatabase = $('#checklist_database');
				let checklistFirstUser = $('#checklist_firstuser');


				// On Page Load
				$(document).ready(function() {

					resetButton.click(function() {
						resetModal.modal('show');
					});

				});


				function enable_installbutton() {
					var install_possible = false;

					if ((checklistPrechecks.hasClass('fa-check-circle') || checklistPrechecks.hasClass('fa-exclamation-triangle')) &&
						checklistConfiguration.hasClass('fa-check-circle') &&
						checklistDatabase.hasClass('fa-check-circle') &&
						(checklistFirstUser.hasClass('fa-check-circle') || checklistFirstUser.hasClass('fa-exclamation-triangle'))) {
						install_possible = true;
					}

					if (install_possible) {
						$('#submit').prop('disabled', false).html("<?= __("Install Now"); ?>");
						$('#install_is_ready_msg').show();
						$('#submit').css('margin-top', '50px');
					} else {
						$('#submit').prop('disabled', true).html("<?= __("Install not possible. Checklist incomplete."); ?>");
						$('#install_is_ready_msg').hide();
						$('#submit').css('margin-top', '150px');
					}

				}

				function checklist_configuration() {
					var checklist_configuration = true;

					if ($('#callbook_password').hasClass('is-invalid')) {
						checklist_configuration = false;
					}

					if ($('#directory').hasClass('is-invalid')) {
						checklist_configuration = false;
					}

					if ($('#websiteurl').val() == '' || $('#websiteurl').hasClass('is-invalid')) {
						checklist_configuration = false;
					}

					if (checklist_configuration) {
						checklistConfiguration.removeClass('fa-times-circle');
						checklistConfiguration.addClass('fa-check-circle').css('color', '#04a004');
					} else {
						checklistConfiguration.removeClass('fa-check-circle');
						checklistConfiguration.addClass('fa-times-circle').css('color', 'red');
					}
				}

				function checklist_database() {
					var checklist_database = true;

					if ($('#db_hostname').val() === '') {
						checklist_database = false;
					}
					if ($('#db_name').val() === '') {
						checklist_database = false;
					}
					if ($('#db_username').val() === '') {
						checklist_database = false;
					}

					var checklist_icon = checklistDatabase;
					checklist_icon.removeClass('fa-check-circle fa-times-circle fa-exclamation-triangle');

					if (checklist_database) {
						if (db_connection_results.hasClass('alert-warning')) {
							checklist_icon.addClass('fa-exclamation-triangle').css('color', '#ffc107');
						} else if (db_connection_results.hasClass('alert-success')) {
							checklist_icon.addClass('fa-check-circle').css('color', '#04a004');
						} else {
							checklist_icon.addClass('fa-times-circle').css('color', 'red');
						}
					} else {
						checklist_icon.addClass('fa-times-circle').css('color', 'red');
					}
				}

				function checklist_firstuser() {
					var checklist_firstuser = true;

					firstUserInputIDs.forEach(function(inputID) {
						if ($(inputID).val() == '') {
							input_is_valid($(inputID), 'is-invalid');
							checklist_firstuser = false;
						} else {
							input_is_valid($(inputID), 'is-valid');
							stringForbiddenChars(userCallsignField);
							user_pwd_check();
						}
					});

					if (passwordField.hasClass('is-invalid')) {
						checklist_firstuser = false;
					}

					if (cnfmPasswordField.hasClass('is-invalid')) {
						checklist_firstuser = false;
					}
					if ($(emailField).hasClass('is-invalid')) {
						checklist_firstuser = false;
					}
					if (userLocatorField.hasClass('is-invalid')) {
						checklist_firstuser = false;
					}
					if (userCallsignField.hasClass('is-invalid')) {
						checklist_firstuser = false;
					}

					if (checklist_firstuser) {
						if (passwordField.hasClass('has-warning') || $('#dxcc_button').hasClass('has-warning')) {
							checklistFirstUser.removeClass('fa-times-circle');
							checklistFirstUser.removeClass('fa-check-circle');
							checklistFirstUser.addClass('fa-exclamation-triangle').css('color', '#ffc107');
						} else {
							checklistFirstUser.removeClass('fa-times-circle');
							checklistFirstUser.removeClass('fa-exclamation-triangle');
							checklistFirstUser.addClass('fa-check-circle').css('color', '#04a004');
						}
					} else {
						checklistFirstUser.removeClass('fa-check-circle');
						checklistFirstUser.removeClass('fa-exclamation-triangle');
						checklistFirstUser.addClass('fa-times-circle').css('color', 'red');
					}

					return checklist_firstuser;
				}
			</script>
		</body>

		<?php include 'includes/interface_assets/footer.php'; ?>

	<?php } else { ?>

		<body>
			<div class="container" style="max-width: 1200px; margin-top: 8rem; ">
				<div class="card mt-4" style="min-height: 750px; margin: 0 auto;">
					<div class="card-body text-center p-4">
						<h3 style="margin-top: 50px;"><?= __("PHP Module missing"); ?></h3>
						<img src="assets/images/danger_triangle.png" alt="danger_triangle" style="max-width: 400px; height: auto; margin-bottom: 50px;">
						<p><?= __("The following PHP modules are missing:") . " <code>" . implode(',', installer_required_modules()) . "</code>"; ?></p>
						<p><?= __("Without this module the Wavelog Installer does not work!"); ?></p>
						<p><?= __("Please install the required modules and restart the webserver."); ?></p>
					</div>
				</div>
			</div>
		</body>

	<?php } ?>

<?php } else {

	header("Location: $websiteurl");
} ?>

</html>
