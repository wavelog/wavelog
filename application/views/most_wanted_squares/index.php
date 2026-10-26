<div class="container">
	<br>
	<h2><?= __("Most Wanted Squares") . ' (MMMonVHF)'; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Export a list of 4-digit gridsquares that have not been worked yet on VHF."); ?>
		</div>

		<?php if (empty($station_profile->station_gridsquare)) { ?>
			<div class="alert alert-danger" role="alert">
				<?= __("The active station location has no gridsquare set."); ?>
			</div>
		<?php } else { ?>
			<div class="alert alert-info" role="alert">
				<?= sprintf(__("The list contains all not worked gridsquares within %d km of the gridsquare %s of the active station location %s (%s)."), $radius_km, $station_profile->station_gridsquare, $station_profile->station_callsign, $station_profile->station_profile_name); ?>
			</div>
		<?php } ?>

		<div class="card-body">

			<form class="form" action="<?php echo site_url('most_wanted_squares/export'); ?>" method="post">
				<div class="row">
					<div class="mb-3 col-md-3">
						<label for="band"><?= __("Band"); ?></label>
						<select id="band" name="band" class="form-select">
							<?php foreach ($bands as $band) {
								echo '<option value="' . $band . '">' . $band . '</option>' . "\n";
							} ?>
						</select>
					</div>
					<div class="mb-3 col-md-6 d-flex align-items-center">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="exclude_wet" id="exclude_wet" value="1" checked>
							<label class="form-check-label" for="exclude_wet"><?= __("Exclude squares that contain no land"); ?></label>
						</div>
					</div>
				</div>
				<br>
				<button type="submit" class="btn btn-primary mb-2" value="Export" <?php if (empty($station_profile->station_gridsquare)) echo 'disabled'; ?>><?= __("Export"); ?></button>
			</form>
		</div>
	</div>
</div>
