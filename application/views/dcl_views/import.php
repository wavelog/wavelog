<div class="container dcl">
	<br>
	<h2><?= __("DCL Import"); ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Import Options"); ?>
		</div>

		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php if (isset($dcl_result)) { ?>
				<div class="alert alert-info" role="alert">
					<?php echo nl2br(html_escape($dcl_result)); ?>
				</div>
			<?php } ?>

			<?php if ($this->config->item('disable_manual_dcl') ?? false) { ?>
				<div class="alert alert-warning" role="alert">
					<?= __("Manual syncing is disabled by configuration"); ?>
				</div>
			<?php } else { ?>
				<?php echo form_open('dcl/import'); ?>
					<input type="hidden" name="dclimport" value="fetch" />

					<p class="card-text"><?= __("From date"); ?>:</p>
					<div class="row">
						<div class="col-md-3">
							<input type="date" name="from" id="from" class="form-control w-auto" value="<?php echo html_escape(set_value('from')); ?>" />
						</div>
					</div>
					<br />
					<input class="btn btn-primary" type="submit" value="<?= __("Import DCL Matches"); ?>" />
				</form>

				<br />
				<p class="card-text"><?= __("Wavelog will use the DCL key stored in your profile to download confirmations from DCL for you. All confirmations since the chosen date, or since your last DCL confirmation (fetched from your log), up until now, will be downloaded and marked as confirmed."); ?></p>
				<p class="card-text"><?= sprintf(__("To import a DCL file manually, use the %sDCL import%s at the ADIF Import page."), '<a href="'.site_url('adif/import').'">', '</a>'); ?></p>
			<?php } ?>
		</div>
	</div>

</div>
