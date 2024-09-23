<div class="container qso_panel">
<script language="javascript">
  var qso_manual  = "<?php echo $manual_mode; ?>";
  var text_error_timeoff_less_timeon = "<?= __("TimeOff is less than TimeOn"); ?>";
  var lang_qso_title_previous_contacts = "<?= __("Previous Contacts"); ?>";
  var lang_qso_title_times_worked_before = "<?= __("times worked before"); ?>";
  var lang_qso_title_not_worked_before = "<?= __("Not worked before"); ?>";
  var lang_dxccsummary_for = "<?= __("DXCC Summary for "); ?>";
</script>

<div class="row qsopane">

  <div class="col-sm-5">
    <div class="card">

    <form id="qso_input" method="post" action="<?php echo site_url('qso') . "?manual=" . $manual_mode; ?>" name="qsos" autocomplete="off" onReset="resetTimers(<?php echo $manual_mode; ?>);">

      <div class="card-header">
        <ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right"  id="myTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="qsp-tab" data-bs-toggle="tab" href="#qso" role="tab" aria-controls="qso" aria-selected="true"><?= __("QSO"); ?><?php if ($manual_mode == 0) { echo " <span class=\"badge text-bg-success\">" . __("LIVE") . "</span>"; }; if ($manual_mode == 1) { echo " <span class=\"badge text-bg-danger\">" . __("POST") . "</span>"; } ?></a>
          </li>

          <li class="nav-item">
            <a class="nav-link" id="station-tab" data-bs-toggle="tab" href="#station" role="tab" aria-controls="station" aria-selected="false"><?= __("Station"); ?></a>
          </li>

          <li class="nav-item">
            <a class="nav-link" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="false"><?= __("General"); ?></a>
          </li>

