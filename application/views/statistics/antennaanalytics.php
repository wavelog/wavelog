<style>
	#azimuthal_svg {
		max-width: 100%;
		height: auto;
		display: block;
	}
</style>

<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= __("Antenna Analytics"); ?></h2>
	<div class="card">
		<div class="card-header">
			<?= __("View azimuth and elevation data"); ?>
		</div>
		<div class="card-body">

			<div class="tabs">
				<ul class="nav nav-tabs" id="myTab" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true"><?= __("Azimuth"); ?></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="elevation-tab" data-bs-toggle="tab" href="#elevation" role="tab" aria-controls="elevation" aria-selected="false"><?= __("Elevation"); ?></a>
					</li>
				</ul>
			</div>
			<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab"><br />
					<form class="form" name="azForm" id="azForm">
						<div class="mb-3 d-flex align-items-center gap-3">
								<label class="w-auto control-label" for="band"><?= __("Band") ?></label>
								<div class="w-auto">
									<select id="band" name="band" class="form-select form-select-sm">
										<option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
										<?php foreach($bands as $band) {
											echo '<option value="' . $band . '"';
											if ($this->input->post('band') == $band) echo ' selected';
											echo '>' . $band . '</option>'."\n";
										} ?>
									</select>
								</div>

								<label class="w-auto control-label" for="mode"><?= __("Mode") ?></label>
								<div class="w-auto">
									<select id="mode" name="mode" class="form-select form-select-sm">
										<option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
										<?php
										foreach($modes as $mode){
												echo '<option value="' . $mode . '"';
												if ($this->input->post('mode') == $mode) echo ' selected';
												echo '>' . $mode . '</option>'."\n";
										}
										?>
									</select>
								</div>
								<div hidden class="sats_dropdown d-flex align-items-center gap-3">
									<label class="w-auto control-label" for="sat"><?= __("Sat") ?></label>
									<div class="w-auto">
										<select id="sat" name="sat" class="form-select form-select-sm">
											<option value="All" <?php if ($this->input->post('sat') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
											<?php
											foreach($sats as $sat){
													echo '<option value="' . $sat . '"';
													if ($this->input->post('sat') == $sat) echo ' selected';
													echo '>' . $sat . '</option>'."\n";
											}
											?>
										</select>
									</div>
								</div>
								<div hidden class="orbits_dropdown  d-flex align-items-center gap-3">
									<label class="w-auto control-label" for="orbit"><?= __("Orbit") ?></label>
									<div class="w-auto">
										<select id="orbit" name="orbit" class="form-select form-select-sm" multiple="multiple">
											<?php
											foreach($orbits as $orbit){
													echo '<option value="'.$orbit.'" selected>' . $orbit . '</option>'."\n";
											}
											?>
										</select>
									</div>
								</div>


							<div class="btn-group" id="azimuth_view_toggle" role="group" aria-label="<?= __("View"); ?>">
								<button type="button" class="btn btn-sm btn-outline-primary active" data-view="map"><?= __("Map") ?></button>
								<button type="button" class="btn btn-sm btn-outline-primary" data-view="radar"><?= __("Radar") ?></button>
							</div>

							<div class="w-auto">
								<button id="button1id" type="button" name="button1id" class="btn btn-sm btn-primary" onclick="plot_azimuth()"><?= __("Show") ?></button>
							</div>
						</div>

					</form>
					<div id="azimuthal_wrap" class="d-flex justify-content-center position-relative">
						<svg id="azimuthal_svg" role="img" aria-label="<?= __("Azimuthal map"); ?>"></svg>
						<div class="legend" style="position:absolute; top:10px; right:10px; z-index:500; pointer-events:none; font-size:12px; line-height:18px;">
							<h4 id="azimuthal_peak"><?= __("Azimuth"); ?></h4>
							<div id="azimuthal_center" style="text-align:center; margin: 0 0 6px;"></div>
							<i style="background:rgba(54,162,235,0.35); border:1px solid rgba(54,162,235,1);"></i><span><?= __("QSOs per degree of azimuth"); ?></span><br>
							<i style="background:none; height:0; border-top:2px dashed rgba(255,255,255,0.7); margin-top:8px;"></i><span><?= __("25 / 50 / 75 % of peak"); ?></span><br>
							<i class="rounded-circle" style="background:rgba(54,162,235,1); width:12px; height:12px; margin-top:3px;"></i><span><?= __("Marker every 5°"); ?></span><br>
							<i class="rounded-circle" style="background:#dc3545; width:12px; height:12px; margin-top:3px;"></i><span><?= __("Station location"); ?></span>
						</div>
					</div>
					<canvas id="azimuthchart" hidden></canvas>
				</div>

				<div class="tab-pane fade show" id="elevation" role="tabpanel" aria-labelledby="elevation-tab"><br />
					<form class="form" name="elForm" id="elForm">
					<div class="mb-3 d-flex align-items-center gap-3">
						<label class="w-auto control-label" for="sat"><?= __("Sat") ?></label>
						<div class="w-auto">
							<select id="satel" name="satel" class="form-select form-select-sm">
								<option value="All" <?php if ($this->input->post('sat') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
								<?php
								foreach($sats as $sat){
										echo '<option value="' . $sat . '"';
										if ($this->input->post('sat') == $sat) echo ' selected';
										echo '>' . $sat . '</option>'."\n";
								}
								?>
							</select>
						</div>
							<label class="w-auto control-label" for="orbit"><?= __("Orbit") ?></label>
							<div class="w-auto">
								<select id="orbitel" name="orbitel" class="form-select form-select-sm" multiple="multiple">
									<?php
									foreach($orbits as $orbit){
											echo '<option value="'.$orbit.'" selected>' . $orbit . '</option>'."\n";
									}
									?>
								</select>
							</div>

					<div class="w-auto">
						<button id="plot" type="button" name="plot" class="btn btn-sm btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plot_satel()"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
							</div>
							</div>
					</form>
					<div>

					<canvas id="elevationchart"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var ant_center = <?php echo json_encode(['lat' => $home_lat, 'lng' => $home_lng], JSON_HEX_TAG | JSON_HEX_APOS); ?>;
	var ant_homegrid = <?php echo json_encode($homegrid, JSON_HEX_TAG | JSON_HEX_APOS); ?>;
</script>
