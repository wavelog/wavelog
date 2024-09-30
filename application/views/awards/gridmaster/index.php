<div class="container gridsquare_map_form">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            <?php if (strtoupper($dxcc) == "US") { ?>
                var lang_award_info_ln1 = "<?= __("US Gridmaster Award"); ?>";
                var lang_award_info_ln2 = "<?= __("The GridMaster Award is the most prestigious AMSAT award, first introduced in 2014 by the Star Comm Group. It is available to all amateur radio operators worldwide who manage to work all 488 grid squares in the USA via satellite and can provide QSL confirmations for each contact."); ?>";
                var lang_award_info_ln3 = "<?= sprintf(__("Official information from the %s: Two-way communication must be established via amateur satellite with each grid. There is no minimum signal report required. Contacts must be made from the same location or from locations no two of which are more than 200 kilometers apart. The applicant's attestation in the award application serves as affirmation of abidance by the distance rule. Individuals may apply for and be granted multiple GridMaster awards when achieved from another location, which is in a different 200-kilometer circle."), "<a href='https://www.amsat.org/gridmaster/' target='_blank'>" . __("website") . "</a>"); ?>";
                var lang_award_info_ln4 = "<?= __("This map shows only QSOs worked on SAT."); ?>";
            <?php } else { ?>
                var lang_award_info_ln1 = "<?= __("Gridmaster Award"); ?>";
                var lang_award_info_ln2 = "<?= __("The Gridmaster Award was originally designed for the 488 gridsquares to be worked in the USA."); ?>";
                var lang_award_info_ln3 = "<?= __("On this map the grids for the particular DXCC are shown. This is no official award but just showing the grids which were worked according to the US Gridmaster Award rules for this DXCC."); ?>";
                var lang_award_info_ln4 = "<?= __("This map shows only QSOs worked on SAT."); ?>";
            <?php } ?>
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
var dxcc = '<?php echo $dxcc; ?>';
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
