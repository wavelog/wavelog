<?php

function echo_table_header_col($ctx, $name) {
	switch($name) {
		case 'Mode': echo '<th scope="col">'.__("Mode").'</th>'; break;
		case 'RSTS': echo '<th scope="col">'.__("RST (S)").'</th>'; break;
		case 'RSTR': echo '<th scope="col">'.__("RST (R)").'</th>'; break;
		case 'Country': echo '<th scope="col">'.__("Country").'</th>'; break;
		case 'IOTA': echo '<th scope="col">'.__("IOTA").'</th>'; break;
		case 'SOTA': echo '<th scope="col">'.__("SOTA").'</th>'; break;
		case 'WWFF': echo '<th scope="col">'.__("WWFF").'</th>'; break;
		case 'POTA': echo '<th scope="col">'.__("POTA").'</th>'; break;
		case 'State': echo '<th scope="col">'.__("State").'</th>'; break;
		case 'Grid': echo '<th scope="col">'.__("Gridsquare").'</th>'; break;
		case 'Distance': echo '<th scope="col">'.__("Distance").'</th>'; break;
		case 'Band': echo '<th scope="col">'.__("Band").'</th>'; break;
		case 'Frequency': echo '<th scope="col">'.__("Frequency").'</th>'; break;
		case 'Operator': echo '<th scope="col">'.__("Operator").'</th>'; break;
		case 'Location': echo '<th scope="col">'.__("Station Location").'</th>'; break;
		case 'Name': echo '<th scope="col">'.__("Name").'</th>'; break;
		case 'Bearing': echo '<th scope="col">'.__("Bearing").'</th>'; break;
		case 'Propagation': echo '<th scope="col">'.__("Propagation").'</th>'; break;
	}
}

