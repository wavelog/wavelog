<div class="container">

	<br>

	<h2><?= __("QSL Cards"); ?></h2>

	<?php $userdata_dir = $this->config->item('userdata');
	if (isset($userdata_dir)) { ?>
		<div class="alert alert-info" role="alert">
			<?= sprintf(__("You are using %s of disk space to store QSL Card assets"), $storage_used ); ?>
		</div>
	<?php } ?>

	<!-- View toggle buttons -->
	<div class="mb-3">
		<div class="btn-group" role="group">
			<button type="button" class="btn btn-primary" id="listViewBtn"><?= __("List View"); ?></button>
			<button type="button" class="btn btn-outline-primary" id="galleryViewBtn"><?= __("Gallery View"); ?></button>
		</div>
	</div>

	<?php
	if ($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}
	?>

	<!-- List View -->
	<div id="listView">
		<?php
		if (is_array($qslarray->result())) {
			echo '<table style="width:100%" class="qsltable table table-sm table-bordered table-hover table-striped table-condensed">
        <thead>
        <tr>
        <th style=\'text-align: center\'>'.__("Callsign").'</th>
        <th style=\'text-align: center\'>'.__("Mode").'</th>
        <th style=\'text-align: center\'>'.__("Date").'</th>
        <th style=\'text-align: center\'>'.__("Time").'</th>
        <th style=\'text-align: center\'>'.__("Band").'</th>
        <th style=\'text-align: center\'>'.__("QSL Date").'</th>
        <th style=\'text-align: center\'></th>
        <th style=\'text-align: center\'></th>
        <th style=\'text-align: center\'></th>
        </tr>
        </thead><tbody>';

			foreach ($qslarray->result() as $qsl) {
				echo '<tr>';
				echo '<td style=\'text-align: center\'>'.str_replace("0", "&Oslash;", $qsl->COL_CALL).'</td>';
				echo '<td style=\'text-align: center\'>';
				echo $qsl->COL_SUBMODE == null ? $qsl->COL_MODE : $qsl->COL_SUBMODE;
				echo '</td>';
				echo '<td style=\'text-align: center\'>';
				$timestamp = strtotime($qsl->COL_TIME_ON);
				echo date($custom_date_format, $timestamp);
				echo '</td>';
				echo '<td style=\'text-align: center\'>';
				$timestamp = strtotime($qsl->COL_TIME_ON);
				echo date('H:i', $timestamp);
				echo '</td>';
				echo '<td style=\'text-align: center\'>';
				if ($qsl->COL_SAT_NAME != null) {
					echo $qsl->COL_SAT_NAME;
				} else {
					echo strtolower($qsl->COL_BAND);
				};
				echo '</td>';
				echo '<td style=\'text-align: center\'>';
				$timestamp = strtotime($qsl->COL_QSLRDATE ?? '');
				echo date($custom_date_format, $timestamp);
				echo '</td>';
				echo '<td id="'.$qsl->id.'" style=\'text-align: center\'><button onclick="deleteQsl(\''.$qsl->id.'\')" class="btn btn-sm btn-danger">' . __("Delete") . '</button></td>';
				echo '<td style=\'text-align: center\'><button onclick="viewQsl(\''.$qsl->filename.'\', \''.$qsl->COL_CALL.'\')" class="btn btn-sm btn-success">' . __("View") . '</button></td>';
				echo '<td style=\'text-align: center\'><button onclick="addQsosToQsl(\''.$qsl->filename.'\')" class="btn btn-sm btn-success">' . __("Add Qsos") . '</button></td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}
		?>
	</div>

	<!-- Gallery View -->
	<div id="galleryView" class="qsl-gallery" style="display: none;">
		<div class="waterfall-grid">
			<?php if (is_array($qslarray->result())): ?>
				<?php foreach ($qslarray->result() as $qsl): ?>
					<?php
					$qslDate = null;
					$timestamp = strtotime($qsl->COL_TIME_ON);
					if (!empty($qsl->COL_QSLRDATE)) {
						$qslDate = date($custom_date_format, strtotime($qsl->COL_QSLRDATE));
					}
					$band = ($qsl->COL_SAT_NAME != null) ? $qsl->COL_SAT_NAME : strtolower($qsl->COL_BAND);
					$mode = $qsl->COL_SUBMODE == null ? $qsl->COL_MODE : $qsl->COL_SUBMODE;

					// Extract the user ID and filename for constructing the correct path
					$parts = explode('/', $qsl->filename);
					$filename = end($parts);
					$userdata_dir = $this->config->item('userdata');
					$user_id = $this->session->userdata('user_id');

					// Build correct image path: userdata/[user_id]/qsl_card/[filename]
					$image_path = $this->paths->getPathQsl() . '/' . $filename;
					?>
					<div class="waterfall-item">
						<div class="card h-100">
							<div class="card-img-container">
								<img src="<?= $image_path ?>" class="card-img-top qsl-card-img" alt="QSL Card from <?= str_replace("0", "&Oslash;", $qsl->COL_CALL) ?>" onclick="viewQsl('<?= $qsl->filename ?>', '<?= str_replace("0", "&Oslash;", $qsl->COL_CALL) ?>')">
							</div>
							<div class="card-body">
								<h5 class="card-title"><?= str_replace("0", "&Oslash;", $qsl->COL_CALL) ?></h5>
								<p class="card-text">
									<?= $mode ?> | <?= $band ?><br>
									<?= date($custom_date_format, $timestamp) ?> <?= date('H:i', $timestamp) ?><br>
									<?= $qslDate == '' ? '' : __("QSL Date") . ': ' . $qslDate ?>
								</p>
							</div>
							<div class="card-footer text-center">
								<div class="btn-group btn-group-sm" role="group">
									<button onclick="deleteQsl('<?= $qsl->id ?>')" class="me-1 btn btn-danger"><?= __("Delete") ?></button>
									<button onclick="addQsosToQsl('<?= $qsl->filename ?>')" class="me-1 btn btn-success"><?= __("Add Qsos") ?></button>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		document.getElementById('listViewBtn').onclick = function() {
			document.getElementById('listView').style.display = 'block';
			document.getElementById('galleryView').style.display = 'none';
			this.classList.remove('btn-outline-primary');
			this.classList.add('btn-primary');
			document.getElementById('galleryViewBtn').classList.remove('btn-primary');
			document.getElementById('galleryViewBtn').classList.add('btn-outline-primary');
		};

		document.getElementById('galleryViewBtn').onclick = function() {
			document.getElementById('listView').style.display = 'none';
			document.getElementById('galleryView').style.display = 'block';
			this.classList.remove('btn-outline-primary');
			this.classList.add('btn-primary');
			document.getElementById('listViewBtn').classList.remove('btn-primary');
			document.getElementById('listViewBtn').classList.add('btn-outline-primary');
		};
	});
</script>
