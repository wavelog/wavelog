<script>
	var lang_export_cabrillo_proceed = '<?= __("Proceed") ?>';
	var lang_export_cabrillo_select_year = "<?= __("Select Year") ?>";
	var lang_export_cabrillo_select_contest = '<?= __("Select Contest") ?>';
	var lang_export_cabrillo_select_date_range = '<?= __("Select Date Range") ?>'; 
	var lang_export_cabrillo_no_contests_for_stationlocation = '<?= __("No contests were found for this station location!") ?>';
</script>
<div class="container">

    <br>

    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
			<?= __("Export a contest to a Cabrillo log"); ?>
        </div>
        <div class="card-body">

		<?php
		  echo '<div class="contests">';


		  if ($station_profile) { ?>

			<form class="form" action="<?php echo site_url('cabrillo/export'); ?>" method="post" enctype="multipart/form-data">
				<div class="mb-3 d-flex align-items-center row">
					<div class="col-md-3 control-label" for="station_id"><?= __("Select Station Location:"); ?> </div>
					<select id="station_id" name="station_id" class="form-select my-1 me-sm-2 col-md-4 w-auto">
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $this->stations->find_active()) { echo " selected =\"selected\""; } ?>><?= __("Callsign") ?>: <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)</option>
					<?php } ?>
					</select>
					<button id="button1id" type="button" onclick="loadYears();" name="button1id" class="btn btn-sm btn-primary w-auto"> <?= __("Proceed") ?></button>
				</div>

				<div class="mb-3 d-flex align-items-center row contestyear">
				</div>
				<div class="mb-3 d-flex align-items-center row contestname">
				</div>
				<div class="mb-3 d-flex align-items-center row contestdates">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="club"><?= __("Club"); ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="club" type="club" name="club" aria-label="club">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="location"><?= __("Location"); ?>: <i class="fas fa-question-circle col-md-1" data-bs-toggle="contestinfo" data-bs-placement="right" title="<?= __("For USA and Canada stations LOCATION must be the ARRL section abbreviation. For foreign stations LOCATION must be 'DX'. This information is required for IARU-HF and for all ARRL and CQ contests.") . "<br><br>" . __("For the RSGB-IOTA contest this information contains the IOTA name (not the IOTA reference code).") . "<br><br>" . __("For the RDXC contest this contains the RDA number.") ?>"></i></div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="location" type="location" name="location" aria-label="location">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categoryoperator"><?= __("Category Operator") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categoryoperator" name="categoryoperator">
						<option value="SINGLE-OP">SINGLE-OP</option>
						<option value="MULTI-OP">MULTI-OP</option>
						<option value="CHECKLOG">CHECKLOG</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categoryassisted"><?= __("Category Assisted") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categoryassisted" name="categoryassisted">
						<option value="NON-ASSISTED">NON-ASSISTED</option>
						<option value="ASSISTED">ASSISTED</option>
					</select>
				</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categoryband"><?= __("Category Band") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categoryband" name="categoryband">
						<option value="ALL"><?= __("All") ?></option>
						<option value="160M">160 M</option>
						<option value="80M">80 M</option>
						<option value="40M">40 M</option>
						<option value="20M">20 M</option>
						<option value="15M">15 M</option>
						<option value="10M">10 M</option>
						<option value="6M">6 M</option>
						<option value="4M">4 M</option>
						<option value="2M">2 M</option>
						<option value="222">222 MHz (1.25 M)</option>
						<option value="432">432 MHz (70 CM)</option>
						<option value="902">902 MHz (33 CM)</option>
						<option value="1.2G">1.2 GHz</option>
						<option value="2.3G">2.3 GHz</option>
						<option value="3.4G">3.4 GHz</option>
						<option value="5.7G">5.7 GHz</option>
						<option value="10G">10 GHz</option>
						<option value="24G">24 GHz</option>
						<option value="47G">47 GHz</option>
						<option value="75G">75 GHz</option>
						<option value="122G">122 GHz</option>
						<option value="134G">134 GHz</option>
						<option value="241G">241 GHz</option>
						<option value="Light"><?= __("Light/Laser") ?></option>
						<option value="VHF-3-BAND and VHF-FM-ONLY (ARRL VHF Contests only)"><?= __("VHF-3-BAND and VHF-FM-ONLY (ARRL VHF Contests only)") ?></option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categorymode"><?= __("Category Mode") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categorymode" name="categorymode">
						<option value="MIXED">MIXED</option>
						<option value="CW">CW</option>
						<option value="DIGI">DIGI</option>
						<option value="FM">FM</option>
						<option value="RTTY">RTTY</option>
						<option value="SSB">SSB</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categorypower"><?= __("Category Power") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categorypower" name="categorypower">
						<option value="LOW">LOW</option>
						<option value="HIGH">HIGH</option>
						<option value="QRP">QRP</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categorystation"><?= __("Category Station") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categorystation" name="categorystation">
						<option value="FIXED">FIXED</option>
						<option value="DISTRIBUTED">DISTRIBUTED</option>
						<option value="MOBILE">MOBILE</option>
						<option value="PORTABLE">PORTABLE</option>
						<option value="ROVER">ROVER</option>
						<option value="ROVER-LIMITED">ROVER-LIMITED</option>
						<option value="ROVER-UNLIMITED">ROVER-UNLIMITED</option>
						<option value="EXPEDITION">EXPEDITION</option>
						<option value="HQ">HQ</option>
						<option value="SCHOOL">SCHOOL</option>
						<option value="EXPLORER">EXPLORER</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categorytransmitter"><?= __("Category Transmitter") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categorytransmitter" name="categorytransmitter">
						<option value="ONE">ONE</option>
						<option value="TWO">TWO</option>
						<option value="LIMITED">LIMITED</option>
						<option value="UNLIMITED">UNLIMITED</option>
						<option value="SWL">SWL</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categorytime"><?= __("Category Time") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categorytime" name="categorytime">
						<option value="6-HOURS"><?= sprintf(__("%d Hours"), 6); ?></option>
						<option value="8-HOURS"><?= sprintf(__("%d Hours"), 8); ?></option>
						<option value="12-HOURS"><?= sprintf(__("%d Hours"), 12); ?></option>
						<option value="24-HOURS"><?= sprintf(__("%d Hours"), 24); ?></option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="categoryoverlay"><?= __("Category Overlay") ?>: </div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="categoryoverlay" name="categoryoverlay">
						<option value="CLASSIC">CLASSIC</option>
						<option value="ROOKIE">ROOKIE</option>
						<option value="TB-WIRES">TB-WIRES</option>
						<option value="YOUTH">YOUTH</option>
						<option value="NOVICE-TECH">NOVICE-TECH</option>
						<option value="YL">YL</option>
						<option value="WIRE-ONLY">WIRE-ONLY</option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="operators"><?= __("Operators") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="operators" type="operators" name="operators" aria-label="operators">
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="soapbox"><?= __("Soapbox") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="soapbox" type="text" name="soapbox" aria-label="soapbox">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="address"><?= __("Address") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="address" type="text" name="address" aria-label="address">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresscity"><?= __("Address City") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresscity" type="text" name="addresscity" aria-label="addresscity">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addressstateprovince"><?= __("Address State/Province") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addressstateprovince" type="text" name="addressstateprovince" aria-label="addressstateprovince">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresspostalcode"><?= __("Address Postalcode") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresspostalcode" type="text" name="addresspostalcode" aria-label="addresspostalcode">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresscountry"><?= __("Address Country") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresscountry" type="text" name="addresscountry" aria-label="addresscountry">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="certificate"><?= __("Certificate") ?>: <i class="fas fa-question-circle col-md-1" data-bs-toggle="contestinfo" data-bs-placement="right" title="<?= __("Indicate if you wish to receive, if eligible, a paper certificate sent via postal mail by the contest sponsor. The contest sponsor may or may not honor this tag.") ?>"></i></div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="certificate" name="certificate">
						<option value=""></option>
						<option value="YES"><?= __("Yes"); ?></option>
						<option value="NO"><?= __("No"); ?></option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="grid_export"><?= __("Include logged grids?") ?>: <i class="fas fa-question-circle col-md-1" data-bs-toggle="contestinfo" data-bs-placement="right" title="<?= __("If the gridsquare was part of the exchange, you should select YES.") ?>"></i></div>
					<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="grid_export" name="grid_export">
						<option value="0" selected><?= __("No"); ?></option>
						<option value="1"><?= __("Yes"); ?></option>
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="button1id"></div>
					<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary w-auto"> <?= __("Export") ?></button>
				</div>
			</form>

			<?php }
			else {
				echo __("No contests were found in your log.");
			}
			?>

        </div>
    </div>
</div>