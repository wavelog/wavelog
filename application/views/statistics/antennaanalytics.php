<script>
let azdata =
<?php
echo json_encode($azelarray);
?>;
</script>
<div class="container">
<br />
<div class="card">
	<div class="card-header">
		<?= __("Antenna Analytics"); ?>
	</div>
	<div class="tables-container mx-2">
	<form class="d-flex align-items-center">
			<label class="my-1 me-2" id="satslabel" for="distplot_sats"><?= __("Satellite"); ?></label>
			<select class="form-select my-1 me-sm-2 w-auto"  id="sats">
				<?php foreach($satellites as $sat) {
					echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
				} ?>
			</select>


		<button id="plot" type="button" name="plot" class="btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plot_sat()"><?= __("Plot"); ?><div class="ld ld-ring ld-spin"></div></button>
	</form>
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
