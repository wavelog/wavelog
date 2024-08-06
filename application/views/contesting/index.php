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
                                        <option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?php echo $row->radio; ?></option>
                                        <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="callsign"><?= __("Callsign"); ?></label>
                                <input type="text" class="form-control form-control-sm" id="callsign" name="callsign" required>
                                <small id="callsign_info" class="badge text-bg-danger"></small><br/>
                                <small id="bearing_info" class="form-text text-muted"></small>
                            </div>

                            <div class="mb-3 col-md-1">
                                <label for="rst_sent"><?= __("RST (S)"); ?></label>
                                <input type="text" class="form-control form-control-sm" name="rst_sent" id="rst_sent" value="59">
                            </div>

                            <div style="display:none" class="mb-3 col-md-1 serials">
								<label for="exch_serial_s"><?= __("Serial (S)"); ?></label>
								<input type="number" class="form-control form-control-sm" name="exch_serial_s" id="exch_serial_s" min="0" value="">
							</div>

                            <div style="display:none" class="mb-3 col-md-1 exchanges">
                                <label for="exch_sent"><?= __("Exch (S)"); ?></label>
                                <input type="text" class="form-control form-control-sm" name="exch_sent" id="exch_sent" value="">
                            </div>

							<div style="display:none" class="mb-3 col-md-2 gridsquares">
								<label for="exch_gridsquare_s"><?= __("Gridsquare (S)"); ?></label>
								<input disabled type="text" class="form-control form-control-sm" name="exch_gridsquare_s" id="exch_gridsquare_s" value="<?php echo $my_gridsquare;?>">
							</div>

                            <div class="mb-3 col-md-1">
                                <label for="rst_rcvd"><?= __("RST (R)"); ?></label>
                                <input type="text" class="form-control form-control-sm" name="rst_rcvd" id="rst_rcvd" value="59">
                            </div>

                            <div style="display:none" class="mb-3 col-md-1 serialr">
								<label for="exch_serial_r"><?= __("Serial (R)"); ?></label>
								<input type="number" class="form-control form-control-sm" name="exch_serial_r" id="exch_serial_r" min="0" value="">
							</div>

							<div style="display:none" class="mb-3 col-md-1 exchanger">
								<label for="exch_rcvd"><?= __("Exch (R)"); ?></label>
								<input type="text" class="form-control form-control-sm" name="exch_rcvd" id="exch_rcvd" value="">
							</div>

							<div style="display:none" class="mb-3 col-md-2 gridsquarer">
								<label for="exch_gridsquare_r"><?= __("Gridsquare (R)"); ?></label>
								<input type="text" class="form-control form-control-sm" name="locator" id="exch_gridsquare_r" value="">
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
                        <div class="row">
                            <label class="col-auto control-label" for="radio"><?= __("Copy received exchange to"); ?></label>
                            <div class="form-check-inline col-auto">
                                <select class="form-select form-select-sm" id="copyexchangeto" name="copyexchangeto" title="<?= __("Exchange is only copied if it is matching rules for the selected field!"); ?>">
                                    <option value='None'><?= __("None"); ?></option>
                                    <option value='dok'><?= __("DOK"); ?></option>
                                    <option value='name'><?= __("Name"); ?></option>
                                    <option value='age'><?= __("Age"); ?></option>
                                    <option value='state'><?= __("State"); ?></option>
                                    <option value='power'><?= __("RX Power (W)"); ?></option>
                                    <option value='locator'><?= __("Locator"); ?></option>
                                </select>
                            </div>
                      </div>
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
