<form method="post" class="d-flex align-items-center">
		<select id="editColumn" name="type" class="form-select form-select-sm w-auto me-2">
			<option value="band"><?= __("Band"); ?></option>
			<option value="date"><?= __("Date"); ?></option>
			<option value="comment"><?= __("Comment"); ?></option>
			<option value="cqz"><?= __("CQ Zone"); ?></option>
			<option value="dxcc"><?= __("DXCC"); ?></option>
			<option value="gridsquare"><?= __("Gridsquare"); ?></option>
			<option value="iota"><?= __("IOTA"); ?></option>
			<option value="ituz"><?= __("ITU Zone"); ?></option>
			<option value="mode"><?= __("Mode"); ?></option>
			<option value="operator"><?= __("Operator"); ?></option>
			<option value="pota"><?= __("POTA"); ?></option>
			<option value="propagation"><?= __("Propagation"); ?></option>
			<option value="qslvia"><?= __("QSL via"); ?></option>
			<option value="satellite"><?= __("Satellite"); ?></option>
			<option value="sota"><?= __("SOTA"); ?></option>
			<option value="station"><?= __("Station Location"); ?></option>
			<option value="wwff"><?= __("WWFF"); ?></option>
			<option value="state"><?= __("State"); ?></option>
			<option value="contest"><?= __("Contest"); ?></option>
			<option value="lotwsent"><?= __("LoTW Sent"); ?></option>
			<option value="lotwreceived"><?= __("LoTW Received"); ?></option>
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

		<select id="editBand" class="form-select w-auto form-select-sm" name="editBand">
			<?php foreach($bands as $key=>$bandgroup) {
					echo '<optgroup label="' . strtoupper($key) . '">';
					foreach($bandgroup as $band) {
						echo '<option value="' . $band . '">' . $band . '</option>'."\n";
					}
					echo '</optgroup>';
				}
			?>
		</select>

		<label id="editBandRxLabel" class="mx-2 w-auto" for="gridlabel"><?= __("Band RX"); ?></label>
		<select id="editBandRx" class="form-select w-auto form-select-sm" name="editBandRx">
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

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editLoTW"  name="lotw">
			<option value="Y">Yes</option>
			<option value="N">No</option>
			<option value="R">Requested</option>
			<option value="I">Invalid</option>
			<option value="V">Verified</option>
		</select>
	</form>
