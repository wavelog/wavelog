<?= __("Please choose the column to be edited:"); ?><br/><br/>
<form method="post" class="d-flex align-items-center">
		<select id="editColumn" name="type" class="form-select form-select-sm w-auto me-2">
			<option value="">-</option>
			<optgroup label="<?= __("QSO details"); ?>">
				<option value="band"><?= __("Band"); ?></option>
				<option value="comment"><?= __("Comment"); ?></option>
				<option value="contest"><?= __("Contest"); ?></option>
				<option value="stxstring"><?= __("Contest Exch (S)"); ?></option>
				<option value="date"><?= __("Date"); ?></option>
				<option value="distance"><?= __("Distance"); ?></option>
				<option value="mode"><?= __("Mode"); ?></option>
				<option value="operator"><?= __("Operator"); ?></option>
				<option value="propagation"><?= __("Propagation"); ?></option>
				<option value="rstr"><?= __("RST (R)"); ?></option>
				<option value="rsts"><?= __("RST (S)"); ?></option>
				<option value="satellite"><?= __("Satellite"); ?></option>
				<option value="station"><?= __("Station Location"); ?></option>
				<option value="stationpower"><?= __("Station Power"); ?></option>
			</optgroup>

			<optgroup label="<?= __("Awards"); ?>">
				<option value="continent"><?= __("Continent"); ?></option>
				<option value="cqz"><?= __("CQ Zone"); ?></option>
				<option value="dok"><?= __("DOK"); ?></option>
				<option value="dxcc"><?= __("DXCC"); ?></option>
				<option value="gridsquare"><?= __("Gridsquare"); ?></option>
				<option value="iota"><?= __("IOTA"); ?></option>
				<option value="ituz"><?= __("ITU Zone"); ?></option>
				<option value="pota"><?= __("POTA"); ?></option>
				<option value="region"><?= __("Region"); ?></option>
				<option value="sota"><?= __("SOTA"); ?></option>
				<option value="state"><?= __("State"); ?></option>
				<option value="wwff"><?= __("WWFF"); ?></option>
			</optgroup>

			<optgroup label="<?= __("QSL / LoTW / Clublog / eQSL / QRZ / DCL"); ?>">
				<option value="clublogreceived"><?= __("Clublog Received"); ?></option>
				<option value="clublogsent"><?= __("Clublog Sent"); ?></option>
				<option value="dclsent"><?= __("DCL Sent"); ?></option>
				<option value="dclreceived"><?= __("DCL Received"); ?></option>
				<option value="eqslreceived"><?= __("eQSL Received"); ?></option>
				<option value="eqslsent"><?= __("eQSL Sent"); ?></option>
				<option value="lotwreceived"><?= __("LoTW Received"); ?></option>
				<option value="lotwsent"><?= __("LoTW Sent"); ?></option>
				<option value="qrzreceived"><?= __("QRZ Received"); ?></option>
				<option value="qrzsent"><?= __("QRZ Sent"); ?></option>
				<option value="qslreceived"><?= __("QSL Received"); ?></option>
				<option value="qslsent"><?= __("QSL Sent"); ?></option>
				<option value="qslmsg"><?= __("QSLMSG"); ?></option>
				<option value="qslreceivedmethod"><?= __("QSL Received Method"); ?></option>
				<option value="qslsentmethod"><?= __("QSL Sent Method"); ?></option>
				<option value="qslvia"><?= __("QSL via"); ?></option>
			</optgroup>

			</select>
		<div>&nbsp;</div>

		<input style="display:none" class="form-control form-control-sm w-auto" id="editTextInput" type="text" name="editTextInput" placeholder="" aria-label="editTextInput">

		<input style="display:none" name="editDate" id="editDate" type="date" class="form-control form-control-sm w-auto">

		<!-- CQ Zone -->
		<select style="display:none" class="form-select form-select-sm w-auto" id="editCqz" name="cqz" required>
			<?php
			for ($i = 1; $i <= 40; $i++) {
				echo '<option value="' . $i . '">' . $i . '</option>';
			}
			?>
		</select>

		<!-- ITU Zone -->
		<select style="display:none" class="form-select form-select-sm w-auto" id="editItuz" name="ituz" required>
			<?php
			for ($i = 1; $i <= 90; $i++) {
				echo '<option value="' . $i . '">' . $i . '</option>';
			}
			?>
		</select>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editDxcc" name="dxcc" required>

		</select>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editDxccState" name="dxccstate" required>
		<option value="">-</option>
		<?php
		foreach($stateDxcc as $dxcc){
						echo '<option value=' . $dxcc->adif;
						echo '>' . $dxcc->prefix . ' - ' . ucwords(strtolower($dxcc->name), "- (/");
						echo '</option>';
					}
		?>
		</select>

		<label style="display:none" id="editDxccStateListLabel" class="mx-2 w-auto" for="statelabel"><?= __("State"); ?></label>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editDxccStateList" name="dxccstatelist" required>

		</select>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editSatellite" name="satellite" required>

		</select>
		<label style="display:none" id="editSatelliteModeLabel" class="mx-2 w-auto" for="editSatelliteMode"><?= __("SAT Mode"); ?></label>
		<input style="display:none" class="form-control form-control-sm w-auto" id="editSatelliteMode" type="text" name="editSatelliteMode" placeholder="" aria-label="editSatelliteMode">

		<label style="display:none" id="editBandTxLabel" class="mx-2 w-auto" for="editBand"><?= __("Band TX"); ?></label>
		<select style="display:none" id="editBand" class="form-select w-auto form-select-sm" name="editBand">
			<option value="">-</option>
			<?php foreach($bands as $key=>$bandgroup) {
					echo '<optgroup label="' . strtoupper($key) . '">';
					foreach($bandgroup as $band) {
						echo '<option value="' . $band . '">' . $band . '</option>'."\n";
					}
					echo '</optgroup>';
				}
			?>
		</select>

		<label style="display:none" id="editBandRxLabel" class="mx-2 w-auto" for="gridlabel"><?= __("Band RX"); ?></label>
		<select style="display:none" id="editBandRx" class="form-select w-auto form-select-sm" name="editBandRx">
			<option value="">-</option>
			<?php foreach($bands as $key=>$bandgroup) {
					echo '<optgroup label="' . strtoupper($key) . '">';
					foreach($bandgroup as $band) {
						echo '<option value="' . $band . '">' . $band . '</option>'."\n";
					}
					echo '</optgroup>';
				}
			?>
		</select>

		<select style="display:none" id="editMode" class="form-select mode form-select-sm w-auto" name="editMode">
		<?php
			foreach($modes->result() as $mode){
			if ($mode->submode == null) {
				printf("<option value=\"%s\">%s</option>", $mode->mode, $mode->mode);
			} else {
				printf("<option value=\"%s\">&rArr; %s</option>", $mode->submode, $mode->submode);
			}
			}
		?>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm" id="editPropagation" name="propagation" required>

		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editIota" name="iota_ref">

		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editStationLocation" name="station_location">

		</select>
		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editContest"  name="contest">
			<?php
				foreach($contests as $contest){
					echo '<option value="' . $contest['adifname'] . '">' . $contest["name"] . '</option>'."\n";
				}
			?>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editQsl"  name="qsl">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="R"><?= __("Requested"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editLoTW"  name="lotw">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="R"><?= __("Requested"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
			<option value="V"><?= __("Verified"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editQrz"  name="qrz">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editDcl"  name="dcl">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editEqsl"  name="eqsl">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editClublog"  name="clublog">
			<option value="Y"><?= __("Yes"); ?></option>
			<option value="N"><?= __("No"); ?></option>
			<option value="R"><?= __("Requested"); ?></option>
			<option value="I"><?= __("Invalid"); ?></option>
			<option value="V"><?= __("Verified"); ?></option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editQslMethod"  name="qslmethod">
			<option value=""><?= __("None/Empty"); ?></option>
			<option value="D"><?= __("Direct"); ?></option>
			<option value="B"><?= __("Bureau"); ?></option>
			<option value="E"><?= __("Electronic"); ?></option>
			<option value="M"><?= __("Manager"); ?></option>
		</select>

		<select style="display:none" id="editContinent" name="continent" class="form-select w-auto form-select-sm w-auto">
			<option value=""><?= __("None/Empty"); ?></option>
			<option value="AF"><?= __("Africa"); ?></option>
			<option value="AN"><?= __("Antarctica"); ?></option>
			<option value="NA"><?= __("North America"); ?></option>
			<option value="AS"><?= __("Asia"); ?></option>
			<option value="EU"><?= __("Europe"); ?></option>
			<option value="SA"><?= __("South America"); ?></option>
			<option value="OC"><?= __("Oceania"); ?></option>
		</select>

		<select style="display:none" id="editRegion" name="region" class="form-select w-auto form-select-sm w-auto">
			<option value=""></option>
			<option value="NONE"><?= __("NONE"); ?></option>
			<option value="AI"><?= __("African Italy"); ?></option>
			<option value="BI"><?= __("Bear Island"); ?></option>
			<option value="ET"><?= __("European Turkey"); ?></option>
			<option value="IV"><?= __("ITU Vienna"); ?></option>
			<option value="KO"><?= __("Kosovo"); ?></option>
			<option value="SI"><?= __("Shetland Islands"); ?></option>
			<option value="SY"><?= __("Sicily"); ?></option>
		</select>

		<label style="display:none" id="editDistanceInputLabel" class="mx-2 w-auto" for="editDistanceInput"><?= __("Distance (in km). Leave blank to recalculate distance. (It will only work if a gridsquare is set)."); ?></label>
		<input style="display:none" class="form-control form-control-sm w-auto" id="editDistanceInput" type="text" name="editDistanceInput" placeholder="" aria-label="editDistanceInput">
		<input style="display:none" class="form-control form-control-sm w-auto uppercase" id="editDokInput" type="text" name="editDokInput" placeholder="" aria-label="editDokInput">
		<input style="display:none" class="form-control form-control-sm w-auto uppercase" id="editGridsquareInput" type="text" name="editGridsquareInput" placeholder="" aria-label="editGridsquareInput">
	</form>
