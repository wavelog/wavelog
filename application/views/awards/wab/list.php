<div class="container">
		<table style="width:100%" class="wabtable table-sm table table-bordered table-hover table-striped table-condensed text-center">
			<thead>
				<tr>
					<th class="select-filter" scope="col"><?= __("WAB Square"); ?></th>
					<th class="select-filter" scope="col"><?= __("Confirmed"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($wab_array as $wab => $key) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;" ><?php echo '<a href=\'javascript:displayContacts(' . js_escape($wab) . ',' . js_escape($postdata['band']) . ',' . js_escape($postdata['sat']) . ',' . js_escape($postdata['orbit']) . ',' . js_escape($postdata['mode']) . ',"WAB")\'>'. html_escape($wab); ?></td>
					<td style="text-align: center; vertical-align: middle;" ><?php echo $key == 'C' ? 'Yes' : 'No'; ?></td>
				</tr>

				<?php } ?>
			</tbody>
		</table>
</div>
