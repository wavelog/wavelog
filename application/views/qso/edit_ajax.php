<!-- <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"> -->
<head>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <?php if ($this->optionslib->get_theme()) { ?>
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme(); ?>/bootstrap.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme(); ?>/overrides.css">
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
                                    <a class="nav-item nav-link active" id="nav-qso-tab" data-bs-toggle="tab" href="#nav-qso" role="tab" aria-controls="nav-qso" aria-selected="true"><?= __("QSO"); ?></a>
                                    <a class="nav-item nav-link" id="nav-satellites-tab" data-bs-toggle="tab" href="#nav-satellites" role="tab" aria-controls="nav-awards" aria-selected="true"><?= __("Sats"); ?></a>
                                    <a class="nav-item nav-link" id="nav-awards-tab" data-bs-toggle="tab" href="#nav-awards" role="tab" aria-controls="nav-awards" aria-selected="true"><?= __("Awards"); ?></a>
                                    <a class="nav-item nav-link" id="nav-qso-notes-tab" data-bs-toggle="tab" href="#nav-qso-notes" role="tab" aria-controls="nav-qso-notes" aria-selected="false"><?= __("Notes"); ?></a>
                                    <?php if (clubaccess_check(9)) { ?>
                                    <a class="nav-item nav-link" id="nav-qsl-edit-tab" data-bs-toggle="tab" href="#nav-qsl-edit" role="tab" aria-controls="nav-qsl-edit" aria-selected="false"><?= __("QSL"); ?></a>
                                    <?php } ?>
                                    <a class="nav-item nav-link" id="nav-station-tab" data-bs-toggle="tab" href="#nav-station" role="tab" aria-controls="nav-station" aria-selected="false"><?= __("Station"); ?></a>
                                    <a class="nav-item nav-link" id="nav-contest-tab" data-bs-toggle="tab" href="#nav-contest" role="tab" aria-controls="nav-contest" aria-selected="false"><?= __("Contest"); ?></a>
                                </div>
                            </nav>

                        </div>

                        <div class="card-body">

                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-qso" role="tabpanel" aria-labelledby="nav-qso-tab">
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="start_date"><?= __("Start Date/Time"); ?></label>
                                            <input type="text" class="form-control" name="time_on" id="time_on" value="<?php echo $qso->COL_TIME_ON; ?>">
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                            <label for="start_time"><?= __("End Date/Time"); ?></label>
                                            <input type="text" class="form-control" name="time_off" id="time_off" value="<?php echo $qso->COL_TIME_OFF; ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="callsign"><?= __("Callsign"); ?></label>
                                            <input type="text" class="form-control uppercase" id="edit_callsign" name="callsign" value="<?php echo $qso->COL_CALL; ?>">
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="freq"><?= __("Frequency"); ?></label>
                                            <input type="text" class="form-control" id="freq" name="freq" value="<?php echo $qso->COL_FREQ; ?>">
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                            <label for="freq"><?= __("RX Frequency"); ?></label>
                                            <input type="text" class="form-control" id="freqrx" name="freq_display_rx" value="<?php if ($qso->COL_FREQ_RX != "0") { echo $qso->COL_FREQ_RX; } ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="freq"><?= __("Band"); ?></label>
                                            <select id="edit_band" class="form-select" name="band">
                                                <?php foreach ($bands as $key => $bandgroup) {
                                                    echo '<optgroup label="' . strtoupper($key) . '">';
                                                    foreach ($bandgroup as $band) {
                                                        echo '<option value="' . $band . '"';
                                                        if (strtolower($qso->COL_BAND ?? '') == $band) echo ' selected';
                                                        echo '>' . $band . '</option>' . "\n";
                                                    }
                                                    echo '</optgroup>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                            <label for="freq"><?= __("RX Band"); ?></label>
                                            <select id="band_rx" class="form-select" name="band_rx">
                                                <option value="" <?php if (strtolower($qso->COL_BAND_RX == "")) { echo "selected=\"selected\""; } ?>></option>
                                                <?php foreach ($bands as $key => $bandgroup) {
                                                    echo '<optgroup label="' . strtoupper($key) . '">';
                                                    foreach ($bandgroup as $band) {
                                                        echo '<option value="' . $band . '"';
                                                        if (strtolower($qso->COL_BAND_RX ?? '') == $band) echo ' selected';
                                                        echo '>' . $band . '</option>' . "\n";
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
                                            <label for="freq"><?= __("Mode"); ?></label>
                                            <select id="edit_mode" class="form-select mode" name="mode">
                                                <?php
                                                foreach ($modes->result() as $mode) {
                                                    var_dump($mode);
                                                    if ($mode->submode == null) {
                                                        printf("<option value=\"%s\" %s>%s</option>", $mode->mode, $qso->COL_MODE == $mode->mode ? "selected=\"selected\"" : "", $mode->mode);
                                                    } else {
                                                        printf("<option value=\"%s\" %s>&rArr; %s</option>", $mode->submode, $qso->COL_SUBMODE == $mode->submode ? "selected=\"selected\"" : "", $mode->submode);
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="transmit_power"><?= __("Transmit Power (W)"); ?> <i id="power_tooltip" data-bs-toggle="tooltip" data-bs-placement="right" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Give power value in Watts. Include only numbers in the input."); ?>"></i></label>
                                            <input type="number" step="0.001" class="form-control" id="transmit_power" name="transmit_power" value="<?php echo $qso->COL_TX_PWR; ?>" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="rst_sent"><?= __("RST (S)"); ?></label>
                                            <input type="text" class="form-control" name="rst_sent" id="rst_sent" value="<?php echo $qso->COL_RST_SENT; ?>">
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                            <label for="rst_rcvd"><?= __("RST (R)"); ?></label>
                                            <input type="text" class="form-control" name="rst_rcvd" id="rst_rcvd" value="<?php echo $qso->COL_RST_RCVD; ?>">
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="locator"><?= __("Gridsquare"); ?></label>
                                            <input type="text" class="form-control uppercase" id="locator_edit" name="locator" value="<?php echo $qso->COL_GRIDSQUARE; ?>">
                                            <small id="locator_info_edit" class="form-text text-muted"><?php if ($qso->COL_DISTANCE != "") echo $qso->COL_DISTANCE . " km"; ?></small>
                                        </div>

                                        <input type="hidden" name="distance" id="distance" value="<?php print ($qso->COL_DISTANCE != "") ? $qso->COL_DISTANCE : "0"; ?>">

                                        <div class="mb-3 col-sm-6">
                                            <label for="vucc_grids"><?= __("VUCC Gridsquare"); ?> <i id="vucc_grid_tooltip" data-bs-toggle="tooltip" data-bs-placement="right" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Used for VUCC MultiGrids"); ?>"></i></label>
                                            <input type="text" class="form-control uppercase" id="vucc_grids" name="vucc_grids" value="<?php echo $qso->COL_VUCC_GRIDS; ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="name"><?= __("Name"); ?></label>
                                            <input type="text" class="form-control" id="name_edit" name="name" value="<?php echo $qso->COL_NAME; ?>">
                                        </div>

                                        <div class="mb-3 col-sm-6">
                                            <label for="qth"><?= __("QTH"); ?></label>
                                            <input type="text" class="form-control" id="qth_edit" name="qth" value="<?php echo $qso->COL_QTH; ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="comment"><?= __("Comment"); ?></label>
                                        <input type="text" class="form-control" id="comment" name="comment" value="<?php echo htmlspecialchars($qso->COL_COMMENT ? $qso->COL_COMMENT : '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="prop_mode"><?= __("Propagation Mode"); ?></label>
                                            <select class="form-select" id="prop_mode_edit" name="prop_mode">
                                                <option value="" <?php if ($qso->COL_PROP_MODE == "") { echo "selected=\"selected\""; } ?>></option>
                                                <option value="AS" <?php if ($qso->COL_PROP_MODE == "AS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Aircraft Scatter"); ?></option>
                                                <option value="AUR" <?php if ($qso->COL_PROP_MODE == "AUR") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Aurora"); ?></option>
                                                <option value="AUE" <?php if ($qso->COL_PROP_MODE == "AUE") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Aurora-E"); ?></option>
                                                <option value="BS" <?php if ($qso->COL_PROP_MODE == "BS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Back scatter"); ?></option>
                                                <option value="ECH" <?php if ($qso->COL_PROP_MODE == "ECH") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "EchoLink"); ?></option>
                                                <option value="EME" <?php if ($qso->COL_PROP_MODE == "EME") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Earth-Moon-Earth"); ?></option>
                                                <option value="ES" <?php if ($qso->COL_PROP_MODE == "ES") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Sporadic E"); ?></option>
                                                <option value="FAI" <?php if ($qso->COL_PROP_MODE == "FAI") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Field Aligned Irregularities"); ?></option>
                                                <option value="F2" <?php if ($qso->COL_PROP_MODE == "F2") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "F2 Reflection"); ?></option>
                                                <option value="INTERNET" <?php if ($qso->COL_PROP_MODE == "INTERNET") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Internet-assisted"); ?></option>
                                                <option value="ION" <?php if ($qso->COL_PROP_MODE == "ION") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Ionoscatter"); ?></option>
                                                <option value="IRL" <?php if ($qso->COL_PROP_MODE == "IRL") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "IRLP"); ?></option>
                                                <option value="MS" <?php if ($qso->COL_PROP_MODE == "MS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Meteor scatter"); ?></option>
                                                <option value="RPT" <?php if ($qso->COL_PROP_MODE == "RPT") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Terrestrial or atmospheric repeater or transponder"); ?></option>
                                                <option value="RS" <?php if ($qso->COL_PROP_MODE == "RS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Rain scatter"); ?></option>
                                                <option value="SAT" <?php if ($qso->COL_PROP_MODE == "SAT") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Satellite"); ?></option>
                                                <option value="TEP" <?php if ($qso->COL_PROP_MODE == "TEP") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Trans-equatorial"); ?></option>
                                                <option value="TR" <?php if ($qso->COL_PROP_MODE == "TR") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode", "Tropospheric ducting"); ?></option>
                                            </select>
                                            <small id="lotw_propmode_hint" class="form-text text-muted">
                                                <?php if (in_array($qso->COL_PROP_MODE, $this->config->item('lotw_unsupported_prop_modes'))) {
                                                    echo __("Propagation mode is not supported by LoTW. LoTW QSL fields disabled.");
                                                } else {
                                                    echo "&nbsp;";
                                                } ?>
                                            </small>
                                        </div>
                                        <input type="hidden" class="form-control" id="country" name="country" value="<?php echo $qso->COL_COUNTRY; ?>">
                                        <div class="mb-3 col-sm-6">
                                            <label for="ant_path"><?= __("Antenna Path"); ?></label>
                                            <select class="form-select" id="ant_path_edit" name="ant_path">
                                                <option value=""></option>
                                                <option value="G" <?php if ($qso->COL_ANT_PATH == "G") { echo "selected=\"selected\""; } ?>><?= __("Greyline"); ?></option>
                                                <option value="O" <?php if ($qso->COL_ANT_PATH == "O") { echo "selected=\"selected\""; } ?>><?= __("Other"); ?></option>
                                                <option value="S" <?php if ($qso->COL_ANT_PATH == "S") { echo "selected=\"selected\""; } ?>><?= __("Short Path"); ?></option>
                                                <option value="L" <?php if ($qso->COL_ANT_PATH == "L") { echo "selected=\"selected\""; } ?>><?= __("Long Path"); ?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="mb-3 col-sm-6">
                                            <label for="dxcc_id"><?= __("DXCC"); ?></label>
                                            <select class="form-select" id="dxcc_id_edit" name="dxcc_id" required>
                                                <option value=""><?= __("Please select one"); ?></option>
                                                <?php
                                                foreach ($dxcc as $d) {
                                                    if ($d->adif == '0') {
                                                        echo '<option value=' . $d->adif;
                                                        if ($qso->COL_DXCC == $d->adif) {
                                                            echo " selected=\"selected\"";
                                                        }
                                                        echo '>' . $d->name . '</option>';
                                                    } else {
                                                        echo '<option value=' . $d->adif;
                                                        if ($qso->COL_DXCC == $d->adif) {
                                                            echo " selected=\"selected\"";
                                                        }
                                                        echo '>' . $d->prefix . ' - ' . ucwords(strtolower(($d->name)));
                                                        if ($d->Enddate != null) {
                                                            echo ' (' . __("Deleted DXCC") . ')';
                                                        }
                                                        echo '</option>';
                                                    }
                                                }
                                                ?>

                                            </select>
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="continent"><?= __("Continent"); ?></label>
                                            <select class="form-select" id="continent_edit" name="continent">
                                                <option value=""></option>
                                                <option value="AF" <?php if ($qso->COL_CONT == "AF") { echo "selected=\"selected\""; } ?>><?= __("Africa"); ?></option>
                                                <option value="AN" <?php if ($qso->COL_CONT == "AN") { echo "selected=\"selected\""; } ?>><?= __("Antarctica"); ?></option>
                                                <option value="AS" <?php if ($qso->COL_CONT == "AS") { echo "selected=\"selected\""; } ?>><?= __("Asia"); ?></option>
                                                <option value="EU" <?php if ($qso->COL_CONT == "EU") { echo "selected=\"selected\""; } ?>><?= __("Europe"); ?></option>
                                                <option value="NA" <?php if ($qso->COL_CONT == "NA") { echo "selected=\"selected\""; } ?>><?= __("North America"); ?></option>
                                                <option value="OC" <?php if ($qso->COL_CONT == "OC") { echo "selected=\"selected\""; } ?>><?= __("Oceania"); ?></option>
                                                <option value="SA" <?php if ($qso->COL_CONT == "SA") { echo "selected=\"selected\""; } ?>><?= __("South America"); ?></option>
                                            </select>
                                        </div>
                                    	<div class="mb-3">
                                       		<label for="email"><?= __("E-mail"); ?></label>
                                       		<input type="text" class="form-control" id="email_edit" name="email" value="<?php echo $qso->COL_EMAIL; ?>">
                                    	</div>
                                    </div>
                                </div>

                                <!-- Satellite Panel Contents -->
                                <div class="tab-pane fade" id="nav-satellites" role="tabpanel" aria-labelledby="nav-satellites-tab">
                                    <div class="mb-3">
                                        <label for="sat_name"><?= __("Sat Name"); ?></label>
                                        <input type="text" class="form-control" name="sat_name" id="sat_name" value="<?php echo $qso->COL_SAT_NAME; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="sat_mode"><?= __("Sat Mode"); ?></label>
                                        <input type="text" class="form-control" name="sat_mode" id="sat_mode" value="<?php echo $qso->COL_SAT_MODE; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="ant_az"><?= __("Antenna Azimuth (°)"); ?> <i id="azimuth_tooltip" data-bs-toggle="tooltip" data-bs-placement="right" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Antenna azimuth in decimal degrees."); ?>"></i></label>
                                        <input type="number" step="0.1" min="0" max="360" class="form-control" id="ant_az" name="ant_az" value="<?php echo $qso->COL_ANT_AZ; ?>" />
                                    </div>

                                    <div class="mb-3">
                                        <label for="ant_el"><?= __("Antenna Elevation (°)"); ?> <i id="elevation_tooltip" data-bs-toggle="tooltip" data-bs-placement="right" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("Antenna elevation in decimal degrees."); ?>"></i></label>
                                        <input type="number" step="0.1" min="0" max="90" class="form-control" id="ant_el" name="ant_el" value="<?php echo $qso->COL_ANT_EL; ?>" />
                                    </div>
                                </div>

                                <!-- Awards Panel Contents -->
                                <div class="tab-pane fade" id="nav-awards" role="tabpanel" aria-labelledby="nav-awards-tab">
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="cqz"><?= __("CQ Zone"); ?></label>
                                            <select class="form-select" id="cqz_edit" name="cqz" required>
                                                <?php for ($i = 1; $i <= 40; $i++) { ?>
                                                    <option value="<?= $i; ?>" <?php if ($qso->COL_CQZ == $i) echo "selected=\"selected\""; ?>><?= $i; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="ituz"><?= __("ITU Zone"); ?></label>
                                            <select class="form-select" id="ituz_edit" name="ituz">
                                                <option value=''></option>
                                                <?php for ($i = 1; $i <= 90; $i++) { ?>
                                                    <option value="<?= $i; ?>" <?php if ($qso->COL_ITUZ == $i) echo "selected=\"selected\""; ?>><?= $i; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
									</div>
									<div class="row">
										<div class="mb-3 col-sm-6">
                                            <label for="region"><?= __("Region"); ?></label>
                                            <select class="form-select" id="region_edit" name="region">
                                                    <option value=''<?php if (($qso->COL_REGION ?? '') == '') echo " selected=\"selected\""; ?>></option>
													<option value="NONE"<?php if (($qso->COL_REGION ?? '') == 'NONE') echo " selected=\"selected\""; ?>><?= __("NONE"); ?></option>
													<option value="AI"<?php if (($qso->COL_REGION ?? '') == 'AI') echo " selected=\"selected\""; ?>><?= __("African Italy"); ?></option>
													<option value="BI"<?php if (($qso->COL_REGION ?? '') == 'BI') echo " selected=\"selected\""; ?>><?= __("Bear Island"); ?></option>
													<option value="ET"<?php if (($qso->COL_REGION ?? '') == 'ET') echo " selected=\"selected\""; ?>><?= __("European Turkey"); ?></option>
													<option value="IV"<?php if (($qso->COL_REGION ?? '') == 'IV') echo " selected=\"selected\""; ?>><?= __("ITU Vienna"); ?></option>
													<option value="KO"<?php if (($qso->COL_REGION ?? '') == 'KO') echo " selected=\"selected\""; ?>><?= __("Kosovo"); ?></option>
													<option value="SI"<?php if (($qso->COL_REGION ?? '') == 'SI') echo " selected=\"selected\""; ?>><?= __("Shetland Islands"); ?></option>
													<option value="SY"<?php if (($qso->COL_REGION ?? '') == 'SY') echo " selected=\"selected\""; ?>><?= __("Sicily"); ?></option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <?php
                                            $CI = &get_instance();
                                            $CI->load->library('subdivisions');

                                            $subdivision_name = $CI->subdivisions->get_primary_subdivision_name($qso->COL_DXCC);
                                            $state_list = $CI->subdivisions->get_state_list($qso->COL_DXCC);
                                            ?>

                                            <label for="stateDropdown" id="stateInputLabelEdit"><?php echo $subdivision_name; ?></label>
                                            <select class="form-select" id="stateDropdownEdit" name="input_state_edit">
                                                <option value=""></option>
                                                <?php foreach ($state_list->result() as $state) {
                                                    $selected = ($qso->COL_STATE == $state->state) ? 'selected="selected"' : ''; ?>
                                                    <option value="<?php echo $state->state; ?>" <?php echo $selected; ?>>
                                                        <?php echo $state->subdivision . ' (' . $state->state . ')'; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div style="display: none;" class="mb-3 col-sm-6" id="location_us_county_edit">
                                            <label for="stationCntyInput"><?= __("USA County"); ?></label>
                                            <input class="form-control" id="stationCntyInputEdit" type="text" name="usa_county" value="<?php echo $qso->COL_CNTY; ?>" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="iota_ref"><?= __("IOTA"); ?></label>
                                            <select class="form-select" id="iota_ref_edit" name="iota_ref">
                                                <option value=""></option>
                                                <?php foreach ($iota as $i) { ?>
                                                    <option value="<?= $i->tag; ?>" <?php if ($qso->COL_IOTA == $i->tag) echo "selected=\"selected\""; ?>><?= $i->tag . ' - ' . $i->name; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="sota_ref"><?= __("SOTA"); ?></label>
                                            <input type="text" class="form-control text-uppercase" id="sota_ref_edit" name="sota_ref" value="<?php echo $qso->COL_SOTA_REF; ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="pota_ref"><?= __("POTA"); ?></label>
                                            <input type="text" class="form-control text-uppercase" id="pota_ref_edit" name="pota_ref" value="<?php echo $qso->COL_POTA_REF; ?>">
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="wwff_ref"><?= __("WWFF"); ?></label>
                                            <input type="text" class="form-control text-uppercase" id="wwff_ref_edit" name="wwff_ref" value="<?php echo $qso->COL_WWFF_REF; ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-6">
                                            <label for="sig"><?= __("Sig"); ?></label>
                                            <input type="text" class="form-control text-uppercase" id="sig" name="sig" value="<?php echo $qso->COL_SIG; ?>">
                                        </div>
                                        <div class="mb-3 col-sm-6">
                                            <label for="sig_info"><?= __("Sig Info"); ?></label>
                                            <input type="text" class="form-control text-uppercase" id="sig_info" name="sig_info" value="<?php echo $qso->COL_SIG_INFO; ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="darc_dok"><?= __("DOK"); ?></label>
                                        <input type="text" class="form-control text-uppercase" id="darc_dok_edit" name="darc_dok" value="<?php echo $qso->COL_DARC_DOK; ?>">
                                    </div>
                                </div>

                                <!-- Notes Panel Contents -->
                                <div class="tab-pane fade" id="nav-qso-notes" role="tabpanel" aria-labelledby="nav-qso-notes-tab">
                                    <div class="mb-3">
                                        <label for="notes"><?= __("Notes"); ?></label>
                                        <textarea type="text" class="form-control" id="notes" name="notes" rows="10"><?php echo $qso->COL_NOTES; ?></textarea>
                                        <div class="small form-text text-muted"><?= __("Note: Gets exported to third-party services.") ?></div>
                                    </div>
                                </div>

                                <!-- QSL Panel Contents -->
                                <div class="tab-pane fade" id="nav-qsl-edit" role="tabpanel" aria-labelledby="nav-qsl-edit-tab">
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="qsl-edit-tab" data-bs-toggle="tab" href="#qsl-edit" role="tab" aria-controls="qsl-edit" aria-selected="true"><?= __("QSL Card"); ?></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="eqsl-tab" data-bs-toggle="tab" href="#eqsl-edit" role="tab" aria-controls="eqsl" aria-selected="false"><?= __("eQSL"); ?></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="lotw-tab" data-bs-toggle="tab" href="#lotw-edit" role="tab" aria-controls="lotw" aria-selected="false"><?= __("LoTW"); ?></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="qrz-tab" data-bs-toggle="tab" href="#qrz-edit" role="tab" aria-controls="qrz" aria-selected="false"><?= __("QRZ"); ?></a>
                                        </li>
										<li class="nav-item">
                                            <a class="nav-link" id="clublog-tab" data-bs-toggle="tab" href="#clublog-edit" role="tab" aria-controls="clublog" aria-selected="false"><?= __("Clublog"); ?></a>
                                        </li>
                                    </ul>
                                    <div class="tab-content" id="qsl_edit_tabs">
                                        <div class="tab-pane fade show active" id="qsl-edit" role="tabpanel" aria-labelledby="qsl-edit-tab">
                                            <div class="mt-3 mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="qsl_sent">
                                                        <option value="N" <?php if ($qso->COL_QSL_SENT == "N") { echo "selected=\"selected\""; } ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_QSL_SENT == "Y") { echo "selected=\"selected\""; } ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_QSL_SENT == "R") { echo "selected=\"selected\""; } ?>><?= __("Requested"); ?></option>
                                                        <option value="Q" <?php if ($qso->COL_QSL_SENT == "Q") { echo "selected=\"selected\""; } ?>><?= __("Queued"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_QSL_SENT == "I") { echo "selected=\"selected\""; } ?>><?= __("Invalid (Ignore)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent-method" class="col-sm-3 col-form-label"><?= __("Sent Method"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="qsl_sent_method">
                                                        <option value="" <?php if ($qso->COL_QSL_SENT_VIA == "") { echo "selected=\"selected\""; } ?>><?= __("Method"); ?></option>
                                                        <option value="D" <?php if ($qso->COL_QSL_SENT_VIA == "D") { echo "selected=\"selected\""; } ?>><?= __("Direct"); ?></option>
                                                        <option value="B" <?php if ($qso->COL_QSL_SENT_VIA == "B") { echo "selected=\"selected\""; } ?>><?= __("Bureau"); ?></option>
                                                        <option value="E" <?php if ($qso->COL_QSL_SENT_VIA == "E") { echo "selected=\"selected\""; } ?>><?= __("Electronic"); ?></option>
                                                        <option value="M" <?php if ($qso->COL_QSL_SENT_VIA == "M") { echo "selected=\"selected\""; } ?>><?= __("Manager"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="qsl-via" class="col-sm-3 col-form-label"><?= __("Sent Via"); ?></label>
                                                <div class="col-sm-9">
                                                    <input type="text" id="qsl-via" class="form-control" name="qsl_via_callsign" value="<?php echo $qso->COL_QSL_VIA; ?>" />
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent-method" class="col-sm-3 col-form-label"><?= __("Received"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="qsl_rcvd">
                                                        <option value="N" <?php if ($qso->COL_QSL_RCVD == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_QSL_RCVD == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_QSL_RCVD == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_QSL_RCVD == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="V" <?php if ($qso->COL_QSL_RCVD == "V") echo "selected=\"selected\""; ?>><?= __("Verified (Match)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent-method" class="col-sm-3 col-form-label"><?= __("Received Method"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="qsl_rcvd_method">
                                                        <option value="" <?php if ($qso->COL_QSL_RCVD_VIA == "") echo "selected=\"selected\""; ?>><?= __("Method"); ?></option>
                                                        <option value="D" <?php if ($qso->COL_QSL_RCVD_VIA == "D") echo "selected=\"selected\""; ?>><?= __("Direct"); ?></option>
                                                        <option value="B" <?php if ($qso->COL_QSL_RCVD_VIA == "B") echo "selected=\"selected\""; ?>><?= __("Bureau"); ?></option>
                                                        <option value="E" <?php if ($qso->COL_QSL_RCVD_VIA == "E") echo "selected=\"selected\""; ?>><?= __("Electronic"); ?></option>
                                                        <option value="M" <?php if ($qso->COL_QSL_RCVD_VIA == "M") echo "selected=\"selected\""; ?>><?= __("Manager"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="eqsl-edit" role="tabpanel" aria-labelledby="eqsl-tab">
                                            <div class="mt-3 mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="eqsl_sent">
                                                        <option value="N" <?php if ($qso->COL_EQSL_QSL_SENT == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_EQSL_QSL_SENT == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_EQSL_QSL_SENT == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="Q" <?php if ($qso->COL_EQSL_QSL_SENT == "Q") echo "selected=\"selected\""; ?>><?= __("Queued"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_EQSL_QSL_SENT == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Received"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" name="eqsl_rcvd">
                                                        <option value="N" <?php if ($qso->COL_EQSL_QSL_RCVD == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_EQSL_QSL_RCVD == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_EQSL_QSL_RCVD == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_EQSL_QSL_RCVD == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="V" <?php if ($qso->COL_EQSL_QSL_RCVD == "V") echo "selected=\"selected\""; ?>><?= __("Verified (Match)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <div>
                                                    <label for="qslmsg"><?= __("QSL Msg (S)"); ?><span class="qso_eqsl_qslmsg_update" title="<?= __("Get the default message for eQSL, for this station."); ?>"><i class="fas fa-redo-alt"></i></span></label>
                                                    <label class="position-absolute end-0 mb-2 me-3" for="qslmsg" id="charsLeft"> </label>
                                                    <textarea type="text" class="form-control" id="qslmsg" name="qslmsg" rows="5" maxlength="240"><?php echo $qso->COL_QSLMSG; ?></textarea>
                                                    <div class="small form-text text-muted"><?= __("Note: Gets exported to third-party services.") ?></div>
                                                    <div id="qslmsg_hide" style="display:none;"><?php echo $qso->COL_QSLMSG; ?></div>
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <div>
                                                    <label for="qslmsgr"><?= __("QSL Msg (R)"); ?></label>
                                                    <label class="position-absolute end-0 mb-2 me-3" for="qslmsg" id="charsLeft"> </label>
                                                    <textarea readonly type="text" class="form-control" id="qslmsgr" name="qslmsgr" rows="2"><?php echo htmlentities($qso->COL_QSLMSG_RCVD ?? ''); ?></textarea>
                                                    <div class="small form-text text-muted"><?= __("Note: Not editable. Only displayed here.") ?></div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="tab-pane fade" id="lotw-edit" role="tabpanel" aria-labelledby="lotw-tab">
                                            <div class="mt-3 mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="lotw_sent" name="lotw_sent" <?php if (in_array($qso->COL_PROP_MODE, $this->config->item('lotw_unsupported_prop_modes'))) echo "disabled=\"disabled\""; ?>>
                                                        <option value="N" <?php if ($qso->COL_LOTW_QSL_SENT == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_LOTW_QSL_SENT == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_LOTW_QSL_SENT == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="Q" <?php if ($qso->COL_LOTW_QSL_SENT == "Q") echo "selected=\"selected\""; ?>><?= __("Queued"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_LOTW_QSL_SENT == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Received"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="lotw_rcvd" name="lotw_rcvd" <?php if (in_array($qso->COL_PROP_MODE, $this->config->item('lotw_unsupported_prop_modes'))) echo "disabled=\"disabled\""; ?>>
                                                        <option value="N" <?php if ($qso->COL_LOTW_QSL_RCVD == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_LOTW_QSL_RCVD == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_LOTW_QSL_RCVD == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_LOTW_QSL_RCVD == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="V" <?php if ($qso->COL_LOTW_QSL_RCVD == "V") echo "selected=\"selected\""; ?>><?= __("Verified (Match)"); ?></option>
                                                    </select>
                                                    <small id="lotw_propmode_hint" class="form-text text-muted"><?php if (in_array($qso->COL_PROP_MODE, $this->config->item('lotw_unsupported_prop_modes'))) echo __("Propagation mode is not supported by LoTW. LoTW QSL fields disabled."); else echo "&nbsp;"; ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="qrz-edit" role="tabpanel" aria-labelledby="qrz-tab">
                                            <div class="mt-3 mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="qrz_sent" name="qrz_sent">
                                                        <option value="N" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="Q" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "Q") echo "selected=\"selected\""; ?>><?= __("Queued"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="M" <?php if ($qso->COL_QRZCOM_QSO_UPLOAD_STATUS == "M") echo "selected=\"selected\""; ?>><?= __("Modified"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Received"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="qrz_rcvd" name="qrz_rcvd">
                                                        <option value="N" <?php if ($qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="V" <?php if ($qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS == "V") echo "selected=\"selected\""; ?>><?= __("Verified (Match)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
										<div class="tab-pane fade" id="clublog-edit" role="tabpanel" aria-labelledby="clublog-tab">
                                            <div class="mt-3 mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="clublog_sent" name="clublog_sent">
                                                        <option value="N" <?php if ($qso->COL_CLUBLOG_QSO_UPLOAD_STATUS == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_CLUBLOG_QSO_UPLOAD_STATUS == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_CLUBLOG_QSO_UPLOAD_STATUS == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="Q" <?php if ($qso->COL_CLUBLOG_QSO_UPLOAD_STATUS == "Q") echo "selected=\"selected\""; ?>><?= __("Queued"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_CLUBLOG_QSO_UPLOAD_STATUS == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label for="sent" class="col-sm-3 col-form-label"><?= __("Received"); ?></label>
                                                <div class="col-sm-9">
                                                    <select class="form-select" id="clublog_rcvd" name="clublog_rcvd">
                                                        <option value="N" <?php if ($qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "N") echo "selected=\"selected\""; ?>><?= __("No"); ?></option>
                                                        <option value="Y" <?php if ($qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "Y") echo "selected=\"selected\""; ?>><?= __("Yes"); ?></option>
                                                        <option value="R" <?php if ($qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "R") echo "selected=\"selected\""; ?>><?= __("Requested"); ?></option>
                                                        <option value="I" <?php if ($qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "I") echo "selected=\"selected\""; ?>><?= __("Invalid (Ignore)"); ?></option>
                                                        <option value="V" <?php if ($qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS == "V") echo "selected=\"selected\""; ?>><?= __("Verified (Match)"); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Station Panel Contents -->
                                <div class="tab-pane fade" id="nav-station" role="tabpanel" aria-labelledby="nav-station-tab">

                                    <?php
                                    $CI = &get_instance();
                                    $CI->load->model('stations');
                                    $my_stations = $CI->stations->all_of_user();
                                    ?>

                                    <div class="mb-3">
                                        <label for="inputStationProfile"><?= __("Change Station Profile"); ?></label>
                                        <select id="stationProfile" class="form-select" name="station_profile">
                                            <?php foreach ($my_stations->result() as $stationrow) { ?>
                                                <option value="<?= $stationrow->station_id; ?>" <?php if ($qso->station_id == $stationrow->station_id) echo "selected=\"selected\""; ?>><?= $stationrow->station_profile_name; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <?php if(clubaccess_check(9)) { ?>
                                    <div class="mb-3">
                                        <label for="operatorCallsign"><?= __("Operator Callsign"); ?></label>
                                        <input type="text" id="operatorCallsign" class="form-control uppercase" name="operator_callsign" value="<?php echo $qso->COL_OPERATOR; ?>" />
                                    </div>
                                    <?php } ?>


                                </div>
                                <!-- Contest Panel Contents -->
                                <div class="tab-pane fade" id="nav-contest" role="tabpanel" aria-labelledby="nav-contest-tab">
                                    <div class="mb-3">
                                        <label for="contest_name"><?= __("Contest Name"); ?></label>
                                        <select class="form-select" id="contest_name" name="contest_name">
                                            <option value=""></option>
                                            <?php foreach ($contest as $c) { ?>
                                                <option value="<?= $c['adifname']; ?>" <?php if ($qso->COL_CONTEST_ID == $c['adifname']) echo "selected=\"selected\""; ?>><?= $c['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="mb-3 col-sm-3">
                                            <label for="srx"><?= __("Serial (R)"); ?></label>
                                            <input type="text" id="srx" class="form-control" name="srx" value="<?php echo $qso->COL_SRX; ?>" />
                                        </div>

                                        <div class="mb-3 col-sm-3">
                                            <label for="stx"><?= __("Serial (S)"); ?></label>
                                            <input type="text" id="stx" class="form-control" name="stx" value="<?php echo $qso->COL_STX; ?>" />
                                        </div>

                                        <div class="mb-3 col-sm-3">
                                            <label for="srx_string"><?= __("Exchange (R)"); ?></label>
                                            <input type="text" id="srx_string" class="form-control uppercase" name="srx_string" value="<?php echo $qso->COL_SRX_STRING; ?>" />
                                        </div>

                                        <div class="mb-3 col-sm-3">
                                            <label for="stx_string"><?= __("Exchange (S)"); ?></label>
                                            <input type="text" id="stx_string" class="form-control uppercase" name="stx_string" value="<?php echo $qso->COL_STX_STRING; ?>" />
                                        </div>
                                    </div>



                                </div>

                                <input type="hidden" name="id" value="<?php echo $qso->COL_PRIMARY_KEY; ?>" />

                                <div class="actions">
                                    <a class="btn btn-danger" href="javascript:qso_delete(<?php echo $qso->COL_PRIMARY_KEY; ?>, '<?php echo $qso->COL_CALL; ?>')"><i class="fas fa-trash-alt"></i> <?= __("Delete QSO"); ?></a>
                                    <div class="float-end">
                                        <button id="update_from_callbook" type="button" class="btn btn-warning ld-ext-right" onclick="single_callbook_update();"><i class="fas fa-book"></i> <?= __("Update from Callbook"); ?><div class="ld ld-ring ld-spin"></div></button>
                                        <button id="show" type="button" name="download" class="btn btn-primary" onclick="qso_save();"><i class="fas fa-save"></i> <?= __("Save changes"); ?></button>
                                    </div>
                                </div>
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
