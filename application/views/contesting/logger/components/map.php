<?php
$config = [
	/**
	 * Simple Map Component
	 */

	"component_name" => "map",
	"title" => "Map",
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 400,
		"height" => 360,
	],
	"min_size" => [
		"width" => 90,
		"height" => 100,
	]
];
?>

<!-- Map Component: Leaflet dependencies to be loaded dynamically -->
<script type="text/javascript">
	// Extend ContestLoggerConfig with map-specific options
	if (!window.ContestLoggerConfig) {
		window.ContestLoggerConfig = {};
	}

	// Add map configuration
	window.ContestLoggerConfig.map = {
		tileServer: <?php echo json_encode($this->optionslib->get_option('option_map_tile_server') ?: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'); ?>,
		tileServerCopyright: <?php echo json_encode($this->optionslib->get_option('option_map_tile_server_copyright') ?: '&copy; OpenStreetMap contributors'); ?>,
		tileSubdomains: <?php echo json_encode($this->optionslib->get_option('option_map_tile_subdomains') ?: 'abc'); ?>
	};

	// CRITICAL: Wavelog uses a modified Leaflet.js that requires these global variables
	// They must be defined BEFORE loading leaflet.js
	window.option_map_tile_subdomains = window.ContestLoggerConfig.map.tileSubdomains;
	window.option_map_tile_server = window.ContestLoggerConfig.map.tileServer;
	window.option_map_tile_server_copyright = window.ContestLoggerConfig.map.tileServerCopyright;

	// Store Leaflet library paths for dynamic loading by MapComponent
	if (!window.MapComponentAssets) {
		window.MapComponentAssets = [
			<?php
			$leafletAssets = [
				"assets/js/leaflet/leaflet.js",
				"assets/js/leaflet/Control.FullScreen.js",
				"assets/js/leaflet/L.Maidenhead.qrb.js",
				"assets/js/leaflet/leaflet.geodesic.js"
			];
			$output = [];
			foreach ($leafletAssets as $asset) {
				$output[] = json_encode($this->paths->cache_buster($asset));
			}
			echo implode(",", $output);
			?>
		];
	}
</script>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body" style="overflow: hidden; padding: 0;">
		<div class="map-container" style="width: 100%; height: 100%;">
			<div class="map-leaflet" id="map-display" style="width: 100%; height: 100%;">
			</div>
		</div>
	</div>
</div>