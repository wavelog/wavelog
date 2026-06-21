<div class="container-fluid p-4">
	<div class="row g-4">
		<!-- Search Tips Card -->
		<div class="col-md-6">
			<div class="card shadow-sm border-0">
				<div class="card-header bg-primary text-white">
					<h5 class="mb-0"><i class="fas fa-search me-2"></i><?= __("Search Tips"); ?></h5>
				</div>
				<div class="card-body">
					<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("Use these special operators in text input searches:"); ?></p>
					<ul class="mb-0">
						<li>
							<span class="badge bg-secondary me-4 fs-6 p-2">*</span>
							<span><?= __("Search for everything"); ?></span>
						</li>
						<li>
							<span class="badge bg-secondary me-4 fs-6 p-2">blank</span>
							<span><?= __("Search where column is empty"); ?></span>
						</li>
						<li>
							<span class="badge bg-secondary me-4 fs-6 p-2">!empty</span>
							<span><?= __("Search where column is not empty"); ?></span>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Dupe Search Card -->
		<div class="col-md-6">
			<div class="card shadow-sm border-0 h-100">
				<div class="card-header bg-warning text-dark">
					<h5 class="mb-0"><i class="fas fa-clone me-2"></i><?= __("Duplicate Search"); ?></h5>
				</div>
				<div class="card-body">
					<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("The dupe search checks for duplicate QSOs with the same:"); ?></p>
					<ul class="mb-0">
						<li><?= __("Callsign"); ?></li>
						<li><?= __("Mode / Submode"); ?></li>
						<li><?= __("Station callsign"); ?></li>
						<li><?= __("Band"); ?></li>
						<li><?= __("Satellite"); ?></li>
					</ul>
					<hr>
					<p class="mb-0"><strong><?= __("Time window:"); ?></strong> <span class="badge bg-secondary me-4 fs-6 p-2">1500 <?= __("seconds"); ?></span></p>
				</div>
			</div>
		</div>

		<!-- Invalid Search Card -->
		<div class="col-md-6">
			<div class="card shadow-sm border-0 h-100">
				<div class="card-header bg-danger text-white">
					<h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?= __("Invalid Search"); ?></h5>
				</div>
				<div class="card-body">
					<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("Checks for the following conditions:"); ?></p>
					<ul class="mb-0">
						<li><?= __("Mode is blank or set to 0"); ?></li>
						<li><?= __("Band is blank"); ?></li>
						<li><?= __("Callsign is blank"); ?></li>
						<li><?= __("Time and date is not set"); ?></li>
						<li><?= __("Date is set to 1970-01-01"); ?></li>
						<li><?= __("Continent is invalid (not AF, AN, AS, EU, NA, OC, or SA)"); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Map Card -->
		<div class="col-md-6">
			<div class="card shadow-sm border-0 h-100">
				<div class="card-header bg-success text-white">
					<h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i><?= __("Map View"); ?></h5>
				</div>
				<div class="card-body">
					<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("The map uses the same search criteria as the normal search."); ?></p>
					<p class="card-text mb-0"><strong><?= __("Note:"); ?></strong> <?= __("All QSOs in the search result will be mapped, unless you have checked one or more QSOs."); ?></p>
				</div>
			</div>
		</div>

		<!-- Batch Edit Card -->
			<div class="col-md-6">
				<div class="card shadow-sm border-0">
					<div class="card-header bg-dark text-white">
						<h5 class="mb-0"><i class="fas fa-edit me-2"></i><?= __("Batch Edit"); ?></h5>
					</div>
					<div class="card-body">
						<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("Batch edit allows you to modify a single column across all checked QSOs at once."); ?></p>
						<p class="card-text mb-2"><strong><?= __("Available edit categories:"); ?></strong></p>
						<ul class="mb-0">
							<li><strong><?= __("QSO Details:"); ?></strong></li>
							<li><strong><?= __("Awards:"); ?></strong></li>
							<li><strong><?= __("Confirmations:"); ?></strong></li>
						</ul>
						<p class="card-text mb-0"><?= __("Not all columns are available for batch editing. If you can't find the column you are looking for, use the regular QSO edit (click the callsign, then click the edit QSO button)."); ?></p>
						<hr>
						<p class="card-text mb-0"><strong><?= __("Note:"); ?></strong> <?= __("You must check one or more QSOs before using batch edit. For complex changes, use the regular QSO edit instead."); ?></p>
					</div>
				</div>
			</div>

			<!-- ADIF Export Card -->
		<div class="col-md-6">
			<div class="card shadow-sm border-0 h-100">
				<div class="card-header bg-info text-white">
					<h5 class="mb-0"><i class="fas fa-file-export me-2"></i><?= __("ADIF Export"); ?></h5>
				</div>
				<div class="card-body">
					<p class="card-text"><i class="fas fa-2x fa-info-circle text-info me-2"></i><?= __("The ADIF export uses the same search criteria as the normal search."); ?></p>
					<p class="card-text mb-0"><strong><?= __("Note:"); ?></strong> <?= __("All QSOs will be exported (all for selected location), unless you have checked one or more QSOs."); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
