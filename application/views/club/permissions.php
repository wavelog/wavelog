<div class="container">
    <br>
    <h2><?= sprintf(__("Club Permissions for %s"), $club->user_callsign); ?></h2>
    <!-- <a class="btn btn-primary" href="<?= site_url('user'); ?>"><i class="fas fa-arrow-left"></i> <?= __("Go back"); ?></a> -->

    <div class="card mt-3">
        <div class="card-header">
            <?= __("Club Permissions"); ?>
        </div>
        <div class="card-body">
            <p><?= __("In order for users to log QSOs with this club/special callsign, they need appropriate authorizations. Add users to the table below and set the appropriate permission."); ?></p>
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#permissionsModal"><i class="fas fa-info-circle"></i> <?= __("See available Permissions"); ?></button>
            <div class="modal fade bg-black bg-opacity-50" id="permissionsModal" aria-labelledby="permissionsLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="permissionsLabel"><?= __("Available Permissions") ?></h5>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="table">
                                        <tr>
                                            <th><?= __("Action"); ?></th>
                                            <th><?= __("Operator"); ?></th>
                                            <th><?= __("Manager"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= __("Log QSOs via Web GUI (live and post)"); ?></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Log QSOs via API"); ?></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Edit a QSO"); ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("QSO was done by the operator"); ?></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("QSO was done by another operator"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Delete a QSO"); ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("QSO was done by the operator"); ?></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("QSO was done by another operator"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Manage Stationsetup (edit/create logbooks and locations)"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Manage Third-Party services"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Import QSO per ADIF"); ?></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>

                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("Export QSO per ADIF"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                        <tr class="empty-row">
                                            <td colspan="3"></td>
                                        </tr>
                                        <tr>
                                            <td><?= __("User Management"); ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("Can create new users in Wavelog"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("Can edit other users in Wavelog"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-5"><i class="fas fa-arrow-right me-3"></i><?= __("Can edit Club permissions and add/remove users"); ?></td>
                                            <td><i class="fas fa-times text-danger"></i></td>
                                            <td><i class="fas fa-check text-success"></i></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header">
            <?= __("Users with Permissions"); ?>
        </div>
        <div class="card-body">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> <?= __("Add User"); ?>
            </button>
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserLabel"><?= __("Add User to Club"); ?></h5>
                        </div>
                        <div class="modal-body">
                            <form action="<?= site_url('user/club_permissions/add'); ?>" method="post">
                                <!-- Club ID as Hidden Field -->
                                <input type="hidden" name="club_id" value="2">

                                <!-- User Callsign Input -->
                                <div class="mb-3">
                                    <label for="user_callsign" class="form-label"><?= __("User Callsign"); ?></label>
                                    <input type="text" class="form-control" id="user_callsign" name="user_callsign" required>
                                </div>

                                <!-- Permission Selector -->
                                <div class="mb-3">
                                    <label for="permission" class="form-label"><?= __("Permission"); ?></label>
                                    <select class="form-select" id="permission" name="permission" required>
                                        <option value="operator"><?= __("Operator"); ?></option>
                                        <option value="manager"><?= __("Manager"); ?></option>
                                    </select>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-success w-100"><?= __("Add User"); ?></button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Close"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (empty($users)) { ?>
                <div class="text-center">
                    <h5><?= __("No users currently have access to this club station."); ?></h5>
                </div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="clubuserstable">
                        <thead>
                            <tr>
                                <th><?= __("User"); ?></th>
                                <th><?= __("QSOs"); ?></th>
                                <th><?= __("Permission"); ?></th>
                                <th><?= __("Edit"); ?></th>
                                <th><?= __("Delete"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) { ?>
                                <tr>
                                    <td><?= $user->user_callsign; ?></td>
                                    <td><?= $user->qso_count; ?></td>
                                    <td><?= $user->permission; ?></td>
                                    <td>
                                        <a href="<?= site_url('user/club_permissions/edit/' . $user->id); ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> <?= __("Edit"); ?></a>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('user/club_permissions/delete/' . $user->id); ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> <?= __("Delete"); ?></a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                </div>
        </div>
    </div>