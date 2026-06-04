<?php
$config = [
	"component_name" => "statistics",
	"title" => __("Statistics"),
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 420,
		"height" => 480,
	],
	"min_size" => [
		"width" => 300,
		"height" => 340,
	]
];
?>

<?php // Translations for JS ?>
<script>
	let lang_stats_total      = "<?= __("Total QSOs") ?>";
	let lang_stats_rate60     = "<?= __("Rate/h (60 min)") ?>";
	let lang_stats_rate10     = "<?= __("Rate/h (10 min)") ?>";
	let lang_stats_own_col    = "<?= __("Own") ?>";
	let lang_stats_others_col = "<?= __("Others") ?>";
</script>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold">
			<i class="fas fa-chart-bar"></i> <?php echo $config['title']; ?>
		</div>
		<div class="window-controls">
			<button class="window-btn" id="stats-refresh-btn" title="<?= __("Refresh") ?>">
				<i class="fas fa-sync-alt"></i>
			</button>
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body stats-body">

		<!-- Rate counter kacheln -->
		<div class="row g-1 mb-2">
			<div class="col-4 text-center">
				<div class="stats-counter-box">
					<div class="stats-counter-value" id="stats-total">–</div>
					<div class="stats-counter-label" id="lbl-stats-total"></div>
				</div>
			</div>
			<div class="col-4 text-center">
				<div class="stats-counter-box">
					<div class="stats-counter-value text-info" id="stats-rate60">–</div>
					<div class="stats-counter-label" id="lbl-stats-rate60"></div>
				</div>
			</div>
			<div class="col-4 text-center">
				<div class="stats-counter-box">
					<div class="stats-counter-value text-warning" id="stats-rate10">–</div>
					<div class="stats-counter-label" id="lbl-stats-rate10"></div>
				</div>
			</div>
		</div>

		<!-- Time window selector -->
		<div class="d-flex justify-content-end mb-1">
			<div class="btn-group btn-group-sm" id="stats-window-btns" role="group" aria-label="<?= __("Time window") ?>">
				<button type="button" class="btn btn-outline-secondary" data-window="1">1h</button>
				<button type="button" class="btn btn-outline-secondary" data-window="2">2h</button>
				<button type="button" class="btn btn-outline-secondary active" data-window="4">4h</button>
				<button type="button" class="btn btn-outline-secondary" data-window="8">8h</button>
				<button type="button" class="btn btn-outline-secondary" data-window="12">12h</button>
				<button type="button" class="btn btn-outline-secondary" data-window="24">24h</button>
			</div>
		</div>

		<!-- Combined stacked area chart: QSOs per UTC hour, coloured by band -->
		<div class="stats-chart-wrap">
			<canvas id="stats-combined-chart"></canvas>
		</div>

	</div>
</div>
