<div class="container settings">

    <div class="row">
        <!-- Nav Start -->
        <?php $this->load->view('options/sidebar') ?>
        <!-- Nav End -->

        <!-- Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $page_title; ?> - <?php echo $sub_heading; ?></h2>
                </div>

                <div class="card-body">
                    <?php if ($this->session->flashdata('success')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                            <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->session->flashdata('warning')) { ?>
                        <!-- Display warning Message -->
                        <div class="alert alert-warning">
                            <?php echo $this->session->flashdata('warning'); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->session->flashdata('message')) { ?>
                        <!-- Display Message -->
                        <div class="alert-message error">
                            <?php echo $this->session->flashdata('message'); ?>
                        </div>
                    <?php } ?>

                    <?php if (validation_errors()) { ?>
                        <div class="alert alert-danger">
                            <a class="btn-close" data-bs-dismiss="alert"></a>
                            <?php echo validation_errors(); ?>
                        </div>
                    <?php } ?>

                    <?php echo form_open('options/maptiles_save'); ?>

                        <?php if (!$this->session->flashdata('warning')) { ?>
                            <p class="alert alert-danger">
                                <b><u><?= __("Important"); ?></u></b><br>
                                <?= sprintf(__("This modifies the map source in various locations within Wavelog. Do not change any values here unless you are confident in what you are doing. Test each change on your %sDashboard%s!"), '<a href="' . site_url('/') . '" target="_blank"><u>', '</u></a>'); ?><br>
                                <?= sprintf(__("You can find a list of tested settings and all default values in the %sWavelog Wiki%s."), '<a href="https://github.com/wavelog/wavelog/wiki/Maptile-Server" target="_blank"><u>', '</u></a>'); ?>
                            </p>
                        <?php } ?>
                        <div class="mb-3">
                            <label for="maptile_server_url"><?= __("Maptiles Server URL"); ?></label>
                            <input type="text" name="maptile_server_url" class="form-control" id="maptile_server_url" aria-describedby="maptile_server_urlHelp" value="<?php echo $maptile_server_url; ?>">
                            <small id="maptile_server_urlHelp" class="form-text text-muted"><?= __("URL of the map server which serves the maptiles."); ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="maptile_server_url_dark"><?= __("Maptiles Server URL for Dark Tiles - ONLY Static Map API"); ?></label>
                            <input type="text" name="maptile_server_url_dark" class="form-control" id="maptile_server_url_dark" aria-describedby="maptile_server_url_darkHelp" value="<?php echo $maptile_server_url_dark; ?>">
                            <small id="maptile_server_url_darkHelp" class="form-text text-muted"><?= __("URL of the map server which serves the dark maptiles. Only used for Static Map."); ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="subdomain_system"><?= __("Subdomain System of Maptile Server"); ?></label>
                            <input type="text" name="subdomain_system" class="form-control" id="subdomain_system" aria-describedby="subdomain_systemHelp" value="<?php echo $subdomain_system; ?>">
                            <small id="subdomain_systemHelp" class="form-text text-muted"><?= __("System of the subdomains at this server ({s} in the URL). They are used for loadbalancing."); ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="copyright_url"><?= __("URL of the Copyright Source"); ?></label>
                            <input type="text" name="copyright_url" class="form-control" id="copyright_url" aria-describedby="copyright_urlHelp" value="<?php echo $copyright_url; ?>">
                            <small id="copyright_urlHelp" class="form-text text-muted"><?= __("Source URL for the copyright tag."); ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="copyright_text"><?= __("Name of the Copyright Source"); ?></label>
                            <input type="text" name="copyright_text" class="form-control" id="copyright_text" aria-describedby="copyright_textHelp" value="<?php echo $copyright_text; ?>">
                            <small id="copyright_textHelp" class="form-text text-muted"><?= __("Text for the copyright tag."); ?></small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                            <button class="btn btn-secondary" type="submit" name="reset_defaults" value="1"><?= __("Reset to Defaults"); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>