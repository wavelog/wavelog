<div class="container gridsquare_map_form">

	<br>

	<h2><?= __("Activated Gridsquare Map"); ?></h2>

<form class="d-flex align-items-center">
            <label class="my-1 me-2" for="band"><?= __("Band"); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="band">
                <option value="All"><?= __("All")?></option>
                <?php foreach($bands as $band) {
                    echo '<option value="'.$band.'"';
                    if ($user_default_band == $band) {
                        echo ' selected="selected"';
                    }
                    echo '>'.$band.'</option>'."\n";
                } ?>
            </select>
            <?php if (count($sats_available) != 0) { ?>
                <label class="my-1 me-2" for="distplot_sats" id="satslabel" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Satellite"); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?= __("All")?></option>
                    <?php foreach($sats_available as $sat) {
                        echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
                    } ?>
                </select>
            <?php } else { ?>
                <input id="sats" type="hidden" value="All"></input>
            <?php } ?>
                <label class="my-1 me-2" id="orbitslabel" for="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Orbit"); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?= __("All")?></option>
                    <?php
                    foreach($orbits as $orbit){
                         echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>'."\n";
                    }
                    ?>
            </select>
			<label class="my-1 me-2" for="mode"><?= __("Mode"); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="mode">
			<option value="All"><?= __("All")?></option>
                    <?php
                    foreach($modes as $mode){
                        if ($mode->submode ?? '' == '') {
                            echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>'."\n";
                        }
                    }
                    ?>
            </select>
			<label class="my-1 me-2"><?= __("Confirmation"); ?></label>
                <div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="qsl" id="qsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="lotw" id="lotw"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="eqsl" id="eqsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="qrz" id="qrz"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
                    </div>
                </div>

            <button id="plot" type="button" name="plot" class="btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="gridPlot(this.form,<?php echo $visitor == true ? "true" : "false"; ?>)"><?= __("Plot"); ?><div class="ld ld-ring ld-spin"></div></button>
			<button id="clear" type="button" name="clear" class="btn btn-primary me-1 ld-ext-right ld-ext-right-clear" onclick="clearMarkers()"><?= __("Clear Markers"); ?><div class="ld ld-ring ld-spin"></div></button>
</form>

		<?php if($this->session->flashdata('message')) { ?>
			<!-- Display Message -->
			<div class="alert-message error">
			  <p><?php echo $this->session->flashdata('message'); ?></p>
			</div>
		<?php } ?>
</div>

<div id="gridmapcontainer">
	<div id="gridsquare_map" class="map-leaflet" style="width: 100%;"></div>
</div>
<div class="coordinates d-flex">
        <div class="cohidden"><?= __("Latitude")?>:&nbsp;</div>
        <div class="cohidden col-auto text-success fw-bold" id="latDeg"></div>
        <div class="cohidden"><?= __("Longitude")?>:&nbsp;</div>
        <div class="cohidden col-auto text-success fw-bold" id="lngDeg"></div>
        <div class="cohidden"><?= __("Gridsquare")?>:&nbsp;</div>
        <div class="cohidden col-auto text-success fw-bold" id="locator"></div>
        <div class="cohidden"><?= __("Distance")?>:&nbsp;</div>
        <div class="cohidden col-auto text-success fw-bold" id="distance"></div>
        <div class="cohidden"><?= __("Bearing")?>:&nbsp;</div>
        <div class="cohidden col-auto text-success fw-bold" id="bearing"></div>
</div>
<script>
var gridsquaremap = true;
var type = "activated";
<?php if ($visitor == true) { ?>
var visitor = true;
<?php } else { ?>
var visitor = false;
<?php } ?>
<?php
    echo 'var jslayer ="' . $layer .'";';
    echo "var jsattribution ='" . $attribution . "';";
    echo "var homegrid ='" . strtoupper($homegrid[0]) . "';";

    echo 'var gridsquares_gridsquares = "' . $gridsquares_gridsquares . '";';
    echo 'var gridsquares_gridsquares_confirmed = "' . $gridsquares_gridsquares_confirmed . '";';
    echo 'var gridsquares_gridsquares_not_confirmed = "' . $gridsquares_gridsquares_not_confirmed . '";';
    echo 'var gridsquares_gridsquares_total_worked = "' . $gridsquares_gridsquares_total_activated . '";';
	echo "var gridsquares_fields = \"" . $gridsquares_fields . "\";\n";
    echo "var gridsquares_fields_confirmed = \"" . $gridsquares_fields_confirmed . "\";\n";
    echo "var gridsquares_fields_not_confirmed = \"" . $gridsquares_fields_not_confirmed . "\";\n";
    echo "var gridsquares_fields_total_worked = \"" . $gridsquares_fields_total_worked . "\";\n";
?>
</script>
