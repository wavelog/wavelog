<div class="container mb-4 mt-2">
    <br>
    <h2><?php echo $page_title; ?></h2>

    <div style="display: none;" id="cron_message_area" role="alert"></div>

    <div class="card">
        <div class="card-header">
            <?= __("How it works"); ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-auto">
                    <p class="card-text">
                        <?= __("The Cron Manager assists the administrator in managing cron jobs without requiring CLI access."); ?>
                    </p>
                    <?php if ($mastercron['status_class'] != 'success') { ?>
                        <p class="card-text">
                            <?= __("To execute cron jobs based on the data below, remove all old cron jobs and create a new one:"); ?>
                        </p>
                        <div class="main_cronjob">
                            <pre><code id="main_cronjob">* * * * * curl --silent <?php if ($cron_allow_insecure) { echo '--insecure '; } echo base_url(); ?>index.php/cron/run &>/dev/null</code><span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onclick='copyCron("main_cronjob")'><i class="copy-icon fas fa-copy"></i></span></pre>
                        </div>
                    <?php } ?>
                </div>
                <div class="col text-end" id="alert_status">
                    <?php if (version_compare(PHP_VERSION, $min_php_version) >= 0) { ?>
                        <div class="alert alert-<?php echo $mastercron['status_class'] ?? 'danger'; ?> d-inline-block">
                            <?= __("Status Master-Cron:"); ?><br><?php echo $mastercron['status'] ?? _pgettext("Master Cron", "Not running"); ?>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-danger d-inline-block">
                            <?= __("Status Master-Cron:"); ?><br><?= __("PHP Version not supported."); ?><br><?= _pgettext("PHP Version", "Min. Version is"); ?> <?php echo $min_php_version; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?= __("Cron List"); ?>
        </div>
        <div class="card-body">
            <?php if (version_compare(PHP_VERSION, $min_php_version) >= 0) { ?>
                <?php if ($mastercron['status_class'] != 'danger') { ?>
                    <div class="table-responsive">
                        <table id="cron_table" style="width:100%" class="crontable table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th><?= __("ID"); ?></th>
                                    <th><?= __("Description"); ?></th>
                                    <th><?= __("Status"); ?></th>
                                    <th><?= __("Intervall"); ?></th>
                                    <th><?= __("Last Run"); ?></th>
                                    <th><?= __("Next Run"); ?></th>
                                    <th><?= __("Edit"); ?></th>
                                    <th>I/O</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($crons as $cron) { ?>
                                    <tr>
                                        <td style="vertical-align: middle;" class='cron_<?php echo $cron->id; ?>'><?php echo $cron->id; ?></td>
                                        <td style="vertical-align: middle;"><?php echo $cron->description; ?></td>
                                        <td style="vertical-align: middle;"><?php
                                                                            if ($cron->enabled == '1') {
                                                                                if ($cron->status == 'healthy') { ?>
                                                    <span class="badge text-bg-success"><?= __("healthy"); ?></span>
                                                <?php } else if ($cron->status == 'failed') { ?>
                                                    <span class="badge text-bg-danger"><?= __("failed"); ?></span>
                                                <?php } else { ?>
                                                    <span class="badge text-bg-warning"><?php echo $cron->status; ?></span>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <span class="badge text-bg-secondary"><?= __("disabled"); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td style="vertical-align: middle;"><?php echo '<code id="humanreadable_tooltip" data-bs-toggle="tooltip">' . $cron->expression . '</code>'; ?></td>
                                        <td style="vertical-align: middle;"><?php echo $cron->last_run ?? __("never"); ?></td>
                                        <td style="vertical-align: middle;"><?php if ($cron->enabled == '1') {
                                                                                echo $cron->next_run ?? __("never");
                                                                            } else {
                                                                                echo __("never");
                                                                            } ?></td>
                                        <td style="vertical-align: middle;"><button id="<?php echo $cron->id; ?>" class="editCron btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></button></td>
                                        <td style="vertical-align: middle;">
                                            <div class="form-check form-switch"><input name="cron_enable_switch" class="form-check-input enableCronSwitch" type="checkbox" role="switch" id="<?php echo $cron->id; ?>" <?php if ($cron->enabled ?? '0') {
                                                                                                                                                                                                                            echo 'checked';
                                                                                                                                                                                                                        } ?>></div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="text-center">
                        <h4><?= __("Your Mastercron isn't running."); ?><br>
                        <?= __("Copy the cron above to a external cron service or into your server's cron to use this cron manager."); ?></h4>
                        <p><?= __("On a basic linux server with shell access use this command to edit your crons:"); ?>
                        <pre><code>crontab -e</code></pre>
                        </p>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="alert alert-danger" role="alert">
                    <?= sprintf(__("You need to upgrade your PHP version. Minimum version is %s. Your Version is %s"), $min_php_version, PHP_VERSION);?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>