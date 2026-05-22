<?php
// Add mode progress if available
if (isset($dxcc_mode_summary) && is_array($dxcc_mode_summary) && !empty($dxcc_mode_summary)) {
	echo '<table class="table table-sm table-hover table-striped">';
	echo '<thead><tr><th>' . __("Mode") . '</th><th>' . __("Worked") . '</th><th>' . __("Confirmed") . '</th><th>' . __("Worked Progress") . '</th><th>' . __("Confirmed Progress") . '</th></tr></thead>';
	echo '<tbody>';

	foreach ($dxcc_mode_summary as $mode_code => $mode_data) {
		// Get totals for all bands combined
		$total_worked = isset($mode_data['worked']['Total']) ? $mode_data['worked']['Total'] : 0;
		$total_confirmed = isset($mode_data['confirmed']['Total']) ? $mode_data['confirmed']['Total'] : 0;
		$mode_total = isset($mode_data['total']) ? $mode_data['total'] : 0;

		$worked_percentage = $mode_total > 0 ? ($total_worked / $mode_total) * 100 : 0;
		$confirmed_percentage = $mode_total > 0 ? ($total_confirmed / $mode_total) * 100 : 0;

		$worked_progress_class = $worked_percentage == 100 ? 'success' : ($worked_percentage >= 50 ? 'warning' : 'danger');
		$confirmed_progress_class = $confirmed_percentage == 100 ? 'success' : ($confirmed_percentage >= 50 ? 'warning' : 'danger');

		echo '<tr>';
		echo '<td><strong>' . htmlspecialchars($mode_data['name']) . '</strong></td>';
		echo '<td>' . $total_worked . '/' . $mode_total . ' (' . number_format($worked_percentage, 1) . '%)</td>';
		echo '<td>' . $total_confirmed . '/' . $mode_total . ' (' . number_format($confirmed_percentage, 1) . '%)</td>';
		echo '<td><div class="progress" style="height: 20px; position: relative;"><div class="progress-bar bg-' . $worked_progress_class . '" role="progressbar" style="width: ' . number_format($worked_percentage, 2) . '%;">' . number_format($worked_percentage, 1) . '%' . '</div></div></td>';
		echo '<td><div class="progress" style="height: 20px; position: relative;"><div class="progress-bar bg-' . $confirmed_progress_class . '" role="progressbar" style="width: ' . number_format($confirmed_percentage, 2) . '%;">' . number_format($confirmed_percentage, 1) . '%' . '</div></div></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
} else {
	echo '<div class="alert alert-info" role="alert">' . __("No mode data available.") . '</div>';
}
?>
