<form method="post" class="d-flex align-items-center">
		<select id="editColumn" name="type" class="form-select form-select-sm w-auto me-2">
			<option value="band">Band</option>
			<option value="date">Date</option>
			<option value="comment">Comment</option>
			<option value="cqz">CQ Zone</option>
			<option value="dxcc">DXCC</option>
			<option value="gridsquare">Gridsquare</option>
			<option value="iota">IOTA</option>
			<option value="mode">Mode</option>
			<option value="operator">Operator</option>
			<option value="pota">POTA</option>
			<option value="propagation">Propagation</option>
			<option value="qslvia">QSL via</option>
			<option value="satellite">Satellite</option>
			<option value="sota">SOTA</option>
			<option value="station">Station Location</option>
			<option value="wwff">WWFF</option>
			<option value="was">US State</option>
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

		<select style="display:none" class="form-select form-select-sm w-auto" id="editDxcc" name="dxcc" required>

		</select>

		<select style="display:none" class="form-select form-select-sm w-auto" id="editSatellite" name="satellite" required>

		</select>

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

		<select style="display:none" class="form-select form-select-sm w-auto" id="editState" name="was">
			<option value="">-</option>
			<option value="AL">Alabama (AL)</option>
			<option value="AK">Alaska (AK)</option>
			<option value="AZ">Arizona (AZ)</option>
			<option value="AR">Arkansas (AR)</option>
			<option value="CA">California (CA)</option>
			<option value="CO">Colorado (CO)</option>
			<option value="CT">Connecticut (CT)</option>
			<option value="DE">Delaware (DE)</option>
			<option value="DC">District Of Columbia (DC)</option>
			<option value="FL">Florida (FL)</option>
			<option value="GA">Georgia (GA)</option>
			<option value="HI">Hawaii (HI)</option>
			<option value="ID">Idaho (ID)</option>
			<option value="IL">Illinois (IL)</option>
			<option value="IN">Indiana (IN)</option>
			<option value="IA">Iowa (IA)</option>
			<option value="KS">Kansas (KS)</option>
			<option value="KY">Kentucky (KY)</option>
			<option value="LA">Louisiana (LA)</option>
			<option value="ME">Maine (ME)</option>
			<option value="MD">Maryland (MD)</option>
			<option value="MA">Massachusetts (MA)</option>
			<option value="MI">Michigan (MI)</option>
			<option value="MN">Minnesota (MN)</option>
			<option value="MS">Mississippi (MS)</option>
			<option value="MO">Missouri (MO)</option>
			<option value="MT">Montana (MT)</option>
			<option value="NE">Nebraska (NE)</option>
			<option value="NV">Nevada (NV)</option>
			<option value="NH">New Hampshire (NH)</option>
			<option value="NJ">New Jersey (NJ)</option>
			<option value="NM">New Mexico (NM)</option>
			<option value="NY">New York (NY)</option>
			<option value="NC">North Carolina (NC)</option>
			<option value="ND">North Dakota (ND)</option>
			<option value="OH">Ohio (OH)</option>
			<option value="OK">Oklahoma (OK)</option>
			<option value="OR">Oregon (OR)</option>
			<option value="PA">Pennsylvania (PA)</option>
			<option value="RI">Rhode Island (RI)</option>
			<option value="SC">South Carolina (SC)</option>
			<option value="SD">South Dakota (SD)</option>
			<option value="TN">Tennessee (TN)</option>
			<option value="TX">Texas (TX)</option>
			<option value="UT">Utah (UT)</option>
			<option value="VT">Vermont (VT)</option>
			<option value="VA">Virginia (VA)</option>
			<option value="WA">Washington (WA)</option>
			<option value="WV">West Virginia (WV)</option>
			<option value="WI">Wisconsin (WI)</option>
			<option value="WY">Wyoming (WY)</option>
		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editIota" name="iota_ref">

		</select>

		<select style="display:none" class="form-select w-auto form-select-sm w-auto" id="editStationLocation" name="station_location">

		</select>
	</form>
