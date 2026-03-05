<script>
	var lang_edit_cat_settings = "<?= __("Edit CAT Settings"); ?>";
</script>

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
	    <?= __("Active Radios"); ?>
	  </div>
	  <div class="card-body">
	    <p class="card-text"><?= __("Below is a list of active radios that are connected to Wavelog."); ?> <?= __("If you haven't connected any radios yet, see the API page to generate API keys."); ?></p>
		<?php if ($this->session->userdata('clubstation') == 1) { ?>
			<p class="card-text"><?= __("As a clubstation operator, you can set a default radio which applies only to you. This allows you to have a default radio that is automatically selected when you log in, while still being able to use other radios if you want."); ?></p>
		<?php } else { ?>
			<p class="card-text"><?= __("As a normal user, you can set a default radio for yourself. This allows you to have a default radio that is automatically selected when you log in, while still being able to use other radios if you want."); ?></p>
		<?php } ?>
		<p class="card-text">
	    	<span class="badge text-bg-info"><?= __("Info"); ?></span> <?= sprintf(__("You can find out how to use the %sradio functions%s in the wiki."), '<a href="https://docs.wavelog.org/user-guide/integrations/radio-interface/" target="_blank">', '</a>'); ?>
	    </p>
	    <div class="table-responsive">
		    <!-- Display Radio Statuses -->
			<table class="table table-sm table-condensated table-striped status"></table>
			<h6 id="radioResultsLoading" class="text-center"><div class="me-3 ld ld-ring ld-spin"></div><?= __("Please wait..."); ?></h6>
		</div>
	  </div>
	</div>

</div>
