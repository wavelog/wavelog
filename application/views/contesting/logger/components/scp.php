<?php
$config = [
	/**
	 * SCP (Super Check Partial) Component
	 * Provides callsign lookup and autocomplete from MASTER.SCP and Clublog databases
	 */

	"component_name" => "scp",
	"title" => __("Super Check Partial"),
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 500,
		"height" => 400,
	],
	"min_size" => [
		"width" => 350,
		"height" => 300,
	]
];
?>

<?php // Translations for JS ?>
<script>
	let lang_scp_loading = "<?= __("Loading SCP databases...") ?>";
	let lang_scp_ready = "<?= __("Ready") ?>";
	let lang_scp_error = "<?= __("Error loading database") ?>";
	let lang_scp_loading_pct = "<?= __("Loading %s...") ?>";
	let lang_scp_no_matches = "<?= __("No matches found for '%s'") ?>";
	let lang_scp_hint = "<?= __("Enter a partial callsign to see matches") ?>";
</script>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold">
			<i class="fas fa-search"></i> <?php echo $config['title']; ?>
		</div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body" style="display: flex; flex-direction: column; overflow: hidden;">
		<div class="scp-container" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">

			<!-- Status Bar -->
			<div class="card border mb-3" style="flex-shrink: 0;">
				<div class="card-body p-2">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<span class="badge bg-secondary" id="scp-status">
								<i class="fas fa-circle-notch fa-spin"></i> <?= __("Loading..."); ?>
							</span>
						</div>
						<div>
							<small class="text-muted">
								<?= __("Total:"); ?> <span id="scp-total-count" class="fw-bold">0</span>
							</small>
						</div>
					</div>
				</div>
			</div>

			<!-- Results List -->
			<div class="card border" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
				<div class="card-header d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
					<h6 class="mb-0 fw-bold"><?= __("Matches"); ?></h6>
					<span class="badge bg-primary" id="scp-match-count">0</span>
				</div>
				<div class="card-body p-0" style="flex: 1; overflow: hidden;">
					<div id="scp-results" class="scp-results" style="overflow-y: auto; height: 100%; display: flex; align-items: center; justify-content: center;">
						<div class="text-center text-muted p-4">
							<i class="fas fa-info-circle fa-2x mb-2"></i>
							<p><?= __("Enter a partial callsign to see matches"); ?></p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>