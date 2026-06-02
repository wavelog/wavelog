<?php
$config = [
	/**
	 * QSO Entry Form Component
	 * Provides a form for logging QSOs during a contest.
	 */

	"component_name" => "qso-form",
	"title" => __("QSO Logger"),
	"version" => "1.0",
	"author" => "WaveLog Development Team",
	"default_size" => [
		"width" => 1400,
		"height" => 600,
	],
	"min_size" => [
		"width" => 800,
		"height" => 400,
	]
];
?>

<?php // Translations for JS ?>
<script>
	let lang_dxcc_lookup = "<?= __("DXCC Lookup...") ?>";
	let lang_dxcc_lookup_failed = "<?= __("DXCC Lookup failed") ?>";
	let lang_dxcc_not_found = "<?= __("DXCC: not found") ?>";
	let lang_worked_before = "<?= __("Already worked on %s") ?>";
	let lang_radio_component_not_available = "<?= __("Radio component not available. Can not save QSO.") ?>";
	let lang_frequency_or_mode_not_set = "<?= __("Frequency or Mode not set. Please check radio settings.") ?>";
	let lang_error = "<?= __("Error") ?>";
	let lang_status_new = "<?= __("New") ?>";
	let lang_status_synced = "<?= __("Synced") ?>";
	let lang_status_error = "<?= __("Error") ?>";
	let lang_status_unknown = "<?= __("Unknown") ?>";
	let lang_delete_qso_confirm = "<?= __("Delete this QSO?") ?>";
	let lang_qso_save = "<?= __("Save") ?>";
	let lang_qso_edit = "<?= __("Edit") ?>";
	let lang_qso_delete = "<?= __("Delete") ?>";
	let lang_qso_cancel = "<?= __("Cancel") ?>";
	let lang_qso_not_own = "<?= __("You can only edit your own QSOs") ?>";
</script>

