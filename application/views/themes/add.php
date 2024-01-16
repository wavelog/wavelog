
<div class="container" id="create_mode">

	<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

	<?php if($this->session->flashdata('notice')) { ?>
		<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
		</div>
	<?php } ?>

	<?php $this->load->helper('form'); ?>

	<?php echo validation_errors(); ?>

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
			<small id="themeModeInputHelp" class="form-text text-muted">This defines wherever the theme is a light or a dark one. On this basis the Logo is chosen.</small>
		</div>

		<button type="button" onclick="addTheme(this.form);" class="btn btn-primary"><i class="fas fa-plus-square"></i> Add theme</button>
	</form>
</div>
