<?php
$config = [
	"component_name" => "winkeyer",
	"title" => __("Winkeyer"),
	"version" => "1.1",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 380,
		"height" => 300,
	],
	"min_size" => [
		"width" => 300,
		"height" => 220,
	]
];
?>

<script>
	// Globals required by winkey.js
	var station_callsign = "<?= htmlspecialchars($session_info['station_callsign'] ?? ''); ?>";
	var my_call = station_callsign;
	var lang_admin_close = "<?= __('Close'); ?>";
	var lang_esm_run = "<?= html_entity_decode(_pgettext("ESM mode", "Run")); ?>";
	var lang_esm_sp  = "<?= html_entity_decode(_pgettext("ESM mode", "S&P")); ?>";

	// Map contest form field IDs so winkey.js reads the right inputs
	window.winkeyCallsignField = 'qso-callsign';
	window.winkeyRstField      = 'qso-rst-sent';
	window.winkeyRstRField     = 'qso-rst-received';
	window.winkeySerialField    = 'qso-serial-sent';
	window.winkeySerialRField   = 'qso-serial-received';
	window.winkeyExchangeField  = 'qso-exchange-sent';
	window.winkeyExchangeRField = 'qso-exchange-received';
	window.winkeyGridField      = 'qso-gridsquare-sent';
	window.winkeyGridRField     = 'qso-gridsquare-received';
	window.winkeyAlwaysVisible = true;
</script>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body p-2">
		<div id="winkeyer-component" class="h-100 d-flex flex-column">
			<div id="winkey" class="d-flex flex-column flex-grow-1">
				<div class="d-flex align-items-center gap-2 mb-2">
					<button id="connectButton" class="btn btn-sm btn-primary"><?= __("Connect"); ?></button>
					<button id="winkey_settings" type="button" class="btn btn-sm btn-secondary"><i class="fas fa-cog"></i> <?= __("Settings"); ?></button>
					<button id="esm_mode_toggle" type="button" class="btn btn-sm btn-secondary" style="display:none" title="<?= __("Toggle Run / Search & Pounce for ESM"); ?>"><?= _pgettext("ESM mode", "Run"); ?></button>
				</div>

				<div id="winkey_buttons">
					<!-- Function buttons -->
					<div class="d-flex flex-wrap gap-1 mb-2">
						<button id="morsekey_func1" onclick="morsekey_func1()" class="btn btn-sm btn-warning">F1</button>
						<button id="morsekey_func2" onclick="morsekey_func2()" class="btn btn-sm btn-warning">F2</button>
						<button id="morsekey_func3" onclick="morsekey_func3()" class="btn btn-sm btn-warning">F3</button>
						<button id="morsekey_func4" onclick="morsekey_func4()" class="btn btn-sm btn-warning">F4</button>
						<button id="morsekey_func5" onclick="morsekey_func5()" class="btn btn-sm btn-warning">F5</button>
						<button id="morsekey_func6" onclick="morsekey_func6()" class="btn btn-sm btn-warning">F6</button>
						<button id="morsekey_func7" onclick="morsekey_func7()" class="btn btn-sm btn-warning">F7</button>
						<button id="morsekey_func8" onclick="morsekey_func8()" class="btn btn-sm btn-warning">F8</button>
						<button id="morsekey_func9" onclick="morsekey_func9()" class="btn btn-sm btn-warning">F9</button>
						<button id="morsekey_func10" onclick="morsekey_func10()" class="btn btn-sm btn-warning">F10</button>
					</div>

					<!-- CW Speed and control buttons -->
					<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
						<label for="winkeycwspeed" class="form-label mb-0"><?= __("CW Speed"); ?></label>
						<input class="form-control form-control-sm w-auto" type="number" id="winkeycwspeed" name="cwspeed" min="1" max="100" value="20" step="1">
						<button onclick="stop_cw_sending()" class="btn btn-sm btn-danger"><?= __("Stop"); ?></button>
						<button onclick="send_carrier()" id="send_carrier" class="btn btn-sm btn-danger"><?= __("Tune"); ?></button>
						<button hidden id="stop_carrier" onclick="stop_carrier()" class="btn btn-sm btn-danger"><?= __("Stop Tune"); ?></button>
					</div>

					<!-- Text send input -->
					<div class="input-group mb-2">
						<input id="sendText" type="text" class="form-control form-control-sm" placeholder="<?= __('Enter text...'); ?>">
						<button id="sendButton" type="button" class="btn btn-sm btn-success"><?= __("Send"); ?></button>
					</div>
				</div>

				<div class="mt-auto">
					<!-- Status bar -->
					<span id="statusBar" class="small text-muted"></span>

					<!-- TX status: shows the text currently being sent -->
					<div class="d-flex align-items-center gap-2 mt-2">
						<span class="badge bg-secondary">TX</span>
						<span id="winkeySendStatus" class="small font-monospace text-truncate flex-grow-1"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
