<?php if ($query->num_rows() > 0) {  foreach ($query->result() as $row) { ?>
<div class="container-fluid">

    <ul style="margin-bottom: 10px;" class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#qsodetails" role="tab" aria-controls="table" aria-selected="true"><?= __("QSO Details"); ?></a>
        </li>
        <li class="nav-item">
            <a id="station-tab" class="nav-link" data-bs-toggle="tab" href="#stationdetails" role="tab" aria-controls="table" aria-selected="true"><?= __("Station Location"); ?></a>
        </li>
        <?php
        if ($row->COL_NOTES != null) {?>
        <li class="nav-item">
            <a id="notes-tab" class="nav-link" data-bs-toggle="tab" href="#notesdetails" role="tab" aria-controls="table" aria-selected="true"><?= __("Notes"); ?></a>
        </li>
        <?php }?>
        <?php
        if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) {

            echo '<li ';
            if (count($qslimages) == 0) {
                echo 'hidden ';
            }
                echo 'class="qslcardtab nav-item">
                <a class="nav-link" id="qsltab" data-bs-toggle="tab" href="#qslcard" role="tab" aria-controls="home" aria-selected="false">'. __("QSL Card") .'</a>
                </li>';

            echo '<li class="nav-item">
            <a class="nav-link" id="qslmanagementtab" data-bs-toggle="tab" href="#qslupload" role="tab" aria-controls="home" aria-selected="false">'. __("QSL Management") .'</a>
            </li>';
        }

        ?>
        <?php
        if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) {

            echo '<li ';
            if ($row->eqsl_image_file == null) {
                echo 'hidden ';
            }
                echo 'class="eqslcardtab nav-item">
                <a class="nav-link" id="eqsltab" data-bs-toggle="tab" href="#eqslcard" role="tab" aria-controls="home" aria-selected="false">'. __("eQSL Card") .'</a>
                </li>';
        }

        ?>

    </ul>

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane active" id="qsodetails" role="tabpanel" aria-labelledby="home-tab">

        <div class="row">
            <div class="col-md">

                <table width="100%">
                    <tr>
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

                        <td><?= __("Date/Time"); ?></td>
                        <?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE || ($this->config->item('show_time'))) { ?>
                        <td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); $timestamp = strtotime($row->COL_TIME_ON); $time_on = date('H:i', $timestamp); echo " at ".$time_on; ?>
                        <?php $timestamp = strtotime($row->COL_TIME_OFF); $time_off = date('H:i', $timestamp); if ($time_on != $time_off) { echo " - ".$time_off; } ?>
                        </td>
                        <?php } else { ?>
                        <td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
                        <?php } ?>
                    </tr>

                    <tr>
                        <td><?= __("Callsign"); ?></td>
                        <td><b><?php echo str_replace("0","&Oslash;",strtoupper($row->COL_CALL)); ?></b> <a target="_blank" href="https://www.qrz.com/db/<?php echo strtoupper($row->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="Lookup <?php echo strtoupper($row->COL_CALL); ?> on QRZ.com"></a> <a target="_blank" href="https://www.hamqth.com/<?php echo strtoupper($row->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/hamqth.png" alt="Lookup <?php echo strtoupper($row->COL_CALL); ?> on HamQTH"></a> <a target="_blank" href="http://www.eqsl.cc/Member.cfm?<?php echo strtoupper($row->COL_CALL); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/eqsl.png" alt="Lookup <?php echo strtoupper($row->COL_CALL); ?> on eQSL.cc"></a> <a target="_blank" href="https://clublog.org/logsearch.php?log=<?php echo strtoupper($row->COL_CALL); ?>&call=<?php echo strtoupper($row->station_callsign); ?>"><img width="16" height="16" src="<?php echo base_url(); ?>images/icons/clublog.png" alt="Clublog Log Search"></a></td>
                    </tr>

                    <tr>
                        <td><?= __("Band"); ?></td>
                        <td><?php echo $row->COL_BAND; ?></td>
                    </tr>

                    <?php if($this->config->item('display_freq') == true) { ?>
                        <?php if($row->COL_FREQ != 0) { ?>
                        <tr>
                            <td><?= __("Frequency"); ?></td>
                            <td><?php echo $this->frequency->qrg_conversion($row->COL_FREQ); ?></td>
                        </tr>
                        <?php } ?>
                        <?php if($row->COL_FREQ_RX != 0) { ?>
                        <tr>
                            <td><?= __("Frequency (RX)"); ?></td>
                            <td><?php echo $this->frequency->qrg_conversion($row->COL_FREQ_RX); ?></td>
                        </tr>
                        <?php } ?>
                    <?php } ?>

                    <tr>
                        <td><?= __("Mode"); ?></td>
                        <td><?php echo $row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE; ?></td>
                    </tr>

                    <tr>
                        <td><?= __("RST (S)"); ?></td>
                        <td><?php echo $row->COL_RST_SENT; ?> <?php if ($row->COL_STX) { ?>(<?php printf("%03d", $row->COL_STX);?>)<?php } ?> <?php if ($row->COL_STX_STRING) { ?>(<?php echo $row->COL_STX_STRING;?>)<?php } ?></td>
                    </tr>

                    <tr>
                        <td><?= __("RST (R)"); ?></td>
                        <td><?php echo $row->COL_RST_RCVD; ?> <?php if ($row->COL_SRX) { ?>(<?php printf("%03d", $row->COL_SRX);?>)<?php } ?> <?php if ($row->COL_SRX_STRING) { ?>(<?php echo $row->COL_SRX_STRING;?>)<?php } ?></td>
                    </tr>

                    <?php if($row->COL_GRIDSQUARE != null) { ?>
                    <tr>
                        <td>Gridsquare:</td>
                        <td><?php echo $row->COL_GRIDSQUARE; ?> <a href="javascript:spawnQrbCalculator('<?php echo $row->station_gridsquare . '\',\'' . $row->COL_GRIDSQUARE; ?>')"><i class="fas fa-globe"></i></a></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_GRIDSQUARE != null && strlen($row->COL_GRIDSQUARE) >= 4) { ?>
                    <!-- Total Distance Between the Station Profile Gridsquare and Logged Square -->
                    <tr>
                        <td><?= __("Total Distance"); //Total distance ?></td>
                        <td>
                            <?php
                                // Cacluate Distance
                                $distance = $this->qra->distance($row->station_gridsquare, $row->COL_GRIDSQUARE, $measurement_base);

                                switch ($measurement_base) {
                                    case 'M':
                                        $distance .= " mi";
                                        break;
                                    case 'K':
                                        $distance .= " km";
                                        break;
                                    case 'N':
                                        $distance .= " nmi";
                                        break;
                                }
                                echo $distance;
                            ?>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_VUCC_GRIDS != null) { ?>
                    <tr>
                        <td>Gridsquare (Multi):</td>
                        <td><?php echo $row->COL_VUCC_GRIDS; ?> <a href="javascript:spawnQrbCalculator('<?php echo $row->station_gridsquare . '\',\'' . $row->COL_VUCC_GRIDS; ?>')"><i class="fas fa-globe"></i></a></td>
                            <?php
                                // Cacluate Distance
                                $distance = $this->qra->distance($row->station_gridsquare, $row->COL_VUCC_GRIDS, $measurement_base);

                                switch ($measurement_base) {
                                    case 'M':
                                        $distance .= " mi";
                                        break;
                                    case 'K':
                                        $distance .= " km";
                                        break;
                                    case 'N':
                                        $distance .= " nmi";
                                        break;
                                }
                                echo $distance;
                            ?>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_STATE != null) { ?>
                    <tr>
                        <td><?php echo $primary_subdivision ?>:</td>
                        <td><?php if ($row->subdivision != '') { echo $row->subdivision.' ('.$row->COL_STATE.')'; } else { echo $row->COL_STATE; } ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_CNTY != null && $row->COL_CNTY != ",") { ?>
                    <tr>
                        <td><?php echo $secondary_subdivision ?>:</td>
                        <td><?php echo $row->COL_CNTY; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_NAME != null) { ?>
                    <tr>
                        <td><?= __("Name"); ?></td>
                        <td><?php echo $row->COL_NAME; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE) { ?>
                    <?php if($row->COL_COMMENT != null) { ?>
                    <tr>
                        <td><?= __("Comment"); ?></td>
                        <td><?php echo $row->COL_COMMENT; ?></td>
                    </tr>
                    <?php } ?>
                    <?php } ?>

                    <?php if($row->COL_SAT_NAME != null) { ?>
                    <tr>
                        <td><?= __("Satellite Name"); ?></td>
                        <td><a href="https://db.satnogs.org/search/?q=<?php echo $row->COL_SAT_NAME; ?>" target="_blank"><?php echo $row->COL_SAT_NAME; ?></a></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_SAT_MODE != null) { ?>
                    <tr>
                        <td><?= __("Satellite Mode"); ?></td>
                        <td><?php echo (strlen($row->COL_SAT_MODE) == 2 ? (strtoupper($row->COL_SAT_MODE[0]).'/'.strtoupper($row->COL_SAT_MODE[1])) : strtoupper($row->COL_SAT_MODE)); ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_ANT_AZ != null) { ?>
                    <tr>
                        <td><?= __("Antenna Azimuth"); ?></td>
                        <td><?php echo round($row->COL_ANT_AZ, 1); ?>&deg; <span style="margin-left: 2px; display: inline-block; transform: rotate(<?php echo (-45+$row->COL_ANT_AZ); ?>deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_ANT_EL != null) { ?>
                    <tr>
                        <td><?= __("Antenna Elevation"); ?></td>
                        <td><?php echo round($row->COL_ANT_EL, 1); ?>&deg; <span style="margin-left: 2px; display: inline-block; transform: rotate(<?php echo (-$row->COL_ANT_EL); ?>deg);"><i class="fas fa-arrow-right fa-xs"></i></span></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->name != null) { ?>
                    <tr>
                        <td><?= __("Country"); ?></td>
                        <td><?php echo ucwords(strtolower(($row->name)), "- (/"); if ($dxccFlag != null) { echo " ".$dxccFlag; } if ($row->end != null) { echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; } ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_CONT != null) { ?>
                    <tr>
                        <td><?= __("Continent"); ?></td>
                        <td>
                        <?php
                           switch($row->COL_CONT) {
                             case "AF":
                               echo __("Africa");
                               break;
                             case "AN":
                               echo __("Antarctica");
                               break;
                             case "AS":
                               echo __("Asia");
                               break;
                             case "EU":
                               echo __("Europe");
                               break;
                             case "NA":
                               echo __("North America");
                               break;
                             case "OC":
                               echo __("Oceania");
                               break;
                             case "SA":
                               echo __("South America");
                               break;
                           }
                        ?>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_CONTEST_ID != null) { ?>
                    <tr>
                        <td><?= __("Contest Name"); ?></td>
                        <td><?php echo $row->COL_CONTEST_ID; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_IOTA != null) { ?>
                    <tr>
                        <td><?= __("IOTA Reference"); ?></td>
                        <td><a href="https://www.iota-world.org/iotamaps/?grpref=<?php echo $row->COL_IOTA; ?>" target="_blank"><?php echo $row->COL_IOTA; ?></a></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_SOTA_REF != null) { ?>
                    <tr>
                        <td><?= __("SOTA Reference"); ?></td>
                        <td><a href="https://summits.sota.org.uk/summit/<?php echo $row->COL_SOTA_REF; ?>" target="_blank"><?php echo $row->COL_SOTA_REF; ?></a></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_WWFF_REF != null) { ?>
                    <tr>
                        <td><?= __("WWFF Reference"); ?></td>
                        <td><a href="https://www.cqgma.org/zinfo.php?ref=<?php echo $row->COL_WWFF_REF; ?>" target="_blank"><?php echo $row->COL_WWFF_REF; ?></a></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_POTA_REF != null) { ?>
                    <tr>
                        <td><?= __("POTA Reference(s)"); ?></td>
                        <td>
                            <?php
                            $pota_refs = explode(',', $row->COL_POTA_REF);
                            $link_output = '';

                            foreach ($pota_refs as $pota_ref) {
                                $pota_ref = trim($pota_ref);
                                if (!empty($pota_ref)) {
                                    $link_output .= '<a href="https://pota.app/#/park/' . $pota_ref . '" target="_blank">' . $pota_ref . '</a>, ';
                                }
                            }

                            $link_output = rtrim($link_output, ', ');
                            echo $link_output;
                            ?>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_SIG != null) { ?>
                    <tr>
                        <td><?= __("Sig"); ?></td>
                        <?php
                        switch ($row->COL_SIG) {
                        case "GMA":
                           echo "<td><a href=\"https://cqgma.org/\" target=\"_blank\">".$row->COL_SIG."</a></td>";
                           break;
                        default:
                           echo "<td>".$row->COL_SIG."</td>";
                           break;
                        }
                        ?>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_SIG_INFO != null) { ?>
                    <tr>
                        <td><?= __("Sig Info"); ?></td>
                        <?php
                        switch ($row->COL_SIG) {
                        case "GMA":
                           echo "<td><a href=\"https://www.cqgma.org/zinfo.php?ref=".$row->COL_SIG_INFO."\" target=\"_blank\">".$row->COL_SIG_INFO."</a></td>";
                           break;
                        case "MQC":
                           echo "<td><a href=\"https://www.mountainqrp.it/awards/referenza.php?ref=".$row->COL_SIG_INFO."\" target=\"_blank\">".$row->COL_SIG_INFO."</a></td>";
                           break;
                        default:
                           echo "<td>".$row->COL_SIG_INFO."</td>";
                           break;
                        }
                        ?>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_DARC_DOK != null) { ?>
                    <tr>
                        <td><?= __("DOK"); ?></td>
                        <?php if (preg_match('/^[A-Y]\d{2}$/', $row->COL_DARC_DOK)) { ?>
                        <td><a href="https://www.darc.de/<?php echo $row->COL_DARC_DOK; ?>" target="_blank"><?php echo $row->COL_DARC_DOK; ?></a></td>
                        <?php } else if (preg_match('/^DV[ABCDEFGHIKLMNOPQRSTUVWXY]$/', $row->COL_DARC_DOK)) { ?>
                        <td><a href="https://www.darc.de/der-club/distrikte/<?php echo strtolower(substr($row->COL_DARC_DOK, 2, 1)); ?>" target="_blank"><?php echo $row->COL_DARC_DOK; ?></a></td>
                        <?php } else if (preg_match('/^Z\d{2}$/', $row->COL_DARC_DOK)) { ?>
                        <td><a href="https://<?php echo $row->COL_DARC_DOK; ?>.vfdb.org" target="_blank"><?php echo $row->COL_DARC_DOK; ?></a></td>
                        <?php } else { ?>
                        <td><?php echo $row->COL_DARC_DOK; ?></td>
                        <?php } ?>
                    </tr>
                    <?php } ?>

                </table>
                <?php if($row->COL_QSL_SENT == "Y" || $row->COL_QSL_RCVD == "Y") { ?>
                    <h3><?= __("QSL Info"); ?></h3>

                    <?php if($row->COL_QSL_SENT == "Y") {?>
                        <?php if ($row->COL_QSL_SENT_VIA == "B") { ?>
                            <p><?= __("QSL Card has been sent via the bureau"); ?>
                        <?php } else if($row->COL_QSL_SENT_VIA == "D") { ?>
                            <p><?= __("QSL Card has been sent via direct"); ?>
                        <?php } else if($row->COL_QSL_SENT_VIA == "E") { ?>
                            <p><?= __("QSL Card has been sent electronically"); ?>
                        <?php } else if($row->COL_QSL_SENT_VIA == "M") { ?>
                            <p><?= __("QSL Card has been sent via manager"); ?>
                        <?php } else { ?>
                            <p><?= __("QSL Card has been sent"); ?>
                        <?php } ?>
                        <?php if ($row->COL_QSLSDATE != null) { ?>
                            <?php $timestamp = strtotime($row->COL_QSLSDATE); echo " (".date($custom_date_format, $timestamp).")"; ?></p>
                        <?php } ?>
                    <?php } ?>

                    <?php if($row->COL_QSL_RCVD == "Y") { ?>
                        <?php if ($row->COL_QSL_RCVD_VIA == "B") { ?>
                            <p><?= __("QSL Card has been received via the bureau"); ?>
                        <?php } else if($row->COL_QSL_RCVD_VIA == "D") { ?>
                            <p><?= __("QSL Card has been received via direct"); ?>
                        <?php } else if($row->COL_QSL_RCVD_VIA == "E") { ?>
                            <p><?= __("QSL Card has been received electronically"); ?>
                        <?php } else if($row->COL_QSL_RCVD_VIA == "M") { ?>
                            <p><?= __("QSL Card has been received via manager"); ?>
                        <?php } else { ?>
                            <p><?= __("QSL Card has been received"); ?>
                        <?php } ?>
                        <?php if ($row->COL_QSLRDATE != null) { ?>
                            <?php $timestamp = strtotime($row->COL_QSLRDATE); echo " (".date($custom_date_format, $timestamp).")"; ?></p>
                        <?php } ?>
                    <?php } ?>

                <?php } ?>
                    <?php if($row->lotwuser != null) { ?>
                    <br /><p><?= __("This station uses LoTW."); ?> <a href="https://lotw.arrl.org/lotwuser/act?act=<?php echo $row->COL_CALL;?>" target="_blank"><?= __("Last Upload").'</a>: '; ?><?php $timestamp = strtotime($row->lastupload); echo date($custom_date_format, $timestamp); $timestamp = strtotime($row->lastupload); echo " ".date('H:i', $timestamp);?> UTC.</p>
                    <?php } ?>

                    <?php if($row->COL_LOTW_QSL_RCVD == "Y" && $row->COL_LOTW_QSLRDATE != null) { ?>
                    <h3><?= __("LoTW"); ?></h3>
                    <p><?= __("This QSO was confirmed on"); ?> <?php $timestamp = strtotime($row->COL_LOTW_QSLRDATE); echo date($custom_date_format, $timestamp); ?>.</p>
                    <?php } ?>

                    <?php if($row->COL_EQSL_QSL_RCVD == "Y" && $row->COL_EQSL_QSLRDATE != null) { ?>
                    <h3>eQSL</h3>
                        <p><?= __("This QSO was confirmed on"); ?> <?php $timestamp = strtotime($row->COL_EQSL_QSLRDATE); echo date($custom_date_format, $timestamp); ?>.</p>
                    <?php } ?>

                    <?php if($row->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "Y" && $row->COL_QRZCOM_QSO_DOWNLOAD_DATE != null) { ?>
                    <h3>QRZ.com</h3>
                        <p><?= __("This QSO was confirmed on"); ?> <?php $timestamp = strtotime($row->COL_QRZCOM_QSO_DOWNLOAD_DATE); echo date($custom_date_format, $timestamp); ?>.</p>
                    <?php } ?>

                    <?php if($row->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "Y" && $row->COL_CLUBLOG_QSO_DOWNLOAD_DATE != null) { ?>
                    <h3><?= __("Clublog"); ?></h3>
                        <p><?= __("This QSO was confirmed on"); ?> <?php $timestamp = strtotime($row->COL_CLUBLOG_QSO_DOWNLOAD_DATE); echo date($custom_date_format, $timestamp); ?>.</p>
                    <?php } ?>
            </div>

                <div class="col-md">

                    <div id="mapqso" class="map-leaflet" style="width: 100%; height: 250px"></div>

                    <?php if(($this->config->item('use_auth') && ($this->session->userdata('user_type') >= 2)) || $this->config->item('use_auth') === FALSE) { ?>
                        <br>
                            <div style="display: inline-block;"><p class="editButton"><a class="btn btn-primary" href="<?php echo site_url('qso/edit'); ?>/<?php echo $row->COL_PRIMARY_KEY; ?>" href="javascript:;"><i class="fas fa-edit"></i> <?= __("Edit QSO"); ?></a></p></div>
                            <div style="display: inline-block;"><form method="POST" action="<?php echo site_url('search'); ?>"><input type="hidden" value="<?php echo strtoupper($row->COL_CALL); ?>" name="callsign"><button class="btn btn-primary" type="submit"><i class="fas fa-eye"></i> <?= __("More QSOs"); ?></button></form></div>
                    <?php } ?>

                    <?php

                        if($row->COL_SAT_NAME != null) {
                            $twitter_band_sat = $row->COL_SAT_NAME." \u{1F6F0}\u{FE0F}";
                            if($row->COL_ANT_EL != null && $row->COL_ANT_AZ != null ) {
                                $twitter_band_sat .= " (".$row->COL_ANT_EL."° el / ".$row->COL_ANT_AZ."° az)";
                            }
                            $hashtags = "#hamr #wavelog #amsat";
                        } else {
                            $twitter_band_sat = $row->COL_BAND;
                            $hashtags = "#hamr #wavelog";
                        }
                        if($row->COL_IOTA != null) {
                            $hashtags .= " #IOTA ".$row->COL_IOTA;
                        }
                        if($row->COL_SOTA_REF != null) {
                            $hashtags .= " #SOTA ".$row->COL_SOTA_REF;
                        }
                        if($row->COL_POTA_REF != null) {
                            $hashtags .= " #POTA ".$row->COL_POTA_REF;
                        }
                        if($row->COL_WWFF_REF != null) {
                            $hashtags .= " #WWFF ".$row->COL_WWFF_REF;
                        }
                        if($row->COL_SIG != null && $row->COL_SIG_INFO != null) {
                            $hashtags .= " #".$row->COL_SIG." ".$row->COL_SIG_INFO;
                        }
                        if (!isset($distance)) {
                            $twitter_string = urlencode("Just worked ".$row->COL_CALL." ");
                            if ($row->COL_DXCC != 0) {
                               $twitter_string .= urlencode("in ".ucwords(strtolower(($row->COL_COUNTRY)))." ");
                            }
                            $twitter_string .= urlencode("on ".$twitter_band_sat." using ".($row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE)." ".$hashtags);
                        } else {
                            $twitter_string = urlencode("Just worked ".$row->COL_CALL." ");
                            if ($row->COL_DXCC != 0) {
                               $twitter_string .= urlencode("in ".ucwords(strtolower(($row->COL_COUNTRY)))." ");
                               if ($dxccFlag != null) {
                                  $twitter_string .= $dxccFlag." ";
                               }
                            }
                            $distancestring = '';
                            if ($row->COL_VUCC_GRIDS == null) {
                               $distancestring = "(Gridsquare: ".$row->COL_GRIDSQUARE." / distance: ".$distance.")";
                            } else {
                               if (substr_count($row->COL_VUCC_GRIDS, ',') == 1) {
                                  $distancestring = "(Gridline: ".preg_replace('/\s+/', '', $row->COL_VUCC_GRIDS)." / distance: ".$distance.")";
                               } else if (substr_count($row->COL_VUCC_GRIDS, ',') == 3) {
                                  $distancestring = "(Gridcorner: ".preg_replace('/\s+/', '', $row->COL_VUCC_GRIDS)." / distance: ".$distance.")";
                               } else {
                                  $distancestring = "(Grids: ".$row->COL_VUCC_GRIDS.")";
                               }
                            }
                            $twitter_string .= urlencode($distancestring." on ".$twitter_band_sat." using ".($row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE)." ".$hashtags);
                        }
                    ?>

                    <div style="display: inline-block;"><a class="btn btn-primary twitter-share-button" target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo $twitter_string; ?>"><i class="fab fa-twitter"></i> Tweet</a></div>
                    <?php if($this->session->userdata('user_mastodon_url') != null) { echo '<div style="display: inline-block;"><a class="btn btn-primary twitter-share-button" target="_blank" href="'.$this->session->userdata('user_mastodon_url').'/share?text='.$twitter_string.'"><i class="fab fa-mastodon"></i> Toot</a></div>'; } ?>

                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="stationdetails" role="tabpanel" aria-labelledby="table-tab">
            <h3><?= __("Station") . ' ' . __("Details"); ?></h3>

            <table width="100%">
                    <tr>
                        <td><?= __("Station") . ' ' . __("Callsign"); ?></td>
                        <td><?php echo str_replace("0","&Oslash;",strtoupper($row->station_callsign)); ?></td>
                    </tr>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Name"); ?></td>
                        <td><?php echo $row->station_profile_name; ?></td>
                    </tr>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Gridsquare"); ?></td>
                        <td><?php echo $row->station_gridsquare; ?></td>
                    </tr>

                    <?php if($row->station_city) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("City"); ?></td>
                        <td><?php echo $row->station_city; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_country) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Country"); ?></td>
                        <td><?php echo ucwords(strtolower(($row->station_country)), "- (/"); if ($row->station_end != null) echo ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_OPERATOR) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Operator"); ?></td>
                        <td><?php echo $row->COL_OPERATOR; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->COL_TX_PWR) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Transmit Power (W)"); ?></td>
                        <td><?php echo $row->COL_TX_PWR; ?> W</td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_iota) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("IOTA Reference"); ?></td>
                        <td><?php echo $row->station_iota; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_sota) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("SOTA Reference"); ?></td>
                        <td><?php echo $row->station_sota; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_wwff) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("WWFF Reference"); ?></td>
                        <td><?php echo $row->station_wwff; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_pota) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("POTA Reference(s)"); ?></td>
                        <td><?php echo $row->station_pota; ?></td>
                    </tr>
                    <?php } ?>

                    <?php if($row->station_sig) { ?>
                    <tr>
                        <td><?= __("Station") . ' ' . __("Sig"); ?></td>
                        <td><?php echo $row->station_sig; ?></td>
                    </tr>

                    <tr>
                        <td><?= __("Station") . ' ' . __("Sig Info"); ?></td>
                        <td><?php echo $row->station_sig_info; ?></td>
                    </tr>
                    <?php } ?>
            </table>
        </div>

        <div class="tab-pane fade" id="notesdetails" role="tabpanel" aria-labelledby="table-tab">
            <h3><?= __("Notes"); ?></h3>
            <?php if (isset($row->COL_NOTES)) { echo nl2br($row->COL_NOTES); } ?>
        </div>

        <?php
        if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) {
        ?>
        <div class="tab-pane fade" id="qslupload" role="tabpanel" aria-labelledby="table-tab">
            <?php
	    if (!($this->config->item('disable_qsl') ?? false)) {
            if (count($qslimages) > 0) {
            echo '<table style="width:100%" class="qsltable table table-sm table-bordered table-hover table-striped table-condensed">
                <thead>
                <tr>
                    <th style=\'text-align: center\'>' . __("QSL image file") . '</th>
                    <th style=\'text-align: center\'></th>
                    <th style=\'text-align: center\'></th>
                </tr>
                </thead><tbody>';

                foreach ($qslimages as $qsl) {
                echo '<tr>';
                    echo '<td style=\'text-align: center\'>' . $qsl->filename . '</td>';
                    echo '<td id="'.$qsl->id.'" style=\'text-align: center\'><button onclick="deleteQsl('.$qsl->id.')" class="btn btn-sm btn-danger">' . __("Delete") . '</button></td>';
                    echo '<td style=\'text-align: center\'><button onclick="viewQsl(\''.$qsl->filename.'\')" class="btn btn-sm btn-success">' . __("View") . '</button></td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            }
            ?>

            <p><div class="alert alert-warning" role="alert"><span class="badge text-bg-warning"><?= __("Warning"); ?></span> <?= __("Maximum file upload size is "); ?> <?php echo $max_upload; ?>B.</div></p>

            <form class="form" id="fileinfo" name="fileinfo" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md">
                        <fieldset>

                            <div class="mb-3">
                                <label for="qslcardfront"><?= __("Uploaded QSL Card front image"); ?></label>
                                <input class="form-control" type="file" id="qslcardfront" name="qslcardfront" accept="image/*" >
                            </div>

                            <input type="hidden" class="form-control" id="qsoinputid" name="qsoid" value="<?php echo $row->COL_PRIMARY_KEY; ?>">
                            <button type="button" onclick="uploadQsl();" id="button1id"  name="button1id" class="btn btn-primary"><?= __("Upload QSL Card image"); ?></button>

                </div>
                <div class="col-md">
                            <div class="mb-3">
                                <label for="qslcardback"><?= __("Uploaded QSL Card back image"); ?></label>
                                <input class="form-control" type="file" id="qslcardback" name="qslcardback" accept="image/*">
                            </div>

                        </fieldset>
                    </div>
                </div>
            </form>
	    <?php } ?>
            <p>
            <div class="row">
                <div class="col-md">
                        <button type="button" onclick="qsl_rcvd(<?php echo $row->COL_PRIMARY_KEY; ?>, 'B');" id="qslrxb"  name="qslrxb" class="btn btn-sm btn-success ld-ext-right ld-ext-right-r-B"><i class="fas fa-envelope"></i> <?= __("Mark QSL Received (Bureau)"); ?> <div class="ld ld-ring ld-spin"></div></button>

                        <button type="button" onclick="qsl_rcvd(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D');" id="qslrxd"  name="qslrxd" class="btn btn-sm btn-success ld-ext-right ld-ext-right-r-D"><i class="fas fa-envelope"></i> <?= __("Mark QSL Received (Direct)"); ?> <div class="ld ld-ring ld-spin"></div></button>

                        <button type="button" onclick="qsl_rcvd(<?php echo $row->COL_PRIMARY_KEY; ?>, 'E');" id="qslrxe"  name="qslrxe" class="btn btn-sm btn-success ld-ext-right ld-ext-right-r-E"><i class="fas fa-envelope"></i> <?= __("Mark QSL Received (Electronic)"); ?> <div class="ld ld-ring ld-spin"></div></button>
                </div>
            </div>
            <p>
            <div class="row">
                <div class="col-md">
                        <button type="button" onclick="qsl_requested(<?php echo $row->COL_PRIMARY_KEY; ?>, 'B');" id="qsltxb"  name="qsltxb" class="btn btn-sm btn-warning ld-ext-right ld-ext-right-t-B"><i class="fas fa-envelope"></i> <?= __("Mark QSL Card Requested (Bureau)"); ?> <div class="ld ld-ring ld-spin"></div></button>

                        <button type="button" onclick="qsl_requested(<?php echo $row->COL_PRIMARY_KEY; ?>, 'D');" id="qsltxd"  name="qsltxd" class="btn btn-sm btn-warning ld-ext-right ld-ext-right-t-D"><i class="fas fa-envelope"></i> <?= __("Mark QSL Card Requested (Direct)"); ?> <div class="ld ld-ring ld-spin"></div></button>

                        <button type="button" onclick="qsl_ignore(<?php echo $row->COL_PRIMARY_KEY; ?>, 'I');" id="qsltxi"  name="qsltxi" class="btn btn-sm btn-warning ld-ext-right ld-ext-right-ignore"><i class="fas fa-envelope"></i> <?= __("Mark QSL Card Not Required"); ?> <div class="ld ld-ring ld-spin"></div></button>

                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="qslcard" role="tabpanel" aria-labelledby="table-tab">
            <?php $this->load->view('qslcard/qslcarousel', $qslimages); ?>
        </div>

        <div class="tab-pane fade" id="eqslcard" role="tabpanel" aria-labelledby="table-tab">
        <?php
	    if ($row->eqsl_image_file != null) {
		    echo '<img class="d-block" src="' . base_url() . '/'. $this->paths->getPathEqsl() .'/' . $row->eqsl_image_file .'" alt="' . __("eQSL picture") . '">';
	    }
        ?>
        </div>
        <?php
        }
        ?>
</div>
</div>

<?php
	if($row->COL_GRIDSQUARE != null && strlen($row->COL_GRIDSQUARE) >= 4) {
		$stn_loc = $this->qra->qra2latlong(trim($row->COL_GRIDSQUARE));
        if($stn_loc[0] != 0) {
		    $lat = $stn_loc[0];
		    $lng = $stn_loc[1];
        }
    } elseif($row->COL_VUCC_GRIDS != null) {
        $grids = explode(",", $row->COL_VUCC_GRIDS);
        if (count($grids) == 2) {
            $grid1 = $this->qra->qra2latlong(trim($grids[0]));
            $grid2 = $this->qra->qra2latlong(trim($grids[1]));

            $coords[]=array('lat' => $grid1[0],'lng'=> $grid1[1]);
            $coords[]=array('lat' => $grid2[0],'lng'=> $grid2[1]);

            $midpoint = $this->qra->get_midpoint($coords);
            $lat = $midpoint[0];
		    $lng = $midpoint[1];
        }
        if (count($grids) == 4) {
            $grid1 = $this->qra->qra2latlong(trim($grids[0]));
            $grid2 = $this->qra->qra2latlong(trim($grids[1]));
            $grid3 = $this->qra->qra2latlong(trim($grids[2]));
            $grid4 = $this->qra->qra2latlong(trim($grids[3]));

            $coords[]=array('lat' => $grid1[0],'lng'=> $grid1[1]);
            $coords[]=array('lat' => $grid2[0],'lng'=> $grid2[1]);
            $coords[]=array('lat' => $grid3[0],'lng'=> $grid3[1]);
            $coords[]=array('lat' => $grid4[0],'lng'=> $grid4[1]);

            $midpoint = $this->qra->get_midpoint($coords);
            $lat = $midpoint[0];
		    $lng = $midpoint[1];
        }
	} else {
        if(isset($row->lat)) {
			$lat = $row->lat;
        } else {
            $lat = 0;
        }

        if(isset($row->long)) {
			$lng = $row->long;
        } else {
            $lng = 0;
        }
	}
?>

<script>
var lat = <?php echo $lat; ?>;
var long = <?php echo $lng; ?>;
var callsign = "<?php echo $row->COL_CALL; ?>";
</script>
    <div hidden id ='lat'><?php echo $lat; ?></div>
    <div hidden id ='long'><?php echo $lng; ?></div>
    <div hidden id ='callsign'><?php echo $row->COL_CALL; ?></div>
    <div hidden id ='qsoid'><?php echo $row->COL_PRIMARY_KEY; ?></div>

<?php } } ?>
