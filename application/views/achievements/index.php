<div class="container mt-2">

	<?php
	$custom_date_format = $this->session->userdata('user_date_format');
	if (!$custom_date_format) {
		$custom_date_format = $this->config->item('qso_date_format');
	}

	if ($trophies === null) {
		?>
		<div class="alert alert-warning" role="alert"><?= __("No station locations are linked to your active logbook. Trophies need at least one linked station location."); ?></div>
		<?php
	} else {
		$total = 0;
		$unlocked_total = 0;
		foreach ($trophies['families'] as $family) {
			foreach ($family['trophies'] as $trophy) {
				$total++;
				if ($trophy['unlocked']) {
					$unlocked_total++;
				}
			}
		}
		?>

		<style>
			.trophy-grid {
				display: flex;
				flex-wrap: wrap;
				gap: .5rem;
			}
			.trophy-card {
				width: 106px;
				padding: .375rem .25rem;
				color: var(--bs-body-color);
				cursor: pointer;
				transition: transform .1s ease-out;
			}
			.trophy-card:hover, .trophy-card:focus-visible {
				transform: translateY(-2px);
			}
			.trophy-card img {
				width: 72px;
				height: 72px;
			}
			.trophy-title {
				font-size: .78rem;
				line-height: 1.2;
				max-width: 98px;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
				white-space: normal;
				overflow: hidden;
			}
			.trophy-sub {
				display: block;
				max-width: 98px;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			.trophy-card .progress {
				height: 4px;
			}
			.trophy-locked img {
				filter: grayscale(1);
				opacity: .35;
			}
		</style>

		<div class="d-flex align-items-baseline justify-content-between flex-wrap">
			<h1 class="h4 mb-0"><?= __("Achievements"); ?></h1>
			<small class="text-muted">
				<?= sprintf(__("You have unlocked %s of %s trophies."), (int) $unlocked_total, (int) $total); ?>
				<?php if (!empty($trophies['cached_at'])) { ?>
					· <?= sprintf(__("Cached data as of %s"), html_escape(date($custom_date_format . ' H:i', (int) $trophies['cached_at']))); ?>
				<?php } ?>
			</small>
		</div>

		<?php foreach ($trophies['families'] as $family) { ?>
			<div class="mt-3">
				<h5 class="mb-1">
					<?= html_escape($family['title']); ?>
					<?php if (!empty($family['subtitle'])) { ?>
						<small class="text-muted fw-normal"><?= html_escape($family['subtitle']); ?></small>
					<?php } ?>
				</h5>
				<div class="trophy-grid">
					<?php foreach ($family['trophies'] as $trophy) {
						$locked = !$trophy['unlocked'];
						$pct = 0;
						if ($trophy['progress_target'] > 0) {
							$pct = (int) min(100, round(100 * $trophy['progress_now'] / $trophy['progress_target']));
						}
						$payload = array(
							'u' => $this->paths->cache_buster('/assets/svg/achievements/' . $trophy['icon']),
							't' => $trophy['title'],
							'ok' => (bool) $trophy['unlocked'],
							'd' => $trophy['unlock_date'] !== null ? date($custom_date_format, strtotime($trophy['unlock_date'])) : null,
							'pct' => $pct,
							'pl' => $trophy['progress_label'],
							'kpi' => $trophy['detail'],
						);
						?>
						<button type="button" class="trophy-card text-center border rounded-3 bg-body <?= $locked ? 'trophy-locked' : ''; ?>"
							data-bs-toggle="modal" data-bs-target="#trophyModal"
							data-trophy="<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8'); ?>"
							title="<?= html_escape($trophy['title']); ?>">
							<img src="<?= $this->paths->cache_buster('/assets/svg/achievements/' . $trophy['icon']); ?>" alt="<?= html_escape($trophy['title']); ?>" loading="lazy" />
							<div class="trophy-title mx-auto"><?= html_escape($trophy['title']); ?></div>
							<?php if (!$locked && $trophy['unlock_date'] !== null) { ?>
								<small class="trophy-sub text-success" style="font-size: .68rem;"><?= html_escape(date($custom_date_format, strtotime($trophy['unlock_date']))); ?></small>
							<?php } elseif (!$locked) { ?>
								<small class="trophy-sub text-success" style="font-size: .68rem;"><?= __("Unlocked"); ?></small>
							<?php } elseif ($trophy['progress_target'] > 0) { ?>
								<div class="progress mt-1 w-100" role="progressbar" aria-valuenow="<?= (int) $pct; ?>" aria-valuemin="0" aria-valuemax="100">
									<div class="progress-bar" style="width: <?= (int) $pct; ?>%;"></div>
								</div>
							<?php } ?>
						</button>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	<?php } ?>

</div>

<div class="modal fade" id="trophyModal" tabindex="-1" aria-labelledby="trophyModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="trophyModalTitle"><?= __("Trophy"); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __("Close"); ?>"></button>
			</div>
			<div class="modal-body">
				<div class="d-flex flex-column flex-md-row align-items-center gap-3">
					<img id="trophyModalIcon" src="" alt="" width="256" height="256" class="flex-shrink-0" />
					<div class="flex-grow-1 w-100">
						<h4 id="trophyModalName" class="mb-1"></h4>
						<p class="mb-2">
							<span id="trophyModalBadge" class="badge"></span>
							<span id="trophyModalDate" class="text-muted ms-2"></span>
						</p>
						<div id="trophyModalProgressWrap" class="mb-2">
							<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
								<div id="trophyModalProgressBar" class="progress-bar" style="width: 0%;"></div>
							</div>
							<small id="trophyModalProgressLabel" class="text-muted"></small>
						</div>
						<table class="table table-sm mb-0">
							<tbody id="trophyModalKpi"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	document.getElementById('trophyModal').addEventListener('show.bs.modal', function (e) {
		var btn = e.relatedTarget;
		if (!btn) return;
		var data;
		try { data = JSON.parse(btn.dataset.trophy); } catch (err) { return; }

		var icon = document.getElementById('trophyModalIcon');
		if (typeof data.u === 'string' && data.u.indexOf('/assets/svg/achievements/') !== -1) {
			icon.src = data.u;
		} else {
			icon.removeAttribute('src');
		}
		icon.alt = data.t || '';
		document.getElementById('trophyModalTitle').textContent = data.t || '';
		document.getElementById('trophyModalName').textContent = data.t || '';

		var badge = document.getElementById('trophyModalBadge');
		badge.textContent = data.ok ? <?= json_encode(__("Unlocked")); ?> : <?= json_encode(__("Locked")); ?>;
		badge.className = 'badge ' + (data.ok ? 'bg-success' : 'bg-secondary');

		document.getElementById('trophyModalDate').textContent = (data.ok && data.d) ? data.d : '';

		var wrap = document.getElementById('trophyModalProgressWrap');
		if (data.pl) {
			wrap.style.display = '';
			document.getElementById('trophyModalProgressBar').style.width = Math.max(0, Math.min(100, data.pct || 0)) + '%';
			document.getElementById('trophyModalProgressLabel').textContent = data.pl;
		} else {
			wrap.style.display = 'none';
		}

		var tbody = document.getElementById('trophyModalKpi');
		tbody.textContent = '';
		(data.kpi || []).forEach(function (pair) {
			var tr = document.createElement('tr');
			var th = document.createElement('th');
			th.scope = 'row';
			th.textContent = pair.label;
			var td = document.createElement('td');
			td.textContent = pair.value;
			tr.append(th, td);
			tbody.append(tr);
		});
	});
</script>
