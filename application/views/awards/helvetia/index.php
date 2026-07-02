<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
	var lang_switzerland_canton = "<?= _pgettext("Switzerland Canton", "Canton"); ?>";
	var lang_hover_over_a_canton = "<?= __("Hover over a canton"); ?>";
</script>
<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>

<style>
	#helvetiamap {
		height: calc(100vh - 300px) !important;
		max-height: 900px !important;
	}

	.dropdown-filters-responsive {
		width: min(850px, 90vw);
		min-width: 600px;
	}
</style>
<div class="container px-3 px-lg-4 mt-3 mb-3">
	<!-- Award Info Box -->
	<div id="awardInfoButton">
		<script>
		var lang_awards_info_button = "<?= __("Award Info"); ?>";
		var lang_award_info_ln1 = "<?= __("HELVETIA 26 | SWITZERLAND AWARD"); ?>";
		var lang_award_info_ln2 = "<?= __("The USKA (Union of Swiss Shortwave Amateurs) sponsors two awards, the HELVETIA 26 (H26) Award and the SWITZERLAND Award, aimed at promoting activities on the bands by encouraging contacts across as many Swiss cantons as possible on multiple bands."); ?>";
		var lang_award_info_ln3 = "<?= __("These awards come in two versions: one for HF bands and the other for VHF (including SHF and UHF) bands. Valid connections for these awards date back to January 1, 1980"); ?>";
		var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://www.uska.ch/contest/uska-diplome/' target='_blank'>https://www.uska.ch/contest/uska-diplome/</a>"); ?>";
		var lang_award_info_ln5 = "<?= __("Fields taken for this Award: DXCC (Must be Switzerland) and STATE (Must contain a valid state abbreviation!)"); ?>";
		</script>
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>
	<!-- End of Award Info Box -->
	<div class="card">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="true"><i class="fas fa-table"></i> <?= __("Table"); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="map-tab" onclick="load_helvetia_map();" data-bs-toggle="tab" href="#helvetiamaptab" role="tab" aria-controls="home" aria-selected="false"><i class="fas fa-map"></i> <?= __("Map"); ?></a>
				</li>
			</ul>
		</div>
		<div class="card-body">
			<form class="form" action="<?php echo site_url('awards/helvetia'); ?>" method="post" enctype="multipart/form-data">
				<div class="mb-4 text-center">
					<div class="dropdown" data-bs-auto-close="outside">
						<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
						<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
					<?php if ($helvetia_array) {
						?><button type="button" onclick="load_helvetia_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i> <?= __("Show Helvetia Map"); ?></button>
					<?php }?>

				<!-- Dropdown Menu with Filter Content -->
				<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown">
					<div class="card-body filterbody">
						<!-- Multiple Checkboxes (inline) -->
						<div class="mb-3 row">
							<div class="col-md-2" for="checkboxes"><?= __("Worked / Confirmed"); ?></div>
							<div class="col-md-10">
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="worked" id="worked" value="1" <?php if ($this->input->post('worked') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="worked"><?= __("Show worked"); ?></label>
								</div>
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="confirmed" id="confirmed" value="1" <?php if ($this->input->post('confirmed') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="confirmed"><?= __("Show confirmed"); ?></label>
								</div>
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="notworked" id="notworked" value="1" <?php if ($this->input->post('notworked') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="notworked"><?= __("Show not worked"); ?></label>
								</div>
							</div>
						</div>

						<div class="mb-3 row">
							<div class="col-md-2"><?= __("Show QSO with QSL Type"); ?></div>
							<div class="col-md-10">
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
								</div>
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
								</div>
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl')) echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
								</div>
								<div class="form-check-inline">
									<input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if ($this->input->post('qrz')) echo ' checked="checked"'; ?> >
									<label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
								</div>
							</div>
						</div>

						<div class="mb-3 row">
							<label class="col-md-2 control-label" for="band2"><?= __("Band"); ?></label>
							<div class="col-md-4">
								<select id="band2" name="band" class="form-select form-select-sm">
									<option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("Every band"); ?></option>
									<?php foreach($worked_bands as $band) {
										echo '<option value="' . $band . '"';
										if ($this->input->post('band') == $band) echo ' selected';
										echo '>' . $band . '</option>'."\n";
									} ?>
								</select>
							</div>
						</div>

						<div class="mb-3 row">
							<label class="col-md-2 control-label" for="mode"><?= __("Mode"); ?></label>
							<div class="col-md-4">
								<select id="mode" name="mode" class="form-select form-select-sm">
									<option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'mode') echo ' selected'; ?>><?= __("All"); ?></option>
									<?php
									foreach($modes->result() as $mode){
										if ($mode->submode == null) {
											echo '<option value="' . $mode->mode . '"';
											if ($this->input->post('mode') == $mode->mode) echo ' selected';
											echo '>'. $mode->mode . '</option>'."\n";
										} else {
											echo '<option value="' . $mode->submode . '"';
											if ($this->input->post('mode') == $mode->submode) echo ' selected';
											echo '>' . $mode->submode . '</option>'."\n";
										}
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				</div>
			</form>

			<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade" id="helvetiamaptab" role="tabpanel" aria-labelledby="home-tab">
					<div id="helvetiamap" class="map-leaflet"></div>
				</div>

				<div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">
				<?php
				if ($helvetia_array) {
					$i = 1;
					echo '
					<table style="width:100%" class="table table-sm tablehelvetia table-bordered table-hover table-striped table-condensed text-center">
						<thead>
						<tr>
							<td>#</td>
							<td>' . __("Canton") . '</td>';
						foreach($bands as $band) {
							echo '<td>' . $band . '</td>';
						}
						echo '</tr>
						</thead>
						<tbody>';

						foreach ($helvetia_array as $helvetia => $value) {      // Fills the table with the data
							echo '<tr>
								<td>' . $i++ . '</td>
								<td>'. $helvetia .'</td>';
							foreach ($value  as $key) {
								echo '<td style="text-align: center">' . $key . '</td>';
							}
							echo '</tr>';
						}
						echo '</table>

						<h2>' . __("Summary") . '</h2>

						<table class="table tablesummary table-sm table-bordered table-hover table-striped table-condensed text-center">
						<thead>
						<tr><td></td>';

						foreach($bands as $band) {
							echo '<td>' . $band . '</td>';
						}
						echo '<td>' . __("Total") . '</td></tr>
						</thead>
						<tbody>

						<tr><td>' . __("Total worked") . '</td>';

						foreach ($helvetia_summary['worked'] as $helvetia) {      // Fills the table with the data
							echo '<td style="text-align: center">' . $helvetia . '</td>';
						}

						echo '</tr><tr>
						<td>' . __("Total confirmed") . '</td>';
						foreach ($helvetia_summary['confirmed'] as $helvetia) {      // Fills the table with the data
							echo '<td style="text-align: center">' . $helvetia . '</td>';
						}

						echo '</tr>
						</tbody>
						</table>';
				}
				else {
					echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
				}
				?>
				</div>
			</div>
		</div>
	</div>
</div>
