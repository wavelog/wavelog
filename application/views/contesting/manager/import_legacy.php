<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<div class="row">
		<div class="col-12">
			<?php $this->load->view('layout/messages'); ?>
			<div class="card">
				<div class="card-header">
					<?= __("Historical Contest QSOs") ?>
				</div>
				<div class="card-body">
					<p class="card-text">
						<?= __("The following contest groups were found in your logbook but are not yet linked to a contest session. Select the ones you want to import and click \"Import Selected\".") ?>
					</p>
					<?php if (!empty($all_users)): ?>
						<div class="alert alert-warning">
							<strong><?= __("Admin mode:") ?></strong>
							<?= __("This will import contests for all users of this instance.") ?>
						</div>
					<?php endif; ?>

					<form method="post" action="<?= $form_action ?>" id="import-legacy-form">
						<?php $this->security->get_csrf_token_name(); /* CSRF field */ ?>
						<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

						<div class="table-responsive">
							<table class="table table-sm table-hover table-striped">
								<thead>
									<tr>
										<th><input type="checkbox" id="select-all" checked title="<?= __("Select all") ?>"></th>
										<th><?= __("Contest") ?></th>
										<th><?= __("ADIF ID") ?></th>
										<th><?= __("Year") ?></th>
										<th><?= __("Station") ?></th>
										<th><?= __("First QSO") ?></th>
										<th><?= __("Last QSO") ?></th>
										<th><?= __("#QSO") ?></th>
										<th><?= __("Note") ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($groups as $row):
										$is_other = (empty($row['contest_table_id']) || ($row['contest_table_id'] == 1 && $row['adif_name'] !== 'Other'));
										if (!empty($all_users)) {
											$key = $row['adif_name'] . '|' . $row['station_id'] . '|' . $row['contest_year'] . '|' . $row['owner_user_id'];
										} else {
											$key = $row['adif_name'] . '|' . $row['station_id'] . '|' . $row['contest_year'];
										}
									?>
									<tr>
										<td><input type="checkbox" name="groups[]" value="<?= htmlspecialchars($key) ?>" checked></td>
										<td><?= htmlspecialchars($row['contest_name']) ?></td>
										<td><code><?= htmlspecialchars($row['adif_name']) ?></code></td>
										<td><?= (int)$row['contest_year'] ?></td>
										<td><?= htmlspecialchars($row['station_callsign']) ?></td>
										<td><?= !empty($row['time_start']) ? date('Y-m-d H:i', strtotime($row['time_start'])) : '-' ?></td>
										<td><?= !empty($row['time_end'])   ? date('Y-m-d H:i', strtotime($row['time_end']))   : '-' ?></td>
										<td><?= (int)$row['qso_count'] ?></td>
										<td>
											<?php if ($is_other): ?>
												<span class="badge text-bg-warning"><?= __("Unknown → Other") ?></span>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<div class="mt-3">
							<button type="submit" class="btn btn-primary btn-sm" id="import-btn">
								<i class="fas fa-file-import"></i> <?= __("Import Selected") ?>
							</button>
							<a href="<?= site_url('contesting') ?>" class="btn btn-outline-secondary btn-sm ms-2">
								<?= __("Cancel") ?>
							</a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
document.getElementById('select-all').addEventListener('change', function() {
	document.querySelectorAll('#import-legacy-form input[type="checkbox"][name="groups[]"]')
		.forEach(cb => cb.checked = this.checked);
});

document.getElementById('import-legacy-form').addEventListener('submit', function(e) {
	var checked = document.querySelectorAll('#import-legacy-form input[name="groups[]"]:checked').length;
	if (checked === 0) {
		e.preventDefault();
		alert('<?= __("Please select at least one contest to import.") ?>');
		return;
	}
	if (!confirm('<?= __("Are you sure you want to import the selected contest sessions? This cannot be undone.") ?>')) {
		e.preventDefault();
	}
});
</script>
