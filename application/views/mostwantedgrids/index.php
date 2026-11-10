<style>
	.perc-slider {
		position: relative;
		height: 1.5rem;
	}

	.perc-slider input[type="range"] {
		position: absolute;
		width: 100%;
		height: 100%;
		margin: 0;
		pointer-events: none;
		background: transparent;
	}

	.perc-slider input[type="range"]::-webkit-slider-thumb {
		pointer-events: auto;
	}

	.perc-slider input[type="range"]::-moz-range-thumb {
		pointer-events: auto;
	}

	.perc-slider input[type="range"]::-webkit-slider-runnable-track {
		background: transparent;
	}

	.perc-slider input[type="range"]::-moz-range-track {
		background: transparent;
	}

	.perc-slider-track {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		left: 0;
		right: 0;
		height: 0.4rem;
		border-radius: 1rem;
		background: var(--bs-secondary-bg, #dee2e6);
	}

	.perc-slider-fill {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		height: 0.4rem;
		border-radius: 1rem;
		background: var(--bs-success);
	}
</style>
<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<div class="container gridsquare_map_form px-3 px-lg-4 mt-3 mb-3">

    <h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("View a map of most wanted gridsquares on satellite"); ?>
		</div>
		<div class="card-body">

		<?php if ($this->session->flashdata('message')) { ?>
			<div class="alert-message error">
				<p><?php echo $this->session->flashdata('message'); ?></p>
			</div>
		<?php } ?>

		<?php if ($this->session->flashdata('warning')) { ?>
			<div class="alert alert-warning">
				<p><?php echo $this->session->flashdata('warning'); ?></p>
			</div>
		<?php } ?>

		<?php echo sprintf(__("The displayed data is provided by %s. Please consider uploading your SAT logs there to help making this data more convenient. If you have an account already you can find your own satellite %smap%s or %slist%s here."), '<a target="_blank" href="https://gridmaster.fr/">gridmaster.fr</a>', '<a target="_blank" href="https://gridmaster.fr/sat@'.strtolower($this->session->userdata('user_callsign')).'">', '</a>', '<a target="_blank" href="https://gridmaster.fr/sat/list@'.strtolower($this->session->userdata('user_callsign')).'">', '</a>'); ?>
		<?php if ($stats->number > 0) {
			echo sprintf(__("There are %s grids in the database. The most active grid is contained in %s%% of the logs."), $stats->number, $stats->maxperc);
		} ?>
		</div> <!-- /card-body -->

		<div class="px-3 pb-1 small text-muted"><?= __("Filter: percentage of logs containing the grid"); ?></div>
		<div class="d-flex align-items-center gap-2 px-3 pb-2" id="perc_filter">
			<span class="text-nowrap"><?= __("Min"); ?> <span id="perc_min_val" class="fw-bold text-success">0%</span></span>
			<div class="perc-slider flex-grow-1">
				<div class="perc-slider-track"></div>
				<div class="perc-slider-fill" id="perc_fill" style="left: 0; right: 0;"></div>
				<input type="range" class="form-range" id="perc_min" min="0" max="100" step="1" value="0">
				<input type="range" class="form-range" id="perc_max" min="0" max="100" step="1" value="100">
			</div>
			<span class="text-nowrap"><span id="perc_max_val" class="fw-bold text-success">100%</span> <?= __("Max"); ?></span>
		</div>

		<?php
			$mc = json_decode($user_map_custom, true);
			$validHex = function($c, $default) { return preg_match('/^#[0-9a-fA-F]{6}$/', $c ?? '') ? $c : $default; };
			$c_rare   = $validHex($mc['unworked']['color']   ?? '', '#ff0000');
			$c_mid    = $validHex($mc['qso']['color']        ?? '', '#ffff00');
			$c_common = $validHex($mc['qsoconfirm']['color'] ?? '', '#00ff00');
		?>
		<div class="d-flex align-items-center gap-2 px-3 pb-2">
			<span class="small text-nowrap"><?= __("Rare"); ?></span>
			<span class="rounded" style="flex:1; height:.6rem; background:linear-gradient(to right, <?= $c_rare ?>, <?= $c_mid ?>, <?= $c_common ?>);"></span>
			<span class="small text-nowrap"><?= __("Common"); ?></span>
		</div>

		<div id="gridmapcontainer">
			<div id="gridsquare_map" class="map-leaflet" style="width: 100%;"></div>
		</div>

		<script>
			<?php
				echo "var homegrid = \"" . strtoupper($homegrid[0] ?? '') . "\";\n";
			?>
			var gridsquaremap = true;
			var type = "worked";
			<?php
			echo "var jslayer = \"" . $layer . "\";\n";
			echo "var jsattribution ='" . $attribution . "';";

			?>
		</script>
	</div> <!-- /card -->
</div> <!-- /container -->
