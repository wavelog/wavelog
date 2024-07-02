<div class="container settings mb-4">

	<div class="row">
		<!-- Nav Start -->
		<?php $this->load->view('options/sidebar') ?>
		<!-- Nav End -->

		<!-- Content -->
		<div class="col-md-9">
            <div class="card">
                <div class="card-header"><h2><?php echo $page_title; ?> - <?php echo $sub_heading; ?></h2></div>

                <div class="card-body">
                    <?php if($this->session->flashdata('success')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                        <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php } ?>

                    <?php if($this->session->flashdata('message')) { ?>
                        <!-- Display Message -->
                        <div class="alert alert-info">
                        <?php echo $this->session->flashdata('message'); ?>
                        </div>
                    <?php } ?>

                    <?php if($this->session->flashdata('testmailFailed')) { ?>
                        <!-- Display testmailFailed Message -->
                        <div class="alert alert-danger">
                            <?php echo $this->session->flashdata('testmailFailed'); ?>
                        </div>
                    <?php } ?>

                    <?php if($this->session->flashdata('testmailSuccess')) { ?>
                        <!-- Display testmailSuccess Message -->
                        <div class="alert alert-success">
                            <?php echo $this->session->flashdata('testmailSuccess'); ?>
                        </div>
                    <?php } ?>
                        
                    <?php echo form_open('options/email_save'); ?>

                        <div class="mb-3">
                            <label for="emailProtocol"><?= __("Outgoing Protocol"); ?></label>
                            <select name="emailProtocol" class="form-select" id="emailProtocol">
                                <option value="sendmail" <?php if($this->optionslib->get_option('emailProtocol')== "sendmail") { echo "selected=\"selected\""; } ?>>Sendmail</option>
                                <option value="smtp" <?php if($this->optionslib->get_option('emailProtocol')== "smtp") { echo "selected=\"selected\""; } ?>>SMTP</option>
                            </select>
                            <small class="form-text text-muted"><?= __("The protocol that will be used to send out emails."); ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="smtpEncryption"><?= __("SMTP Encryption"); ?></label>
                            <select name="smtpEncryption" class="form-select" id="smtpEncryption">
                                <option value="" <?php if($this->optionslib->get_option('smtpEncryption') == "") { echo "selected=\"selected\""; } ?>><?= __("No Encryption"); ?></option>
                                <option value="tls" <?php if($this->optionslib->get_option('smtpEncryption') == "tls") { echo "selected=\"selected\""; } ?>>TLS</option>
                                <option value="ssl" <?php if($this->optionslib->get_option('smtpEncryption') == "ssl") { echo "selected=\"selected\""; } ?>>SSL</option>
                            </select>
                            <small class="form-text text-muted"><?= __("Choose whether emails should be sent with TLS or SSL."); ?></small>
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
                            <label for="smtpUsername" class="col-sm-2 col-form-label"><?= __("SMTP Username"); ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="smtpUsername" class="form-control" id="smtpUsername" value="<?php if($this->optionslib->get_option('smtpUsername') != "") { echo $this->optionslib->get_option('smtpUsername'); } ?>">
                                <small class="form-text text-muted"><?= __("The username to log in to the mail server, usually this is the email address that is used."); ?></small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="smtpPassword" class="col-sm-2 col-form-label"><?= __("SMTP Password"); ?></label>
                            <div class="col-sm-10">
                                <input type="password" name="smtpPassword" class="form-control" id="smtpPassword"  value="<?php if($this->optionslib->get_option('smtpPassword') != "") { echo $this->optionslib->get_option('smtpPassword'); } ?>">
                                <small class="form-text text-muted"><?= __("The password to log in to the mail server."); ?></small>
                            </div>
                        </div>

                        <!-- Save the Form -->
                        <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                    </form>
                    <br>
                    <?php echo form_open('options/sendTestMail'); ?>
                        <input class="btn btn-primary" type="submit" value="<?= __("Send Test-Mail"); ?>" />
                        <small class="form-text text-muted"><?= __("The email will be sent to the address defined in your account settings."); ?></small>
                    </form>
                </div>
            </div>
		</div>
	</div>

</div>