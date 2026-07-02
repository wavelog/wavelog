<?php if ((($solar_bandconditions ?? '') != '') && (($solar_solardata ?? '') != '')){ ?>
		<div class="card mb-3" id="solar-card" title="<?= __("Right-click for options"); ?>">
			<div class="card-header py-2">
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<h6 class="mb-0"><i class="fas fa-sun"></i> <?= __("Solar Data & Propagation"); ?></h6>
						<small class="text-muted"><?= sprintf(__("Last update at %s."), $solar_solardata['updated']); ?></small>
					</div>
					<a class="ms-2 text-body fas fa-info-circle float-end"
						data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
						href="https://www.hamqsl.com/" target="_blank"
						title="<?= __("Data provided by HAMqsl."); ?>" style="cursor: pointer;">
					</a>
				</div>
			</div>
			<div class="card-body p-2">
				<?php
				function bandcondition_badge($condition) {
					$classes = ['Good' => 'text-bg-success', 'Fair' => 'text-bg-warning', 'Poor' => 'text-bg-danger', 'n/a' => 'text-bg-secondary'];
					$class = $classes[$condition] ?? 'text-bg-secondary';
					$label = $condition ?: 'n/a';
					return '<span class="badge rounded-pill ' . $class . '">' . $label . '</span>';
				}
				?>
				<table class="table table-borderless table-sm mb-0 small text-center align-middle">
					<thead>
						<tr>
							<th class="text-start border-0 pb-1" style="width:16%"></th>
							<th class="border-0 pb-1" style="width:21%">80-40m</th>
							<th class="border-0 pb-1" style="width:21%">30-20m</th>
							<th class="border-0 pb-1" style="width:21%">17-15m</th>
							<th class="border-0 pb-1" style="width:21%">12-10m</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-start text-muted"><?= __("Day"); ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['80m-40m']['day'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['30m-20m']['day'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['17m-15m']['day'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['12m-10m']['day'] ?: 'n/a') ?></td>
						</tr>
						<tr>
							<td class="text-start text-muted"><?= __("Night"); ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['80m-40m']['night'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['30m-20m']['night'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['17m-15m']['night'] ?: 'n/a') ?></td>
							<td><?= bandcondition_badge($solar_bandconditions['12m-10m']['night'] ?: 'n/a') ?></td>
						</tr>
					</tbody>
				</table>
				<hr class="my-2">
				<div class="row g-0 text-center small">
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("Solar Flux Index") ?>">
						<small class="text-muted d-block">SFI</small>
						<strong><?= $solar_solardata['solarflux'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("Sunspot Number") ?>">
						<small class="text-muted d-block">SSN</small>
						<strong><?= $solar_solardata['sunspots'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("K-index: Planetary geomagnetic activity (0-9)") ?>">
						<small class="text-muted d-block">Kp</small>
						<strong><?= $solar_solardata['kindex'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("A-index: Daily geomagnetic activity index") ?>">
						<small class="text-muted d-block">A</small>
						<strong><?= $solar_solardata['aindex'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("Solar Wind speed (km/s)") ?>">
						<small class="text-muted d-block">SW</small>
						<strong><?= $solar_solardata['solarwind'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("Signal Noise ratio") ?>">
						<small class="text-muted d-block">SS</small>
						<strong><?= $solar_solardata['signalnoise'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("X-Ray solar flux level") ?>">
						<small class="text-muted d-block">X</small>
						<strong><?= $solar_solardata['xray'] ?></strong>
					</div>
					<div class="col-3 py-1" data-bs-toggle="tooltip" title="<?= __("Aurora activity level (Kp borealis)") ?>">
						<small class="text-muted d-block">Aurora</small>
						<strong><?= $solar_solardata['aurora'] ?></strong>
					</div>
				</div>
			</div>
		</div>
		<?php $this->load->view('dashboard/_options_menu', ['menu_id'=>'solarOptsMenu','target_id'=>'solar-card']); ?>
		<?php } ?>
