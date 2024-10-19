<div class="container eqsl">
	<h2><?php echo $page_title; ?></h2>
	<div class="card">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs">
				<li class="nav-item">
					<a class="nav-link active" href="<?php echo site_url('eqsl/import'); ?>"><?= __("Download QSOs"); ?></a>
				</li>
				<?php if (!($this->config->item('disable_manual_eqsl'))) { ?>
					<li class="nav-item">
						<a class="nav-link" href="<?php echo site_url('eqsl/Export'); ?>"><?= __("Upload QSOs"); ?></a>
					</li>
				<?php } ?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo site_url('eqsl/tools'); ?>"><?= __("Tools"); ?></a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo site_url('eqsl/download'); ?>"><?= __("Download eQSL cards"); ?></a>
				</li>
			</ul>
		</div>
		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php echo form_open_multipart('eqsl/import'); ?>

			<div class="form-check">
            			<?php if (($next_run ?? '') != '') { echo "<p>".__("The next automatic sync with eQSL will happen at: ").$next_run."</p>"; } ?>
				<input class="form-check-input" type="radio" name="eqslimport" id="upload" value="upload" checked />
				<label class="form-check-label" for="exampleRadios1">
					<?= __("Import from file..."); ?>
				</label>
				<br>
				<p><?= sprintf(__("Upload the Exported ADIF file from eQSL from the %s page, to mark QSOs as confirmed on eQSL."), '<a href="https://eqsl.cc/qslcard/DownloadInBox.cfm" target="_blank">' . __("Download Inbox") . '</a>'); ?></p>
				<p><?= __("Choose Station(location) eQSL File belongs to:"); ?></p>
				<select name="station_profile" class="form-select mb-2 me-sm-2 w-50 w-lg-100">
					<option value="0"><?= __("Select Station Location"); ?></option>
					<?php foreach ($station_profile->result() as $station) {
						if ($station->eqslqthnickname) { ?>
							<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $this->stations->find_active()) {
																					echo " selected =\"selected\"";
																				} ?>><?= __("Callsign"); ?>: <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name . ") eQSL: " . $station->eqslqthnickname; ?></option>
					<?php }
					} ?>
				</select>
				<p><span class="badge bg-info me-1"><?= __("Important"); ?></span><?= __("Log files must have the file type .adi"); ?></p>
				<input class="form-control mb-2 me-sm-2 mt-1 w-50 w-lg-100" type="file" name="userfile" size="20" />
			</div>
			<hr class="divider">
			<?php if (!($this->config->item('disable_manual_eqsl'))) { ?>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="eqslimport" id="fetch" value="fetch" checked="checked" />
					<label class="form-check-label" for="exampleRadios1"><?= __("Import directly from eQSL"); ?></label>
					<p><?= __("Wavelog will use the eQSL credentials from your Wavelog user profile to connect to eQSL and download confirmations."); ?></p>
					<div class="row">
						<div class="mb-3 col-sm-2">
							<div class="dxatlasdatepicker input-group date" id="eqsl_force_from_date" data-target-input="nearest">
								<input name="eqsl_force_from_date" id="eqsl_force_from_date" type="date" class="form-control w-auto">
							</div>
						</div>
						<div class="mb-3 col-sm-5" style="vertical-align:middle;"><label class="form-label"><?php echo "(Select a date, only if you want to force an import with an older date)"; ?></label></div>
					</div>
				</div>
				<hr class="divider">
			<?php } ?>
			<div class="mb-3"><input class="btn btn-primary" type="submit" value="Import eQSL QSO Matches" /></div>
			</form>
		</div>
	</div>

</div>
