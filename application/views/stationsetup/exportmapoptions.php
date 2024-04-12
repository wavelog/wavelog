<input type="hidden" id="logbookid" name="id" value="<?php echo $logbookid; ?>">
<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="useroptions">
	<thead>
		<tr>
			<th class="text-start">Options</th>
			<th><?php echo lang('filter_options_show'); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>CQ Zone overlay</td>
			<td><div class="form-check"><input class="form-check-input" name="cqzone_layer" type="checkbox" <?php if (($exportmapoptions['cqzone_layer']->option_value ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Gridsquare overlay</td>
			<td><div class="form-check"><input class="form-check-input" name="gridsquare_layer" type="checkbox" <?php if (($exportmapoptions['gridsquare_layer']->option_value ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Night shadow overlay</td>
			<td><div class="form-check"><input class="form-check-input" name="nightshadow_layer" type="checkbox" <?php if (($exportmapoptions['nightshadow_layer']->option_value ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Path lines</td>
			<td><div class="form-check"><input class="form-check-input" name="path_lines" type="checkbox" <?php if (($exportmapoptions['path_lines']->option_value ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td># QSOs shown</td>
			<td><input class="form-control input-group-sm" type="number" id="qsocount" name="qsos" value="<?php echo ($exportmapoptions['qsocount']->option_value ?? 250); ?>"></td>
		</tr>
	</tbody>
</table>
