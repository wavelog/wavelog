<?php
	// Get Date format
	if($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}
?>
<div class="container-fluid" id="missingGridDialogContent"
	data-msg-selected="<?= __("%s QSO(s) selected") ?>"
	data-msg-remaining="<?= __("%s QSO(s) remaining") ?>"
	data-msg-stat-updated="<?= __("Updated: %s") ?>"
	data-msg-stat-notfound="<?= __("Not found: %s") ?>"
	data-msg-stat-error="<?= __("Errors: %s") ?>"
	data-msg-running="<?= __("Retrieving callbook data...") ?>"
	data-msg-notfound="<?= __("Not found in callbook") ?>"
	data-msg-skipped="<?= __("Skipped") ?>"
	data-msg-error="<?= __("Error") ?>"
	data-msg-finished="<?= __("Lookup finished: %s gridsquare(s) set, %s not found, %s error(s).") ?>"
	data-msg-cancelled="<?= __("Lookup cancelled.") ?>"
>
    <?php if (!empty($qsos) && count($qsos) > 0): ?>
		<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
			<div>
				<span class="fw-bold" id="missingGridCounter"></span>
				<span class="ms-2" id="missingGridCounterStats"></span>
			</div>
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" role="switch" id="missingGridUnconfirmedOnly" />
				<label class="form-check-label" for="missingGridUnconfirmedOnly"><?= __("Only unconfirmed QSOs") ?></label>
			</div>
		</div>
		<p class="text-muted" id="missingGridStatus">
			<?php echo sprintf(__("Found %s QSO(s) with missing gridsquare."), count($qsos)); ?>
			<?php echo __("Uncheck the QSOs you want to skip, then start the lookup."); ?>
		</p>
		<div class="table-responsive" style="max-height:50vh; overflow:auto;">
            <table class="table table-sm table-striped table-hover" id="missingGridTable">
                <thead>
                    <tr>
						<th><div class="form-check"><input class="form-check-input mt-0" type="checkbox" id="checkBoxAllMissingGrids" checked /></div></th>
                        <th><?= __("Call") ?></th>
                        <th><?= __("Date/Time") ?></th>
                        <th><?= __("Mode") ?></th>
                        <th><?= __("Band") ?></th>
                        <th><?= __("Station") ?></th>
                        <th><?= __("Confirmed") ?></th>
                        <th><?= __("Result") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qsos as $qso): ?>
						<?php
							// Compact QSL markers
							$qsl = '';
							if (($qso->col_qsl_rcvd ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" title="' . __("QSL card") . '">Q</span> '; }
							if (($qso->col_lotw_qsl_rcvd ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" title="' . __("LoTW") . '">L</span> '; }
							if (($qso->col_eqsl_qsl_rcvd ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" title="' . __("eQSL") . '">E</span> '; }
							$confirmed = ($qsl !== '');
						?>
                        <tr id="qsoID-<?php echo $qso->col_primary_key; ?>"<?php echo $confirmed ? ' data-confirmed="1"' : ''; ?>>
							<td><div class="form-check"><input class="row-check form-check-input mt-0" type="checkbox" checked /></div></td>
                            <td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . (int) $qso->col_primary_key . ')">' . htmlspecialchars($qso->col_call) . '</a>'; ?></td>
							<td><?php $timestamp = strtotime($qso->col_time_on); echo date($custom_date_format . ' H:i', $timestamp); ?></td>
                            <td><?php echo html_escape($qso->col_submode ? $qso->col_submode : $qso->col_mode); ?></td>
                            <td><?php echo html_escape($qso->col_band); ?></td>
                            <td><?php echo html_escape($qso->station_profile_name); ?></td>
							<td class="text-center"><?php echo $confirmed ? $qsl : '<span class="text-muted">—</span>'; ?></td>
							<td class="lookupResult"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
		</div>
    <?php else: ?>
        <div class="alert alert-success">
            <h4><?= __("No Issues Found") ?></h4>
        </div>
    <?php endif; ?>
</div>
