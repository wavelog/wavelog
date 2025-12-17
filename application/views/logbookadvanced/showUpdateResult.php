<?php

switch ($type) {
	case 'dxcc':
		showDxccUpdateResult($result, $all);
		break;
	case 'state':
		showStateUpdateResult($result, $country);
		break;
	case 'continent':
		showContinentUpdateResult($result);
		break;
	case 'distance':
		showDistanceUpdateResult($result);
		break;
	case 'cqzones':
		showCqzoneUpdateResult($result);
		break;
	case 'ituzones':
		showItuzoneUpdateResult($result);
		break;
	default:
		// Invalid type
		break;
}

function showDxccUpdateResult($result, $all) {
	if ($result['count'] == 0) {
		if ($all == 'false') {
			echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for missing DXCC IDs was") .' ' . $result['count'] . '</div>';
		} else {
			echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs re-checked for DXCC was") .' ' . $result['count'] . '</div>';
		}
	} else {
		if ($all == 'false') {
			echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for missing DXCC IDs was") .' ' . $result['count'] . '</div>';
		} else {
			echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs re-checked for DXCC was") .' ' . $result['count'] . '</div>';
		}
	}

	if ($result) {
		echo __("These QSOs could not be updated:");
		$details = [];
		foreach ($result as $r) {
			if (is_array($r)) {
				$details[] = $r;
			}
		}

		if (!empty($details)) { ?>
			<div class="table-responsive mt-3">
			<table class="table table-striped table-hover">
			<thead class="table-dark">
			<tr>
			<th> <?php echo __("Callsign"); ?> </th>
			<th> <?php echo __("Reason"); ?> </th>
			<th> <?php echo __("Station location"); ?> </th>
			</tr>
			</thead>
			<tbody>

			<?php foreach ($details as $r) { ?>
				<tr>
				<td><a id="edit_qso" href="javascript:displayQso(<?php echo $r['id']; ?>)"><?php echo htmlspecialchars($r['callsign']); ?></a></td>
				<td> <?php echo htmlspecialchars($r['reason']); ?> </td>
				<td> <?php echo htmlspecialchars($r['location']); ?> </td>
				</tr>
			<?php } ?>

			</tbody>
			</table>
			</div>
		<?php }
	}
}

function showStateUpdateResult($result, $country) {
	if ($result['count'] == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for state/province in") . ' ' . $country . ' : ' . $result['count'] . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for state/province in") . ' ' . $country . ' : ' . $result['count'] . '</div>';
	}

	if ($result) {
		echo __("These QSOs could not be updated:");
		$details = [];
		foreach ($result as $r) {
			if (is_array($r)) {
				$details[] = $r;
			}
		}

		if (!empty($details)) { ?>
			<div class="table-responsive mt-3">
			<table class="table table-striped table-hover">
			<thead class="table-dark">
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
	if ($result == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for continent is") . ' : ' . $result . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for continent is") . ' : ' . $result . '</div>';
	}
}

function showDistanceUpdateResult($result) {
	if ($result == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for distance is") . ' : ' . $result . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for distance is") . ' : ' . $result . '</div>';
	}
}

function showCqzoneUpdateResult($result) {
	if ($result == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for CQ zone is") . ' : ' . $result . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for CQ zone is") . ' : ' . $result . '</div>';
	}
}

function showItuzoneUpdateResult($result) {
	if ($result == 0) {
		echo '<div class="alert alert-danger" role="alert">' . __("The number of QSOs updated for ITU zone is") . ' : ' . $result . '</div>';
	} else {
		echo '<div class="alert alert-success" role="alert">' . __("The number of QSOs updated for ITU zone is") . ' : ' . $result . '</div>';
	}
}
