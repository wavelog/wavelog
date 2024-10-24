<input type="hidden" id="logbookid" name="id" value="<?php echo $logbookid; ?>">
<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="useroptions">
	<thead>
		<tr>
			<th class="text-start"><?= __("Options"); ?></th>
			<th><?= __("Show"); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?= _pgettext("Map Options", "CQ Zone overlay"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="cqzone_layer" type="checkbox" <?php if (($exportmapoptions['cqzone_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "Gridsquare overlay"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="gridsquare_layer" type="checkbox" <?php if (($exportmapoptions['gridsquare_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "Night shadow overlay"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="nightshadow_layer" type="checkbox" <?php if (($exportmapoptions['nightshadow_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "Path lines"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="path_lines" type="checkbox" <?php if (($exportmapoptions['path_lines']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("# QSOs shown"); ?></td>
			<td><input class="form-control input-group-sm" type="number" id="qsocount" name="qsos" value="<?php echo ($exportmapoptions['qsocount']->option_value ?? 250); ?>"></td>
		</tr>
		<tr>
			<td><?= __("Band"); ?></td>
			<td><select id="band" class="form-select form-select-sm my-1 me-sm-2 w-auto" name="band">
				<option value=""><?= __("All"); ?></option>
				<?php foreach ($bands as $band) {
						echo '<option value="' . $band . '"';
						if ($band == ($exportmapoptions['band']->option_value ?? '')) echo ' selected';
						echo '>' . $band . '</option>' . "\n";
				}
				?>
			</select></td>
		</tr>
	</tbody>
</table>
<?php if($slug !== "") { ?>
		<div id="slugLink" class="alert alert-info" role="alert" style="margin-top: 20px;">
			<p><?= __("Visit export map"); ?>:</p>
			<p><a href="<?php echo site_url('visitor/exportmap'); ?>/<?php echo $slug; ?>" target="_blank"><?php echo site_url('visitor/exportmap'); ?>/<?php echo $slug; ?></a></p>
</div>
<?php } ?>
