<?php
	$custom_date_format = $this->session->userdata('user_date_format');
	if (!$custom_date_format) {
		$custom_date_format = $this->config->item('qso_date_format');
	}
?>
<h5><?= sprintf(__("Last %s Activations of %s"), $count, $gridsquare); ?></h5>

<p><?= sprintf(__("Grid is contained in %s%% of all logs on %s"), $grid_percentage, '<a href="https://gridmaster.fr" target="_blank">gridmaster.fr</a>'); ?></p>
<div class="table-responsive">
<table style="width:100%" id="test" class="table test table-striped table-hover">
	<thead>
		<tr class="titles">
			<th scope="col"><?= __("Call"); ?></th>
			<th scope="col"><?= __("Date"); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
		foreach ($activation_data as $activation) {
			echo "<tr>";
			echo "<td class=\"callsign\">".$activation->call;
			$lotw_hint = "";
			if ($activation->lotw > 0) {
				if ($activation->lotw > 365) {
					$lotw_hint = "lotw_info_red";
				} else if ($activation->lotw > 30) {
					$lotw_hint = "lotw_info_orange";
				} else if ($activation->lotw > 7) {
					$lotw_hint = "lotw_info_orange";
				}
				echo " <a href=\"https://lotw.arrl.org/lotwuser/act?act=".$activation->call."\" target=\"_blank\"><small id=\"lotw_info\" class=\"badge bg-success ".$lotw_hint." data-bs-toggle=\"tooltip\" title=\"\">L</small></a>";
			}
			echo "</td>";
			echo "<td>".date($custom_date_format, strtotime($activation->date))."</td>";
			echo "</tr>";
		}
	?>
	</tbody>
</table>
</div>
