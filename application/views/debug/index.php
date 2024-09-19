<div class="container debug_main mb-4">
    <br>
    <?php if ($this->session->flashdata('success')) { ?>
        <!-- Display Message -->
        <div class="alert alert-success">
            <p><?php echo $this->session->flashdata('success'); ?></p>
        </div>
    <?php } ?>

    <?php if ($this->session->flashdata('error')) { ?>
        <!-- Display Message -->
        <div class="alert alert-danger">
            <p><?php echo $this->session->flashdata('error'); ?></p>
        </div>
    <?php } ?>

    <h2><?php echo $page_title; ?></h2>

    <div class="row">
        <div class="col">

            <div class="card">
                <div class="card-header"><?= __("Wavelog Information"); ?></div>
                <div class="card-body">
                    <table width="100%">
                        <tr>
                            <td><?= __("Version"); ?></td>
                            <td><?php echo $running_version; ?>
                                <?php if ($running_version == $latest_release) { ?>
                                    <span class="badge text-bg-success"> <?= __("Latest Version"); ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php if ($newer_version_available) { ?>
                        <tr>
                            <td><?= __("Latest Release"); ?></td>
                            <td><a href="https://github.com/wavelog/wavelog/releases/tag/<?php echo $latest_release; ?>" target="_blank"><?php echo $latest_release."\n"; ?></a></td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td><?= __("Language"); ?></td>
                            <td><?php echo __(ucfirst($this->config->item('language'))) . "\n"; ?></td>
                        </tr>
                        <tr>
                            <td><?= __("Base URL"); ?></td>
                            <td><span id="baseUrl"><a href="<?php echo $this->config->item('base_url') ?>" target="_blank"><?php echo $this->config->item('base_url'); ?></a></span> <span data-bs-toggle="tooltip" title="<?= __("Copy to clipboard"); ?>" onclick='copyURL("<?php echo $this->config->item('base_url'); ?>")'><i class="copy-icon fas fa-copy"></span></td>
                        </tr>
                        <tr>
                            <td><?= __("Migration"); ?></td>
                            <td><?php echo (isset($migration_version) ? $migration_version : "<span class='badge text-bg-danger'>". __("There is something wrong with your Migration in Database!") . "</span>"); ?></td>
                        </tr>
                        <?php if (!$migration_is_uptodate) { ?>
                        </table>
                            <div class="alert alert-danger mt-3 mb-3">
                                <h5><?= __("Migration is outdated and locked!"); ?></h5>
                                <p><?= sprintf(__("The current migration is not the version it is supposed to be. Reload this page after %s seconds. If this warning persists, your migration is likely locked due to a previously failed process. Delete the file %s to force the migration to run again."), $miglock_lifetime, $migration_lockfile); ?></p>
                                <p><?= sprintf(__("Check this wiki article %shere%s for more information."), '<u><a href="https://github.com/wavelog/wavelog/wiki/Migration-is-locked" target="_blank">', '</a></u>'); ?></p>
                                <p><?= sprintf(__("Current migration is %s"), $migration_version); ?><br>
                                    <?= sprintf(__("Migration should be %s"), $migration_config); ?></p>
                            </div>
                        <table>
                        <?php } ?>
                        <tr>
                            <td><?= __("Environment"); ?></td>
                            <td><?php echo ENVIRONMENT; ?></td>
                        </tr>
                        <tr class="blank-row">
                            <td> </td>
                            <td> </td>
                        </tr>
                        <tr>
                            <td><?= __("Total QSO on this instance"); ?></td>
                            <td><?php echo number_format($qso_total, 0, '.', ',') . ' QSOs'; ?></td>
                        </tr>
                        <tr>
                            <td><?= __("Total User"); ?></td>
                            <td><?php echo number_format($users_total, 0, '.', ',') . ' ' . _ngettext("User", "Users", intval($users_total)); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><?= __("Server Information"); ?></div>
                <div class="card-body">
                    <table width="100%">
                        <tr>
                            <td><?= __("Server Software"); ?></td>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                        </tr>

                        <tr>
                            <td><?= __("PHP Version"); ?></td>
                            <td>
                              <?php
                                 if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                                    echo phpversion()." <span class=\"badge text-bg-success\">OK</span>";
                                 } else {
                                    echo phpversion()." <span data-bs-toggle=\"tooltip\" title=\"Please update!\" class=\"badge text-bg-warning\">".__("Deprecated")."</span>";
                                 }
                              ?>
                           </td>
                        </tr>

                        <tr>
                            <td><?= __("MySQL Version"); ?></td>
                            <td><?php echo $this->db->version(); ?></td>
                        </tr>
                        <tr>
                            <td><?= __("Codeigniter Version"); ?></td>
                            <td><?php echo CI_VERSION; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><?= __("Folder Permissions"); ?></div>
                <div class="card-body">
                    <p><?= __("This verifies that the folders used by Wavelog have read and write permissions by PHP."); ?></p>
                    <table width="100%">
                        <tr>
                            <td>/backup</td>
                            <td>
                                <?php if ($backup_folder == true) { ?>
                                    <span class="badge text-bg-success"><?= __("Success"); ?></span>
                                <?php } else { ?>
                                    <span class="badge text-bg-danger"><?= __("Failed"); ?></span>
                                <?php } ?>
                            </td>
                        </tr>

                        <tr>
                            <td>/cache</td>
                            <td>
                                <?php if ($cache_folder == true) { ?>
                                    <span class="badge text-bg-success"><?= __("Success"); ?></span>
                                <?php } else { ?>
                                    <span class="badge text-bg-danger"><?= __("Failed"); ?></span>
                                <?php } ?>
                            </td>
                        </tr>

                        <tr>
                            <td>/updates</td>
                            <td>
                                <?php if ($updates_folder == true) { ?>
                                    <span class="badge text-bg-success"><?= __("Success"); ?></span>
                                <?php } else { ?>
                                    <span class="badge text-bg-danger"><?= __("Failed"); ?></span>
                                <?php } ?>
                            </td>
                        </tr>

                        <tr>
                            <td>/uploads</td>
                            <td>
                                <?php if ($uploads_folder == true) { ?>
                                    <span class="badge text-bg-success"><?= __("Success"); ?></span>
                                <?php } else { ?>
                                    <span class="badge text-bg-danger"><?= __("Failed"); ?></span>
                                <?php } ?>
                            </td>
                        </tr>

                        <?php if (isset($userdata_enabled)) { ?>
                            <tr>
                                <td>/userdata</td>
                                <td>
                                    <?php if ($userdata_folder == true) { ?>
                                        <span class="badge text-bg-success"><?= __("Success"); ?></span>
                                    <?php } else { ?>
                                        <span class="badge text-bg-danger"><?= __("Failed"); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <?= __("Config Maintenance"); ?>
                </div>
                <div class="card-body">
                    <?php if ($this->config->item('auth_mode') != '3') { ?>
                        <div class="alert alert-primary">
                            <div class="alert alert-danger" role="alert">
                                <span class="badge rounded-pill text-bg-warning">auth_mode: <?= __("Warning"); ?></span> <?= __("Your authentication mode is outdated and possibly unsafe"); ?>
                            </div>
                            <p><?= sprintf(__("Please edit your %s File:"),"<code>application/config/config.php</code>"); ?></br>
                                <?= __("Go to your application/config Folder and compare config.sample.php with your config.php"); ?></br></br>
                                <?= sprintf(__("Change %s to the value %s (Strongly recommended)"),"<span class=\"badge rounded-pill text-bg-secondary\">\$config['auth_mode']</span>","<span class=\"badge rounded-pill text-bg-secondary\">3</span>"); ?>
                            </p>
                        </div>
                    <?php
                    } else { ?>
                        <div class="mb-2">
                            <span class="badge rounded-pill text-bg-success">auth_mode: <?= __("Ok"); ?></span> <?= __("Authentication Mode is set correctly"); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->config->item('encryption_key') == 'flossie1234555541') { ?>
                        <div class="alert alert-primary">
                            <div class="alert alert-danger" role="alert">
                                <span class="badge rounded-pill text-bg-warning">encryption_key: <?= __("Warning"); ?></span> <?= __("You use the default encryption key. You should change it!"); ?>
                            </div>
                            <p><?= sprintf(__("Please edit your %s File:"),"<code>application/config/config.php</code>"); ?></br>
                                <?= __("This will also enable the 'Keep me logged in' feature.");?></br>
                                <?= sprintf(__("Change the value of %s to a new encryption key other then 'flossie1234555541'. Choose a safe and long password. (Strongly recommended)"),"<span class=\"badge rounded-pill text-bg-secondary\">\$config['encryption_key']</span>"); ?>
                            </p>
                        </div>
                    <?php
                    } else { ?>
                        <div class="mb-2">
                            <span class="badge rounded-pill text-bg-success">encryption_key: <?= __("Ok"); ?></span> <?= __("You do not use the default encryption key"); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php if (isset($userdata_enabled)) { ?>
                <div class="card">
                    <div class="card-header"><?= __("Migrate Userdata"); ?></div>
                    <div class="card-body">
                        <p><?= __("Here you can migrate existing QSL cards and eQSL cards to the new userdata folder."); ?></p>
                        <a href="<?php echo site_url('debug/migrate_userdata'); ?>" class="btn btn-primary <?php echo $userdata_status['btn_class']; ?>"><?php echo $userdata_status['btn_text']; ?></a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="col">
            <div class="card">
                <div class="card-header">PHP</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col border-end">
                            <p><u><?= __("Modules"); ?></u></p>
                            <table width="100%">
                                <tr>
                                    <td>php-curl</td>
                                    <td>
                                        <?php if (in_array('curl', get_loaded_extensions())) { ?>
                                            <span class="badge text-bg-success"><?= __("Installed"); ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("Not Installed"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>php-mysql</td>
                                    <td>
                                        <?php if (in_array('mysqli', get_loaded_extensions())) { ?>
                                            <span class="badge text-bg-success"><?= __("Installed"); ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("Not Installed"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>php-mbstring</td>
                                    <td>
                                        <?php if (in_array('mbstring', get_loaded_extensions())) { ?>
                                            <span class="badge text-bg-success"><?= __("Installed"); ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("Not Installed"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>php-xml</td>
                                    <td>
                                        <?php if (in_array('xml', get_loaded_extensions())) { ?>
                                            <span class="badge text-bg-success"><?= __("Installed"); ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("Not Installed"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>php-zip</td>
                                    <td>
                                        <?php if (in_array('zip', get_loaded_extensions())) { ?>
                                            <span class="badge text-bg-success"><?= __("Installed"); ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("Not Installed"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col">
                            <p><u><?= __("Settings"); ?></u></p>
                            <?php
                            $max_execution_time = 600;        // Seconds
                            $upload_max_filesize = 8;      // Megabyte
                            $post_max_size = 8;                // Megabyte
                            $memory_limit = 256;            // Megabyte
                            $req_allow_url_fopen = '1';        // 1 = on
                            ?>
                            <table width="100%">
                                <tr>
                                    <td>max_execution_time</td>
                                    <td><?php echo '> ' . $max_execution_time . ' s'; ?></td>
                                    <td>
                                        <?php
                                        $maxExecutionTime = ini_get('max_execution_time');
                                        if ($maxExecutionTime >= $max_execution_time) { ?>
                                            <span class="badge text-bg-success"><?php echo $maxExecutionTime . ' s'; ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-warning"><?php echo $maxExecutionTime; ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>upload_max_filesize</td>
                                    <td><?php echo '> ' . $upload_max_filesize . 'M'; ?></td>
                                    <td>
                                        <?php
                                        $maxUploadFileSize = ini_get('upload_max_filesize');
                                        $maxUploadFileSizeBytes = (int)($maxUploadFileSize) * (1024 * 1024); // convert to bytes
                                        if ($maxUploadFileSizeBytes >= ($upload_max_filesize * 1024 * 1024)) { // compare with given value in bytes
                                        ?>
                                            <span class="badge text-bg-success"><?php echo $maxUploadFileSize; ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-warning"><?php echo $maxUploadFileSize; ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>post_max_size</td>
                                    <td><?php echo '> ' . $post_max_size . 'M'; ?></td>
                                    <td>
                                        <?php
                                        $postMaxSize = ini_get('post_max_size');
                                        $postMaxSizeBytes = (int)($postMaxSize) * (1024 * 1024); // convert to bytes
                                        if ($postMaxSizeBytes >= ($post_max_size * 1024 * 1024)) { // compare with given value in bytes
                                        ?>
                                            <span class="badge text-bg-success"><?php echo $postMaxSize; ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-warning"><?php echo $postMaxSize; ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>memory_limit</td>
                                    <td><?php echo '> ' . $memory_limit . 'M'; ?></td>
                                    <td>
                                        <?php
                                        $memoryLimit = ini_get('memory_limit');
                                        $memoryLimitBytes = (int)($memoryLimit) * (1024 * 1024); // convert to bytes
                                        if ($memoryLimitBytes >= ($memory_limit * 1024 * 1024)) { // compare with given value in bytes
                                        ?>
                                            <span class="badge text-bg-success"><?php echo $memoryLimit; ?></span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-warning"><?php echo $memoryLimit; ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>allow_url_fopen</td>
                                    <td>On</td>
                                    <td>
                                        <?php
                                        $get_allow_url_fopen = ini_get('allow_url_fopen');
                                        if ($get_allow_url_fopen == $req_allow_url_fopen) {
                                        ?>
                                            <span class="badge text-bg-success">On</span>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger">Off</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (file_exists(realpath(APPPATH . '../') . '/.git') && function_usable('exec')) { ?>
                <?php
                //Below is a failsafe where git commands fail
                try {
                    $commitHash = trim(exec('git log --pretty="%H" -n1 HEAD'));    // Get last LOCAL commit of HEAD
                    $branch = '';
                    $remote = '';
                    $owner = '';
                    // only proceed here if git can actually be executed
                    if ($commitHash != "") {
                        $commitDate = trim(exec('git log --pretty="%ci" -n1 HEAD'));
                        $line = trim(exec('git log -n 1 --pretty=%D HEAD'));
                        $pieces = explode(', ', $line);
                        $lastFetch = trim(exec('stat -c %Y ' . realpath(APPPATH . '../') . '/.git/FETCH_HEAD'));
                        //Below is a failsafe for systems without the stat command
                        try {
                            $dt = new DateTime("@$lastFetch");
                        } catch (Exception $e) {
                            $dt = new DateTime(date("Y-m-d H:i:s"));
                        }
                        if (isset($pieces[1])) {
                            $remote = substr($pieces[1], 0, strpos($pieces[1], '/'));
                            $branch = trim(exec('git rev-parse --abbrev-ref HEAD')); // Get ONLY Name of the Branch we're on
                            $url = trim(exec('git remote get-url ' . $remote));
                            if (strpos($url, 'https://github.com') !== false) {
                                $owner = preg_replace('/https:\/\/github\.com\/(\w+)\/[w|W]avelog\.git/', '$1', $url);
                            } else if (strpos($url, 'git@github.com') !== false) {
                                $owner = preg_replace('/git@github\.com:(\w+)\/[w|W]avelog\.git/', '$1', $url);
                            }
                        }
                        $tag = trim(exec('git describe --tags ' . $commitHash));
                    }
                } catch (\Throwable $th) {
                    $commitHash = "";
                }
                ?>

                <?php if ($commitHash != "") { ?>
                    <div class="card">
                        <div class="card-header"><?= __("Git Information"); ?></div>
                        <div class="card-body">
                            <table width="100%">
                                <tr>
                                    <td><?= __("Branch"); ?></td>
                                    <td>
                                        <?php if ($branch != "") { ?>
                                            <?php if ($owner != "") { ?>
                                                <a target="_blank" href="https://github.com/<?php echo $owner; ?>/Wavelog/tree/<?php echo $branch ?>">
                                                <?php } ?>
                                                <span class="badge text-bg-success"><?php echo $branch; ?></span>
                                                <?php if ($owner != "") { ?>
                                                </a>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("n/a"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                <tr>
                                    <td><?= __("Commit"); ?></td>
                                    <td>
                                        <?php if ($commitHash != "") { ?>
                                            <a target="_blank" href="https://github.com/<?php echo $owner; ?>/Wavelog/commit/<?php echo $commitHash ?>"><span class="badge text-bg-success"><?php echo substr($commitHash, 0, 8); ?></span></a>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("n/a"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= __("Tag"); ?></td>
                                    <td>
                                        <?php if ($commitHash != "") { ?>
                                            <a target="_blank" href="https://github.com/wavelog/wavelog/releases/tag/<?php echo substr($tag, 0, strpos($tag, '-')); ?>"><span class="badge text-bg-success"><?php echo $tag; ?></span></a>
                                        <?php } else { ?>
                                            <span class="badge text-bg-danger"><?= __("n/a"); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= __("Last Fetch"); ?></td>
                                    <td>
                                        <?php echo ($dt == null ? '' : $dt->format(\DateTime::RFC850)); ?>
                                    </td>
                                </tr>
                            </table>
                            <div class="border-bottom border-top pt-2 pb-2 mt-2 mb-2" id="version_check">
                                <p id="version_check_result"></p>
                                <small id="last_version_check"></small>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <button class="btn btn-primary me-3 ld-ext-right" onClick="update_version_check('<?php echo $branch; ?>');" id="version_check_button"><?= __("Check for new version"); ?><div class="ld ld-ring ld-spin"></div></button>
                                    <a class="btn btn-primary" style="display: none;" id="version_update_button" href="debug/selfupdate" onClick='this.classList.add("disabled");'><?= __("Update now"); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php }
            }

            // Get Date format
            if ($this->session->userdata('user_date_format')) {
                // If Logged in and session exists
                $custom_date_format = $this->session->userdata('user_date_format');
            } else {
                // Get Default date format from /config/wavelog.php
                $custom_date_format = $this->config->item('qso_date_format');
            }
            ?>
            <div class="card">
                <div class="card-header"><?= __("File download date"); ?></div>
                <div class="card-body">
                    <table width="100%" class="table-sm table table-hover table-striped">
                        <thead>
                            <th><?= __("File"); ?></th>
                            <th><?= __("Last update"); ?></th>
                            <th></th>
                        </thead>
                        <tr>
                            <td><?= __("DXCC update from Club Log"); ?></td>
                            <td><?php echo $dxcc_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update'); ?>"><?= __("Update"); ?></a></td>

                        </tr>
                        <tr>
                            <td><?= __("DOK file download"); ?></td>
                            <td><?php echo $dok_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/update_dok'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                        <tr>
                            <td><?= __("LoTW users download"); ?></td>
                            <td><?php echo $lotw_user_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/lotw_users'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                        <tr>
                            <td><?= __("POTA file download"); ?></td>
                            <td><?php echo $pota_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/update_pota'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                        <tr>
                            <td><?= __("SCP file download"); ?></td>
                            <td><?php echo $scp_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/update_clublog_scp'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                        <tr>
                            <td><?= __("SOTA file download"); ?></td>
                            <td><?php echo $sota_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/update_sota'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                        <tr>
                            <td><?= __("WWFF file download"); ?></td>
                            <td><?php echo $wwff_update->last_run ?? __("never"); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="<?php echo site_url('update/update_wwff'); ?>"><?= __("Update"); ?></a></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="card" style="margin-bottom: 15px;">
                <div class="card-header">
                    <?= __("QSO-DB Maintenance"); ?>
                </div>
                <?php if (!empty($qsos_with_no_station_id)) { ?>
                    <div class="alert alert-danger" role="alert" style="margin-bottom: 0px !important;">
                        <span class="badge rounded-pill text-bg-warning"><?= __("Warning"); ?></span> <?= sprintf(_ngettext("The Database contains %d QSO without a station-profile (location)", "The Database contains %d QSOs without a station-profile (location)", intval(count($qsos_with_no_station_id))), intval(count($qsos_with_no_station_id))); ?><br />
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="unasigned_qsos_table" class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><input type="checkbox" onClick="toggleAll(this)"></th>
                                        <th scope="col"><?= __("Date"); ?></th>
                                        <th scope="col"><?= __("Time"); ?></th>
                                        <th scope="col"><?= __("Call"); ?></th>
                                        <th scope="col"><?= __("Mode"); ?></th>
                                        <th scope="col"><?= __("Band"); ?></th>
                                        <th scope="col"><?= __("Station Callsign"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($this->session->userdata('user_date_format')) {
                                        $custom_date_format = $this->session->userdata('user_date_format');
                                    } else {
                                        $custom_date_format = 'd.m.Y';
                                    }
                                    foreach ($qsos_with_no_station_id as $qso) {
                                        echo '<tr>';
                                        echo '<td><input type="checkbox" id="' . $qso->COL_PRIMARY_KEY . '" name="cBox[]" value="' . $qso->COL_PRIMARY_KEY . '"></td>';
                                        $timestamp = strtotime($qso->COL_TIME_ON);
                                        echo '<td>' . date($custom_date_format, $timestamp) . '</td>';
                                        $timestamp = strtotime($qso->COL_TIME_ON);
                                        echo '<td>' . date('H:i', $timestamp) . '</td>';
                                        echo '<td>' . $qso->COL_CALL . '</td>';
                                        echo '<td>' . $qso->COL_MODE . '</td>';
                                        echo '<td>' . $qso->COL_BAND . '</td>';
                                        echo '<td>' . $qso->COL_STATION_CALLSIGN . '</td>';
                                        echo '</tr>';
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="card-text"><?= __("Please mark QSOs and reassign them to an existing station location:"); ?></p>


                        <div class="table-responsive">
                            <table id="station_locations_table" class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= __("Call"); ?></th>
                                        <th scope="col"><?= _pgettext("Stationlocation", "Target Location"); ?></th>
                                        <th scope="col"><?= __("Reassign"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($calls_wo_sid as $call) {
                                        echo '<tr><td><div id="station_call">' . $call['COL_STATION_CALLSIGN'] . '</div></td><td><select name="station_profile" id="station_profile" onChange="updateCallsign(this)">';
                                        $options = '';
                                        foreach ($stations->result() as $station) {
                                            $options .= '<option value=' . $station->station_id . '>' . $station->station_profile_name . ' (' . $station->station_callsign . ')</option>';
                                        }
                                        echo $options . '</select></td><td><button class="btn btn-warning" onClick="reassign(\'' . $call['COL_STATION_CALLSIGN'] . '\',$(\'#station_profile option:selected\').val());"><i class="fas fa-sync"></i> ' . __("Reassign") . '</a></button></td></tr>';
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php
                } else { ?>
                    <div class="alert alert-secondary" role="alert" style="margin-bottom: 0px !important;">
                        <span class="badge rounded-pill text-bg-success"><?= __("Everything ok"); ?></span> <?= __("Every QSO in your Database is assigned to a station-profile (location)"); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</div>

<script>
    <?php if (file_exists(realpath(APPPATH . '../') . '/.git')) { ?>
        var local_branch = '<?php echo $branch; ?>';
    <?php } else { ?>
        var local_branch = 'n/a';
    <?php } ?>
</script>

<?php
/**
 * Hidden field to be able to translate the language names
 * Add english Language Name here if you add new languages to application/config/gettext.php
 * This helps the po scanner to make them translatable
 */
?>
<div style="display: none">
    <?= __("Albanian"); ?>
    <?= __("Bosnian"); ?>
    <?= __("Bulgarian"); ?>
    <?= __("Chinese (Simplified)"); ?>
    <?= __("Croatian"); ?>
    <?= __("Czech"); ?>
    <?= __("Dutch"); ?>
    <?= __("English"); ?>
    <?= __("Finnish"); ?>
    <?= __("French"); ?>
    <?= __("German"); ?>
    <?= __("Greek"); ?>
    <?= __("Italian"); ?>
    <?= __("Montenegrin"); ?>
    <?= __("Polish"); ?>
    <?= __("Portuguese"); ?>
    <?= __("Russian"); ?>
    <?= __("Serbian"); ?>
    <?= __("Spanish"); ?>
    <?= __("Swedish"); ?>
    <?= __("Turkish"); ?>
</div>
