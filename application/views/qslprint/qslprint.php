<?php

function echo_qsl_sent_via($method) {
	switch($method) {
		case 'B': echo __("Bureau"); break;
		case 'D': echo __("Direct"); break;
		case 'E': echo __("Electronic"); break;
	}
}

if (empty($station_id)) {
	$station_id = 'all';
}

if ($qsos->result() != NULL) {
        echo '<div style="padding-top: 10px; margin-top: 0px;" class="container logbook mb-4">';
	echo '<table style="width:100%" class="table table-sm table-bordered table-hover table-striped table-condensed qslprint" id="qslprint_table">
<thead>
<tr>
<th style=\'text-align: center\'><div class="form-check" style="margin-top: -1.5em"><input class="form-check-input" type="checkbox" id="checkBoxAll" /></div></th>
<th style=\'text-align: center\'>'.__("Callsign").'</th>
<th style=\'text-align: center\'>' . __("Date") . '</th>
<th style=\'text-align: center\'>'. __("Time") .'</th>
<th style=\'text-align: center\'>' . __("Mode") . '</th>
<th style=\'text-align: center\'>' . __("Band") . '</th>
<th style=\'text-align: center\'>' . __("RST (S)") . '</th>
<th style=\'text-align: center\'>' . __("RST (R)") . '</th>
<th style=\'text-align: center\'>' . __("QSL") . ' ' . __("Via") . '</th>
<th style=\'text-align: center\'>' . __("Station") . '</th>
<th style=\'text-align: center\'>' . __("Profile name") . '</th>
<th style=\'text-align: center\'>' . __("Send Method") . '</th>
<th style=\'text-align: center\'>' . __("Mark as sent") . '</th>
<th style=\'text-align: center\'>' . __("Remove") . '</th>
<th style=\'text-align: center\'>' . __("QSO List") . '</th>
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
		echo '<tr id="qslprint_'.$qsl->COL_PRIMARY_KEY.'">';
		echo '<td style=\'text-align: center\'><div class="form-check"><input class="form-check-input" type="checkbox" /></div></td>';
                ?><td style='text-align: center'><span class="qso_call"><a id="edit_qso" href="javascript:displayQso(<?php echo $qsl->COL_PRIMARY_KEY; ?>);"><?php echo str_replace("0","&Oslash;",strtoupper($qsl->COL_CALL)); ?></a><a target="_blank" href="https://www.qrz.com/db/<?php echo strtoupper($qsl->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="Lookup <?php echo strtoupper($qsl->COL_CALL); ?> on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/<?php echo strtoupper($qsl->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/hamqth.png" alt="Lookup <?php echo strtoupper($qsl->COL_CALL); ?> on HamQTH"></a> <a target="_blank" href="http://www.eqsl.cc/Member.cfm?<?php echo strtoupper($qsl->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/eqsl.png" alt="Lookup <?php echo strtoupper($qsl->COL_CALL); ?> on eQSL.cc"></a></td><?php
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qsl->COL_TIME_ON); echo date($custom_date_format, $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qsl->COL_TIME_ON); echo date('H:i', $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'>'; echo $qsl->COL_SUBMODE==null?$qsl->COL_MODE:$qsl->COL_SUBMODE; echo '</td>';
		echo '<td style=\'text-align: center\'>'; if($qsl->COL_SAT_NAME != null) { echo $qsl->COL_SAT_NAME; } else { echo strtolower($qsl->COL_BAND); }; echo '</td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_RST_SENT . '</td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_RST_RCVD . '</td>';
		echo '<td style=\'text-align: center\'>' . $qsl->COL_QSL_VIA . '</td>';
		echo '<td style=\'text-align: center\'><span class="badge text-bg-light">' . $qsl->station_callsign . '</span></td>';
		echo '<td style=\'text-align: center\'>' . $qsl->station_profile_name . '</span></td>';
		echo '<td style=\'text-align: center\'>'; echo_qsl_sent_via($qsl->COL_QSL_SENT_VIA); echo '</td>';
		echo '<td style=\'text-align: center\'><button onclick="mark_qsl_sent(\''.$qsl->COL_PRIMARY_KEY.'\', \''. $qsl->COL_QSL_SENT_VIA. '\')" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button></td>';
		echo '<td style=\'text-align: center\'><button onclick="deleteFromQslQueue(\''.$qsl->COL_PRIMARY_KEY.'\')" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button></td>';
		echo '<td style=\'text-align: center\'><button onclick="openQsoList(\''.$qsl->COL_CALL.'\')" class="btn btn-sm btn-success"><i class="fas fa-search"></i></button></td>';
		echo '</tr>';
	}
	echo '</tbody></table></div>';
	?>

	<p><button onclick="markSelectedQsos();" title="<?= __("Mark selected QSOs as sent"); ?>" class="btn btn-success markallprinted"><?= __("Mark selected QSOs as sent"); ?></button>
	<button onclick="removeSelectedQsos();" title="<?= __("Remove selected QSOs from the queue"); ?>" class="btn btn-danger removeall"><?= __("Remove selected QSOs from the queue"); ?></button>
	<button onclick="exportSelectedQsos();" title="<?= __("Export selected QSOs to ADIF-file"); ?>" class="btn btn-primary exportselected"><?= __("Export selected QSOs to ADIF-file"); ?></button></p>

	<p><a href="<?php echo site_url('qslprint/exportcsv/' . $station_id); ?>" title="<?= __("Export CSV-file"); ?>" class="btn btn-primary"><?= __("Export requested QSLs to CSV-file"); ?></a>

	<a href="<?php echo site_url('qslprint/exportadif/' . $station_id); ?>" title="<?= __("Export ADIF"); ?>" class="btn btn-primary"><?= __("Export requested QSLs to ADIF-file"); ?></a>

	<a href="<?php echo site_url('qslprint/qsl_printed/' . $station_id); ?>" title="<?= __("Mark QSLs as printed"); ?>" class="btn btn-primary"><?= __("Mark requested QSLs as sent"); ?></a></p>

<?php
} else {
	echo '<div class="alert alert-danger">' . __("No QSLs to print were found!") . '</div>';
}
?>
