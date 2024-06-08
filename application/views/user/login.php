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
            <form method="post" action="<?php echo site_url('user/login'); ?>" name="users">
			<?php $this->form_validation->set_error_delimiters('', ''); ?>
                <input type="hidden" name="id" value="<?php echo $this->uri->segment(3); ?>" />
                <div>
                    <label for="floatingInput"><strong><?= __("Username"); ?></strong></label>
                    <input type="text" name="user_name" class="form-control" id="floatingInput" placeholder="<?= __("Username"); ?>"
                        value="<?php echo $this->input->post('user_name'); ?>" autofocus>
                </div>
                <div>
                    <label for="floatingPassword"><strong><?= __("Password"); ?></strong></label>
                    <input type="password" name="user_password" class="form-control" id="floatingPassword"
                        placeholder="<?= __("Password"); ?>">
                </div>

                <div>
                    <p><small><a class="" href="<?php echo site_url('user/forgot_password'); ?>"><?= __("Forgot your password?"); ?></a></small></p>
                </div>
					<?php $this->load->view('layout/messages'); ?>
                <button class="w-100 btn btn-primary" type="submit"><?= __("Login"); ?> →</button>
            </form>
        </div>
    </div>
</main>
