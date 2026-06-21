<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="card-header py-2">
	<ul class="nav nav-tabs card-header-tabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'appearance') echo 'active fw-bold'; ?>" href="<?= site_url('options/appearance'); ?>">
				<i class="fas fa-palette"></i> <?= __("Appearance"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'radio') echo 'active fw-bold'; ?>" href="<?= site_url('options/radio'); ?>">
				<i class="fas fa-broadcast-tower"></i> <?= __("Radios"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'email') echo 'active fw-bold'; ?>" href="<?= site_url('options/email'); ?>">
				<i class="fas fa-envelope"></i> <?= __("Email"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'dxcluster') echo 'active fw-bold'; ?>" href="<?= site_url('options/dxcluster'); ?>">
				<i class="fas fa-satellite-dish"></i> <?= __("DXCluster"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'maptiles') echo 'active fw-bold'; ?>" href="<?= site_url('options/maptiles'); ?>">
				<i class="fas fa-map"></i> <?= __("Maptiles Server"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'hon') echo 'active fw-bold'; ?>" href="<?= site_url('options/hon'); ?>">
				<i class="fas fa-star"></i> <?= __("Hams Of Note"); ?>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?php if (isset($active_tab) && $active_tab == 'version_dialog') echo 'active fw-bold'; ?>" href="<?= site_url('options/version_dialog'); ?>">
				<i class="fas fa-code-branch"></i> <?= __("Version Info"); ?>
			</a>
		</li>
	</ul>
</div>
