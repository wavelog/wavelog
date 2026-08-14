<form id="attachForm">
    <?php foreach ($qsoIds as $id): ?>
        <input type="hidden" name="qsoIds[]" value="<?php echo htmlspecialchars($id); ?>">
    <?php endforeach; ?>

    <div class="container-fluid">
        <table id="user_contests_table" class="table-sm table table-hover table-striped table-condensed">
            <thead>
                <tr>
                    <th></th>
                    <th scope="col"><?= __("Status") ?></th>
                    <th scope="col"><?= __("Start") ?></th>
                    <th scope="col"><?= __("End") ?></th>
                    <th scope="col"><?= __("Contest") ?></th>
                    <th scope="col"><?= __("Station") ?></th>
                    <th scope="col"><?= __("Comment") ?></th>
                    <th scope="col"><?= __("#QSO"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contests as $row) { ?>
                    <?php
                    $now = time();
                    $start = !empty($row['time_start']) ? strtotime($row['time_start']) : null;
                    $end   = !empty($row['time_end'])   ? strtotime($row['time_end'])   : null;

                    if ($start && $start > $now) {
                        $status = __("Coming Up");
                    } elseif ($start && $end && $now >= $start && $now <= $end) {
                        $status = __("In Progress");
                    } elseif ($end && $end < $now) {
                        $status = __("Completed");
                    } else {
                        $status = "-";
                    }
                    ?>
                    <tr onclick="this.querySelector('input').checked = true;">
                        <td><input type="radio" name="selected_contest" value="<?= $row['contest_session_id'] ?>"></td>
                        <td><?php echo $status; ?></td>
                        <td><?php echo !empty($row['time_start']) ? date($custom_date_format . ' H:i', strtotime($row['time_start'])) : '-'; ?></td>
                        <td><?php echo !empty($row['time_end']) ? date($custom_date_format . ' H:i', strtotime($row['time_end'])) : '-'; ?></td>
                        <td><?php echo isset($row['contestname']) ? $row['contestname'] : '-'; ?></td>
                        <td><?php echo isset($row['station']) ? $row['station'] : '-'; ?></td>
                        <td><?php echo isset($row['comment']) ? $row['comment'] : '-'; ?></td>
                        <td><?php echo isset($row['qso_count']) ? $row['qso_count'] : '0'; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

    </div>
</form>