<?php if ($sat_active) { ?>
          <li class="nav-item">
            <a class="nav-link" id="satellite-tab" data-bs-toggle="tab" href="#satellite" role="tab" aria-controls="satellite" aria-selected="false"><?= __("Sat"); ?></a>
          </li>
<?php } ?>

          <li class="nav-item">
            <a class="nav-link" id="notes-tab" data-bs-toggle="tab" href="#nav-notes" role="tab" aria-controls="notes" aria-selected="false"><?= __("Notes"); ?></a>
          </li>

          <li class="nav-item">
            <a class="nav-link" id="qsl-tab" data-bs-toggle="tab" href="#qsl" role="tab" aria-controls="qsl" aria-selected="false"><?= __("QSL"); ?></a>
          </li>

  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" id="fav_item" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-star"></i></a>
    <div class="dropdown-menu">
      <a class="dropdown-item" href="#" id="fav_add"><?= __("Add Band/Mode to Favs"); ?></a>
      <div class="dropdown-divider"></div>
      <div id="fav_menu"></div>
    </div>
  </li>

	        </ul>
      </div>

      <div class="card-body">
        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active" id="qso" role="tabpanel" aria-labelledby="qso-tab">
                      <!-- HTML for Date/Time -->
              <?php if ($this->session->userdata('user_qso_end_times')  == 1) { ?>
              <div class="row">
                <div class="mb-3 col-md-3">
                  <label for="start_date"><?= __("Date"); ?></label>
                  <input type="text" class="form-control form-control-sm input_date" name="start_date" id="start_date" tabindex="4" value="<?php if (($this->session->userdata('start_date') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo $this->session->userdata('start_date'); } else { echo date('d-m-Y');}?>" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> required pattern="[0-3][0-9]-[0-1][0-9]-[0-9]{4}">
                </div>

                <div class="mb-3 col-md-4">
                <label for="start_time"><?= __("Time on"); ?></label>
                  <div class="input-group">
                    <input type="text" class="form-control form-control-sm input_start_time" name="start_time" id="start_time" tabindex="5" value="<?php if (($this->session->userdata('start_time') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo substr($this->session->userdata('start_time'),0,5); } else { echo $manual_mode == 0 ? date('H:i:s') : date('H:i'); } ?>" size="7" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> required pattern="[0-2][0-9]:[0-5][0-9]">
                    <?php if ($manual_mode != 1) { ?>
                      <span class="input-group-text btn-included-on-field"><i id="reset_time" data-bs-toggle="tooltip" title="Reset start time" class="fas fa-stopwatch"></i></span>
                    <?php } else { ?>
                      <span class="input-group-text btn-included-on-field"><i id="reset_start_time" data-bs-toggle="tooltip" title="Reset start time" class="fas fa-stopwatch"></i></span>
                    <?php } ?>
                  </div>
                </div>

                <div class="mb-3 col-md-4">
                  <label for="end_time"><?= __("Time off"); ?></label>
                  <div class="input-group">
                    <input type="text" class="form-control form-control-sm input_end_time" name="end_time" id="end_time" tabindex="6" value="<?php if (($this->session->userdata('end_time') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo substr($this->session->userdata('end_time'),0,5); } else { echo $manual_mode == 0 ? date('H:i:s') : date('H:i'); } ?>" size="7" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> required pattern="[0-2][0-9]:[0-5][0-9]">
                    <?php if ($manual_mode == 1) { ?>
                      <span class="input-group-text btn-included-on-field"><i id="reset_end_time" data-bs-toggle="tooltip" title="Reset end time" class="fas fa-stopwatch"></i></span>
                    <?php } ?>
                  </div>
                </div>

                <?php if ( $manual_mode == 0 ) { ?>
                  <input class="input_start_time" type="hidden" id="start_time"  name="start_time"value="<?php echo date('H:i:s'); ?>" />
                  <input class="input_end_time" type="hidden" id="end_time"  name="end_time"value="<?php echo date('H:i:s'); ?>" />
                  <input class="input_date" type="hidden" id="start_date" name="start_date" value="<?php echo date('d-m-Y'); ?>" />
                <?php } ?>
              </div>

              <?php } else {?>
              <div class="row">
                <div class="mb-3 col-md-6">
                  <label for="start_date"><?= __("Date"); ?></label>
                  <input type="text" class="form-control form-control-sm input_date" name="start_date" id="start_date" tabindex="4" value="<?php if (($this->session->userdata('start_date') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo $this->session->userdata('start_date'); } else { echo date('d-m-Y');}?>" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> required pattern="[0-3][0-9]-[0-1][0-9]-[0-9]{4}">
                </div>

                <div class="mb-3 col-md-6">
                  <label for="start_time"><?= __("Time"); ?></label>
                  <div class="input-group">
                    <input type="text" class="form-control form-control-sm input_start_time" name="start_time" id="start_time" tabindex="5" value="<?php if (($this->session->userdata('start_time') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo substr($this->session->userdata('start_time'),0,5); } else { echo $manual_mode == 0 ? date('H:i:s') : date('H:i'); } ?>" size="7" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> required pattern="[0-2][0-9]:[0-5][0-9]">
                    <?php if ($manual_mode == 1) { ?>
                      <span class="input-group-text btn-included-on-field"><i id="reset_start_time" data-bs-toggle="tooltip" title="Reset start time" class="fas fa-stopwatch"></i></span>
                    <?php } ?>
                  </div>
                </div>

                <?php if ( $manual_mode == 0 ) { ?>
                  <input class="input_start_time" type="hidden" id="start_time"  name="start_time"value="<?php echo date('H:i:s'); ?>" />
                  <input class="input_date" type="hidden" id="start_date" name="start_date" value="<?php echo date('d-m-Y'); ?>" />
                <?php } ?>
              </div>
              <?php } ?>

              <!-- Callsign Input -->
              <div class="row">
                <div class="mb-3 col-md-12">
                  <label for="callsign"><?= __("Callsign"); ?></label>&nbsp;<i id="check_cluster" data-bs-toggle="tooltip" title="<?= __("Search DXCluster for latest Spot"); ?>" class="fas fa-search"></i>
                  <div class="input-group">
                    <input tabindex="7" type="text" class="form-control" id="callsign" name="callsign" required>
                    <span id="qrz_info" class="input-group-text btn-included-on-field d-none py-0"></span>
                    <span id="hamqth_info" class="input-group-text btn-included-on-field d-none py-0"></span>
                  </div>
                  <small id="callsign_info" class="badge text-bg-secondary"></small> <a id="lotw_link"><small id="lotw_info" class="badge text-bg-success"></small></a>
                </div>
              </div>

              <div class="row">
                <div class="mb-3 col">
                  <label for="mode"><?= __("Mode"); ?></label>
                  <select id="mode" tabindex="1" class="form-select mode form-select-sm" name="mode">
                  <?php
                      foreach($modes->result() as $mode){
                        if ($mode->submode == null) {
                          printf("<option value=\"%s\" %s>%s</option>", $mode->mode, $this->session->userdata('mode')==$mode->mode?"selected=\"selected\"":"",$mode->mode);
                        } else {
                          printf("<option value=\"%s\" %s>&rArr; %s</option>", $mode->submode, $this->session->userdata('mode')==$mode->submode?"selected=\"selected\"":"",$mode->submode);
                        }
                      }
                  ?>
                  </select>
                </div>

                <div class="mb-3 col">
                  <label for="band"><?= __("Band"); ?></label>

                  <select id="band" tabindex="2" class="form-select form-select-sm" name="band">
                  <?php foreach($bands as $key=>$bandgroup) {
                          echo '<optgroup label="' . strtoupper($key) . '">';
                          foreach($bandgroup as $band) {
                            echo '<option value="' . $band . '"';
                            if ($this->session->userdata('band') == $band || $user_default_band == $band) {
                              echo ' selected';
                            }
                            echo '>' . $band . '</option>'."\n";
                          }
                          echo '</optgroup>';
                        }
                  ?>
                  </select>
                </div>
                <div class="mb-3 col">
                  <label for="frequency"><?= __("Frequency"); ?></label>
                  <input type="text" tabindex="3" class="form-control form-control-sm" id="frequency" name="freq_display" value="<?php echo $this->session->userdata('freq'); ?>" />
                </div>
              </div>

              <!-- Signal Report Information -->
              <div class="row">
                <div class="mb-3 col-md-6">
                  <label for="rst_sent"><?= __("RST (S)"); ?></label>
                  <input tabindex="8" type="text" class="form-control form-control-sm" name="rst_sent" id="rst_sent" value="59">
                </div>

                <div class="mb-3 col-md-6">
                  <label for="rst_rcvd"><?= __("RST (R)"); ?></label>
                  <input tabindex="9" type="text" class="form-control form-control-sm" name="rst_rcvd" id="rst_rcvd" value="59">
                </div>
              </div>

              <div class="mb-3 row">
                  <label for="name" class="col-sm-3 col-form-label"><?= __("Name"); ?></label>
                  <div class="col-sm-9">
                    <input tabindex="10" type="text" class="form-control form-control-sm" name="name" id="name" maxlength="128" value="">
                </div>
              </div>

              <?php if ($user_iota_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="iota_ref"><?= __("IOTA Reference"); ?></label>
                      <div class="col-sm-9 align-self-center">
                      <select class="form-select" id="iota_ref" tabindex="11" name="iota_ref">
                          <option value =""></option>
                          <?php
                          foreach($iota as $i){
                              echo '<option value=' . $i->tag . '>' . $i->tag . ' - ' . $i->name . '</option>';
                          }
                          ?>
                      </select>
                      </div>
              </div>
              <?php } ?>

              <?php if ($user_sota_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="sota_ref"><?= __("SOTA Reference"); ?></label>
                <div class="col-sm-7 align-self-center">
                  <input class="form-control" id="sota_ref" tabindex="12" type="text" name="sota_ref" value="" />
                </div>
                <div class="col-sm-2 align-self-center">
                  <small id="sota_info" class="btn btn-secondary spw-buttons"></small>
                </div>
              </div>
              <?php } ?>

              <?php if ($user_wwff_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="wwff_ref"><?= __("WWFF Reference"); ?></label>
                <div class="col-sm-7 align-self-center">
                  <input class="form-control" id="wwff_ref" tabindex="13" type="text" name="wwff_ref" value="" />
                </div>
                <div class="col-sm-2 align-self-center">
                  <small id="wwff_info" class="btn btn-secondary spw-buttons"></small>
                </div>
              </div>
              <?php } ?>

              <?php if ($user_pota_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="pota_ref"><?= __("POTA Reference(s)"); ?></label>
                <div class="col-sm-7 align-self-center">
                  <input class="form-control" id="pota_ref" tabindex="14" type="text" name="pota_ref" value="" />
                </div>
                <div class="col-sm-2 align-self-center">
                  <small id="pota_info" class="btn btn-secondary spw-buttons"></small>
                </div>
              </div>
              <?php } ?>

              <?php if ($user_sig_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="sig"><?= __("Sig"); ?></label>
                <div class="col-sm-9">
                  <input class="form-control" id="sig" tabindex="15" type="text" name="sig" value="" />
                </div>
              </div>

              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="sig_info"><?= __("Sig Info"); ?></label>
                <div class="col-sm-9">
                  <input class="form-control" id="sig_info" tabindex="16" type="text" name="sig_info" value="" />
                </div>
              </div>
              <?php } ?>

              <?php if ($user_dok_to_qso_tab ?? false) { ?>
              <div class="mb-3 row">
                <label class="col-sm-3 col-form-label" for="darc_dok"><?= __("DOK"); ?></label>
                <div class="col-sm-9">
                  <input class="form-control" id="darc_dok" tabindex="17" type="text" name="darc_dok" value="" />
                </div>
              </div>
              <?php } ?>

              <div class="mb-3 row">
                <label for="qth" class="col-sm-3 col-form-label"><?= __("Location"); ?></label>
                <div class="col-sm-9">
                    <input tabindex="18" type="text" class="form-control form-control-sm" name="qth" id="qth" maxlength="64" value="">
                </div>
              </div>

              <div class="mb-3 row">
                  <label for="locator" class="col-sm-3 col-form-label"><?= __("Gridsquare"); ?></label>
                  <div class="col-sm-9">
                    <input tabindex="19" type="text" class="form-control form-control-sm" name="locator" id="locator" value="">
                    <small id="locator_info" class="form-text text-muted"></small>
                </div>
              </div>

              <input type="hidden" name="distance" id="distance" value="0">

              <div class="mb-3 row">
                  <label for="comment" class="col-sm-3 col-form-label"><?= __("Comment"); ?></label>
                  <div class="col-sm-9">
                    <input tabindex="20"type="text" class="form-control form-control-sm" name="comment" id="comment" value="">
                </div>
              </div>

          </div>

          <!-- Station Panel Data -->
          <div class="tab-pane fade" id="station" role="tabpanel" aria-labelledby="station-tab">
            <div class="mb-3">
              <label for="stationProfile"><?= __("Station Location"); ?></label>
              <select id="stationProfile" class="form-select" name="station_profile">
                <?php
                   $power = '';
                      foreach ($stations->result() as $stationrow) {
                ?>
                <option value="<?php echo $stationrow->station_id; ?>" <?php if($active_station_profile == $stationrow->station_id) { echo "selected=\"selected\""; $power = $stationrow->station_power; } ?>><?php echo $stationrow->station_profile_name; ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="radio"><?= __("Radio"); ?></label>
              <select class="form-select radios" id="radio" name="radio">
                <option value="0" selected="selected"><?= __("None"); ?></option>
                <?php foreach ($radios->result() as $row) { ?>
                  <option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?php echo $row->radio; ?> <?php if ($radio_last_updated->id == $row->id) { echo "(".__("last updated").")"; } else { echo ''; } ?></option>
                <?php } ?>
                </select>
            </div>

            <div class="mb-3">
              <label for="frequency_rx"><?= __("Frequency (RX)"); ?></label>
              <input type="text" class="form-control" id="frequency_rx" name="freq_display_rx" value="<?php echo $this->session->userdata('freq_rx'); ?>" />
            </div>

            <div class="mb-3">
                  <label for="band_rx"><?= __("Band (RX)"); ?></label>

                  <select id="band_rx" class="form-select" name="band_rx">
                    <option value="" <?php if($this->session->userdata('band_rx') == "") { echo "selected=\"selected\""; } ?>></option>

                  <?php foreach($bands as $key=>$bandgroup) {
                          echo '<optgroup label="' . strtoupper($key) . '">';
                          foreach($bandgroup as $band) {
                            echo '<option value="' . $band . '"';
                              if ($this->session->userdata('band_rx') == $band) echo ' selected';
                              echo '>' . $band . '</option>'."\n";
                          }
                          echo '</optgroup>';
                        }
                  ?>
                  </select>
            </div>

            <div class="mb-3">
              <label for="transmit_power"><?= __("Transmit Power (W)"); ?></label>
              <input type="number" step="0.001" class="form-control" id="transmit_power" name="transmit_power" value="<?php if ($this->session->userdata('transmit_power')) { echo $this->session->userdata('transmit_power'); } else { echo $power; } ?>" />
              <small id="powerHelp" class="form-text text-muted"><?= __("Give power value in Watts. Include only numbers in the input."); ?></small>
            </div>

            <div class="mb-3">
              <label for="operator_callsign"><?= __("Operator Callsign"); ?></label>
              <input type="text" class="form-control" id="operator_callsign" name="operator_callsign" value="<?php if ($this->session->userdata('operator_callsign')) { echo $this->session->userdata('operator_callsign'); } ?>" />
            </div>

        </div>

          <!-- General Items -->
          <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
              <div class="mb-3">
                  <label for="dxcc_id"><?= __("DXCC"); ?></label>
                  <select class="form-control" id="dxcc_id" name="dxcc_id" required>
                      <option value="0">- NONE -</option>
                      <?php
                      foreach($dxcc as $d){
                          echo '<option value=' . $d->adif . '>' . $d->prefix . ' - ' . ucwords(strtolower(($d->name)));
                          if ($d->Enddate != null) {
                              echo ' ('.__("Deleted DXCC").')';
                          }
                          echo '</option>';
                      }
                      ?>

                  </select>
              </div>
              <div class="mb-3">
                  <label for="continent"><?= __("Continent"); ?></label>
                  <select class="form-select" id="continent" name="continent">
                      <option value=""></option>
                      <option value="AF"><?= __("Africa"); ?></option>
                      <option value="AN"><?= __("Antarctica"); ?></option>
                      <option value="AS"><?= __("Asia"); ?></option>
                      <option value="EU"><?= __("Europe"); ?></option>
                      <option value="NA"><?= __("North America"); ?></option>
                      <option value="OC"><?= __("Oceania"); ?></option>
                      <option value="SA"><?= __("South America"); ?></option>
                  </select>
              </div>
              <div class="mb-3">
                  <label for="cqz"><?= __("CQ Zone"); ?></label>
                  <select class="form-select" id="cqz" name="cqz" required>
                      <?php
                      for ($i = 0; $i<=40; $i++) {
                          echo '<option value="'. $i . '">'. $i .'</option>';
                      }
                      ?>
                  </select>
              </div>
              <div class="mb-3">
                  <label for="ituz"><?= __("ITU Zone"); ?></label>
                  <select class="form-select" id="ituz" name="ituz">
                      <?php
                      for ($i = 0; $i<=90; $i++) {
                          echo '<option value="'. $i . '">'. $i .'</option>';
                      }
                      ?>
                  </select>
              </div>

            <div class="mb-3">
              <label for="selectPropagation"><?= __("Propagation Mode"); ?></label>
              <select class="form-select" id="selectPropagation" name="prop_mode">
                <option value="" <?php if(!empty($this->session->userdata('prop_mode'))) { echo "selected=\"selected\""; } ?>></option>
                <option value="AS" <?php if($this->session->userdata('prop_mode') == "AS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Aircraft Scatter"); ?></option>
                <option value="AUR" <?php if($this->session->userdata('prop_mode') == "AUR") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Aurora"); ?></option>
                <option value="AUE" <?php if($this->session->userdata('prop_mode') == "AUE") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Aurora-E"); ?></option>
                <option value="BS" <?php if($this->session->userdata('prop_mode') == "BS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Back scatter"); ?></option>
                <option value="ECH" <?php if($this->session->userdata('prop_mode') == "ECH") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","EchoLink"); ?></option>
                <option value="EME" <?php if($this->session->userdata('prop_mode') == "EME") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Earth-Moon-Earth"); ?></option>
                <option value="ES" <?php if($this->session->userdata('prop_mode') == "ES") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Sporadic E"); ?></option>
                <option value="FAI" <?php if($this->session->userdata('prop_mode') == "FAI") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Field Aligned Irregularities"); ?></option>
                <option value="F2" <?php if($this->session->userdata('prop_mode') == "F2") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","F2 Reflection"); ?></option>
                <option value="INTERNET" <?php if($this->session->userdata('prop_mode') == "INTERNET") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Internet-assisted"); ?></option>
                <option value="ION" <?php if($this->session->userdata('prop_mode') == "ION") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Ionoscatter"); ?></option>
                <option value="IRL" <?php if($this->session->userdata('prop_mode') == "IRL") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","IRLP"); ?></option>
                <option value="MS" <?php if($this->session->userdata('prop_mode') == "MS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Meteor scatter"); ?></option>
                <option value="RPT" <?php if($this->session->userdata('prop_mode') == "RPT") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Terrestrial or atmospheric repeater or transponder"); ?></option>
                <option value="RS" <?php if($this->session->userdata('prop_mode') == "RS") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Rain scatter"); ?></option>
                <option value="SAT" <?php if($this->session->userdata('prop_mode') == "SAT") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Satellite"); ?></option>
                <option value="TEP" <?php if($this->session->userdata('prop_mode') == "TEP") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Trans-equatorial"); ?></option>
                <option value="TR" <?php if($this->session->userdata('prop_mode') == "TR") { echo "selected=\"selected\""; } ?>><?= _pgettext("Propagation Mode","Tropospheric ducting"); ?></option>
              </select>
            </div>

            <div class="mb-3">
              <label for="stateInput" id="stateInputLabel"></label>
                <select class="form-select" name="input_state_edit" id="stateDropdown">
                  <option value=""></option>
                </select>
            </div>

            <div class="mb-3" id="location_us_county">
                <label for="stationCntyInputQso"><?= __("USA County"); ?></label>
                <input class="form-control" id="stationCntyInputQso" type="text" name="county" value="" />
            </div>

            <?php if (!$user_iota_to_qso_tab ?? false) { ?>
            <div class="mb-3">
              <label for="iota_ref"><?= __("IOTA Reference"); ?></label>
                    <select class="form-select" id="iota_ref" name="iota_ref">
                        <option value =""></option>

                        <?php
                        foreach($iota as $i){
                            echo '<option value=' . $i->tag . '>' . $i->tag . ' - ' . $i->name . '</option>';
                        }
                        ?>

                    </select>
            </div>
            <?php } ?>

            <?php if (!$user_sota_to_qso_tab ?? false) { ?>
            <div class="row">
              <div class="mb-3 col-md-9">
                <label for="sota_ref"><?= __("SOTA Reference"); ?></label>
                <input class="form-control" id="sota_ref" type="text" name="sota_ref" value="" />
                <small id="sotaRefHelp" class="form-text text-muted"><?= __("For example: GM/NS-001."); ?></small>
              </div>
              <div class="mb-3 col-md-3 align-self-center">
                <small id="sota_info" class="btn btn-secondary spw-buttons"></small>
              </div>
            </div>
            <?php } ?>

            <?php if (!$user_wwff_to_qso_tab ?? false) { ?>
            <div class="row">
              <div class="mb-3 col-md-9">
                <label for="wwff_ref"><?= __("WWFF Reference"); ?></label>
                <input class="form-control" id="wwff_ref" type="text" name="wwff_ref" value="" />
                <small id="wwffRefHelp" class="form-text text-muted"><?= __("For example: DLFF-0069."); ?></small>
              </div>
              <div class="mb-3 col-md-3 align-self-center">
                <small id="wwff_info" class="btn btn-secondary spw-buttons"></small>
              </div>
            </div>
            <?php } ?>

            <?php if (!$user_pota_to_qso_tab ?? false) { ?>
            <div class="row">
              <div class="mb-3 col-md-9">
                <label for="pota_ref"><?= __("POTA Reference(s)"); ?></label>
                <input class="form-control" id="pota_ref" type="text" name="pota_ref" value="" />
                <small id="potaRefHelp" class="form-text text-muted"><?= __("For example: PA-0150. Multiple values allowed."); ?></small>
              </div>
              <div class="mb-3 col-md-3 align-self-center">
                <small id="pota_info" class="btn btn-secondary spw-buttons"></small>
              </div>
            </div>
            <?php } ?>

            <?php if (!$user_sig_to_qso_tab ?? false) { ?>
            <div class="mb-3">
              <label for="sig"><?= __("Sig"); ?></label>
              <input class="form-control" id="sig" type="text" name="sig" value="" />
              <small id="sigHelp" class="form-text text-muted"><?= __("For example: GMA"); ?></small>
            </div>

            <div class="mb-3">
              <label for="sig_info"><?= __("Sig Info"); ?></label>
              <input class="form-control" id="sig_info" type="text" name="sig_info" value="" />
              <small id="sigInfoHelp" class="form-text text-muted"><?= __("For example: DA/NW-357"); ?></small>
            </div>
            <?php } ?>

            <?php if (!$user_dok_to_qso_tab ?? false) { ?>
            <div class="mb-3">
              <label for="darc_dok"><?= __("DOK"); ?></label>
              <input class="form-control" id="darc_dok" type="text" name="darc_dok" value="" />
              <small id="dokHelp" class="form-text text-muted"><?= __("For example: Q03"); ?></small>
            </div>
            <?php } ?>

          </div>

          <!-- Satellite Panel -->
          <div class="tab-pane fade" id="satellite" role="tabpanel" aria-labelledby="satellite-tab">
            <div class="mb-3">
              <label for="sat_name"><?= __("Satellite Name"); ?></label>

              <input list="satellite_names" id="sat_name" type="text" name="sat_name" class="form-control" value="<?php echo $this->session->userdata('sat_name'); ?>">

              <datalist id="satellite_names" class="satellite_names_list"></datalist>
            </div>

            <div class="mb-3">
              <label for="sat_mode"><?= __("Satellite Mode"); ?></label>

              <input list="satellite_modes" id="sat_mode" type="text" name="sat_mode" class="form-control" value="<?php echo $this->session->userdata('sat_mode'); ?>">

              <datalist id="satellite_modes" class="satellite_modes_list"></datalist>
            </div>

            <div class="mb-3">
              <label for="ant_az"><?= __("Antenna Azimuth (°)"); ?></label>
              <input type="number" step="0.1" min="0" max="360" class="form-control" id="ant_az" name="ant_az" />
              <small id="azHelp" class="form-text text-muted"><?= __("Antenna azimuth in decimal degrees."); ?></small>
            </div>

            <div class="mb-3">
              <label for="ant_el"><?= __("Antenna Elevation (°)"); ?></label>
              <input type="number" step="0.1" min="0" max="90" class="form-control" id="ant_el" name="ant_el" />
              <small id="elHelp" class="form-text text-muted"><?= __("Antenna elevation in decimal degrees."); ?></small>
            </div>
          </div>

          <!-- Notes Panel Contents -->
          <div class="tab-pane fade" id="nav-notes" role="tabpanel" aria-labelledby="notes-tab">
           <div class="mb-3">
              <label for="notes"><?= __("Notes"); ?></label>
              <textarea  type="text" class="form-control" id="notes" name="notes" rows="10"></textarea>
              <div class="small form-text text-muted"><?= __("Note: Gets exported to third-party services.") ?></div>
            </div>
          </div>

          <!-- QSL Tab -->
          <div class="tab-pane fade" id="qsl" role="tabpanel" aria-labelledby="qsl-tab">

            <div class="mb-3 row">
              <label for="sent" class="col-sm-3 col-form-label"><?= __("Sent"); ?></label>
              <div class="col-sm-9">
                <select class="form-select" id="sent" name="qsl_sent">
                  <option value="N" selected="selected"><?= __("No"); ?></option>
                  <option value="Y"><?= __("Yes"); ?></option>
                  <option value="R"><?= __("Requested"); ?></option>
                  <option value="Q"><?= __("Queued"); ?></option>
                  <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                </select>
              </div>
            </div>

            <div class="mb-3 row">
              <label for="sent-method" class="col-sm-3 col-form-label"><?= __("Method"); ?></label>
              <div class="col-sm-9">
                <select class="form-select" id="sent-method" name="qsl_sent_method">
                 <option value="" selected="selected"><?= __("Method"); ?></option>
                 <option value="D"><?= __("Direct"); ?></option>
                 <option value="B"><?= __("Bureau"); ?></option>
                 <option value="E"><?= __("Electronic"); ?></option>
                 <option value="M"><?= __("Manager"); ?></option>
                </select>
              </div>
            </div>

            <div class="mb-3 row">
              <label for="qsl_via" class="col-sm-2 col-form-label"><?= __("Via"); ?></label>
              <div class="col-sm-10">
                <input type="text" id="qsl_via" class="form-control" name="qsl_via" value="" />
              </div>
            </div>

           <div class="mb-3">
            <label for="qslmsg"><?= __("QSL MSG"); ?><span class="qso_eqsl_qslmsg_update" title="<?= __("Get the default message for eQSL, for this station."); ?>"><i class="fas fa-redo-alt"></i></span></label>
						<label class="position-absolute end-0 mb-2 me-3" for="qslmsg" id="charsLeft"> </label>
            <textarea  type="text" class="form-control" id="qslmsg" name="qslmsg" rows="5" maxlength="240"><?php echo $qslmsg; ?></textarea>
            <div class="small form-text text-muted"><?= __("Note: Gets exported to third-party services.") ?></div>
            <div id="qslmsg_hide" style="display:none;"><?php echo $qslmsg; ?></div>
            </div>
          </div>
        </div>



        <div class="info">
          <input size="20" id="country" type="hidden" name="country" value="" />
        </div>

        <div class="btn-group" role="group">
              <button tabindex="22" type="button" class="btn btn-secondary" id="btn_reset"><?= __("Clear"); ?></button>
        <button id="btnGroupDrop1" type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                <li><a class="dropdown-item" href="#" id="btn_fullreset"><?= __("Reset to Default"); ?></a></li>
            </ul>
        </div>
        <button tabindex="21" type="submit" id="saveQso" name="saveQso" class="btn btn-primary"><i class="fas fa-save"></i> <?= __("Save QSO"); ?></button>
        <div class="alert alert-danger warningOnSubmit mt-3" style="display:none;"><span><i class="fas fa-times-circle"></i></span> <span class="warningOnSubmit_txt ms-1"><?= __("Error"); ?></span></div>
      </div>
    </form>
    </div>
  </div>


  <div class="col-sm-7">

<div id="noticer" role="alert"></div>
<?php if($notice) { ?>
<div id="notice-alerts" class="alert alert-info" role="alert">
  <?php echo $notice; ?>
</div>
<?php } ?>

<?php if(validation_errors()) { ?>
<div id="notice-alerts" class="alert alert-warning" role="alert">
  <?php echo validation_errors(); ?>
</div>
<?php } ?>

    <!-- QSO Map -->
    <div class="card qso-map">
            <div id="qsomap" class="map-leaflet" style="width: 100%; height: 200px;"></div>
    </div>

    <div id="radio_status"></div>

    <!-- Winkey Starts -->

   <?php
    // if isWinkeyEnabled in session data is true
    if ($this->session->userdata('isWinkeyEnabled')) { ?>

    <div id="winkey" class="card winkey-settings" style="margin-bottom: 10px;">
        <div class="card-header">
			<h4 style="font-size: 16px; font-weight: bold;" class="card-title"><?= __("Winkey"); ?>

			<button id="connectButton" class="btn btn-sm btn-primary"><?= __("Connect"); ?></button>

			<button id="winkey_settings" type="button" class="btn-sm btn btn-secondary" class="btn btn-primary"><i class="fas fa-cog"></i> <?= __("Settings"); ?></button>

			</h4>
        </div>

        <div id="winkey_buttons" class="card-body">
			<div class="form-inline d-flex align-items-center mb-2">
				<button onclick="stop_cw_sending()" class="btn btn-sm btn-danger" style="margin-left: 2px; margin-right: 2px;"><?= __("Stop"); ?></button>
				<button onclick="send_carrier()" id="send_carrier" class="btn btn-sm btn-danger" style="margin-left: 2px; margin-right: 2px;"><?= __("Tune"); ?></button>
				<button hidden id="stop_carrier" onclick="stop_carrier()" class="btn btn-sm btn-danger" style="margin-left: 2px; margin-right: 2px;"><?= __("Stop Tune"); ?></button>
				<button id="morsekey_func1" onclick="morsekey_func1()" class="btn btn-sm btn-warning" style="margin-left: 2px; margin-right: 2px;">F1</button>
				<button id="morsekey_func2" onclick="morsekey_func2()" class="btn btn-sm btn-warning" style="margin-left: 2px; margin-right: 2px;">F2</button>
				<button id="morsekey_func3" onclick="morsekey_func3()" class="btn btn-sm btn-warning" style="margin-left: 2px; margin-right: 2px;">F3</button>
				<button id="morsekey_func4" onclick="morsekey_func4()" class="btn btn-sm btn-warning" style="margin-left: 2px; margin-right: 2px;">F4</button>
				<button id="morsekey_func5" onclick="morsekey_func5()" class="btn btn-sm btn-warning" style="margin-left: 2px; margin-right: 2px;">F5</button>
				<label class="mx-2 mb-1 w-auto" for="cwspeed"><?= __("CW Speed"); ?></label>
				<input class="w-auto form-control form-control-sm" type="number" id="winkeycwspeed" name="cwspeed" min="1" max="100" value="20" step="1">
			</div>

			<input id="sendText" type="text" class="form-control mb-1">
			<button id="sendButton" type="button" class="btn btn-sm btn-success"><?= __("Send"); ?></button>

			<span id="statusBar"></span>

        </div>
    </div>
    <?php } // end of isWinkeyEnabled if statement ?>
    <!-- Winkey Ends -->

    <div class="card callsign-suggest">
        <div class="card-header"><h4 style="font-size: 16px; font-weight: bold;" class="card-title"><?= __("Suggestions"); ?></h4></div>

        <div class="card-body callsign-suggestions"></div>
    </div>

    <?php if ($this->session->userdata('user_show_profile_image')) { ?>
    <div class="card callsign-image" id="callsign-image" style="display: none;">
        <div class="card-header"><h4 style="font-size: 16px; font-weight: bold;" class="card-title"><?= __("Profile Picture"); ?></h4></div>

        <div class="card-body callsign-image">
            <div class="callsign-image-content" id="callsign-image-content">
            </div>
        </div>
    </div>
    <?php } ?>

    <div class="card previous-qsos">
      <div class="card-header"><h4 class="card-title" id="timesWorked" style="font-size: 16px; font-weight: bold;"><?= __("Previous Contacts"); ?></h4></div>

        <div id="partial_view" style="font-size: 0.95rem;"></div>

		<?php
		$result = $this->optionslib->get_option('disable_refresh_past_contacts');
		if($result === null) { ?>
			<div id="qso-last-table" hx-get="<?php echo site_url('/qso/component_past_contacts'); ?>" hx-trigger="load, qso_event, every 5s">
		<?php } else { ?>
			<div id="qso-last-table" hx-get="<?php echo site_url('/qso/component_past_contacts'); ?>" hx-trigger="load, qso_event">
		<?php } ?>

        </div>
      </div>
      <small class="mt-0.5" style="float: right;"><?= __("Max. 5 previous contacts are shown"); ?></small>
    </div>
  </div>

</div>

</div>
