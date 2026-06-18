<div class="container px-3 px-lg-4 mt-3 mb-3">
    <h2><?php echo $page_title; ?></h2>
    <h3><?= __("Filtering on"); ?> <?php echo $filter; ?></h3>

    <div class="card">
        <div class="card-header">
            <?= __("Gridsquare list"); ?>
        </div>
        <div class="card-body">
        <?php
        $i = 1;
        if ($vucc_array) {
            echo '<table style="width:100%" class="table table-sm tablevucc table-bordered table-hover table-striped table-condensed text-center">
            <thead>
            <tr>
                <th>#</th>
                <th>' . __("Gridsquare") . '</th>';

            if ($type != 'worked') {
                echo '<th>' . __("QSL") . '</th>
                    <th>' . __("LoTW") . '</th>';
            } else {
                echo '<th>' . __("Call") . '</th>';
            }
            echo '</tr>
            </thead>
            <tbody>';
            foreach ($vucc_array as $vucc => $value) {      // Fills the table with the data
                echo '<tr>
                    <td>'. $i++ .'</td>
                    <td><a href=\'javascript:displayContacts("'. $vucc .'","'. $band . '","All","All","All","VUCC")\'>'. $vucc .'</a></td>';

                if ($type != 'worked') {
                    echo '<td>'. $value['qsl'] . '</td>
                        <td>'. $value['lotw'] .'</td>';
                } else {
                    echo '<td>'. $value['call'] .'</td>';
                }

                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        else {
            echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        }
        ?>
        </div>
    </div>
</div>
