<script>
	var lang_export_reg1testedi_proceed = '<?= __("Proceed") ?>';
	var lang_export_reg1testedi_select_year = "<?= __("Select Year") ?>";
	var lang_export_reg1testedi_select_contest = '<?= __("Select Contest") ?>';
	var lang_export_reg1testedi_select_date_range = '<?= __("Select Date Range") ?>'; 
	var lang_export_reg1testedi_select_band = '<?= __("Select Band") ?>';
	var lang_export_reg1testedi_no_contests_for_stationlocation = '<?= __("No contests were found for this station location!") ?>';
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
				<div class="mb-3 d-flex align-items-center row contestbands">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="sentexchange"><?= __("Sent Exchange"); ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="sentexchange" type="sentexchange" name="sentexchange" aria-label="sentexchange">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="club"><?= __("Club"); ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="club" type="club" name="club" aria-label="club">
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
					<div class="col-md-3 control-label" for="responsible_operator"><?= __("Callsign of responsible operator") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="responsible_operator" type="text" name="responsible_operator" aria-label="responsible_operator">
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="operators"><?= __("Operators") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="operators" type="operators" name="operators" aria-label="operators">
					</select>
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="contestaddress1"><?= __("Contest Address 1") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="contestaddress1" type="text" name="contestaddress1" aria-label="contestaddress1">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="contestaddress2"><?= __("Contest Address 2") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="contestaddress2" type="text" name="contestaddress2" aria-label="contestaddress2">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="address1"><?= __("Operator Address 1") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="address1" type="text" name="address1" aria-label="address1">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="address2"><?= __("Operator Address 2") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="address2" type="text" name="address2" aria-label="address2">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresspostalcode"><?= __("Operator Address Postalcode") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresspostalcode" type="text" name="addresspostalcode" aria-label="addresspostalcode">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresscity"><?= __("Operator Address City") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresscity" type="text" name="addresscity" aria-label="addresscity">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="addresscountry"><?= __("Operator Address Country") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="addresscountry" type="text" name="addresscountry" aria-label="addresscountry">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="operatorphone"><?= __("Operator Phone Number") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="operatorphone" type="text" name="operatorphone" aria-label="operatorphone">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="txequipment"><?= __("Transmit Equipment Description") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="txequipment" type="text" name="txequipment" aria-label="txequipment">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="power"><?= __("Transmit Power (W)") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="power" type="text" name="power" aria-label="power">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="rxequipment"><?= __("Receive Equipment Description") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="rxequipment" type="text" name="rxequipment" aria-label="rxequipment">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="antenna"><?= __("Antenna Description") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="antenna" type="text" name="antenna" aria-label="antenna">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="antennaheight"><?= __("Antenna Height Above Ground (m)") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="antennaheight" type="text" name="antennaheight" aria-label="antennaheight">
				</div>
				<div hidden="true" class="mb-3 d-flex align-items-center row additionalinfo">
					<div class="col-md-3 control-label" for="soapbox"><?= __("Soapbox") ?>: </div>
					<input class="form-control my-1 me-sm-2 col-md-4 w-auto" id="soapbox" type="text" name="soapbox" aria-label="soapbox">
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