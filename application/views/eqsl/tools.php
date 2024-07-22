<div class="container eqsl">
<h2><?php echo $page_title; ?></h2>
<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/import');?>"><?= __("Download QSOs"); ?></a>
      </li>
<?php if (!($this->config->item('disable_manual_eqsl'))) { ?>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/Export');?>"><?= __("Upload QSOs"); ?></a>
      </li>
<?php } ?>
      <li class="nav-item">
        <a class="nav-link active" href="<?php echo site_url('eqsl/tools');?>"><?= __("Tools"); ?></a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="<?php echo site_url('eqsl/download');?>"><?= __("Download eQSL cards"); ?></a>
      </li>
    </ul>
  </div>

  <div class="card-body">
		<?php $this->load->view('layout/messages'); ?>
    <div class="alert alert-warning">
      <p><?= __("This does NOT upload any QSOs. It only marks QSOs as sent. If you use this button you need to upload them manually on the eQSL.cc website."); ?></p>
    </div>
    <p><a class="btn btn-primary" href="<?php echo site_url('eqsl/mark_all_sent'); ?>"><?= __("Mark All QSOs as Sent to eQSL"); ?></a> <?= __("Use this if you have lots of QSOs to upload to eQSL it will save the server timing out."); ?></p>
  </div>
</div>

</div>
