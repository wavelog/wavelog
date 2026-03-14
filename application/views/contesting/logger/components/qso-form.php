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

<div class="window-component" data-component="<?php echo $config['component_name']; ?>" data-config="<?php echo htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8'); ?>">
	<div class="window-header">
		<div class="window-title fw-semibold"><?php echo $config['title']; ?></div>
		<div class="window-controls">
			<button class="window-btn close" data-action="close">x</button>
		</div>
	</div>

	<div class="window-body" style="display: flex; flex-direction: column; overflow: hidden;">
		<div class="qso-form-container" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
			<!-- QSO Entry Form -->
			<div class="card bg-dark border-secondary mb-2" style="flex-shrink: 0;">
				<div class="card-body p-2">
					<div id="qso-form" class="qso-form">
						<div class="row g-2 align-items-end">
							<div class="col-12 col-lg-3">
								<label for="qso-callsign" class="form-label fw-bold mb-1"><?= __("Callsign"); ?></label>
								<input type="text" id="qso-callsign" name="callsign" class="form-control fw-bold" autocomplete="off" style="letter-spacing: 2px;">
								<input type="hidden" id="qso-dxcc" name="dxcc_id" value="" readonly>
								<input type="hidden" id="qso-dxcc-adif" name="dxcc_adif" value="" readonly>
								<input type="hidden" id="qso-dxcc-cont" name="dxcc_cont" value="" readonly>
								<input type="hidden" id="qso-dxcc-entity" name="dxcc_entity" value="" readonly>
								<input type="hidden" id="qso-dxcc-cqz" name="dxcc_cqz" value="" readonly>
								<input type="hidden" id="qso-dxcc-lat" name="dxcc_lat" value="" readonly>
								<input type="hidden" id="qso-dxcc-long" name="dxcc_long" value="" readonly>
								<input type="hidden" id="qso-dxcc-start" name="dxcc_start" value="" readonly>
								<input type="hidden" id="qso-dxcc-end" name="dxcc_end" value="" readonly>
							</div>
							<div class="col-6 col-lg-1">
								<label for="qso-rst-sent" class="form-label fw-bold mb-1"><?= __("RSTS"); ?></label>
								<input type="text" id="qso-rst-sent" name="rst_sent" class="form-control text-center fw-bold" value="59" placeholder="59" maxlength="3">
							</div>
							<div class="col-6 col-lg-1">
								<label for="qso-rst-received" class="form-label fw-bold mb-1"><?= __("RSTR"); ?></label>
								<input type="text" id="qso-rst-received" name="rst_received" class="form-control text-center fw-bold" value="59" placeholder="59" maxlength="3">
							</div>
							<div class="col-12 col-lg-2">
								<label for="qso-exchange-sent" class="form-label fw-bold mb-1"><?= __("Exchange Sent"); ?></label>
								<input type="text" id="qso-exchange-sent" name="exchange_sent" class="form-control" placeholder="">
							</div>
							<div class="col-12 col-lg-2">
								<label for="qso-exchange-received" class="form-label fw-bold mb-1"><?= __("Exchange Rcvd"); ?></label>
								<input type="text" id="qso-exchange-received" name="exchange_received" class="form-control" placeholder="">
							</div>
							<div class="col-12 col-lg-3">
								<button type="button" id="log-qso-btn" onclick="logQso()" class="btn btn-success w-100 fw-bold">
									<i class="fas fa-check-circle me-2"></i><?= __("LOG"); ?>
								</button>
							</div>
							<div id="qso-dxcc-info" class="form-text text-muted small mb-1 mt-1" aria-live="polite"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- QSO List -->
			<div class="card bg-dark border-secondary" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
				<div class="card-header bg-secondary d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
					<h6 class="mb-0 fw-bold"><?= __("Recent QSOs"); ?></h6>
					<span class="badge bg-success" id="qso-count-badge">0</span>
				</div>
				<div class="card-body p-0" style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
					<div class="qso-table-scroll table-responsive" style="flex: 1; overflow-y: auto;">
						<table class="table table-dark table-hover mb-0 qso-table" id="qso-table">
							<thead class="table-secondary sticky-top">
								<tr>
									<th class="fw-bold"><?= __("Time"); ?></th>
									<th class="fw-bold"><?= __("Callsign"); ?></th>
									<th class="fw-bold"><?= __("Band"); ?></th>
									<th class="fw-bold"><?= __("Mode"); ?></th>
									<th class="fw-bold"><?= __("RST In"); ?></th>
									<th class="fw-bold"><?= __("Exchange In"); ?></th>
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