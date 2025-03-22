<div class="modal fade bg-black bg-opacity-50" id="firstLoginWizardModal" tabindex="-1" aria-labelledby="firstLoginWizardLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="firstLoginWizardLabel"><?= __("First Login Wizard") ?></h5>
            </div>
            <div class="modal-body" id="firstloginwizard_modal_content">
                <form action="<?php echo site_url('user/firstlogin_wizard_form'); ?>" method="post" style="display: inline;">
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-5" id="logo-container">
                            <img src="<?php echo base_url(); ?>assets/logo/wavelog_logo_darkly.png" alt="" style="max-width: 100%; height: auto; margin-top: 70px;">
                        </div>

                        <div class="col-md-7">
                            <?php if ($this->session->flashdata('fl_wiz_error') != '') { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $this->session->flashdata('fl_wiz_error'); ?>
                                </div>
                            <?php } ?>
                            <h4 style="margin-top: 10px;"><?= __("Hello and Welcome to Wavelog!"); ?></h4>
                            <p style="margin-top: 20px;"><?= sprintf(__("Before you can start logging QSOs, we need to set up your first Station Location. You can find more information about how Station Locations and Logbooks work in our %sWiki here%s!"), '<a href="https://github.com/wavelog/wavelog/wiki/Stationsetup" target="_blank">', '</a>'); ?></p>
                            <p><?= __("Please provide some additional information so that Wavelog can create your first Station:"); ?></p>
                            <div class="container">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="station_name" class="form-label mb-0"><?= __("Station Name"); ?></label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="station_name" name="station_name" placeholder="<?= __("Home QTH") ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="station_callsign" class="form-label mb-0"><?= __("Station Callsign"); ?></label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control uppercase" id="station_callsign" pattern="^\S+$" name="station_callsign" placeholder="4W7EST" required>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="station_dxcc" class="form-label mb-0"><?= __("Station DXCC"); ?></label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="station_dxcc" name="station_dxcc" required>
                                            <option value=""><?= __("Please select one"); ?></option>
                                            <?php foreach ($dxcc_list->result() as $dxcc) { ?>
                                                <?php $isDeleted = $dxcc->end !== NULL; ?>
                                                <option value="<?php echo $dxcc->adif; ?>">
                                                    <?php echo ucwords(strtolower($dxcc->name)) . ' - ' . $dxcc->prefix;
                                                    if ($isDeleted) {
                                                        echo ' (' . __("Deleted DXCC") . ')';
                                                    }
                                                    ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <p><?= __("Station Zones"); ?></p>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row gx-2">
                                            <div class="col-md-6">
                                                <select class="form-select" id="stationCQZoneInput" name="station_cqz" required>
                                                    <?php
                                                    for ($i = 1; $i <= 40; $i++) {
                                                        echo '<option value="' . $i . '">' . $i . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <small class="form-text text-muted"><?= __("CQ Zone"); ?> (<?= sprintf(_pgettext("zone lookup","%sLookup%s"),"<a href='https://zone-check.eu/?m=cq' target='_blank'>", "</a>"); ?>)</small>
                                            </div>
                                            <div class="col-md-6">
                                                <select class="form-select" id="stationITUZoneInput" name="station_ituz" required>
                                                    <?php
                                                    for ($i = 1; $i <= 90; $i++) {
                                                        echo '<option value="' . $i . '">' . $i . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <small class="form-text text-muted"><?= __("ITU Zone"); ?> (<?= sprintf(_pgettext("zone lookup","%sLookup%s"),"<a href='https://zone-check.eu/?m=itu' target='_blank'>", "</a>"); ?>)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="station_locator" class="form-label mb-0"><?= __("Station Locator"); ?></label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control uppercase" id="station_locator" name="station_locator" placeholder="HM45AP" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success"><?= __("Save and Start Logging"); ?></button>
            </div>
            </form>
        </div>
    </div>
</div>
