<div class="container mb-4 mt-2">
    <br>
    <h2><?php echo $page_title; ?></h2>
    <div class="card">
        <div class="card-header">
            How it works
        </div>
        <div class="card-body">
            <p class="card-text">
                The Cron Manager helps the admin to manage cronjobs without the need of an CLI Access.
            </p>
            <p class="card-text">
                To run cronjobs based on the data below remove all old cronjobs and create a new cronjob:
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
                <table style="width:100%" class="crontable table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Function</th>
                            <th>Minute</th>
                            <th>Hour</th>
                            <th>Day (Month)</th>
                            <th>Month</th>
                            <th>Day (Week)</th>
                            <th>Last Run</th>
                            <th>Next Run</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($crons as $cron) { ?>
                            <tr>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->id; ?>'><?php echo $cron->id; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->name; ?>'><?php echo $cron->name; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->function; ?>'><?php echo $cron->function; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->minute; ?>'><?php echo $cron->minute; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->hour; ?>'><?php echo $cron->hour; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->day_month; ?>'><?php echo $cron->day_month; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->month; ?>'><?php echo $cron->month; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->day_week; ?>'><?php echo $cron->day_week; ?></td>
                                <td style="text-align: center; vertical-align: middle;" class='band_<?php echo $cron->last_run; ?>'><?php echo $cron->last_run; ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
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