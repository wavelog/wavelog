<div class="tabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="logbook-tab" data-bs-toggle="tab" href="#logbooktab" role="tab" aria-controls="home" aria-selected="true"><?= __("Logbook"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="map-tab" data-bs-toggle="tab" href="#maptab" role="tab" aria-controls="map" aria-selected="false"><?= __("Map"); ?></a>
			</li>
		</ul>
	</div>
<div class="tab-content" id="myTabContent">
	<div class="tab-pane fade show active" id="logbooktab" role="tabpanel" aria-labelledby="logbook-tab">
	<div class="card border-dark mt-2">
		<div class="card-header">
			<?= __("Basic QSO Information"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="datetime" type="checkbox" id="datetime" <?php if (($options->datetime->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="datetime"><?= __("Date/Time"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="de" type="checkbox" id="de" <?php if (($options->de->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="de"><?= __("De"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="dx" type="checkbox" id="dx" <?php if (($options->dx->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="dx"><?= __("Dx"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="mode" type="checkbox" id="mode" <?php if (($options->mode->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="mode"><?= __("Mode"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="rsts" type="checkbox" id="rsts" <?php if (($options->rsts->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="rsts"><?= __("RST (S)"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="rstr" type="checkbox" id="rstr" <?php if (($options->rstr->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="rstr"><?= __("RST (R)"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="band" type="checkbox" id="band" <?php if (($options->band->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="band"><?= __("Band"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="frequency" type="checkbox" id="frequency" <?php if (($options->frequency->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="frequency"><?= __("Frequency"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Station Details"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="gridsquare" type="checkbox" id="gridsquare" <?php if (($options->gridsquare->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="gridsquare"><?= __("Gridsquare"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="name" type="checkbox" id="name" <?php if (($options->name->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="name"><?= __("Name"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qth" type="checkbox" id="qth" <?php if (($options->qth->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qth"><?= __("QTH"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qslvia" type="checkbox" id="qslvia" <?php if (($options->qslvia->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qslvia"><?= __("QSL via"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Confirmation Services"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="clublog" type="checkbox" id="clublog" <?php if (($options->clublog->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qsl" type="checkbox" id="qsl" <?php if (($options->qsl->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="eqsl" type="checkbox" id="eqsl" <?php if (($options->eqsl->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="lotw" type="checkbox" id="lotw" <?php if (($options->lotw->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qrz" type="checkbox" id="qrz" <?php if (($options->qrz->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qrz"><?= __("QRZ"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="dcl" type="checkbox" id="dcl" <?php if (($options->dcl->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="dcl"><?= __("DCL"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qslmsgs" type="checkbox" id="qslmsgs" <?php if (($options->qslmsgs->show ?? "false") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qslmsgs"><?= __("QSL Msg (S)"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="qslmsgr" type="checkbox" id="qslmsgr" <?php if (($options->qslmsgr->show ?? "false") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="qslmsgr"><?= __("QSL Msg (R)"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Geographic Information"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="dxcc" type="checkbox" id="dxcc" <?php if (($options->dxcc->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="dxcc"><?= __("DXCC"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="state" type="checkbox" id="state" <?php if (($options->state->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="state"><?= __("State"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="county" type="checkbox" id="county" <?php if (($options->county->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="county"><?= __("County"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="cqzone" type="checkbox" id="cqzone" <?php if (($options->cqzone->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="cqzone"><?= __("CQ Zone"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="ituzone" type="checkbox" id="ituzone" <?php if (($options->ituzone->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="ituzone"><?= __("ITU Zone"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="continent" type="checkbox" id="continent" <?php if (($options->continent->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="continent"><?= __("Continent"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Awards Programs"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="iota" type="checkbox" id="iota" <?php if (($options->iota->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="iota"><?= __("IOTA"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="pota" type="checkbox" id="pota" <?php if (($options->pota->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="pota"><?= __("POTA"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="sota" type="checkbox" id="sota" <?php if (($options->sota->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="sota"><?= __("SOTA"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="dok" type="checkbox" id="dok" <?php if (($options->dok->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="dok"><?= __("DOK"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="wwff" type="checkbox" id="wwff" <?php if (($options->wwff->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="wwff"><?= __("WWFF"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="sig" type="checkbox" id="sig" <?php if (($options->sig->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="sig"><?= __("SIG"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="region" type="checkbox" id="region" <?php if (($options->region->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="region"><?= __("Region"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Additional Information"); ?>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="operator" type="checkbox" id="operator" <?php if (($options->operator->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="operator"><?= __("Operator"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="comment" type="checkbox" id="comment" <?php if (($options->comment->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="comment"><?= __("Comment"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="propagation" type="checkbox" id="propagation" <?php if (($options->propagation->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="propagation"><?= __("Propagation"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="contest" type="checkbox" id="contest" <?php if (($options->contest->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="contest"><?= __("Contest"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="myrefs" type="checkbox" id="myrefs" <?php if (($options->myrefs->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="myrefs"><?= __("My Refs"); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card border-dark">
		<div class="card-header">
			<?= __("Technical Details"); ?>
		</div>
		<div class="card-body">
			<div class="row">

				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="distance" type="checkbox" id="distanceoption" <?php if (($options->distance->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="distance"><?= __("Distance"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="antennaazimuth" type="checkbox" id="antennaazimuth" <?php if (($options->antennaazimuth->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="antennaazimuth"><?= __("Antenna azimuth"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="antennaelevation" type="checkbox" id="antennaelevation" <?php if (($options->antennaelevation->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="antennaelevation"><?= __("Antenna elevation"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="profilename" type="checkbox" id="profilename" <?php if (($options->profilename->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="profilename"><?= __("Profile name"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="stationpower" type="checkbox" id="stationpower" <?php if (($options->stationpower->show ?? "true") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="stationpower"><?= __("Station power"); ?></label>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="form-check">
						<input class="form-check-input" name="last_modification" type="checkbox" id="last_modification" <?php if (($options->last_modification->show ?? "false") == "true") { echo 'checked'; } ?>>
						<label class="form-check-label" for="last_modification">
							<span title="<?= __("This is meant for debugging purposes only and not designed to be displayed by default"); ?>"><?= __("Last modified"); ?> <small id="debug_last_modified" class="badge text-bg-danger"><?= __("For debugging only"); ?></small></span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="tab-pane fade show" id="maptab" role="tabpanel" aria-labelledby="map-tab">
	<div class="card">
		<div class="card-header">
			<?= __("Map Layers"); ?>
		</div>
		<div class="card-body border-dark mt-2">
				<div class="row">
					<div class="col-md-6 col-lg-4">
						<div class="form-check">
							<input class="form-check-input" name="pathlines" type="checkbox" id="pathlines" <?php if (($mapoptions['path_lines']->option_value ?? "false") == "true") { echo 'checked'; } ?>>
							<label class="form-check-label" for="pathlines">Path lines</label>
						</div>
					</div>
					<div class="col-md-6 col-lg-4">
						<div class="form-check">
							<input class="form-check-input" name="gridsquareoverlay" type="checkbox" id="gridsquareoverlay" <?php if (($mapoptions['gridsquare_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>>
							<label class="form-check-label" for="gridsquareoverlay">Gridsquares</label>
						</div>
					</div>
					<div class="col-md-6 col-lg-4">
						<div class="form-check">
							<input class="form-check-input" name="cqzones" type="checkbox" id="cqzones" <?php if (($mapoptions['cqzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>>
							<label class="form-check-label" for="cqzones">CQ Zones</label>
						</div>
					</div>
					<div class="col-md-6 col-lg-4">
						<div class="form-check">
							<input class="form-check-input" name="ituzones" type="checkbox" id="ituzones" <?php if (($mapoptions['ituzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>>
							<label class="form-check-label" for="ituzones">ITU Zones</label>
						</div>
					</div>
					<div class="col-md-6 col-lg-4">
						<div class="form-check">
							<input class="form-check-input" name="nightshadow" type="checkbox" id="nightshadow" <?php if (($mapoptions['nightshadow_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>>
							<label class="form-check-label" for="nightshadow">Night Shadow</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
