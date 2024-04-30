<form method="post" class="d-flex align-items-center">
		<select id="editColumn" name="type" class="form-select form-select-sm w-auto me-2">
			<option value="band">Band</option>
			<option value="date">Date</option>
			<option value="comment">Comment</option>
			<option value="cqz">CQ Zone</option>
			<option value="dxcc">DXCC</option>
			<option value="gridsquare">Gridsquare</option>
			<option value="iota">IOTA</option>
			<option value="ituz">ITU Zone</option>
			<option value="mode">Mode</option>
			<option value="operator">Operator</option>
			<option value="pota">POTA</option>
			<option value="propagation">Propagation</option>
			<option value="qslvia">QSL via</option>
			<option value="satellite">Satellite</option>
			<option value="sota">SOTA</option>
			<option value="station">Station Location</option>
			<option value="wwff">WWFF</option>
			<option value="state">State</option>
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

		<label style="display:none" id="editDxccStateListLabel" class="mx-2 w-auto" for="statelabel">State</label>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editDxccStateList" name="dxccstatelist" required>

		</select>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editSatellite" name="satellite" required>

		</select>
		<label style="display:none" id="editSatelliteModeLabel" class="mx-2 w-auto" for="editSatelliteMode">SAT Mode</label>
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

		<label id="editBandRxLabel" class="mx-2 w-auto" for="gridlabel">Band RX</label>
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
	</form>
