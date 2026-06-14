<?php
$config = [
	/**
	 * Simple UTC Clock Component
	 */

	"component_name" => "clock",
	"title" => "UTC",
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 400,
		"height" => 120,
	],
	"min_size" => [
		"width" => 90,
		"height" => 100,
	]
];
?>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body" style="overflow: hidden;">
		<div class="clock-container">
			<div class="clock-display" id="utc-clock">
				<span class="time font-monospace fw-bold" id="utc-time">--:--:--</span>
			</div>
		</div>
	</div>
</div>