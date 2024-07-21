<!DOCTYPE html>
<html>

<?php include 'includes/interface_assets/header.php'; ?>

<body>
    <div class="container" style="max-width: 1200px; margin-top: 8rem; ">
        <div class="card mt-4" style="min-height: 750px; margin: 0 auto;">
            <div class="card-body text-center">
                <h3 style="margin-top: 150px;"><?= __("Installation"); ?></h3>

                <p style="margin-bottom: 60px;"><?= __("Please wait..."); ?></p>

                <div class="mb-3" id="config_file" style="opacity: 50%;">
                    <i id="config_file_spinner" class="ld-ext-right"><?= __("Copy config.php to application/config/") ?><div class="ld ld-ring ld-spin"></div></i><i id="config_file_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>
                <div class="mb-3" id="database_file" style="opacity: 50%;">
                    <i id="database_file_spinner" class="ld-ext-right"><?= __("Copy database.php to application/config/") ?><div class="ld ld-ring ld-spin"></div></i><i id="database_file_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>
                <div class="mb-3" id="database_tables" style="opacity: 50%;">
                    <i id="database_tables_spinner" class="ld-ext-right"><?= __("Creating database tables") ?><div class="ld ld-ring ld-spin"></div></i><i id="database_tables_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>
                <div class="mb-3" id="database_migrations" style="opacity: 50%;">
                    <i id="database_migrations_spinner" class="ld-ext-right"><?= __("Running database migrations") ?><div class="ld ld-ring ld-spin"></div></i><i id="database_migrations_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>
                <div class="mb-3" id="update_dxcc" style="opacity: 50%;">
                    <i id="update_dxcc_spinner" class="ld-ext-right"><?= __("Updating DXCC data") ?><i id="skip_dxcc_update_message"></i><div class="ld ld-ring ld-spin"></div></i><i id="update_dxcc_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>

                <?php
                // we can easily add more steps here if necessary 
                ?>

                <div class="mb-3" id="installer_lock" style="opacity: 50%;">
                    <i id="installer_lock_spinner" class="ld-ext-right"><?= __("Lock the installer") ?><div class="ld ld-ring ld-spin"></div></i><i id="installer_lock_check" class="ms-2 fas fa-check-circle" style="display: none;"></i>
                </div>

                <div class="mb-3" id="success_message" style="display: none;">
                    <p><?= sprintf(__("All install steps went through. Redirect to user login in %s seconds..."), "<span id='countdown'>4</span>"); ?></p>
                </div>
                <div id="error_message"></div>
            </div>
        </div>
    </div>
</body>

<script>

    let _POST = <?php echo json_encode($_POST); ?>;

    $(document).ready(async function() {
        try {
            await config_file();
            await database_file();
            await database_tables();
            await database_migrations();
            await update_dxcc();
            await installer_lock();

            // after all install steps went through we can show a success message and redirect to the user/login
            $("#success_message").show();

            // Initialize the countdown
            var countdown = 4;
            var countdownInterval = setInterval(function() {
                countdown--;
                $("#countdown").text(countdown);
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "/index.php/user/login/1";
                }
            }, 1000);
        } catch (error) {
            $("#error_message").text("Installation failed: " + error).show();
        }
    });

    async function config_file() {

        var field = '#config_file';

        running(field, true);

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: {
                    data: _POST,
                    run_config_file: 1
                },
                success: function(response) {
                    if (response == 'success') {
                        running(field, false);
                        resolve();
                    } else {
                        running(field, false, true);
                        reject("<?= __("Could not create application/config/config.php"); ?>");
                    }
                },
                error: function(error) {
                    running(field, false, true);
                    reject(error);
                }
            });
        });
    }

    async function database_file() {

        var field = '#database_file';

        running(field, true);

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: {
                    data: _POST,
                    run_database_file: 1
                },
                success: function(response) {
                    if (response == 'success') {
                        running(field, false);
                        resolve();
                    } else {
                        running(field, false, true);
                        reject("<?= __("Could not create application/config/database.php"); ?>");
                    }
                },
                error: function(error) {
                    running(field, false, true);
                    reject(error);
                }
            });
        });
    }

    async function database_tables() {
        var field = '#database_tables';

        running(field, true);

        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: {
                    data: _POST,
                    run_database_tables: 1
                },
                success: function(response) {
                    if (response == 'success') {
                        running(field, false);
                        resolve();
                    } else {
                        running(field, false, true);
                        reject("<?= __("Could not create database tables"); ?>");
                    }
                },
                error: function(error) {
                    running(field, false, true);
                    reject(error);
                }
            });
        });
    }

    async function database_migrations() {
        var field = '#database_migrations';

        running(field, true);

        return new Promise((resolve, reject) => {
            $.ajax({
                url: "<?php echo $_POST['websiteurl']; ?>" + "index.php/migrate",
                success: function(response) {
                    if (response == 'success') {
                        running(field, false);
                        resolve();
                    } else {
                        running(field, false, true);
                        reject("<?= __("Could not run database migrations"); ?>");
                    }
                },
                error: function(error) {
                    running(field, false, true);
                    reject(error);
                }
            });
        });
    }

    async function update_dxcc() {
        var field = '#update_dxcc';
        
        return new Promise((resolve, reject) => {
            if(_POST.skip_dxcc_update == 0) {

                running(field, true);

                $.ajax({
                    url: "<?php echo $_POST['websiteurl']; ?>" + "index.php/update/dxcc",
                    success: function(response) {
                        if (response == 'success') {
                            running(field, false);
                            resolve();
                        } else {
                            running(field, false, true);
                            reject("<?= __("Could not update DXCC data"); ?>");
                        }
                    },
                    error: function(error) {
                        running(field, false, true);
                        reject(error);
                    }
                });
            } else {
                $('#skip_dxcc_update_message').text(" "+"<?= __("(skipped)"); ?>");
                resolve();
            }
        });
    }

    async function installer_lock() {
        var field = '#installer_lock';

        running(field, true);
        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: {
                    run_installer_lock: 1
                },
                success: function(response) {
                    if (response == 'success') {
                        running(field, false);
                        resolve();
                    } else {
                        running(field, false, true);
                        reject("<?= __("Could not create install/.lock file"); ?>");
                    }
                },
                error: function(error) {
                    running(field, false, true);
                    reject(error);
                }
            });
        });
    }

    //

    function running(field, running, failure = false) {

        if (running) {
            $(field).css('opacity', '100%');
            $(field + '_spinner').addClass("running");
        } else {
            $(field + '_spinner').removeClass("running");
            if (failure) {
                $(field + '_check').addClass('fa-times-circle');
                $(field + '_check').css('color', 'red');
            } else {
                $(field + '_check').addClass('fa-check-circle');
                $(field + '_check').css('color', '#04a004');
            }
            $(field + '_check').css('display', 'inline');
        }

    }
</script>

<?php include 'includes/interface_assets/footer.php'; ?>

</html>