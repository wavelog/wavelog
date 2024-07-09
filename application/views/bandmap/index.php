<script>
	var dxcluster_provider="<?php echo base_url(); ?>index.php/dxcluster";
	var dxcluster_maxage=<?php echo $this->optionslib->get_option('dxcluster_maxage') ?? 60; ?>;
	var cat_timeout_interval="<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?>";
</script>


<div class="container">
<br>
<center><button type="button" class="btn" id="menutoggle"><i class="fa fa-arrow-up" id="menutoggle_i"></i></button></center>
<h2 id="dxtitle"><?php echo $page_title; ?></h2>
<div class="tabs" id="dxtabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="index"><?= __("BandMap"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="list"><?= __("BandList"); ?></a>
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
			<option value="<?php echo $row->id; ?>" <?php if($this->session->userdata('radio') == $row->id) { echo "selected=\"selected\""; } ?>><?php echo $row->radio; ?></option>
			<?php } ?>
		</select>

		<label class="my-1 me-2" for="decontSelect"><?= __("Spots de"); ?></label>
		<select class="form-select form-select-sm my-1 me-sm-2 w-auto" id="decontSelect" name="dxcluster_decont" aria-describedby="dxcluster_decontHelp" required>
				<option value="Any">*</option>
				<option value="AF"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AF') { echo " selected"; } ?>><?= __("Africa"); ?></option>
				<option value="AN"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AN') { echo " selected"; } ?>><?= __("Antarctica"); ?></option>
				<option value="AS"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AS') { echo " selected"; } ?>><?= __("Asia"); ?></option>
				<option value="EU"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'EU') { echo " selected"; } ?>><?= __("Europe"); ?></option>
				<option value="NA"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'NA') { echo " selected"; } ?>><?= __("North America"); ?></option>
				<option value="OC"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'OC') { echo " selected"; } ?>><?= __("Oceania"); ?></option>
				<option value="SA"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'SA') { echo " selected"; } ?>><?= __("South America"); ?></option>
                </select>

		<label class="my-1 me-2" for="band"><?= __("Band"); ?></label>
		<select id="band" class="form-select form-select-sm my-1 me-sm-2 w-auto" name="band">
			<?php foreach($bands as $key=>$bandgroup) {
					echo '<optgroup label="' . strtoupper($key) . '">';
					foreach($bandgroup as $band) {
					echo '<option value="' . $band . '"';
						if ($band == "20m") echo ' selected';
						echo '>' . $band . '</option>'."\n";
					}
					echo '</optgroup>';
				}
			?>
		</select>
	</div>

<figure class="highcharts-figure">
    <div id="bandmap"></div>
    <p class="highcharts-description">
    </p>
</figure>
</div>
</div>
