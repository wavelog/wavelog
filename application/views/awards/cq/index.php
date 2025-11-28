<script>
    let tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
    let lang_general_hamradio_cqzone = "<?= __("CQ Zone"); ?>";
    let lang_hover_over_a_zone = "<?= __("Hover over a zone"); ?>";
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<style>
    #cqmap {
	height: calc(100vh - 480px) !important;
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
		let lang_awards_info_button = "<?= __("Award Info"); ?>";
		let lang_award_info_ln1 = "<?= __("CQ WAZ (Worked All Zones) Award"); ?>";
		let lang_award_info_ln2 = "<?= __("The CQ Magazine was located in the US and one of the most popular amateur radio magazines in the world. They stopped service by the end of 2023. The magazine first appeared in January 1945 and focuses on awards and the practical aspects of amateur radio."); ?>";
		let lang_award_info_ln3 = "<?= __("The WAZ Award stands for 'Worked All Zones' and requires radio contacts to all 40 CQ Zones along with the corresponding confirmation. Since the CQ Magazine does no longer exists the CQ WAZ Awards is now managed directly by N4BAA."); ?>";
		let lang_award_info_ln4 = "<?= sprintf(__("You can find all the information and rules on the Website of N4BAA: %s"), "<a href='https://n4baa.com/cqwaz.html' target='_blank'>N4BAA.com</a>"); ?>";
		let lang_award_info_ln5 = "<?= __("Fields taken for this Award: CQ-Zone (ADIF: CQZ)"); ?>";
    </script>
    <h2><?= __("Awards - CQ WAZ"); ?></h2>
    <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
  </div>
  <!-- End of Award Info Box -->
            <form class="form" action="<?php echo site_url('awards/cq'); ?>" method="post" enctype="multipart/form-data">
		<div class="mb-4 text-center">
				<div class="dropdown" data-bs-auto-close="outside">
					<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
					<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
				<?php if ($cq_array) {
					?><button type="button" onclick="load_cq_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i> <?= __("Show CQ Zone Map"); ?></button>
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
							<input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto border border-secondary" <?php if ($this->input->post('dateFrom', TRUE)) echo 'value="' . $this->input->post('dateFrom', TRUE) . '"'; ?>>
						</div>
					</div>
				</div>
				<div class="mb-3 row">
					<div class="col-md-2 control-label" for="checkboxes"><?= __("Date to"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto border border-secondary" <?php if ($this->input->post('dateTo', TRUE)) echo 'value="' . $this->input->post('dateTo', TRUE) . '"'; ?>>
						</div>
					</div>
				</div>

				<!-- Multiple Checkboxes (inline) -->
				<div class="mb-3 row">
					<div class="col-md-2" for="checkboxes"><?= __("Worked") . ' / ' . __("Confirmed")?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="worked" id="worked" value="1" <?php if ($this->input->post('worked', TRUE) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="worked"><?= __("Show worked"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="confirmed" id="confirmed" value="1" <?php if ($this->input->post('confirmed', TRUE) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="confirmed"><?= __("Show confirmed"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="notworked" id="notworked" value="1" <?php if ($this->input->post('notworked', TRUE) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="notworked"><?= __("Show not worked"); ?></label>
						</div>
					</div>
				</div>

				<div class="mb-3 row">
					<div class="col-md-2"><?= __("Show QSO with QSL Type"); ?></div>
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl', TRUE) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="qsl"><?= __("QSL Card"); ?></label>
						</div>
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw', TRUE) || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
							<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
						</div>
					   <div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl', TRUE)) echo ' checked="checked"'; ?> >
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
					<div class="col-md-3">
						<select id="band2" name="band" class="form-select form-select-sm">
							<option value="All" <?php if ($this->input->post('band', TRUE) == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All"); ?></option>
							<?php foreach($worked_bands as $band) {
								echo '<option value="' . $band . '"';
								if ($this->input->post('band', TRUE) == $band) echo ' selected';
								echo '>' . $band . '</option>'."\n";
							} ?>
						</select>
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
									if ($this->input->post('mode', TRUE) == $mode->mode) echo ' selected';
									echo '>'. $mode->mode . '</option>'."\n";
								} else {
									echo '<option value="' . $mode->submode . '"';
									if ($this->input->post('mode', TRUE) == $mode->submode) echo ' selected';
									echo '>' . $mode->submode . '</option>'."\n";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
    </form>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="true"><?= __("Table"); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" onclick="load_cq_map();" id="map-tab" data-bs-toggle="tab" href="#cqmaptab" role="tab" aria-controls="home" aria-selected="false"><?= __("Map"); ?></a>
        </li>
    </ul>
    <br />

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade" id="cqmaptab" role="tabpanel" aria-labelledby="home-tab">
    <br />

    <div id="cqmap" class="map-leaflet" ></div>

    </div>

        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab" style="margin-bottom: 30px;">

<?php
    $i = 1;
    if ($cq_array) {
    echo "
    <table style='width:100%' class='table tablecq table-sm table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr>
            <td>#</td>
            <td>" . __("CQ Zone") . "</td>";
        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
            }
            echo '</tr>
        </thead>
        <tbody>';
        foreach ($cq_array as $cq => $value) {      // Fills the table with the data
        echo '<tr>
            <td>' . $i++ . '</td>
            <td>'. $cq .'</td>';
            foreach ($value  as $key) {
            echo '<td style="text-align: center">' . $key . '</td>';
            }
            echo '</tr>';
        }
        echo "</table>
        <h2>" . __("Summary") . "</h2>

        <table class='table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr><td></td>";

        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo "<td>" . __("Total") . "</td></tr>
        </thead>
        <tbody>

        <tr><td>" . __("Total worked") . "</td>";

        foreach ($cq_summary['worked'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo "</tr><tr>
        <td>" . __("Total confirmed") . "</td>";
        foreach ($cq_summary['confirmed'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>

            </div>
        </div>
</div>
