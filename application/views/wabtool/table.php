<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function wabtool_h($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div id="wabtoolResult">
<?php if (count($rows) === 0) { ?>
	<div class="alert alert-info mb-2"><?= __("No 100% matching QSOs found.") ?></div>
<?php } else { ?>
<div class="table-responsive">
	<table class="table table-sm table-striped" id="wabtoolTable">
		<thead>
			<tr>
				<th style="width: 2rem;"><input type="checkbox" id="wabtoolSelectAll"></th>
				<th><?= __("Date/Time") ?></th>
				<th><?= __("Callsign") ?></th>
				<th><?= __("Band") ?></th>
				<th><?= __("Grid") ?></th>
				<th><?= __("WAB Square") ?></th>
				<th><?= __("Station") ?></th>
				<th><?= __("Confirmed") ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($rows as $row) { ?>
			<tr>
				<td><input type="checkbox" class="wabtool-row" value="<?php echo $row['id']; ?>"></td>
				<td data-order="<?php echo $row['ts']; ?>"><?php echo wabtool_h($row['datetime']); ?></td>
				<td><a href="javascript:displayQso('<?php echo $row['id']; ?>')"><?php echo wabtool_h($row['callsign']); ?></a></td>
				<td><?php echo wabtool_h($row['band']); ?></td>
				<td><?php echo wabtool_h($row['grid']); ?></td>
				<td>
					<span class="badge bg-primary"><?php echo wabtool_h($row['square']); ?></span>
					<a href="#" class="wabtool-map text-muted" data-grid="<?php echo wabtool_h($row['grid']); ?>" data-bs-toggle="tooltip" title="Show on map"><i class="fas fa-map-marked-alt"></i></a>
				</td>
				<td><?php echo wabtool_h($row['station']); ?></td>
				<td>
					<?php if ($row['confirmed'] !== '') { ?>
						<span data-bs-toggle="tooltip" title="Q = QSL card, L = LoTW, E = eQSL, Z = QRZ.com, C = Clublog">
							<?php foreach (str_split($row['confirmed']) as $letter) { ?><span class="badge bg-success"><?php echo wabtool_h($letter); ?></span> <?php } ?>
						</span>
					<?php } else { ?>
						<span class="text-muted">&mdash;</span>
					<?php } ?>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php } ?>
</div>
