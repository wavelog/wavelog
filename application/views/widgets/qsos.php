<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><?= __("QSOs"); ?></title>
	<style type="text/css" media="screen">
		body {
			font-family: Arial, "MS Trebuchet", sans-serif;
		}
		.titles th {
			font-weight: bold;
			text-align: left;
		}
	</style>
</head>

<?php if (($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) {
	$show_time = true;
} else {
	$show_time = false;
} ?>


<body>
	   <table width="100%" class="zebra-striped">
			<tr class="titles">
				<th><?= __("Date"); ?></th>
				<?php if ($show_time) { ?>
					<th><?= __("Time"); ?></th>
				<?php } ?>
				<th><?= __("Call"); ?></th>
				<th><?= __("Mode"); ?></th>
				<th><?= __("Sent"); ?></th>
				<th><?= __("Rcvd"); ?></th>
				<th><?= __("Band"); ?></th>
			</tr>

			<?php

			// Get Date format
			if($this->session->userdata('user_date_format')) {
				// If Logged in and session exists
				$custom_date_format = $this->session->userdata('user_date_format');
			} else {
				// Get Default date format from /config/wavelog.php
				$custom_date_format = $this->config->item('qso_date_format');
			}

			$i = 0;
			foreach ($last_five_qsos->result() as $row) { ?>
				<?php  echo '<tr class="tr'.($i & 1).'">'; ?>
					<td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
					<?php if ($show_time) { ?>
						<td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
					<?php } ?>
					<td><?php echo str_replace("0","&Oslash;",strtoupper($row->COL_CALL)); ?></td>
					<td><?php echo $row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE; ?></td>
					<td><?php echo $row->COL_RST_SENT; ?> <?php if ($row->COL_STX_STRING) { ?>(<?php echo $row->COL_STX_STRING;?>)<?php } ?></td>
					<td><?php echo $row->COL_RST_RCVD; ?> <?php if ($row->COL_SRX_STRING) { ?>(<?php echo $row->COL_SRX_STRING;?>)<?php } ?></td>
					<?php if($row->COL_SAT_NAME != null) { ?>
					<td><?php echo $row->COL_SAT_NAME; ?></td>
					<?php } else { ?>
					<td><?php echo $row->COL_BAND; ?></td>
					<?php } ?>
				</tr>
			<?php $i++; } ?>
		</table>
</body>

</html>
