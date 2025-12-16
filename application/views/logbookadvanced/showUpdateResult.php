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

		if (!empty($details)) {
			echo '<div class="table-responsive mt-3">';
			echo '<table class="table table-striped table-hover">';
			echo '<thead class="table-dark">';
			echo '<tr>';
			echo '<th>' . __("Callsign") . '</th>';
			echo '<th>' . __("Reason") . '</th>';
			echo '<th>' . __("Station location") . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ($details as $r) {
				echo '<tr>';
				echo '<td><a id="edit_qso" href="javascript:displayQso(' . $r['id'] . ')">' . htmlspecialchars($r['callsign']) . '</a></td>';
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
			echo '<th>' . __("Callsign") . '</th>';
			echo '<th>' . __("Reason") . '</th>';
			echo '<th>' . __("Station location") . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ($details as $r) {
				echo '<tr>';
				echo '<td><a id="edit_qso" href="javascript:displayQso(' . $r['id'] . ')">' . htmlspecialchars($r['callsign']) . '</a></td>';
				echo '<td>' . htmlspecialchars($r['reason']) . '</td>';
				echo '<td>' . htmlspecialchars($r['station_profile_name']) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '</div>';
		}
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
