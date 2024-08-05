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