<?php // hide number input spinners, qso form field sizing ?>
<style>
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { -moz-appearance: textfield; appearance: textfield; }
#qso-gridsquare-sent, #qso-gridsquare-received,
#qso-exchange-sent, #qso-exchange-received { text-transform: uppercase; }
</style>

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body d-flex flex-column overflow-hidden">
		<div class="qso-form-container d-flex flex-column flex-grow-1 overflow-hidden">
			<!-- QSO Entry Form -->
			<div class="card border mb-2 flex-shrink-0">
				<div class="card-body p-2">
					<div id="qso-form" class="qso-form">
						<div class="row g-2 align-items-end flex-nowrap">

							<!-- Callsign -->
							<div class="col-2">
								<label for="qso-callsign" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Callsign"); ?></label>
								<input type="text" id="qso-callsign" name="callsign" class="form-control fw-bold" autocomplete="off" style="letter-spacing: 2px;">
								<input type="hidden" id="qso-dxcc" name="dxcc_id" value="">
								<input type="hidden" id="qso-dxcc-adif" name="dxcc_adif" value="">
								<input type="hidden" id="qso-dxcc-cont" name="dxcc_cont" value="">
								<input type="hidden" id="qso-dxcc-entity" name="dxcc_entity" value="">
								<input type="hidden" id="qso-dxcc-cqz" name="dxcc_cqz" value="">
								<input type="hidden" id="qso-dxcc-lat" name="dxcc_lat" value="">
								<input type="hidden" id="qso-dxcc-long" name="dxcc_long" value="">
								<input type="hidden" id="qso-dxcc-start" name="dxcc_start" value="">
								<input type="hidden" id="qso-dxcc-end" name="dxcc_end" value="">
							</div>

							<!-- RST Sent / Received -->
							<div class="col-1">
								<label for="qso-rst-sent" class="form-label fw-bold mb-1 small text-uppercase"><?= __("RST S"); ?></label>
								<input type="text" id="qso-rst-sent" name="rst_sent" class="form-control text-center fw-bold" value="59" placeholder="59" maxlength="3" tabindex="-1">
							</div>
							<div class="col-1">
								<label for="qso-rst-received" class="form-label fw-bold mb-1 small text-uppercase"><?= __("RST R"); ?></label>
								<input type="text" id="qso-rst-received" name="rst_received" class="form-control text-center fw-bold" value="59" placeholder="59" maxlength="3" tabindex="-1">
							</div>

							<!-- Serial Sent / Received (shown only when exchangetype has serial) -->
							<div class="serial-field col-1" style="display:none;">
								<label for="qso-serial-sent" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Nr. S"); ?></label>
								<input type="number" id="qso-serial-sent" name="serial_sent" class="form-control text-center fw-bold no-spinner" min="1" tabindex="-1">
							</div>
							<div class="serial-field col-1" style="display:none;">
								<label for="qso-serial-received" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Nr. R"); ?></label>
								<input type="number" id="qso-serial-received" name="serial_received" class="form-control text-center fw-bold no-spinner" min="1">
							</div>

							<!-- Gridsquare Sent / Received (shown only when exchangetype has gridsquare) -->
							<div class="gridsquare-field col-1" style="display:none;">
								<label for="qso-gridsquare-sent" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Grid S"); ?></label>
								<input type="text" id="qso-gridsquare-sent" name="gridsquare_sent" class="form-control text-center fw-bold" maxlength="10">
							</div>
							<div class="gridsquare-field col-1" style="display:none;">
								<label for="qso-gridsquare-received" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Grid R"); ?></label>
								<input type="text" id="qso-gridsquare-received" name="gridsquare_received" class="form-control text-center fw-bold" maxlength="10">
							</div>

							<!-- Exchange Sent / Received (shown only when exchangetype has text exchange) -->
							<div class="exchange-text-field col">
								<label for="qso-exchange-sent" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Exch S"); ?></label>
								<input type="text" id="qso-exchange-sent" name="exchange_sent" class="form-control text-center fw-bold" tabindex="-1">
							</div>
							<div class="exchange-text-field col">
								<label for="qso-exchange-received" class="form-label fw-bold mb-1 small text-uppercase"><?= __("Exch R"); ?></label>
								<input type="text" id="qso-exchange-received" name="exchange_received" class="form-control text-center fw-bold">
							</div>

						</div>
						<!-- Worked before warning -->
						<div id="qso-worked-before-warning" class="badge bg-danger fw-bold" style="display:none;" aria-live="assertive"></div>
						<!-- DXCC info line -->
						<div id="qso-dxcc-info" class="form-text text-muted small mt-1" aria-live="polite"></div>
					</div>
				</div>
			</div>

			<!-- QSO List -->
			<div class="card border d-flex flex-column flex-grow-1 min-h-0">
				<div class="card-header d-flex justify-content-between align-items-center flex-shrink-0">
					<h6 class="mb-0 fw-bold"><?= __("Recent QSOs"); ?></h6>
					<span class="badge bg-success" id="qso-count-badge">0</span>
				</div>
				<div class="card-body p-0 d-flex flex-column flex-grow-1 overflow-hidden">
					<div class="qso-table-scroll table-responsive flex-grow-1 overflow-y-auto">
						<table class="table table-hover table-sm mb-0 qso-table" id="qso-table">
							<thead class="table-secondary sticky-top">
								<tr>
									<th class="fw-bold"><?= __("Time"); ?></th>
									<th class="fw-bold"><?= __("Callsign"); ?></th>
									<th class="fw-bold"><?= __("Band"); ?></th>
									<th class="fw-bold"><?= __("Mode"); ?></th>
									<th class="fw-bold"><?= __("RST"); ?></th>
									<th class="fw-bold serial-col" style="display:none;"><?= __("Nr. S"); ?></th>
									<th class="fw-bold serial-col" style="display:none;"><?= __("Nr. R"); ?></th>
									<th class="fw-bold gridsquare-col" style="display:none;"><?= __("Grid"); ?></th>
									<th class="fw-bold exchange-text-col"><?= __("Exch"); ?></th>
									<?php if (!empty($is_club_station)): ?>
									<th class="fw-bold operator-col"><?= __("Operator"); ?></th>
									<?php endif; ?>
									<th class="fw-bold text-center"></th>
									<th class="fw-bold text-center"></th>
								</tr>
							</thead>
							<tbody id="qso-tbody">
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
