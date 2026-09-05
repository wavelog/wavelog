<?php
$i = 1;
if ($counties_array) {
    echo '<table style="width:100%" class="countiestable table table-sm table-bordered table-hover table-striped table-condensed text-center">
    <thead>
    <tr>
        <td>#</td>
        <td>' . __("State") . '</td>
        <td>' . __("County") . '</td>
    </tr>
    </thead>
    <tbody>';
    $prev_group = null;
    foreach ($counties_array as $county) {
        $group = $county['group'] ?? null;
        if ($group !== null && $group !== $prev_group) {
            // 3 separate cells, not colspan - DataTables (initialized on this table
            // in footer.php) indexes cells by column and misreads a colspan row.
            // Borders removed inline so the row reads as one merged bar.
            $cell = ' style="border:none"';
            echo '<tr class="table-secondary"><th'. $cell .'></th><th'. $cell .'></th><th'. $cell .' class="text-uppercase">'. html_escape($group) .'</th></tr>';
        }
        $prev_group = $group;

        $flag = isset($county['not_in_list']) ? ' <i data-bs-toggle="tooltip" title="' . __("Not in USA-CA county list") . '" class="fas fa-exclamation-triangle text-warning"></i>' : '';
        echo '<tr>
        <td>'. $i++ .'</td>
        <td>'. html_escape($county['COL_STATE']) .'</td>
        <td><a href=\'javascript:displayCountyContacts(' . js_escape($county['COL_STATE']) . ',' . js_escape($county['COL_CNTY']) . ')\'>'. html_escape($county['COL_CNTY']) .'</a>'. $flag .'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
