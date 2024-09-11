<div class="container search">

	<h1>
		<?= __("Advanced Search"); ?>
		<small class="text-muted"></small>
	</h1>

	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

	<!-- Filter options here -->
	<div class="card">
	  <div class="card-header">
	    <ul class="nav nav-tabs card-header-tabs">
	      <li class="nav-item">
	        <a class="nav-link" href="<?php echo site_url('search'); ?>"><?= __("Search"); ?></a>
	      </li>
	      <li class="nav-item">
	        <a class="nav-link active" href="<?php echo site_url('search/filter'); ?>"><?= __("Advanced Search"); ?></a>
	      </li>
		  <li class="nav-item">
	        <a class="nav-link" href="<?php echo site_url('search/incorrect_cq_zones'); ?>"><?= __("Incorrect CQ Zones"); ?></a>
	      </li>
		  <li class="nav-item">
	        <a class="nav-link" href="<?php echo site_url('search/incorrect_itu_zones'); ?>"><?= __("Incorrect ITU Zones"); ?></a>
	      </li>
		  <li class="nav-item">
	        <a class="nav-link" href="<?php echo site_url('search/lotw_unconfirmed'); ?>"><?= __("QSOs unconfirmed on LoTW"); ?></a>
	      </li>
	    </ul>
	  </div>
	  <div class="card-body main">

		<div class="card-text col-md-4" id="builder"></div>

		<p class="card-text">
		<button class="btn btn-sm btn-primary ld-ext-right searchbutton" id="btn-get"><?= __("Search"); ?><div class="ld ld-ring ld-spin"></div></button>

		<button class="btn btn-sm btn-warning" id="btn-reset"><?= __("Reset"); ?></button>
		</p>
	  <p>
		<button style="display:none;" class="btn btn-sm btn-primary" id="btn-save"><?= __("Save query"); ?></button>

		  <?php if ($stored_queries) { ?>
			<button class="btn btn-sm btn-primary" onclick="edit_stored_query_dialog()" id="btn-edit"><?= __("Edit queries"); ?></button></p>


		  <div class="mb-3 row querydropdownform">
			  <label class="col-md-2 control-label" for="querydropdown"><?= __("Stored queries"); ?>:</label>
			  <div class="col-md-2">
				  <select id="querydropdown" name="querydropdown" class="form-select form-select-sm">
					  <?php
					  foreach($stored_queries as $q){
						  echo '<option value="' . $q->id . '">'. $q->description . '</option>'."\n";
					  }
					  ?>
				  </select>
			  </div>
			  <button class="btn btn-sm btn-primary ld-ext-right runbutton col-md-1" onclick="run_query()"><?= __("Run Query"); ?><div class="ld ld-ring ld-spin"></div></button>
		  </div>

			<?php
		  } else {
			echo '</p>';
		  }
		  ?>

	    	<div style="display:none;"><span  class="badge text-bg-info"><?= __("Info"); ?></span> <?= sprintf(__("You can find out how to use the %s in the wiki."), '<a href="https://github.com/wavelog/wavelog/wiki/Search----Filter" target="_blank">' . __("search filter functions") . '</a>'); ?></div>

	  </div>
	</div>

	<br>

	<!-- Search Results here -->
	<div class="card search-results-box">
	  <div class="card-header">
	    <?= __("Search Results"); ?>:  <div class="exportbutton"><button class="btn btn-sm btn-primary" id="btn-export"><?= __("Export to ADIF"); ?></button></div>
	  </div>
	  <div class="card-body result">

	  </div>
	</div>

</div>
