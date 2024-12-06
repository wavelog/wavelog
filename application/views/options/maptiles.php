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

                    <p class="alert alert-info">
                        <?= __("This modifies the map source in various locations within Wavelog. Do not change any values here unless you are confident in what you are doing."); ?>
                    </p>
                    <div class="mb-3">
                        <label for="maptile_server_url"><?= __("Maptiles Server URL"); ?></label>
                        <input type="text" name="maptile_server_url" class="form-control" id="maptile_server_url" aria-describedby="maptile_server_urlHelp" value="<?php echo $maptile_server_url; ?>">
                        <small id="maptile_server_urlHelp" class="form-text text-muted"><?= __("URL of the map server which serves the maptiles."); ?> <?= sprintf(__("For the %sStatic Map API%s this URL is only used when theme is set to %s. Default: %s"), '<a href="https://github.com/wavelog/wavelog/wiki/Static-Map-Images">', '</a>', "'light'", "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"); ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="maptile_server_url_dark"><?= __("Maptiles Server URL for Dark Tiles - ONLY Static Map API"); ?></label>
                        <input type="text" name="maptile_server_url_dark" class="form-control" id="maptile_server_url_dark" aria-describedby="maptile_server_url_darkHelp" value="<?php echo $maptile_server_url_dark; ?>">
                        <small id="maptile_server_url_darkHelp" class="form-text text-muted"><?= sprintf(__("URL of the map server which serves the dark maptiles. Only used for Static Map API. Default: %s"),"https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"); ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="subdomain_system"><?= __("Subdomain System of Maptile Server"); ?></label>
                        <input type="text" name="subdomain_system" class="form-control" id="subdomain_system" aria-describedby="subdomain_systemHelp" value="<?php echo $subdomain_system; ?>">
                        <small id="subdomain_systemHelp" class="form-text text-muted"><?= sprintf(__("System of the subdomains at this server ({s}). They are used for loadbalancing. Default: %s"), "abc"); ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="copyright_url"><?= __("URL of the Copyright Source"); ?></label>
                        <input type="text" name="copyright_url" class="form-control" id="copyright_url" aria-describedby="copyright_urlHelp" value="<?php echo $copyright_url; ?>">
                        <small id="copyright_urlHelp" class="form-text text-muted"><?= sprintf(__("Source URL for the copyright tag. Default: %s"), "https://www.openstreetmap.org/"); ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="copyright_text"><?= __("Name of the Copyright Source"); ?></label>
                        <input type="text" name="copyright_text" class="form-control" id="copyright_text" aria-describedby="copyright_textHelp" value="<?php echo $copyright_text; ?>">
                        <small id="copyright_textHelp" class="form-text text-muted"><?= sprintf(__("Text for the copyright tag. Default: %s"), "OpenStreetMap"); ?></small>
                    </div>

                    <!-- Save the Form -->
                    <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>