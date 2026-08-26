<div class="container dcl">
	<br>
	<h2><?= __("DCL Import"); ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Import Options"); ?>
		</div>

		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php if (isset($error)) { ?>
				<div class="alert alert-danger" role="alert">
					<?php echo $error; ?>
				</div>
			<?php } ?>

			<?php if (isset($dcl_result)) { ?>
				<div class="alert alert-info" role="alert">
					<?php echo nl2br(html_escape($dcl_result)); ?>
				</div>
			<?php } ?>

			<?php if (isset($dcl_details) && !empty($dcl_details)) { ?>
				<div class="table-responsive">
					<table class="table table-sm table-striped">
						<thead>
							<tr>
								<th><?= __("Date"); ?></th>
								<th><?= __("Time"); ?></th>
								<th><?= __("Call"); ?></th>
								<th><?= __("Band"); ?></th>
								<th><?= __("Mode"); ?></th>
								<th><?= __("DOK in DCL"); ?></th>
								<th><?= __("QSL Date"); ?></th>
								<th><?= __("Status"); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($dcl_details as $dcl_qso) { ?>
								<tr<?= $dcl_qso['matched'] ? '' : ' class="table-danger"' ?>>
									<td><?= html_escape(date($date_format ?? 'd/m/Y', strtotime($dcl_qso['date']))); ?></td>
									<td><?= html_escape($dcl_qso['time']); ?></td>
									<td class="callsign"><?= html_escape($dcl_qso['call']); ?></td>
									<td><?= html_escape($dcl_qso['band']); ?></td>
									<td><?= html_escape($dcl_qso['mode'] ?? ''); ?></td>
									<td><?= html_escape($dcl_qso['dok']); ?></td>
									<td><?= html_escape($dcl_qso['qsl_date']); ?></td>
									<td><?= $dcl_qso['matched'] ? __("Confirmed") : __("QSO could not be matched"); ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			<?php } ?>

			<?php echo form_open_multipart('dcl/import'); ?>

			<div class="form-check">
				<input type="radio" id="dclimport_upload" name="dclimport" class="form-check-input" value="upload"<?php if ($this->config->item('disable_manual_dcl') ?? false) { echo ' checked="checked"'; } ?>>
				<label class="form-check-label" for="dclimport_upload"><?= __("Upload a File"); ?></label>
				<br><br>
				<p class="card-text"><?= sprintf(__("Go to %s and export your logbook with confirmed DOKs. To speed up the process you can select only DL QSOs to download (i.e. put 'DL' into Prefix List). The downloaded ADIF file can be uploaded here in order to update QSOs with DOK info."), "<a href='https://dcl.darc.de/dml/export_adif_form.php' target='_blank'>" . __("DARC DCL") . "</a>") ?> <?= sprintf(__("More information regarding the confirmation status in DCL can be found on the %sDCL Confluence page%s."), '<a target="_blank" href="https://confluence.darc.de/pages/viewpage.action?pageId=21037270">', '</a>'); ?></p>
				<p><span class="badge text-bg-info"><?= __("Important"); ?></span> <?= __("Log files must have the file type .adi"); ?></p>

				<div class="mb-3 row">
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="onlyConfirmed" value="1" id="onlyConfirmed" checked>
							<label class="form-check-label" for="onlyConfirmed"><?= __("Only import DOK data from QSOs confirmed on DCL.") ?></label>
						</div>
						<div class="small form-text text-muted"><?= __("Uncheck if you also want to update DOK with data from unconfirmed QSOs in DCL.") ?></div>
					</div>
				</div>
				<div class="mb-3 row">
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="overwriteDok" value="1" id="overwriteDok">
							<label class="form-check-label" for="overwriteDok"><span class="badge text-bg-warning"><?= __("Warning") ?></span> <?= __("Overwrites exisiting DOK in log by DCL (if different).") ?></label>
						</div>
						<div class="small form-text text-muted"><?= __("If checked Wavelog will forcibly overwrite existing DOK with DOK from DCL log.") ?></div>
					</div>
				</div>
				<div class="mb-3 row">
					<div class="col-md-10">
						<div class="form-check-inline">
							<input class="form-check-input" type="checkbox" name="ignoreAmbiguous" value="1" id="ignoreAmbiguous" checked>
							<label class="form-check-label" for="ignoreAmbiguous"><?= __("Ignore QSOs that cannot be matched.") ?></label>
						</div>
						<div class="small form-text text-muted"><?= __("If unchecked, information about QSOs which could not be found in Wavelog will be displayed.") ?></div>
					</div>
				</div>

				<label class="visually-hidden" for="adiffile"><?= __("Choose file"); ?></label>
				<input type="file" class="file-input mb-2 me-sm-2" id="adiffile" name="userfile" size="20" accept=".adi,.ADI,.adif,.ADIF" />
			</div>

			<br><br>

			<?php if (!($this->config->item('disable_manual_dcl') ?? false)) { ?>
			<div>
				<div class="form-check">
					<input type="radio" name="dclimport" id="fetch" class="form-check-input" value="fetch" checked="checked" />
					<label class="form-check-label" for="fetch"><?= __("Pull DCL data for me"); ?></label>
					<br><br>
					<p class="card-text"><?= __("From date"); ?>:</p>
					<div class="row">
						<div class="col-md-3">
							<input type="date" name="from" id="from" class="form-control w-auto" value="<?php echo html_escape(set_value('from')); ?>" />
						</div>
					</div>
					<br />
					<p class="card-text"><?= __("Wavelog will use the DCL key stored in your profile to download confirmations from DCL for you. All confirmations since the chosen date, or since your last DCL confirmation (fetched from your log), up until now, will be downloaded and marked as confirmed."); ?></p>
				</div>
			</div>
			<?php } ?>

			<input class="btn btn-primary" type="submit" value="<?= __("Import DCL Matches"); ?>" />

			</form>
		</div>
	</div>

</div>
