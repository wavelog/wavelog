<table style="width:100%" class="table-sm table table-striped table-bordered table-hover table-condensed">
	<thead>
		<tr>
			<th><?php echo __("Status"); ?></th>
			<th><?php echo __("Message"); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?= __("Open request"); ?></td>
			<td><?= __("The request is currently open, and needs to be reviewed by you."); ?></td>
		</tr>
		<tr>
			<td><?= __("Not in log request"); ?></td>
			<td><?= __("The request is not in the log, so you need to check your log and process the request."); ?></td>
		</tr>
		<tr>
			<td><?= __("Done / sent"); ?></td>
			<td><?= __("The request has been processed and the QSL has been sent."); ?></td>
		</tr>
		<tr>
			<td><?= __("Pending"); ?></td>
			<td><?= __("The request is still being processed."); ?></td>
		</tr>
		<tr>
			<td><?= __("Rejected"); ?></td>
			<td><?= __("The request has been rejected and will not be processed."); ?></td>
		</tr>
	</tbody>
</table>
