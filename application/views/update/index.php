<div class="container">
	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right"  id="myTab" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" id="dxcc-tab" data-bs-toggle="tab" href="#dxcc" role="tab" aria-controls="update" aria-selected="true"><?= __("DXCC Lookup Data"); ?></a>
				</li>
			</ul>
		</div>
		<div class="card-body">
			<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade show active" id="dxcc" role="tabpanel" aria-labelledby="dxcc-tab">
					<p class="card-text"><?= __("Here you can update the DXCC lookup data that is used for displaying callsign information."); ?></p>
					<p class="card-text"><?= __("This data is provided by"); ?> <a href="https://clublog.org/"><?= __("Clublog"); ?></a>.</p>

					<?php if(!extension_loaded('xml')) { ?>
						<div class="alert alert-danger" role="alert">
						<?= __("You must install php-xml for this to work."); ?>
						</div>
					<?php } else { ?>
						<h5><?= __("Check for DXCC Data Updates"); ?></h5>
						<button type="submit" class="btn btn-primary ld-ext-right" id="btn_update_dxcc"><div class="ld ld-ring ld-spin"></div><?= __("Update DXCC Data"); ?></button>

						<div id="dxcc_update_status" class="alert alert-secondary mt-3 w-25 w-lg-100" style="display: none;"><?= __("Status:"); ?></br></div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>


