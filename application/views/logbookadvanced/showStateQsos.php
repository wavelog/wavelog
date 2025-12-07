    <h4>QSOs Missing State Information</h4>
    <?php if (!empty($qsos) && count($qsos) > 0): ?>
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Date/Time</th>
                        <th>Mode</th>
                        <th>Submode</th>
                        <th>Band</th>
                        <th>State</th>
                        <th>Gridsquare</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qsos as $qso): ?>
                        <tr>
                            <td><?php echo $qso->col_call; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($qso->col_time_on)); ?></td>
                            <td><?php echo $qso->col_mode; ?></td>
                            <td><?php echo $qso->col_submode ?? ''; ?></td>
                            <td><?php echo $qso->col_band; ?></td>
                            <td><?php echo $qso->col_state; ?></td>
                            <td><?php echo $qso->col_gridsquare; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <div>
            <p class="text-muted">
                Found <?php echo count($qsos); ?> QSO(s) missing state information for DXCC <?php echo $dxcc; ?>.
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <h4>No Issues Found</h4>
        </div>
    <?php endif; ?>
