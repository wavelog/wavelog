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
    foreach ($counties_array as $county) {
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
