
<?php
$custom_date_format = $this->session->userdata('user_date_format');
?>
<div class="container eqsl">
<div class="card">
  <div class="card-header">
    <h5 class="card-title"><?php echo $page_title; ?></h5>
    <ul class="nav nav-tabs card-header-tabs">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/import');?>"><?= __("Download QSOs"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/Export');?>"><?= __("Upload QSOs"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/tools');?>"><?= __("Tools"); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="<?php echo site_url('eqsl/download');?>"><?= __("Download eQSL cards"); ?></a>
      </li>
    </ul>
  </div>

	<div class="card-body">
		<?php $this->load->view('layout/messages'); ?>

			<div class="alert alert-info" role="alert">
				<?php echo $eqsl_stats; ?>
			</div>
				<table width="100%">
					<tr class="titles">
						<td><?= __("Date"); ?></td>
						<td><?= __("Time"); ?></td>
						<td><?= __("Call"); ?></td>
						<td><?= __("Mode"); ?></td>
						<td><?= __("Submode"); ?></td>
						<td><?= __("eQSL Status"); ?></td>
					</tr>
					<?php foreach ($eqsl_results as $qso) { ?>
					<tr>
						<?php $timestamp = strtotime($qso['date']); ?>
						<td><?php echo date($custom_date_format, $timestamp) ?></td>
						<td><?php echo date('H:i', $timestamp); ?></td>
						<td><a id="edit_qso" href="javascript:displayQso(<?php echo $qso['qsoid']; ?>)"><?php echo $qso['call']; ?></a></td>
						<td><?php echo $qso['mode']; ?></td>
						<td><?php echo $qso['submode']; ?></td>
						<td><?php echo $qso['status']; ?></td>
					</tr>
					<?php } ?>
				</table>
	</div>
</div>

</div>
