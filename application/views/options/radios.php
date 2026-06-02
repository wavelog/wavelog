<div class="container">
	<h2><?= __("Wavelog Options"); ?></h2>
	<div class="card">
		<?php $this->load->view('options/tabs', ['active_tab' => $active_tab ?? '']); ?>
		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php echo form_open('options/radio_save'); ?>

				<div class="mb-3">
					<label for="globalSearch"><?= __("Radio Timeout Warning"); ?></label>
					<p><?= __("The Radio Timeout Warning is used on the QSO entry panel to alert you to radio interface disconnects."); ?></p>
					<input type="text" name="radioTimeout" class="form-control" id="radioTimeout" aria-describedby="radioTimeoutHelp" value="<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>">
					<small id="radioTimeoutHelp" class="form-text text-muted"><?= __("This number is in seconds."); ?></small>
				</div>

				<!-- Save the Form -->
				<input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
			</form>
		</div>
	</div>
</div>
