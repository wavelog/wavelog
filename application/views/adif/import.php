<div class="container adif" id="adif_import">

    <h2><?php echo $page_title; ?></h2>
    <?php
    $showtab = '';
    if (isset($tab)) {
        $showtab = $tab;
    }
    ?>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs pull-right" role="tablist">
                <?php
                // Define all available tabs
                $all_tabs = ['import', 'export', 'lotw', 'dcl', 'pota', 'cbr'];

                // Use allowed_tabs if set, otherwise show all tabs
                $tabs_to_show = isset($allowed_tabs) ? $allowed_tabs : $all_tabs;
                ?>

                <?php if (in_array('import', $tabs_to_show)): ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($showtab == '' || $showtab == 'adif') {
                                                    echo 'active';
                                                } ?>" id="import-tab" data-bs-toggle="tab" href="#import" role="tab" aria-controls="import" aria-selected="<?php if ($showtab == '' || $showtab == 'adif') {
                                                                                                echo 'true';
                                                                                            } else {
                                                                                                echo 'false';
                                                                                            } ?>"><?= __("ADIF Import") ?></a>
                </li>
                <?php endif; ?>

                <?php if (in_array('export', $tabs_to_show)): ?>
                <li class="nav-item">
                    <a class="nav-link" id="export-tab" data-bs-toggle="tab" href="#export" role="tab" aria-controls="export" aria-selected="false"><?= __("ADIF Export") ?></a>
                </li>
                <?php endif; ?>

                <?php if (in_array('dcl', $tabs_to_show)): ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($showtab == 'dcl') {
                                                    echo 'active';
                                                } ?>" id="dcl-tab" data-bs-toggle="tab" href="#dcl" role="tab" aria-controls="dcl" aria-selected="<?php if ($showtab == 'dcl') {
                                                                                                        echo 'true';
                                                                                                    } else {
                                                                                                        echo 'false';
                                                                                                    } ?>"><?= __("DARC DCL") ?></a>
                </li>
                <?php endif; ?>

                <?php if (in_array('pota', $tabs_to_show)): ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($showtab == 'potab') {
                                                    echo 'active';
                                                } ?>" id="potab-tab" data-bs-toggle="tab" href="#potab" role="tab" aria-controls="potab" aria-selected="<?php if ($showtab == 'potab') {
                                                                                                        echo 'true';
                                                                                                    } else {
                                                                                                        echo 'false';
                                                                                                    } ?>"><?= __("POTA") ?></a>
                </li>
                <?php endif; ?>

                <?php if (in_array('cbr', $tabs_to_show)): ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($showtab == 'cbr') {
                                                    echo 'active';
                                                } ?>" id="cbr-tab" data-bs-toggle="tab" href="#cbr" role="tab" aria-controls="cbr" aria-selected="<?php if ($showtab == 'cbr') {
                                                                                                        echo 'true';
                                                                                                    } else {
                                                                                                        echo 'false';
                                                                                                    } ?>"><?= __("CBR Import") ?></a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                <?php if (in_array('import', $tabs_to_show)): ?>
                <div class="tab-pane <?php if ($showtab == '' || $showtab == 'adif') {
                                                    echo 'active';
                                                } else {
                                                    echo 'fade';
                                                } ?>" id="import" role="tabpanel" aria-labelledby="import-tab">

                    <?php if (isset($error) && ($showtab == '' || $showtab == 'adif')) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <p><span class="badge text-bg-warning"><?= __("Important") ?></span> <?= __("Log Files must have the file type *.adi") ?></p>
                    <p><span class="badge text-bg-warning"><?= __("Warning") ?></span> <?= __("Maximum file upload size is ") ?><?php echo $max_upload; ?>B.</p>

                    <form class="form" id="upform" action="<?php echo site_url('adif/import'); ?>" method="post" enctype="multipart/form-data">
						<div class="row mb-4">
							<div class="col-md-6">
								<input type="hidden" name="fhash" id="fhash" value="<?php echo hash('sha256', $this->session->userdata('user_callsign')); ?>">

								<div class="small form-text text-muted"><?= __("Select Station Location") ?></div>
								<select name="station_profile" class="form-select mb-2 me-sm-2">
									<option value="0"><?= __("Select Station Location") ?></option>
									<?php foreach ($station_profile->result() as $station) { ?>
										<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $active_station_id) {
																								echo " selected =\"selected\"";
																							} ?>><?= __("Callsign") . ": " ?><?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)</option>
									<?php } ?>
								</select>

								<div class="small form-text text-muted"><?= __("Choose ADIF File") ?></div>
								<input class="form-control" type="file" name="userfile" id="userfile" accept=".adi,.ADI,.adif,.ADIF" />

							</div>

							<div class="col-md-6">
								<div class="small form-text text-muted"><?= __("Add QSOs to Contest") ?></div>
								<select name="contest" id="contest" class="form-select mb-2 me-sm-2">
									<option value="" selected><?= __("No Contest"); ?></option>
									<?php
									foreach ($contests as $contest) {
										echo '<option value="' . $contest['adifname'] . '">' . $contest['name'] . '</option>';
									} ?>
								</select>
								<?php
								$show_operator_question = true;
								if ($this->config->item('special_callsign') && (!empty($club_operators))) {
									$show_operator_question = false; ?>
									<div class="small form-text text-muted"><?= __("Type in the operators callsign of the imported QSOs. Leave empty to use the operator callsign in the ADIF file.") ?></div>
									<input type="text"  name="club_operator" class="form-control mb-2 me-sm-2" value="<?php echo ($this->session->userdata('cd_src_call') ?? ''); ?>">
								<?php } ?>
							</div>
						</div>

						<div class="card mb-3">
							<div class="card-header">
								<h6 class="mb-0"><?= __("Basic Settings") ?></h6>
							</div>
							<div class="card-body">
								<div class="mb-3 row">
									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="skipDuplicate" value="1" id="skipDuplicate">
											<label class="form-check-label" for="skipDuplicate"><?= __("Import duplicate QSOs") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if QSOs shall be imported, even if they already exist.") ?></div>
									</div>
										<div class="col-md-6">
											<div class="form-check-inline">
												<input class="form-check-input" type="checkbox" name="dxccAdif" value="1" id="dxccAdif">
													<label class="form-check-label" for="dxccAdif"><?= __("Use DXCC information from ADIF") ?></label>
											</div>
											<div class="small form-text text-muted"><?= __("If not selected, Wavelog will attempt to determine DXCC information automatically.") ?></div>
										</div>
								</div>

								<div class="mb-3 row">
								<?php if ($cd_p_level != 6){ ?>
									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="skipStationCheck" value="1" id="skipStationCheck">
											<label class="form-check-label" for="skipStationCheck"><span class="badge text-bg-warning"><?= __("DANGER") ?></span> <?= __("Ignore Station callsign on import") ?></label>
										</div>
										<div class="small form-text text-muted"><?= sprintf(__("If selected, Wavelog will try to import %sall%s QSOs from the ADIF, regardless if they match to the chosen station-location."), '<b>', '</b>'); ?></div>
									</div>

								<?php } if (($show_operator_question) && ($cd_p_level != 6)){ ?>
										<div class="col-md-6">
											<div class="form-check-inline">
												<input class="form-check-input" type="checkbox" name="operatorName" value="1" id="operatorName">
												<label class="form-check-label" for="operatorName"><?= __("Always use the logged-in account callsign as the operator call during import") ?></label>
											</div>
										</div>
								<?php } ?>
								</div>
							</div>
						</div>

						<?php if ($cd_p_level != 6) { ?>
						<div class="card mb-3">
							<div class="card-header">
								<h6 class="mb-0"><?= __("Mark QSOs as uploaded (This does NOT upload QSOs to these services!)") ?></h6>
							</div>
							<div class="card-body">
								<div class="mb-3 row">
									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markLotw" value="1" id="markLotwImport">
											<label class="form-check-label" for="markLotwImport"><?= __("Mark imported QSOs as uploaded to LoTW") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>

									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markEqsl" value="1" id="markEqslImport">
											<label class="form-check-label" for="markEqslImport"><?= __("Mark imported QSOs as uploaded to eQSL Logbook") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>
								</div>

								<div class="mb-3 row">
									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markHrd" value="1" id="markHrdImport">
											<label class="form-check-label" for="markHrdImport"><?= __("Mark imported QSOs as uploaded to HRDLog.net Logbook") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>

									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markQrz" value="1" id="markQrzImport">
											<label class="form-check-label" for="markQrzImport"><?= __("Mark imported QSOs as uploaded to QRZ Logbook") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>
								</div>

								<div class="mb-3 row">
									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markClublog" value="1" id="markClublogImport">
											<label class="form-check-label" for="markClublogImport"><?= __("Mark imported QSOs as uploaded to Clublog Logbook") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>

									<div class="col-md-6">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markDcl" value="1" id="markDclImport">
											<label class="form-check-label" for="markDclImport"><?= __("Mark imported QSOs as uploaded to DCL Logbook") ?></label>
										</div>
										<div class="small form-text text-muted"><?= __("Select if ADIF being imported does not contain this information.") ?></div>
									</div>
								</div>
								<button type="button" class="btn mb-2 btn-sm btn-success" onclick="toggleAll(this)"><?= __("Toggle all checkboxes") ?></button>
							</div>
						</div>
						<?php } ?>


                        <button id="prepare_sub" class="btn btn-sm btn-primary mb-2 ld-ext-right" value="Upload"><?= __("Upload") ?><div class="ld ld-ring ld-spin"></div></button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (in_array('export', $tabs_to_show)): ?>
                <div class="tab-pane fade" id="export" role="tabpanel" aria-labelledby="export-tab">

                    <form class="form" action="<?php echo site_url('adif/export_custom'); ?>" method="post" enctype="multipart/form-data">

                        <h5 class="card-title"><?= __("Take your logbook file anywhere!") ?> </h5>
                        <p class="card-text"><?= __("Exporting ADIFs allows you to import contacts into third party applications like LoTW, Awards or just for keeping a backup.") ?> </p>
                        <p class="card-text"><?= sprintf(_pgettext("", "If you need more filtering, you can use %sthe Advanced Logbook%s to filter and export!"), '<a href="' . site_url('logbookadvanced') . '">', "</a>"); ?>
						<div class="row mb-4">
							<div class="col-md-12">
								<div class="col-md-6">
									<div class="small form-text text-muted"><?= __("Select Station Location") ?></div>
									<select name="station_profile" class="form-select mb-2 me-sm-2">
										<option value="0"><?= __("All") ?></option>
										<?php foreach ($station_profile->result() as $station) { ?>
											<option value="<?php echo $station->station_id; ?>"
												<?php if ($station->station_id == $this->stations->find_active()) {
													echo " selected =\"selected\"";
												} ?>>
												<?= __("Callsign") . ": " ?><?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)
											</option>
										<?php } ?>
									</select>
								</div>
							</div>

							<div class="col-md-12">
								<div class="col-md-6">
									<div class="row">
										<div class="col-6">
											<div class="small form-text text-muted"><?= __("From date") . ":"; ?></div>
											<input name="from" id="from" type="date" class="form-control">
										</div>
										<div class="col-6">
											<div class="small form-text text-muted"><?= __("To date") . ":"; ?></div>
											<input name="to" id="to" type="date" class="form-control">
										</div>
									</div>
								</div>
							</div>

						</div>

						<div class="card mb-3">
							<div class="card-header">
								<h6 class="mb-0"><?= __("Export options") ?></h6>
							</div>
							<div class="card-body">
                						<?php if (in_array('lotw', $tabs_to_show)) {?>
								<div class="mb-3 row">
									<div class="col-md-10">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="markLotw" value="1" id="markLotwExport">
											<label class="form-check-label" for="markLotwExport"><?= __("Mark exported QSOs as already uploaded to LoTW") ?></label>
										</div>
									</div>
								</div>
								<?php } ?>
								<div class="mb-3 row">
									<div class="col-md-10">
										<div class="form-check-inline">
											<input class="form-check-input" type="checkbox" name="exportLotw" value="1" id="exportLotw">
											<label class="form-check-label" for="exportLotw"><?= __("Export QSOs not uploaded to LoTW") ?></label>
										</div>
									</div>
								</div>
							</div>
						</div>

                        <button type="submit" class="btn btn-sm btn-primary" value="Export"><?= __("Export QSOs") ?></button>
                    </form>

                    <br><br>

                    <h5><?= __("Export Satellite-Only QSOs") ?></h5>
                    <p><a href="<?php echo site_url('adif/exportsat'); ?>" title="Export All Satellite Contacts" target="_blank" class="btn btn-sm btn-primary"><?= __("Export All Satellite QSOs") ?></a></p>

                    <p><a href="<?php echo site_url('adif/exportsatlotw'); ?>" title="Export All Satellite QSOs Confirmed on LoTW" target="_blank" class="btn btn-sm btn-primary"><?= __("Export All Satellite QSOs Confirmed on LoTW") ?></a></p>
                </div>
                <?php endif; ?>

                <?php if (in_array('dcl', $tabs_to_show)): ?>
                <div class="tab-pane <?php if ($showtab == 'dcl') {
                                                    echo 'active';
                                                } else {
                                                    echo 'fade';
                                                } ?>" id="dcl" role="tabpanel" aria-labelledby="dcl-tab">
                    <?php if (isset($error) && $showtab == 'dcl') { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <p class="card-text"><?= sprintf(__("Go to %s and export your logbook with confirmed DOKs. To speed up the process you can select only DL QSOs to download (i.e. put 'DL' into Prefix List). The downloaded ADIF file can be uploaded here in order to update QSOs with DOK info."), "<a href='http://dcl.darc.de/dml/export_adif_form.php' target='_blank'>" . __("DARC DCL") . "</a>") ?> <?= sprintf(__("More information regarding the confirmation status in DCL can be found on the %sDCL Confluence page%s."), '<a target="_blank" href="https://confluence.darc.de/pages/viewpage.action?pageId=21037270">', '</a>'); ?></p>
                    <form class="form" action="<?php echo site_url('adif/dcl'); ?>" method="post" enctype="multipart/form-data">

                        <div class="mb-3 row">
                            <div class="col-md-10">
                                <div class="form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="onlyConfirmed" value="1" id="onlyConfirmed" checked>
                                    <label class="form-check-label" for="onlyConfirmed"><?= __("Only import DOK data from QSOs confirmed on DCL.") ?></label>
                                </div>
                                <div class="small form-text text-muted"><?= __("Uncheck if you also want to update DOK with data from unconfirmed QSOs in DCL.") ?></div>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-10">
                                <div class="form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="overwriteDok" value="1" id="overwriteDok">
                                    <label class="form-check-label" for="overwriteDok"><span class="badge text-bg-warning"><?= __("Warning") ?></span> <?= __("Overwrites exisiting DOK in log by DCL (if different).") ?></label>
                                </div>
                                <div class="small form-text text-muted"><?= __("If checked Wavelog will forcibly overwrite existing DOK with DOK from DCL log.") ?></div>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-10">
                                <div class="form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="ignoreAmbiguous" value="1" id="ignoreAmbiguous" checked>
                                    <label class="form-check-label" for="ignoreAmbiguous"><?= __("Ignore QSOs that cannot be matched.") ?></label>
                                </div>
                                <div class="small form-text text-muted"><?= __("If unchecked, information about QSOs which could not be found in Wavelog will be displayed.") ?></div>
                            </div>
                        </div>
                        <input class="form-control w-auto mb-2 me-sm-2" type="file" name="userfile" size="20" accept=".adi,.ADI,.adif,.ADIF" />
                        <button type="submit" class="btn btn-sm btn-primary mb-2" value="Upload"><?= __("Upload") ?></button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (in_array('pota', $tabs_to_show)): ?>
                <div class="tab-pane <?php if ($showtab == 'potab') {
                                                    echo 'active';
                                                } else {
                                                    echo 'fade';
                                                } ?>" id="potab" role="tabpanel" aria-labelledby="potab-tab">
                    <?php if (isset($error) && $showtab == 'potab') { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <p><span class="badge text-bg-warning"><?= __("Important") ?></span> <?= ("This function can be used to import POTA references from POTA hunter log in ADIF format. It will only import references for existing QSOs. It will not import QSO data itself.") ?></p>
                    <p><?= sprintf(__("An example for a tool to export the POTA hunter log in ADIF format is %s made by AF0G and available on GitHub."), '<a target="_blank" href="https://github.com/adamfast/pota-hunter-log-adif">'.'pota-hunter-log-adif'.'</a>'); ?></p>
                    <form class="form" action="<?php echo site_url('adif/pota'); ?>" method="post" enctype="multipart/form-data">

                        <input class="form-control w-auto mb-2 me-sm-2" type="file" name="userfile" size="20" accept=".adi,.ADI,.adif,.ADIF" />
                        <button type="submit" class="btn btn-sm btn-primary mb-2" value="Upload"><?= __("Upload") ?></button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (in_array('cbr', $tabs_to_show)): ?>
                <div class="tab-pane <?php if ($showtab == 'cbr') {
                                            echo 'active';
                                        } else {
                                            echo 'fade';
                                        } ?>" id="cbr" role="tabpanel" aria-labelledby="home-tab">
                    <?php if (isset($error) && $showtab == 'cbr') { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <p class="card-text"><span class="badge text-bg-info"><?= __("Information"); ?></span> <?= __("If you imported an ADIF file of a contest, provided by another logging software, sometimes, depending on that software, your exchanges will not be imported properly from that softwares ADIF. If you like to correct that, you can provide the Cabrillo file that this software also provides to rewrite that data in Wavelog.") ?></p>
                    <p class="card-text"><span class="badge text-bg-warning"><?= __("Important"); ?></span> <?= __("Please use this function before changing anything about the QSOs in Wavelog, as this function uses the Contest ID, as well as date and time information from both your already imported ADIF file, as well as the CBR file you are about to upload to match the QSOs and only correct relevant data.") ?></p>
                    <form class="form" action="<?php echo site_url('cabrillo/cbrimport'); ?>" method="post" enctype="multipart/form-data">

                        <div class="mb-3 row">
                            <div class="col-md-10">
                                <div class="small form-text text-muted"><span class="badge text-bg-success"><?= __("Optional"); ?></span> <?= __("Contest Name, only if Contest ID in CBR is different") ?></div>
                                <select name="contest_id" id="contest_id" class="form-select mb-2 me-sm-2 w-50 w-lg-100">
                                    <option value="" selected><?= __("No Contest"); ?></option>
                                    <?php
                                    foreach ($contests as $contest) {
                                        echo '<option value="' . $contest['adifname'] . '">' . $contest['name'] . '</option>';
                                    } ?>
                                </select>
                                <div class="form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="trx_number_present" value="1" id="serial_number_present" unchecked>
                                    <label class="form-check-label" for="trx_number_present"><?= __("The CBR file contains a TRX number at the end of each QSO line (for multi-op stations)") ?></label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="serial_number_present" value="1" id="serial_number_present" unchecked>
                                    <label class="form-check-label" for="serial_number_present"><?= __("A serial number is ALWAYS part of the exchange for both parties in this contest.") ?></label>
                                </div>
                                <div class="small form-text text-muted"><?= __("If you or your partner only sometimes exchange serial numbers, please leave this unchecked.") ?></div>
                                <div class="small form-text text-muted"><?= __("If unchecked, this will erase the default serial number that (for example) N1MM+ produces. If checked, it will correct the serial number if necessary.") ?></div>
                            </div>
                        </div>
                        <input class="form-control w-auto mb-2 me-sm-2" type="file" name="userfile" size="20" accept=".cbr,.CBR" />
                        <button type="submit" class="btn btn-sm btn-primary mb-2" value="Upload"><?= __("Upload") ?></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
