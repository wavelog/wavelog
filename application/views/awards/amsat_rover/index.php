<style>
#exportText {
	width: 100%;
	min-height: 200px;
	font-family: monospace;
	font-size: 12px;
}
</style>

<div class="container">

<br>

<h2><?php echo $page_title; ?></h2>
<p class="text-muted">
	<?php echo __("The AMSAT Rover Award is granted to stations who achieve a combined 25 points using satellite grid activations."); ?>
	<a href="https://www.amsat.org/amsat-rover-award/" target="_blank"><?php echo __("Official rules"); ?></a>
	—
	<a href="<?php echo site_url('activated_gridmap'); ?>"><?php echo __("Activated Gridsquare Map"); ?></a>
</p>

<?php if (empty($home_grid)) { ?>
	<div class="alert alert-warning">
		<?php echo __("Please set your home grid square in your station profile to calculate grid activations correctly."); ?>
		<a href="<?php echo site_url('station'); ?>"><?php echo __("Go to Station Profile"); ?></a>
	</div>
<?php } ?>

<!-- Filters Form -->
<form class="form" action="<?php echo site_url('awards/amsat_rover'); ?>" method="post">
	<div class="card mb-4">
		<div class="card-body">
			<h5 class="card-title"><?php echo __("Filters"); ?></h5>
			<div class="row">
				<!-- Confirmation Methods -->
				<div class="col-md-12 mb-3">
					<label class="form-label"><?php echo __("Confirmations"); ?></label>
					<div class="form-check-inline">
						<input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw"
							<?php echo in_array('lotw', $filters['confirmations'] ?? []) ? 'checked="checked"' : ''; ?>>
						<label class="form-check-label" for="lotw"><?php echo __("LoTW"); ?></label>
					</div>
					<div class="form-check-inline">
						<input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl"
							<?php echo in_array('qsl', $filters['confirmations'] ?? []) ? 'checked="checked"' : ''; ?>>
						<label class="form-check-label" for="qsl"><?php echo __("QSL"); ?></label>
					</div>
				</div>

				<!-- Bonus Points Accordion -->
				<div class="col-md-12 mb-3">
					<?php $has_bonus = ($bonus_social ?? false) || ($bonus_photos ?? false) || ($bonus_mm ?? false) || ($bonus_journal ?? false); ?>
					<div class="accordion" id="bonusAccordion">
						<div class="accordion-item">
							<h2 class="accordion-header" id="bonusHeader">
								<button class="accordion-button <?php echo $has_bonus ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#bonusBody" aria-expanded="<?php echo $has_bonus ? 'true' : 'false'; ?>" aria-controls="bonusBody">
									<?php echo __("Bonus Points"); ?>
								</button>
							</h2>
							<div id="bonusBody" class="accordion-collapse collapse <?php echo $has_bonus ? 'show' : ''; ?>" aria-labelledby="bonusHeader">
								<div class="accordion-body">
									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" name="bonus_social" value="1" id="bonus_social"
											<?php echo ($bonus_social ?? false) ? 'checked="checked"' : ''; ?>>
										<label class="form-check-label" for="bonus_social">
											<?php echo __("Social Media Promotion (+5)"); ?>
										</label>
										<small class="form-text text-muted d-block ms-4">
											<?php echo __("Post must be made at least 24 hours before activation and include @amsat tag. Include link to post in your email."); ?>
										</small>
									</div>
									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" name="bonus_photos" value="1" id="bonus_photos"
											<?php echo ($bonus_photos ?? false) ? 'checked="checked"' : ''; ?>>
										<label class="form-check-label" for="bonus_photos">
											<?php echo __("Photo Posting (+5)"); ?>
										</label>
										<small class="form-text text-muted d-block ms-4">
											<?php echo __("Photos posted with @amsat tag. Include link to photo post in your email."); ?>
										</small>
									</div>
									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" name="bonus_mm" value="1" id="bonus_mm"
											<?php echo ($bonus_mm ?? false) ? 'checked="checked"' : ''; ?>>
										<label class="form-check-label" for="bonus_mm">
											<?php echo __("Maritime Mobile (+10)"); ?>
										</label>
										<small class="form-text text-muted d-block ms-4">
											<?php echo __("Operation was conducted while maritime mobile (/MM)."); ?>
										</small>
									</div>
									<div class="form-check mb-2">
										<input class="form-check-input" type="checkbox" name="bonus_journal" value="1" id="bonus_journal"
											<?php echo ($bonus_journal ?? false) ? 'checked="checked"' : ''; ?>>
										<label class="form-check-label" for="bonus_journal">
											<?php echo __("AMSAT Journal Article (+15)"); ?>
										</label>
										<small class="form-text text-muted d-block ms-4">
											<?php echo __("Published article about your rover operation in the AMSAT Journal."); ?>
										</small>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Submit Button -->
				<div class="col-md-12">
					<button id="submit" type="submit" name="submit" class="btn btn-primary">
						<?php echo __("Show Results"); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</form>

