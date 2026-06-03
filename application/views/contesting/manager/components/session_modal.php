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
                        <select class="form-select p-0" id="contest_adif_id" name="contest_adif_id" required placeholder="<?= htmlspecialchars(__("Please select a contest")); ?>">
                            <option value=""></option>
                            <?php foreach ($available_contests as $contest) { ?>
                                <option value="<?= $contest['id']; ?>" <?php if (isset($session_info) && $session_info['contest_id'] == $contest['id']) echo 'selected'; ?>><?= htmlspecialchars($contest['name']); ?></option>
                            <?php } ?>
                        </select>
                        <div id="contest-error" class="text-danger small mt-1" style="display:none;"><?= __("Please select a contest.") ?></div>
                        <small class="text-muted d-block mt-2"><?= sprintf(__("Select the contest for this session. If you can't find the contest, please choose %s."), "'Other'"); ?></small>
                    </div>
                    <hr class="my-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="session_start" class="form-label"><?= __("Start Date/Time") ?> <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="session_start" name="session_start" required value="<?php if (isset($session_info)) echo htmlspecialchars(str_replace(' ', 'T', substr($session_info['time_start'], 0, 16))); ?>">
                            <small class="text-muted d-block mt-2"><?= __("When should the session start?"); ?></small>
                            <div class="mt-2 d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary" id="preset_start_now"><?= __("Now") ?></button>
                                <button type="button" class="btn btn-sm btn-primary" id="preset_start_friday"><?= __("Friday 12:00") ?></button>
                                <button type="button" class="btn btn-sm btn-primary" id="preset_start_saturday"><?= __("Saturday 12:00") ?></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="session_end" class="form-label"><?= __("End Date/Time") ?> <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="session_end" name="session_end" required value="<?php if (isset($session_info)) echo htmlspecialchars(str_replace(' ', 'T', substr($session_info['time_end'], 0, 16))); ?>">
                            <small class="text-muted d-block mt-2"><?= __("When should the session end?"); ?></small>
                            <div class="mt-2 d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary" id="preset_end_4h">+4h</button>
                                <button type="button" class="btn btn-sm btn-primary" id="preset_end_12h">+12h</button>
                                <button type="button" class="btn btn-sm btn-primary" id="preset_end_24h">+24h</button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-4">
                        <label for="station_location" class="form-label"><?= __("Station Location") ?> <span class="text-danger">*</span></label>
                        <select class="form-select form-control form-control-sm" id="station_location" name="station_location" required>
                            <option value="" disabled selected><?= __("Please select a station"); ?></option>
                            <?php 
                            function is_current($value, $session_info = null, $active_station_location = null) {
                                if (isset($session_info) && $session_info['station_id'] == $value) {
                                    return 'selected';
                                } elseif (!isset($session_info) && $active_station_location == $value) {
                                    return 'selected';
                                }
                                return '';
                            }
                            
                            foreach ($stations->result() as $stationrow) { ?>
                                <option value="<?= $stationrow->station_id; ?>" <?= is_current($stationrow->station_id, $session_info, $active_station_location); ?>><?= htmlspecialchars($stationrow->station_profile_name) . " (" . htmlspecialchars($stationrow->station_callsign) . ")"; ?></option>
                            <?php } ?>
                        </select>
                        <small class="text-muted d-block mt-2"><?= __("Choose one of your stations"); ?></small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><?= __("Exchange Fields") ?> <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2"><?= __("Toggle fields on/off and drag to set the tab order in the logger.") ?></small>
                        <?php
                        $allFields = [
                            'serial'     => __("Serial Number"),
                            'gridsquare' => __("Grid Square"),
                            'exchange'   => __("Exchange (text)"),
                        ];
                        $activeFields = isset($session_info) ? ($session_info['exchangefields'] ?? ['serial']) : ['serial'];
                        // Render active fields first (in saved order), then inactive ones
                        $ordered = [];
                        foreach ($activeFields as $f) {
                            if (isset($allFields[$f])) $ordered[$f] = $allFields[$f];
                        }
                        foreach ($allFields as $f => $label) {
                            if (!isset($ordered[$f])) $ordered[$f] = $label;
                        }
                        ?>
                        <ul id="exchange-field-list" class="list-group" style="cursor:grab;">
                            <?php foreach ($ordered as $field => $label) { ?>
                            <li class="list-group-item d-flex align-items-center gap-2 py-2" draggable="true" data-field="<?php echo $field ?>">
                                <i class="fas fa-grip-vertical text-muted"></i>
                                <input type="checkbox" class="form-check-input flex-shrink-0" id="ef-<?php echo $field ?>"
                                       <?php if (in_array($field, $activeFields)) echo 'checked'; ?>>
                                <label for="ef-<?php echo $field ?>" class="mb-0 flex-grow-1" style="cursor:pointer;"><?php echo $label ?></label>
                            </li>
                            <?php } ?>
                        </ul>
                        <input type="hidden" id="exchangefields-input" name="exchangefields" value="">
                        <div id="exchangefields-error" class="text-danger small mt-1" style="display:none;"><?= __("Please enable at least one exchange field.") ?></div>
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
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="callbook_lookup" name="callbook_lookup" value="1"
                                <?php if (!isset($session_info) || ($session_info['callbook_lookup'] ?? true)) echo 'checked'; ?>>
                            <label class="form-check-label" for="callbook_lookup"><?= __("Callbook Lookup (Online)") ?></label>
                        </div>
                        <small class="text-muted d-block mt-1"><?= __("Lookup callbook data in the configured online callbook service. If disabled, only existing log data from previous qsos with this callsign will be used. Log data is always prioritized.") ?></small>
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
<script>
(function () {
    function formatDatetimeLocal(d) {
        return d.getUTCFullYear() + '-' +
            String(d.getUTCMonth() + 1).padStart(2, '0') + '-' +
            String(d.getUTCDate()).padStart(2, '0') + 'T' +
            String(d.getUTCHours()).padStart(2, '0') + ':' +
            String(d.getUTCMinutes()).padStart(2, '0');
    }

    function nextWeekday(dayOfWeek, hour) {
        var now = new Date();
        var d = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), hour, 0, 0));
        var diff = (dayOfWeek - d.getUTCDay() + 7) % 7 || 7;
        d.setUTCDate(d.getUTCDate() + diff);
        return d;
    }

    document.getElementById('preset_start_now').addEventListener('click', function () {
        document.getElementById('session_start').value = formatDatetimeLocal(new Date());
    });
    document.getElementById('preset_start_friday').addEventListener('click', function () {
        document.getElementById('session_start').value = formatDatetimeLocal(nextWeekday(5, 12));
    });
    document.getElementById('preset_start_saturday').addEventListener('click', function () {
        document.getElementById('session_start').value = formatDatetimeLocal(nextWeekday(6, 12));
    });

    function addHoursToStart(hours) {
        var startVal = document.getElementById('session_start').value;
        if (!startVal) return;
        var d = new Date(startVal + ':00Z');
        d.setUTCHours(d.getUTCHours() + hours);
        document.getElementById('session_end').value = formatDatetimeLocal(d);
    }

    document.getElementById('preset_end_4h').addEventListener('click', function () { addHoursToStart(4); });
    document.getElementById('preset_end_12h').addEventListener('click', function () { addHoursToStart(12); });
    document.getElementById('preset_end_24h').addEventListener('click', function () { addHoursToStart(24); });
})();

