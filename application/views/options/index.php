<div class="container">
	<h2><?= __("Wavelog Options"); ?></h2>
	<div class="card">
		<?php $this->load->view('options/tabs', ['active_tab' => $active_tab ?? '']); ?>
		<div class="card-body">
			<p><?= __("Wavelog Options are global settings used for all users of the installation, which are overridden if there's a setting on a user level."); ?></p>
		</div>
	</div>
</div>
