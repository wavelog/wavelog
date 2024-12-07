<div class="container">
    <br>
    <h2><?= sprintf(__("Club Permissions for %s"), $club->user_callsign); ?></h2>
    <!-- <a class="btn btn-primary" href="<?= site_url('user'); ?>"><i class="fas fa-arrow-left"></i> <?= __("Go back"); ?></a> -->

    <?php $this->load->view('layout/messages'); ?>

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
                                            <th><?php echo $permissions[3]; ?></th>
                                            <th><?php echo $permissions[9]; ?></th>
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
                        <form action="<?= site_url('club/alter_member'); ?>" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="club_id" value="<?php echo $club->user_id; ?>">

                                <div class="mb-3">
                                    <label for="user_id" class="form-label"><?= __("User Callsign"); ?></label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value=""><?= __("Select User"); ?></option>
                                        <?php foreach ($users->result() as $user) { ?>
                                            <option value="<?php echo $user->user_id; ?>"><?php echo $user->user_callsign; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="permission" class="form-label"><?= __("Permission"); ?></label>
                                    <select class="form-select" id="permission" name="permission" required>
                                        <option value="3"><?php echo $permissions[3]; ?></option>
                                        <option value="9"><?php echo $permissions[9]; ?></option>
                                    </select>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success"><?= __("Add User"); ?></button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php if (empty($club_members)) { ?>
                <div class="text-center">
                    <h5><?= __("No users currently have access to this club station."); ?></h5>
                </div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="clubuserstable">
                        <thead>
                            <tr>
                                <th><?= __("User"); ?></th>
                                <th><?= __("Permission"); ?></th>
                                <th><?= __("Edit"); ?></th>
                                <th><?= __("Delete"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($club_members as $member) { ?>
                                <tr>
                                    <td><?php echo $member->user_callsign; ?></td>
                                    <td><?php echo $permissions[$member->p_level]; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $member->user_id; ?>"><i class="fas fa-edit"></i> <?= __("Edit"); ?></button>
                                        <div class="modal fade bg-black bg-opacity-50" id="editModal_<?php echo $member->user_id; ?>" tabindex="-1" aria-labelledby="editLabel_<?php echo $member->user_id; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editLabel_<?php echo $member->user_id; ?>"><?= __("Edit User") ?></h5>
                                                    </div>
                                                    <form action="<?= site_url('club/alter_member'); ?>" method="post">
                                                        <div class="modal-body" style="text-align: left !important;">
                                                            <input type="hidden" name="club_id" value="<?php echo $club->user_id; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $member->user_id; ?>">

                                                            <div class="mb-3">
                                                                <label for="user_id" class="form-label"><?= __("User Callsign"); ?></label>
                                                                <p><?php echo $member->user_callsign; ?></p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="permission" class="form-label"><?= __("Permission"); ?></label>
                                                                <select class="form-select" id="permission" name="permission" required>
                                                                    <option value="3" <?php if ($member->p_level == 3) { echo 'selected'; } ?>><?php echo $permissions[3]; ?></option>
                                                                    <option value="9" <?php if ($member->p_level == 9) { echo 'selected'; } ?>><?php echo $permissions[9]; ?></option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-success"><?= __("Save"); ?></button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal_<?php echo $member->user_id; ?>"><i class="fas fa-trash"></i> <?= __("Delete"); ?></button>
                                        <div class="modal fade bg-black bg-opacity-50" id="deleteModal_<?php echo $member->user_id; ?>" tabindex="-1" aria-labelledby="deleteLabel_<?php echo $member->user_id; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteLabel_<?php echo $member->user_id; ?>"><?= __("Delete User") ?></h5>
                                                    </div>
                                                    <form action="<?= site_url('club/delete_member'); ?>" method="post">
                                                        <div class="modal-body" style="text-align: left !important;">
                                                            <input type="hidden" name="club_id" value="<?php echo $club->user_id; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $member->user_id; ?>">

                                                            <p><?= __("Are you sure you want to delete this user from the club?"); ?></p>

                                                            <div class="mb-3">
                                                                <p>
                                                                    <?= sprintf(__("Callsign: %s"), $member->user_callsign); ?><br>
                                                                    <?= sprintf(__("Role: %s"), $permissions[$member->p_level]); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger"><?= __("Delete"); ?></button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                </div>
        </div>
    </div>
</div>