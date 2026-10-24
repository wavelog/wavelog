<style>
	#prop-map { height: 560px; min-height: 320px; }
	.prop-chart { position: relative; height: 320px; }
	.prop-table { white-space: nowrap; font-size: .85rem; }
	.prop-table td, .prop-table th { text-align: center; vertical-align: middle; }
	.prop-table td { background: var(--prop-signal-color) !important; color: #17212b !important; box-shadow: none; }
	.prop-scale { width: 100%; height: 16px; border-radius: .25rem; background: linear-gradient(to right, rgb(248, 113, 113), rgb(250, 204, 21), rgb(74, 222, 128)); }
	.prop-scale-labels { width: 100%; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; font-size: .85rem; }
	.prop-table button { color: inherit; background: transparent; border: 0; width: 100%; }
</style>
<div class="container-fluid mt-3 mb-4" id="propagation">
	<h2><?= __("Propagation"); ?></h2>
	<div class="alert alert-info alert-dismissible fade show" role="alert">
		<?= __("Approximate HF skywave planning estimates, not measured conditions or a guarantee of contact. This independent model is not MINIMUF or VOACAP and has not been calibrated against either. It excludes sporadic E, ground wave, terrain and real-time reception reports."); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __("Close"); ?>"></button>
	</div>
	<div id="prop-error" class="alert alert-danger d-none" role="alert"></div>
	<div id="prop-solar-warning" class="alert alert-warning d-none" role="status">
		<?= __("Recent solar data is unavailable. Check the solar flux and K-index below; these are editable assumptions, not live measurements. Wavelog's solar-data cron job supplies the cached values."); ?>
	</div>
	<form id="prop-form" class="card mb-3">
		<div class="card-body">
			<div class="row g-3">
				<div class="col-sm-6 col-lg-2">
					<label for="prop-home" class="form-label"><?= __("Your locator"); ?></label>
					<input id="prop-home" name="home" class="form-control" value="<?= html_escape($homegrid); ?>" required maxlength="10">
				</div>
				<div class="col-sm-6 col-lg-3">
					<label for="prop-target" class="form-label"><?= __("DX callsign or locator"); ?></label>
					<input id="prop-target" name="target" class="form-control" maxlength="32" placeholder="<?= __("Callsign or locator"); ?>">
				</div>
				<div class="col-sm-6 col-lg-3">
					<label for="prop-time" class="form-label"><?= __("Start time (UTC)"); ?></label>
					<input id="prop-time" name="time" type="datetime-local" class="form-control" required>
				</div>
				<div class="col-sm-6 col-lg-2">
					<label for="prop-mode" class="form-label"><?= __("Mode"); ?></label>
					<select id="prop-mode" name="mode" class="form-select">
						<?php foreach (['SSB', 'CW', 'FT8', 'FT4', 'RTTY'] as $mode) { ?>
						<option><?= $mode; ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-sm-6 col-lg-2">
					<label for="prop-path" class="form-label"><?= __("Path"); ?></label>
					<select id="prop-path" name="path" class="form-select">
						<option value="auto"><?= __("Best path"); ?></option>
						<option value="short"><?= __("Short path"); ?></option>
						<option value="long"><?= __("Long path"); ?></option>
					</select>
				</div>
			</div>
			<details class="mt-3">
				<summary><?= __("Station and solar assumptions"); ?></summary>
				<div class="row g-3 mt-1">
					<?php
					$numeric = [
						'power' => [__("Your power (W)"), $station_power, 1, 5000],
						'dxPower' => [__("DX power (W)"), 100, 1, 5000],
						'gain' => [__("Your antenna gain (dBi)"), 0, -20, 30],
						'dxGain' => [__("DX antenna gain (dBi)"), 0, -20, 30],
						'sfi' => [__("Solar flux (SFI)"), 100, 50, 400],
						'k' => [__("K-index"), 2, 0, 9],
					];
					foreach ($numeric as $name => $field) { ?>
					<div class="col-sm-6 col-lg-2">
						<label for="prop-<?= $name; ?>" class="form-label"><?= $field[0]; ?></label>
						<input type="number" class="form-control" id="prop-<?= $name; ?>" name="<?= $name; ?>"
							value="<?= $field[1]; ?>" min="<?= $field[2]; ?>" max="<?= $field[3]; ?>" step="any" required>
					</div>
					<?php } ?>
					<?php foreach (['noise' => __("Your noise environment"), 'dxNoise' => __("DX noise environment")] as $name => $label) { ?>
					<div class="col-sm-6 col-lg-3">
						<label for="prop-<?= $name; ?>" class="form-label"><?= $label; ?></label>
						<select class="form-select" id="prop-<?= $name; ?>" name="<?= $name; ?>">
							<option value="quiet"><?= __("Quiet rural"); ?></option>
							<option value="rural" selected><?= __("Rural"); ?></option>
							<option value="residential"><?= __("Residential"); ?></option>
							<option value="city"><?= __("City"); ?></option>
						</select>
					</div>
					<?php } ?>
				</div>
				<p class="text-muted small mt-2 mb-0">
					<?= __("Antenna gains are effective gains towards the path, including feeder losses. The DX settings describe an assumed station. Settings are saved in this browser per user and station location; the start time is not saved."); ?>
				</p>
			</details>
			<div class="d-flex flex-wrap gap-2 align-items-center mt-3">
				<button type="submit" class="btn btn-primary" id="prop-submit"><?= __("Calculate"); ?></button>
				<button type="button" class="btn btn-secondary" id="prop-now"><?= __("Now (UTC)"); ?></button>
				<span id="prop-solar-info" class="small text-muted"></span>
			</div>
		</div>
	</form>
	<div class="prop-legend mb-3">
		<div class="prop-scale" aria-hidden="true"></div>
		<div class="prop-scale-labels">
			<span><?= __("Unlikely"); ?><br>&le; -20 dB</span>
			<span class="text-center"><?= __("Mode threshold"); ?><br>0 dB</span>
			<span class="text-end"><?= __("Favourable"); ?><br>&ge; +20 dB</span>
		</div>
		<p class="small mt-2 mb-0"><?= __("Estimated margin above the selected mode's SNR threshold. RX: you hear DX. TX: DX hears you."); ?></p>
	</div>
	<ul class="nav nav-tabs mb-3" role="tablist">
		<?php foreach (['dx' => __("Can I work this DX?"), 'world' => __("Bands towards the world"), 'map' => __("Band map")] as $id => $label) { ?>
		<li class="nav-item" role="presentation">
			<button class="nav-link<?= $id === 'dx' ? ' active' : ''; ?>" id="prop-tab-<?= $id; ?>" data-bs-toggle="tab"
				data-bs-target="#prop-pane-<?= $id; ?>" type="button" role="tab" aria-controls="prop-pane-<?= $id; ?>"
				aria-selected="<?= $id === 'dx' ? 'true' : 'false'; ?>"><?= $label; ?></button>
		</li>
		<?php } ?>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade show active" id="prop-pane-dx" role="tabpanel" aria-labelledby="prop-tab-dx">
			<p id="prop-dx-info" role="status"><?= __("Enter a DX callsign or locator to calculate the next 24 hours."); ?></p>
			<div class="d-flex align-items-center gap-2 mb-3">
				<label for="prop-chart-band" class="form-label mb-0"><?= __("Graph band"); ?></label>
				<select id="prop-chart-band" class="form-select w-auto"></select>
			</div>
			<div class="prop-chart mb-3"><canvas id="prop-chart" role="img" aria-label="<?= __("Estimated RX and TX SNR over the next 24 hours"); ?>"></canvas></div>
			<p class="small text-muted"><?= __("The tables show RX / TX margin in dB, not contact probabilities. LP marks long path. Click a cell to graph that band."); ?></p>
			<div id="prop-dx-table" class="table-responsive"></div>
		</div>
		<div class="tab-pane fade" id="prop-pane-world" role="tabpanel" aria-labelledby="prop-tab-world">
			<p><?= __("Representative region centres, not predictions for every station in that region. Cells show RX / TX margin in dB. Click a cell to open that band on the map."); ?></p>
			<div id="prop-world-table" class="table-responsive"></div>
		</div>
		<div class="tab-pane fade" id="prop-pane-map" role="tabpanel" aria-labelledby="prop-tab-map">
			<div class="d-flex flex-wrap gap-3 mb-3">
				<div>
					<label for="prop-map-band" class="form-label"><?= __("Band"); ?></label>
					<select id="prop-map-band" class="form-select"></select>
				</div>
				<div>
					<label for="prop-direction" class="form-label"><?= __("Direction"); ?></label>
					<select id="prop-direction" class="form-select">
						<option value="rx"><?= __("RX: you hear DX"); ?></option>
						<option value="tx"><?= __("TX: DX hears you"); ?></option>
					</select>
				</div>
			</div>
			<p><?= __("Coarse 5-degree skywave coverage estimate. Dark shading marks night at the selected time. Click the map to inspect a point."); ?></p>
			<div id="prop-map"></div>
		</div>
	</div>
	<div class="mt-3" id="prop-time-control" hidden>
		<label for="prop-hour" class="form-label"><?= __("World / map forecast time (UTC)"); ?>: <strong id="prop-hour-label"></strong></label>
		<input type="range" id="prop-hour" class="form-range" min="0" max="23" step="1" value="0">
	</div>
</div>
<script>
	const propagationConfig = <?= json_encode([
		'lookupUrl' => site_url('propagation/lookup'),
		'settingsKey' => $settings_key,
		'solar' => [
			'sfi' => isset($solar['solarflux']) && is_numeric($solar['solarflux']) ? (float)$solar['solarflux'] : null,
			'k' => isset($solar['kindex']) && is_numeric($solar['kindex']) ? (float)$solar['kindex'] : null,
			'updated' => $solar['updated'] ?? '',
			'modified' => $solar_modified,
			'observed' => $solar_observed,
		],
		'text' => [
			'homeError' => __("Enter a valid Maidenhead locator for your station."),
			'lookupError' => __("Callsign lookup failed. Please try again or enter a locator."),
			'storageError' => __("Unable to save or restore propagation settings in this browser."),
			'calculationError' => __("Unable to calculate the forecast. Check the station settings and UTC date."),
			'loading' => __("Calculating..."),
			'calculate' => __("Calculate"),
			'region' => __("Region"), 'band' => __("Band"), 'threshold' => __("Mode threshold"),
			'rx' => __("RX SNR (dB)"), 'tx' => __("TX SNR (dB)"), 'solar' => __("Cached solar data"),
			'empty' => __("Enter a DX callsign or locator to calculate the next 24 hours."),
			'good' => __("Favourable"), 'marginal' => __("Marginal"), 'unlikely' => __("Unlikely"),
			'saved' => __("Saved station assumptions restored. Solar inputs remain editable."),
		],
		'regions' => [
			[__("Northern Europe"), 60, 15], [__("Southern Europe"), 42, 15],
			[__("Eastern North America"), 40, -75], [__("Western North America"), 38, -122],
			[__("Central America"), 15, -90], [__("Northern South America"), 0, -65],
			[__("Southern South America"), -35, -65], [__("North Africa"), 28, 15],
			[__("Southern Africa"), -28, 25], [__("Middle East"), 30, 45],
			[__("Central Asia"), 45, 75], [__("East Asia"), 35, 135],
			[__("Southeast Asia"), 5, 110], [__("Oceania"), -30, 145],
		],
	], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
