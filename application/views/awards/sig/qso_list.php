<div class="container">
    <h2><?php echo $page_title; ?></h2>

    <?php if ($sig_all) { ?>

	<table style="width:100%" class="table-sm table tablesig table-hover table-striped table-condensed text-center">
			<thead>
        <tr>
            <td><?= __("Reference"); ?></td>
            <td><?= __("Date/Time"); ?></td>
            <td><?= __("Callsign"); ?></td>
			<td><?= __("Mode"); ?></td>
            <td><?= __("Band"); ?></td>
            <td><?= __("RST Sent"); ?></td>
            <td><?= __("RST Received"); ?></td>
        </tr>
		</thead>
        <?php foreach ($sig_all->result() as $row) { ?>
            <tr>
                <td>
                    <?php echo $row->COL_SIG_INFO; ?>
                </td>
                <td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('d/m/y', $timestamp); ?> - <?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
                <td><?php echo $row->COL_CALL; ?></td>
				<td><?php echo $row->COL_MODE; ?></td>
                <td><?php echo $row->COL_BAND; ?></td>
                <td><?php echo $row->COL_RST_SENT; ?></td>
                <td><?php echo $row->COL_RST_RCVD; ?></td>
            </tr>
                <?php } ?>

    </table>
    <?php } ?>
    <p><a href="<?php echo site_url('/awards/sigexportadif/' . $type); ?>" title="<?= __("Export QSOs to ADIF"); ?>" target="_blank" class="btn btn-primary"><?= __("Export QSOs to ADIF"); ?></a></p>
</div>
