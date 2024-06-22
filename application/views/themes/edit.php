<div class="container">
	<form id="ThemeForm">
		<div class="mb-3">
			<label for="themenameInput"><?= __("Theme Name"); ?></label>
			<input type="text" class="form-control" name="name" id="nameInput" aria-describedby="themenameInputHelp" value="<?php if(set_value('name') != "") { echo set_value('name'); } else { echo $theme->name; } ?>">
			<small id="themenameInputHelp" class="form-text text-muted"><?= __("This is the name that is used to display the theme in the theme list."); ?></small>
		</div>

		<div class="mb-3">
			<label for="foldernameInput"><?= __("Folder Name"); ?></label>
			<input type="text" class="form-control" name="foldername" id="foldernameInput" aria-describedby="foldernameInputHelp" value="<?php if(set_value('foldername') != "") { echo set_value('foldername'); } else { echo $theme->foldername; } ?>">
			<small id="foldernameInputHelp" class="form-text text-muted"><?= __("This is the name of the folder where your CSS-files are placed under assets/css."); ?></small>
		</div>

		<div class="mb-3">
			<label for="themeModeInput"><?= __("Theme Mode"); ?></label>
			<select class="form-select" id="themeModeInput" name="theme_mode">
				<option value="light" <?php if ($theme->theme_mode == 'light') { echo " selected =\"selected\""; } ?>><?= __("Light"); ?></option>
				<option value="dark" <?php if ($theme->theme_mode == 'dark') { echo " selected =\"selected\""; } ?>><?= __("Dark"); ?></option>
			</select>
			<small id="themeModeInputHelp" class="form-text text-muted"><?= __("This defines wherever the theme is a light or a dark one. On this basis the Logo is chosen."); ?></small>
		</div>

		<div class="mb-3">
			<label for="headerLogoInput"><?= __("Header Logo"); ?></label>
			<input type="text" class="form-control" name="header_logo" id="headerLogoInput" aria-describedby="headerLogoInputHelp" value="<?php if(set_value('header_logo') != "") { echo set_value('header_logo'); } else { echo $theme->header_logo; } ?>">
			<small id="headerLogoInputHelp" class="form-text text-muted"><?= sprintf(__("This is the name of the file which is used as %s small %s Logo in the header placed in assets/logo."), "<u>", "</u>"); ?><br><?= __("Only PNG files with a size ratio of 1:1 are allowed."); ?></small>
		</div>

		<div class="mb-3">
			<label for="mainLogoInput"><?= __("Main Logo"); ?></label>
			<input type="text" class="form-control" name="main_logo" id="mainLogoInput" aria-describedby="mainLogoInputHelp" value="<?php if(set_value('main_logo') != "") { echo set_value('main_logo'); } else { echo $theme->main_logo; } ?>">
			<small id="mainLogoInputHelp" class="form-text text-muted"><?= sprintf(__("This is the name of the file which is used as %s big %s Logo in the login screen placed in assets/logo."), "<u>", "</u>"); ?><br><?= __("Only PNG files with a size ratio of 1:1 are allowed."); ?></small>
		</div>
		<div class="alert alert-warning" role="alert" id="warningMessageTheme" style="display: none"> </div>
		<button id="submitButton" type="button" onclick="editTheme(this.form, '<?php echo $theme->id; ?>');" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Update theme"); ?></button>
	</form>
</div>
