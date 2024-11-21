<div class="container search">

	<h1>
		<?= __("Search"); ?>
		<small class="text-muted"><?= __("Ready to find a QSO?"); ?></small>
	</h1>

	<div class="card text-center">
	  <div class="card-header">
	    <ul class="nav nav-tabs card-header-tabs">
	      <li class="nav-item">
	        <a class="nav-link active" href="<?php echo site_url('search'); ?>"><?= __("Search"); ?></a>
	      </li>
	      <li class="nav-item">
	        <a class="nav-link" href="<?php echo site_url('search/filter'); ?>"><?= __("Advanced Search"); ?></a>
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
	  <div class="card-body">
	  	<form method="post" action="" id="search_box" name="test">
		  <div class="mb-3 row">
		    <label for="callsign" class="col-sm-2 col-form-label"><?= __("Callsign / Gridsquare"); ?></label>
		    <div class="col-sm-8">
		      <input type="text" class="form-control" id="callsign" value="<?php if ($this->input->post('callsign') !== null) { echo htmlspecialchars($this->input->post('callsign')); }; ?>">
		    </div>
		    <div class="col-sm-2">
		    	<button onclick="searchButtonPress()" class="btn btn-outline-success my-2 my-sm-0" type="submit"><i class="fas fa-search"></i> <?= __("Search"); ?></button>
		    </div>
		  </div>
		</form>

		<div id="partial_view"></div>

	  </div>
	</div>

</div>
