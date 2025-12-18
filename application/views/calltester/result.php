<?php $execution_time ?>
	<?php $calls_tested ?>
	<table class="table table-striped table-bordered table-sm">
		<thead>
		<tr>

      <?php // Table header
        foreach ($result[0] as $key=>$value) {
            echo "<th>".$key."</th>";
        }

        // Table body
		echo '<tbody>';
        foreach ($result as $value) {
            echo "<tr>";
            foreach ($value as $val) {
                    echo "<td>".$val."</td>";
            }
            echo "</tr>";
        }
		echo '</tbody>';
		echo "</table>"; ?>