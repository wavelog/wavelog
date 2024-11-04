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
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                        <?php echo validation_errors(); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open('options/callbook_save'); ?>

                        <div class="mb-3">
                            <p><?= __("This callbook configuration is used for live callbook requests during QSO logging. You can choose between QRZ.com and HamQTH.com."); ?>
                            <?= __("If you choose QRZ.com, it is recommended to have an XML subscription. It works without one as well, but you will receive limited data (e.g. no gridsquare)."); ?></p>
                            <div class="row">
                                <div class="col">
                                    <label class="mt-3" for="callbook_provider"><?= __("Callbook"); ?></label>
                                    <select class="form-select" name="callbook_provider" id="callbook_provider">
                                        <option value="disabled" <?php if($callbook_provider == '') { echo "selected"; } ?>><?= __("Disabled"); ?></option>
                                        <option value="qrz" <?php if($callbook_provider == "qrz") { echo "selected"; } ?>>QRZ.com</option>
                                        <option value="hamqth" <?php if($callbook_provider == "hamqth") { echo "selected"; } ?>>HamQTH.com</option>
                                    </select>
                                    <label class="mt-3" for="callbook_fullname"><?= __("Use Full Name"); ?> <i id="fullname_tooltip" data-bs-toggle="tooltip" data-bs-placement="top" class="fas fa-question-circle text-muted ms-2" data-bs-custom-class="custom-tooltip" data-bs-html="true" data-bs-title="<?= __("This defines whether you want to retrieve the operator's full name, including first and last names. In some countries, this may not be legal due to data protection laws."); ?>"></i></label>
                                    <select class="form-select" name="callbook_fullname" id="callbook_fullname">
                                        <option value="0" <?php if($callbook_fullname == "0" || $callbook_fullname == '') { echo "selected"; } ?>><?= __("No"); ?></option>
                                        <option value="1" <?php if($callbook_fullname == "1") { echo "selected"; } ?>><?= __("Yes"); ?></option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="mt-3" for="callbook_username"><?= __("Callbook Username"); ?></label>
                                    <input class="form-control" type="text" name="callbook_username" id="callbook_username" value="<?php echo $callbook_username; ?>" />
                                    <label class="mt-3" for="callbook_password"><?= __("Callbook Password"); ?></label>
                                    <input class="form-control" type="password" name="callbook_password" id="callbook_password" value="<?php echo $callbook_password; ?>" />
                                </div>
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