<div class="container">
<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<h2><?php echo $page_title; ?></h2>

<div class="card">
  <div class="card-header">
    <?= __("Backup"); ?>
  </div>
  <div class="card-body">
    <p class="card-text"><?= __("Some of the data stored in Wavelog can be exported so that you can keep a backup copy elsewhere."); ?></p>
	<p class="card-text"><?= __("It's recommended to create backups on a regular basis to protect your data."); ?></p>
	<p><a href="<?php echo site_url('backup/adif'); ?>" title="Export a backup copy of your ADIF data" class="btn btn-primary"><?= __("Backup ADIF data"); ?></a></p>
	<p><a href="<?php echo site_url('backup/notes'); ?>" title="Export a backup copy of your notes" class="btn btn-primary"><?= __("Backup Notes"); ?></a></p>
  </div>
</div>


</div>