(function () {
    var list = document.getElementById('exchange-field-list');
    var hiddenInput = document.getElementById('exchangefields-input');
    var errorDiv = document.getElementById('exchangefields-error');
    var dragEl = null;

    function serialize() {
        var active = [];
        list.querySelectorAll('li').forEach(function (li) {
            if (li.querySelector('input[type=checkbox]').checked) {
                active.push(li.dataset.field);
            }
        });
        hiddenInput.value = JSON.stringify(active);
        return active;
    }

    list.querySelectorAll('li').forEach(function (li) {
        li.addEventListener('dragstart', function (e) {
            dragEl = li;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(function () { li.classList.add('opacity-50'); }, 0);
        });
        li.addEventListener('dragend', function () {
            li.classList.remove('opacity-50');
            dragEl = null;
        });
        li.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragEl || dragEl === li) return;
            var rect = li.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                list.insertBefore(dragEl, li);
            } else {
                list.insertBefore(dragEl, li.nextSibling);
            }
        });
    });

    document.getElementById('contestSessionForm').addEventListener('submit', function (e) {
        var active = serialize();
        var contestVal = document.getElementById('contest_adif_id').value;
        var prevent = false;

        if (!contestVal) {
            e.preventDefault();
            document.getElementById('contest-error').style.display = '';
            prevent = true;
        } else {
            document.getElementById('contest-error').style.display = 'none';
        }

        if (active.length === 0) {
            if (!prevent) e.preventDefault();
            errorDiv.style.display = '';
        } else {
            errorDiv.style.display = 'none';
        }
    });
})();
</script>