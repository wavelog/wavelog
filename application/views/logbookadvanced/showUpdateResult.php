<?php

switch ($type) {
	case 'state':
		showStateUpdateResult($result, $country);
		break;
	case 'continent':
		showContinentUpdateResult($result);
		break;
	case 'distance':
		showDistanceUpdateResult($result);
		break;
	case 'grids':
		showGridUpdateResult($result);
		break;
	default:
		// Invalid type
		break;
}

function showStateUpdateResult($result, $country) {
	echo '<h5>' . __("Results for state update:") . '</h5>';
	if ($result['count'] == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for state/province in") . ' ' . $country . ': ' . $result['count'] . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for state/province in") . ' ' . $country . ': ' . $result['count'] . '</div>';
	}

	if ($result) {
		$details = [];
		foreach ($result as $r) {
			if (is_array($r)) {
				$details[] = $r;
			}
		}

		if (!empty($details)) { ?>
			<?= __("These QSOs could not be updated:"); ?>
			<div class="table-responsive mt-3">
			<table class="table table-sm table-striped table-hover">
				<thead>
					<tr>
						<th> <?php echo __("Callsign"); ?> </th>
						<th> <?php echo __("Gridsquare"); ?> </th>
						<th> <?php echo __("Station location"); ?> </th>
						<th> <?php echo __("Reason"); ?> </th>
					</tr>
				</thead>
				<tbody>

				<?php foreach ($details as $r) { ?>
					<tr>
					<td><a id="edit_qso" href="javascript:displayQso(<?php echo $r['id']; ?>)"><?php echo htmlspecialchars($r['callsign']); ?></a></td>
					<td> <?php echo htmlspecialchars($r['gridsquare']); ?> </td>
					<td> <?php echo htmlspecialchars($r['station_profile_name']); ?> </td>
					<td> <?php echo htmlspecialchars($r['reason']); ?> </td>
					</tr>
				<?php } ?>

				</tbody>
			</table>
			</div>

		<?php }
	}
}

function showContinentUpdateResult($result) {
	echo '<h5>' . __("Results for continent update:") . '</h5>';
	echo '<div class="alert alert-' . ($result == 0 ? 'danger' : 'success') . '" role="alert">' . sprintf(__("The number of QSOs updated for continent is") . ': %d', $result) . '</div>';
}

function showDistanceUpdateResult($result) {
	echo '<h5>' . __("Results for distance update:") . '</h5>';
	echo '<div class="alert alert-' . ($result == 0 ? 'danger' : 'success') . '" role="alert">' . sprintf(__("The number of QSOs updated for distance is") . ': %d', $result) . '</div>';
}

function showGridUpdateResult($result) {
	echo '<h5>' . __("Results for gridsquare update:") . '</h5>';
	echo '<div class="alert alert-' . ($result == 0 ? 'danger' : 'success') . '" role="alert">' . sprintf(__("The number of QSOs updated for gridsquare is") . ': %d', $result) . '</div>';
}
