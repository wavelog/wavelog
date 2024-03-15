
					<p><?php echo lang('station_logbooks_public_slug_hint'); ?></p>
					<p><?php echo lang('station_logbooks_public_slug_format1')?><br>
					<?php echo site_url('visitor'); ?>/<?php echo lang('station_logbooks_public_slug_format2'); ?></p>
					<form hx-post="<?php echo site_url('logbooks/save_publicslug/'); ?>" hx-target="#publicSlugForm" style="display: inline;">
					<div id="publicSlugForm">
					</div>
					<div class="mb-3">
						<input type="hidden" name="logbook_id" id="logbook_id" value="<?php echo $station_logbook_details->logbook_id; ?>">
						<label for="publicSlugInput"><?php echo lang('station_logbooks_public_slug_input'); ?></label>
						<input class="form-control" name="public_slug" id="publicSlugInput" pattern="[a-zA-Z0-9-]+" value="<?php echo $station_logbook_details->public_slug; ?>" required>
					</div>
					</form>

					<?php if($station_logbook_details->public_slug != "") { ?>
					<div id="slugLink" class="alert alert-info" role="alert" style="margin-top: 20px;">
						<p><?php echo lang('station_logbooks_public_slug_visit') . " "; ?></p>
						<p><a href="<?php echo site_url('visitor'); ?>/<?php echo $station_logbook_details->public_slug; ?>" target="_blank"><?php echo site_url('visitor'); ?>/<?php echo $station_logbook_details->public_slug; ?></a></p>
					</div>
					<?php } ?>

