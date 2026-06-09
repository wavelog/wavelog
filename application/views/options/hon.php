<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= __("Wavelog Options"); ?></h2>
	<div class="card">
		<?php $this->load->view('options/tabs', ['active_tab' => $active_tab ?? '']); ?>
		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php echo form_open('options/hon_save'); ?>

				<div class="mb-3">
					<label for="globalSearch"><?= __("Provider for Hams Of Note"); ?></label>
					<p><?= sprintf(__("The URL which provides the Hams Of Note List. See example and how it works here %s"),'<a href="https://docs.wavelog.org/user-guide/features/hams-of-note/">'.__("Wiki")."<a/>"); ?></p>
					<input type="text" name="hon_url" class="form-control" id="dxcache_url" aria-describedby="hon_urlHelp" value="<?php echo $this->optionslib->get_option('hon_url'); ?>">
					<small id="hon_urlHelp" class="form-text text-muted"><?= sprintf(__("URL of the Hams Of Note List. e.g. %s"), "https://api.ham2k.net/data/ham2k/hams-of-note.txt" ); ?></small>
				</div>
				<!-- Save the Form -->
				<input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
			</form>
		</div>
	</div>
</div>
