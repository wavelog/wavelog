<div id="qso-last-table">

<div class="table-responsive" style="font-size: 0.95rem;">
  <table class="table table-striped">
    <tr class="log_title titles">
      <th><?= __("Date/Time"); ?></th>
	<th><?= __("Call"); ?></th>
	<?php
	echo_table_header_col($this, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
	echo_table_header_col($this, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
	echo_table_header_col($this, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
	echo_table_header_col($this, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
       ?>
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
  if($query != false) {
  foreach ($query->result() as $row) {
        echo '<tr class="tr'.($i & 1).'">';
          echo '<td>';
              $timestamp = strtotime($row->COL_TIME_ON);
              echo date($custom_date_format, $timestamp);
              echo date(' H:i',strtotime($row->COL_TIME_ON));
          ?>
        </td>
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

<?php
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
			echo '<td>' . ($ci->qra->echoQrbCalcLink($row->COL_MY_GRIDSQUARE, $row->COL_VUCC_GRIDS, $row->COL_GRIDSQUARE)) . '</td>'; break;
		case 'Distance':    echo '<td>' . ($row->COL_DISTANCE ? $row->COL_DISTANCE . '&nbsp;km' : '') . '</td>'; break;
		case 'Band':    echo '<td>'; if($row->COL_SAT_NAME != null) { echo '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { echo strtolower($row->COL_BAND); } echo '</td>'; break;
		case 'Frequency':
			echo '<td>'; if($row->COL_SAT_NAME != null) { echo '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { if($row->COL_FREQ != null) { echo $ci->frequency->qrg_conversion($row->COL_FREQ); } else { echo strtolower($row->COL_BAND); } } echo '</td>'; break;
		case 'State':   echo '<td>' . ($row->COL_STATE) . '</td>'; break;
		case 'Operator': echo '<td>' . ($row->COL_OPERATOR) . '</td>'; break;
	}
}

function echo_table_header_col($ctx, $name) {
	switch($name) {
		case 'Mode': echo '<th>'.__("Mode").'</th>'; break;
		case 'RSTS': echo '<th class="d-none d-sm-table-cell">'.__("RST (S)").'</th>'; break;
		case 'RSTR': echo '<th class="d-none d-sm-table-cell">'.__("RST (R)").'</th>'; break;
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
	}
}

?>
