<div class="container-fluid">
	<?= __("Update QSOs with state/province information based on gridsquare and DXCC country."); ?><br /><br />
	<?= __("This feature uses GeoJSON boundary data to determine the state/province from the gridsquare locator."); ?><br /><br />
	<?= __("Update will only set the state for QSOs where:"); ?>
	<ul>
		<li><?= __("The state field is empty"); ?></li>
		<li><?= __("A gridsquare is present (at least 6 characters)"); ?></li>
		<li><?= __("The DXCC country supports state lookup"); ?></li>
	</ul>
	<?= __("Currently supported countries: Canada, France, Germany, Italy, Netherlands, Poland, Switzerland, USA, Japan"); ?>
</div>
