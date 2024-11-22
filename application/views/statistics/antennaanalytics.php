<script>
let azdata =
<?php
echo json_encode($azelarray);
?>;
</script>
<div class="container">
	<br />
	<h2><?= __("Antenna Analytics"); ?></h2>
	<br>
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
		<form class="form">
        <!-- Select Basic -->
                <div class="mb-3 row">
                    <label class="w-auto control-label" for="band"><?= __("Band") ?></label>
                    <div class="w-auto">
                        <select id="band" name="band" class="form-select">
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
                        <select id="mode" name="mode" class="form-select">
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
					<label class="w-auto control-label" for="mode"><?= __("Sat") ?></label>
                    <div class="w-auto">
                        <select id="sat" name="sat" class="form-select">
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
					<label class="w-auto control-label" for="mode"><?= __("Orbit") ?></label>
                    <div class="w-auto">
                        <select id="orbit" name="orbit" class="form-select">
                            <option value="All" <?php if ($this->input->post('orbit') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
                            <?php
                            foreach($orbits as $orbit){
                                    echo '<option value="' . $orbit . '"';
                                    if ($this->input->post('orbit') == $orbit) echo ' selected';
                                    echo '>' . $orbit . '</option>'."\n";
                            }
                            ?>
                        </select>
                    </div>

                <div class="w-auto">
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary" onclick="plot_azimuth()"><?= __("Show") ?></button>
                </div>
            </div>

    	</form>
		<canvas id="azimuthchart"></canvas>
		</div>

        <div class="tab-pane fade show" id="elevation" role="tabpanel" aria-labelledby="elevation-tab"><br />
			<form>
			<div class="mb-3 row">
					<label class="w-auto control-label" id="satslabel" for="distplot_sats"><?= __("Satellite"); ?></label>
					<div class="w-auto">
					<select class="form-select"  id="sats">
						<?php foreach($satellites as $sat) {
							echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
						} ?>
					</select>
					</div>

					<div class="w-auto">
				<button id="plot" type="button" name="plot" class="btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plot_sat()"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
					</div>
					</div>
			</form>
			<div>
			<?php
			if ($azelarray) {
				echo '<table style="width:100%" class="qsotable table-sm table table-bordered table-hover table-striped table-condensed text-center">
					<thead>';
							echo '<tr>';
							echo '<th>'.__("Elevation").'</th>';
							echo '<th>'.__("# QSOs").'</th>';
							echo '</tr>
					</thead>
					<tbody>';
			foreach ($azelarray as $qso) {
				echo '<tr>';
				echo '<th>' . $qso->elevation . '</th>';
				echo '<th>' . $qso->qsos . '</th>';
				echo '</tr>';
			}
			echo '</tfoot></table>';
			}

			?>
			<canvas id="elevationchart"></canvas>
		</div>
</div>
</div>
