<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <?php if($this->optionslib->get_theme()) { ?>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme();?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme();?>/overrides.css">
	<?php } ?>

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery.fancybox.min.css" />

    <script src="<?php echo base_url(); ?>assets/js/jquery-3.3.1.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery.fancybox.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
</head>

<body class="container-fluid qso-edit-box">

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <?php echo validation_errors(); ?>
            <form name="qsos" id="qsoform">
                <div class="card">
                    <div class="card-header">
                        <nav class="card-header-tabs">
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <a class="nav-item nav-link active" id="nav-qso-tab" data-bs-toggle="tab" href="#nav-qso" role="tab" aria-controls="nav-qso" aria-selected="true">QSO</a>
                                <a class="nav-item nav-link" id="nav-satellites-tab" data-bs-toggle="tab" href="#nav-satellites" role="tab" aria-controls="nav-awards" aria-selected="true">Sats</a>
                                <a class="nav-item nav-link" id="nav-awards-tab" data-bs-toggle="tab" href="#nav-awards" role="tab" aria-controls="nav-awards" aria-selected="true">Awards</a>
                                <a class="nav-item nav-link" id="nav-qso-notes-tab" data-bs-toggle="tab" href="#nav-qso-notes" role="tab" aria-controls="nav-qso-notes" aria-selected="false">Notes</a>
                                <a class="nav-item nav-link" id="nav-qsl-tab" data-bs-toggle="tab" href="#nav-qsl" role="tab" aria-controls="nav-qsl" aria-selected="false">QSL</a>
                                <a class="nav-item nav-link" id="nav-station-tab" data-bs-toggle="tab" href="#nav-station" role="tab" aria-controls="nav-station" aria-selected="false">Station</a>
								<a class="nav-item nav-link" id="nav-contest-tab" data-bs-toggle="tab" href="#nav-contest" role="tab" aria-controls="nav-contest" aria-selected="false">Contest</a>
                            </div>
                        </nav>

                    </div>

                    <div class="card-body">

                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-qso" role="tabpanel" aria-labelledby="nav-qso-tab">
                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="start_date">Start Date/Time</label>
                                        <input type="text" class="form-control form-control-sm" name="time_on" id="time_on" value="<?php echo $qso->COL_TIME_ON; ?>">
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                        <label for="start_time">End Date/Time</label>
                                        <input type="text" class="form-control form-control-sm" name="time_off" id="time_off" value="<?php echo $qso->COL_TIME_OFF; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="callsign">Callsign</label>
                                        <input type="text" class="form-control" id="callsign" name="callsign" value="<?php echo $qso->COL_CALL; ?>">
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="freq">Frequency</label>
                                        <input type="text" class="form-control" id="freq" name="freq" value="<?php echo $qso->COL_FREQ; ?>">
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                    <label for="freq">RX Frequency</label>
                                    <input type="text" class="form-control" id="freqrx" name="freq_display_rx" value="<?php if($qso->COL_FREQ_RX != "0") { echo $qso->COL_FREQ_RX; } ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="freq">Band</label>
                                        <select id="band" class="form-select form-select-sm" name="band">
                                        <?php foreach($bands as $key=>$bandgroup) {
                                            echo '<optgroup label="' . strtoupper($key) . '">';
                                            foreach($bandgroup as $band) {
                                                echo '<option value="' . $band . '"';
                                                if (strtolower($qso->COL_BAND ?? '') == $band) echo ' selected';
                                                echo '>' . $band . '</option>'."\n";
                                            }
                                            echo '</optgroup>';
                                            }
                                        ?>
                                        </select>
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                        <label for="freq">RX Band</label>
                                        <select id="band_rx" class="form-select form-select-sm" name="band_rx">
                                            <option value="" <?php if(strtolower($qso->COL_BAND_RX == "")) { echo "selected=\"selected\""; } ?>></option>
                                            <?php foreach($bands as $key=>$bandgroup) {
                                            echo '<optgroup label="' . strtoupper($key) . '">';
                                            foreach($bandgroup as $band) {
                                                echo '<option value="' . $band . '"';
                                                if (strtolower($qso->COL_BAND_RX ?? '') == $band) echo ' selected';
                                                echo '>' . $band . '</option>'."\n";
                                            }
                                            echo '</optgroup>';
                                            }
                                        ?>
                                        </select>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="freq">Mode</label>
                                        <select id="mode" class="form-select mode form-select-sm" name="mode">
                                            <?php
                                            foreach($modes->result() as $mode){
                                                var_dump($mode);
                                                if ($mode->submode == null) {
                                                    printf("<option value=\"%s\" %s>%s</option>", $mode->mode, $qso->COL_MODE==$mode->mode?"selected=\"selected\"":"",$mode->mode);
                                                } else {
                                                    printf("<option value=\"%s\" %s>&rArr; %s</option>", $mode->submode, $qso->COL_SUBMODE==$mode->submode?"selected=\"selected\"":"",$mode->submode);
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-sm6">
              		                    <label for="transmit_power">Transmit Power (W)</label>
              		                    <input type="number" step="0.001" class="form-control" id="transmit_power" name="transmit_power" value="<?php echo $qso->COL_TX_PWR; ?>" />
					                    <small id="powerHelp" class="form-text text-muted">Give power value in Watts. Include only numbers in the input.</small>
					                </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="rst_sent">RST (S)</label>
                                        <input type="text" class="form-control form-control-sm" name="rst_sent" id="rst_sent" value="<?php echo $qso->COL_RST_SENT; ?>">
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                        <label for="rst_rcvd">RST (R)</label>
                                        <input type="text" class="form-control form-control-sm" name="rst_rcvd" id="rst_rcvd" value="<?php echo $qso->COL_RST_RCVD; ?>">
                                    </div>
                                </div>



                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="locator">Gridsquare</label>
                                        <input type="text" class="form-control" id="locator" name="locator" value="<?php echo $qso->COL_GRIDSQUARE; ?>">
                                        <small id="locator_info" class="form-text text-muted"><?php if ($qso->COL_DISTANCE != "") echo $qso->COL_DISTANCE." km"; ?></small>
                                    </div>

                                    <input type="hidden" name="distance" id="distance" value="<?php print ($qso->COL_DISTANCE != "") ? $qso->COL_DISTANCE : "0"; ?>">

                                    <div class="mb-3 col-sm-6">
                                        <label for="vucc_grids">VUCC Gridsquare</label>
                                        <input type="text" class="form-control" id="vucc_grids" name="vucc_grids" value="<?php echo $qso->COL_VUCC_GRIDS; ?>">
                                        <p>Used for VUCC MultiGrids</p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $qso->COL_NAME; ?>">
                                    </div>

                                    <div class="mb-3 col-sm-6">
                                        <label for="qth">QTH</label>
                                        <input type="text" class="form-control" id="qth" name="qth" value="<?php echo $qso->COL_QTH; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="comment">Comment</label>
                                    <input type="text" class="form-control" id="comment" name="comment" value="<?php echo htmlspecialchars($qso->COL_COMMENT ? $qso->COL_COMMENT : '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="prop_mode">Propagation Mode</label>
                                        <select class="form-select" id="prop_mode" name="prop_mode">
                                            <option value="" <?php if($qso->COL_PROP_MODE == "") { echo "selected=\"selected\""; } ?>></option>
                                            <option value="AS" <?php if($qso->COL_PROP_MODE == "AS") { echo "selected=\"selected\""; } ?>>Aircraft Scatter</option>
                                            <option value="AUR" <?php if($qso->COL_PROP_MODE == "AUR") { echo "selected=\"selected\""; } ?>>Aurora</option>
                                            <option value="AUE" <?php if($qso->COL_PROP_MODE == "AUE") { echo "selected=\"selected\""; } ?>>Aurora-E</option>
                                            <option value="BS" <?php if($qso->COL_PROP_MODE == "BS") { echo "selected=\"selected\""; } ?>>Back scatter</option>
                                            <option value="ECH" <?php if($qso->COL_PROP_MODE == "ECH") { echo "selected=\"selected\""; } ?>>EchoLink</option>
                                            <option value="EME" <?php if($qso->COL_PROP_MODE == "EME") { echo "selected=\"selected\""; } ?>>Earth-Moon-Earth</option>
                                            <option value="ES" <?php if($qso->COL_PROP_MODE == "ES") { echo "selected=\"selected\""; } ?>>Sporadic E</option>
                                            <option value="FAI" <?php if($qso->COL_PROP_MODE == "FAI") { echo "selected=\"selected\""; } ?>>Field Aligned Irregularities</option>
                                            <option value="F2" <?php if($qso->COL_PROP_MODE == "F2") { echo "selected=\"selected\""; } ?>>F2 Reflection</option>
                                            <option value="INTERNET" <?php if($qso->COL_PROP_MODE == "INTERNET") { echo "selected=\"selected\""; } ?>>Internet-assisted</option>
                                            <option value="ION" <?php if($qso->COL_PROP_MODE == "ION") { echo "selected=\"selected\""; } ?>>Ionoscatter</option>
                                            <option value="IRL" <?php if($qso->COL_PROP_MODE == "IRL") { echo "selected=\"selected\""; } ?>>IRLP</option>
                                            <option value="MS" <?php if($qso->COL_PROP_MODE == "MS") { echo "selected=\"selected\""; } ?>>Meteor scatter</option>
                                            <option value="RPT" <?php if($qso->COL_PROP_MODE == "RPT") { echo "selected=\"selected\""; } ?>>Terrestrial or atmospheric repeater or transponder</option>
                                            <option value="RS" <?php if($qso->COL_PROP_MODE == "RS") { echo "selected=\"selected\""; } ?>>Rain scatter</option>
                                            <option value="SAT" <?php if($qso->COL_PROP_MODE == "SAT") { echo "selected=\"selected\""; } ?>>Satellite</option>
                                            <option value="TEP" <?php if($qso->COL_PROP_MODE == "TEP") { echo "selected=\"selected\""; } ?>>Trans-equatorial</option>
                                            <option value="TR" <?php if($qso->COL_PROP_MODE == "TR") { echo "selected=\"selected\""; } ?>>Tropospheric ducting</option>
                                        </select>
                                    </div>

                                    <input type="hidden" class="form-control" id="country" name="country" value="<?php echo $qso->COL_COUNTRY; ?>">

                                </div>
                                <div class="row">
                                    <div class="mb-3 col-sm-6">
                                        <label for="dxcc_id">DXCC</label>
                                        <select class="form-select" id="dxcc_id" name="dxcc_id" required>
                                            <option value="0">- NONE -</option>
                                            <?php
                                            foreach($dxcc as $d){
                                                echo '<option value=' . $d->adif;
                                                if ($qso->COL_DXCC == $d->adif) {
                                                    echo " selected=\"selected\"";
                                                }
                                                echo '>' . $d->prefix . ' - ' . ucwords(strtolower(($d->name)));
                                                if ($d->Enddate != null) {
                                                    echo ' ('.__("Deleted DXCC").')';
                                                }
                                                echo '</option>';
                                            }
                                            ?>

                                        </select>
                                    </div>
                                    <div class="mb-3 col-sm-6">
                                        <label for="continent"><?php echo __("Continent"); ?></label>
                                        <select class="form-select" id="continent" name="continent">
                                            <option value=""></option>
                                            <option value="AF" <?php if($qso->COL_CONT == "AF") { echo "selected=\"selected\""; } ?>><?php echo __("Africa"); ?></option>
                                            <option value="AN" <?php if($qso->COL_CONT == "AN") { echo "selected=\"selected\""; } ?>><?php echo __("Antarctica"); ?></option>
                                            <option value="AS" <?php if($qso->COL_CONT == "AS") { echo "selected=\"selected\""; } ?>><?php echo __("Asia"); ?></option>
                                            <option value="EU" <?php if($qso->COL_CONT == "EU") { echo "selected=\"selected\""; } ?>><?php echo __("Europe"); ?></option>
                                            <option value="NA" <?php if($qso->COL_CONT == "NA") { echo "selected=\"selected\""; } ?>><?php echo __("North America"); ?></option>
                                            <option value="OC" <?php if($qso->COL_CONT == "OC") { echo "selected=\"selected\""; } ?>><?php echo __("Oceania"); ?></option>
                                            <option value="SA" <?php if($qso->COL_CONT == "SA") { echo "selected=\"selected\""; } ?>><?php echo __("South America"); ?></option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <!-- Satellite Panel Contents -->
                            <div class="tab-pane fade" id="nav-satellites" role="tabpanel" aria-labelledby="nav-satellites-tab">
                                <div class="mb-3">
                                    <label for="sat_name">Sat Name</label>
                                    <input type="text" class="form-control form-control-sm" name="sat_name" id="sat_name" value="<?php echo $qso->COL_SAT_NAME; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="sat_mode">Sat Mode</label>
                                    <input type="text" class="form-control form-control-sm" name="sat_mode" id="sat_mode" value="<?php echo $qso->COL_SAT_MODE; ?>">
                                </div>
                            </div>

                            <!-- Awards Panel Contents -->
                            <div class="tab-pane fade" id="nav-awards" role="tabpanel" aria-labelledby="nav-awards-tab">

                                <div class="mb-3">
                                    <label for="cqz">CQ Zone</label>
                                    <select class="form-select" id="cqz" name="cqz" required>
                                        <?php
                                        for ($i = 1; $i<=40; $i++) {
                                            echo '<option value='. $i;
                                            if ($qso->COL_CQZ == $i) {
                                                echo " selected=\"selected\"";
                                            }
                                            echo '>'. $i .'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <?php 
                                        $CI =& get_instance();
                                        $CI->load->library('subdivisions');

                                        $subdivision_name = $CI->subdivisions->get_primary_subdivision_name($qso->COL_DXCC);
                                        $state_list = $CI->subdivisions->get_state_list($qso->COL_DXCC);
                                    ?>
                                    
                                    <label for="stateDropdown" id="stateInputLabel"><?php echo $subdivision_name; ?></label>
                                    <select class="form-select" id="stateDropdown" name="input_state_edit">
                                        <option value=""></option>

                                        <?php foreach ($state_list->result() as $state) {
                                            $selected = ($qso->COL_STATE == $state->state) ? 'selected="selected"' : '';
                                        ?>
                                            <option value="<?php echo $state->state; ?>" <?php echo $selected; ?>>
                                                <?php echo $state->subdivision . ' (' . $state->state . ')'; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                
                                <?php if ($qso->COL_DXCC == '291' || $qso->COL_DXCC == '110'  || $qso->COL_DXCC == '006') { ?>
                                <div class="mb-3" id="location_us_county">
                                    <label for="stationCntyInput">USA County</label>
                                    <input class="form-control" id="stationCntyInputEdit" type="text" name="usa_county" value="<?php echo $qso->COL_CNTY; ?>" />
                                </div>
                                <?php } ?> 

                                <div class="mb-3">
                                    <label for="iota_ref">IOTA</label>
                                    <select class="form-select" id="iota_ref" name="iota_ref">
                                        <option value =""></option>

                                        <?php
                                        foreach($iota as $i){
                                            echo '<option value=' . $i->tag;
                                            if ($qso->COL_IOTA == $i->tag) {
                                                echo " selected=\"selected\"";
                                            }
                                            echo '>' . $i->tag . ' - ' . $i->name . '</option>';
                                        }
                                        ?>

                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="sota_ref">SOTA</label>
                                    <input type="text" class="form-control" id="sota_ref_edit" name="sota_ref" value="<?php echo $qso->COL_SOTA_REF; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="wwff_ref">WWFF</label>
                                    <input type="text" class="form-control" id="wwff_ref_edit" name="wwff_ref" value="<?php echo $qso->COL_WWFF_REF; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="pota_ref">POTA</label>
                                    <input type="text" class="form-control" id="pota_ref_edit" name="pota_ref" value="<?php echo $qso->COL_POTA_REF; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="sig">Sig</label>
                                    <input type="text" class="form-control" id="sig" name="sig" value="<?php echo $qso->COL_SIG; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="sig_info">Sig Info</label>
                                    <input type="text" class="form-control" id="sig_info" name="sig_info" value="<?php echo $qso->COL_SIG_INFO; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="darc_dok">DOK</label>
                                    <input type="text" class="form-control" id="darc_dok_edit" name="darc_dok" value="<?php echo $qso->COL_DARC_DOK; ?>">
                                </div>
                            </div>

                            <!-- Notes Panel Contents -->
                            <div class="tab-pane fade" id="nav-qso-notes" role="tabpanel" aria-labelledby="nav-qso-notes-tab">
                                <div class="mb-3">
                                    <label for="notes">Notes (for internal usage only)</label>
                                    <textarea  type="text" class="form-control" id="notes" name="notes" rows="10"><?php echo $qso->COL_NOTES; ?></textarea>
                                </div>
                            </div>

                            <!-- QSL Panel Contents -->
                            <div class="tab-pane fade" id="nav-qsl" role="tabpanel" aria-labelledby="nav-qsl-tab">
                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">QSL Card</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">eQSL</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">LoTW</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                        <div class="mb-3 row">
                                            <label for="sent" class="col-sm-3 col-form-label">Sent</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="qsl_sent">
                                                    <option value="N" <?php if($qso->COL_QSL_SENT == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_QSL_SENT == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_QSL_SENT == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="Q" <?php if($qso->COL_QSL_SENT == "Q") { echo "selected=\"selected\""; } ?>>Queued</option>
                                                    <option value="I" <?php if($qso->COL_QSL_SENT == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="sent-method" class="col-sm-3 col-form-label">Sent Method</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="qsl_sent_method">
                                                    <option value="" <?php if($qso->COL_QSL_SENT_VIA == "") { echo "selected=\"selected\""; } ?>>Method</option>
                                                    <option value="D" <?php if($qso->COL_QSL_SENT_VIA == "D") { echo "selected=\"selected\""; } ?>>Direct</option>
                                                    <option value="B" <?php if($qso->COL_QSL_SENT_VIA == "B") { echo "selected=\"selected\""; } ?>>Bureau</option>
                                                    <option value="E" <?php if($qso->COL_QSL_SENT_VIA == "E") { echo "selected=\"selected\""; } ?>>Electronic</option>
                                                    <option value="M" <?php if($qso->COL_QSL_SENT_VIA == "M") { echo "selected=\"selected\""; } ?>>Manager</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="qsl-via" class="col-sm-2 col-form-label">Sent Via</label>
                                            <div class="col-sm-10">
                                                <input type="text" id="qsl-via" class="form-control" name="qsl_via_callsign" value="<?php echo $qso->COL_QSL_VIA; ?>" />
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="sent-method" class="col-sm-3 col-form-label">Received</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="qsl_rcvd">
                                                    <option value="N" <?php if($qso->COL_QSL_RCVD == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_QSL_RCVD == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_QSL_RCVD == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="I" <?php if($qso->COL_QSL_RCVD == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                    <option value="V" <?php if($qso->COL_QSL_RCVD == "V") { echo "selected=\"selected\""; } ?>>Verified (Match)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="sent-method" class="col-sm-3 col-form-label">Received Method</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="qsl_rcvd_method">
                                                    <option value="" <?php if($qso->COL_QSL_RCVD_VIA == "") { echo "selected=\"selected\""; } ?>>Method</option>
                                                    <option value="D" <?php if($qso->COL_QSL_RCVD_VIA == "D") { echo "selected=\"selected\""; } ?>>Direct</option>
                                                    <option value="B" <?php if($qso->COL_QSL_RCVD_VIA == "B") { echo "selected=\"selected\""; } ?>>Bureau</option>
                                                    <option value="E" <?php if($qso->COL_QSL_RCVD_VIA == "E") { echo "selected=\"selected\""; } ?>>Electronic</option>
                                                    <option value="M" <?php if($qso->COL_QSL_RCVD_VIA == "M") { echo "selected=\"selected\""; } ?>>Manager</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                        <div class="mb-3 row">
                                            <label for="sent" class="col-sm-3 col-form-label">Sent</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="eqsl_sent">
                                                    <option value="N" <?php if($qso->COL_EQSL_QSL_SENT == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_EQSL_QSL_SENT == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_EQSL_QSL_SENT == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="Q" <?php if($qso->COL_EQSL_QSL_SENT == "Q") { echo "selected=\"selected\""; } ?>>Queued</option>
                                                    <option value="I" <?php if($qso->COL_EQSL_QSL_SENT == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="sent" class="col-sm-3 col-form-label">Received</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="eqsl_rcvd">
                                                    <option value="N" <?php if($qso->COL_EQSL_QSL_RCVD == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_EQSL_QSL_RCVD == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_EQSL_QSL_RCVD == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="I" <?php if($qso->COL_EQSL_QSL_RCVD == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                    <option value="V" <?php if($qso->COL_EQSL_QSL_RCVD == "V") { echo "selected=\"selected\""; } ?>>Verified (Match)</option>
                                                </select></div>
                                        </div>
                                        <div class="mb-3 row">
                                            <div>
                                                <div class="alert alert-info" role="alert">
                                                    <span class="badge text-bg-info"><?php echo __("Info"); ?></span> <?php echo __("This note content is exported to QSL services like eqsl.cc."); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="qslmsg"><?php echo __("Notes"); ?><span class="qso_eqsl_qslmsg_update" title="<?php echo __("Get the default message for eQSL, for this station."); ?>"><i class="fas fa-redo-alt"></i></span></label>
						                        <label class="position-absolute end-0 mb-2 me-3" for="qslmsg" id="charsLeft"> </label>
                                                <textarea  type="text" class="form-control" id="qslmsg" name="qslmsg" rows="5" maxlength="240"><?php echo $qso->COL_QSLMSG; ?></textarea>
                                                <div id="qslmsg_hide" style="display:none;"><?php echo $qso->COL_QSLMSG; ?></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                        <div class="mb-3 row">
                                            <label for="sent" class="col-sm-3 col-form-label">Sent</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="lotw_sent">
                                                    <option value="N" <?php if($qso->COL_LOTW_QSL_SENT == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_LOTW_QSL_SENT == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_LOTW_QSL_SENT == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="Q" <?php if($qso->COL_LOTW_QSL_SENT == "Q") { echo "selected=\"selected\""; } ?>>Queued</option>
                                                    <option value="I" <?php if($qso->COL_LOTW_QSL_SENT == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                </select></div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label for="sent" class="col-sm-3 col-form-label">Received</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" name="lotw_rcvd">
                                                    <option value="N" <?php if($qso->COL_LOTW_QSL_RCVD == "N") { echo "selected=\"selected\""; } ?>>No</option>
                                                    <option value="Y" <?php if($qso->COL_LOTW_QSL_RCVD == "Y") { echo "selected=\"selected\""; } ?>>Yes</option>
                                                    <option value="R" <?php if($qso->COL_LOTW_QSL_RCVD == "R") { echo "selected=\"selected\""; } ?>>Requested</option>
                                                    <option value="I" <?php if($qso->COL_LOTW_QSL_RCVD == "I") { echo "selected=\"selected\""; } ?>>Invalid (Ignore)</option>
                                                    <option value="V" <?php if($qso->COL_LOTW_QSL_RCVD == "V") { echo "selected=\"selected\""; } ?>>Verified (Match)</option>
                                                </select></div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Station Panel Contents -->
                            <div class="tab-pane fade" id="nav-station" role="tabpanel" aria-labelledby="nav-station-tab">

                                <?php
                                $CI =& get_instance();
                                $CI->load->model('stations');
                                $my_stations = $CI->stations->all_of_user();
                                ?>

                                <div class="mb-3">
                                    <label for="inputStationProfile">Change Station Profile</label>
                                    <select id="stationProfile" class="form-select" name="station_profile">
                                        <?php foreach ($my_stations->result() as $stationrow) { ?>
                                            <option value="<?php echo $stationrow->station_id; ?>" <?php if($qso->station_id == $stationrow->station_id) { echo "selected=\"selected\""; } ?>><?php echo $stationrow->station_profile_name; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="operatorCallsign">Operator Callsign</label>
                                    <input type="text" id="operatorCallsign" class="form-control" name="operator_callsign" value="<?php echo $qso->COL_OPERATOR; ?>" />
                                </div>


                            </div>
							<!-- Contest Panel Contents -->
							<div class="tab-pane fade" id="nav-contest" role="tabpanel" aria-labelledby="nav-contest-tab">
								<div class="mb-3">
									<label for="contest_name">Contest Name</label>
									<select class="form-select" id="contest_name" name="contest_name">
										<option value =""></option>

										<?php
										foreach($contest as $c) {
											echo '<option value=' . $c['adifname'];
											if ($qso->COL_CONTEST_ID == $c['adifname']) {
												echo " selected=\"selected\"";
											}
											echo '>' . $c['name'] . '</option>';
										}
										?>

									</select>
								</div>
								<div class="row">
									<div class="mb-3 col-sm-3">
										<label for="srx">Serial (R)</label>
										<input type="text" id="srx" class="form-control" name="srx" value="<?php echo $qso->COL_SRX; ?>" />
									</div>

									<div class="mb-3 col-sm-3">
										<label for="stx">Serial (S)</label>
										<input type="text" id="stx" class="form-control" name="stx" value="<?php echo $qso->COL_STX; ?>" />
									</div>

									<div class="mb-3 col-sm-3">
										<label for="srx_string">Exchange (R)</label>
										<input type="text" id="srx_string" class="form-control" name="srx_string" value="<?php echo $qso->COL_SRX_STRING; ?>" />
									</div>

									<div class="mb-3 col-sm-3">
										<label for="stx_string">Exchange (S)</label>
										<input type="text" id="stx_string" class="form-control" name="stx_string" value="<?php echo $qso->COL_STX_STRING; ?>" />
									</div>
								</div>



                        	</div>

                        <input type="hidden" name="id" value="<?php echo $qso->COL_PRIMARY_KEY; ?>" />

                        <div class="actions">
                            <a class="btn btn-danger" href="javascript:qso_delete(<?php echo $qso->COL_PRIMARY_KEY; ?>, '<?php echo $qso->COL_CALL; ?>')"><i class="fas fa-trash-alt"></i> Delete QSO</a>
                            <button id="show" type="button" name="download" class="btn btn-primary float-end" onclick="qso_save();"><i class="fas fa-save"></i> Save changes</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>

</div>
