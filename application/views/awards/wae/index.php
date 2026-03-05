<script>
document.addEventListener("DOMContentLoaded", function() {
  	document.querySelectorAll('.dropdown').forEach(dd => {
		dd.addEventListener('hide.bs.dropdown', function (e) {
			if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
				e.preventDefault(); // stop Bootstrap from closing
			}
		});
	});
});
</script>
<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAE Award"); ?>";
            var lang_award_info_ln2 = "<?= __("The oldest and most renowned of all DARC certificates is awarded for contacts with amateur radio stations in European countries and on islands listed in the WAE country list on different bands."); ?>";
            var lang_award_info_ln3 = "<?= __("The WAE will be issued in the following modes: CW, SSB, Phone, RTTY, FT8, Digital and Mixed Modes. It is issued in five classes: WAE III, WAE II, WAE I, WAE TOP and the WAE Trophy."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("Official information and the rules can be found in this document: %s."), "<a href='https://www.darc.de/en/der-club/referate/committee-dx/diplome/wae-award/' target='_blank'>https://www.darc.de/en/der-club/referate/committee-dx/diplome/wae-award/</a>"); ?>";
            var lang_award_info_ln5 = "<?= __("Fields taken for this Award: Region, DXCC"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/wae'); ?>" method="post" enctype="multipart/form-data">
        <div class="mb-4 text-center">
				<div class="dropdown" data-bs-auto-close="outside">
					<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
					<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>

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

    <?php
	echo '<br /><br />';
    $i = 1;
    if ($wae_array) {
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
					<td>' . __("WAE Name") . '</td>
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
        foreach ($wae_array as $dxcc => $value) {      // Fills the table with the data
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

        foreach($bands as $band) {
			if (($posted_band != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			echo '<td>' . $band . '</td>';
		}
		if ($posted_band != 'SAT') {
			echo '<td><b>' . __("Total (ex SAT)") . '</b></td>';
		}
		echo '
        </tr>
        </thead>
        <tbody>

        <tr><td>' . __("Total worked") . '</td>';

		$addsat='';
        foreach ($wae_summary['worked'] as $band => $dxcc) {      // Fills the table with the data
			if (($posted_band != 'SAT') && ($band == 'SAT')) {
				continue;
			}

			if (($posted_band == 'SAT') && ($band == 'Total')) {
				continue;
			}

			echo '<td style="text-align: center">';
			if ($band == 'Total' && $posted_band != 'SAT') {
				echo '<b>'.$dxcc.'</b>';
			} else {
				echo $dxcc;
			}
		echo '</td>';
        }

        echo '</tr><tr>
        <td>' . __("Total confirmed") . '</td>';

		$addsat='';
        foreach ($wae_summary['confirmed'] as $band => $dxcc) {      // Fills the table with the data
            if (($posted_band != 'SAT') && ($band == 'SAT')) {
				continue;
			}

			if (($posted_band == 'SAT') && ($band == 'Total')) {
				continue;
			}

			echo '<td style="text-align: center">';
			if (($posted_band != 'SAT') && ($band == 'Total')) {
				echo '<b>'.$dxcc.'</b>';
			} else {
				echo $dxcc;
			}
			echo '</td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
