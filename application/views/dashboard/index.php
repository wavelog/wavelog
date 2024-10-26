<?php
function echo_table_header_col($name) {
	switch($name) {
		case 'Mode': echo '<th>'.__("Mode").'</th>'; break;
		case 'RSTS': echo '<th class="d-none d-sm-table-cell">'.__("RSTS").'</th>'; break;
		case 'RSTR': echo '<th class="d-none d-sm-table-cell">'.__("RSTR").'</th>'; break;
		case 'Country': echo '<th>'.__("Country").'</th>'; break;
		case 'IOTA': echo '<th>'.__("IOTA").'</th>'; break;
		case 'SOTA': echo '<th>'.__("SOTA").'</th>'; break;
		case 'WWFF': echo '<th>'.__("WWFF").'</th>'; break;
		case 'POTA': echo '<th>'.__("POTA").'</th>'; break;
		case 'State': echo '<th>'.__("State").'</th>'; break;
		case 'Grid': echo '<th>'.__("Gridsquare").'</th>'; break;
		case 'Distance': echo '<th>'.__("Distance").'</th>'; break;
		case 'Band': echo '<th>'.__("Band").'</th>'; break;
		case 'Frequency': echo '<th>'.__("Frequency").'</th>'; break;
		case 'Operator': echo '<th>'.__("Operator").'</th>'; break;
		case 'Name': echo '<th>'.__("Name").'</th>'; break;
	}
}

