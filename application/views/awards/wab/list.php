<div class="container">
		<table style="width:100%" class="wabtable table-sm table table-hover table-striped table-condensed text-center">
			<thead>
				<tr>
					<th class="select-filter" scope="col"><?= __("WAB Square"); ?></th>
					<th class="select-filter" scope="col"><?= __("Confirmed"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($wab_array as $wab => $key) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;" ><?php echo '<a href=\'javascript:displayContacts("'. $wab .'","'. $postdata['band'] . '","' . $postdata['sat'] . '","' . $postdata['orbit'] . '","' . $postdata['mode'] . '","WAB")\'>'. $wab; ?></td>
					<td style="text-align: center; vertical-align: middle;" ><?php echo $key == 'C' ? 'Yes' : 'No'; ?></td>
				</tr>

				<?php } ?>
			</tbody>
		</table>
</div>
