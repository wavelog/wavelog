<?php if (isset($result) && is_array($result) && count($result) > 0): ?>
<div class="col-md-12 result">
    <h5><?= __("State Check Results"); ?></h5>
    <p><?= __("QSOs with missing state and gridsquares with 6 or more characters found for the following DXCCs:"); ?></p>

	<button type="button" class="btn btn-sm btn-primary ld-ext-right mb-2" id="fixAllStatesBtn" onclick="fixAllStates()">
		<?= __("Fix all DXCCs") ?><div class="ld ld-ring ld-spin"></div>
	</button>

	<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<table class="table table-sm table-striped table-bordered table-condensed mb-0">
			<thead>
				<tr>
					<th><?= __("Prefix"); ?></th>
					<th><?= __("DXCC"); ?></th>
					<th><?= __("QSOs"); ?></th>
					<th><?= __("Action"); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($result as $index => $item): ?>
				<?php
					$rawName = isset($item->dxcc_name) ? $item->dxcc_name : '';
					$formattedName = ucwords(strtolower($rawName), "- (/");
					$name = htmlspecialchars($formattedName, ENT_QUOTES, 'UTF-8');
					$qsos = isset($item->count) ? intval($item->count) : 0;
				?>
				<tr>
					<td><?php echo html_escape($item->prefix); ?></td>
					<td><?php echo $name; ?></td>
					<td><?php echo $qsos; ?></td>
					<td>
						<button type="button" class="btn btn-sm btn-primary ld-ext-right" id="fixStateBtn_<?php echo (int) $item->col_dxcc; ?>" onclick='fixState(<?php echo (int) $item->col_dxcc; ?>, <?php echo js_escape($formattedName); ?>)'>
							<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
						</button>
						<button id="openStateListBtn_<?php echo (int) $item->col_dxcc; ?>" onclick='openStateList(<?php echo (int) $item->col_dxcc; ?>, <?php echo js_escape($formattedName); ?>)' class="btn btn-sm btn-success"><i class="fas fa-search"></i></button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php else: ?>
<div class="col-md-12 result">
    <h5><?= __("State Check Results"); ?></h5>
    <p><?= __("No QSOs were found where state information can be fixed."); ?></p>
</div>
<?php endif; ?>
