<div class="container">
	<h2><?= __("Wavelog Options"); ?></h2>
	<div class="card">
		<?php $this->load->view('options/tabs', ['active_tab' => $active_tab ?? '']); ?>
		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php echo form_open('options/appearance_save'); ?>

				<!-- Form options for selecting global theme choice -->
				<div class="mb-3">
					<label for="themeSelect"><?= __("Theme"); ?></label>
					<select class="form-select" id="themeSelect" name="theme" aria-describedby="themeHelp" required>
						<?php
						foreach ($themes as $theme) {
							echo '<option value="' . $theme->foldername . '"';
							if ($this->optionslib->get_option('option_theme') == $theme->foldername) {
								echo 'selected="selected"';
							}
							echo '>' . $theme->name . '</option>';
						}
						?>
					</select>
					<small id="themeHelp" class="form-text text-muted"><?= __("Global Theme Choice, this is used when users arent logged in."); ?></small>
				</div>

				<div class="mb-3">
					<label for="logbookMap"><?= __("Logbook Map"); ?></label>
					<select class="form-select" id="logbookMap" name="logbookMap" aria-describedby="logbookMapHelp" required>
						<option value='true' <?php if($this->optionslib->get_option('logbook_map') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
						<option value='false' <?php if($this->optionslib->get_option('logbook_map') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
					</select>
					<small id="logbookMapHelp" class="form-text text-muted"><?= __("This allows to disable the map in the logbook."); ?></small>
				</div>

				<div class="mb-3">
					<label for="publicMaps"><?= __("Public Maps"); ?></label>
					<select class="form-select" id="publicMaps" name="publicMaps" aria-describedby="publicMapsHelp" required>
						<option value='true' <?php if($this->optionslib->get_option('public_maps') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
						<option value='false' <?php if($this->optionslib->get_option('public_maps') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
					</select>
					<small id="publicMapsHelp" class="form-text text-muted"><?= __("This allows to disable all maps in the public view. This affects the main map and the gridsquares map."); ?></small>
				</div>

				<div class="mb-3">
					<label for="publicGithubButton"><?= __("Public Github Button"); ?></label>
					<select class="form-select" id="publicGithubButton" name="publicGithubButton" aria-describedby="publicGithubButtonHelp" required>
						<option value='true' <?php if($this->optionslib->get_option('public_github_button') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
						<option value='false' <?php if($this->optionslib->get_option('public_github_button') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
					</select>
					<small id="publicGithubButtonHelp" class="form-text text-muted"><?= __("This enables the button to Wavelog's Github page in the public view"); ?></small>
				</div>

				<div class="mb-3">
					<label for="publicLoginButton"><?= __("Public Login Button"); ?></label>
					<select class="form-select" id="publicLoginButton" name="publicLoginButton" aria-describedby="publicLoginButtonHelp" required>
						<option value='true' <?php if($this->optionslib->get_option('public_login_button') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
						<option value='false' <?php if($this->optionslib->get_option('public_login_button') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
					</select>
					<small id="publicLoginButtonHelp" class="form-text text-muted"><?= __("This enables the button to login to Wavelog in the public view"); ?></small>
				</div>

				<!-- Save the Form -->
				<input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
			</form>
		</div>
	</div>
</div>
