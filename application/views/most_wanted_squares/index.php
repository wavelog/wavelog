<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?> <small class="text-muted">(MMMonVHF)</small></h2>

	<?php if (empty($station_profile->station_gridsquare)) { ?>

		<div class="alert alert-warning" role="alert">
			<?= __("The active station location has no gridsquare set."); ?>
			<a href="<?php echo site_url('stationsetup'); ?>" class="alert-link"><?= __("Station Setup"); ?></a>
		</div>

	<?php } else { ?>

	<div class="card">
		<div class="card-header">
			<?= __("Export a list of 4-digit gridsquares that have not been worked yet on VHF."); ?>
		</div>
		<div class="card-body">

			<p class="small text-muted mb-3">
				<?= sprintf(__("The list contains all not worked gridsquares within %d km of the gridsquare %s of the active station location %s (%s)."), $radius_km, htmlspecialchars($station_profile->station_gridsquare), htmlspecialchars($station_profile->station_callsign), htmlspecialchars($station_profile->station_profile_name)); ?>
			</p>

			<form class="d-flex flex-wrap gap-2 align-items-center" action="<?php echo site_url('most_wanted_squares/export'); ?>" method="post">
				<label for="band"><?= __("Band"); ?></label>
				<select id="band" name="band" class="form-select form-select-sm w-auto">
					<?php foreach ($bands as $band) {
						echo '<option value="' . $band . '">' . $band . '</option>' . "\n";
					} ?>
				</select>

				<div class="form-check ms-2">
					<input class="form-check-input" type="checkbox" name="exclude_wet" id="exclude_wet" value="1" checked>
					<label class="form-check-label" for="exclude_wet"><?= __("Exclude squares that contain no land"); ?></label>
				</div>

				<button type="submit" class="btn btn-sm btn-primary ms-2"><i class="fas fa-download"></i> <?= __("Export"); ?></button>
			</form>

		</div>
	</div>

	<?php } ?>

</div>
