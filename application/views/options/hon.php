<div class="container settings">

	<div class="row">
		<!-- Nav Start -->
		<?php $this->load->view('options/sidebar') ?>
		<!-- Nav End -->

		<!-- Content -->
		<div class="col-md-9">
            <div class="card">
                <div class="card-header"><h2><?php echo $page_title; ?> - <?php echo $sub_heading; ?></h2></div>

                <div class="card-body">
                    <?php if($this->session->flashdata('success')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                        <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php } ?>

                    <?php if($this->session->flashdata('message')) { ?>
                        <!-- Display Message -->
                        <div class="alert-message error">
                        <?php echo $this->session->flashdata('message'); ?>
                        </div>
                    <?php } ?>

                    <?php if(validation_errors()) { ?>
                    <div class="alert alert-danger">
                        <a class="btn-close" data-bs-dismiss="alert">x</a>
                        <?php echo validation_errors(); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open('options/hon_save'); ?>

                        <div class="mb-3">
                            <label for="globalSearch"><?= __("Provider for Hams Of Note"); ?></label>
                            <p><?= sprintf(__("The URL which provides the Hams Of Note List. See example and how it works here %s"),'<a href="https://github.com/wavelog/wavelog/wiki/Hams-Of-Note">'.__("Wiki")."<a/>"); ?></p>
                            <input type="text" name="hon_url" class="form-control" id="dxcache_url" aria-describedby="hon_urlHelp" value="<?php echo $this->optionslib->get_option('hon_url'); ?>">
                            <small id="hon_urlHelp" class="form-text text-muted"><?= sprintf(__("URL of the Hams Of Note List. e.g. %s"), "https://api.ham2k.net/data/ham2k/hams-of-note.txt" ); ?></small>
                        </div>
                        <!-- Save the Form -->
                        <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                    </form>
                </div>
            </div>
		</div>
	</div>

</div>
