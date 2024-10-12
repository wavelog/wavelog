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
<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="useroptions">
	<thead>
		<tr>
			<th class="text-start"><?= __("Column"); ?></th>
			<th><?= __("Show"); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?= __("Date/Time"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="datetime" type="checkbox" <?php if (($options->datetime->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("De"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="de" type="checkbox" <?php if (($options->de->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Dx"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="dx" type="checkbox" <?php if (($options->dx->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Mode"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="mode" type="checkbox" <?php if (($options->mode->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("RST (S)"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="rsts" type="checkbox" <?php if (($options->rsts->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("RST (R)"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="rstr" type="checkbox" <?php if (($options->rstr->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Band"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="band" type="checkbox" <?php if (($options->band->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("My Refs"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="myrefs" type="checkbox" <?php if (($options->myrefs->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Name"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="name" type="checkbox" <?php if (($options->name->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("QSL via"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qslvia" type="checkbox" <?php if (($options->qslvia->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("QSL"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qsl" type="checkbox" <?php if (($options->qsl->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("LoTW"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="lotw" type="checkbox" <?php if (($options->lotw->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("eQSL"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="eqsl" type="checkbox" <?php if (($options->eqsl->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Clublog"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="clublog" type="checkbox" <?php if (($options->clublog->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("QSL Msg"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qslmsg" type="checkbox" <?php if (($options->qslmsg->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("DXCC"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="dxcc" type="checkbox" <?php if (($options->dxcc->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("State"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="state" type="checkbox" <?php if (($options->state->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("CQ Zone"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="cqzone" type="checkbox" <?php if (($options->cqzone->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("ITU Zone"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="ituzone" type="checkbox" <?php if (($options->ituzone->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("SOTA"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="sota" type="checkbox" <?php if (($options->sota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("IOTA"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="iota" type="checkbox" <?php if (($options->iota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("POTA"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="pota" type="checkbox" <?php if (($options->pota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Operator"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="operator" type="checkbox" <?php if (($options->operator->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Comment"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="comment" type="checkbox" <?php if (($options->comment->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Propagation"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="propagation" type="checkbox" <?php if (($options->propagation->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Contest"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="contest" type="checkbox" <?php if (($options->contest->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Gridsquare"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="gridsquare" type="checkbox" <?php if (($options->gridsquare->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("DOK"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="dok" type="checkbox" <?php if (($options->dok->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("WWFF"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="wwff" type="checkbox" <?php if (($options->wwff->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("SIG"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="sig" type="checkbox" <?php if (($options->sig->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Continent"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="continent" type="checkbox" <?php if (($options->continent->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("QRZ"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qrz" type="checkbox" <?php if (($options->qrz->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= __("Profile name"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="profilename" type="checkbox" <?php if (($options->profilename->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
	</tbody>
</table>
</div>
	<div class="tab-pane fade show" id="maptab" role="tabpanel" aria-labelledby="map-tab">
	<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="mapoptions">
	<thead>
		<tr>
			<th class="text-start"><?= _pgettext("Map Options", "Layer"); ?></th>
			<th><?= __("Default on"); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?= _pgettext("Map Options", "Path lines"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="pathlines" type="checkbox" <?php if (($mapoptions['path_lines']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "Gridsquares"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="gridsquareoverlay" type="checkbox" <?php if (($mapoptions['gridsquare_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "CQ Zones"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="cqzones" type="checkbox" <?php if (($mapoptions['cqzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "ITU Zones"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="ituzones" type="checkbox" <?php if (($mapoptions['ituzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?= _pgettext("Map Options", "Night Shadow"); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="nightshadow" type="checkbox" <?php if (($mapoptions['nightshadow_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
	</tbody>
	</table>
</div>
</div>
