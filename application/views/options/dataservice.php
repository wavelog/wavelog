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

                    <?php echo form_open('options/dataservice_save'); ?>

                    <div class="mb-3">
                        <p>
                            <?= __("Certain features in Wavelog rely on data that is too large and complex to be stored directly in Wavelog. To handle this, Wavelog integrates with the Wavelog Dataservice, a separate application that runs on a different server and provides data via an API."); ?>
                            <?= sprintf(__("While we recommend using the official %sWavelog Dataservice%s, you also have the option to self-host your own instance."), '<a href="' . $default_dataservice_url . '" target="_blank">', '</a>'); ?>
                            <?= __("If you choose to self-host, please enter the URL of your custom Dataservice below."); ?>
                            <?= sprintf(__("Instructions for self-hosting the Dataservice are available in the %sofficial repository%s."), '<a href="https://github.com/wavelog/wavelog-dataservice" target="_blank">', '</a>'); ?>
                        </p>
                        <p>
                            <?= __("The official Wavelog Dataservice also allows us to count active Wavelog installations. It's inspiring to see how many users are running Wavelog!"); ?>
                            <?= __("Rest assured, we do not store any personal data. The only information collected includes the Wavelog ID (an anonymous, non-traceable hash), the 4-character Maidenhead grid (e.g., HA44), and the Wavelog version. We do not collect IP addresses or any other data that could identify users."); ?>
                            <?= __("If you prefer not to participate, you can either self-host the Dataservice or disable it entirely. However, please note that disabling the Dataservice will impact some features, such as displaying the local time information of the QSO partner's location in the currently logged QSO."); ?>
                        </p>
                        <div class="mb-2">
                            <input type="checkbox" name="dataservice_enabled" class="form-check-input" id="dataservice_enabled" <?php if ($dataservice_enabled) { echo "checked"; } ?>>
                            <label class="ms-3 form-check-label" for="dataservice_enabled"><b><?= __("Dataservice enabled"); ?></b></label>
                        </div>
                        <div style="display: none;" id="dataservice_settings_div">
                            <div class="mb-3">
                                <input type="checkbox" name="dataservice_insecure" class="form-check-input" id="dataservice_insecure" <?php if ($dataservice_insecure) { echo "checked"; } ?>>
                                <label class="ms-3 form-check-label" for="dataservice_insecure"><?= __("Allow Insecure Connection"); ?></label>
                                <i class="ms-1 fas fa-question-circle" data-bs-toggle="insecureinfo" data-bs-placement="right" title="<?= __("Enabled this if you use self-signed certificates. Default: Off") ?>"></i>
                            </div>
                            <div class="mb-3">
                                <label for="dataservice_url"><?= __("Dataservice URL"); ?></label>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group">
                                            <input type="text" name="dataservice_url" class="form-control" id="dataservice_url" aria-describedby="dataservice_urlHelp" value="<?php echo $dataservice_url; ?>">
                                            <button class="btn btn-secondary w-25" type="button" id="dataservice_url_tester" onclick="test_dataservice($('#dataservice_url').val())"><?= __("Test"); ?></button>
                                        </div>
                                    </div>
                                    <div class="col d-flex align-items-center">
                                        <small class="text-muted d-flex align-items-center">
                                            <i id="ds_testresult"></i>
                                            <span class="ms-2" id="ds_testresult_text"></span>
                                        </small>
                                    </div>
                                </div>
                                <small id="dataservice_urlHelp" class="form-text text-muted"><?= sprintf(__("URL of the Dataservice Server, Default: %s"), $default_dataservice_url); ?></small>
                            </div>
                        </div>

                        <!-- Save the Form -->
                        <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>