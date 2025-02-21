
<?php
$custom_date_format = $this->session->userdata('user_date_format');
?>
<div class="container eqsl">
<div class="card">
  <div class="card-header">
    <h5 class="card-title"><?php echo $page_title; ?></h5>
    <ul class="nav nav-tabs card-header-tabs">
      <li class="nav-item">
        <a class="nav-link active" href="<?php echo site_url('eqsl/import');?>"><?= __("Download QSOs"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/Export');?>"><?= __("Upload QSOs"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/tools');?>"><?= __("Tools"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/download');?>"><?= __("Download eQSL cards"); ?></a>
      </li>
    </ul>
  </div>

	<div class="card-body">
		<?php $this->load->view('layout/messages'); ?>

		<?php foreach ($eqsl_results as $import) { ?>
			<h3><?php echo $import['name']; ?></h3>
			<div class="alert alert-info" role="alert">
				<?php echo $import['status']; ?>
			</div>
			<?php if (count($import['qsos']) > 0) { ?>
				<table width="100%">
					<tr class="titles">
						<td><?= __("Date"); ?></td>
						<td><?= __("Time"); ?></td>
						<td><?= __("Call"); ?></td>
						<td><?= __("Mode"); ?></td>
						<td><?= __("Submode"); ?></td>
						<td><?= __("eQSL Received Date"); ?></td>
						<td><?= __("Log Status"); ?></td>
						<td><?= __("eQSL Status"); ?></td>
					</tr>
					<?php foreach ($import['qsos'] as $qso) { ?>
						<tr>
							<?php $timestamp = strtotime($qso['date']); ?>
							<td><?php echo date($custom_date_format, $timestamp) ?></td>
							<td><?php echo date('H:i', $timestamp); ?></td>
							<?php if ($qso['status'] == "Found") { ?>
								<td><a id="view_eqsl_qso" href="javascript:displayQso(<?php echo $qso['qsoid']; ?>)"><?php echo $qso['call']; ?></a></td>
							<?php } else { ?>
								<td><?php echo $qso['call']; ?></td>
							<?php } ?>
							<td><?php echo $qso['mode']; ?></td>
							<td><?php echo $qso['submode']; ?></td>
							<?php if ($qso['status'] == "Found") { ?>
								<td><a href="<?php echo site_url("eqsl/image/".$qso['qsoid']); ?>" data-fancybox="images" data-width="528" data-height="336"><?php echo date ($custom_date_format, strtotime($qso['eqsl_qslrdate'])); ?></a></td>
							<?php } else { ?>
								<td><?php echo date ($custom_date_format, strtotime($qso['eqsl_qslrdate'])); ?></td>
							<?php } ?>
							<td><?php echo $qso['status']; ?></td>
							<td><?php echo $qso['eqsl_status']; ?></td>
						</tr>
					<?php } ?>
				</table>
			<?php } else { ?>
				<p><?= __("There are no QSO confirmations waiting for you at eQSL.cc"); ?></p>
			<?php } ?>
		<?php } ?>
	</div>
</div>

</div>
