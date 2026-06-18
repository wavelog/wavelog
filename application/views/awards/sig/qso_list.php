<div class="container px-3 px-lg-4 mt-3 mb-3">
    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
            <?= __("QSO List"); ?>
        </div>
        <div class="card-body">
        <?php if ($sig_all) { ?>
            <table style="width:100%" class="table table-sm tablesig table-bordered table-hover table-striped table-condensed text-center">
                <thead>
                    <tr>
                        <th><?= __("Reference"); ?></th>
                        <th><?= __("Date/Time"); ?></th>
                        <th><?= __("Callsign"); ?></th>
                        <th><?= __("Mode"); ?></th>
                        <th><?= __("Band"); ?></th>
                        <th><?= __("RST Sent"); ?></th>
                        <th><?= __("RST Received"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sig_all->result() as $row) { ?>
                    <tr>
                        <td><?php echo $row->COL_SIG_INFO; ?></td>
                        <td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('d/m/y', $timestamp) . ' - ' . date('H:i', $timestamp); ?></td>
                        <td><?php echo $row->COL_CALL; ?></td>
                        <td><?php echo $row->COL_MODE; ?></td>
                        <td><?php echo $row->COL_BAND; ?></td>
                        <td><?php echo $row->COL_RST_SENT; ?></td>
                        <td><?php echo $row->COL_RST_RCVD; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php }
        else {
            echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        }
        ?>
            <p><a href="<?php echo site_url('/awards/sigexportadif/' . $type); ?>" title="<?= __("Export QSOs to ADIF"); ?>" target="_blank" class="btn btn-primary"><?= __("Export QSOs to ADIF"); ?></a></p>
        </div>
    </div>
</div>
