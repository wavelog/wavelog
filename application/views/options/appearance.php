<div class="container settings">

	<div class="row">
		<!-- Nav Start -->
		<?php $this->load->view('options/sidebar') ?>
		<!-- Nav End -->

		<!-- Content -->
		<div class="col-md-9">
            <div class="card">
                <div class="card-header"><h2><?php echo $page_title; ?> - <?php echo $sub_heading; ?></h2></div>

                <div class="card-body">
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
                        
                        
                            <select class="form-select" id="globalSearch" name="globalSearch" style="display: none;">
                                <option value='true' <?php if($this->optionslib->get_option('global_search') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
                                <option value='false' <?php if($this->optionslib->get_option('global_search') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
                            </select>

                        <div class="mb-3">
                            <label for="dashboardBanner"><?= __("Dashboard Notification Banner"); ?></label>
                            <select class="form-select" id="dashboardBanner" name="dashboardBanner" aria-describedby="dashboardBannerHelp" required>
                                <option value='true' <?php if($this->optionslib->get_option('dashboard_banner') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
                                <option value='false' <?php if($this->optionslib->get_option('dashboard_banner') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
                            </select>
                            <small id="dashboardBannerHelp" class="form-text text-muted"><?= __("This allows to disable the global notification banner on the dashboard."); ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="dashboardMap"><?= __("Dashboard Map"); ?></label>
                            <select class="form-select" id="dashboardMap" name="dashboardMap" aria-describedby="dashboardMapHelp" required>
                                <option value='true' <?php if($this->optionslib->get_option('dashboard_map') == "true") { echo "selected=\"selected\""; } ?>><?= __("Enabled"); ?></option>
                                <option value='false' <?php if($this->optionslib->get_option('dashboard_map') == "false") { echo "selected=\"selected\""; } ?>><?= __("Disabled"); ?></option>
                                <option value='map_at_right' <?php if($this->optionslib->get_option('dashboard_map') == "map_at_right") { echo "selected=\"selected\""; } ?>><?= __("Map at right"); ?></option>
                            </select>
                            <small id="dashboardMapHelp" class="form-text text-muted"><?= __("This allows the map on the dashboard to be disabled or placed on the right."); ?></small>
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
	</div>

</div>
