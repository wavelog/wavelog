<?php
$config = [
	/**
	 * Radio CAT Monitoring Component
	 * Displays frequency and mode from selected radio (one-way monitoring)
	 */

	"component_name" => "radio",
	"title" => __("Radio"),
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 400,
		"height" => 250,
	],
	"min_size" => [
		"width" => 300,
		"height" => 200,
	]
];
?>

<?php // Translations for JS ?>
<script>
	let lang_radio_no_data = "<?= __("No radio data available") ?>";
	let lang_radio_data_old = "<?= __("Radio data is %s minutes old") ?>";
	let lang_radio_connected = "<?= __("Radio connected") ?>";
	let lang_radio_manual_mode = "<?= __("Manual mode: Enter frequency/mode manually") ?>";
	let lang_radio_ws_connecting = "<?= __("Connecting to WebSocket radio...") ?>";
	let lang_radio_waiting = "<?= __("Waiting for radio data...") ?>";
	let lang_radio_ws_connected = "<?= __("WebSocket radio connected") ?>";
	let lang_radio_ws_error = "<?= __("WebSocket connection error") ?>";
	let lang_radio_ws_reconnecting = "<?= __("WebSocket disconnected – reconnecting...") ?>";
	let lang_radio_ws_offline = "<?= __("WebSocket radio offline") ?>";
