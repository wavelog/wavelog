<script type="text/javascript">
    var lang_summary_dxcc = '<?= __("Showing summary for DXCC"); ?>';
    var lang_summary_state = '<?= __("Showing summary for US State"); ?>';
    var lang_summary_cq = '<?= __("Showing summary for CQ zone"); ?>';
    var lang_summary_wwff = '<?= __("Showing summary for WWFF"); ?>';
    var lang_summary_pota = '<?= __("Showing summary for POTA"); ?>';
    var lang_summary_sota = '<?= __("Showing summary for SOTA"); ?>';
    var lang_summary_iota = '<?= __("Showing summary for IOTA"); ?>';
    var lang_summary_continent = '<?= __("Showing summary for continent"); ?>';
    var lang_summary_gridsquare = '<?= __("Showing summary for gridsquare"); ?>';
	var lang_summary_warning_empty_state = '<?= __("State input needs to be filled to show a summary!"); ?>';
	var lang_summary_warning_empty_sota = '<?= __("SOTA input needs to be filled to show a summary!"); ?>';
	var lang_summary_warning_empty_pota = '<?= __("POTA input needs to be filled to show a summary!"); ?>';
	var lang_summary_warning_empty_iota = '<?= __("IOTA input needs to be filled to show a summary!"); ?>';
	var lang_summary_warning_empty_wwff = '<?= __("WWFF input needs to be filled to show a summary!"); ?>';
	var lang_summary_warning_empty_gridsquare = '<?= __("Gridsquare input needs to be filled to show a summary!"); ?>';
	var lang_summary_info_only_first_pota = '<?= __("Summary only shows for the first POTA entered."); ?>';
	var lang_summary_info_only_first_gridsquare = '<?= __("Summary only shows for the first gridsquare entered."); ?>';
	var lang_summary_state_valid = '<?= __("Summary only shows for US states."); ?>';
</script>
<div class="card">
	<div class="card-header">
		<ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right" id="awardTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="dxcc-summary-tab" data-bs-toggle="tab" href="#dxcc-summary" role="tab" aria-controls="dxcc-summary" aria-selected="true"><?= __("DXCC"); ?> <i class="dxcc-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="cq-summary-tab" data-bs-toggle="tab" href="#cq-summary" role="tab" aria-controls="cq-summary" aria-selected="false"><?= __("CQ"); ?> <i class="cq-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="continent-summary-tab" data-bs-toggle="tab" href="#continent-summary" role="tab" aria-controls="continent-summary" aria-selected="false"><?= __("Continent"); ?> <i class="continent-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="gridsquare-summary-tab" data-bs-toggle="tab" href="#gridsquare-summary" role="tab" aria-controls="gridsquare-summary" aria-selected="false"><?= __("Gridsquare"); ?> <i class="gridsquare-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="iota-summary-tab" data-bs-toggle="tab" href="#iota-summary" role="tab" aria-controls="iota-summary" aria-selected="false"><?= __("IOTA"); ?> <i class="iota-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="state-summary-tab" data-bs-toggle="tab" href="#state-summary" role="tab" aria-controls="state-summary" aria-selected="false"><?= __("US State"); ?> <i class="state-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="pota-summary-tab" data-bs-toggle="tab" href="#pota-summary" role="tab" aria-controls="pota-summary" aria-selected="false"><?= __("POTA"); ?> <i class="pota-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="sota-summary-tab" data-bs-toggle="tab" href="#sota-summary" role="tab" aria-controls="sota-summary" aria-selected="false"><?= __("SOTA"); ?> <i class="sota-summary-reload fas fa-sync"></i></a>
			</li>

			<li class="nav-item">
				<a class="nav-link" id="wwff-summary-tab" data-bs-toggle="tab" href="#wwff-summary" role="tab" aria-controls="wwff-summary" aria-selected="false"><?= __("WWFF"); ?> <i class="wwff-summary-reload fas fa-sync"></i></a>
			</li>
		</ul>
	</div>
	<div class="card-body">
		<div class="tab-content">
			<div class="tab-pane fade show active" id="dxcc-summary" role="tabpanel" aria-labelledby="dxcc-summary-tab"></div>
			<div class="tab-pane fade" id="cq-summary" role="tabpanel" aria-labelledby="cq-summary-tab"></div>
			<div class="tab-pane fade" id="continent-summary" role="tabpanel" aria-labelledby="continent-summary-tab"></div>
			<div class="tab-pane fade" id="gridsquare-summary" role="tabpanel" aria-labelledby="gridsquare-summary-tab"></div>
			<div class="tab-pane fade" id="iota-summary" role="tabpanel" aria-labelledby="iota-summary-tab"></div>
			<div class="tab-pane fade" id="state-summary" role="tabpanel" aria-labelledby="state-summary-tab"></div>
			<div class="tab-pane fade" id="pota-summary" role="tabpanel" aria-labelledby="pota-summary-tab"></div>
			<div class="tab-pane fade" id="sota-summary" role="tabpanel" aria-labelledby="sota-summary-tab"></div>
			<div class="tab-pane fade" id="wwff-summary" role="tabpanel" aria-labelledby="wwff-summary-tab"></div>
		</div>
	</div>
</div>
