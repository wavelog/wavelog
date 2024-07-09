<div class="container">
	<form id="ThemeForm">
		<div class="mb-3">
			<label for="nameInput"><?= __("Theme Name"); ?></label>
			<input type="text" class="form-control" name="name" id="nameInput" aria-describedby="nameInputHelp" required>
			<small id="nameInputHelp" class="form-text text-muted"><?= __("This is the name that is used to display the theme in the theme list."); ?></small>
		</div>

		<div class="mb-3">
			<label for="foldernameInput"><?= __("Folder Name"); ?></label>
			<input type="text" class="form-control" name="foldername" id="foldernameInput" aria-describedby="foldernameInputHelp">
			<small id="foldernameInputHelp" class="form-text text-muted"><?= __("This is the name of the folder where your CSS-files are placed under assets/css."); ?></small>
		</div>

		<div class="mb-3">
			<label for="themeModeInput"><?= __("Theme Mode"); ?></label>
			<select class="form-select" id="themeModeInput" name="theme_mode">
				<option value="light"><?= __("Light"); ?></option>
				<option value="dark"><?= __("Dark"); ?></option>
			</select>
			<small id="themeModeInputHelp" class="form-text text-muted"><?= __("This defines wherever the theme is a light or a dark one."); ?></small>
		</div>

		<div class="mb-3">
			<label for="headerLogoInput"><?= __("Header Logo"); ?></label>
			<input type="text" class="form-control" name="header_logo" id="headerLogoInput" aria-describedby="headerLogoInputHelp">
			<small id="headerLogoInputHelp" class="form-text text-muted"><?= sprintf(__("This is the name of the file which is used as %s small %s Logo in the header placed in assets/logo."), "<u>", "</u>"); ?><br><?= __("Only PNG files with a size ratio of 1:1 are allowed."); ?></small>
		</div>

		<div class="mb-3">
			<label for="mainLogoInput"><?= __("Main Logo"); ?></label>
			<input type="text" class="form-control" name="main_logo" id="mainLogoInput" aria-describedby="mainLogoInputHelp">
			<small id="mainLogoInputHelp" class="form-text text-muted"><?= sprintf(__("This is the name of the file which is used as %s big %s Logo in the login screen placed in assets/logo."), "<u>", "</u>"); ?><br><?= __("Only PNG files with a size ratio of 1:1 are allowed."); ?></small>
		</div>
		<div class="alert alert-warning" role="alert" id="warningMessageTheme" style="display: none"> </div>
		<button id="submitButton" type="button" onclick="addTheme(this.form);" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Add theme"); ?></button>
	</form>
</div>