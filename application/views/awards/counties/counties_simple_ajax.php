<?php
if ($counties_array) {
    echo '<table style="width:100%" class="countiestable table table-sm table-bordered table-hover table-striped table-condensed text-center">
    <thead>
    <tr>
        <td>#</td>
        <td>' . __("County") . '</td>
    </tr>
    </thead>
    <tbody>';
    $i = 1;
    foreach ($counties_array as $county) {
        echo '<tr>
        <td>'. $i++ .'</td>
        <td>'. html_escape($county) .'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
