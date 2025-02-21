<script>
	var dxcluster_provider = "<?php echo base_url(); ?>index.php/dxcluster";
	var cat_timeout_interval = "<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>";
	var dxcluster_maxage = <?php echo $this->optionslib->get_option('dxcluster_maxage') ?? 60; ?>;
	var custom_date_format = "<?php echo $custom_date_format ?>";
	var popup_warning = "<?= __("Pop-up was blocked! Please allow pop-ups for this site permanently."); ?>";
	var lang_click_to_prepare_logging = "<?= __("Click to prepare logging."); ?>";
</script>

<style>
	.spotted_call {
		cursor: alias;
	}

	.kHz::after {
		content: " kHz";
	}

	.bandlist {
		-webkit-transition: all 15s ease;
		-moz-transition: all 15s ease;
		-o-transition: all 15s ease;
		transition: 15s;
	}

	.fresh {
		/* -webkit-transition: all 15s ease;
    -moz-transition: all 15s ease;
    -o-transition: all 15s ease; */
		transition: all 500ms ease;
		--bs-table-bg: #3981b2;
		--bs-table-accent-bg: #3981b2;
	}

	tbody a {
		color: inherit;
		text-decoration: none;
	}
	.dataTables_wrapper {
		margin: 10px;
	}
</style>


<div class="container">
	<br>
	<center><button type="button" class="btn" id="menutoggle"><i class="fa fa-arrow-up" id="menutoggle_i"></i></button></center>

	<div id="errormessage" style="display: none;"></div>

	<h2 id="dxtitle"><?php echo $page_title; ?></h2>

	<div id="dxtabs" class="tabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link" href="index"><?= __("BandMap"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link active" href="list"><?= __("BandList"); ?></a>
			</li>
		</ul>
	</div>

	<div class="tab-content" id="myTabContent">
		<div class="messages my-1 me-2"></div>
		<div class="d-flex align-items-center">
			<label class="my-1 me-2" for="radio"><?= __("Radio"); ?></label>
			<select class="form-select form-select-sm radios my-1 me-sm-2 w-auto" id="radio" name="radio">
				<option value="0" selected="selected"><?= __("None"); ?></option>
				<?php foreach ($radios->result() as $row) { ?>
					<option value="<?php echo $row->id; ?>" <?php if ($this->session->userdata('radio') == $row->id) {
																echo "selected=\"selected\"";
															} ?>><?php echo $row->radio; ?></option>
				<?php } ?>
			</select>
			<label class="my-1 me-2" for="cwnSelect"><?= __("DXCC-Status"); ?></label>
			<select class="form-select form-select-sm my-1 me-sm-2 w-auto" id="cwnSelect" name="dxcluster_cwn" aria-describedby="dxcluster_cwnHelp" required>
				<option value="All"><?= __("All"); ?></option>
				<option value="wkd"><?= __("Worked"); ?></option>
				<option value="cnf"><?= __("Confirmed"); ?></option>
				<option value="ucnf"><?= __("Not Confirmed"); ?></option>
			</select>
			<label class="my-1 me-2" for="decontSelect"><?= __("Spots de"); ?></label>
			<select class="form-select form-select-sm my-1 me-sm-2 w-auto" id="decontSelect" name="dxcluster_decont" aria-describedby="dxcluster_decontHelp" required>
				<option value="Any"><?= __("All"); ?></option>
				<option value="AF" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AF') {echo " selected";} ?>><?= __("Africa"); ?></option>
				<option value="AN" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AN') {echo " selected";} ?>><?= __("Antarctica"); ?></option>
				<option value="AS" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'AS') {echo " selected";} ?>><?= __("Asia"); ?></option>
				<option value="EU" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'EU') {echo " selected";} ?>><?= __("Europe"); ?></option>
				<option value="NA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'NA') {echo " selected";} ?>><?= __("North America"); ?></option>
				<option value="OC" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'OC') {echo " selected";} ?>><?= __("Oceania"); ?></option>
				<option value="SA" <?php if ($this->optionslib->get_option('dxcluster_decont') == 'SA') {echo " selected";} ?>><?= __("South America"); ?></option>
			</select>

			<label class="my-1 me-2" for="band"><?= __("Band"); ?></label>
			<select id="band" class="form-select form-select-sm my-1 me-sm-2 w-auto" name="band">
				<option value="All"><?= __("All"); ?></option>
				<?php foreach ($bands as $key => $bandgroup) {
					echo '<optgroup label="' . strtoupper($key) . '">';
					foreach ($bandgroup as $band) {
						echo '<option value="' . $band . '"';
						if ($band == "20m") echo ' selected';
						echo '>' . $band . '</option>' . "\n";
					}
					echo '</optgroup>';
				}
				?>
			</select>
		</div>
		</div>

</div>

		<p>

		<table style="width:100%;" class="table-sm table spottable table-bordered table-hover table-striped table-condensed">
			<thead>
				<tr class="log_title titles">
					<th style="width:200px;"><?= __("Date"); ?>/<?= __("Time"); ?></th>
					<th style="width:150px;"><?= __("Frequency"); ?></th>
					<th><?= __("Call"); ?></th>
					<th><?= __("DXCC"); ?></th>
					<th style="width:30px;"><?= __("WAC"); ?></th>
					<th style="width:150px;"><?= __("Spotter"); ?></th>
					<th><?= __("Message"); ?></th>
					<th><?= __("Last Worked"); ?></th>
				</tr>
			</thead>

			<tbody class="spots_table_contents">
			</tbody>
		</table>
	</div>


