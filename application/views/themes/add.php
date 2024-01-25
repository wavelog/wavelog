<div class="container">
	<form>
		<div class="mb-3">
			<label for="nameInput">Theme Name</label>
			<input type="text" class="form-control" name="name" id="nameInput" aria-describedby="nameInputHelp" required>
			<small id="nameInputHelp" class="form-text text-muted">This is the name that is used to display the theme in the theme list.</small>
		</div>

		<div class="mb-3">
			<label for="foldernameInput">Folder Name</label>
			<input type="text" class="form-control" name="foldername" id="foldernameInput" aria-describedby="foldernameInputHelp">
			<small id="foldernameInputHelp" class="form-text text-muted">This is the name of the folder where your CSS-files are placed under assets/css.</small>
		</div>

		<div class="mb-3">
			<label for="themeModeInput">Theme Mode</label>
			<select class="form-select" id="themeModeInput" name="theme_mode">
				<option value="light">Light</option>
				<option value="dark">Dark</option>
			</select>
			<small id="themeModeInputHelp" class="form-text text-muted">This defines wherever the theme is a light or a dark one.</small>
		</div>

		<div class="mb-3">
			<label for="headerLogoInput">Header Logo</label>
			<input type="text" class="form-control" name="header_logo" id="headerLogoInput" aria-describedby="headerLogoInputHelp">
			<small id="headerLogoInputHelp" class="form-text text-muted">This is the name of the file which is used as <u>small</u> Logo in the header placed in assets/logo.<br>Only PNG files with a size ratio of 1:1 are allowed.</small>
		</div>

		<div class="mb-3">
			<label for="mainLogoInput">Main Logo</label>
			<input type="text" class="form-control" name="main_logo" id="mainLogoInput" aria-describedby="mainLogoInputHelp">
			<small id="mainLogoInputHelp" class="form-text text-muted">This is the name of the file which is used as <u>big</u> Logo in the login screen placed in assets/logo.<br>Only PNG files with a size ratio of 1:1 are allowed.</small>
		</div>

		<button type="button" onclick="addTheme(this.form);" class="btn btn-primary"><i class="fas fa-plus-square"></i> Add theme</button>
	</form>
</div>