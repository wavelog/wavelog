<div class="tabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="logbook-tab" data-bs-toggle="tab" href="#logbooktab" role="tab" aria-controls="home" aria-selected="true">Logbook</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="map-tab" data-bs-toggle="tab" href="#maptab" role="tab" aria-controls="map" aria-selected="false">Map</a>
			</li>
		</ul>
	</div>
<div class="tab-content" id="myTabContent">
	<div class="tab-pane fade show active" id="logbooktab" role="tabpanel" aria-labelledby="logbook-tab">
<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="useroptions">
	<thead>
		<tr>
			<th class="text-start"><?php echo lang('filter_options_column'); ?></th>
			<th><?php echo lang('filter_options_show'); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo lang('general_word_datetime'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="datetime" type="checkbox" <?php if (($options->datetime->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_de'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="de" type="checkbox" <?php if (($options->de->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_dx'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="dx" type="checkbox" <?php if (($options->dx->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_mode'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="mode" type="checkbox" <?php if (($options->mode->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_rsts'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="rsts" type="checkbox" <?php if (($options->rsts->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_rstr'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="rstr" type="checkbox" <?php if (($options->rstr->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_band'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="band" type="checkbox" <?php if (($options->band->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_myrefs'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="myrefs" type="checkbox" <?php if (($options->myrefs->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_refs'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="refs" type="checkbox" <?php if (($options->refs->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('general_word_name'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="name" type="checkbox" <?php if (($options->name->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_qslvia'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qslvia" type="checkbox" <?php if (($options->qslvia->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_qsl'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qsl" type="checkbox" <?php if (($options->qsl->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('lotw_short'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="lotw" type="checkbox" <?php if (($options->lotw->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('eqsl_short'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="eqsl" type="checkbox" <?php if (($options->eqsl->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_qslmsg'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="qslmsg" type="checkbox" <?php if (($options->qslmsg->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_dxcc'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="dxcc" type="checkbox" <?php if (($options->dxcc->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_state'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="state" type="checkbox" <?php if (($options->state->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_cq_zone'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="cqzone" type="checkbox" <?php if (($options->cqzone->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_itu_zone'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="ituzone" type="checkbox" <?php if (($options->ituzone->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_sota'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="sota" type="checkbox" <?php if (($options->sota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_iota'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="iota" type="checkbox" <?php if (($options->iota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_pota'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="pota" type="checkbox" <?php if (($options->pota->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('gen_hamradio_operator'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="operator" type="checkbox" <?php if (($options->operator->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('general_word_comment'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="comment" type="checkbox" <?php if (($options->comment->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td><?php echo lang('filter_general_propagation'); ?></td>
			<td><div class="form-check"><input class="form-check-input" name="propagation" type="checkbox" <?php if (($options->propagation->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Contest</td>
			<td><div class="form-check"><input class="form-check-input" name="contest" type="checkbox" <?php if (($options->contest->show ?? "true") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
	</tbody>
</table>
</div>
	<div class="tab-pane fade show" id="maptab" role="tabpanel" aria-labelledby="map-tab">
	<table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-start" id="mapoptions">
	<thead>
		<tr>
			<th class="text-start">Layer</th>
			<th>Default on</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Path lines</td>
			<td><div class="form-check"><input class="form-check-input" name="pathlines" type="checkbox" <?php if (($mapoptions['path_lines']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Gridsquares</td>
			<td><div class="form-check"><input class="form-check-input" name="gridsquareoverlay" type="checkbox" <?php if (($mapoptions['gridsquare_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>CQ Zones</td>
			<td><div class="form-check"><input class="form-check-input" name="cqzones" type="checkbox" <?php if (($mapoptions['cqzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>ITU Zones</td>
			<td><div class="form-check"><input class="form-check-input" name="ituzones" type="checkbox" <?php if (($mapoptions['ituzones_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
		<tr>
			<td>Night Shadow</td>
			<td><div class="form-check"><input class="form-check-input" name="nightshadow" type="checkbox" <?php if (($mapoptions['nightshadow_layer']->option_value ?? "false") == "true") { echo 'checked'; } ?>></div></td>
		</tr>
	</tbody>
	</table>
</div>
</div>
