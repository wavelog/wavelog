
<div class="container gridsquare_map_form">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("Fred Fish Memorial Award"); ?>";
            var lang_award_info_ln2 = "<?= __("The Fred Fish Memorial Award was created in honor of Fred Fish, W5FF (SK), who was the first amateur to have worked and confirmed all 488 Maidenhead grid squares in the 48 contiguous United States on 6 Meters."); ?>";
            var lang_award_info_ln3 = "<?= __("The award will be given to any amateur who can duplicate W5FF's accomplishment."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, you can visit this link: %s."), "<a href='https://www.arrl.org/ffma' target='_blank'>https://www.arrl.org/ffma</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

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
        <div class="cohidden"><?= __("Latitude")?>: </div>
        <div class="cohidden col-auto text-success fw-bold" id="latDeg"></div>
        <div class="cohidden"><?= __("Longitude")?>: </div>
        <div class="cohidden col-auto text-success fw-bold" id="lngDeg"></div>
        <div class="cohidden"><?= __("Gridsquare")?>: </div>
        <div class="cohidden col-auto text-success fw-bold" id="locator"></div>
        <div class="cohidden"><?= __("Distance")?>: </div>
        <div class="cohidden col-auto text-success fw-bold" id="distance"></div>
        <div class="cohidden"><?= __("Bearing")?>: </div>
        <div class="cohidden col-auto text-success fw-bold" id="bearing"></div>
</div>
<script>var gridsquaremap = true;
var type = "worked";
<?php
    echo 'var jslayer ="' . $layer .'";';
    echo "var jsattribution ='" . $attribution . "';";
    echo "var homegrid ='" . strtoupper($homegrid[0]) . "';";

    echo 'var gridsquares_gridsquares = "' . $gridsquares_gridsquares . '";';
    echo 'var gridsquares_gridsquares_worked = "' . $gridsquares_gridsquares_worked . '";';
    echo 'var gridsquares_gridsquares_lotw = "' . $gridsquares_gridsquares_lotw . '";';
    echo 'var gridsquares_gridsquares_paper = "' . $gridsquares_gridsquares_paper . '";';
?>
</script>
