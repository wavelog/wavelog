<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= __("Logbook"); ?></h2>
	<div class="card">
	<?php if ($results) { ?>
		<div class="card-header py-2">
			<h6 class="mb-0"><i class="fas fa-list"></i> <?= __("Active Logbook"); ?>: <span class="badge text-bg-info ms-1"><?php echo $this->logbooks_model->find_name($this->session->userdata('active_station_logbook')); ?></span><i id="directory_tooltip" data-bs-toggle="tooltip" data-bs-placement="right" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Displaying all QSOs of station locations which are linked to this logbook"); ?>"></i></h6>
		</div>
	<?php } ?>
	<?php if ($this->session->flashdata('notice')) { ?>
		<div class="alert alert-info" role="alert">
			<?php echo $this->session->flashdata('notice'); ?>
		</div>
	<?php } ?>

	<?php if ($this->optionslib->get_option('logbook_map') != "false") { ?>
	<script>
		let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
		</script>

<!-- Map -->
<div id="map" class="map-leaflet" style="width: 100%; height: 350px"></div>
<?php } ?>

		<div class="card">
			<div class="card-header py-2">
				<h6 class="mb-0"><i class="fas fa-list"></i> <?= __("Recent QSOs"); ?></h6>
			</div>
			<div class="card-body">
				<?php $this->load->view('view_log/partial/log_ajax') ?>
			</div>
		</div>
</div>
