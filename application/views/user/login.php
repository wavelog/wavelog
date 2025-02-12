<style>
    html,
    body {
        height: 100%;
    }

    body {
        display: flex;
        align-items: center;
        padding-top: 40px;
        padding-bottom: 40px;
    }

    .form-signin {
        width: 100%;
        max-width: 430px;
        padding: 15px;
        margin: auto;
    }

    .form-signin input[type="email"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    .form-signin input[type="password"] {
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
</style>
<main class="form-signin">
    <img src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('main_logo'); ?>.png" class="mx-auto d-block mainLogo" alt="">
    <?php if (ENVIRONMENT == 'maintenance') { ?>
        <div class="d-flex justify-content-center align-items-center">
            <span class="badge text-bg-warning mb-4 pt-2 pb-2"><?= __("MAINTENANCE MODE"); ?></span>
        </div>
    <?php } ?>
    <div class="my-2 rounded-0 shadow-sm card mb-2 shadow-sm">
        <div class="card-body">
            <?php 
            /**
             * Wavelog Demo
             * 
             * This enables the Wavelog Demo as used in https://demo.wavelog.org.
             * 
             * If you want to use this, place a file called `.demo` in the root folder 
             * and create a non-admin user `demo` with password `demo` by hand. 
             * 
             * It's recommend to create a cronjob which resets this installation every day at 0200 UTC from a backup.
             * We do not provide any functionality for this, so you have to build this on your own.
             * 
             */
            if (file_exists('.demo')) { ?>
                <div class="border-bottom mb-3">
                    <h5><?= __("Welcome to the Demo of Wavelog"); ?></h5>
                    <p><?= __("This demo will be reset every night at 0200z."); ?><br><br>
                    <?= __("Username"); ?>: demo<br>
                    <?= __("Password"); ?>: demo<br><br>
                    <?= sprintf(__("More Information about Wavelog on %sGithub%s."), '<a href="https://www.github.com/wavelog/wavelog" target="_blank">', '</a>'); ?></p>
                </div>
            <?php }
            // End of Demo Part
             ?>
            <form method="post" action="<?php echo site_url('user/login'); ?>" name="users">
                <?php $this->form_validation->set_error_delimiters('', ''); ?>
                <input type="hidden" name="id" value="<?php echo $this->uri->segment(3); ?>" />
                <div class="mb-2">
                    <label for="floatingInput"><strong><?= __("Username"); ?></strong></label>
                    <input type="text" name="user_name" class="form-control" id="floatingInput" placeholder="<?php if (file_exists('.demo')) { echo "demo"; } else { echo __("Username"); } ?>" value="<?php echo $this->input->post('user_name'); ?>" autofocus>
                </div>
                <div class="mb-2">
                    <label for="floatingPassword"><strong><?= __("Password"); ?></strong></label>
                    <input type="password" name="user_password" class="form-control" id="floatingPassword" placeholder="<?php if (file_exists('.demo')) { echo "demo"; } else { echo __("Password"); } ?>">
                </div>
                <div class="mb-2">
                    <div class="row">
                        <div class="col text-start">
                            <small><a class="" href="<?php echo site_url('user/forgot_password'); ?>"><?= __("Forgot your password?"); ?></a></small>
                        </div>
                        <div class="col text-end">
                            <?php  // we only want to create these cookies if the site is reached by https
                                if ($https_check == true && $this->config->item('encryption_key') != 'flossie1234555541') { ?>
                                    <input type="checkbox" value="1" name="keep_login" id="keep_login" />
                                    <label for="keep_login"><small><?= __("Keep me logged in"); ?></small></label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php $this->load->view('layout/messages'); ?>
                <button class="w-100 btn btn-primary" type="submit"><?= __("Login"); ?> →</button>
            </form>
        </div>
    </div>
</main>
