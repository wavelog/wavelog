<div class="container">
<?php

echo $calls_tested . " calls tested.<br/>";

if ($result) {
	array_to_table($result);
} ?>

</div>

<?php

function array_to_table($table) {
	// Sort table by Passed column (no first, then yes)
	usort($table, function($a, $b) {
		$aPassed = strtolower($a['Passed'] ?? '');
		$bPassed = strtolower($b['Passed'] ?? '');

		// no comes before yes
		if ($aPassed !== 'yes' && $bPassed === 'yes') return -1;
		if ($aPassed === 'yes' && $bPassed !== 'yes') return 1;
		return 0;
	});

	echo '<table style="width:100%" class="table-sm table table-hover table-bordered text-center">';

	// Table header
	foreach ($table[0] as $key=>$value) {
		echo "<th>".$key."</th>";
	}

	// Table body
	foreach ($table as $value) {
		$passed = strtolower($value['Passed'] ?? '');
		$rowClass = ($passed === 'yes') ? 'table-success' : 'table-danger';
		echo "<tr class='".$rowClass."'>";
		foreach ($value as $key=>$val) {
			echo "<td>".$val."</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
}