</script>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body">
		<div id="radio-component">
			<div class="radio-component p-2 rounded">
				<div class="mb-2">
					<label for="radio-select" class="form-label">
						<i class="fas fa-broadcast-tower"></i> <?= __("Radio"); ?>
					</label>
					<select id="radio-select" class="form-select">
						<option value="0"><?= __("Manual - No Radio"); ?></option>
						<option value="ws"><?= __("WebSocket (Real-time)"); ?></option>
						<?php if (isset($radios) && $radios->num_rows() > 0): ?>
							<?php foreach ($radios->result() as $row): ?>
								<option value="<?php echo $row->id; ?>"><?php echo htmlspecialchars($row->radio); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<div class="radio-display">
					<div class="row g-2">
						<div class="col-7">
							<label for="frequency" class="form-label"><?= __("Frequency"); ?></label>
							<div class="input-group">
								<input type="text" tabindex="3" class="form-control font-monospace" id="freq_calculated" name="freq_calculated" value="0" />
								<span class="input-group-text btn-included-on-field" id="qrg_unit">...</span>
							</div>
							<input style="display: none;" type="text" class="form-control" id="frequency" name="freq_display" value="<?php echo $this->session->userdata('freq'); ?>" />
						</div>
						<div class="col-5">
							<label for="mode" class="form-label"><?= __("Mode"); ?></label>
							<select id="mode" class="form-select text-uppercase">
								<?php if (isset($modes) && is_array($modes) && count($modes) > 0): ?>
									<?php foreach ($modes as $mode): ?>
										<?php if ($mode->submode == null): ?>
											<option value="<?php echo htmlspecialchars($mode->mode); ?>"><?php echo htmlspecialchars($mode->mode); ?></option>
										<?php else: ?>
											<option value="<?php echo htmlspecialchars($mode->submode); ?>">&rArr; <?php echo htmlspecialchars($mode->submode); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>

					<!-- Band Selection Tabs -->
					<div class="mt-2">
						<label class="form-label mb-1"><?= __("Band"); ?></label>
						<?php if (isset($bands) && is_array($bands) && count($bands) > 0): ?>
							<?php
							// Gruppiere Bänder in logische Kategorien
							$hf_mf = [];
							$vhf_uhf = [];
							$microwave = [];

							foreach ($bands as $key => $bandgroup) {
								if (in_array(strtolower($key), ['hf', 'mf'])) {
									$hf_mf = array_merge($hf_mf, $bandgroup);
								} elseif (in_array(strtolower($key), ['vhf', 'uhf'])) {
									$vhf_uhf = array_merge($vhf_uhf, $bandgroup);
								} else {
									$microwave = array_merge($microwave, $bandgroup);
								}
							}
							$hasHF = !empty($hf_mf);
							$hasVHF = !empty($vhf_uhf);
							$hasMW = !empty($microwave);
							?>
							<ul class="nav nav-tabs nav-tabs-sm mb-2" id="bandTabs" role="tablist">
								<?php if ($hasHF): ?>
									<li class="nav-item" role="presentation">
										<button class="nav-link active" id="hf-tab" data-bs-toggle="tab" data-bs-target="#hf-bands" type="button" role="tab">HF/MF</button>
									</li>
								<?php endif; ?>
								<?php if ($hasVHF): ?>
									<li class="nav-item" role="presentation">
										<button class="nav-link<?php echo !$hasHF ? ' active' : ''; ?>" id="vhf-tab" data-bs-toggle="tab" data-bs-target="#vhf-bands" type="button" role="tab">VHF/UHF</button>
									</li>
								<?php endif; ?>
								<?php if ($hasMW): ?>
									<li class="nav-item" role="presentation">
										<button class="nav-link<?php echo (!$hasHF && !$hasVHF) ? ' active' : ''; ?>" id="mw-tab" data-bs-toggle="tab" data-bs-target="#mw-bands" type="button" role="tab">SHF</button>
									</li>
								<?php endif; ?>
							</ul>
							<div class="tab-content" id="bandTabContent">
								<?php if ($hasHF): ?>
									<div class="tab-pane fade show active" id="hf-bands" role="tabpanel">
										<div class="band-buttons-compact small">
											<?php foreach ($hf_mf as $band): ?>
												<button type="button" class="btn btn-sm btn-outline-secondary band-btn-compact"
													data-band="<?php echo htmlspecialchars($band); ?>"
													<?php if ($this->session->userdata('band') == $band): ?>data-selected="true" <?php endif; ?>>
													<?php echo htmlspecialchars($band); ?>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
								<?php if ($hasVHF): ?>
									<div class="tab-pane fade<?php echo !$hasHF ? ' show active' : ''; ?>" id="vhf-bands" role="tabpanel">
										<div class="band-buttons-compact small">
											<?php foreach ($vhf_uhf as $band): ?>
												<button type="button" class="btn btn-outline-secondary band-btn-compact"
													data-band="<?php echo htmlspecialchars($band); ?>"
													<?php if ($this->session->userdata('band') == $band): ?>data-selected="true" <?php endif; ?>>
													<?php echo htmlspecialchars($band); ?>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
								<?php if ($hasMW): ?>
									<div class="tab-pane fade<?php echo (!$hasHF && !$hasVHF) ? ' show active' : ''; ?>" id="mw-bands" role="tabpanel">
										<div class="band-buttons-compact small">
											<?php foreach ($microwave as $band): ?>
												<button type="button" class="btn btn-outline-secondary band-btn-compact"
													data-band="<?php echo htmlspecialchars($band); ?>"
													<?php if ($this->session->userdata('band') == $band): ?>data-selected="true" <?php endif; ?>>
													<?php echo htmlspecialchars($band); ?>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="radio-status-info mt-2" id="radio-status-info"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	window.ContestLoggerConfig = window.ContestLoggerConfig || {};
	window.ContestLoggerConfig.radios = [
		<?php if (isset($radios) && $radios->num_rows() > 0): ?>
			<?php foreach ($radios->result() as $row):
				echo "{
				id: '{$row->id}',
				name: " . json_encode($row->radio) . "
			},";
			endforeach; ?>
		<?php endif; ?>
	];

	// Band default frequencies cache (loaded from backend)
	// Format: { "40m": { "SSB": 7100000, "CW": 7020000, "DATA": 7070000 }, ... }
	window.ContestLoggerConfig.bandDefaults = <?php
												$bandDefaults = [];
												if (isset($bands) && is_array($bands) && count($bands) > 0):
													$this->load->library('frequency');
													$modes = ['SSB', 'CW', 'DATA'];

													// Flatten all bands
													$allBands = [];
													foreach ($bands as $bandgroup) {
														$allBands = array_merge($allBands, $bandgroup);
													}

													// For each band, get default frequencies for each mode
													foreach ($allBands as $band) {
														$bandDefaults[$band] = [];
														foreach ($modes as $mode) {
															$freq = $this->frequency->convert_band($band, $mode);
															$bandDefaults[$band][$mode] = intval($freq);
														}
													}
												endif;
												echo json_encode($bandDefaults, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
												?>;

	// QRG units cache - user's preferred frequency display units per band
	// Loaded from user session to avoid additional Ajax calls
	window.ContestLoggerConfig.qrgUnits = <?php
											$qrgUnits = [];
											foreach ($this->session->get_userdata() as $key => $value) {
												if (strpos($key, 'qrgunit_') === 0) {
													$band = str_replace('qrgunit_', '', $key);
													$qrgUnits[$band] = $value;
												}
											}
											echo json_encode($qrgUnits, JSON_PRETTY_PRINT);
											?>;
</script>