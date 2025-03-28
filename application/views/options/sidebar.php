<div class="col-md-3">
	<div class="card">
		<ul class="list-group list-group-flush">
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/appearance'); ?>"><?= __("Appearance"); ?></a></li>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/radio'); ?>"><?= __("Radios"); ?></a></li>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/email'); ?>"><?= __("Email"); ?></a></li>
      <?php if (!($this->config->item('disable_oqrs') ?? false)) { ?>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/oqrs'); ?>"><?= __("OQRS Options"); ?></a></li>
      <?php } ?>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/dxcluster'); ?>"><?= __("DXCluster"); ?></a></li>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/maptiles'); ?>"><?= __("Maptiles Server"); ?></a></li>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/hon'); ?>"><?= __("Hams Of Note"); ?></a></li>
			<li class="list-group-item"><a class="nav-link" href="<?php echo site_url('options/version_dialog'); ?>"><?= __("Version Info"); ?></a></li>
		</ul>
	</div>
</div>
