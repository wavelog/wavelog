<div class="modal fade bg-black bg-opacity-50" id="contestCreateSessionModal" tabindex="-1" aria-labelledby="contestSessionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="contestSessionLabel"><i class="fas fa-plus-circle me-2"></i>
                    <?php if (isset($session_info)) {
                        echo __("Edit Contest Session");
                    } else {
                        echo __("Create Contest Session");
                    } ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="flex-shrink: 0;"></button>
            </div>
            <?php if (isset($session_info)) {
                $method = 'edit_session';
            } else {
                $method = 'create_session';
            } ?>
            <form action="<?= site_url('contesting/' . $method); ?>" method="post" id="contestSessionForm">
                <div class="modal-body">
                    <p class="text-muted mb-4"><?= __("Enter all required information to create a new contest session."); ?></p>
                    <div class="mb-4">
                        <label for="contest_adif_id" class="form-label"><?= __("Contest") ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="contest_adif_id" name="contest_adif_id" required>
                            <option value=""><?= __("Please select a contest"); ?></option>
                            <?php foreach ($available_contests as $contest) { ?>
                                <option value="<?= $contest['id']; ?>" <?php if (isset($session_info) && $session_info['contest_id'] == $contest['id']) echo 'selected'; ?>><?= htmlspecialchars($contest['name']); ?></option> <!-- TODO: Make Dropdown searchable -->
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2"><?= __("Select the contest for this session"); ?></small>
                    </div>
                    <hr class="my-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="session_start" class="form-label"><?= __("Start Date/Time") ?> <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="session_start" name="session_start" required value="<?php if (isset($session_info)) echo htmlspecialchars($session_info['time_start']); ?>">
                            <small class="text-muted d-block mt-2"><?= __("When should the session start?"); ?></small>
                        </div>
                        <div class="col-md-6">
                            <label for="session_end" class="form-label"><?= __("End Date/Time") ?> <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="session_end" name="session_end" required value="<?php if (isset($session_info)) echo htmlspecialchars($session_info['time_end']); ?>">
                            <small class="text-muted d-block mt-2"><?= __("When should the session end?"); ?></small>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-4">
                        <label for="station_location" class="form-label"><?= __("Station Location") ?> <span class="text-danger">*</span></label>
                        <select class="form-select form-control form-control-sm" id="station_location" name="station_location" required>
                            <option value=""><?= __("Please select a station"); ?></option>
                            <?php foreach ($stations->result() as $stationrow) { ?>
                                <option value="<?= $stationrow->station_id; ?>" <?php if (isset($session_info) && $session_info['station_id'] == $stationrow->station_id) echo 'selected'; ?>><?= htmlspecialchars($stationrow->station_profile_name) . " (" . htmlspecialchars($stationrow->station_callsign) . ")"; ?></option>
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2"><?= __("Choose one of your stations"); ?></small>
                    </div>
                    <div class="mb-4">
                        <label for="exchangetype" class="form-label"><?= __("Exchange Type") ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="exchangetype" name="exchangetype" required>
                            <option value="Exchange" <?php if (!isset($session_info) || ($session_info['exchangetype'] ?? '') === 'Exchange') echo 'selected'; ?>><?= __("Exchange (free text only)"); ?></option>
                            <option value="Serial" <?php if (isset($session_info) && ($session_info['exchangetype'] ?? '') === 'Serial') echo 'selected'; ?>><?= __("Serial number only"); ?></option>
                            <option value="Serialexchange" <?php if (isset($session_info) && ($session_info['exchangetype'] ?? '') === 'Serialexchange') echo 'selected'; ?>><?= __("Serial number + Exchange"); ?></option>
                            <option value="Serialgridsquare" <?php if (isset($session_info) && ($session_info['exchangetype'] ?? '') === 'Serialgridsquare') echo 'selected'; ?>><?= __("Serial number + Grid Square"); ?></option>
                            <option value="SerialGridExchange" <?php if (isset($session_info) && ($session_info['exchangetype'] ?? '') === 'SerialGridExchange') echo 'selected'; ?>><?= __("Serial number + Grid + Exchange"); ?></option>
                        </select>
                        <small class="text-muted d-block mt-2"><?= __("Defines which exchange fields are used in this contest"); ?></small>
                    </div>
                    <div class="mb-4">
                        <label for="copyexchangeto" class="form-label"><?= __("Copy Exchange to Field") ?></label>
                        <select class="form-select" id="copyexchangeto" name="copyexchangeto">
                            <option value="" <?php if (!isset($session_info) || ($session_info['copyexchangeto'] ?? '') === '') echo 'selected'; ?>><?= __("— None —") ?></option>
                            <option value="dok"     <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'dok')     echo 'selected'; ?>><?= __("DOK") ?></option>
                            <option value="locator" <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'locator') echo 'selected'; ?>><?= __("Gridquare") ?></option>
                            <option value="qth"     <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'qth')     echo 'selected'; ?>><?= __("QTH") ?></option>
                            <option value="name"    <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'name')    echo 'selected'; ?>><?= __("Name") ?></option>
                            <option value="age"     <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'age')     echo 'selected'; ?>><?= __("Age") ?></option>
                            <option value="state"   <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'state')   echo 'selected'; ?>><?= __("State") ?></option>
                            <option value="power"   <?php if (isset($session_info) && ($session_info['copyexchangeto'] ?? '') === 'power')   echo 'selected'; ?>><?= __("RX Power (W)") ?></option>
                        </select>
                        <small class="text-muted d-block mt-2"><?= __("The received Exchange (Exch R) value will be copied to this logbook field.") ?></small>
                    </div>
                    <div class="mb-4">
                        <label for="session_notes" class="form-label"><?= __("Session Notes") ?></label>
                        <textarea class="form-control" id="session_notes" name="session_notes" rows="3" placeholder="<?= __("Add any additional information about this session..."); ?>"><?php if (isset($session_info)) echo htmlspecialchars($session_info['comment'] ?? ''); ?></textarea>
                        <small class="text-muted d-block mt-2"><?= __("Optional: Any additional details or notes"); ?></small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i><?= isset($session_info) ? __("Update Session") : __("Create Session"); ?>
                    </button>
                </div>
                <input type="hidden" name="contest_session_id" value="<?php if (isset($session_info)) echo htmlspecialchars($session_info['contest_session_id']); ?>">
            </form>
        </div>
    </div>
</div>