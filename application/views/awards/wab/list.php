<div class="container">
		<table style="width:100%" class="wabtable table-sm table table-bordered table-hover table-striped table-condensed text-center">
			<thead>
				<tr>
					<th scope="col"><?= __("DXCC"); ?></th>
					<th scope="col"><?= __("WAB Square"); ?></th>
					<th scope="col"><?= __("Gridsquare"); ?></th>
					<th scope="col"><?= __("Callsign"); ?></th>
					<th scope="col"><?= __("Date"); ?></th>
					<th scope="col"><?= __("Band"); ?></th>
					<th scope="col"><?= __("Mode"); ?></th>
					<th scope="col"><?= __("Confirmed"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($qsos as $qso) {
					$ts = strtotime($qso->col_time_on);
					$letters = '';
					if ($qso->col_qsl_rcvd == 'Y') { $letters .= 'Q'; }
					if ($qso->col_lotw_qsl_rcvd == 'Y') { $letters .= 'L'; }
					if ($qso->col_eqsl_qsl_rcvd == 'Y') { $letters .= 'E'; }
					if ($qso->qrz == 'Y') { $letters .= 'Z'; }
					if ($qso->clublog == 'Y') { $letters .= 'C'; }
				?>
				<tr>
					<td data-order="<?= html_escape($qso->dxcc); ?>"><?= $qso->dxcc != '' ? html_escape($dxcc_names[$qso->dxcc] ?? $qso->dxcc) . ' (' . html_escape($qso->dxcc) . ')' : '&mdash;'; ?></td>
					<td><?php echo '<a href=\'javascript:displayContacts(' . js_escape($qso->col_sig_info) . ',' . js_escape($postdata['band']) . ',' . js_escape($postdata['sat']) . ',' . js_escape($postdata['orbit']) . ',' . js_escape($postdata['mode']) . ',"WAB")\'>' . html_escape($qso->col_sig_info) . '</a>'; ?></td>
					<td><?= $qso->col_gridsquare != '' ? html_escape($qso->col_gridsquare) : '&mdash;'; ?></td>
					<td><a class="callsign" href="javascript:displayQso(<?= (int) $qso->col_primary_key; ?>)"><?= html_escape($qso->col_call); ?></a></td>
					<td data-order="<?= html_escape($qso->col_time_on); ?>"><?= html_escape(date($date_format, $ts) . ' ' . date('H:i', $ts)); ?></td>
					<td><?php if (($qso->col_sat_name ?? '') != '') { // sat QSOs: show the satellite instead of the bare 'SAT' band
						echo '<a href="https://db.satnogs.org/search/?q=' . html_escape($qso->col_sat_name) . '" target="_blank">' . html_escape($qso->col_sat_name) . '</a>';
					} else {
						echo html_escape($qso->col_band);
					} ?></td>
					<td><?= html_escape(strtoupper($qso->col_submode ?: $qso->col_mode)); ?></td>
					<td title="<?= __("Q = QSL card, L = LoTW, E = eQSL, Z = QRZ.com, C = Clublog"); ?>"><?php echo $letters != '' ? implode(' ', array_map(function ($l) { return '<span class="badge bg-success">' . html_escape($l) . '</span>'; }, str_split($letters))) : __("No"); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
