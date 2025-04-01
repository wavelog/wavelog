<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/overrides.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">

    <title><?= "QSOs"; ?></title>
</head>

<body>
	   <table width="100%" class="table table-striped">
			<tr class="titles">
				<th class="<?= $text_size_class ?>"><?= __("Date"); ?></th>
				<th class="<?= $text_size_class ?>"><?= __("Call"); ?></th>
				<th class="<?= $text_size_class ?>"><?= __("Mode"); ?></th>
				<th class="<?= $text_size_class ?>"><?= __("Sent"); ?></th>
				<th class="<?= $text_size_class ?>"><?= __("Rcvd"); ?></th>
				<th class="<?= $text_size_class ?>"><?= __("Band"); ?></th>
			</tr>

			<?php

			$i = 0;
			foreach ($last_qsos_list->result() as $row) { ?>
				<?php  echo '<tr class="tr'.($i & 1).'">'; ?>
					<td class="<?= $text_size_class ?>">
						<?php $timestamp = strtotime($row->COL_TIME_ON); echo date($date_format, $timestamp); ?>
						<?php if ($show_time) { ?>
							<?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?>
						<?php } ?>
					</td>
					<td class="<?= $text_size_class ?>"><?php echo str_replace("0","&Oslash;",strtoupper($row->COL_CALL)); ?></td>
					<td class="<?= $text_size_class ?>"><?php echo $row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE; ?></td>
					<td class="<?= $text_size_class ?>"><?php echo $row->COL_RST_SENT; ?> <?php if ($row->COL_STX_STRING) { ?>(<?php echo $row->COL_STX_STRING;?>)<?php } ?></td>
					<td class="<?= $text_size_class ?>"><?php echo $row->COL_RST_RCVD; ?> <?php if ($row->COL_SRX_STRING) { ?>(<?php echo $row->COL_SRX_STRING;?>)<?php } ?></td>
					<?php if($row->COL_SAT_NAME != null) { ?>
					<td class="<?= $text_size_class ?>"><?php echo $row->COL_SAT_NAME; ?></td>
					<?php } else { ?>
					<td class="<?= $text_size_class ?>"><?php echo $row->COL_BAND; ?></td>
					<?php } ?>
				</tr>
			<?php $i++; } ?>
		</table>
</body>

</html>
