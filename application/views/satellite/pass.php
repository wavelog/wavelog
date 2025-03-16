<div class="container container-fluid">
<h2><?= __("Satellite passes"); ?></h2>
<div class="card">
	<div class="card-body">
		<form class="d-flex align-items-center">
		<div class="row">
			<?php if ($satellites) { ?>

			<h4>Your station</h4>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" id="label_minelevation" for="minelevation"><?= __("Min. Satellite Elevation"); ?></label>
				<input class="my-1 me-sm-2 w-auto form-control form-control-sm" id="minelevation" type="number" min="0" max="90" name="minelevation" value="0" />
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="minazimuth"><?= __("Min. Azimuth"); ?></label>
				<select class="my-1 me-sm-2 w-auto form-select form-select-sm" id="minazimuth" name="minazimuth">
				<?php for ($i = 0; $i <= 350; $i += 10): ?>
					<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
				<?php endfor; ?>
			</select>
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="maxazimuth"><?= __("Max. Azimuth"); ?></label>
				<select class="my-1 me-sm-2 w-auto form-select form-select-sm" id="maxazimuth" name="maxazimuth">
					<?php for ($i = 10; $i <= 360; $i += 10): ?>
						<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
					<?php endfor; ?>
				</select>
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="yourgrid"><?= __("Gridsquare"); ?></label>
					<input class="my-1 me-sm-2 w-auto form-control form-control-sm uppercase"  id="yourgrid" type="text" name="gridsquare" value="<?php echo $activegrid; ?>"/>
			</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="date"><?= __("Date"); ?></label>
						<input name="date" id="date" type="date" class="form-control form-control-sm my-1 me-sm-2 w-auto" value="<?php echo date('Y-m-d'); ?>" >
					</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="mintime"><?= __("Min. time"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select form-select-sm" id="mintime" name="mintime">
                        <?php for ($i = 0; $i <= 24; $i += 1): ?>
                            <option value="<?= $i ?>" <?= ($i == gmdate("H")) ? 'selected' : '' ?>><?= $i ?>:00</option>
                        <?php endfor; ?>
						</select>
				</div>
                <div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" id="satslabel" for="satlist"><?= __("Satellite"); ?>   <i class="fa fa-question-circle" aria-hidden="true" data-bs-toggle="tooltip" title="Only satellites with TLE data are shown here!"></i></label>
					<select class="my-1 me-sm-2 w-auto form-select form-select-sm"  id="satlist">
						<option value="">All</option>
						<?php foreach($satellites as $sat) {
							echo '<option value="' . $sat->satname . '"' . '>' . $sat->satname . '</option>'."\n";
						} ?>
					</select>
				</div>

				<div id="addskedpartner" style="display:none" class="row">
				<h4>Sked partner</h2>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" id="minskedelevationlabel" for="minskedelevation"><?= __("Min. Satellite Elevation"); ?></label>
						<input class="my-1 me-sm-2 w-auto form-control form-control-sm" id="minskedelevation" type="number" min="0" max="90" name="minskedelevation" value="0" />
					</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="minskedazimuth"><?= __("Min. Azimuth"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select form-select-sm" id="minskedazimuth" name="minskedazimuth">
						<?php for ($i = 0; $i <= 350; $i += 10): ?>
							<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
						<?php endfor; ?>
					</select>
					</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="maxskedazimuth"><?= __("Max. Azimuth"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select form-select-sm" id="maxskedazimuth" name="maxskedazimuth">
							<?php for ($i = 10; $i <= 360; $i += 10): ?>
								<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="mb-3 w-auto">
							<label class="my-1 me-sm-2 w-auto" for="skedgrid"><?= __("Gridsquare"); ?></label>
							<input class="my-1 me-sm-2 w-auto form-control form-control-sm uppercase"  id="skedgrid" type="text" name="skedgrid" value=""/>
					</div>
				</div>
				</form>
				</div>
		<button id="plot" type="button" name="searchpass" class="btn-sm btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="searchpasses()"><i class="fas fa-search"></i> <?= __("Load predictions"); ?><div class="ld ld-ring ld-spin"></div></button>
		<button id="addsked" type="button" name="addsked" class="btn-sm btn btn-success me-1" onclick="addskedpartner()" disabled><i class="fa fa-plus"></i> <?= __("Add sked partner"); ?></button>
		<?php } else { ?>
			<?= __("No TLE information detected. Please update TLE's.")?>
		<?php } ?>
	</div>
    <div id="resultpasses">

    </div>
</div>
</div>
</div>
