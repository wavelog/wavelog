<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<style>
    #dxccmap {
	height: calc(100vh - 300px) !important;
	max-height: 900px !important;
}

    .dropdown-filters-responsive {
        width: 800px;
    }

    @media (max-width: 900px) {
        .dropdown-filters-responsive {
            width: 90vw;
            max-width: none;
        }
    }
</style>
<div class="container">
	<!-- Award Info Box -->
	<br>
	<div id="awardInfoButton">
		<script>
		var lang_awards_info_button = "<?= __("Award Info"); ?>";
		var lang_award_info_ln1 = "<?= __("DXCC Award"); ?>";
		var lang_award_info_ln2 = "<?= sprintf(__("DXCC stands for 'DX Century Club,' an award based on worked countries. The DXCC List is based on an article created in 1935 by Clinton B. DeSoto, W1CBD, titled %s."), "<a href='http://www.arrl.org/desoto' target='_blank'>" . __("'How to Count Countries Worked, A New DX Scoring System'") . "</a>"); ?>";
		var lang_award_info_ln3 = "<?= sprintf(__("You can find all information about the DXCC Award on the %s."), "<a href='https://www.arrl.org/dxcc-rules' target='_blank'>" . __("ARRL website") . "</a>"); ?>";
		var lang_award_info_ln4 = "<?= __("Important Note: Over time, the criteria for the DXCC List have changed. The List remains unchanged until an entity no longer satisfies the criteria under which it was added, at which time it is moved to the Deleted List. You will find Deleted DXCC entities also in the lists on Wavelog. Be aware that these DXCC entities are outdated and no longer valid."); ?>";
		var lang_award_info_ln5 = "<?= __("Fields taken for this Award: DXCC (Needs to be a valid one out of the DXCC-ADIF-Spec-List"); ?>";
		</script>
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>
	<!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/dxcc'); ?>" method="post" enctype="multipart/form-data">
		<div class="mb-4 text-center">
				<div class="dropdown" data-bs-auto-close="outside">
					<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
					<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
				<?php if ($dxcc_array) {
					?><button type="button" onclick="load_dxcc_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i> <?= __("Show DXCC Map"); ?></button>
				<?php }?>

		<!-- Dropdown Menu with Filter Content -->
		<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown" style="max-width: 800px;">
			<div class="card-body filterbody">
				<div class="row mb-3">
					<label class="form-label" for="checkboxes"><?= __("Date Presets") . ": " ?></label>
						<div class="d-flex gap-1 d-flex flex-wrap">
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('today')"><?= __("Today") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('yesterday')"><?= __("Yesterday") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last7days')"><?= __("Last 7 Days") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last30days')"><?= __("Last 30 Days") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thismonth')"><?= __("This Month") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastmonth')"><?= __("Last Month") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thisyear')"><?= __("This Year") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastyear')"><?= __("Last Year") ?></button>
							<button type="button" class="btn btn-danger btn-sm flex-shrink-0" onclick="resetDates()"><i class="fas fa-times"></i> <?= __("Clear") ?></button>
						</div>
				</div>

				<div class="mb-3 row">
					<div class="col-md-2 control-label" for="checkboxes"><?= __("Date from"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto border border-secondary" <?php if ($this->input->post('dateFrom')) echo 'value="' . $this->input->post('dateFrom') . '"'; ?>>
						</div>
					</div>
				</div>
				<div class="mb-3 row">
					<div class="col-md-2 control-label" for="checkboxes"><?= __("Date to"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto border border-secondary" <?php if ($this->input->post('dateTo')) echo 'value="' . $this->input->post('dateTo') . '"'; ?>>
						</div>
					</div>
				</div>

				<div class="mb-3 row">
					<div class="col-md-2 control-label" for="checkboxes"><?= __("Deleted DXCC"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="includedeleted" id="includedeleted" value="1" <?php if ($this->input->post('includedeleted')) echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="includedeleted"><?= __("Include deleted"); ?></label>
						</div>
					</div>
				</div>

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
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if ($this->input->post('clublog')) echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
						</div>
					</div>
				</div>

				<div class="mb-3 row">
					<div class="col-md-2"><?= __("Continents"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="Antarctica" id="Antarctica" value="1" <?php if ($this->input->post('Antarctica') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="Antarctica"><?= __("Antarctica"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="Africa" id="Africa" value="1" <?php if ($this->input->post('Africa') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="Africa"><?= __("Africa"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="Asia" id="Asia" value="1" <?php if ($this->input->post('Asia') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="Asia"><?= __("Asia"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="Europe" id="Europe" value="1" <?php if ($this->input->post('Europe') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="Europe"><?= __("Europe"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="NorthAmerica" id="NorthAmerica" value="1" <?php if ($this->input->post('NorthAmerica') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="NorthAmerica"><?= __("North America"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="SouthAmerica" id="SouthAmerica" value="1" <?php if ($this->input->post('SouthAmerica') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="SouthAmerica"><?= __("South America"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input"  type="checkbox" name="Oceania" id="Oceania" value="1" <?php if ($this->input->post('Oceania') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="Oceania"><?= __("Oceania"); ?></label>
						</div>
					</div>
				</div>

				<div class="mb-3 row">
					<label class="col-md-2 control-label" for="band2"><?= __("Band"); ?></label>
					<div class="col-md-3">
						<select id="band2" name="band" class="form-select form-select-sm">
							<option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("Every band (w/o SAT)"); ?></option>
							<?php foreach($worked_bands as $band) {
								echo '<option value="' . $band . '"';
								if ($this->input->post('band') == $band) echo ' selected';
								echo '>' . $band . '</option>'."\n";
							} ?>
						</select>
					</div>
				</div>
				<div id="satrow" class="mb-3 row" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
				<?php if (count($sats_available) != 0) { ?>
					<label class="col-md-2 control-label" id="satslabel" for="sats"><?= __("Satellite"); ?></label>
					<div class="col-md-3">
					<select class="form-select form-select-sm"  id="sats" name="sats">
						<option value="All" <?php if ($this->input->post('sats') == "All" || $this->input->method() !== 'post') echo ' selected'; ?>><?= __("All")?></option>
						<?php foreach($sats_available as $sat) {
							echo '<option value="' . $sat . '"';
							if ($this->input->post('sats') == $sat) echo ' selected';
							echo '>' . $sat . '</option>'."\n";
						} ?>
					</select>
					</div>
				<?php } else { ?>
					<input id="sats" type="hidden" value="All"></input>
				<?php } ?>
				</div>
			<div id="orbitrow" class="mb-3 row" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
				<label class="col-md-2 control-label" id="orbitslabel" for="orbits"><?= __("Orbit"); ?></label>
					<div class="col-md-3">
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
			<div class="mb-3 row">
				<label class="col-md-2 control-label" for="mode"><?= __("Mode"); ?></label>
				<div class="col-md-3">
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
    </form>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="true"><?= __("Table"); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="map-tab" onclick="load_dxcc_map();" data-bs-toggle="tab" href="#dxccmaptab" role="tab" aria-controls="home" aria-selected="false"><?= __("Map"); ?></a>
        </li>
    </ul>
    <br />

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade" id="dxccmaptab" role="tabpanel" aria-labelledby="home-tab">

    <div id="dxccmap" class="map-leaflet" ></div>

    </div>

        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">

    <?php
    $i = 1;
    if ($dxcc_array) {
	    echo __('Legend:');
	    echo '<pre>'.__("(Q)SL-Paper-Card").", ";
	    echo __("(L)oTW").", ";
	    echo __("(e)QSL").", ";
	    echo __('QR(Z)-"confirmation"').", ";
	    echo __("(C)lublog").", ";
	    echo __("(W)orked").'</pre>';
	    echo '
		<table style="width:100%" class="table-sm table tabledxcc table-bordered table-hover table-striped table-condensed text-center">
		    <thead>
		    <tr>
			<td>#</td>
			<td>' . __("DXCC Name") . '</td>
			<td>' . __("Prefix") . '</td>';
	    foreach($bands as $band) {
		    if (($posted_band != 'SAT') && ($band == 'SAT')) {
			   continue;
		    }
		    echo '<td>' . $band . '</td>';
	    }
	echo '</tr>
		    </thead>
		    <tbody>';
	    foreach ($dxcc_array as $dxcc => $value) {      // Fills the table with the data
		    echo '<tr>
			    <td>'. $i++ .'</td>';
		    foreach ($value as $name => $key) {
			    if (isset($value['Deleted']) && $value['Deleted'] == 1 && $name == "name") {
				    echo '<td style="text-align: center">' . $key . ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span></td>';
			    } else if ($name == "Deleted") {
				    continue;
			    } else {
				    echo '<td style="text-align: center">' . $key . '</td>';
			    }
		    }
		    echo '</tr>';
	    }
	    echo '</table>
		    <h2>' . __("Summary") . '</h2>

		    <table class="table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center">
		    <thead>
		    <tr><td></td>';

				    $addsat='';
				    foreach($bands as $band) {
					    if ($band != 'SAT') {
						    echo '<td>' . $band . '</td>';
					    } else {
						    $addsat='<td>' . $band . '</td>';
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
	<tbody>

	<tr><td>' . __("Total worked") . '</td>';
	$addsat='';
	foreach ($dxcc_summary['worked'] as $band => $dxcc) {      // Fills the table with the data
		if ($band != 'SAT') {
			echo '<td style="text-align: center">';
			if ($band == 'Total') {
				echo '<b>'.$dxcc.'</b>';
			} else {
				echo $dxcc;
			}
			echo '</td>';
		} else {
			$addsat='<td style="text-align: center">' . $dxcc . '</td>';
		}
	}

	if (count($bands) > 1) {
		echo '<td class="spacingcell"></td>';
	}

	if ($addsat != '' && count($dxcc_summary['worked']) > 1) {
		echo $addsat;
	}

	echo '</tr><tr>
	<td>' . __("Total confirmed") . '</td>';
	$addsat='';
	foreach ($dxcc_summary['confirmed'] as $band => $dxcc) {      // Fills the table with the data
		if ($band != 'SAT') {
			echo '<td style="text-align: center">';
			if ($band == 'Total') {
				echo '<b>'.$dxcc.'</b>';
			} else {
				echo $dxcc;
			}
			echo '</td>';
		} else {
			$addsat='<td style="text-align: center">' . $dxcc . '</td>';
		}
	}
	if (count($bands) > 1) {
		echo '<td class="spacingcell"></td>';
	}

	if ($addsat != '' && count($dxcc_summary['confirmed']) > 1) {
		echo $addsat;
	}

	echo '</tr>
	</table>
	</div>';

    } else {
	    echo '<div class="alert alert-danger" role="alert">' . __("No results found for your search criteria. Please try again.") . '</div>';
    }
    ?>
                </div>
        </div>
</div>
