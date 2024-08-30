<div class="container qso_panel contesting">
    <button type="button" class="btn btn-sm btn-warning float-end" onclick="reset_contest_session()"><i class="fas fa-sync-alt"></i> <?= __("Start new Contest Session"); ?></button>
    <h2 style="display:inline"><?= __("Contest Logging"); ?> </h2> <?php echo ($manual_mode == 0 ? " <span style='display:inline' class='align-text-top badge text-bg-success'>LIVE</span>" : " <span style='display:inline' class='align-text-top badge text-bg-danger'>POST</span>");  ?>
    <div class="row">

        <div class="col-sm-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="qso_input" name="qsos">
                        <div class="mb-3 row">
							<label class="col-auto control-label" for="radio"><?= __("Exchange Type"); ?></label>

							<div class="col-auto">
								<select class="form-select form-select-sm" id="exchangetype" name="exchangetype">
									<option value='None'><?= __("None"); ?></option>
									<option value='Exchange'><?= __("Exchange"); ?></option>
									<option value='Gridsquare'><?= __("Gridsquare"); ?></option>
									<option value='Serial'><?= __("Serial"); ?></option>
									<option value='Serialexchange'><?= __("Serial + Exchange"); ?></option>
									<option value='Serialgridsquare'><?= __("Serial + Gridsquare"); ?></option>
                                    <option value='SerialGridExchange'><?= __("Serial + Gridsquare + Exchange"); ?></option>
								</select>
							</div>

                            <label class="col-auto control-label" for="contestname"><?= __("Contest Name"); ?></label>

                            <div class="col-auto">
                                <select class="form-select form-select-sm" id="contestname" name="contestname">
									<?php foreach($contestnames as $contest) {
										echo "<option value='" . $contest['adifname'] . "'>" . $contest['name'] . "</option>";
									} ?>
                                </select>
                            </div>

                            <label class="col-auto control-label" for="operatorcall"><?= __("Operator Callsign"); ?></label>
                            <div class="col-auto">
                                <input type="text" class="form-control form-control-sm" id="operator_callsign" name="operator_callsign" value='<?php echo $this->session->userdata('operator_callsign'); ?>' required>
                            </div>
                            <div class="col-auto">
                                <a class="btn btn-sm btn-primary" id="moreSettingsButton"><i class="fas fa-wrench"></i> <?= __("More Settings"); ?></a>
                                <div class="modal fade" id="moreSettingsModal" tabindex="-1" aria-labelledby="moreSettingsModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="moreSettingsModalLabel"><?= __("More Settings"); ?></h5>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <label class="col-auto control-label"><?= __("Copy received exchange to"); ?></label>
                                                    <div class="form-check-inline col-auto">
                                                        <select class="form-select form-select-sm" id="copyexchangeto" name="copyexchangeto" title="<?= __("Exchange is only copied if it is matching rules for the selected field!"); ?>">
                                                            <option value='None'><?= __("None"); ?></option>
                                                            <option value='dok'><?= __("DOK"); ?></option>
                                                            <option value='name'><?= __("Name"); ?></option>
                                                            <option value='age'><?= __("Age"); ?></option>
                                                            <option value='state'><?= __("State"); ?></option>
                                                            <option value='power'><?= __("RX Power (W)"); ?></option>
                                                            <option value='locator'><?= __("Locator"); ?></option>
                                                            <option value='qth'><?= __("QTH"); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <label class="col-auto control-label me-1"><?= __("Sequence of Exchanges"); ?>
                                                        <i class="fas fa-question-circle" id="exchangesequence" 
                                                            data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" 
                                                            title="<?= __("Choose in which order you want to type in the different reports. However, only the elements contained in the selected exchange type are displayed."); ?>">
                                                        </i>
                                                    </label>
                                                    <div class="form-check-inline col-auto">
                                                        <select class="form-select form-select-sm" id="exchangesequence_select" name="exchangesequence_select">
                                                            <option value='s-g-e'><?= _pgettext("Keep the translation short!", "Serial"); ?> > <?= _pgettext("Keep the translation short!", "Grid"); ?> > <?= _pgettext("Keep the translation short!", "Exchange"); ?></option>
                                                            <option value='s-e-g'><?= _pgettext("Keep the translation short!", "Serial"); ?> > <?= _pgettext("Keep the translation short!", "Exchange"); ?> > <?= _pgettext("Keep the translation short!", "Grid"); ?></option>
                                                            <option value='g-s-e'><?= _pgettext("Keep the translation short!", "Grid"); ?> > <?= _pgettext("Keep the translation short!", "Serial"); ?> > <?= _pgettext("Keep the translation short!", "Exchange"); ?></option>
                                                            <option value='g-e-s'><?= _pgettext("Keep the translation short!", "Grid"); ?> > <?= _pgettext("Keep the translation short!", "Exchange"); ?> > <?= _pgettext("Keep the translation short!", "Serial"); ?></option>
                                                            <option value='e-s-g'><?= _pgettext("Keep the translation short!", "Exchange"); ?> > <?= _pgettext("Keep the translation short!", "Serial"); ?> > <?= _pgettext("Keep the translation short!", "Grid"); ?></option>
                                                            <option value='e-g-s'><?= _pgettext("Keep the translation short!", "Exchange"); ?> > <?= _pgettext("Keep the translation short!", "Grid"); ?> > <?= _pgettext("Keep the translation short!", "Serial"); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-2">
                                <label for="start_date"><?= __("Date"); ?></label>
                                <input type="text" class="form-control form-control-sm input_date" name="start_date" id="start_date" value="<?php if (($this->session->userdata('start_date') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo $this->session->userdata('start_date'); } else { echo date('d-m-Y');}?>" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> >
                            </div>

                            <div class="mb-3 col-md-1">
                                <label for="start_time"><?= __("Time"); ?></label>
                                <input type="text" class="form-control form-control-sm input_time" name="start_time" id="start_time" value="<?php if (($this->session->userdata('start_time') != NULL && ((time() - $this->session->userdata('time_stamp')) < 24 * 60 * 60))) { echo substr($this->session->userdata('start_time'),0,5); } else { echo $manual_mode == 0 ? date('H:i:s') : date('H:i'); } ?>" size="7" <?php echo ($manual_mode == 0 ? "disabled" : "");  ?> >
                            </div>

                            <?php if ( $manual_mode == 0 ) { ?>
                              <input class="input_time" type="hidden" id="start_time"  name="start_time"value="<?php echo date('H:i'); ?>" />
                              <input class="input_date" type="hidden" id="start_date" name="start_date" value="<?php echo date('d-m-Y'); ?>" />
                            <?php } ?>

                            <div class="mb-3 col-md-2">
                                <label for="mode"><?= __("Mode"); ?></label>
                                <select id="mode" class="form-select mode form-select-sm" name="mode">
                                    <?php foreach($modes->result() as $mode) {
                                            if ($mode->submode == null) {
                                                printf("<option value=\"%s\" %s>%s</option>", $mode->mode, $this->session->userdata('mode')==$mode->mode?"selected=\"selected\"":"",$mode->mode);
                                            } else {
                                                printf("<option value=\"%s\" %s>&rArr; %s</option>", $mode->submode, $this->session->userdata('mode')==$mode->submode?"selected=\"selected\"":"",$mode->submode);
                                            }
                                    } ?>
                                </select>
                            </div>

                            <div class="mb-3 col-md-2">
                                <label for="band"><?= __("Band"); ?></label>

                                <select id="band" class="form-select form-select-sm" name="band">
                                <?php foreach($bands as $key=>$bandgroup) {
                                    echo '<optgroup label="' . strtoupper($key) . '">';
                                    foreach($bandgroup as $band) {
                                        echo '<option value="' . $band . '"';
                                        if ($this->session->userdata('band') == $band) echo ' selected';
                                        echo '>' . $band . '</option>'."\n";
                                    }
                                    echo '</optgroup>';
                                    }
                                ?>
                                </select>
                            </div>

                            <div class="mb-3 col-md-2">
                                <label for="frequency"><?= __("Frequency"); ?></label>
                                <input type="text" class="form-control form-control-sm" id="frequency" name="freq_display" value="<?php echo $this->session->userdata('freq'); ?>" />
                            </div>

                            <div class="mb-3 col-md-2">
                                <label for="inputRadio"><?= __("Radio"); ?></label>
                                <select class="form-select form-select-sm radios" id="radio" name="radio">
                                    <option value="0" selected="selected"><?= __("None"); ?></option>
                                        <?php foreach ($radios->result() as $row) { ?>
                                        <option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?php echo $row->radio; ?> <?php if ($radio_last_updated->id == $row->id) { echo "(".__("last updated").")"; } else { echo ''; } ?></option>
                                        <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div>
                                    <label for="callsign"><?= __("Callsign"); ?></label>
                                    <input type="text" class="form-control form-control-sm" id="callsign" name="callsign" required>
                                    <small id="callsign_info" class="badge text-bg-danger"></small><br/>
                                    <small id="bearing_info" class="form-text text-muted"></small>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div>
                                    <label for="rst_sent"><?= __("RST (S)"); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="rst_sent" id="rst_sent" value="59">
                                </div>
                            </div>

                            <div id="sent_exchange" class="d-flex gap-2">
                                <div style="display:none" class="serials">
                                    <label for="exch_serial_s"><?= __("Serial (S)"); ?></label>
                                    <input type="number" class="form-control form-control-sm" name="exch_serial_s" id="exch_serial_s" min="0" value="">
                                </div>

                                <div style="display:none" class="gridsquares">
                                    <label for="exch_gridsquare_s"><?= __("Gridsquare (S)"); ?></label>
                                    <input disabled type="text" class="form-control form-control-sm" name="exch_gridsquare_s" id="exch_gridsquare_s" value="<?php echo $my_gridsquare;?>">
                                </div>

                                <div style="display:none" class="exchanges">
                                    <label for="exch_sent"><?= __("Exch (S)"); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="exch_sent" id="exch_sent" value="">
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div>
                                    <label for="rst_rcvd"><?= __("RST (R)"); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="rst_rcvd" id="rst_rcvd" value="59">
                                </div>
                            </div>
                            
                            <div id="rcvd_exchange" class="d-flex gap-2">
                                <div style="display:none" class="serialr">
                                    <label for="exch_serial_r"><?= __("Serial (R)"); ?></label>
                                    <input type="number" class="form-control form-control-sm" name="exch_serial_r" id="exch_serial_r" min="0" value="">
                                </div>

                                <div style="display:none" class="gridsquarer">
                                    <label for="exch_gridsquare_r"><?= __("Gridsquare (R)"); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="locator" id="exch_gridsquare_r" value="">
                                </div>

                                <div style="display:none" class="exchanger">
                                    <label for="exch_rcvd"><?= __("Exch (R)"); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="exch_rcvd" id="exch_rcvd" value="">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-5">
                                <label for="name"><?= __("Name"); ?></label>
                                <input type="text" class="form-control form-control-sm" name="name" id="name" value="">
                            </div>

                            <div class="mb-3 col-md-5">
                                <label for="comment"><?= __("Comment"); ?></label>
                                <input type="text" class="form-control form-control-sm" name="comment" id="comment" value="">
                            </div>
                        </div>

                        <button type="button" class="mb-3 btn btn-sm btn-secondary" onclick="reset_log_fields()"><i class="fas fa-sync-alt"></i> <?= __("Reset QSO"); ?></button>
                        <button type="button" class="mb-3 btn btn-sm btn-primary" onclick="logQso();"><i class="fas fa-save"></i> <?= __("Save QSO"); ?></button>
                    </form>
                </div>
            </div>

            <br/>

            <!-- Callsign SCP Box -->
            <div class="card callsign-suggest">
                <div class="card-header"><h5 class="card-title"><?= __("Callsign Suggestions"); ?></h5></div>

                <div class="card-body callsign-suggestions"></div>
            </div>

            <!-- Past QSO Box -->
            <div class="card log">
                <div class="card-header"><h5 class="card-title"><?= __("Contest Logbook"); ?></h5></div>
                <p>

                <table style="width:100%" class="table-sm table qsotable table-bordered table-hover table-striped table-condensed text-center">
                    <thead>
                        <tr class="log_title titles">
                            <th><?= __("Date"); ?>/<?= __("Time"); ?></th>
                            <th><?= __("Call"); ?></th>
                            <th><?= __("Band"); ?></th>
                            <th><?= __("Mode"); ?></th>
                            <th><?= __("RST (S)"); ?></th>
                            <th><?= __("RST (R)"); ?></th>
                            <th><?= __("Exch (S)"); ?></th>
                            <th><?= __("Exch (R)"); ?></th>
							<th><?= __("Serial (S)"); ?></th>
							<th><?= __("Serial (R)"); ?></th>
							<th><?= __("Gridsquare"); ?></th>
							<th><?= __("VUCC Gridsquare"); ?></th>
                        </tr>
                    </thead>

                    <tbody class="contest_qso_table_contents">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
?>