<?php if (isset($summary)): ?>
	<!-- Summary Cards -->
	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card">
				<div class="card-body text-center">
					<h5 class="card-title"><?php echo __("Base Points"); ?></h5>
					<h2 class="display-4 text-primary"><?php echo $summary['total_points']; ?></h2>
					<small class="text-muted"><?php echo $summary['grid_count']; ?> <?php echo __("activations"); ?></small>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-body text-center">
					<h5 class="card-title"><?php echo __("Bonus Points"); ?></h5>
					<h2 class="display-4 text-primary" id="bonusPoints">0</h2>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-body text-center">
					<h5 class="card-title"><?php echo __("Total Points"); ?></h5>
					<h2 class="display-4 <?php echo $summary['complete'] ? 'text-success' : 'text-warning'; ?>" id="totalPoints">
						<?php echo $summary['total_points']; ?>
					</h2>
					<small class="text-muted"><?php echo __("Target"); ?>: 25</small>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card">
				<div class="card-body text-center">
					<h5 class="card-title"><?php echo __("Status"); ?></h5>
					<h4 class="<?php echo $summary['complete'] ? 'text-success' : 'text-warning'; ?>" id="statusText">
						<?php echo $summary['complete'] ? __("APPROVED") : __("IN PROGRESS"); ?>
					</h4>
				</div>
			</div>
		</div>
	</div>

	<!-- Progress Bar -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="progress" style="height: 30px;">
				<?php
				$percentage = min(($summary['total_points'] / 25) * 100, 100);
				?>
				<div class="progress-bar bg-<?php echo $summary['complete'] ? 'success' : 'warning'; ?>"
					role="progressbar" style="width: <?php echo $percentage; ?>%">
					<?php echo $summary['total_points']; ?> / 25
				</div>
			</div>
		</div>
	</div>

	<!-- Mode Breakdown -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title"><?php echo __("Activations by Mode"); ?></h5>
					<div class="row text-center">
						<div class="col-md-4">
							<strong><?php echo __("FM"); ?> (1<?php echo __("pt"); ?>):</strong> <?php echo $summary['fm_count']; ?> <?php echo __("activations"); ?>
						</div>
						<div class="col-md-4">
							<strong><?php echo __("Linear"); ?> (2<?php echo __("pt"); ?>):</strong> <?php echo $summary['linear_count']; ?> <?php echo __("activations"); ?>
						</div>
						<div class="col-md-4">
							<strong><?php echo __("Digital"); ?> (3<?php echo __("pt"); ?>):</strong> <?php echo $summary['digital_count']; ?> <?php echo __("activations"); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php if (empty($activations)): ?>
		<!-- No Results Message -->
		<div class="alert alert-info">
			<?php echo __("No confirmed satellite QSOs found matching your filters."); ?>
		</div>
	<?php else: ?>
		<!-- Activations Table -->
		<div class="row mb-4">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title"><?php echo __("Grid Activations"); ?></h5>
						<div class="table-responsive">
							<table class="table table-sm table-striped">
								<thead>
									<tr>
										<th><?php echo __("Grid"); ?></th>
										<th><?php echo __("Mode"); ?></th>
										<th><?php echo __("Category"); ?></th>
										<th><?php echo __("Points"); ?></th>
										<th><?php echo __("Confirmation"); ?></th>
										<th><?php echo __("Satellite"); ?></th>
										<th><?php echo __("Date / UTC"); ?></th>
										<th><?php echo __("Callsign"); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($activations as $act): ?>
									<tr>
										<td><a href="javascript:displayRoverGridQsos('<?php echo $act['my_grid']; ?>')"><strong><?php echo htmlspecialchars($act['my_grid']); ?></strong></a></td>
										<td><?php echo htmlspecialchars($act['mode']); ?></td>
										<td>
											<span class="badge bg-<?php
												echo $act['mode_category'] == 'FM' ? 'primary' :
													($act['mode_category'] == 'Digital' ? 'success' : 'secondary');
											?>">
												<?php echo htmlspecialchars($act['mode_category']); ?>
											</span>
										</td>
										<td><?php echo $act['points']; ?> <?php echo __("pts"); ?></td>
										<td><?php echo htmlspecialchars($act['confirmation']); ?></td>
										<td><?php echo htmlspecialchars($act['satellite'] ?? 'N/A'); ?></td>
										<td><?php echo date('Y-m-d H:i', strtotime($act['date'])) . ' UTC'; ?></td>
										<td><?php echo htmlspecialchars($act['call_worked']); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Export Section -->
		<div class="row mb-4">
			<div class="col-12">
				<div class="card card-body">
					<h5><?php echo __("Export for Application"); ?></h5>
					<p class="text-muted">
						<?php echo __("Generate a copy-paste ready application for email submission to AMSAT."); ?>
					</p>
					<p>
						<strong><?php echo __("Send to"); ?>:</strong>
						<a href="mailto:rover@amsat.org">rover@amsat.org</a>
					</p>
					<div class="mb-3">
						<button type="button" class="btn btn-outline-primary me-2" onclick="generateTextExport()">
							<i class="fas fa-file-alt"></i> <?php echo __("Generate Text for Email"); ?>
						</button>
						<button type="button" class="btn btn-outline-success me-2" onclick="copyToClipboard()">
							<i class="fas fa-copy"></i> <?php echo __("Copy to Clipboard"); ?>
						</button>
						<button type="button" class="btn btn-outline-secondary" onclick="downloadCsv()">
							<i class="fas fa-file-csv"></i> <?php echo __("Download CSV"); ?>
						</button>
					</div>
					<textarea id="exportText" readonly></textarea>
				</div>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>