function echo_table_col($row, $name) {
	$ci =& get_instance();
	switch($name) {
		case 'Mode':    echo '<td>'; echo $row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE . '</td>'; break;
      	case 'RSTS':    echo '<td class="d-none d-sm-table-cell">' . $row->COL_RST_SENT; if ($row->COL_STX) { echo ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; printf("%03d", $row->COL_STX); echo '</span>';} if ($row->COL_STX_STRING) { echo ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . $row->COL_STX_STRING . '</span>';} echo '</td>'; break;
      	case 'RSTR':    echo '<td class="d-none d-sm-table-cell">' . $row->COL_RST_RCVD; if ($row->COL_SRX) { echo ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; printf("%03d", $row->COL_SRX); echo '</span>';} if ($row->COL_SRX_STRING) { echo ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . $row->COL_SRX_STRING . '</span>';} echo '</td>'; break;
		case 'Country': echo '<td>' . ucwords(strtolower(($row->COL_COUNTRY))); if ($row->end != NULL) echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'  . '</td>'; break;
		case 'IOTA':    echo '<td>' . ($row->COL_IOTA) . '</td>'; break;
		case 'SOTA':    echo '<td>' . ($row->COL_SOTA_REF) . '</td>'; break;
		case 'WWFF':    echo '<td>' . ($row->COL_WWFF_REF) . '</td>'; break;
		case 'POTA':    echo '<td>' . ($row->COL_POTA_REF) . '</td>'; break;
		case 'Grid':
			if(!$ci->load->is_loaded('Qra')) {
				$ci->load->library('qra');
			}
			echo '<td>' . ($ci->qra->echoQrbCalcLink($row->station_gridsquare, $row->COL_VUCC_GRIDS, $row->COL_GRIDSQUARE)) . '</td>'; break;
		case 'Distance':    echo '<td>' . ($row->COL_DISTANCE ? $row->COL_DISTANCE . '&nbsp;km' : '') . '</td>'; break;
		case 'Band':    echo '<td>'; if($row->COL_SAT_NAME != null) { echo '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { echo strtolower($row->COL_BAND); } echo '</td>'; break;
		case 'Frequency':
			echo '<td>'; if($row->COL_SAT_NAME != null) { echo '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { if($row->COL_FREQ != null) { echo $ci->frequency->qrg_conversion($row->COL_FREQ); } else { echo strtolower($row->COL_BAND); } } echo '</td>'; break;
		case 'State':   echo '<td>' . ($row->COL_STATE) . '</td>'; break;
		case 'Operator': echo '<td>' . ($row->COL_OPERATOR) . '</td>'; break;
		case 'Name': echo '<td>' . ($row->COL_NAME) . '</td>'; break;
	}
}
?>

<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>

<div class="container dashboard">
<?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE) { ?>

	<?php if (version_compare(PHP_VERSION, '7.4.0') <= 0) { ?>
		<div class="alert alert-danger" role="alert">
		<?= __("You need to upgrade your PHP version. Minimum version is 7.4. Your version is") . ' ' . PHP_VERSION . '.';?>
		</div>
	<?php } ?>
	<?php if(($this->session->userdata('user_type') == 99) && !($this->config->item('disable_version_check') ?? false) && $this->optionslib->get_option('latest_release')) { ?>
		<?php if (version_compare($this->optionslib->get_option('latest_release'), $this->optionslib->get_option('version'), '>')) { ?>
			<div class="alert alert-success" role="alert" style="margin-top: 1rem;">
				<?= sprintf(_pgettext("Dashboard Warning", "A new version of Wavelog has been published. See: %s."), "<a href=\"https://github.com/wavelog/wavelog/releases/tag/".$this->optionslib->get_option('latest_release')."\" target=\"_blank\"><u>Release ".$this->optionslib->get_option('latest_release')."</u></a>"); ?>
			</div>
		<?php } ?>
	<?php } ?>

	<?php if ($countryCount == 0) { ?>
		<div class="alert alert-danger mt-3" role="alert">
		<?= sprintf(
				_pgettext("Dashboard Warning", "You need to update country files! Click %shere%s to do it."), '<u><a href="' . site_url('update') . '">', "</a></u>"
			); ?>
		</div>
	<?php } ?>

	<?php if ($locationCount == 0) { ?>
		<div class="alert alert-danger" role="alert">
		<?= sprintf(
				_pgettext("Dashboard Warning", "You have no station locations. Click %shere%s to do it."), '<u><a href="' . site_url('stationsetup') . '">', '</a></u>'
			); ?>
		</div>
	<?php } ?>

	<?php if ($logbookCount == 0) { ?>
		<div class="alert alert-danger" role="alert">
		<?= sprintf(
				_pgettext("Dashboard Warning", "You have no station logbook. Click %shere%s to do it."), '<u><a href="' . site_url('stationsetup') . '">', '</a></u>'
			); ?>
		</div>
	<?php } ?>

	<?php if (($linkedCount > 0) && $active_not_linked) { ?>
		<div class="alert alert-danger" role="alert">
		<?= sprintf(
				_pgettext("Dashboard Warning", "Your active Station Location isn't linked to your Logbook. Click %shere%s to do it."), '<u><a href="' . site_url('stationsetup') . '">', '</a></u>'
			); ?>
		</div>
	<?php } ?>

	<?php if ($linkedCount == 0) { ?>
		<div class="alert alert-danger" role="alert">
		<?= sprintf(
				_pgettext("Dashboard Warning", "You have no station linked to your Logbook. Click %shere%s to do it."), '<u><a href="' . site_url('stationsetup') . '">', '</a></u>'
			); ?>
		</div>
	<?php } ?>

	<?php if($this->optionslib->get_option('dashboard_banner') != "false") { ?>
	<?php if($todays_qsos >= 1) { ?>
		<div class="alert alert-success" role="alert" style="margin-top: 1rem;">
			<?= sprintf(
					_ngettext("You have had %d QSO today", "You have had %d QSOs today", intval($todays_qsos)), 
					intval($todays_qsos)
				); ?>
		</div>
	<?php } else { ?>
		<div class="alert alert-warning" role="alert" style="margin-top: 1rem;">
			  <span class="badge text-bg-info"><?= __("Important"); ?></span> <i class="fas fa-broadcast-tower"></i> <?= __("You have made no QSOs today; time to turn on the radio!"); ?>
		</div>
	<?php } ?>
	<?php } ?>

	<?php if($current_active == 0) { ?>
		<div class="alert alert-danger" role="alert">
		  <?= __("Attention: you need to set an active station location."); ?>
		</div>
	<?php } ?>

	<?php if($themesWithoutMode != 0) { ?>
		<div class="alert alert-danger" role="alert">
		  	<?= __("You have themes without defined theme mode. Please ask the admin to edit the themes."); ?>
		</div>
	<?php } ?>

	<?php if ($this->session->userdata('user_id')) { ?>
		<?php
			if($lotw_cert_expired == true) { ?>
			<div class="alert alert-danger" role="alert">
				<span class="badge text-bg-info"><?= __("Important"); ?></span> <i class="fas fa-hourglass-end"></i> <?= __("At least one of your LoTW certificates is expired!"); ?>
			</div>
		<?php } ?>

		<?php if($lotw_cert_expiring == true) { ?>
			<div class="alert alert-warning" role="alert">
				<span class="badge text-bg-info"><?= __("Important"); ?></span> <i class="fas fa-hourglass-half"></i> <?= __("At least one of your LoTW certificates is about to expire!"); ?>
			</div>
		<?php } ?>
	<?php } ?>
	
<?php } ?>
<?php $this->load->view('layout/messages'); ?>
</div>

<?php if($dashboard_map != "false" && $dashboard_map != "map_at_right") { ?>
<!-- Map -->
<div id="map" class="map-leaflet" style="width: 100%; height: 350px"></div>
<?php } ?>
<div style="padding-top: 0px; margin-top: 5px;" class="container dashboard">

<!-- Log Data -->
<div class="row logdata">
  <div class="col-sm-8">

  	<div class="table-responsive">
    	<table class="table table-striped table-hover border-top">

    		<thead>
				<tr class="titles">
					<th><?= __("Date"); ?></th>

					<?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) { ?>
					<th><?= __("Time"); ?></th>
					<?php } ?>
					<th><?= __("Callsign"); ?></th>
					<?php
					echo_table_header_col($this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
					echo_table_header_col($this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
					echo_table_header_col($this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
					echo_table_header_col($this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
				?>
				</tr>
			</thead>

			<?php
			$i = 0;
			if(!empty($last_five_qsos) > 0) {
			foreach ($last_five_qsos->result() as $row) { ?>
				<?php  echo '<tr id="qso_'.$row->COL_PRIMARY_KEY.'" class="tr'.($i & 1).'">'; ?>

					<?php

					// Get Date format
					if($this->session->userdata('user_date_format')) {
						// If Logged in and session exists
						$custom_date_format = $this->session->userdata('user_date_format');
					} else {
						// Get Default date format from /config/wavelog.php
						$custom_date_format = $this->config->item('qso_date_format');
					}

					?>

					<td><?php $timestamp = strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00'); echo date($custom_date_format, $timestamp); ?></td>
					<?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) { ?>
					<td><?php $timestamp = strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00'); echo date('H:i', $timestamp); ?></td>

					<?php } ?>
					<td>
                        <a id="edit_qso" href="javascript:displayQso(<?php echo $row->COL_PRIMARY_KEY; ?>)"><?php echo str_replace("0","&Oslash;",strtoupper($row->COL_CALL)); ?></a>
					</td>
					<?php
						echo_table_col($row, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
						echo_table_col($row, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
						echo_table_col($row, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
						echo_table_col($row, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
					?>
				</tr>
			<?php $i++; } } ?>
		</table>
	</div>
  </div>

  <div class="col-sm-4">
  	<?php if($dashboard_map == "map_at_right") { ?>
	<!-- Map -->
	<div id="map" class="map-leaflet" style="width: 100%; height: 350px;  margin-bottom: 15px;"></div>
	<?php } ?>
  	<div class="table-responsive">


		<div id="radio_display" hx-get="<?php echo site_url('dashboard/radio_display_component'); ?>" hx-trigger="load, every 5s"></div>

    	<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-chart-bar"></i> <?= __("QSOs Breakdown"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Total"); ?></td>
				<td width="50%"><?php echo $total_qsos; ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Year"); ?></td>
				<td width="50%"><?php echo $year_qsos; ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Month"); ?></td>
				<td width="50%"><?php echo $month_qsos; ?></td>
			</tr>
		</table>



		<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-globe-europe"></i> <?= __("DXCCs Breakdown"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Worked"); ?></td>
				<td width="50%"><?php echo $total_countries; ?></td>
			</tr>
			<tr>
				<td width="50%"><a href="#" onclick="return false" title="<?= __("QSL Cards") ." / ". __("LoTW") ." / " . __("eQSL"); ?>" data-bs-toggle="tooltip"><?= __("Confirmed"); ?></a></td>
				<td width="50%">
					<?php echo $total_countries_confirmed_paper; ?> /
					<?php echo $total_countries_confirmed_lotw; ?> /
					<?php echo $total_countries_confirmed_eqsl; ?>
				</td>
			</tr>

			<tr>
				<td width="50%"><?= __("Needed"); ?></td>
				<td width="50%"><?php echo $total_countries_needed; ?></td>
			</tr>
		</table>

		<?php if((($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE) && ($total_qsl_sent != 0 || $total_qsl_rcvd != 0 || $total_qsl_requested != 0)) { ?>
		<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-envelope"></i> <?= __("QSL Cards"); ?></td>
				<td colspan="1"><?= __("Today"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Sent"); ?></td>
				<td width="25%"><?php echo $total_qsl_sent; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','All','All','All','All','QSLSDATE','');"><?php echo $qsl_sent_today; ?></a></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Received"); ?></td>
				<td width="25%"><?php echo $total_qsl_rcvd; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','All','All','All','All','QSLRDATE','');"><?php echo $qsl_rcvd_today; ?></a></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Requested"); ?></td>
				<td width="25%"><?php echo $total_qsl_requested; ?></td>
				<td width="25%"><?php echo $qsl_requested_today; ?></td>
			</tr>
		</table>
		<?php } ?>

		<?php if((($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === false) && ($total_lotw_sent != 0 || $total_lotw_rcvd != 0)) { ?>
		<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-list"></i> <?= _pgettext("Probably no translation needed as this is a name.","Logbook of the World"); ?></td>
				<td colspan="1"><?= __("Today"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Sent"); ?></td>
				<td width="25%"><?php echo $total_lotw_sent; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','all','all','All','All','LOTWSDATE','');"><?php echo $lotw_sent_today; ?></a></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Received"); ?></td>
				<td width="25%"><?php echo $total_lotw_rcvd; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','all','all','All','All','LOTWRDATE','');"><?php echo $lotw_rcvd_today; ?></a></td>
			</tr>
		</table>
		<?php } ?>

		<?php if((($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE) && ($total_eqsl_sent != 0 || $total_eqsl_rcvd != 0)) { ?>
		<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-address-card"></i> <?= __("eQSL Cards"); ?></td>
				<td colspan="1"><?= __("Today"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Sent"); ?></td>
				<td width="25%"><?php echo $total_eqsl_sent; ?></td>
            <td width="25%"><a href="javascript:displayContacts('','All','All','All','All','EQSLSDATE','');"><?php echo $eqsl_sent_today; ?></a></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Received"); ?></td>
				<td width="25%"><?php echo $total_eqsl_rcvd; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','All','All','All','All','EQSLRDATE','');"><?php echo $eqsl_rcvd_today; ?></a></td>
			</tr>
		</table>
		<?php } ?>

		<?php if((($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === false) && ($total_qrz_sent != 0 || $total_qrz_rcvd != 0)) { ?>
		<table class="table table-striped border-top">
			<tr class="titles">
				<td colspan="2"><i class="fas fa-list"></i> QRZ.com</td>
				<td colspan="1"><?= __("Today"); ?></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Sent"); ?></td>
				<td width="25%"><?php echo $total_qrz_sent; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','all','all','All','All','QRZSDATE','');"><?php echo $qrz_sent_today; ?></a></td>
			</tr>

			<tr>
				<td width="50%"><?= __("Received"); ?></td>
				<td width="25%"><?php echo $total_qrz_rcvd; ?></td>
				<td width="25%"><a href="javascript:displayContacts('','all','all','All','All','QRZRDATE','');"><?php echo $qrz_rcvd_today; ?></a></td>
			</tr>
		</table>
		<?php } ?>

		<?php if((($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE)) { ?>
    	 <table class="table table-striped border-top">
        <tr class="titles">
            <td colspan="2"><i class="fas fa-globe-europe"></i> <?= __("VUCC-Grids"); ?></td>
            <td colspan="1"><?= __("SAT"); ?></td>
        </tr>

        <tr>
            <td width="50%"><?= __("Worked"); ?></td>
            <td width="25%"><?php echo $vucc['All']['worked']; ?></td>
            <td width="25%"><?php echo $vuccSAT['SAT']['worked'] ?? '0'; ?></td>
        </tr>

        <tr>
            <td width="50%"><?= __("Confirmed"); ?></td>
            <td width="25%"><?php echo $vucc['All']['confirmed']; ?></td>
            <td width="25%"><?php echo $vuccSAT['SAT']['confirmed'] ?? '0'; ?></td>
        </tr>

    </table>
    <?php } ?>
	</div>
  </div>
</div>

</div>
