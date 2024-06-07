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
                    <?php if ($this->session->flashdata('success0')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                            <?php echo $this->session->flashdata('success0'); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->session->flashdata('success1')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                            <?php echo $this->session->flashdata('success1'); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->session->flashdata('success2')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                            <?php echo $this->session->flashdata('success2'); ?>
                        </div>
                    <?php } ?>

                    <?php if ($this->session->flashdata('message')) { ?>
                        <!-- Display Message -->
                        <div class="alert-message error">
                            <?php echo $this->session->flashdata('message'); ?>
                        </div>
                    <?php } ?>

                    <?php echo form_open('options/version_dialog_save'); ?>

                    <div class="mb-3">
                        <label for="version_dialog_header"><?= __("Version Info Header"); ?></label>
                        <input type="text" name="version_dialog_header" class="form-control" id="version_dialog_header" aria-describedby="version_dialog_header" value="<?php echo htmlspecialchars($this->optionslib->get_option('version_dialog_header') ?? ''); ?>">
                        <small id="version_dialog_header_hint" class="form-text text-muted"><?= __("You can change the header of the version info dialog."); ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="version_dialog_mode"><?= __("Version Info Mode"); ?></label>
                        <select name="version_dialog_mode" class="form-select" id="version_dialog_mode">
                            <option value="release_notes" <?php if ($this->optionslib->get_option('version_dialog') == "release_notes") {
                                                                echo "selected=\"selected\"";
                                                            } ?>><?= __("Only Release Notes"); ?></option>
                            <option value="custom_text" <?php if ($this->optionslib->get_option('version_dialog') == "custom_text") {
                                                            echo "selected=\"selected\"";
                                                        } ?>><?= __("Only Custom Text"); ?></option>
                            <option value="both" <?php if ($this->optionslib->get_option('version_dialog') == "both") {
                                                        echo "selected=\"selected\"";
                                                    } ?>><?= __("Release Notes and Custom Text"); ?></option>
                            <option value="disabled" <?php if ($this->optionslib->get_option('version_dialog') == "disabled") {
                                                            echo "selected=\"selected\"";
                                                        } ?>><?= __("Disabled"); ?></option>
                        </select>
                        <small id="version_dialog_mode_hint" class="form-text text-muted"><?= __("The Version Info is shown to every user. The user has the option to dismiss the dialog after he read it. Select if you want to show only release notes (fetched from github), only custom text or both."); ?></small>
                    </div>

                    <div class="mb-3" id="version_dialog_custom_textarea" style="display: none" role="alert">
                        <label for="version_dialog_custom_text"><?= __("Version Info Custom Text"); ?></label>
                        <textarea type="text" rows="6" name="version_dialog_custom_text" class="form-control" id="version_dialog_custom_text" aria-describedby="version_dialog_custom_text"><?php echo htmlspecialchars($this->optionslib->get_option('version_dialog_text') ?? ''); ?></textarea>
                        <small id="version_dialog_custom_text_hint" class="form-text text-muted"><?= __("This is the custom text which is shown in the dialog."); ?></small>
                    </div>

                    <!-- Save the Form -->
                    <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                    </form>
                </div>
            </div>
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h5><?= __("Show/Hide Version Info Dialog for all Users"); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($this->session->flashdata('success_trigger')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-info">
                            <?php echo $this->session->flashdata('success_trigger'); ?>
                        </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-md-6 border-end d-flex flex-column align-items-center text-center">
                            <?php echo form_open('options/version_dialog_show_to_all'); ?>
                            <button class="btn btn-success m-2"><?= __("Show for all Users"); ?></button>
                            </form>
                            <small class="form-text text-muted"><?= __("This will show the version dialog automatically to all users on their next page reload."); ?></small>
                        </div>

                        <div class="col-md-6 d-flex flex-column align-items-center text-center">
                            <?php echo form_open('options/version_dialog_show_to_none'); ?>
                            <button class="btn btn-danger m-2"><?= __("Hide for all Users"); ?></button>
                            </form>
                            <small class="form-text text-muted"><?= __("This will deactivate the automatic popup of the version dialog for all users."); ?></small>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

</div>