function echo_table_col($row, $name, $adif_propmodes) {
	$ci =& get_instance();
	switch($name) {
		case 'Mode':    echo '<td>'; echo html_escape($row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE) . '</td>'; break;
		case 'RSTS':    echo '<td>' . html_escape($row->COL_RST_SENT ?? ''); if ($row->COL_STX) { echo ' <span data-bs-toggle="tooltip" title="'.html_escape($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; printf("%03d", $row->COL_STX); echo '</span>';} if ($row->COL_STX_STRING) { echo ' <span data-bs-toggle="tooltip" title="'.html_escape($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . html_escape($row->COL_STX_STRING) . '</span>';} echo '</td>'; break;
		case 'RSTR':    echo '<td>' . html_escape($row->COL_RST_RCVD ?? ''); if ($row->COL_SRX) { echo ' <span data-bs-toggle="tooltip" title="'.html_escape($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; printf("%03d", $row->COL_SRX); echo '</span>';} if ($row->COL_SRX_STRING) { echo ' <span data-bs-toggle="tooltip" title="'.html_escape($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . html_escape($row->COL_SRX_STRING) . '</span>';} echo '</td>'; break;
		case 'Country': echo '<td>'; $adif = $row->adif ?? null; $cname = $row->name ?? null; if ($adif !== null) { if ($adif == 0) { echo html_escape($cname); } else { echo html_escape(ucwords(strtolower(($cname==null?"- NONE -":$cname)))); } if (($row->end ?? null) != null) echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; } echo '</td>'; break;
    case 'IOTA':    echo '<td>' . html_escape($row->COL_IOTA ?? '') . '</td>'; break;
		case 'SOTA':    echo '<td>' . html_escape($row->COL_SOTA_REF ?? '') . '</td>'; break;
		case 'WWFF':    echo '<td>' . html_escape($row->COL_WWFF_REF ?? '') . '</td>'; break;
		case 'POTA':    echo '<td>' . html_escape($row->COL_POTA_REF ?? '') . '</td>'; break;
		case 'Grid':
			if(!$ci->load->is_loaded('Qra')) {
				$ci->load->library('Qra');
			}
			echo '<td>' . ($ci->qra->echoQrbCalcLink($row->station_gridsquare ?? '', $row->COL_VUCC_GRIDS, $row->COL_GRIDSQUARE)) . '</td>'; break;
		case 'Distance':echo '<td>'. ($row->COL_DISTANCE != null ? '<span data-bs-toggle="tooltip" title="'.html_escape($row->COL_GRIDSQUARE).'">' . getDistance($row->COL_DISTANCE) . '</span>' : '') .'</td>'; break;
		case 'Bearing':echo '<td><span data-bs-toggle="tooltip" title="'.html_escape($row->COL_VUCC_GRIDS!="" ? $row->COL_VUCC_GRIDS : $row->COL_GRIDSQUARE).'">' . getBearing(($row->COL_VUCC_GRIDS!="" ? $row->COL_VUCC_GRIDS : $row->COL_GRIDSQUARE)) . '</span></td>'; break;
		case 'Band':
			echo '<td>'; if($row->COL_SAT_NAME ?? '' != '') { echo '<a href="https://db.satnogs.org/search/?q='.html_escape($row->COL_SAT_NAME).'" target="_blank"><span data-bs-toggle="tooltip" title="'.html_escape($row->COL_BAND ?? '').'">'.html_escape(($row->sat_name ?? '') != '' ? ($row->sat_name ?? '')." (".($row->sat_displayname ?? '').")" : ($row->sat_displayname ?? '')).'</span></a></td>'; } else { if ($row->COL_FREQ ?? ''!= '') { echo ' <span data-bs-toggle="tooltip" title="'.$ci->frequency->qrg_conversion($row->COL_FREQ ?? 0).'">'. html_escape(strtolower($row->COL_BAND ?? '')).'</span>'; } else { echo html_escape(strtolower($row->COL_BAND ?? '')); } } echo '</td>'; break;
		case 'Frequency':
			echo '<td>'; if($row->COL_SAT_NAME ?? '' != '') { echo '<a href="https://db.satnogs.org/search/?q='.html_escape($row->COL_SAT_NAME).'" target="_blank">'; if ($row->COL_FREQ != null) { echo ' <span data-bs-toggle="tooltip" title="'.$ci->frequency->qrg_conversion($row->COL_FREQ).'">'.html_escape(($row->sat_name ?? '') != '' ? ($row->sat_name ?? '')." (".($row->sat_displayname ?? '').")" : ($row->sat_displayname ?? '')).'</span>'; } else { echo html_escape($row->COL_SAT_NAME); } echo '</a></td>'; } else { if ($row->COL_FREQ != null && $row->COL_FREQ != 0) { echo ' <span data-bs-toggle="tooltip" title="'.html_escape($row->COL_BAND).'">'.$ci->frequency->qrg_conversion($row->COL_FREQ).'</span>'; } else { echo html_escape(strtolower($row->COL_BAND)); } } echo '</td>'; break;
		case 'State':   echo '<td>' . html_escape($row->COL_STATE ?? '') . '</td>'; break;
		case 'Operator':echo '<td>' . html_escape($row->COL_OPERATOR ?? '') . '</td>'; break;
		case 'Location':echo '<td>' . html_escape($row->station_profile_name ?? '') . '</td>'; break;
		case 'Name':echo '<td>' . html_escape($row->COL_NAME ?? '') . '</td>'; break;
		case 'Propagation':
			if (isset($row->COL_PROP_MODE)) {
				if (isset($adif_propmodes[$row->COL_PROP_MODE])) {
					echo '<td>' . htmlspecialchars_decode($adif_propmodes[$row->COL_PROP_MODE]) . '</td>';
				} else {
					echo '<td>' . html_escape($row->COL_PROP_MODE) . '</td>';
				}
			} else {
				echo '<td></td>';
			}
			break;
	}
}

function getBearing($grid = '') {
	if ($grid == '')  return '';
	$ci =& get_instance();
	if (($ci->session->userdata('user_locator') ?? '') != '') {
		if(!$ci->load->is_loaded('qra')) {
			$ci->load->library('qra');
		}
		$bearing=$ci->qra->get_bearing($ci->session->userdata('user_locator'),$grid);
		return($bearing.'&deg;');
	} else {
		return '';
	}
}

function getDistance($distance) {
	if ($distance === null) return '';

	$ci =& get_instance();
	if ($ci->session->userdata('user_measurement_base') == NULL) {
		$measurement_base = $ci->config->item('measurement_base');
	}
	else {
		$measurement_base = $ci->session->userdata('user_measurement_base');
	}

	switch ($measurement_base) {
		case 'M':
			$unit = "mi";
			break;
		case 'K':
			$unit = "km";
			break;
		case 'N':
			$unit = "nmi";
			break;
		default:
			$unit = "km";
		}

	if ($unit == 'mi') {
		$distance = round($distance * 0.621371, 1);
	}
	if ($unit == 'nmi') {
		$distance = round($distance * 0.539957, 1);
	}

	return $distance . ' ' . $unit;
}

?>

<?php
	if ($results) {
		$tableid = "contacttable";
		if (!empty($ispopup)) {
			$tableid = "displaycontactstable";
		}

?>

<div class="table-responsive">
    <?php
    $result_count = $results->num_rows();
    $table_aria_label = $result_count > 1 ? $result_count.' '.__("Results") : ($result_count == 1 ? $result_count.' '.__("Result") : '');
    ?>
    <table style="width:100%" id="<?= $tableid ?>" class="table <?= $tableid ?> table-striped table-hover"<?php if ($table_aria_label != '') { echo ' aria-label="'.htmlspecialchars($table_aria_label).'"'; } ?>>
        <thead>
            <tr class="titles">
                <th scope="col"><?= __("Date"); ?></th>
                <?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) { ?>
                <th scope="col"><?= __("Time"); ?></th>
                <?php } ?>
                <th scope="col"><?= __("Call"); ?></th>
                <?php
                echo_table_header_col($this, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
                echo_table_header_col($this, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
                echo_table_header_col($this, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
                echo_table_header_col($this, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
                echo_table_header_col($this, $this->session->userdata('user_column5'));

                    if(($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) {
    		    	if ( strpos($this->session->userdata('user_default_confirmation'),'Q') !== false  ) { ?>
                    	<th scope="col">QSL</th>
                    <?php } ?>
                    <?php if ( strpos($this->session->userdata('user_default_confirmation'),'E') !== false && ($this->session->userdata('user_eqsl_name') != "") ) { ?>
                        <th scope="col">eQSL</th>
                    <?php } ?>
                    <?php if ( strpos($this->session->userdata('user_default_confirmation'),'L') !== false && ($this->session->userdata('user_lotw_name') != "") ) { ?>
                        <th scope="col">LoTW</th>
                    <?php } ?>
    		    <?php if ( strpos($this->session->userdata('user_default_confirmation'),'Z') !== false && ($this->session->userdata('hasQrzKey') != "") ) { ?>
                        <th scope="col">QRZ</th>
                    <?php } ?>
		    <?php if ( strpos($this->session->userdata('user_default_confirmation'),'C') !== false && ($this->session->userdata('user_clublog_name') != "") ) { ?>
                        <th scope="col"><?= __("Clublog"); ?></th>
                    <?php } ?>
    		    <?php if ( strpos($this->session->userdata('user_default_confirmation'),'D') !== false  ) { ?>
                        <th scope="col"><?= __("DCL"); ?></th>
                    <?php } ?>
                <?php } ?>
                        <th scope="col"><?= __("Station"); ?></th>
                <?php if(($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
                    <th scope="col"><span class="visually-hidden"><?= __("Actions"); ?></span></th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>

        <?php  $i = 0;
            foreach ($results->result() as $row) {
                // Get Date format
                if($this->session->userdata('user_date_format')) {
                    // If Logged in and session exists
                    $custom_date_format = $this->session->userdata('user_date_format');
                } else {
                    // Get Default date format from /config/wavelog.php
                    $custom_date_format = $this->config->item('qso_date_format');
                }
                echo '<tr class="tr'.($i & 1).'" id="qso_'. $row->COL_PRIMARY_KEY .'">'; ?>
            <td><?php $timestamp = strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00'); echo date($custom_date_format, $timestamp); ?></td>
            <?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) { ?>
                <td><?php $timestamp = strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00'); echo date('H:i', $timestamp); ?></td>
            <?php } ?>
            <td>
                <button type="button" class="btn btn-link text-decoration-none p-0 callsign" id="edit_qso" onclick="displayQso(<?php echo (int) $row->COL_PRIMARY_KEY; ?>)"><?php echo html_escape(strtoupper($row->COL_CALL)); ?></button>
                <?php
                   if (isset($row->lastupload) && ($row->lastupload)) {
                       $lotw_hint = '';
                       $diff = (time() - strtotime($row->lastupload)) / 86400;
                       if ($diff > 365) {
                          $lotw_hint = ' lotw_info_red';
                       } elseif ($diff > 30) {
                          $lotw_hint = ' lotw_info_orange';
                       } elseif ($diff > 7) {
                          $lotw_hint = ' lotw_info_yellow';
                       }
                       $timestamp = strtotime($row->lastupload); echo ($row->callsign == '' ? '' : ' <a id="lotw_badge" style="float: right; user-select: none;" href="https://lotw.arrl.org/lotwuser/act?act='.html_escape($row->COL_CALL).'" target="_blank"><small id="lotw_info" class="badge text-bg-success'.$lotw_hint.'" data-bs-toggle="tooltip" title="LoTW User. Last upload was '.date($custom_date_format." H:i", $timestamp).'">L</small></a>');
                    }
                 ?>
            </td>
			<?php

                echo_table_col($row, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'), $adif_propmodes);
                echo_table_col($row, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'), $adif_propmodes);
                echo_table_col($row, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'), $adif_propmodes);
                echo_table_col($row, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'), $adif_propmodes);
                echo_table_col($row, $this->session->userdata('user_column5'), $adif_propmodes);

				if(($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) {
    		    			if ( strpos($this->session->userdata('user_default_confirmation'),'Q') !== false  ) { ?>
                <td id="qsl_<?php echo $row->COL_PRIMARY_KEY; ?>" class="qsl">
                <span <?php if ($row->COL_QSL_SENT != "N") {
                       switch ($row->COL_QSL_SENT) {
                       case "Y":
                          echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Sent");
                          break;
                       case "Q":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued");
                          break;
                       case "R":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested");
                          break;
                       case "I":
                          echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)");
                          break;
                       default:
                          echo "class=\"qsl-red";
                          break;
                       }
                        if ($row->COL_QSLSDATE != null) {
                            $timestamp = strtotime($row->COL_QSLSDATE); echo " "  .($timestamp != '' ? date($custom_date_format, $timestamp) : '');
                        }
                     } else { echo "class=\"qsl-red"; }
                       if ($row->COL_QSL_SENT_VIA != "") {
                          switch ($row->COL_QSL_SENT_VIA) {
                             case "B":
                                echo " (".__("Bureau").")";
                                break;
                             case "D":
                                echo " (".__("Direct").")";
                                break;
                             case "M":
                                echo " (".__("Via").": ".($row->COL_QSL_VIA!="" ? '<span class="callsign">'.html_escape($row->COL_QSL_VIA).'</span>':"n/a").")";
                                break;
                             case "E":
                                echo " (".__("Electronic").")";
                                break;
                         }
                       } ?>">&#9650;</span>
                <span <?php if ($row->COL_QSL_RCVD != "N") {
                       switch ($row->COL_QSL_RCVD) {
                       case "Y":
                          echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Received");
                          break;
                       case "Q":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued");
                          break;
                       case "R":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested");
                          break;
                       case "I":
                          echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)");
                          break;
                       default:
                          echo "class=\"qsl-red";
                          break;
                       }
                       if ($row->COL_QSLRDATE != null) {
                            $timestamp = strtotime($row->COL_QSLRDATE); echo " "  .($timestamp != '' ? date($custom_date_format, $timestamp) : '');
                       }
                     } else { echo "class=\"qsl-red"; }
                       if ($row->COL_QSL_RCVD_VIA != "") {
                          switch ($row->COL_QSL_RCVD_VIA) {
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
                       } ?>">&#9660;</span>
                </td>

                <?php } if (strpos($this->session->userdata('user_default_confirmation'),'E') !== false && ($this->session->userdata('user_eqsl_name') != "")){ ?>
                    <td class="eqsl">
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_EQSL_QSLSDATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_EQSL_QSLSDATE));
                        }
                        switch ($row->COL_EQSL_QSL_SENT) {
                           case "Y":
                              echo "title=\"".__("Sent");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-grey\"";
                              break;
                           case "R":
                              echo "title=\"".__("Requested");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-yellow\"";
                              break;
                           case "Q":
                              echo "title=\"".__("Queued");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-yellow\"";
                              break;
                           default:
                              echo " class=\"eqsl-red\"";
                              break;
                        }
                        ?>>&#9650;</span>
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_EQSL_QSLRDATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_EQSL_QSLRDATE));
                        }
                        switch ($row->COL_EQSL_QSL_RCVD) {
                           case "Y":
                              echo "title=\"".__("Received");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-grey\"";
                              break;
                           case "R":
                              echo "title=\"".__("Requested");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"eqsl-yellow\"";
                              break;
                           default:
                              echo " class=\"eqsl-red\"";
                              break;
                        }?>>
                        <?php if($row->COL_EQSL_QSL_RCVD =='Y') { ?>
                           <a class="eqsl-green" href="<?php echo site_url("eqsl/image/".$row->COL_PRIMARY_KEY); ?>" data-fancybox="images" data-width="528" data-height="336">&#9660;</a>
                        <?php } else { ?>
                           &#9660;
                        <?php } ?>
                    </span>
                    </td>
                <?php } ?>

                <?php if ( strpos($this->session->userdata('user_default_confirmation'),'L') !== false && ($this->session->userdata('user_lotw_name') != "") ) { ?>
                    <td class="lotw">
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_LOTW_QSLSDATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_LOTW_QSLSDATE));
                        }
                        switch ($row->COL_LOTW_QSL_SENT) {
                           case "Y":
                              echo "title=\"".__("Sent");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-grey\"";
                              break;
                           case "R":
                              echo "title=\"".__("Requested");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-yellow\"";
                              break;
                           case "Q":
                              echo "title=\"".__("Queued");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-yellow\"";
                              break;
                           default:
                              echo " class=\"lotw-red\"";
                              break;
                        }
                        ?>>&#9650;</span>
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_LOTW_QSLRDATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_LOTW_QSLRDATE));
                        }
                        switch ($row->COL_LOTW_QSL_RCVD) {
                           case "Y":
                              echo "title=\"".__("Received");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-grey\"";
                              break;
                           case "R":
                              echo "title=\"".__("Requested");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"lotw-yellow\"";
                              break;
                           default:
                              echo " class=\"lotw-red\"";
                              break;
                        }
                        ?>>&#9660;</span>
                    </td>
                <?php } ?>

                <?php if ( strpos($this->session->userdata('user_default_confirmation'),'Z') !== false && ($this->session->userdata('hasQrzKey') != "") ) { ?>
                    <td id="qrz_<?php echo $row->COL_PRIMARY_KEY; ?>" class="qrz">
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_QRZCOM_QSO_UPLOAD_DATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_QRZCOM_QSO_UPLOAD_DATE));
                        }
                        switch ($row->COL_QRZCOM_QSO_UPLOAD_STATUS) {
                           case "Y":
                              echo "title=\"".__("Sent");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"qrz-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"qrz-grey\"";
                              break;
                           case "Q":
                              echo "title=\"".__("Queued");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"qrz-yellow\"";
                              break;
                           case "M":
                              echo 'title="'.__("Modified")."<br />(".__("last sent")." ".date($custom_date_format, strtotime($row->COL_QRZCOM_QSO_UPLOAD_DATE)).")";
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" data-bs-html=\"true\" class=\"qrz-yellow\"";
                              break;
                           default:
                              echo " class=\"qrz-red\"";
                              break;
                        }
                        ?>>&#9650;</span>
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_QRZCOM_QSO_DOWNLOAD_STATUS != null && $row->COL_QRZCOM_QSO_DOWNLOAD_DATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_QRZCOM_QSO_DOWNLOAD_DATE));
                        }
                        switch ($row->COL_QRZCOM_QSO_DOWNLOAD_STATUS) {
                           case "Y":
                              echo "title=\"".__("Received");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"qrz-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"qrz-grey\"";
                              break;
                           default:
                              echo " class=\"qrz-red\"";
                              break;
                        }
                        ?>>&#9660;</span>
                    </td>
                <?php } ?>

                <?php if ( strpos($this->session->userdata('user_default_confirmation'),'C') !== false && ($this->session->userdata('user_clublog_name') != "") ) { ?>
                    <td class="clublog">
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_CLUBLOG_QSO_UPLOAD_DATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_CLUBLOG_QSO_UPLOAD_DATE));
                        }
                        switch ($row->COL_CLUBLOG_QSO_UPLOAD_STATUS) {
                           case "Y":
                              echo "title=\"".__("Sent");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"clublog-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"clublog-grey\"";
                              break;
                           case "Q":
                              echo "title=\"".__("Queued");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"clublog-yellow\"";
                              break;
                           case "M":
                              echo 'title="'.__("Modified")."<br />(".__("last sent")." ".date($custom_date_format, strtotime($row->COL_CLUBLOG_QSO_UPLOAD_DATE)).")";
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" data-bs-html=\"true\" class=\"clublog-yellow\"";
                              break;
                           default:
                              echo " class=\"clublog-red\"";
                              break;
                        }
                        ?>>&#9650;</span>
                    <span <?php
                        $timestamp = '';
                        if ($row->COL_CLUBLOG_QSO_DOWNLOAD_STATUS != null && $row->COL_CLUBLOG_QSO_DOWNLOAD_DATE != null) {
                           $timestamp = date($custom_date_format, strtotime($row->COL_CLUBLOG_QSO_DOWNLOAD_DATE));
                        }
                        switch ($row->COL_CLUBLOG_QSO_DOWNLOAD_STATUS) {
                           case "Y":
                              echo "title=\"".__("Received");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"clublog-green\"";
                              break;
                           case "I":
                              echo "title=\"".__("Invalid (Ignore)");
                              echo $timestamp != '' ? " ".$timestamp : '';
                              echo "\" data-bs-toggle=\"tooltip\" class=\"clublog-grey\"";
                              break;
                           default:
                              echo " class=\"clublog-red\"";
                              break;
                        }
                        ?>>&#9660;</span>
                    </td>
                <?php } ?>

                <?php if ( strpos($this->session->userdata('user_default_confirmation'),'D') !== false ) { ?>
                <td class="dcl">
                <span <?php if (($row->COL_DCL_QSL_SENT ?? 'N') != "N") {
                       switch ($row->COL_DCL_QSL_SENT ?? 'N') {
                       case "Y":
                          echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Sent");
                          break;
                       case "Q":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued");
                          break;
                       case "R":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested");
                          break;
                       case "I":
                          echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)");
                          break;
                       default:
                          echo "class=\"qsl-red";
                          break;
                       }
                        if (!empty($row->COL_DCL_QSLSDATE)) {
                            $timestamp = strtotime($row->COL_DCL_QSLSDATE); echo " "  .($timestamp != '' ? date($custom_date_format, $timestamp) : '');
                        }
                     } else { echo "class=\"qsl-red"; }
                        echo "\">&#9650;</span>"; ?>
                <span <?php if (($row->COL_DCL_QSL_RCVD ?? 'N') != "N") {
                       switch ($row->COL_DCL_QSL_RCVD ?? 'N') {
                       case "Y":
                          echo "class=\"qsl-green\" data-bs-toggle=\"tooltip\" title=\"".__("Received");
                          break;
                       case "Q":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Queued");
                          break;
                       case "R":
                          echo "class=\"qsl-yellow\" data-bs-toggle=\"tooltip\" title=\"".__("Requested");
                          break;
                       case "I":
                          echo "class=\"qsl-grey\" data-bs-toggle=\"tooltip\" title=\"".__("Invalid (Ignore)");
                          break;
                       default:
                          echo "class=\"qsl-red";
                          break;
                       }
                       if (!empty($row->COL_DCL_QSLRDATE)) {
                            $timestamp = strtotime($row->COL_DCL_QSLRDATE); echo " "  .($timestamp != '' ? date($custom_date_format, $timestamp) : '');
                       }
                     } else { echo "class=\"qsl-red"; }
                        echo "\">&#9660;</span>"; ?>
                </td>
                <?php } ?>

            <?php } ?>

                    <?php if(isset($row->station_callsign)) { ?>
                        <td>
                            <span class="badge text-bg-light callsign"><?php echo strtoupper($row->station_callsign); ?></span>
                        </td>
                    <?php } else { ?>
			<td></td>
		    <?php } ?>

            <?php if(($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle text-nowrap" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="<?= __("Actions"); ?>">
                            <i class="fas fa-cog" aria-hidden="true"></i>
                        </button>

                        <div class="dropdown-menu menuOnResultTab" data-bs-toggle="popover" data-bs-placement="auto" data-qsoid="qso_<?php echo $row->COL_PRIMARY_KEY; ?>">
                            <?php if (clubaccess_check(3, $row->COL_PRIMARY_KEY)) { ?>
                            <button type="button" class="dropdown-item" id="edit_qso" onclick="qso_edit(<?php echo $row->COL_PRIMARY_KEY; ?>)"><i class="fas fa-edit" aria-hidden="true"></i> <?= __("Edit QSO"); ?></button>
                            <?php } ?>

                            <?php if (clubaccess_check(9)) { ?>
                                <?php if($row->COL_QSL_SENT !='Y') { ?>
                                    <div class="qsl_sent_<?php echo $row->COL_PRIMARY_KEY; ?>">
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item" onclick="qsl_sent(<?php echo $row->COL_PRIMARY_KEY; ?>, 'B')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Sent (Bureau)"); ?></button>
                                        <button type="button" class="dropdown-item" onclick="qsl_sent(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Sent (Direct)"); ?></button>
                                        <button type="button" class="dropdown-item" onclick="qsl_requested(<?php echo $row->COL_PRIMARY_KEY; ?>, 'B')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Card Requested (Bureau)"); ?></button>
                                        <button type="button" class="dropdown-item" onclick="qsl_requested(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Card Requested (Direct)"); ?></button>
                                        <button type="button" class="dropdown-item" onclick="qsl_ignore(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Card Not Required"); ?></button>
                                    </div>
                                <?php } ?>

                                <?php if($row->COL_QSL_RCVD !='Y') { ?>
                                    <div class="qsl_rcvd_<?php echo $row->COL_PRIMARY_KEY; ?>">
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item" onclick="qsl_rcvd(<?php echo $row->COL_PRIMARY_KEY; ?>, 'B')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Received (Bureau)"); ?></button>
                                        <button type="button" class="dropdown-item" onclick="qsl_rcvd(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D')" ><i class="fas fa-envelope" aria-hidden="true"></i> <?= __("Mark QSL Received (Direct)"); ?></button>
                                    </div>
                                <?php } ?>

                                <div class="dropdown-divider"></div>
                            <?php } ?>

                            <a class="dropdown-item" href="https://www.qrz.com/db/<?php echo html_escape($row->COL_CALL); ?>" target="_blank"><i class="fas fa-question" aria-hidden="true"></i> <?= __("Lookup on QRZ.com"); ?></a>

                            <a class="dropdown-item" href="https://www.hamqth.com/<?php echo html_escape($row->COL_CALL); ?>" target="_blank"><i class="fas fa-question" aria-hidden="true"></i> <?= __("Lookup on HamQTH"); ?></a>

                            <?php if (clubaccess_check(3, $row->COL_PRIMARY_KEY)) { ?>
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item" onclick='qso_delete(<?php echo (int) $row->COL_PRIMARY_KEY; ?>, <?php echo js_escape($row->COL_CALL); ?>)'><i class="fas fa-trash-alt" aria-hidden="true"></i> <?= __("Delete QSO"); ?></button>
                            <?php } ?>
                        </div>
                    </div>
                </td>
            <?php } ?>
            </tr>
            <?php $i++; }
	    ?>
                            </tbody>
    </table></div>
    <?php } ?>

    <?php if (isset($this->pagination)){ ?>
        <?php
        $config['full_tag_open'] = '<ul class="pagination">';
        $config['full_tag_close'] = '</ul>';
        $config['attributes'] = ['class' => 'page-link'];
        $config['first_link'] = false;
        $config['last_link'] = false;
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['prev_link'] = '&laquo';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = '&raquo';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a href="#" class="page-link">';
        $config['cur_tag_close'] = '<span class="visually-hidden">(current)</span></a></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $this->pagination->initialize($config);
        ?>

        <?php echo $this->pagination->create_links(); ?>

    <?php } ?>

</div>
</div>
