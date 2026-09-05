<?php
if ($counties_array) {
    echo '<table style="width:100%" class="counties_states_table table table-sm table-bordered table-hover table-striped table-condensed text-center">
    <thead>
    <tr>
        <td>#</td>
        <td>' . __("County") . '</td>
        <td>' . __("QSOs Worked") . '</td>
        <td>' . __("QSOs Confirmed") . '</td>
    </tr>
    </thead>
    <tbody>';
    $i = 1;
    $total_worked = 0;
    $total_confirmed = 0;
    $prev_group = null;
    foreach ($counties_array as $county) {
        $group = $county['group'] ?? null;
        if ($group !== null && $group !== $prev_group) {
            // 4 separate cells, not colspan - DataTables (initialized on this table
            // in footer.php) indexes cells by column and misreads a colspan row.
            // Borders removed inline so the row reads as one merged bar.
            $cell = ' style="border:none"';
            echo '<tr class="table-secondary"><th'. $cell .'></th><th'. $cell .' class="text-uppercase">'. html_escape($group) .'</th><th'. $cell .'></th><th'. $cell .'></th></tr>';
        }
        $prev_group = $group;

        $worked = (int) $county['worked'];
        $confirmed = (int) $county['confirmed'];
        $total_worked += $worked;
        $total_confirmed += $confirmed;
        // Worked counties link to their QSOs; unworked ones have nothing to show
        $county_cell = $worked > 0
            ? '<a href=\'javascript:displayCountyContacts(' . js_escape($state) . ',' . js_escape($county['COL_CNTY']) . ')\'>'. html_escape($county['COL_CNTY']) .'</a>'
            : html_escape($county['COL_CNTY']);
        if (isset($county['not_in_list'])) {
            $county_cell .= ' <i data-bs-toggle="tooltip" title="' . __("Not in USA-CA county list") . '" class="fas fa-exclamation-triangle text-warning"></i>';
        }
        echo '<tr>
        <td>'. $i++ .'</td>
        <td>'. $county_cell .'</td>
        <td>'. ($worked > 0 ? $worked : '–') .'</td>
        <td>'. ($confirmed > 0 ? $confirmed : '–') .'</td>';
        echo '</tr>';
    }
    echo '</tbody>
    <tfoot>
    <tr>
        <th>#</th>
        <th>' . __("Total") . '</th>
        <th>'. $total_worked .'</th>
        <th>'. $total_confirmed .'</th>
    </tr>
    </tfoot>
    </table>';
}
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
