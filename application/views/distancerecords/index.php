<div class="container">
	<br>
		<h2><?php echo $page_title; ?></h2>
	<p><?php echo __("This page lists distance records per satellite based on gridsquares."); ?>
	<?php
		if ($distances) {
	?>

	<table style="width: 100%" id="distrectable" class="distrectable table table-sm table-striped table-hover">
	<thead>

	<tr>
		<th style="text-align: center"><?= __("Number") ?></th>
		<th style="text-align: center"><?= __("Satellite") ?></th>
		<th style="text-align: center"><?= __("Distance") ?></th>
		<th style="text-align: center"><?= __("Date") ?></th>
		<th style="text-align: center"><?= __("Time") ?></th>
		<th style="text-align: center"><?= __("Callsign") ?></th>
		<th style="text-align: center"><?= __("Mode") ?></th>
		<th style="text-align: center"><?= __("Gridsquare") ?></th>
	</tr>
	</thead>

	<tbody>
	<?php
		if ($distances) {
			$i = 1;
			foreach ($distances as $row) {
	?>

	<tr>
		<td style="text-align: center"><?php echo $i; ?></td>
		<td style="text-align: center"><?php echo $row->sat; ?></td>
		<td style="text-align: right"><?php printf("%.01f", floatval($row->distance)); ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->time ?? ''); echo date($custom_date_format, $timestamp); ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->time ?? ''); echo date('H:i', $timestamp); ?></td>
		<td style="text-align: center"><a href="javascript:displayQso(<?php echo $row->primarykey; ?>)"><?php echo $row->callsign; ?></a></td>
		<td style="text-align: center"><?php echo $row->mode; ?></td>
		<td style="text-align: center"><?php echo $row->grid; ?></td>
	</tr>
	<?php
			$i++;
		  }
		}
	?>

	</tbody>
	</table>
	<?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }?>
</div>
