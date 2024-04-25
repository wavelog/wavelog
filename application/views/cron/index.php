<div class="container mb-4 mt-2">
    <br>
    <h2><?php echo $page_title; ?></h2>
    <div class="card">
        <div class="card-header">
            How it works
        </div>
        <div class="card-body">
            <p class="card-text">
                The Cron Manager assists the administrator in managing cron jobs without requiring CLI access.
            </p>
            <p class="card-text">
                To execute cron jobs based on the data below, remove all old cron jobs and create a new one:
            </p>
            <div class="main_cronjob">
                <pre><code id="main_cronjob">* * * * * curl --silent <?php echo base_url(); ?>index.php/cron/run &>/dev/null</code><span data-bs-toggle="tooltip" title="<?php echo lang('copy_to_clipboard'); ?>" onclick='copyCron("main_cronjob")'><i class="copy-icon fas fa-copy"></i></span></pre>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Cron List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="cron_table" style="width:100%" class="crontable table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Intervall</th>
                            <th>Last Run</th>
                            <th>Next Run</th>
                            <th>Edit</th>
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
                                            <span class="badge text-bg-success">healthy</span>
                                        <?php } else if ($cron->status == 'failed') { ?>
                                            <span class="badge text-bg-danger">failed</span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-warning"><?php echo $cron->status; ?></span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="badge text-bg-secondary">disabled</span>
                                    <?php } ?>
                                </td>
                                <td style="vertical-align: middle;"><?php echo '<code id="humanreadable_tooltip" data-bs-toggle="tooltip">' . $cron->expression . '</code>'; ?></td>
                                <td style="vertical-align: middle;"><?php echo $cron->last_run ?? 'never'; ?></td>
                                <td style="vertical-align: middle;"><?php if ($cron->enabled == '1') {
                                                                        echo $cron->next_run ?? 'never';
                                                                    } else {
                                                                        echo 'never';
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
                    <tfoot>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>