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
		var lang_statistics_months = "<?= __("Months")?>";
		var lang_statistics_modes = "<?= __("Mode")?>";
		var lang_statistics_bands = "<?= __("Bands")?>";
		var lang_statistics_operators = "<?= __("Operators")?>";
		var lang_statistics_number_of_qso_worked_each_year = "<?= __("Number of QSOs worked each year")?>";
		var lang_statistics_number_of_qso_worked_each_month = "<?= __("Number of QSOs worked each month")?>";
		var lang_statistics_year = "<?= __("Year")?>";
		var lang_statistics_month = "<?= __("Month")?>";
		var lang_statistics_number_of_qso_worked = "<?= __("# of QSOs worked")?>";
		var lang_gen_hamradio_mode = "<?= __("Mode")?>";
		var lang_gen_hamradio_band = "<?= __("Band")?>";
		var lang_gen_hamradio_operator = "<?= __("Operator")?>";
		var lang_gen_satellite = "<?= __("Satellite")?>";

		// Month names for translation
		var monthNames = {
			"01": "<?= __("January")?>",
			"02": "<?= __("February")?>",
			"03": "<?= __("March")?>",
			"04": "<?= __("April")?>",
			"05": "<?= __("May")?>",
			"06": "<?= __("June")?>",
			"07": "<?= __("July")?>",
			"08": "<?= __("August")?>",
			"09": "<?= __("September")?>",
			"10": "<?= __("October")?>",
			"11": "<?= __("November")?>",
			"12": "<?= __("December")?>"
		};
</script>

<div class="container statistics">

	<h2>
		<?php echo $page_title; ?>
		<small class="text-muted"><?= __("Explore the logbook."); ?></small>
	</h2>
		<br/>
		<div class="mb-3" id="dateFilterContainer" style="display:none;">
		<label class="form-label"><?= __("Date Presets") . ": " ?></label>
		<div class="d-flex gap-1 d-flex flex-wrap mb-2">
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('today')"><?= __("Today") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('yesterday')"><?= __("Yesterday") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last7days')"><?= __("Last 7 Days") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last30days')"><?= __("Last 30 Days") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thismonth')"><?= __("This Month") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastmonth')"><?= __("Last Month") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thisyear')"><?= __("This Year") ?></button>
			<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastyear')"><?= __("Last Year") ?></button>
			<button type="button" class="btn btn-danger btn-sm flex-shrink-0" onclick="resetDates()"><i class="fas fa-times"></i> <?= __("All") ?></button>
		</div>
		<div class="row g-2">
			<div class="col-auto">
				<label class="col-form-label col-form-label-sm" for="dateFrom"><?= __("Date from") ?></label>
				<input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto border border-secondary">
			</div>
			<div class="col-auto">
				<label class="col-form-label col-form-label-sm" for="dateTo"><?= __("Date to") ?></label>
				<input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto border border-secondary">
			</div>
		</div>
	</div>
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
							<a class="nav-link" id="months-tab" data-bs-toggle="tab" href="#monthstab" role="tab" aria-controls="monthstab" aria-selected="false"><?= __("Months"); ?></a>
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
					<div class="tab-pane fade" id="monthstab" role="tabpanel" aria-labelledby="months-tab">
						<div class="months" style="margin-top: 20px;">
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
						<li class="nav-item">
							<a class="nav-link" id="satunique-tab" data-bs-toggle="tab" href="#satuniquegridtab" role="tab" aria-controls="satuniquegridtab" aria-selected="false"><?= __("Unique Grids"); ?></a>
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
					<div class="tab-pane fade" id="satuniquegridtab" role="tabpanel" aria-labelledby="satuniquegrid-tab">
						<div class="satuniquegrid" style="margin-top: 20px;">
						</div>
					</div>
				</div>
			</div>
		</div>
</div>
