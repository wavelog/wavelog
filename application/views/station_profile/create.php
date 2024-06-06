
<div class="container" id="create_station_profile">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<div class="card">
  <div class="card-header">
    <?php echo $page_title; ?>
  </div>
  <div class="card-body">
    <h5 class="card-title"></h5>
    <p class="card-text"></p>

		<?php if($this->session->flashdata('notice')) { ?>
			<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
			</div>
		<?php } ?>

		<?php $this->load->helper('form'); ?>

		<?php echo validation_errors(); ?>

		<form method="post" action="<?php echo site_url('station/create'); ?>" name="create_profile">
		  <div class="mb-3">
		    <label for="stationNameInput"><?php echo __("Profile Name"); ?></label>
		    <input type="text" class="form-control" name="station_profile_name" id="stationNameInput" aria-describedby="stationNameInputHelp" placeholder="Home QTH" required>
		    <small id="stationNameInputHelp" class="form-text text-muted"><?php echo __("Shortname for the station location. For example: Home (HM54ip)"); ?></small>
		  </div>

			<div class="mb-3">
		    <label for="stationCallsignInput"><?php echo __("Station Callsign"); ?></label>
		    <input type="text" class="form-control" name="station_callsign" id="stationCallsignInput" aria-describedby="stationCallsignInputHelp" placeholder="4W7EST" required>
		    <small id="stationCallsignInputHelp" class="form-text text-muted"><?php echo __("Station callsign. For example: 4W7EST/P"); ?></small>
		  </div>

			<div class="mb-3">
		    <label for="stationPowerInput"><?php echo __("Station Power (W)"); ?></label>
		    <input type="number" class="form-control" name="station_power" id="stationPowerInput" step="1" aria-describedby="stationPowerInputHelp" placeholder="10">
		    <small id="stationPowerInputHelp" class="form-text text-muted"><?php echo __("Default station power in Watt. Overwritten by CAT."); ?></small>
		  </div>
		  <div class="mb-3">
		    <label for="stationDXCCInput"><?php echo __("Station DXCC"); ?></label>
				<?php if ($dxcc_list->num_rows() > 0) { ?>
				<select class="form-select" id="dxcc_id" name="dxcc" aria-describedby="stationCallsignInputHelp">
				<option value="0" selected><?php echo "- " . __("None") . " -"; ?></option>
				<?php foreach ($dxcc_list->result() as $dxcc) { ?>
				<option value="<?php echo $dxcc->adif; ?>"><?php echo ucwords(strtolower($dxcc->name)) . ' - ' . $dxcc->prefix; if ($dxcc->end != NULL) echo ' ('.__("Deleted DXCC").')';?>
				</option>
				<?php } ?>
				</select>
				<?php } ?>
		    <small id="stationDXCCInputHelp" class="form-text text-muted"><?php echo __("Station DXCC entity. For example: Scotland"); ?></small>
			<div class="alert alert-danger" role="alert" id="warningMessageDXCC" style="display: none"> </div>
		  </div>

		  <div class="mb-3">
		    <label for="stationCityInput"><?php echo __("Station City"); ?></label>
		    <input type="text" class="form-control" name="city" id="stationCityInput" aria-describedby="stationCityInputHelp">
		    <small id="stationCityInputHelp" class="form-text text-muted"><?php echo __("Station city. For example: Inverness"); ?></small>
		  </div>

        <!-- State -->
		<div class="mb-3" id="location_state">
			<label for="stateInput" id="stateInputLabel"></label>
			<select class="form-select" name="station_state" id="stateDropdown">
				<option value=""></option>
			</select>
			<small id="StateHelp" class="form-text text-muted"><?php echo __("Station state. Applies to certain countries only. Leave blank if not applicable."); ?></small>
		</div>

		<!-- US County -->
		<div class="mb-3" id="location_us_county">
			<label for="stationCntyInput"><?php echo __("Station County"); ?></label>
			<input type="text" class="form-control" name="station_cnty" id="stationCntyInputEdit" aria-describedby="stationCntyInputHelp">
			<small id="stationCntyInputHelp" class="form-text text-muted"><?php echo __("Station County (Only used for USA/Alaska/Hawaii)."); ?></small>
		</div>

		<div class="row">
			<div class="mb-3 col-sm-6">
				<label for="stationCQZoneInput"><?php echo __("CQ Zone"); ?></label>
				<select class="form-select" id="stationCQZoneInput" name="station_cq" required>
					<?php
					for ($i = 1; $i<=40; $i++) {
						echo '<option value='. $i;

						echo '>'. $i .'</option>';
					}
					?>
				</select>
				<small id="stationCQInputHelp" class="form-text text-muted"><?php echo __("If you don't know your CQ Zone then")." <a href='https://zone-check.eu/?m=cq' target='_blank'>".__("click here")."</a> ".__("to find it!"); ?></small>
			</div>

			<div class="mb-3 col-sm-6">
				<label for="stationITUZoneInput"><?php echo __("ITU Zone"); ?></label>
				<select class="form-select" id="stationITUZoneInput" name="station_itu" required>
					<?php
					for ($i = 1; $i<=90; $i++) {
						echo '<option value='. $i;

						echo '>'. $i .'</option>';
					}
					?>
				</select>
				<small id="stationITUInputHelp" class="form-text text-muted"><?php echo __("If you don't know your ITU Zone then")." <a href='https://zone-check.eu/?m=itu' target='_blank'>".__("click here")."</a> ".__("to find it!"); ?></small>
			</div>
		</div>

		  <div class="mb-3">
		    <label for="stationGridsquareInput"><?php echo __("Station Gridsquare"); ?></label>

			<div class="input-group mb-3">
			<input type="text" class="form-control" name="gridsquare" id="stationGridsquareInput" aria-describedby="stationGridInputHelp" required>
			<div class="input-group-append">
				<button type="button" class="btn btn-outline-secondary" onclick="getLocation()"><i class="fas fa-compass"></i> <?php echo __("Get Gridsquare"); ?></button>
			</div>
			</div>

		    <small id="stationGridInputHelp" class="form-text text-muted"><?php echo lang("station_location_gridsquare_hint_ln1"); ?></small>
		    <small id="stationGridInputHelp" class="form-text text-muted"><?php echo __("If you are located on a grid line, enter multiple grid squares separated with commas. For example: IO77,IO78,IO87,IO88."); ?></small>
		  </div>

            <div class="mb-3">
                <label for="stationIOTAInput"><?php echo __("IOTA Reference"); ?></label>
                <select class="form-select" name="iota" id="stationIOTAInput" aria-describedby="stationIOTAInputHelp" placeholder="EU-005">
                    <option value =""></option>

                    <?php
                    foreach($iota_list as $i){
                        echo '<option value=' . $i->tag . '>' . $i->tag . ' - ' . $i->name . '</option>';
                    }
                    ?>

                </select>
                <small id="stationIOTAInputHelp" class="form-text text-muted"><?php echo __("Station IOTA reference. For example: EU-005"); ?></small>
                <small id="stationIOTAInputHelp" class="form-text text-muted"><?php echo lang("station_location_iota_hint_ln2"); ?></small>
            </div>

		  <div class="mb-3">
		    <label for="stationSOTAInput"><?php echo __("SOTA Reference"); ?></label>
		    <input type="text" class="form-control" name="sota" id="stationSOTAInput" aria-describedby="stationSOTAInputHelp">
		    <small id="stationSOTAInputHelp" class="form-text text-muted"><?php echo lang("station_location_sota_hint_ln1"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationWWFFInput"><?php echo __("WWFF Reference"); ?></label>
		    <input type="text" class="form-control" name="wwff" id="stationWWFFInput" aria-describedby="stationWWFFInputHelp">
		    <small id="stationWWFFInputHelp" class="form-text text-muted"><?php echo lang("station_location_wwff_hint_ln1"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationPOTAInput"><?php echo __("POTA Reference(s)"); ?></label>
		    <input type="text" class="form-control" name="pota" id="stationPOTAInput" aria-describedby="stationPOTAInputHelp">
		    <small id="stationPOTAInputHelp" class="form-text text-muted"><?php echo lang("station_location_pota_hint_ln1"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationSigInput"><?php echo __("Signature"); ?></label>
		    <input type="text" class="form-control" name="sig" id="stationSigInput" aria-describedby="stationSigInputHelp">
		    <small id="stationSigInputHelp" class="form-text text-muted"><?php echo __("Station Signature (e.g. GMA).."); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationSigInfoInput"><?php echo __("Signature Information"); ?></label>
		    <input type="text" class="form-control" name="sig_info" id="stationSigInfoInput" aria-describedby="stationSigInfoInputHelp">
		    <small id="stationSigInfoInput" class="form-text text-muted"><?php echo __("Station Signature Info (e.g. DA/NW-357)."); ?></small>
		  </div>

            <div class="mb-3">
                <label for="eqslNickname">eQSL QTH Nickname</label> <!-- This does not need Multilanguage Support -->
                <input type="text" class="form-control" name="eqslnickname" id="eqslNickname" aria-describedby="eqslhelp">
                <small id="eqslhelp" class="form-text text-muted"><?php echo __("The QTH Nickname which is configured in your eQSL Profile"); ?></small>
            </div>

			<div class="mb-3">
				<label for="eqslDefaultQSLMsg"><?php echo __("Default QSLMSG"); ?></label>
				<label class="position-absolute end-0 mb-2 me-3" for="eqslDefaultQSLMsg" id="charsLeft"> </label>
				<textarea class="form-control" name="eqsl_default_qslmsg" id="eqslDefaultQSLMsg" aria-describedby="eqsldefaultqslmsghelp" maxlength="240" rows="2" style="width:100%;"></textarea>
				<small id="eqsldefaultqslmsghelp" class="form-text text-muted"><?php echo __("Define a default message that will be populated and sent for each QSO for this station location."); ?></small>
			</div>
			<div class="mb-3">
				<label for="clublogignore"><?php echo __("Ignore Clublog Upload"); ?></label>
				<select class="form-select" id="clublogignore" name="clublogignore">
					<option value="1" ><?php echo __("Yes"); ?></option>
					<option value="0" selected><?php echo __("No"); ?></option>
				</select>
				<small class="form-text text-muted"><?php echo __("If enabled, the QSOs made from this location will not be uploaded to Clublog. If this is deactivated on it's own please check if the Call is properly configured at Clublog"); ?></small>
			</div>
            <div class="mb-3" id="clublogrealtimediv">
				<label for="clublogrealtime"><?php echo __("ClubLog Realtime Upload"); ?></label>
				<select class="form-select" id="clublogrealtime" name="clublogrealtime">
					<option value="1"><?php echo __("Yes"); ?></option>
					<option value="0" selected><?php echo __("No"); ?></option>
				</select>
			</div>

            <div class="row">
				<div class="mb-3 col-sm-3">                                                                                                                                                    
					<label for="hrdlog_username"><?php echo __("HRDLog.net Username"); ?></label> 
                    <input type="text" class="form-control" name="hrdlog_username" id="hrdlog_username" aria-describedby="hrdlog_usernameHelp">
                    <small id="hrdlog_usernameHelp" class="form-text text-muted"><?php echo __("The username you are registered with at HRDlog.net (usually your callsign)."); ?></a></small>
                </div>
                <div class="mb-3 col-sm-3">                                                                                                                                                    
					<label for="hrdlog_code"><?php echo __("HRDLog.net API Key"); ?></label>
                    <input type="text" class="form-control" name="hrdlog_code" id="hrdlog_code" aria-describedby="hrdlog_codeHelp">
                    <small id="hrdlog_codeHelp" class="form-text text-muted"><?php echo lang("station_location_hrdlog_code_hint"); ?></a></small>
                </div>
                <div class="mb-3 col-sm-3">
                    <label for="hrdlogrealtime"><?php echo __("HRDLog.net Logbook Realtime Upload"); ?></label>                                                                                                                 
					<select class="form-select" id="hrdlogrealtime" name="hrdlogrealtime">
                        <option value="1"><?php echo __("Yes"); ?></option>
                        <option value="0" selected><?php echo __("No"); ?></option>
                    </select>
                </div>
            </div>

			<div class="alert alert-warning" role="alert">
				<?php echo "QRZ.com - " . __("Subscription Required"); ?>
			</div>

            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label for="qrzApiKey">QRZ.com Logbook API Key</label>  <!-- This does not need Multilanguage Support -->
					<div class="input-group">
                    	<input type="text" class="form-control" name="qrzapikey" pattern="^([A-F0-9]{4}-){3}[A-F0-9]{4}$" id="qrzApiKey" aria-describedby="qrzApiKeyHelp">
						<button class="btn btn-secondary" type="button" id="qrz_apitest_btn">Test API-Key</button>
					</div>
					<div class="alert mt-3" style="display: none;" id="qrz_apitest_msg"></div>
                    <small id="qrzApiKeyHelp" class="form-text text-muted"><?php echo lang("station_location_qrz_hint"); ?></a></small>
                </div>
                <div class="mb-3 col-sm-6">
                    <label for="qrzrealtime"><?php echo __("QRZ.com Logbook Upload"); ?></label>
                    <select class="form-select" id="qrzrealtime" name="qrzrealtime">
                        <option value="-1" selected><?php echo __("Disabled"); ?></option>
                        <option value="1"><?php echo __("Realtime"); ?></option>
                        <option value="0"><?php echo __("Enabled"); ?></option>
                    </select>
                </div>
            </div>

			<div class="row">
				<div class="mb-3 col-sm-6">
					<label for="webadifApiKey"> QO-100 Dx Club API Key </label> <!-- This does not need Multilanguage Support -->
					<input type="text" class="form-control" name="webadifapikey" id="webadifApiKey" aria-describedby="webadifApiKeyHelp">
					<small id="webadifApiKeyHelp" class="form-text text-muted"><?php echo lang("station_location_qo100_hint"); ?></a></small>
				</div>
				<div class="mb-3 col-sm-6">
					<label for="webadifrealtime"><?php echo __("QO-100 Dx Club Realtime Upload"); ?></label>
					<select class="form-select" id="webadifrealtime" name="webadifrealtime">
						<option value="1"><?php echo __("Yes"); ?></option>
						<option value="0" selected><?php echo __("No"); ?></option>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label for="oqrs"><?php echo __("OQRS Enabled"); ?></label>
				<select class="form-select" id="oqrs" name="oqrs">
					<option value="0"><?php echo __("No"); ?></option>
					<option value="1"><?php echo __("Yes"); ?></option>
				</select>
			</div>
			<div class="mb-3">
						<label for="oqrs"><?php echo __("OQRS Email alert"); ?></label>
						<select class="form-select" id="oqrsemail" name="oqrsemail">
						<option value="0"><?php echo __("No"); ?></option>
						<option value="1"><?php echo __("Yes"); ?></option>
						</select>
						<small id="oqrsemailHelp" class="form-text text-muted"><?php echo __("Make sure email is set up under admin and global options."); ?></small>
					</div>
			<div class="mb-3">
				<label for="oqrstext"><?php echo __("OQRS Text"); ?></label>
				<input type="text" class="form-control" name="oqrstext" id="oqrstext" aria-describedby="oqrstextHelp">
				<small id="oqrstextHelp" class="form-text text-muted"><?php echo __("Some info you want to add regarding QSL'ing."); ?></small>
			</div>

			<button type="submit" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?php echo __("Create"); ?> <?php echo __("Station Location"); ?></button>

		</form>
  </div>
</div>

<br>

</div>
