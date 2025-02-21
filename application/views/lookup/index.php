	<form method="post" onsubmit="getLookupResult(this.form); return false;" class="d-flex align-items-center">
		<select id="quicklookuptype" name="type" class="form-select w-auto me-2">
			<option value="cq"><?= __("CQ Zone"); ?></option>
			<option value="continent"><?= __("Continent"); ?></option>
			<option value="dxcc"><?= __("DXCC"); ?></option>
			<option value="vucc"><?= __("Gridsquare"); ?></option>
			<option value="iota"><?= __("IOTA"); ?></option>
			<option value="itu"><?= __("ITU Zone"); ?></option>
			<option value="sota"><?= __("SOTA"); ?></option>
			<option value="was"><?= __("US State"); ?></option>
			<option value="wwff"><?= __("WWFF"); ?></option>
			<option value="lotw"><?= __("LoTW user"); ?></option>
		</select>
		<div>&nbsp;</div>
		<input style="display:none" class="form-control input-group-sm w-auto" id="quicklookuptext" type="text" name="searchfield" placeholder="" aria-label="Search">

		<!-- CQ Zone -->
		<select class="form-select w-auto" id="quicklookupcqz" name="cqz" required>
			<?php
			for ($i = 1; $i <= 40; $i++) {
				echo '<option value="' . $i . '">' . $i . '</option>';
			}
			?>
		</select>

		<!-- ITU Zone -->
		<select style="display:none" class="form-select w-auto" id="quicklookupituz" name="ituz" required>
			<?php
			for ($i = 1; $i <= 90; $i++) {
				echo '<option value="' . $i . '">' . $i . '</option>';
			}
			?>
		</select>

		<!-- DXCC -->
		<select style="display:none" class="form-select w-auto" id="quicklookupdxcc" name="dxcc" required>

			<?php
			foreach ($dxcc as $d) {
				if ($d->adif == '0') {
					echo '<option value='.$d->adif.'>'.$d->name.'</option>';
				} else {
					echo '<option value=' . $d->adif . '>' . $d->prefix . ' - ' . ucwords(strtolower($d->name), "- (/");
					if ($d->Enddate != null) {
						echo ' (' . __("Deleted DXCC") . ')';
					}
					echo '</option>';
				}
			}
			?>

		</select>

		<!-- Continent -->

		<select style="display:none" class="form-select w-auto" id="quicklookupcontinent" name="continent" required>
					<option value="af"><?= __("Africa"); ?></option>
					<option value="an"><?= __("Antarctica"); ?></option>
					<option value="na"><?= __("North America"); ?></option>
					<option value="as"><?= __("Asia"); ?></option>
					<option value="eu"><?= __("Europe"); ?></option>
					<option value="sa"><?= __("South America"); ?></option>
					<option value="oc"><?= __("Oceania"); ?></option>
				</select>
			</div>
		</select>

		<select style="display:none" class="form-select w-auto" id="quicklookupwas" name="was">
			<?php
			$CI = &get_instance();
			$CI->load->library('subdivisions');

			$state_list = $CI->subdivisions->get_state_list('291');                                 // USA hardcoded
			?>
			<option value=""><?= __("Choose a State"); ?></option>
			<?php foreach ($state_list->result() as $state) {?>
				<option value="<?php echo $state->state; ?>">
					<?php echo $state->subdivision . ' (' . $state->state . ')'; ?>
				</option>
			<?php } ?>
		</select>

		<select style="display:none" class="form-select w-auto" id="quicklookupiota" name="iota_ref">

			<?php
			foreach ($iota as $i) {
				echo '<option value=' . $i->tag . '>' . $i->tag . ' - ' . $i->name . '</option>';
			}
			?>

		</select>
		<div>&nbsp;</div><button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right ms-5" onclick="getLookupResult(this.form)"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
	</form>
	<br />
	<div class="table-responsive" id="lookupresulttable">
	</div>
