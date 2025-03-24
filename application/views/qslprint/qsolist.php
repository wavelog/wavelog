<?php
if ($qsos->result() != NULL) {
	echo '<table style="width:100%" class="qsolist table table-sm table-bordered table-hover table-striped table-condensed">
	<thead>
	<tr>
	<th style=\'text-align: center\'>'.__("Callsign").'</th>
	<th style=\'text-align: center\'>' . __("Date") . '</th>
	<th style=\'text-align: center\'>'. __("Time") .'</th>
	<th style=\'text-align: center\'>' . __("Mode") . '</th>
	<th style=\'text-align: center\'>' . __("Band") . '</th>
	<th style=\'text-align: center\'>' . __("RST (S)") . '</th>
	<th style=\'text-align: center\'>' . __("RST (R)") . '</th>
	<th style=\'text-align: center\'>' . __("Station") . '</th>
	<th style=\'text-align: center\'>' . __("Profile name") . '</th>
	<th style=\'text-align: center\'>' . __("QSL") . ' ' . __("Via") . '</th>
	<th style=\'text-align: center\'>' . __("Send Method") . '</th>
	<th style=\'text-align: center\'>' . __("QSL") . '</th>';
	if ($this->session->userdata('user_eqsl_name') != "") {
		echo '<th style=\'text-align: center\'>' . __("eQSL") . '</th>';
	}
	if($this->session->userdata('user_lotw_name') != "") {
		echo '<th style=\'text-align: center\'>' . __("LoTW") . '</th>';
	}
	echo '<th style=\'text-align: center\'></th>
	</tr>
	</thead><tbody>';

	// Get Date format
	if($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}

	foreach ($qsos->result() as $qsl) {
		echo '<tr id ="qsolist_'.$qsl->COL_PRIMARY_KEY.'">';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_CALL . '</td>';
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qsl->COL_TIME_ON); echo date($custom_date_format, $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qsl->COL_TIME_ON); echo date('H:i', $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'>'; echo $qsl->COL_SUBMODE==null?$qsl->COL_MODE:$qsl->COL_SUBMODE; echo '</td>';
		echo '<td style=\'text-align: center\'>'; if($qsl->COL_SAT_NAME != null) { echo $qsl->COL_SAT_NAME; } else { echo strtolower($qsl->COL_BAND ?? ""); }; echo '</td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_RST_SENT . '</td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_RST_RCVD . '</td>';
		echo '<td style=\'text-align: center\'><span class="badge text-bg-light">' . $qsl->station_callsign . '</span></td>';
		echo '<td style=\'text-align: center\'>' . $qsl->station_profile_name . '</span></td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_QSL_VIA . '</td>';
		echo '<td style=\'text-align: center\'>'; echo_qsl_sent_via($qsl->COL_QSL_SENT_VIA); echo '</td>';
		echo '<td style=\'text-align: center\' class="qsl">';
		echo '<span ';
		if ($qsl->COL_QSL_SENT != "N") {
			if ($qsl->COL_QSLSDATE != null) {
				$timestamp = ' '.date($custom_date_format, strtotime($qsl->COL_QSLSDATE));
			} else {
				$timestamp = '';
			}
			switch ($qsl->COL_QSL_SENT) {
			case "Y":
				echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Sent").$timestamp;
				break;
			case "Q":
				echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued").$timestamp;
				break;
			case "R":
				echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested").$timestamp;
				break;
			case "I":
				echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)").$timestamp;
				break;
			default:
				echo "class=\"qsl-red";
				break;
			}
		} else { echo "class=\"qsl-red"; }
		if ($qsl->COL_QSL_SENT_VIA != "") {
			switch ($qsl->COL_QSL_SENT_VIA) {
			case "B":
				echo " (".__("Bureau").")";
				break;
			case "D":
				echo " (".__("Direct").")";
				break;
			case "M":
				echo " (".__("Via").": ".($qsl->COL_QSL_VIA!="" ? $qsl->COL_QSL_VIA:"n/a").")";
				break;
			case "E":
				echo " (".__("Electronic").")";
				break;
			}
		}
		echo '">&#9650;</span>';
		echo '<span ';
		if ($qsl->COL_QSL_RCVD != "N") {
			if ($qsl->COL_QSLRDATE != null) {
				$timestamp = ' '.date($custom_date_format, strtotime($qsl->COL_QSLRDATE));
			} else {
				$timestamp = '';
			}
			switch ($qsl->COL_QSL_RCVD) {
			case "Y":
				echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Received").$timestamp;
				break;
			case "Q":
				echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued").$timestamp;
				break;
			case "R":
				echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested").$timestamp;
				break;
			case "I":
				echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)").$timestamp;
				break;
			default:
				echo "class=\"qsl-red";
				break;
			}
		} else { echo "class=\"qsl-red"; }
		if ($qsl->COL_QSL_RCVD_VIA != "") {
			switch ($qsl->COL_QSL_RCVD_VIA) {
			case "B":
				echo " (".__("Bureau").")";
				break;
			case "D":
				echo " (".__("Direct").")";
				break;
			case "M":
				echo " (".__("Manager").")";
				break;
			case "E":
				echo " (".__("Electronic").")";
				break;
			}
		}
		echo '">&#9660;</span>';

		if ($this->session->userdata('user_eqsl_name') != ""){
			echo '<td style=\'text-align: center\' class="eqsl">';
			echo '<span ';
			if ($qsl->COL_EQSL_QSL_SENT == "Y") {
				echo "title=\"".__("eQSL")." ".__("Sent");
				if ($qsl->COL_EQSL_QSLSDATE != null) {
					$timestamp = strtotime($qsl->COL_EQSL_QSLSDATE);
					echo " ".($timestamp != '' ? date($custom_date_format, $timestamp) : '');
				}
				echo "\" data-bs-toggle=\"tooltip\"";
			}
			echo ' class="eqsl-';
			echo ($qsl->COL_EQSL_QSL_SENT=='Y')?'green':'red';
			echo '">&#9650;</span>';

			echo '<span ';
			if ($qsl->COL_EQSL_QSL_RCVD == "Y") {
				echo "title=\"".__("eQSL")." ".__("Received");
				if ($qsl->COL_EQSL_QSLRDATE != null) {
					$timestamp = strtotime($qsl->COL_EQSL_QSLRDATE);
					echo " ".($timestamp != '' ? date($custom_date_format, $timestamp) : '');
				}
				echo "\" data-bs-toggle=\"tooltip\"";
			}
			echo ' class="eqsl-';
			echo ($qsl->COL_EQSL_QSL_RCVD=='Y')?'green':'red';
			echo '">&#9660;</span>';
			echo '</td>';
		}
		if($this->session->userdata('user_lotw_name') != "") {
			echo '<td style=\'text-align: center\' class="lotw">';
			echo '<span ';
			if ($qsl->COL_LOTW_QSL_SENT == "Y") {
				echo "title=\"".__("LoTW")." ".__("Sent");
				if ($qsl->COL_LOTW_QSLSDATE != null) {
					$timestamp = strtotime($qsl->COL_LOTW_QSLSDATE);
					echo " ".($timestamp != '' ? date($custom_date_format, $timestamp) : '');
				}
				echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-green\"";
			} elseif ($qsl->COL_LOTW_QSL_SENT == "I") {
				echo "class=\"lotw-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)")."\"";
			} else {
				echo " class=\"lotw-red\"";
			}
			echo '>&#9650;</span>';

			echo '<span ';
			if ($qsl->COL_LOTW_QSL_RCVD == "Y") {
				echo "title=\"".__("LoTW")." ".__("Received");
				if ($qsl->COL_LOTW_QSLRDATE) {
					$timestamp = strtotime($qsl->COL_LOTW_QSLRDATE);
					echo " ".($timestamp != '' ? date($custom_date_format, $timestamp) : '');
				}
				echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-green\"";
			} elseif ($qsl->COL_LOTW_QSL_RCVD == "I") {
				echo "class=\"lotw-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)")."\"";
			} else {
				echo " class=\"lotw-red\"";
			}
			echo '>&#9660;</span>';
			echo '</td>';
		}
		echo '<td id="'.$qsl->COL_PRIMARY_KEY.'" style=\'text-align: center\'><button onclick="addQsoToPrintQueue(\''.$qsl->COL_PRIMARY_KEY.'\')" class="btn btn-sm btn-success">' . __("Add to print queue") . '</button></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	?>

	<?php
} else {
	echo '<div class="alert alert-danger">' . __("No additional QSOs were found. That means they are probably already in the queue.") . '</div>';
}

function echo_qsl_sent_via($method) {
	switch($method) {
		case 'B': echo __("Bureau"); break;
		case 'D': echo __("Direct"); break;
		case 'E': echo __("Electronic"); break;
	}
}
?>
