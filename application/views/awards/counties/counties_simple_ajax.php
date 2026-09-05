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
        // 'target'/'needed' entries are ['name' => scoring group, 'members' => raw
        // counties] - Alaska's judicial districts list their boroughs underneath;
        // everywhere else 'members' has just the group's own name, so no nesting.
        if (is_array($county)) {
            $has_members = count($county['members']) > 1;
            $name_class = $has_members ? ' class="fw-bold text-uppercase table-secondary"' : '';
            echo '<tr><td>'. $i++ .'</td><td'. $name_class .'>'. html_escape($county['name']) .'</td></tr>';
            if ($has_members) {
                foreach ($county['members'] as $member) {
                    echo '<tr><td></td><td class="text-muted small ps-4">'. html_escape($member) .'</td></tr>';
                }
            }
        } else {
            echo '<tr><td>'. $i++ .'</td><td>'. html_escape($county) .'</td></tr>';
        }
    }
    echo '</tbody></table>';
}
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
