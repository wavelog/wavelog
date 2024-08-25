<script>
var dxccarray = [];
dxccarray.push({
	adif: 0,
	name: '- None -',
	cq: '',
	itu: '',
});


<?php
if ($dxcc_list->result() > 0) {
	foreach ($dxcc_list->result() as $dxcc) {
	?>
		var dxcc = {
			adif: <?php echo $dxcc->adif; ?>,
			name: "<?php echo $dxcc->name; ?>",
			cq: <?php echo $dxcc->cqz; ?>,
			itu: <?php echo $dxcc->ituz; ?>,
		};
		dxccarray.push(dxcc);
	<?php
	}
}
?>
</script>

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
		    <label for="stationNameInput"><?= __("Location Name"); ?></label>
		    <input type="text" class="form-control" name="station_profile_name" id="stationNameInput" aria-describedby="stationNameInputHelp" placeholder="<?php echo _pgettext("Station Location Setup", "Home QTH"); ?>" required>
		    <small id="stationNameInputHelp" class="form-text text-muted"><?= sprintf(__("Shortname for the station location. For example: %s"), _pgettext("Station Location Setup", "Home QTH")); ?></small>
		  </div>

			<div class="mb-3">
		    <label for="stationCallsignInput"><?= __("Station Callsign"); ?></label>
		    <input type="text" class="form-control" name="station_callsign" id="stationCallsignInput" aria-describedby="stationCallsignInputHelp" placeholder="4W7EST" required>
		    <small id="stationCallsignInputHelp" class="form-text text-muted"><?= __("Station callsign. For example: 4W7EST/P"); ?></small>
		  </div>

			<div class="mb-3">
		    <label for="stationPowerInput"><?= __("Station Power (W)"); ?></label>
		    <input type="number" class="form-control" name="station_power" id="stationPowerInput" step="1" aria-describedby="stationPowerInputHelp" placeholder="10">
		    <small id="stationPowerInputHelp" class="form-text text-muted"><?= __("Default station power in Watt. Overwritten by CAT."); ?></small>
		  </div>
		  <div class="mb-3">
		    <label for="stationDXCCInput"><?= __("Station DXCC"); ?></label>
				<?php if ($dxcc_list->num_rows() > 0) { ?>
				<select class="form-control" id="dxcc_id" name="dxcc" aria-describedby="stationCallsignInputHelp">
				<option value="0" selected><?php echo "- " . _pgettext("DXCC selection", "None") . " -"; ?></option>
				<?php foreach ($dxcc_list->result() as $dxcc) { ?>
				<option value="<?php echo $dxcc->adif; ?>"><?php echo ucwords(strtolower($dxcc->name)) . ' - ' . $dxcc->prefix; if ($dxcc->end != NULL) echo ' ('.__("Deleted DXCC").')';?>
				</option>
				<?php } ?>
				</select>
				<?php } ?>
		    <small id="stationDXCCInputHelp" class="form-text text-muted"><?= __("Station DXCC entity. For example: Bolivia"); ?></small>
			<div class="alert alert-danger" role="alert" id="warningMessageDXCC" style="display: none"> </div>
		  </div>

		  <div class="mb-3">
		    <label for="stationCityInput"><?= __("Station City"); ?></label>
		    <input type="text" class="form-control" name="city" id="stationCityInput" aria-describedby="stationCityInputHelp">
		    <small id="stationCityInputHelp" class="form-text text-muted"><?= __("Station city. For example: Oslo"); ?></small>
		  </div>

        <!-- State -->
		<div class="mb-3" id="location_state">
			<label for="stateInput" id="stateInputLabel"></label>
			<select class="form-select" name="station_state" id="stateDropdown">
				<option value=""></option>
			</select>
			<small id="StateHelp" class="form-text text-muted"><?= __("Station state. Applies to certain countries only."); ?></small>
		</div>

		<!-- US County -->
		<div class="mb-3" id="location_us_county">
			<label for="stationCntyInput"><?= __("Station County"); ?></label>
			<input type="text" class="form-control" name="station_cnty" id="stationCntyInputEdit" aria-describedby="stationCntyInputHelp">
			<small id="stationCntyInputHelp" class="form-text text-muted"><?= __("Station County (Only used for USA/Alaska/Hawaii)."); ?></small>
		</div>

		<div class="row">
			<div class="mb-3 col-sm-6">
				<label for="stationCQZoneInput"><?= __("CQ Zone"); ?></label>
				<select class="form-select" id="stationCQZoneInput" name="station_cq" required>
					<?php
					for ($i = 1; $i<=40; $i++) {
						echo '<option value='. $i;

						echo '>'. $i .'</option>';
					}
					?>
				</select>
				<small id="stationCQInputHelp" class="form-text text-muted"><?= sprintf(_pgettext("uses 'click here'","If you don't know your CQ Zone then %s to find it!"),"<a href='https://zone-check.eu/?m=cq' target='_blank'>".__("click here")."</a> "); ?></small>
			</div>

			<div class="mb-3 col-sm-6">
				<label for="stationITUZoneInput"><?= __("ITU Zone"); ?></label>
				<select class="form-select" id="stationITUZoneInput" name="station_itu" required>
					<?php
					for ($i = 1; $i<=90; $i++) {
						echo '<option value='. $i;

						echo '>'. $i .'</option>';
					}
					?>
				</select>
				<small id="stationITUInputHelp" class="form-text text-muted"><?= sprintf(_pgettext("uses 'click here'","If you don't know your ITU Zone then %s to find it!"),"<a href='https://zone-check.eu/?m=itu' target='_blank'>".__("click here")."</a> "); ?></small>
			</div>
		</div>

		  <div class="mb-3">
		    <label for="stationGridsquareInput"><?= __("Station Gridsquare"); ?></label>

			<div class="input-group mb-3">
			<input type="text" class="form-control" name="gridsquare" id="stationGridsquareInput" aria-describedby="stationGridInputHelp" required>
			<div class="input-group-append">
				<button type="button" class="btn btn-outline-secondary" onclick="getLocation()"><i class="fas fa-compass"></i> <?= __("Get Gridsquare"); ?></button>
			</div>
			</div>

		    <small id="stationGridInputHelp" class="form-text text-muted"><?= sprintf(_pgettext("uses 'click here'", "Station gridsquare. For example: HM54AP. If you don't know your grid square then %s!"), "<a href='https://zone-check.eu/?m=loc' target='_blank'>".__("click here")."</a>"); ?></small><br>
		    <small id="stationGridInputHelp" class="form-text text-muted"><?= __("If you are located on a grid line, enter multiple grid squares separated with commas. For example: IO77,IO78,IO87,IO88."); ?></small>
		  </div>

            <div class="mb-3">
                <label for="stationIOTAInput"><?= __("IOTA Reference"); ?></label>
                <select class="form-select" name="iota" id="stationIOTAInput" aria-describedby="stationIOTAInputHelp" placeholder="EU-005">
                    <option value =""></option>

                    <?php
                    foreach($iota_list as $i){
                        echo '<option value=' . $i->tag . '>' . $i->tag . ' - ' . $i->name . '</option>';
                    }
                    ?>

                </select>
                <small id="stationIOTAInputHelp" class="form-text text-muted"><?= __("Station IOTA reference. For example: EU-005"); ?></small>
                <small id="stationIOTAInputHelp" class="form-text text-muted"><?= sprintf(__("You can look up IOTA references at the %s."), "<a target='_blank' href='https://www.iota-world.org/iota-directory/annex-f-short-title-iota-reference-number-list.html'>".__("IOTA World website")."</a>"); ?></small>
            </div>

		  <div class="mb-3">
		    <label for="stationSOTAInput"><?= __("SOTA Reference"); ?></label>
		    <input type="text" class="form-control" name="sota" id="stationSOTAInput" aria-describedby="stationSOTAInputHelp">
		    <small id="stationSOTAInputHelp" class="form-text text-muted"><?= sprintf(__("Station SOTA reference. You can look up SOTA references at the %s."), "<a target='_blank' href='https://www.sotamaps.org/'>".__("SOTA Maps website")."</a>"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationWWFFInput"><?= __("WWFF Reference"); ?></label>
		    <input type="text" class="form-control" name="wwff" id="stationWWFFInput" aria-describedby="stationWWFFInputHelp">
		    <small id="stationWWFFInputHelp" class="form-text text-muted"><?= sprintf(__("Station WWFF reference. You can look up WWFF references at the %s."), "<a target='_blank' href='https://www.cqgma.org/mvs/'>".__("GMA Map website")."</a>"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationPOTAInput"><?= __("POTA Reference(s)"); ?></label>
		    <input type="text" class="form-control" name="pota" id="stationPOTAInput" aria-describedby="stationPOTAInputHelp">
		    <small id="stationPOTAInputHelp" class="form-text text-muted"><?= sprintf(__("Station POTA reference(s). Multiple comma separated values allowed. You can look up POTA references at the %s."), "<a target='_blank' href='https://pota.app/#/map/'>".__("POTA Map website")."</a>"); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationSigInput"><?= __("Signature Name"); ?></label>
		    <input type="text" class="form-control" name="sig" id="stationSigInput" aria-describedby="stationSigInputHelp">
		    <small id="stationSigInputHelp" class="form-text text-muted"><?= __("Station Signature (e.g. GMA).."); ?></small>
		  </div>

		  <div class="mb-3">
		    <label for="stationSigInfoInput"><?= __("Signature Information"); ?></label>
		    <input type="text" class="form-control" name="sig_info" id="stationSigInfoInput" aria-describedby="stationSigInfoInputHelp">
		    <small id="stationSigInfoInput" class="form-text text-muted"><?= __("Station Signature Info (e.g. DA/NW-357)."); ?></small>
		  </div>

            <div class="mb-3">
                <label for="eqslNickname"><?php echo _pgettext("Probably no translation needed","eQSL QTH Nickname"); ?></label>
                <input type="text" class="form-control" name="eqslnickname" id="eqslNickname" aria-describedby="eqslhelp">
                <small id="eqslhelp" class="form-text text-muted"><?= __("The QTH Nickname which is configured in your eQSL Profile"); ?></small>
            </div>

			<div class="mb-3">
				<label for="eqslDefaultQSLMsg"><?= __("Default QSLMSG"); ?></label>
				<label class="position-absolute end-0 mb-2 me-3" for="eqslDefaultQSLMsg" id="charsLeft"> </label>
				<textarea class="form-control" name="eqsl_default_qslmsg" id="eqslDefaultQSLMsg" aria-describedby="eqsldefaultqslmsghelp" maxlength="240" rows="2" style="width:100%;"></textarea>
				<small id="eqsldefaultqslmsghelp" class="form-text text-muted"><?= __("Define a default message that will be populated and sent for each QSO for this station location."); ?></small>
			</div>
			<div class="mb-3">
				<label for="clublogignore"><?= __("Ignore Clublog Upload"); ?></label>
				<select class="form-select" id="clublogignore" name="clublogignore">
					<option value="1" ><?= __("Yes"); ?></option>
					<option value="0" selected><?= __("No"); ?></option>
				</select>
				<small class="form-text text-muted"><?= __("If enabled, the QSOs made from this location will not be uploaded to Clublog. If this is deactivated on it's own please check if the Call is properly configured at Clublog"); ?></small>
			</div>
            <div class="mb-3" id="clublogrealtimediv">
				<label for="clublogrealtime"><?= __("ClubLog Realtime Upload"); ?></label>
				<select class="form-select" id="clublogrealtime" name="clublogrealtime">
					<option value="1"><?= __("Yes"); ?></option>
					<option value="0" selected><?= __("No"); ?></option>
				</select>
			</div>

            <div class="row">
				<div class="mb-3 col-sm-3">
					<label for="hrdlog_username"><?= __("HRDLog.net Username"); ?></label>
                    <input type="text" class="form-control" name="hrdlog_username" id="hrdlog_username" aria-describedby="hrdlog_usernameHelp">
                    <small id="hrdlog_usernameHelp" class="form-text text-muted"><?= __("The username you are registered with at HRDlog.net (usually your callsign)."); ?></a></small>
                </div>
                <div class="mb-3 col-sm-3">
					<label for="hrdlog_code"><?= __("HRDLog.net API Key"); ?></label>
                    <input type="text" class="form-control" name="hrdlog_code" id="hrdlog_code" aria-describedby="hrdlog_codeHelp">
                    <small id="hrdlog_codeHelp" class="form-text text-muted"><?= sprintf(_pgettext("HRDLog.net Userprofile page", "Create your API Code on your %s"), "<a href='http://www.hrdlog.net/EditUser.aspx' target='_blank'>".__("HRDLog.net Userprofile page")."</a>"); ?></a></small>
                </div>
                <div class="mb-3 col-sm-3">
                    <label for="hrdlogrealtime"><?= __("HRDLog.net Logbook Realtime Upload"); ?></label>
					<select class="form-select" id="hrdlogrealtime" name="hrdlogrealtime">
                        <option value="1"><?= __("Yes"); ?></option>
                        <option value="0" selected><?= __("No"); ?></option>
                    </select>
                </div>
            </div>

			<div class="alert alert-warning" role="alert">
				<?php echo "QRZ.com - " . __("Subscription Required"); ?>
			</div>

            <div class="row">
                <div class="mb-3 col-sm-6">
                    <label for="qrzApiKey"><?php echo _pgettext("Probably no translation needed","QRZ.com Logbook API Key"); ?></label>
					<div class="input-group">
                    	<input type="text" class="form-control" name="qrzapikey" pattern="^([A-F0-9]{4}-){3}[A-F0-9]{4}$" id="qrzApiKey" aria-describedby="qrzApiKeyHelp">
						<button class="btn btn-secondary" type="button" id="qrz_apitest_btn"><?= __("Test API-Key"); ?></button>
					</div>
					<div class="alert mt-3" style="display: none;" id="qrz_apitest_msg"></div>
                    <small id="qrzApiKeyHelp" class="form-text text-muted"><?= sprintf(_pgettext("the QRZ.com Logbook settings page", "Find your API key on %s"), "<a href='https://logbook.qrz.com/logbook' target='_blank'>".__("the QRZ.com Logbook settings page")."</a>"); ?></a></small>
                </div>
                <div class="mb-3 col-sm-6">
                    <label for="qrzrealtime"><?= __("QRZ.com Logbook Upload"); ?></label>
                    <select class="form-select" id="qrzrealtime" name="qrzrealtime">
                        <option value="-1" selected><?= __("Disabled"); ?></option>
                        <option value="1"><?= __("Realtime"); ?></option>
                        <option value="0"><?= __("Enabled"); ?></option>
                    </select>
                </div>
            </div>

			<div class="row">
				<div class="mb-3 col-sm-6">
					<label for="webadifApiKey"><?php echo _pgettext("Probably no translation needed","QO-100 Dx Club API Key"); ?></label>
					<input type="text" class="form-control" name="webadifapikey" id="webadifApiKey" aria-describedby="webadifApiKeyHelp">
					<small id="webadifApiKeyHelp" class="form-text text-muted"><?= sprintf(_pgettext("QO-100 Dx Club's profile page", "Create your API key on your %s"), "<a href='https://qo100dx.club' target='_blank'>".__("QO-100 Dx Club's profile page")."</a>"); ?></a></small>
				</div>
				<div class="mb-3 col-sm-6">
					<label for="webadifrealtime"><?= __("QO-100 Dx Club Realtime Upload"); ?></label>
					<select class="form-select" id="webadifrealtime" name="webadifrealtime">
						<option value="1"><?= __("Yes"); ?></option>
						<option value="0" selected><?= __("No"); ?></option>
					</select>
				</div>
			</div>

<?php if (!($this->config->item('disable_oqrs') ?? false)) { ?>
			<div class="mb-3">
				<label for="oqrs"><?= __("OQRS Enabled"); ?></label>
				<select class="form-select" id="oqrs" name="oqrs">
					<option value="0"><?= __("No"); ?></option>
					<option value="1"><?= __("Yes"); ?></option>
				</select>
			</div>
			<div class="mb-3">
						<label for="oqrs"><?= __("OQRS Email alert"); ?></label>
						<select class="form-select" id="oqrsemail" name="oqrsemail">
						<option value="0"><?= __("No"); ?></option>
						<option value="1"><?= __("Yes"); ?></option>
						</select>
						<small id="oqrsemailHelp" class="form-text text-muted"><?= __("Make sure email is set up under admin and global options."); ?></small>
					</div>
			<div class="mb-3">
				<label for="oqrstext"><?= __("OQRS Text"); ?></label>
				<input type="text" class="form-control" name="oqrstext" id="oqrstext" aria-describedby="oqrstextHelp">
				<small id="oqrstextHelp" class="form-text text-muted"><?= __("Some info you want to add regarding QSL'ing."); ?></small>
			</div>
<?php } ?>

			<button type="submit" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Create"); ?> <?= __("Station Location"); ?></button>

		</form>
  </div>
</div>

<br>

</div>
