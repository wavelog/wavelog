	<form method="post" class="d-flex align-items-center">
		<select id="quicklookuptype" name="type" class="form-select w-auto me-2">
			<option value="cq">CQ Zone</option>
			<option value="dxcc">DXCC</option>
			<option value="vucc">Gridsquare</option>
			<option value="iota">IOTA</option>
			<option value="sota">SOTA</option>
			<option value="was">US State</option>
			<option value="wwff">WWFF</option>
			<option value="lotw">LoTW user</option>
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

		<!-- DXCC -->
		<select style="display:none" class="form-select w-auto" id="quicklookupdxcc" name="dxcc" required>

			<?php
			foreach ($dxcc as $d) {
				echo '<option value=' . $d->adif . '>' . $d->prefix . ' - ' . ucwords(strtolower($d->name), "- (/");
				if ($d->Enddate != null) {
					echo ' (' . lang('gen_hamradio_deleted_dxcc') . ')';
				}
				echo '</option>';
			}
			?>

		</select>

		<select style="display:none" class="form-select w-auto" id="quicklookupwas" name="was">
			<?php
			$CI = &get_instance();
			$CI->load->library('subdivisions');

			$state_list = $CI->subdivisions->get_state_list('291');                                 // USA hardcoded
			?>
			<option value="">Choose a State</option>
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
		<div>&nbsp;</div><button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right ms-5" onclick="getLookupResult(this.form)">Show<div class="ld ld-ring ld-spin"></div></button>
	</form>
	<br />
	<div class="table-responsive" id="lookupresulttable">
	</div>
