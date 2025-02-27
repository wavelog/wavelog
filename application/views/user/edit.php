<div class="container">
	<br>
	<h3>
	  <?php if (isset($user_add)) {
		if ($clubstation) {
			echo __("Create Clubstation Account");
		} else {
			echo __("Create User Account");
		}
	  } else {
		echo __("Edit Account")." <small class=\"text-muted\">".$user_name."</small>";
	  }
	  ?>

	</h3>

	<?php if($this->session->flashdata('success')) { ?>
		<!-- Display Success Message -->
		<div class="alert alert-success">
		  <?php echo $this->session->flashdata('success'); ?>
		</div>
	<?php } ?>

	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <?php echo $this->session->flashdata('message'); ?>
		</div>
	<?php } ?>

	<?php if(validation_errors()) { ?>
    <div class="alert alert-danger">
    	<a class="btn-close" data-bs-dismiss="alert">x</a>
 		<?php echo validation_errors(); ?>
    </div>
	<?php } ?>

	<?php $this->load->helper('form'); ?>

	<form method="post" action="<?php echo $user_form_action; ?>" name="users" autocomplete="off">
	<div class="accordion user_edit">
		<!-- ZONE 1 / User General Information -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_user_general">
				<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_user_general" aria-expanded="true" aria-controls="panelsStayOpen-B_user_general">
				<?= __("General Information"); ?></button>
			</h2>
			<div id="panelsStayOpen-B_user_general" class="accordion-collapse collapse show" aria-labelledby="panelsStayOpen-H_user_general">
				<div class="accordion-body">
					<div class="row">
						<!-- Account Information -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Account"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Username"); ?></label>
										<input class="form-control" type="text" name="user_name" value="<?php if(isset($user_name)) { echo $user_name; } ?>" <?php if (isset($user_name) && $user_name == 'demo' && file_exists('.demo') && $this->session->userdata('user_type') !== '99') { echo 'disabled'; } ?> />
										<?php if(isset($username_error)) { echo "<small class=\"badge bg-danger\">".$username_error."</small>"; } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Email Address"); ?></label>
										<input class="form-control" type="text" name="user_email" value="<?php if(isset($user_email)) { echo $user_email; } ?>" />
										<?php if(isset($email_error)) { echo "<small class=\"badge bg-danger\">".$email_error."</small>"; } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Password"); ?></label>
										<div class="input-group">
											<input class="form-control" type="password" name="user_password" value="<?php if(isset($user_password)) { echo $user_password; } ?>" />
											<span class="input-group-btn"><button class="btn btn-default btn-pwd-showhide" type="button"><i class="fa fa-eye-slash"></i></button></span>
										</div>
										<?php if($clubstation) { ?>
											<small class="text-muted"><?= __("Don't share this password with operators!"); ?></small>
										<?php } ?>
										<?php if(isset($password_error)) {
											echo "<small class=\"badge bg-danger\">".$password_error."</small>";
											} else if (!isset($user_add)) { ?>
										<?php } ?>
									</div>

									<hr/>
									<div class="mb-3">
										<label><?= __("User Role"); ?></label>
										<?php if($this->session->userdata('user_type') == 99) { ?>
											<select class="form-select" name="user_type">
											<?php
												if ($clubstation) {
													echo '<option value="3" selected="selected">' . __("Clubstation") . '</option>';
												} else {
													$levels = $this->config->item('auth_level');
													foreach ($levels as $key => $value) {
														echo '<option value="'. $key . '" '. (($user_type ?? '') == $key ? "selected=\"selected\"":""). '>' . $value . '</option>';
													}
												}
											?>
											</select>
										<?php } else {
											$l = $this->config->item('auth_level');
											echo $l[$user_type];
										}?>
										<?php if ($clubstation) { ?>
											<input type="hidden" name="clubstation" value="1" />
										<?php } ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Personal Information -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?php if ($clubstation) { echo __("Callsign Owner"); } else { echo __("Personal");} ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("First Name"); ?></label>
										<input class="form-control" type="text" name="user_firstname" value="<?php if(isset($user_firstname)) { echo $user_firstname; } ?>" />
											<?php if(isset($firstname_error)) { echo "<small class=\"badge bg-danger\">".$firstname_error."</small>"; } else { ?>
											<?php } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Last Name"); ?></label>
										<input class="form-control" type="text" name="user_lastname" value="<?php if(isset($user_lastname)) { echo $user_lastname; } ?>" />
											<?php if(isset($lastname_error)) { echo "<small class=\"badge bg-danger\">".$lastname_error."</small>"; } else { ?>
											<?php } ?>
									</div>
								</div>
							</div>
						</div>
						<!-- Ham Radio Information -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Ham Radio"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?php if ($clubstation) { echo __("Special/Club Callsign"); } else { echo __("Callsign"); } ?></label>
										<input class="form-control uppercase" type="text" name="user_callsign" value="<?php if(isset($user_callsign)) { echo $user_callsign; } ?>" />
											<?php if(isset($callsign_error)) { echo "<small class=\"badge bg-danger\">".$callsign_error."</small>"; } else { ?>
											<?php } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Gridsquare"); ?></label>
										<input class="form-control uppercase" type="text" name="user_locator" value="<?php if(isset($user_locator)) { echo $user_locator; } ?>" />
											<?php if(isset($locator_error)) { echo "<small class=\"badge bg-danger\">".$locator_error."</small>"; } else { ?>
											<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- ZONE 2 / Wavelog -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_wavelog_general">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_wavelog_general" aria-expanded="false" aria-controls="panelsStayOpen-B_wavelog_general">
				<?= __("Wavelog Preferences"); ?></button>
			</h2>
			<div id="panelsStayOpen-B_wavelog_general" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-H_wavelog_general">
				<div class="accordion-body">
					<div class="row mb-3">
						<!-- Wavelog Preferences -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("General"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Theme").' / '.__("Stylesheet"); ?></label>
										<?php if(!isset($user_stylesheet)) { $user_stylesheet='darkly'; }?>
										<select class="form-select" id="user_stylesheet" name="user_stylesheet" required>
											<?php
											foreach ($themes as $theme) {
												echo '<option value="' . $theme->foldername . '" ' . (( $user_stylesheet == $theme->foldername)?'selected="selected"':"") . '>' . $theme->name . '</option>';
											}
											?>
										</select>
									</div>
									<hr/>
									<div class="mb-3">
										<label for="user_language"><?= __("Wavelog Language"); ?></label>
										<?php
										foreach ($existing_languages as $lang) {
											$options[$lang['folder']] = $this->genfunctions->country2flag($lang['flag']). " " . __($lang['name_en']);
										}
										echo form_dropdown('user_language', $options, $user_language);
										?>
										<small id="language_Help" class="form-text text-muted"><?= __("Choose Wavelog language."); ?></small>
									</div>
									<div class="mb-3">
										<label><?= __("Timezone"); ?></label>
										<?php
										if(!isset($user_timezone)) { $user_timezone='151'; }
										echo form_dropdown('user_timezone', $timezones, $user_timezone);
										?>
									</div>

									<div class="mb-3">
										<label for="SelectDateFormat"><?= __("Date Format"); ?></label>
										<?php if(!isset($user_date_format)) { $user_date_format='d/m/y'; }?>
										<select name="user_date_format" class="form-select" id="SelectDateFormat" aria-describedby="SelectDateFormatHelp">
											<option value="d/m/y" <?php if($user_date_format == "d/m/y") { echo "selected=\"selected\""; } ?>><?php echo date('d/m/y'); ?></option>
											<option value="d/m/Y" <?php if($user_date_format == "d/m/Y") { echo "selected=\"selected\""; } ?>><?php echo date('d/m/Y'); ?></option>
											<option value="m/d/y" <?php if($user_date_format == "m/d/y") { echo "selected=\"selected\""; } ?>><?php echo date('m/d/y'); ?></option>
											<option value="m/d/Y" <?php if($user_date_format == "m/d/Y") { echo "selected=\"selected\""; } ?>><?php echo date('m/d/Y'); ?></option>
											<option value="d.m.Y" <?php if($user_date_format == "d.m.Y") { echo "selected=\"selected\""; } ?>><?php echo date('d.m.Y'); ?></option>
											<option value="y/m/d" <?php if($user_date_format == "y/m/d") { echo "selected=\"selected\""; } ?>><?php echo date('y/m/d'); ?></option>
											<option value="Y-m-d" <?php if($user_date_format == "Y-m-d") { echo "selected=\"selected\""; } ?>><?php echo date('Y-m-d'); ?></option>
											<option value="M d, Y" <?php if($user_date_format == "M d, Y") { echo "selected=\"selected\""; } ?>><?php echo date('M d, Y'); ?></option>
											<option value="M d, y" <?php if($user_date_format == "M d, y") { echo "selected=\"selected\""; } ?>><?php echo date('M d, y'); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("Select how you would like dates shown when logged into your account."); ?></small>
									</div>

									<div class="mb-3">
										<label for="user_measurement_base"><?= __("Measurement preference"); ?></label>
										<?php if(!isset($user_measurement_base)) { $user_measurement_base='M'; }?>
										<select class="form-select" id="user_measurement_base" name="user_measurement_base" aria-describedby="user_measurement_base_Help" required>
											<option value=''></option>
											<option value='K' <?php if($user_measurement_base == "K") { echo "selected=\"selected\""; } ?>><?= __("Kilometers"); ?></option>
											<option value='M' <?php if($user_measurement_base == "M") { echo "selected=\"selected\""; } ?>><?= __("Miles"); ?></option>
											<option value='N' <?php if($user_measurement_base == "N") { echo "selected=\"selected\""; } ?>><?= __("Nautical miles"); ?></option>
										</select>
										<small id="user_measurement_base_Help" class="form-text text-muted"><?= __("Choose which unit distances will be shown in"); ?></small>
									</div>

									<div class="mb-3">
										<label for="user_dashboard_map"><?= __("Show Dashboard Map"); ?></label>
										<?php if(!isset($user_dashboard_map)) { $user_dashboard_map='Y'; }?>
										<select class="form-select" id="user_dashboard_map" name="user_dashboard_map" aria-describedby="user_dashboard_map_Help" required>
											<option value='Y' <?php if($user_dashboard_map == "Y") { echo "selected=\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value='map_at_right' <?php if($user_dashboard_map == "map_at_right") { echo "selected=\"selected\""; } ?>><?= __("Map at right"); ?></option>
											<option value='N' <?php if($user_dashboard_map == "N") { echo "selected=\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small id="user_dashboard_map_Help" class="form-text text-muted"><?= __("Choose whether to show map on dashboard or not"); ?></small>
									</div>
								</div>
							</div>
						</div>

						<!-- Logbook fields Setting -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Logbook fields"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label for="column1"><?= __("Choose column 1"); ?></label>
										<?php if(!isset($user_column1)) { $user_column1='Mode'; }?>
										<select class="form-select" id="column1" name="user_column1">
											<option value="Band" <?php if ($user_column1 == "Band") { echo " selected =\"selected\""; } ?>><?= __("Band"); ?></option>
											<option value="Frequency" <?php if ($user_column1 == "Frequency") { echo " selected =\"selected\""; } ?>><?= __("Frequency"); ?></option>
											<option value="Mode" <?php if ($user_column1 == "Mode") { echo " selected =\"selected\""; } ?>><?= __("Mode"); ?></option>
											<option value="RSTS" <?php if ($user_column1 == "RSTS") { echo " selected =\"selected\""; } ?>><?= __("RST (S)"); ?></option>
											<option value="RSTR" <?php if ($user_column1 == "RSTR") { echo " selected =\"selected\""; } ?>><?= __("RST (R)"); ?></option>
											<option value="Country" <?php if ($user_column1 == "Country") { echo " selected =\"selected\""; } ?>><?= __("Country"); ?></option>
											<option value="IOTA" <?php if ($user_column1 == "IOTA") { echo " selected =\"selected\""; } ?>><?= __("IOTA"); ?></option>
											<option value="SOTA" <?php if ($user_column1 == "SOTA") { echo " selected =\"selected\""; } ?>><?= __("SOTA"); ?></option>
											<option value="WWFF" <?php if ($user_column1 == "WWFF") { echo " selected =\"selected\""; } ?>><?= __("WWFF"); ?></option>
											<option value="POTA" <?php if ($user_column1 == "POTA") { echo " selected =\"selected\""; } ?>><?= __("POTA"); ?></option>
											<option value="State" <?php if ($user_column1 == "State") { echo " selected =\"selected\""; } ?>><?= __("State"); ?></option>
											<option value="Grid" <?php if ($user_column1 == "Grid") { echo " selected =\"selected\""; } ?>><?= __("Gridsquare"); ?></option>
											<option value="Distance" <?php if ($user_column1 == "Distance") { echo " selected =\"selected\""; } ?>><?= __("Distance"); ?></option>
											<option value="Operator" <?php if ($user_column1 == "Operator") { echo " selected =\"selected\""; } ?>><?= __("Operator"); ?></option>
											<option value="Name" <?php if ($user_column1 == "Name") { echo " selected =\"selected\""; } ?>><?= __("Name"); ?></option>
											<option value="Bearing" <?php if ($user_column1 == "Bearing") { echo " selected =\"selected\""; } ?>><?= __("Bearing"); ?></option>
										</select>
									</div>

									<div class="mb-3">
										<label for="column2"><?= __("Choose column 2"); ?></label>
										<?php if(!isset($user_column2)) { $user_column2='RSTS'; }?>
										<select class="form-select" id="column2" name="user_column2">
											<option value="Band" <?php if ($user_column2 == "Band") { echo " selected =\"selected\""; } ?>><?= __("Band"); ?></option>
											<option value="Frequency" <?php if ($user_column2 == "Frequency") { echo " selected =\"selected\""; } ?>><?= __("Frequency"); ?></option>
											<option value="Mode" <?php if ($user_column2 == "Mode") { echo " selected =\"selected\""; } ?>><?= __("Mode"); ?></option>
											<option value="RSTS" <?php if ($user_column2 == "RSTS") { echo " selected =\"selected\""; } ?>><?= __("RST (S)"); ?></option>
											<option value="RSTR" <?php if ($user_column2 == "RSTR") { echo " selected =\"selected\""; } ?>><?= __("RST (R)"); ?></option>
											<option value="Country" <?php if ($user_column2 == "Country") { echo " selected =\"selected\""; } ?>><?= __("Country"); ?></option>
											<option value="IOTA" <?php if ($user_column2 == "IOTA") { echo " selected =\"selected\""; } ?>><?= __("IOTA"); ?></option>
											<option value="SOTA" <?php if ($user_column2 == "SOTA") { echo " selected =\"selected\""; } ?>><?= __("SOTA"); ?></option>
											<option value="WWFF" <?php if ($user_column2 == "WWFF") { echo " selected =\"selected\""; } ?>><?= __("WWFF"); ?></option>
											<option value="POTA" <?php if ($user_column2 == "POTA") { echo " selected =\"selected\""; } ?>><?= __("POTA"); ?></option>
											<option value="State" <?php if ($user_column2 == "State") { echo " selected =\"selected\""; } ?>><?= __("State"); ?></option>
											<option value="Grid" <?php if ($user_column2 == "Grid") { echo " selected =\"selected\""; } ?>><?= __("Gridsquare"); ?></option>
											<option value="Distance" <?php if ($user_column2 == "Distance") { echo " selected =\"selected\""; } ?>><?= __("Distance"); ?></option>
											<option value="Operator" <?php if ($user_column2 == "Operator") { echo " selected =\"selected\""; } ?>><?= __("Operator"); ?></option>
											<option value="Name" <?php if ($user_column2 == "Name") { echo " selected =\"selected\""; } ?>><?= __("Name"); ?></option>
											<option value="Bearing" <?php if ($user_column2 == "Bearing") { echo " selected =\"selected\""; } ?>><?= __("Bearing"); ?></option>
										</select>
									</div>

									<div class="mb-3">
										<label for="column3"><?= __("Choose column 3"); ?></label>
										<?php if(!isset($user_column3)) { $user_column3='RSTR'; }?>
										<select class="form-select" id="column3" name="user_column3">
											<option value="Band" <?php if ($user_column3 == "Band") { echo " selected =\"selected\""; } ?>><?= __("Band"); ?></option>
											<option value="Frequency" <?php if ($user_column3 == "Frequency") { echo " selected =\"selected\""; } ?>><?= __("Frequency"); ?></option>
											<option value="Mode" <?php if ($user_column3 == "Mode") { echo " selected =\"selected\""; } ?>><?= __("Mode"); ?></option>
											<option value="RSTS" <?php if ($user_column3 == "RSTS") { echo " selected =\"selected\""; } ?>><?= __("RST (S)"); ?></option>
											<option value="RSTR" <?php if ($user_column3 == "RSTR") { echo " selected =\"selected\""; } ?>><?= __("RST (R)"); ?></option>
											<option value="Country" <?php if ($user_column3 == "Country") { echo " selected =\"selected\""; } ?>><?= __("Country"); ?></option>
											<option value="IOTA" <?php if ($user_column3 == "IOTA") { echo " selected =\"selected\""; } ?>><?= __("IOTA"); ?></option>
											<option value="SOTA" <?php if ($user_column3 == "SOTA") { echo " selected =\"selected\""; } ?>><?= __("SOTA"); ?></option>
											<option value="WWFF" <?php if ($user_column3 == "WWFF") { echo " selected =\"selected\""; } ?>><?= __("WWFF"); ?></option>
											<option value="POTA" <?php if ($user_column3 == "POTA") { echo " selected =\"selected\""; } ?>><?= __("POTA"); ?></option>
											<option value="State" <?php if ($user_column3 == "State") { echo " selected =\"selected\""; } ?>><?= __("State"); ?></option>
											<option value="Grid" <?php if ($user_column3 == "Grid") { echo " selected =\"selected\""; } ?>><?= __("Gridsquare"); ?></option>
											<option value="Distance" <?php if ($user_column3 == "Distance") { echo " selected =\"selected\""; } ?>><?= __("Distance"); ?></option>
											<option value="Operator" <?php if ($user_column3 == "Operator") { echo " selected =\"selected\""; } ?>><?= __("Operator"); ?></option>
											<option value="Name" <?php if ($user_column3 == "Name") { echo " selected =\"selected\""; } ?>><?= __("Name"); ?></option>
											<option value="Bearing" <?php if ($user_column3 == "Bearing") { echo " selected =\"selected\""; } ?>><?= __("Bearing"); ?></option>
										</select>
									</div>

									<div class="mb-3">
										<label for="column4"><?= __("Choose column 4"); ?></label>
										<?php if(!isset($user_column4)) { $user_column4='Band'; }?>
										<select class="form-select" id="column4" name="user_column4">
											<option value="Band" <?php if ($user_column4 == "Band") { echo " selected =\"selected\""; } ?>><?= __("Band"); ?></option>
											<option value="Frequency" <?php if ($user_column4 == "Frequency") { echo " selected =\"selected\""; } ?>><?= __("Frequency"); ?></option>
											<option value="Mode" <?php if ($user_column4 == "Mode") { echo " selected =\"selected\""; } ?>><?= __("Mode"); ?></option>
											<option value="RSTS" <?php if ($user_column4 == "RSTS") { echo " selected =\"selected\""; } ?>><?= __("RST (S)"); ?></option>
											<option value="RSTR" <?php if ($user_column4 == "RSTR") { echo " selected =\"selected\""; } ?>><?= __("RST (R)"); ?></option>
											<option value="Country" <?php if ($user_column4 == "Country") { echo " selected =\"selected\""; } ?>><?= __("Country"); ?></option>
											<option value="IOTA" <?php if ($user_column4 == "IOTA") { echo " selected =\"selected\""; } ?>><?= __("IOTA"); ?></option>
											<option value="SOTA" <?php if ($user_column4 == "SOTA") { echo " selected =\"selected\""; } ?>><?= __("SOTA"); ?></option>
											<option value="WWFF" <?php if ($user_column4 == "WWFF") { echo " selected =\"selected\""; } ?>><?= __("WWFF"); ?></option>
											<option value="POTA" <?php if ($user_column4 == "POTA") { echo " selected =\"selected\""; } ?>><?= __("POTA"); ?></option>
											<option value="State" <?php if ($user_column4 == "State") { echo " selected =\"selected\""; } ?>><?= __("State"); ?></option>
											<option value="Grid" <?php if ($user_column4 == "Grid") { echo " selected =\"selected\""; } ?>><?= __("Gridsquare"); ?></option>
											<option value="Distance" <?php if ($user_column4 == "Distance") { echo " selected =\"selected\""; } ?>><?= __("Distance"); ?></option>
											<option value="Operator" <?php if ($user_column4 == "Operator") { echo " selected =\"selected\""; } ?>><?= __("Operator"); ?></option>
											<option value="Name" <?php if ($user_column4 == "Name") { echo " selected =\"selected\""; } ?>><?= __("Name"); ?></option>
											<option value="Bearing" <?php if ($user_column4 == "Bearing") { echo " selected =\"selected\""; } ?>><?= __("Bearing"); ?></option>
										</select>
									</div>

									<div class="mb-3">
										<label for="column5"><?= __("Choose column 5 (only for logbook)"); ?></label>
										<?php if(!isset($user_column5)) { $user_column5='Country'; }?>
										<select class="form-select" id="column5" name="user_column5">
											<option value="" <?php if ($user_column5 == "") { echo " selected =\"selected\""; } ?>></option>
											<option value="Band" <?php if ($user_column5 == "Band") { echo " selected =\"selected\""; } ?>><?= __("Band"); ?></option>
											<option value="Frequency" <?php if ($user_column5 == "Frequency") { echo " selected =\"selected\""; } ?>><?= __("Frequency"); ?></option>
											<option value="Mode" <?php if ($user_column5 == "Mode") { echo " selected =\"selected\""; } ?>><?= __("Mode"); ?></option>
											<option value="RSTS" <?php if ($user_column5 == "RSTS") { echo " selected =\"selected\""; } ?>><?= __("RST (S)"); ?></option>
											<option value="RSTR" <?php if ($user_column5 == "RSTR") { echo " selected =\"selected\""; } ?>><?= __("RST (R)"); ?></option>
											<option value="Country" <?php if ($user_column5 == "Country") { echo " selected =\"selected\""; } ?>><?= __("Country"); ?></option>
											<option value="IOTA" <?php if ($user_column5 == "IOTA") { echo " selected =\"selected\""; } ?>><?= __("IOTA"); ?></option>
											<option value="SOTA" <?php if ($user_column5 == "SOTA") { echo " selected =\"selected\""; } ?>><?= __("SOTA"); ?></option>
											<option value="WWFF" <?php if ($user_column5 == "WWFF") { echo " selected =\"selected\""; } ?>><?= __("WWFF"); ?></option>
											<option value="POTA" <?php if ($user_column5 == "POTA") { echo " selected =\"selected\""; } ?>><?= __("POTA"); ?></option>
											<option value="State" <?php if ($user_column5 == "State") { echo " selected =\"selected\""; } ?>><?= __("State"); ?></option>
											<option value="Grid" <?php if ($user_column5 == "Grid") { echo " selected =\"selected\""; } ?>><?= __("Gridsquare"); ?></option>
											<option value="Distance" <?php if ($user_column5 == "Distance") { echo " selected =\"selected\""; } ?>><?= __("Distance"); ?></option>
											<option value="Operator" <?php if ($user_column5 == "Operator") { echo " selected =\"selected\""; } ?>><?= __("Operator"); ?></option>
											<option value="Name" <?php if ($user_column5 == "Name") { echo " selected =\"selected\""; } ?>><?= __("Name"); ?></option>
											<option value="Location" <?php if ($user_column5 == "Location") { echo " selected =\"selected\""; } ?>><?= __("Station Location"); ?></option>
											<option value="Bearing" <?php if ($user_column5 == "Bearing") { echo " selected =\"selected\""; } ?>><?= __("Bearing"); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<!-- QSO Logging Options -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("QSO Logging Options"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label for="logendtime"><?= __("Log End Times for QSOs Separately"); ?></label>
										<?php if(!isset($user_qso_end_times)) { $user_qso_end_times='0'; }?>
										<select class="form-select" id="logendtimes" name="user_qso_end_times">
											<option value="1" <?php if ($user_qso_end_times == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_qso_end_times == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("Choose yes here if you want to log QSO start and end times separately. If set to 'No' the end time will be the same as start time."); ?></small>
									</div>

									<hr />
									<div class="mb-3">
										<label for="profileimages"><?= __("Show profile picture of QSO partner from qrz.com/hamqth.com profile in the log QSO section."); ?></label>
										<?php if(!isset($user_show_profile_image)) { $user_show_profile_image='0'; }?>
										<select class="form-select" id="profileimages" name="user_show_profile_image">
											<option value="1" <?php if ($user_show_profile_image == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_show_profile_image == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("Please set your qrz.com/hamqth.com credentials in the general config file."); ?></small>
									</div>

									<hr />
									<div class="mb-3">
										<label for="qthlookup"><?= __("Location auto lookup."); ?></label>
										<?php if(!isset($user_qth_lookup)) { $user_qth_lookup='0'; }?>
										<select class="form-select" id="qthlookup" name="user_qth_lookup">
											<option value="1" <?php if ($user_qth_lookup == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_qth_lookup == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If set, gridsquare is fetched based on location name."); ?></small>
									</div>

									<div class="mb-3">
										<label for="sotalookup"><?= __("SOTA auto lookup gridsquare and name for summit."); ?></label>
										<?php if(!isset($user_sota_lookup)) { $user_sota_lookup='0'; }?>
										<select class="form-select" id="sotalookup" name="user_sota_lookup">
											<option value="1" <?php if ($user_sota_lookup == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_sota_lookup == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If set, name and gridsquare is fetched from the API and filled in location and locator."); ?></small>
									</div>

									<div class="mb-3">
										<label for="wwfflookup"><?= __("WWFF auto lookup gridsquare and name for reference."); ?></label>
										<?php if(!isset($user_wwff_lookup)) { $user_wwff_lookup='0'; }?>
										<select class="form-select" id="wwfflookup" name="user_wwff_lookup">
											<option value="1" <?php if ($user_wwff_lookup == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_wwff_lookup == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If set, name and gridsquare is fetched from the API and filled in location and locator."); ?></small>
									</div>

									<div class="mb-3">
										<label for="potalookup"><?= __("POTA auto lookup gridsquare and name for park."); ?></label>
										<?php if(!isset($user_pota_lookup)) { $user_pota_lookup='0'; }?>
										<select class="form-select" id="potalookup" name="user_pota_lookup">
											<option value="1" <?php if ($user_pota_lookup == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_pota_lookup == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If set, name and gridsquare is fetched from the API and filled in location and locator."); ?></small>
									</div>
									<div class="mb-3">
										<label for="qso-page-last-qso-count"><?= __("Number of previous contacts displayed on QSO page."); ?></label>
										<select class="form-select" id="qso-page-last-qso-count" name="user_qso_page_last_qso_count">
											<?php for ($i = 5 ; $i <= $qso_page_last_qso_count_limit; $i += 5) {
												$selected_attribute_value = $user_qso_page_last_qso_count == $i ? " selected =\"selected\"" : "";
												printf("<option value=\"{$i}\"{$selected_attribute_value}>{$i}</option>");
											} ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row mb-3">
						<!-- Menu Options -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Menu Options"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label for="shownotes"><?= __("Show notes in the main menu."); ?></label>
										<?php if(!isset($user_show_notes)) { $user_show_notes='0'; }?>
										<select class="form-select" id="shownotes" name="user_show_notes">
											<option value="1" <?php if ($user_show_notes == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_show_notes == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
									</div>

									<hr/>

									<div class="mb-3">
										<label for="quicklog"><?= __("Quicklog Field"); ?></label>
										<?php if(!isset($user_quicklog)) { $user_quicklog='0'; }?>
										<select class="form-select" id="quicklog" name="user_quicklog">
											<option value="1" <?php if ($user_quicklog == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_quicklog == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("With this feature, you can log callsigns using the search field in the header."); ?></small>
									</div>

									<div class="mb-3">
										<label for="quicklog_enter"><?= __("Quicklog - Action on press Enter"); ?></label>
										<?php if(!isset($user_quicklog_enter)) { $user_quicklog_enter='0'; }?>
										<select class="form-select" id="quicklog_enter" name="user_quicklog_enter">
											<option value="0" <?php if ($user_quicklog_enter == 0) { echo " selected =\"selected\""; } ?>><?= __("Log Callsign"); ?></option>
											<option value="1" <?php if ($user_quicklog_enter == 1) { echo " selected =\"selected\""; } ?>><?= __("Search Callsign"); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("What action should be performed when Enter is pressed in the quicklog field?"); ?></small>
									</div>
									<?php if ($this->session->userdata('user_id') == $this->uri->segment(3)) { ?>
									<hr/>

									<div class="mb-3">
										<label for="locations_quickswitch"><?= __("Station Locations Quickswitch"); ?></label>
										<select class="form-select" id="locations_quickswitch" name="user_locations_quickswitch">
											<option value="false" <?php if ($user_locations_quickswitch == 'false') { echo " selected =\"selected\""; } ?>><?= __("Disabled"); ?></option>
											<option value="true" <?php if ($user_locations_quickswitch == 'true') { echo " selected =\"selected\""; } ?>><?= __("Enabled"); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("Show the Station Locations Quickswitch in the main menu"); ?></small>
									</div>

									<div class="mb-3">
										<label for="utc_headermenu"><?= __("UTC Time in Menu"); ?></label>
										<select class="form-select" id="utc_headermenu" name="user_utc_headermenu">
											<option value="false" <?php if ($user_utc_headermenu == 'false') { echo " selected =\"selected\""; } ?>><?= __("Disabled"); ?></option>
											<option value="true" <?php if ($user_utc_headermenu == 'true') { echo " selected =\"selected\""; } ?>><?= __("Enabled"); ?></option>
										</select>
										<small id="SelectDateFormatHelp" class="form-text text-muted"><?= __("Show the current UTC Time in the menu"); ?></small>
									</div>
									<?php } ?>
								</div>
							</div>
						</div>

						<!-- Map Setting -->
						<?php if ($this->session->userdata('user_id') == $this->uri->segment(3)) { ?>
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Map Settings"); ?></div>
								<div class="card-body">
									<div class="row"> <!-- Station -->
										<div class="mb-3 col-md-4">
											<label>&nbsp;</label><br/><label><?= __("Station"); ?></label>
										</div>
										<div class="mb-3 col-md-3">
											<label><?= __("Icon"); ?></label><br/>
											<div class="icon_selectBox" data-boxcontent="station">
												<input type="hidden" name="user_map_station_icon" value="<?php echo $user_map_station_icon; ?>">
												<div class="form-select icon_overSelect"><?php echo (($user_map_station_icon=="0")?substr(__("Not display"),0,10).'.':("<i class='".$user_map_station_icon."'></i>")); ?></div>
											</div>
											<div class="col-md-3 icon_selectBox_data" data-boxcontent="station">
												<?php foreach($map_icon_select['station'] as $val) {
													echo "<label data-value='".$val."'>".(($val=="0")?__("Not displayed"):("<i class='".$val."'></i>"))."</label>";
												} ?>
											</div>
										</div>
										<div class="mb-3 col-md-2">
											<label><?= __("Colors"); ?></label><br/><input type="color" class="form-control user_icon_color" name="user_map_station_color" id="user_map_station_color" value="<?php echo $user_map_station_color; ?>" style="padding:initial;<?php echo ($user_map_station_icon=="0")?'display:none;':''; ?>" data-icon="station" /></div>
									</div>
									<div class="row"> <!-- QSO (default) -->
										<div class="mb-3 col-md-4">
											<label><?= __("QSO (by default)"); ?></label>
										</div>
										<div class="mb-3 col-md-3">
											<div class="icon_selectBox" data-boxcontent="qso">
												<input type="hidden" name="user_map_qso_icon" value="<?php echo $user_map_qso_icon; ?>">
												<div class="form-select icon_overSelect"><?php echo "<i class='".$user_map_qso_icon."'></i>"; ?></div>
											</div>
											<div class="col-md-3 icon_selectBox_data" data-boxcontent="qso">
												<?php foreach($map_icon_select['qso'] as $val) {
													echo "<label data-value='".$val."'><i class='".$val."'></i></label>";
												} ?>
											</div>
										</div>
										<div class="mb-3 col-md-2">
											<input type="color" class="form-control user_icon_color" name="user_map_qso_color" id="user_map_qso_color" value="<?php echo $user_map_qso_color; ?>" style="padding:initial;" data-icon="qso" />
										</div>
									</div>
									<div class="row"> <!-- QSO (confirmed) -->
										<div class="mb-3 col-md-4">
											<label><?= __("QSO (confirmed)"); ?></label>
											<small class="form-text text-muted"><?= __("(If 'No', displayed as 'QSO (by default))'"); ?></small>
										</div>
										<div class="mb-3 col-md-3">
											<div class="icon_selectBox" data-boxcontent="qsoconfirm">
												<input type="hidden" name="user_map_qsoconfirm_icon" value="<?php echo $user_map_qsoconfirm_icon; ?>">
												<div class="form-select icon_overSelect"><?php echo (($user_map_qsoconfirm_icon=="0")?__("No"):("<i class='".$user_map_qsoconfirm_icon."'></i>")); ?></div>
											</div>
											<div class="col-md-3 icon_selectBox_data" data-boxcontent="qsoconfirm">
												<?php foreach($map_icon_select['qsoconfirm'] as $val) {
													echo "<label data-value='".$val."'>".(($val=="0")?__("No"):("<i class='".$val."'></i>"))."</label>";
												} ?>
											</div>
										</div>
										<div class="md-3 col-md-2">
											<input type="color" class="form-control user_icon_color" name="user_map_qsoconfirm_color" id="user_map_qsoconfirm_color" value="<?php echo $user_map_qsoconfirm_color; ?>" style="padding:initial;<?php echo ($user_map_qsoconfirm_icon=="0")?'display:none;':''; ?>" data-icon="qsoconfirm" />
										</div>
									</div>
									<div class="row">
										<div class="md-3 col-md-4">
											<label><?= __("Show Locator"); ?></label>
										</div>
										<div class="md-3 col-md-3">
											<select class="form-select" id="user_map_gridsquare_show" name="user_map_gridsquare_show">
												<option value="1" <?php if ($user_map_gridsquare_show == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
												<option value="0" <?php if ($user_map_gridsquare_show == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="row mb-3">
						<!-- Previous QSL -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Previous QSL Type"); ?></div>
								<div class="card-body">
									<div class="row">
										<div class="mb-3">
											<label for="profileimages"><?= __("Select the type of QSL to show in the previous QSOs section."); ?></label>
											<?php if(!isset($user_previous_qsl_type)) { $user_previous_qsl_type='0'; }?>
											<select class="form-select" id="previousqsltype" name="user_previous_qsl_type">
												<option value="0" <?php if ($user_previous_qsl_type == 0) { echo " selected =\"selected\""; } ?>><?= __("QSL"); ?></option>
												<option value="1" <?php if ($user_previous_qsl_type == 1) { echo " selected =\"selected\""; } ?>><?= __("LoTW"); ?></option>
												<option value="2" <?php if ($user_previous_qsl_type == 2) { echo " selected =\"selected\""; } ?>><?= __("eQSL"); ?></option>
												<option value="4" <?php if ($user_previous_qsl_type == 4) { echo " selected =\"selected\""; } ?>><?= __("QRZ"); ?></option>
												<option value="8" <?php if ($user_previous_qsl_type == 8) { echo " selected =\"selected\""; } ?>><?= __("Clublog"); ?></option>
											</select>
										</div>
									</div>

								</div>
							</div>
							<!-- Dashboard Settings -->
							<div class="card">
								<div class="card-header"><?= __("Dashboard Settings"); ?></div>
								<div class="card-body">
									<div class="row">
										<div class="mb-3">
											<label for="dashboard-last-qso-count"><?= __("Select the number of latest QSOs to be displayed on dashboard."); ?></label>
											<select class="form-select" id="dashboard-last-qso-count" name="user_dashboard_last_qso_count">
												<?php for ($i = 5 ; $i <= $dashboard_last_qso_count_limit; $i += 5) {
													$selected_attribute_value = $user_dashboard_last_qso_count == $i ? " selected =\"selected\"" : "";
													printf("<option value=\"{$i}\"{$selected_attribute_value}>{$i}</option>");
												} ?>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Show Reference Fields on QSO Tab"); ?></div>
								<div class="card-body">
									<div class="row">
										<div class="mb-3">
											<label for="references_select"><?= __("The enabled items will be shown on the QSO tab rather than the General tab."); ?></label>
											<div class="form-check form-switch">
												<input name="user_iota_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="iotaToQsoTab" <?php if ($user_iota_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="iotaToQsoTab" ><?= __("IOTA Reference"); ?></label>
											</div>
											<div class="form-check form-switch">
												<input name="user_sota_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="sotaToQsoTab" <?php if ($user_sota_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="sotaToQsoTab" ><?= __("SOTA Reference"); ?></label>
											</div>
											<div class="form-check form-switch">
												<input name="user_wwff_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="wwffToQsoTab" <?php if ($user_wwff_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="wwffToQsoTab" ><?= __("WWFF Reference"); ?></label>
											</div>
											<div class="form-check form-switch">
												<input name="user_pota_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="potaToQsoTab" <?php if ($user_pota_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="potaToQsoTab" ><?= __("POTA Reference(s)"); ?></label>
											</div>
											<div class="form-check form-switch">
												<input name="user_sig_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="sigToQsoTab" <?php if ($user_sig_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="sigToQsoTab" ><?= __("Sig"); ?> / <?= __("Sig Info"); ?></label>
											</div>
											<div class="form-check form-switch">
												<input name="user_dok_to_qso_tab" class="form-check-input" type="checkbox" role="switch" id="dokToQsoTab" <?php if ($user_dok_to_qso_tab ?? false) { echo 'checked'; } ?>>
												<label class="form-check-label" for="dokToQsoTab" ><?= __("DOK"); ?></label>
											</div>
										</div>
									</div>
									<button type="button" onclick="clearRefSwitches();" class="btn btn-primary"><i class="fas fa-recycle"></i> <?= __("Reset"); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- ZONE 3 / Default Values -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_default_value">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_default_value" aria-expanded="false" aria-controls="panelsStayOpen-B_default_value">
					<?= __("Default Values");?></button>
			</h2>
			<div id="panelsStayOpen-B_default_value" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-H_default_value">
				<div class="accordion-body">
					<div class="row">
						<!-- Default -->
						<div class="col-md">
							<div class="card">
								<!--<div class="card-header"><?= __("Settings for Default Band and Confirmation"); ?></div>-->
								<div class="card-body">
									<div class="mb-3">
										<label for="user_default_band"><?= __("Default Band"); ?></label>
										<?php if(!isset($user_default_band)) { $user_default_band='All'; }?>
										<select id="user_default_band" class="form-select" name="user_default_band">
											<option value="All"><?= __("All"); ?></option>;
											<?php foreach($bands as $band) {
												echo '<option value="'.$band.'" '.(($user_default_band == $band)?' selected="selected"':'').'>'.$band.'</option>'."\n";
											} ?>
										</select>
									</div>
									<div class="mb-3">
										<label class="my-1 me-2"><?= __("Default QSL-Methods"); ?></label>
										<div class="form-check-inline">
											<?php echo '<input class="form-check-input" type="checkbox" name="user_default_confirmation_qsl" id="user_default_confirmation_qsl"';
											if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
												echo ' checked';
											} else if (!isset($user_default_confirmation)) {
												echo ' checked';
											}
											echo '>'; ?>
											<label class="form-check-label" for="user_default_confirmation_qsl"><?= __("QSL"); ?></label>
										</div>
										<div class="form-check-inline">
											<?php echo '<input class="form-check-input" type="checkbox" name="user_default_confirmation_lotw" id="user_default_confirmation_lotw"';
											if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
												echo ' checked';
											} else if (!isset($user_default_confirmation)) {
												echo ' checked';
											}
											echo '>'; ?>
											<label class="form-check-label" for="user_default_confirmation_lotw"><?= __("LoTW"); ?></label>
										</div>
										<div class="form-check-inline">
											<?php echo '<input class="form-check-input" type="checkbox" name="user_default_confirmation_eqsl" id="user_default_confirmation_eqsl"';
											if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
												echo ' checked';
											}
											echo '>'; ?>
											<label class="form-check-label" for="user_default_confirmation_eqsl"><?= __("eQSL"); ?></label>
										</div>
										<div class="form-check-inline">
											<?php echo '<input class="form-check-input" type="checkbox" name="user_default_confirmation_qrz" id="user_default_confirmation_qrz"';
											if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
												echo ' checked';
											}
											echo '>'; ?>
											<label class="form-check-label" for="user_default_confirmation_qrz"><?= __("QRZ.com"); ?></label>
										</div>
										<div class="form-check-inline">
											<?php echo '<input class="form-check-input" type="checkbox" name="user_default_confirmation_clublog" id="user_default_confirmation_clublog"';
											if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
												echo ' checked';
											}
											echo '>'; ?>
											<label class="form-check-label" for="user_default_confirmation_clublog"><?= __("Clublog"); ?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- ZONE 4 / Third Party Services -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_confirmation_account">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_confirmation_account" aria-expanded="false" aria-controls="panelsStayOpen-B_confirmation_account">
				<?= __("Third Party Services"); ?></button>
			</h2>
			<div id="panelsStayOpen-B_confirmation_account" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-H_confirmation_account">
				<div class="accordion-body">
					<div class="row">
						<!-- Logbook of the World -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Logbook of the World"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Logbook of The World (LoTW) Username"); ?></label>
										<input class="form-control" type="text" name="user_lotw_name" id="user_lotw_name" value="<?php if(isset($user_lotw_name)) { echo $user_lotw_name; } ?>" />
										<?php if(isset($userlotwname_error)) { echo "<small class=\"badge bg-danger\">".$userlotwname_error."</small>"; } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Logbook of The World (LoTW) Password"); ?></label>
										<div class="input-group">
											<input class="form-control" type="password" id="user_lotw_password" name="user_lotw_password" value="<?php if(isset($user_lotw_password)) { echo $user_lotw_password; } ?>" />
											<span class="input-group-btn"><button class="btn btn-default btn-pwd-showhide" type="button"><i class="fa fa-eye-slash"></i></button></span>
											<button class="btn btn-secondary ld-ext-right" type="button" id="lotw_test_btn"><?= __("Test Login"); ?><div class="ld ld-ring ld-spin"></div></button>
										</div>
										<div class="alert mt-3" style="display: none;" id="lotw_test_txt"></div>
										<?php if(isset($lotwpassword_error)) {
											echo "<small class=\"badge bg-danger\">".$lotwpassword_error."</small>";
											} else if (!isset($user_add)) { ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>

						<!-- eQSL -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("eQSL"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("eQSL.cc Username"); ?></label>
										<input class="form-control" type="text" name="user_eqsl_name" value="<?php if(isset($user_eqsl_name)) { echo $user_eqsl_name; } ?>" />
											<?php if(isset($eqslusername_error)) { echo "<small class=\"badge bg-danger\">".$eqslusername_error."</small>"; } ?>
									</div>

									<div class="mb-3">
										<label><?= __("eQSL.cc Password"); ?></label>
										<div class="input-group">
											<input class="form-control" type="password" name="user_eqsl_password" value="<?php if(isset($user_eqsl_password)) { echo $user_eqsl_password; } ?>" />
											<span class="input-group-btn"><button class="btn btn-default btn-pwd-showhide" type="button"><i class="fa fa-eye-slash"></i></button></span>
										</div>
										<?php if(isset($eqslpassword_error)) {
											echo "<small class=\"badge bg-danger\">".$eqslpassword_error."</small>";
											} else if (!isset($user_add)) { ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Club Log -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Club Log"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Club Log Email/Callsign"); ?></label>
										<input class="form-control" type="text" name="user_clublog_name" value="<?php if(isset($user_clublog_name)) { echo $user_clublog_name; } ?>" />
										<?php if(isset($userclublogname_error)) { echo "<small class=\"badge bg-danger\">".$userclublogname_error."</small>"; } ?>
									</div>

									<div class="mb-3">
										<label><?= __("Club Log Password"); ?></label>
										<div class="input-group">
											<input class="form-control" type="password" name="user_clublog_password" value="<?php if(isset($user_clublog_password)) { echo $user_clublog_password; } ?>" />
											<span class="input-group-btn"><button class="btn btn-default btn-pwd-showhide" type="button"><i class="fa fa-eye-slash"></i></button></span>
										</div>
										<small class="form-text text-muted"><?= sprintf(__("If you have 2FA enabled at Clublog, you have to generate an App. Password to use Clublog in Wavelog. Visit %syour clublog settings page%s to do so."), '<a target="_blank" href="https://clublog.org/edituser.php?tab=7">', '</a>'); ?></small>
										<?php if(isset($clublogpassword_error)) {
											echo "<small class=\"badge bg-danger\">".$clublogpassword_error."</small>";
											} else if (!isset($user_add)) { ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- ZONE 5 / Widgets -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_widget_settings">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_widget_settings" aria-expanded="false" aria-controls="panelsStayOpen-B_widget_settings">
				<?= __("Widgets");?></button>
			</h2>
			<div id="panelsStayOpen-B_widget_settings" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-H_widget_settings">
				<div class="accordion-body">
					<div class="row">
						<!-- On-Air Widget Settings -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("On-Air widget"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Enabled"); ?></label>
										<?php if(!isset($on_air_widget_enabled)) { $on_air_widget_enabled='false'; }?>
										<select class="form-select" name="on_air_widget_enabled" id="on_air_widget_enabled">
											<option value="false" <?php if ($on_air_widget_enabled == "false") { echo 'selected="selected"'; } ?>><?= __("No"); ?></option>
											<option value="true" <?php if ($on_air_widget_enabled == "true") { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										</select>
										<small class="form-text text-muted">
											<?= sprintf(__("Note: In order to use this widget, you need to have at least one CAT radio configured and working.")); ?>
											<?php if (isset($on_air_widget_url)) {
												// when adding user, the $on_air_widget_url url is not yet availalable, hence the if condition here 
												print("<br>");
												printf(__("When enabled, widget will be available at %s."), "<a href='$on_air_widget_url' target='_blank'>$on_air_widget_url</a>");
											} ?>
										</small>
									</div>
									<div class="mb-3">
										<label><?= __('Display "Last seen" time'); ?></label>
										<?php if(!isset($on_air_widget_display_last_seen)) { $on_air_widget_display_last_seen='false'; }?>
										<select class="form-select" name="on_air_widget_display_last_seen" id="on_air_widget_display_last_seen">
											<option value="false" <?php if ($on_air_widget_display_last_seen == "false") { echo 'selected="selected"'; } ?>><?= __("No"); ?></option>
											<option value="true" <?php if ($on_air_widget_display_last_seen == "true") { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("This setting control whether the 'Last seen' time is displayed in widget or not."); ?></small>
									</div>
									<div class="mb-3">
										<label><?= __("Display only most recently updated radio"); ?></label>
										<?php if(!isset($on_air_widget_show_only_most_recent_radio)) { $on_air_widget_show_only_most_recent_radio='true'; }?>
										<select class="form-select" name="on_air_widget_show_only_most_recent_radio" id="on_air_widget_show_only_most_recent_radio">
										<option value="true" <?php if ($on_air_widget_show_only_most_recent_radio == "true") { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										<option value="false" <?php if ($on_air_widget_show_only_most_recent_radio == "false") { echo 'selected="selected"'; } ?>><?= __("No, show all radios"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If you have multiple CAT radios configured, this setting controls whether the widget should display all on-air radios of the user, or just the most recently updated one. In case you have only one radio, this setting has no effect."); ?></small>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<!-- QSOs Widget Settings -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("QSOs widget"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __('Display exact QSO time'); ?></label>
										<?php if(!isset($qso_widget_display_qso_time)) { $qso_widget_display_qso_time='false'; }?>
										<select class="form-select" name="qso_widget_display_qso_time" id="qso_widget_display_qso_time">
											<option value="false" <?php if ($qso_widget_display_qso_time == "false") { echo 'selected="selected"'; } ?>><?= __("No"); ?></option>
											<option value="true" <?php if ($qso_widget_display_qso_time == "true") { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("This setting control whether exact QSO time should displayed in the QSO widget or not."); ?></small>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- ZONE 6 / Miscellaneous -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-H_miscellaneous">
				<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-B_miscellaneous" aria-expanded="false" aria-controls="panelsStayOpen-B_miscellaneous">
				<?= __("Miscellaneous");?></button>
			</h2>
			<div id="panelsStayOpen-B_miscellaneous" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-H_miscellaneous">
				<div class="accordion-body">
					<div class="row">
						<!-- AMSAT Upload -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("AMSAT Status Upload"); ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label for="amsatsatatusupload"><?= __("Upload status of SAT QSOs to"); ?> <a href="https://www.amsat.org/status/" target="_blank">https://www.amsat.org/status/</a>.</label>
										<?php if(!isset($user_amsat_status_upload)) { $user_amsat_status_upload='0'; }?>
										<select class="form-select" id="amsatstatusupload" name="user_amsat_status_upload">
											<option value="1" <?php if ($user_amsat_status_upload == 1) { echo " selected =\"selected\""; } ?>><?= __("Yes"); ?></option>
											<option value="0" <?php if ($user_amsat_status_upload == 0) { echo " selected =\"selected\""; } ?>><?= __("No"); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<!-- Mastodon -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Mastodonserver"); ?></div>
								<div class="card-body">
									<div class="mb-3">
									<label><?= __("URL of Mastodonserver"); ?></label>
										<input class="form-control" type="text" name="user_mastodon_url" value="<?php if(isset($user_mastodon_url)) { echo $user_mastodon_url; } ?>" />
										<small class="form-text text-muted"><?= sprintf(__("Main URL of your Mastodon server, e.g. %s"), "<a href='https://radiosocial.de/' target='_blank'>https://radiosocial.de</a>"); ?></small>
									</div>
								</div>
							</div>
						</div>

						<!-- Winkeyer -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Winkeyer"); ?> <span class="badge text-bg-danger float-end"><?= __("Experimental"); ?></span></div>
								<div class="card-body">
									<div class="mb-3">
										<label><?= __("Winkeyer Features Enabled"); ?></label>
										<?php if(!isset($user_winkey)) { $user_winkey='0'; }?>
										<select class="form-select" name="user_winkey" id="user_winkeyer">
											<option value="0" <?php if ($user_winkey == 0) { echo 'selected="selected"'; } ?>><?= __("No"); ?></option>
											<option value="1" <?php if ($user_winkey == 1) { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										</select>
										<small class="form-text text-muted"><?= sprintf(__("Winkeyer support in Wavelog is very experimental. Read the wiki first at %s before enabling."), "<a href='https://github.com/wavelog/wavelog/wiki/Winkey' target='_blank'>https://github.com/wavelog/wavelog/wiki/Winkey</a>"); ?></small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<!-- Hams.at Settings -->
						<div class="col-md">
							<div class="card">
								<div class="card-header"><?= __("Hams.at"); ?></div>
								<div class="card-body">
									<div class="mb-3">
									<label><?= __("Private Feed Key"); ?></label>
										<input class="form-control" type="text" name="user_hamsat_key" value="<?php if(isset($user_hamsat_key)) { echo $user_hamsat_key; } ?>" />
										<small class="form-text text-muted"><?= sprintf(_pgettext("Hint for Hamsat API Key; uses Link", "See your profile at %s."), "<a href='https://hams.at/users/settings' target='_blank'>https://hams.at/users/settings</a>"); ?></small>
									</div>
									<div class="mb-3">
										<label><?= __("Show Workable Passes Only"); ?></label>
										<?php if(!isset($user_hamsat_workable_only)) { $user_hamsat_workable_only='0'; }?>
										<select class="form-select" name="user_hamsat_workable_only" id="user_hamsat_workable_only">
											<option value="0" <?php if ($user_hamsat_workable_only == 0) { echo 'selected="selected"'; } ?>><?= __("No"); ?></option>
											<option value="1" <?php if ($user_hamsat_workable_only == 1) { echo 'selected="selected"'; } ?>><?= __("Yes"); ?></option>
										</select>
										<small class="form-text text-muted"><?= __("If enabled shows only workable passes based on the gridsquare set in your hams.at account. Requires private feed key to be set."); ?></small>
									</div>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="id" value="<?php echo $this->uri->segment(3); ?>" />
	<button type="submit" class="btn btn-primary mb-5 mt-3"><i class="fas fa-save"></i> <?= __("Save Account"); ?></button>
</form>
</div>
