<div class="container">
    <h1><?= __("Callsign statistics"); ?></h1>

    <form class="form" action="<?php echo site_url('callstats'); ?>" method="post" enctype="multipart/form-data">
        <!-- Select Basic -->
	<div class="mb-3 row">
			<label class="col-md-1 control-label w-auto" for="band"><?= __("Band"); ?></label>
			<div class="col-md-3 w-auto">
				<select id="band" name="band" class="form-select form-select-sm">
					<option value="All" <?php if ($bandselect == "All") echo ' selected'; ?>><?= __("All"); ?></option>
					<?php
					foreach ($worked_bands as $band) {
						echo '<option value="' . $band . '"';
						if ($bandselect == $band) echo ' selected';
						echo '>' . $band . '</option>' . "\n";
					}
					?>
				</select>
			</div>

			<label id="satlabel" class="col-md-1 control-label w-auto" for="sat" style="display: none;"><?= __("Satellite"); ?></label>
			<div id="satselect" class="col-sm-2 w-auto" style="display: none;">
				<select class="form-select form-select-sm w-auto" name="sat" id="sat">
					<option value="All"><?= __("All") ?></option>
					<?php
					foreach ($sats as $sat) {
						echo '<option value="' . $sat . '"';
						if ($satselect == $sat) echo ' selected';
						echo '>' . $sat . '</option>' . "\n";
					}
					?>
				</select>
			</div>

			<label id="orbitlabel" class="col-md-1 control-label w-auto" for="orbit" style="display: none;"><?= __("Orbit"); ?></label>
			<div id="orbitselect" class="col-md-3 w-auto" style="display: none;">
				<select id="orbit" name="orbit" class="form-select form-select-sm">
					<option value="All"><?= __("All") ?></option>
					<?php
					foreach ($orbits as $orbitval) {
						echo '<option value="' . $orbitval . '"';
						if ($orbit == $orbitval) echo ' selected';
						echo '>' . strtoupper($orbitval) . '</option>' . "\n";
					}
					?>
				</select>
			</div>

			<label class="col-md-1 control-label w-auto" for="mode"><?= __("Mode"); ?></label>
			<div class="col-sm-2 w-auto">
				<select class="form-select form-select-sm w-auto" name="mode" id="mode">
					<option value="All"><?= __("All") ?></option>
					<?php
					foreach ($worked_modes as $mode) {
						if ($mode->submode ?? '' == '') {
							echo '<option value="' . $mode . '"';
							if ($modeselect == $mode) echo ' selected';
							echo '>' . strtoupper($mode) . '</option>' . "\n";
						}
					}
					?>
				</select>
			</div>

			<label class="col-md-1 controll-label w-auto" for="propagation"><?= __("Propagation"); ?></label>
			<div class="col-sm-4 w-auto">
				<select class="form-select form-select-sm w-auto" name="propagation" id="propagation">
					<option value="" <?php if ($propagationselect == '') echo ' selected'; ?>><?= __("All"); ?></option>
					<option value="None" <?php if ($propagationselect == 'None') echo ' selected'; ?>><?= __("None/Empty"); ?></option>
					<option value="NoSAT" <?php if ($propagationselect == 'NoSAT') echo ' selected'; ?>><?= __("All except SAT") ?></option>
					<option value="AS" <?php if ($propagationselect == 'AS') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Aircraft Scatter"); ?></option>
					<option value="AUR" <?php if ($propagationselect == 'AUR') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Aurora"); ?></option>
					<option value="AUE" <?php if ($propagationselect == 'AUE') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Aurora-E"); ?></option>
					<option value="BS" <?php if ($propagationselect == 'BS') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Back scatter"); ?></option>
					<option value="ECH" <?php if ($propagationselect == 'ECH') echo ' selected'; ?>><?= _pgettext("Propagation Mode","EchoLink"); ?></option>
					<option value="EME" <?php if ($propagationselect == 'EME') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Earth-Moon-Earth"); ?></option>
					<option value="ES" <?php if ($propagationselect == 'ES') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Sporadic E"); ?></option>
					<option value="FAI" <?php if ($propagationselect == 'FAI') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Field Aligned Irregularities"); ?></option>
					<option value="F2" <?php if ($propagationselect == 'F2') echo ' selected'; ?>><?= _pgettext("Propagation Mode","F2 Reflection"); ?></option>
					<option value="INTERNET" <?php if ($propagationselect == 'INTERNET') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Internet-assisted"); ?></option>
					<option value="ION" <?php if ($propagationselect == 'ION') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Ionoscatter"); ?></option>
					<option value="IRL" <?php if ($propagationselect == 'IRL') echo ' selected'; ?>><?= _pgettext("Propagation Mode","IRLP"); ?></option>
					<option value="MS" <?php if ($propagationselect == 'MS') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Meteor scatter"); ?></option>
					<option value="RPT" <?php if ($propagationselect == 'RPT') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Terrestrial or atmospheric repeater or transponder"); ?></option>
					<option value="RS" <?php if ($propagationselect == 'RS') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Rain scatter"); ?></option>
					<option value="SAT" <?php if ($propagationselect == 'SAT') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Satellite"); ?></option>
					<option value="TEP" <?php if ($propagationselect == 'TEP') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Trans-equatorial"); ?></option>
					<option value="TR" <?php if ($propagationselect == 'TR') echo ' selected'; ?>><?= _pgettext("Propagation Mode","Tropospheric ducting"); ?></option>
				</select>
			</div>

            <label class="col-md-1 control-label w-auto" for="mincount"><?= __("Minimum Count"); ?></label>
            <div class="col-md-3 w-auto">
                <select id="mincount" name="mincount" class="form-select form-select-sm w-auto">
                    <?php
                    $i = 2;
                    do {
                        echo '<option value="' . $i . '"';
                        if ($mincount == $i) echo ' selected';
                        echo '>' . $i . '</option>' . "\n";
                        $i++;
                    } while ($i <= $maxactivatedgrids);
                    ?>
                </select>
            </div>

                <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary w-auto"><?= __("Show"); ?></button>
        </div>

    </form>

    <?php
    // Get Date format
    if ($this->session->userdata('user_date_format')) {
        // If Logged in and session exists
        $custom_date_format = $this->session->userdata('user_date_format');
    } else {
        // Get Default date format from /config/wavelog.php
        $custom_date_format = $this->config->item('qso_date_format');
    }
    ?>
    <?php
    $vucc_grids = array();

    if ($activators_array) {

        $result = write_activators($activators_array, $bandselect, $modeselect, $satselect, $orbit, $propagationselect);
    } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>

</div>


<?php

function write_activators($activators_array, $band, $mode, $sat, $orbit, $propagation)
{
    if ($band == '') {
        $band = 'All';
    }
    $i = 1;
    echo '<table style="width:100%" class="table table-sm callstatstable table-bordered table-hover table-striped table-condensed text-center">
              <thead>
                    <tr>
                        <td>#</td>
                        <td>' . __("Callsign") . '</td>
                        <td>' . __("#QSOs") . '</td>
                        <td>' . __("Show QSOs") . '</td>
                    </tr>
                </thead>
                <tbody>';

    $activators = array();
    foreach ($activators_array as $line) {
        $call = $line->call;
        $count = $line->count;
        array_push($activators, array($count, $call));
    }
    arsort($activators);
    foreach ($activators as $line) {
        echo '<tr>
                <td>' . $i++ . '</td>
                <td>' . $line[1] . '</td>
                <td>' . $line[0] . '</td>
                <td><a href=javascript:displayCallstatsContacts("' . $line[1] . '","' . $band . '","' . $mode . '","' . $sat . '","' . $orbit . '","' . $propagation .  '")><i class="fas fa-list"></i></a></td>
			</tr>';
    }
    echo '</tfoot></table></div>';
}
