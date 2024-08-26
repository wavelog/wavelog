<script>
	var lang_export_reg1testedi_proceed = '<?= __("Proceed") ?>';
	var lang_export_reg1testedi_select_year = "<?= __("Select Year") ?>";
	var lang_export_reg1testedi_select_contest = '<?= __("Select Contest") ?>';
	var lang_export_reg1testedi_select_date_range = '<?= __("Select Date Range") ?>';
	var lang_export_reg1testedi_select_band = '<?= __("Select Band") ?>';
	var lang_export_reg1testedi_no_contests_for_stationlocation = '<?= __("No contests were found for this station location!") ?>';
	var lang_export_reg1testedi_bandhint = '<?= __("Bands below 50Mhz are not valid for the EDI REG1TEST format and will produce invalid files.") ?>';
</script>
<div class="container">

	<br>

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Export a contest to a REG1TEST EDI log"); ?>
		</div>
		<div class="card-body">

			<?php
			echo '<div class="contests">';


			if ($station_profile) { ?>

				<form class="form" action="<?php echo site_url('reg1test/export'); ?>" method="post" enctype="multipart/form-data">
					<div class="mb-3 d-flex align-items-center row">
						<div class="col-md-4 control-label" for="station_id"><?= __("Select Station Location"); ?> </div>
						<select id="station_id" name="station_id" class="form-select my-1 me-sm-2 col-md-6 w-25 w-lg-75">
							<?php foreach ($station_profile->result() as $station) { ?>
								<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $this->stations->find_active()) { echo " selected =\"selected\""; } ?>><?= __("Callsign") ?> <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)</option>
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
					<div class="mb-3 d-flex align-items-center row contestbands">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="sentexchange"><?= __("Sent Exchange"); ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="6" id="sentexchange" type="sentexchange" name="sentexchange" aria-label="sentexchange">
						<small id="sentexchange_hint" class="form-text text-muted col-md-4"><?= __("The exchange which was sent during the contest. Can be any type of information, e.g. Province, DOK, County, State, Power, Name. Max. length: 6 characters."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="club"><?= __("Club"); ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="club" type="club" name="club" aria-label="club">
						<small id="club_hint" class="form-text text-muted col-md-4"><?= __("Describes the callsign of the radio club where operator(s) are member. E.g. can be used if points are accumulated to the club."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="categoryoperator"><?= __("Category Operator") ?> </div>
						<select class="form-select my-1 me-sm-2 col-md-6 w-25 w-lg-75" id="categoryoperator" name="categoryoperator">
							<option value="SINGLE-OP">SINGLE-OP</option>
							<option value="MULTI-OP">MULTI-OP</option>
							<option value="CHECKLOG">CHECKLOG</option>
						</select>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="responsible_operator"><?= __("Callsign of responsible operator") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="responsible_operator" type="text" name="responsible_operator" aria-label="responsible_operator">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="operators"><?= __("Operators") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="operators" type="operators" name="operators" aria-label="operators">
						<small id="operators_hint" class="form-text text-muted col-md-4"><?= __("List of all operators. Seperated with a semicolon ';'. The responsible operator is not needed here."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="contestaddress1"><?= __("Contest Address 1") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="contestaddress1" type="text" name="contestaddress1" aria-label="contestaddress1">
						<small id="contestaddress1_hint" class="form-text text-muted col-md-4"><?= __("Address of the QTH used during the contest."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="contestaddress2"><?= __("Contest Address 2") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="contestaddress2" type="text" name="contestaddress2" aria-label="contestaddress2">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="address1"><?= __("Operator Address 1") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="address1" type="text" name="address1" aria-label="address1">
						<small id="address1_hint" class="form-text text-muted col-md-4"><?= __("Address of the responsible operator."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="address2"><?= __("Operator Address 2") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="address2" type="text" name="address2" aria-label="address2">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="addresspostalcode"><?= __("Operator Address Postalcode") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="addresspostalcode" type="text" name="addresspostalcode" aria-label="addresspostalcode">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="addresscity"><?= __("Operator Address City") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="addresscity" type="text" name="addresscity" aria-label="addresscity">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="addresscountry"><?= __("Operator Address Country") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="addresscountry" type="text" name="addresscountry" aria-label="addresscountry">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="operatorphone"><?= __("Operator Phone Number") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="operatorphone" type="text" name="operatorphone" aria-label="operatorphone">
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="txequipment"><?= __("Transmit Equipment Description") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="txequipment" type="text" name="txequipment" aria-label="txequipment">
						<small id="txequipment_hint" class="form-text text-muted col-md-4"><?= __("Short description of the used equipment."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="power"><?= __("Transmit Power (W)") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="power" type="text" name="power" aria-label="power">
						<small id="power_hint" class="form-text text-muted col-md-4"><?= __("TX Power during the contest in Watt."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="rxequipment"><?= __("Receive Equipment Description") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="rxequipment" type="text" name="rxequipment" aria-label="rxequipment">
						<small id="rxequipment_hint" class="form-text text-muted col-md-4"><?= __("If you used another gear for RX, then describe it here."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="antenna"><?= __("Antenna Description") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="antenna" type="text" name="antenna" aria-label="antenna">
						<small id="antenna_hint" class="form-text text-muted col-md-4"><?= __("What kind of antenna was used."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="antennaheight"><?= __("Antenna Height Above Ground (m)") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" maxlength="75" id="antennaheight" type="text" name="antennaheight" aria-label="antennaheight">
						<small id="antennaheight_hint" class="form-text text-muted col-md-4"><?= __("Height of the antenna above the ground."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="soapbox"><?= __("Soapbox") ?> </div>
						<input class="form-control my-1 me-sm-2 col-md-6 w-25 w-lg-75" id="soapbox" type="text" name="soapbox" aria-label="soapbox">
						<small id="soapbox_hint" class="form-text text-muted col-md-4"><?= __("Any other remarks."); ?></small>
					</div>
					<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
						<div class="col-md-4 control-label" for="button1id"></div>
						<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary w-auto"> <?= __("Export") ?></button>
					</div>
				</form>

			<?php } else {
				echo __("No contests were found in your log.");
			}
			?>

		</div>
	</div>
</div>