<?php
if ($counties_array) {
    $is_unmatched = ($type ?? null) === 'unmatched';
    echo '<table style="width:100%" class="countiestable table table-sm table-bordered table-hover table-striped table-condensed text-center">
    <thead>
    <tr>
        <td>#</td>
        <td>' . __("County") . '</td>' .
        ($is_unmatched ? '<td>' . __("Credit to adjoining county") . '</td>' : '') . '
    </tr>
    </thead>
    <tbody>';
    $i = 1;
    foreach ($counties_array as $county) {
        if ($is_unmatched) {
            // ['name' => bare UNSCORED name, 'options' => real adjoining
            // counties it may be credited to (rule C.5), 'assigned' =>
            // currently-saved credit, if any] - see Counties::get_counties_unmatched().
            $name_link = "<a href='javascript:displayCountyContacts(" . js_escape($state ?? '') . "," . js_escape($county['name']) . ")'>" . html_escape($county['name']) . "</a>";
            echo '<tr><td>' . $i++ . '</td><td>' . $name_link . '</td><td>';
            if (empty($county['options'])) {
                echo '<span class="text-muted small">' . __("No adjoining county in Wavelog's data") . '</span>';
            } else {
                echo '<div class="d-flex gap-1 justify-content-center">';
                echo '<select class="form-select form-select-sm county-credit-select" data-county="' . html_escape($county['name']) . '" data-assigned="' . html_escape($county['assigned'] ?? '') . '">';
                echo '<option value="">' . __("Not assigned") . '</option>';
                foreach ($county['options'] as $option) {
                    $selected = ($county['assigned'] === $option['value']) ? ' selected' : '';
                    echo '<option value="' . html_escape($option['value']) . '"' . $selected . '>' . html_escape($option['label']) . '</option>';
                }
                echo '</select>';
                echo '<button type="button" class="btn btn-sm btn-primary county-credit-save" disabled>' . __("Save") . '</button>';
                echo '</div>';
            }
            echo '</td></tr>';
        } else if (is_array($county)) {
            // 'target'/'needed' entries are ['name' => scoring group, 'members' => raw
            // counties] - Alaska's judicial districts list their boroughs underneath;
            // everywhere else 'members' has just the group's own name, so no nesting.
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
