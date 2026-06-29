<div class="container px-3 px-lg-4 mt-3 mb-3">
    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
            <?= __("Filtering on"); ?> <?php echo $filter ?>
        </div>
        <div class="card-body">
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
        foreach ($counties_array as $county) {
            $worked = (int) $county['worked'];
            $confirmed = (int) $county['confirmed'];
            $total_worked += $worked;
            $total_confirmed += $confirmed;
            echo '<tr>
            <td>'. $i++ .'</td>
            <td><a href=\'javascript:displayCountyContacts("'. $state .'","'. $county['COL_CNTY'] .'")\'>'. $county['COL_CNTY'] .'</a></td>
            <td>'. $worked .'</td>
            <td>'. $confirmed .'</td>';
            echo '</tr>';
        }
        echo '</tbody>
        <tfoot>
        <tr>
            <td colspan="2">' . __("Total") . '</td>
            <td>'. $total_worked .'</td>
            <td>'. $total_confirmed .'</td>
        </tr>
        </tfoot>
        </table>';
    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
        </div>
    </div>
</div>
