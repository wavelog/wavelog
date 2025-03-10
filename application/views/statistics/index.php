<style>
	/*canvas{
	    margin: 0 auto;
    }*/

	#modeChart, #bandChart, #satChart{
		margin: 0 auto;
	}
</style>


<script>
		// General Language
		var lang_statistics_years = "<?= __("Years")?>";
		var lang_statistics_modes = "<?= __("Mode")?>";
		var lang_statistics_bands = "<?= __("Bands")?>";
		var lang_statistics_operators = "<?= __("Operators")?>";
		var lang_statistics_number_of_qso_worked_each_year = "<?= __("Number of QSOs worked each year")?>";
		var lang_statistics_year = "<?= __("Year")?>";
		var lang_statistics_number_of_qso_worked = "<?= __("# of QSOs worked")?>";
		var lang_gen_hamradio_mode = "<?= __("Mode")?>";
		var lang_gen_hamradio_band = "<?= __("Band")?>";
		var lang_gen_hamradio_operator = "<?= __("Operator")?>";
		var lang_gen_satellite = "<?= __("Satellite")?>";
</script>

<div class="container statistics">

	<h2>
		<?php echo $page_title; ?>
		<small class="text-muted"><?= __("Explore the logbook."); ?></small>
	</h2>
		<br/>
		<select class="form-select form-select-sm me-2 w-auto" style="display:none;" id="yr" name="yr">
			<option value='All'><?= __("All Years"); ?></option>
		<?php 
			foreach($years as $yr) {
				echo '<option value="'.$yr.'">'.__("Year")." ".$yr.'</option>';
			}
		?>
		</select>
	<br>
	<div hidden class="tabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true"><?= __("General"); ?></a>
			</li>
			<?php if ($sat_active) { ?>
			<li class="nav-item">
				<a class="nav-link" id="satellite-tab" data-bs-toggle="tab" href="#satellite" role="tab" aria-controls="satellite" aria-selected="false"><?= __("Satellites"); ?></a>
			</li>
			<?php } ?>
		</ul>
	</div>

		<div class="tab-content" id="myTabContent">
			<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
					<br />
					<ul class="nav nav-pills" id="myTab2" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="years-tab" data-bs-toggle="tab" href="#yearstab" role="tab" aria-controls="yearstab" aria-selected="true"><?= __("Years"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="mode-tab" data-bs-toggle="tab" href="#modetab" role="tab" aria-controls="modetab" aria-selected="false"><?= __("Mode"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="band-tab" data-bs-toggle="tab" href="#bandtab" role="tab" aria-controls="bandtab" aria-selected="false"><?= __("Bands"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="qso-tab" data-bs-toggle="tab" href="#qsotab" role="tab" aria-controls="bandtab" aria-selected="false"><?= __("QSOs"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="operators-tab" data-bs-toggle="tab" href="#operatorstab" role="tab" aria-controls="operatorstab" aria-selected="false"><?= __("Operators"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="unique-tab" data-bs-toggle="tab" href="#uniquetab" role="tab" aria-controls="uniquetab" aria-selected="false"><?= __("Unique callsigns"); ?></a>
						</li>
					</ul>
				<div class="tab-content">
					<div class="tab-pane fade show active" id="yearstab" role="tabpanel" aria-labelledby="years-tab">
						<div class="years" style="margin-top: 20px;">
						</div>
					</div>
					<div class="tab-pane fade" id="modetab" role="tabpanel" aria-labelledby="mode-tab">
							<div class="mode">
							</div>
					</div>
					<div class="tab-pane fade" id="bandtab" role="tabpanel" aria-labelledby="band-tab">
							<div class="band">
							</div>
					</div>
					<div class="tab-pane fade" id="qsotab" role="tabpanel" aria-labelledby="qso-tab">
							<div class="qsos">
							</div>
					</div>
					<div class="tab-pane fade" id="operatorstab" role="tabpanel" aria-labelledby="operators-tab">
							<div class="operators">
							</div>
					</div>
					<div class="tab-pane fade" id="uniquetab" role="tabpanel" aria-labelledby="unique-tab">
							<div class="unique">
							</div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="satellite" role="tabpanel" aria-labelledby="satellite-tab">
					<br />
					<ul class="nav nav-pills" id="myTab3" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="sat-tab" data-bs-toggle="tab" href="#sattab" role="tab" aria-controls="sattab" aria-selected="true"><?= __("Satellites"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="satqsos-tab" data-bs-toggle="tab" href="#satqsostab" role="tab" aria-controls="satqsostab" aria-selected="false"><?= __("QSOs"); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="satunique-tab" data-bs-toggle="tab" href="#satuniquetab" role="tab" aria-controls="satuniquetab" aria-selected="false"><?= __("Unique callsigns"); ?></a>
						</li>
					</ul>
				<div class="tab-content">
					<div class="tab-pane fade show active" id="sattab" role="tabpanel" aria-labelledby="sat-tab">
						<div class="satsummary">
						</div>
					</div>
					<div class="tab-pane fade" id="satqsostab" role="tabpanel" aria-labelledby="satqsos-tab">
						<div class="satqsos" style="margin-top: 20px;">
						</div>
					</div>
					<div class="tab-pane fade" id="satuniquetab" role="tabpanel" aria-labelledby="satunique-tab">
						<div class="satunique" style="margin-top: 20px;">
						</div>
					</div>
				</div>
			</div>
		</div>
</div>