</div>

<script src="<?php echo base_url(); ?>assets/js/sections/amsat_award.js"></script>
<script>
// Bonus points calculation
function calculateBonus() {
	let bonus = 0;
	if (document.getElementById('bonus_social')?.checked) bonus += 5;
	if (document.getElementById('bonus_photos')?.checked) bonus += 5;
	if (document.getElementById('bonus_mm')?.checked) bonus += 10;
	if (document.getElementById('bonus_journal')?.checked) bonus += 15;
	return bonus;
}

document.addEventListener('DOMContentLoaded', function() {
	const bonusCheckboxes = document.querySelectorAll('[id^="bonus_"]');
	bonusCheckboxes.forEach(function(checkbox) {
		checkbox.addEventListener('change', updateTotals);
	});
	updateTotals();
});

function updateTotals() {
	const basePoints = parseInt(<?php echo $summary['total_points'] ?? 0; ?>) || 0;
	const bonus = calculateBonus();
	const total = basePoints + bonus;

	const bonusEl = document.getElementById('bonusPoints');
	const totalEl = document.getElementById('totalPoints');
	const statusEl = document.getElementById('statusText');

	if (bonusEl) bonusEl.textContent = bonus;
	if (totalEl) {
		totalEl.textContent = total;
		totalEl.className = 'display-4 ' + (total >= 25 ? 'text-success' : 'text-warning');
	}
	if (statusEl) {
		statusEl.textContent = total >= 25 ? '<?php echo __("APPROVED"); ?>' : '<?php echo __("IN PROGRESS"); ?>';
		statusEl.className = 'h4 ' + (total >= 25 ? 'text-success' : 'text-warning');
	}
}

function generateTextExport() {
	const form = document.querySelector('form');
	const formData = new FormData(form);

	fetch('<?php echo site_url('awards/amsat_rover_export_text'); ?>', {
		method: 'POST',
		body: formData
	})
	.then(response => response.text())
	.then(data => {
		document.getElementById('exportText').value = data;
	})
	.catch(error => {
		console.error('Error:', error);
	});
}

function copyToClipboard() {
	const textarea = document.getElementById('exportText');
	if (!textarea.value) {
		alert('<?php echo __("Please generate the text first."); ?>');
		return;
	}
	textarea.select();
	document.execCommand('copy');
	alert('<?php echo __("Copied to clipboard!"); ?>');
}

function downloadCsv() {
	const form = document.querySelector('form');
	const formData = new FormData(form);

	fetch('<?php echo site_url('awards/amsat_rover_export_csv'); ?>', {
		method: 'POST',
		body: formData
	})
	.then(response => response.blob())
	.then(blob => {
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'amsat_rover_activations.csv';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		window.URL.revokeObjectURL(url);
	})
	.catch(error => {
		console.error('Error:', error);
	});
}
</script>
