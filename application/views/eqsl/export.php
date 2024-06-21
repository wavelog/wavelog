
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
        <a class="nav-link active" href="<?php echo site_url('eqsl/Export');?>"><?= __("Upload QSOs"); ?></a>
      </li>
<?php } ?>
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

<?php
	if (isset($eqsl_table))
	{
?>
    	<p><?= __("Below is a table of QSOs that have not yet been sent to eQSL."); ?></p>

    	<p><span class="badge text-bg-info"><?= __("Info"); ?></span> <?= __("Please make sure the 'eQSL QTH Nickname' field is set in your station profile and that the value matches the QTH Nickname you set within eQSL."); ?></p>
 <?php

    	echo $eqsl_table;
    	echo '<p>' . __("Clicking 'Upload QSOs' will send QSO information to eQSL.cc.") . '</p>';
		echo form_open('eqsl/export');
		echo "<input type=\"hidden\" name=\"eqslexport\" id=\"eqslexport\" value=\"export\" />";
		echo "<input class=\"btn btn-primary\" type=\"submit\" value=\"Upload QSOs\" /></form>";
	}
	else
	{
		if (isset($eqsl_results_table))
		{
			echo '<p>' . __("The following QSOs were sent to eQSL.") . '</p>';
			echo $eqsl_results_table;
		}
		else
		{
			echo '<p>' . __("There are no QSOs that need to be sent to eQSL at this time. Go log some more QSOs!") . '</p>';
		}
	}
?>
</div>

</div>
</div>
