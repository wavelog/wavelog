
					<p><?= __("Setting a public slug allows you to share your logbook with anyone via a custom website address, this slug can contain letters & numbers only."); ?></p>
					<p><?= __("Later it looks like this:")?><br>
					<?php echo site_url('visitor'); ?>/<?= __("[your slug]"); ?></p>
					<form style="display: inline;">
					<div id="visitorLinkInfo">
					</div>
					<div class="mb-3">
						<input type="hidden" name="logbook_id" id="logbook_id" value="<?php echo $station_logbook_details->logbook_id; ?>">
						<label for="publicSlugInput"><?= __("Type in Public Slug choice"); ?></label>
						<input class="form-control" name="public_slug" id="publicSlugInput" pattern="[a-zA-Z0-9-]+" value="<?php echo $station_logbook_details->public_slug; ?>" required>
					</div>
					</form>

					<?php if($station_logbook_details->public_slug != "") { ?>
					<div id="slugLink" class="alert alert-info" role="alert" style="margin-top: 20px;">
						<p><?= __("Visit Public Page") . " "; ?></p>
						<p><a href="<?php echo site_url('visitor'); ?>/<?php echo $station_logbook_details->public_slug; ?>" target="_blank"><?php echo site_url('visitor'); ?>/<?php echo $station_logbook_details->public_slug; ?></a></p>
					</div>
					<?php } ?>

