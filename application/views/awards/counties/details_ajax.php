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
        echo '<tr>
        <td>'. $i++ .'</td>
        <td>'. html_escape($county['COL_STATE']) .'</td>
        <td><a href=\'javascript:displayCountyContacts(' . json_encode($county['COL_STATE'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ',' . json_encode($county['COL_CNTY'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ')\'>'. html_escape($county['COL_CNTY']) .'</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
