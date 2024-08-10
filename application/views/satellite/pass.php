<div class="container container-fluid">
<h2><?= __("Satellite passes"); ?></h2>
<div class="card">
	<div class="card-body">
		<form class="d-flex align-items-center">
		<div class="row">
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" id="satslabel" for="satslist"><?= __("Min. Satellite Elevation"); ?></label>
				<input class="my-1 me-sm-2 w-auto form-control" id="minelevation" type="number" name="minelevation" value="0" />
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="minazimuth"><?= __("Min. Azimut"); ?></label>
                <select class="my-1 me-sm-2 w-auto form-select" id="minazimuth" name="minazimuth">
				<?php for ($i = 0; $i <= 350; $i += 10): ?>
					<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
				<?php endfor; ?>
			</select>
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="maxazimuth"><?= __("Max. Azimut"); ?></label>
				<select class="my-1 me-sm-2 w-auto form-select" id="maxazimuth" name="maxazimuth">
					<?php for ($i = 10; $i <= 360; $i += 10): ?>
						<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
					<?php endfor; ?>
				</select>
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="yourgrid"><?= __("Gridsquare"); ?></label>
                    <input class="my-1 me-sm-2 w-auto form-control"  id="yourgrid" type="text" name="gridsquare" value="<?php echo $activegrid; ?>"/>
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="altitude"><?= __("Altitude (meters)"); ?></label>
                    <input class="my-1 me-sm-2 w-auto form-control"  id="altitude" type="number" name="altitude" value="0" />
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="timezone"><?= __("Timezone"); ?></label>
					<select class="my-1 me-sm-2 w-auto form-select" id="timezone" name="timezone">
                    <?php foreach($timezones as $timezone) {
                        echo '<option value="' . $timezone->offset . '"' . ($timezone->id == 24 ? ' selected' : '') . '>' . $timezone->name . '</option>'."\n";
                    } ?>
					</select>
					</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="date"><?= __("Date"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="date" name="start">
							<option selected value="0"><?= __("Today"); ?></option>
						</select>
					</div>
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="mintime"><?= __("Min. time"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="mintime" name="mintime">
                        <?php for ($i = 0; $i <= 24; $i += 1): ?>
                            <option value="<?= $i ?>" <?= $i === 8 ? 'selected' : '' ?>><?= $i ?>:00</option>
                        <?php endfor; ?>
						</select>
				</div>
				<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="maxtime"><?= __("Max. time"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="maxtime" name="maxtime">
                        <?php for ($i = 0; $i <= 24; $i += 1): ?>
                            <option value="<?= $i ?>" <?= $i === 22 ? 'selected' : '' ?>><?= $i ?>:00</option>
                        <?php endfor; ?>
						</select>
                </div>
                <div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" id="satslabel" for="satlist"><?= __("Satellites"); ?></label>
					<select class="my-1 me-sm-2 w-auto form-select"  id="satlist">
						<?php foreach($satellites as $sat) {
							echo '<option value="' . $sat->satname . '"' . '>' . $sat->satname . '</option>'."\n";
						} ?>
					</select>
				</div>
        </form>
				</div>
		<button id="plot" type="button" name="searchpass" class="btn-sm btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="searchpasses()"><?= __("Load predictions"); ?><div class="ld ld-ring ld-spin"></div></button>
	</div>
    <div id="resultpasses">

    </div>
</div>
</div>
</div>
