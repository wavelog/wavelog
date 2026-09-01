<style>
	#az_map {
		max-width: 100%;
		height: auto;
		display: block;
	}
</style>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<?php if (!$has_center) { ?>

		<div class="alert alert-warning" role="alert">
			<?= __("No gridsquare is set for your active station location, so the map cannot be centered."); ?>
			<a href="<?php echo site_url('stationsetup'); ?>" class="alert-link"><?= __("Station Setup"); ?></a>
		</div>

	<?php } else { ?>

	<div class="card">
		<div class="card-header">
			<?= __("Azimuthal map centered on your active station location"); ?>
		</div>
		<div class="card-body">

			<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
				<div class="btn-group" id="az_projection_toggle" role="group" aria-label="<?= __("Projection"); ?>">
					<button type="button" class="btn btn-sm btn-primary active" data-projection="equidistant"><?= __("Azimuthal Equidistant"); ?></button>
					<button type="button" class="btn btn-sm btn-primary" data-projection="equalarea"><?= __("Lambert Equal-Area"); ?></button>
				</div>

				<select class="form-select form-select-sm w-auto" id="az_ring_spacing" aria-label="<?= __("Distance rings"); ?>">
					<option value="1000">1000 km</option>
					<option value="2000">2000 km</option>
					<option value="3000">3000 km</option>
					<option value="5000" selected>5000 km</option>
				</select>

				<div class="form-check ms-2">
					<input class="form-check-input" type="checkbox" id="az_fields">
					<label class="form-check-label" for="az_fields"><?= __("Maidenhead fields"); ?></label>
				</div>

				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="az_cq">
					<label class="form-check-label" for="az_cq"><?= __("CQ zones"); ?></label>
				</div>

				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="az_itu">
					<label class="form-check-label" for="az_itu"><?= __("ITU zones"); ?></label>
				</div>

				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="az_dxcc">
					<label class="form-check-label" for="az_dxcc"><?= __("DXCC prefixes"); ?></label>
				</div>

				<button type="button" class="btn btn-sm btn-primary ms-auto" id="az_btn_png"><i class="fas fa-download"></i> <?= __("Download PNG"); ?></button>
			</div>

			<div id="az_map_wrap" class="d-flex justify-content-center">
				<svg id="az_map" role="img" aria-label="<?= __("Azimuthal map"); ?>"></svg>
			</div>

			<p id="az_caption" class="text-center text-muted small mb-0"></p>

		</div>
	</div>

	<script type="text/javascript">
		var az_center = <?php echo json_encode(['lat' => $center_lat, 'lng' => $center_lng], JSON_HEX_TAG | JSON_HEX_APOS); ?>;
		var az_homegrid = <?php echo json_encode($homegrid, JSON_HEX_TAG | JSON_HEX_APOS); ?>;
		var az_station_label = <?php echo json_encode($station_label, JSON_HEX_TAG | JSON_HEX_APOS); ?>;
		var az_dxcc_list = <?php echo json_encode($dxcc_markers, JSON_HEX_TAG | JSON_HEX_APOS); ?>;
	</script>

	<?php } ?>

</div>
