<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <!-- End of Award Info Box -->

	<form method="post" enctype="multipart/form-data">
		<div class="mb-4 text-center">
			<div class="dropdown" data-bs-auto-close="outside">

			<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
			<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>

	<!-- Dropdown Menu with Filter Content -->
	<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5" aria-labelledby="filterDropdown" style="min-width: 250px;">
		<div class="card-body filterbody">
					<div class="row">
						<div class="col-md-6">

							<!-- QSL Types Card -->
							<div class="card mb-3">
								<div class="card-header"><?= __("Show QSO with QSL Type") ?></div>
								<div class="card-body">
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
										<label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
										<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl')) echo ' checked="checked"'; ?> >
										<label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if ($this->input->post('qrz')) echo ' checked="checked"'; ?> >
										<label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if ($this->input->post('clublog')) echo ' checked="checked"'; ?> >
										<label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
									</div>
								</div>
							</div>

							<!-- Continents Card -->
							<div class="card mb-3">
								<div class="card-header"><?= __("Continents") ?></div>
								<div class="card-body">
									<?php
									$continents = [
										"Antarctica" => __("Antarctica"),
										"Africa" => __("Africa"),
										"Asia" => __("Asia"),
										"Europe" => __("Europe"),
										"NorthAmerica" => __("North America"),
										"SouthAmerica" => __("South America"),
										"Oceania" => __("Oceania"),
									];
									foreach ($continents as $key => $label) { ?>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $key ?>" value="1" <?php if ($this->input->post($key) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
											<label class="form-check-label" for="<?= $key ?>"><?= $label ?></label>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<!-- Band/Satellite/Orbit Card -->
							<div class="card mb-3">
								<div class="card-header"><?= __("Band / Satellite / Orbit") ?></div>
								<div class="card-body">
									<div class="mb-3">
										<label class="form-label" for="band2"><?= __("Band"); ?></label>
										<select id="band2" name="band" class="form-select form-select-sm">
											<option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("Every band (w/o SAT)"); ?></option>
											<?php foreach($worked_bands as $band) {
												echo '<option value="' . $band . '"';
												if ($this->input->post('band') == $band) echo ' selected';
												echo '>' . $band . '</option>'."\n";
											} ?>
										</select>
									</div>
									<div id="satrow" class="mb-3" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
										<?php if (count($sats_available) != 0) { ?>
											<label class="form-label" id="satslabel" for="sats"><?= __("Satellite"); ?></label>
											<select class="form-select form-select-sm"  id="sats" name="sats">
												<option value="All" <?php if ($this->input->post('sats') == "All" || $this->input->method() !== 'post') echo ' selected'; ?>><?= __("All")?></option>
												<?php foreach($sats_available as $sat) {
													echo '<option value="' . $sat . '"';
													if ($this->input->post('sats') == $sat) echo ' selected';
													echo '>' . $sat . '</option>'."\n";
												} ?>
											</select>
										<?php } else { ?>
											<input id="sats" type="hidden" value="All"></input>
										<?php } ?>
									</div>
									<div id="orbitrow" class="mb-3" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
										<label class="form-label" id="orbitslabel" for="orbits"><?= __("Orbit"); ?></label>
										<select class="form-select form-select-sm"  id="orbits" name="orbits">
											<option value="All" <?php if ($this->input->post('orbits') == "All" || $this->input->method() !== 'post') echo ' selected'; ?>><?= __("All")?></option>
											<?php
											foreach($orbits as $orbit){
												echo '<option value="' . $orbit . '"';
												if ($this->input->post('orbits') == $orbit) echo ' selected';
												echo '>' . strtoupper($orbit) . '</option>'."\n";
											}
											?>
										</select>
									</div>
								</div>
							</div>

							<!-- Mode Card -->
							<div class="card mb-3">
								<div class="card-header"><?= __("Mode") ?></div>
								<div class="card-body">
									<label class="form-label" for="mode"><?= __("Mode"); ?></label>
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
				<div class="mb-4 text-center">
					<button id="button2id" type="reset" name="button2id" class="btn btn-sm btn-warning"><?= __("Reset"); ?></button>
				</div>
			</div>
		</div>
		</div>
</form>
<!-- End Filtering Parent Card -->

<?php
if ($wpx_array) {
    echo '<h2>' . __("Summary") . '</h2>

        <table class="table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center">
        <thead>
        <tr><td></td>';

    $addsat = '';
    foreach ($bands as $band) {
        if ($band != 'SAT') {
            echo '<td>' . $band . '</td>';
        } else {
            $addsat = '<td>' . $band . '</td>';
        }
    }
    echo '<td><b>' . __("Total") . '</b></td>';
    if (count($bands) > 1) {
        echo '<td class="spacingcell"></td>';
    }
    echo $addsat;
    echo '
        </tr>
        </thead>
        <tbody>';

    // ---- Worked row ----
    echo '<tr><td>' . __("Total worked") . '</td>';
    $addsat = '';
    foreach ($wpx_array['worked'] as $band => $value) {
		if ($band != 'SAT') {
            echo '<td style="text-align: center">';
            echo '<a href=\'javascript:wpxLoadDetails("worked", "' . $band . '")\'>' . $value . '</a>';
            echo '</td>';
        } else {
            $addsat = '<td style="text-align: center"><a href=\'javascript:wpxLoadDetails("worked", "' . $band . '")\'>' . $value . '</a></td>';
        }
    }

    if (count($bands) > 1) {
        echo '<td class="spacingcell"></td>';
    }
    if ($addsat != '' && count($wpx_array['worked']) > 1) {
        echo $addsat;
    }
    echo '</tr>';

    // ---- Confirmed row ----
    echo '<tr><td>' . __("Total confirmed") . '</td>';
    $addsat = '';
    foreach ($wpx_array['confirmed'] as $band => $value) {
        if ($band != 'SAT') {
            echo '<td style="text-align: center">';
            echo '<a href=\'javascript:wpxLoadDetails("confirmed", "' . $band . '")\'>' . $value . '</a>';
            echo '</td>';
        } else {
            $addsat = '<td style="text-align: center"><a href=\'javascript:wpxLoadDetails("confirmed", "' . $band . '")\'>' . $value . '</a></td>';
        }
    }

    if (count($bands) > 1) {
        echo '<td class="spacingcell"></td>';
    }
    if ($addsat != '' && count($wpx_array['confirmed']) > 1) {
        echo $addsat;
    }
    echo '</tr>';

    echo '</table>';
    echo '<div class="showWpxResults mt-3"></div>';

} else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>


</div>
