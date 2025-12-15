<?php

switch ($type) {
	case 'dxcc':
		showDxccUpdateResult($result, $all);
		break;
	case 'state':
		showStateUpdateResult($result, $country);
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

		if (!empty($details)) {
			echo '<div class="table-responsive mt-3">';
			echo '<table class="table table-striped table-hover">';
			echo '<thead class="table-dark">';
			echo '<tr>';
			echo '<th>Callsign</th>';
			echo '<th>Reason</th>';
			echo '<th>Station location</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ($details as $r) {
				echo '<tr>';
				echo '<td>' . htmlspecialchars($r['callsign']) . '</td>';
				echo '<td>' . htmlspecialchars($r['reason']) . '</td>';
				echo '<td>' . htmlspecialchars($r['location']) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';
		}
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

		if (!empty($details)) {
			echo '<div class="table-responsive mt-3">';
			echo '<table class="table table-striped table-hover">';
			echo '<thead class="table-dark">';
			echo '<tr>';
			echo '<th>Callsign</th>';
			echo '<th>Reason</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ($details as $r) {
				echo '<tr>';
				echo '<td>' . htmlspecialchars($r['callsign']) . '</td>';
				echo '<td>' . htmlspecialchars($r['reason']) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';
		}
	}
}
