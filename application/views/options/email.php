<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= __("Wavelog Options"); ?></h2>
	<div class="card">
		<?php $this->load->view('options/tabs', ['active_tab' => $active_tab ?? '']); ?>
		<div class="card-body">
			<?php $this->load->view('layout/messages'); ?>

			<?php echo form_open('options/email_save', 'id="emailSettingsForm"'); ?>

				<div class="mb-3">
					<label for="emailProtocol"><?= __("Outgoing Protocol"); ?></label>
					<select name="emailProtocol" class="form-select" id="emailProtocol">
						<option value="smtp" <?php if($this->optionslib->get_option('emailProtocol')== "smtp") { echo "selected=\"selected\""; } ?>>SMTP</option>
						<option value="sendmail" <?php if($this->optionslib->get_option('emailProtocol')== "sendmail") { echo "selected=\"selected\""; } ?>><?= __("Local mailer (PHP mail)"); ?></option>
					</select>
					<small class="form-text text-muted"><?= __("The protocol that will be used to send out emails. The local mailer hands the message to PHP's mail() function and therefore needs a mail transfer agent installed on the host - the official Docker image does not ship one, so use SMTP there."); ?></small>
				</div>

				<div class="mb-3 row">
					<label for="smtpEncryption" class="col-sm-2 col-form-label"><?= __("SMTP Encryption"); ?></label>
					<div class="col-sm-10">
						<select name="smtpEncryption" class="form-select" id="smtpEncryption">
							<option value="" <?php if($this->optionslib->get_option('smtpEncryption') == "") { echo "selected=\"selected\""; } ?>><?= __("No Encryption"); ?></option>
							<option value="tls" <?php if($this->optionslib->get_option('smtpEncryption') == "tls") { echo "selected=\"selected\""; } ?>>TLS</option>
							<option value="ssl" <?php if($this->optionslib->get_option('smtpEncryption') == "ssl") { echo "selected=\"selected\""; } ?>>SSL</option>
						</select>
						<small class="form-text text-muted"><?= __("Choose whether emails should be sent with TLS or SSL."); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
				<label for="emailSenderName" class="col-sm-2 col-form-label"><?= __("Email Sender Name"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="emailSenderName" class="form-control" id="emailSenderName" value="<?php if($this->optionslib->get_option('emailSenderName') != "") { echo $this->optionslib->get_option('emailSenderName'); } ?>">
						<small class="form-text text-muted"><?= __("The email sender name, e.g. 'Wavelog'"); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
				<label for="emailAddress" class="col-sm-2 col-form-label"><?= __("Email Address"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="emailAddress" class="form-control" id="emailAddress" value="<?php if($this->optionslib->get_option('emailAddress') != "") { echo $this->optionslib->get_option('emailAddress'); } ?>">
						<small class="form-text text-muted"><?= __("The email address from which the emails are sent, e.g. 'wavelog@example.com'"); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
					<label for="smtpHost" class="col-sm-2 col-form-label"><?= __("SMTP Host"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="smtpHost" class="form-control" id="smtpHost" value="<?php if($this->optionslib->get_option('smtpHost') != "") { echo $this->optionslib->get_option('smtpHost'); } ?>">
						<small class="form-text text-muted"><?= __("The hostname of the mail server, e.g. 'mail.example.com' (without 'ssl://' or 'tls://')"); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
					<label for="smtpPort" class="col-sm-2 col-form-label"><?= __("SMTP Port"); ?></label>
					<div class="col-sm-10">
						<input type="number" name="smtpPort" class="form-control" id="smtpPort" value="<?php if($this->optionslib->get_option('smtpPort') != "") { echo $this->optionslib->get_option('smtpPort'); } ?>">
						<small class="form-text text-muted"><?= __("The SMTP port of the mail server, e.g. if TLS is used -> '587', if SSL is used -> '465'"); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
					<label for="smtpTimeout" class="col-sm-2 col-form-label"><?= __("SMTP Timeout"); ?></label>
					<div class="col-sm-10">
						<input type="number" name="smtpTimeout" class="form-control" id="smtpTimeout" min="5" max="120" value="<?php echo $this->optionslib->get_option('smtpTimeout') ?: 30; ?>">
						<small class="form-text text-muted"><?= __("How many seconds to wait for a reply from the mail server, between 5 and 120. Raise this if mails are delivered but reported as failed: some servers scan or greylist a message before they acknowledge it."); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
					<label for="smtpUsername" class="col-sm-2 col-form-label"><?= __("SMTP Username"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="smtpUsername" class="form-control" id="smtpUsername" value="<?php if($this->optionslib->get_option('smtpUsername') != "") { echo $this->optionslib->get_option('smtpUsername'); } ?>">
						<small class="form-text text-muted"><?= __("The username to log in to the mail server, usually this is the email address that is used."); ?></small>
					</div>
				</div>

				<div class="mb-3 row">
					<label for="smtpPassword" class="col-sm-2 col-form-label"><?= __("SMTP Password"); ?></label>
					<div class="col-sm-10">
						<div class="d-flex align-items-center gap-3">
							<input type="password" name="smtpPassword" class="form-control" id="smtpPassword" value="" placeholder="<?php if($this->optionslib->get_option('smtpPassword') != "") { echo __("Leave empty to keep current password"); } ?>">
							<?php if($this->optionslib->get_option('smtpPassword') != "") { ?>
								<div class="form-check mb-0 flex-shrink-0">
									<input type="checkbox" name="smtpPasswordClear" class="form-check-input" id="smtpPasswordClear" value="1">
									<label class="form-check-label text-nowrap" for="smtpPasswordClear"><?= __("Remove the stored password"); ?></label>
								</div>
							<?php } ?>
						</div>
						<small class="form-text text-muted"><?= __("The password to log in to the mail server."); ?></small>
					</div>
				</div>

				<!-- Save the Form -->
				<button class="btn btn-primary" type="submit" id="emailSettingsSave"><?= __("Save"); ?></button>
			</form>
			<br>
			<button class="btn btn-primary" type="button" id="sendTestMail"><?= __("Send Test-Mail"); ?></button>
			<small class="form-text text-muted"><?= __("The email will be sent to the address defined in your account settings."); ?></small>
			<pre id="testmailDetail" class="mt-3 p-2 border rounded d-none" style="white-space: pre-wrap;"></pre>
		</div>
	</div>
</div>
