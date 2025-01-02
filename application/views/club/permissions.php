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
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#permissionsModal"><i class="fas fa-info-circle"></i> <?= __("See available Permissions"); ?></button>
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
                                            <td><i class="fas fa-times text-danger"></i></td>
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
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserLabel"><?= __("Add new User to Club"); ?></h5>
                        </div>
                        <form action="<?= site_url('club/alter_member'); ?>" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="club_id" value="<?php echo $club->user_id; ?>">
                                <p>
                                    <?= sprintf(__("You can only add users to the %s Clubstation if they already exist on this Wavelog Server."), $club->user_callsign); ?>
                                    <?= __("If they don't exist, please ask your Wavelog Administrator to create an account for them."); ?><br><br>
                                    <?= __("Search for the user by their callsign or first/lastname and select the permission level."); ?>
                                </p>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="table">
                                            <tr>
                                                <th class="text-center"><?= __("User (Callsign or Name)"); ?></th>
                                                <th class="text-center"><?= __("Permission"); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <input class="form-control" id="user_id" name="user_id" required />
                                                    <small class="form-text text-muted"><?= __("Type at least 2 characters."); ?></small>
                                                </td>
                                                <td>
                                                    <select class="form-select" id="permission" name="permission" required>
                                                        <option value="3"><?php echo $permissions[3]; ?></option>
                                                        <option value="9"><?php echo $permissions[9]; ?></option>
                                                    </select>
                                                    <div class="mt-2 form-check d-flex justify-content-end text-muted">
                                                        <input class="form-check-input me-2" type="checkbox" id="notify_user" name="notify_user">
                                                        <input type="hidden" name="notify_message" value="new_member">
                                                        <label class="form-check-label" for="notify_user">
                                                            <?= __("Notify the user via email"); ?>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success ld-ext-right"><?= __("Save"); ?><div class="ld ld-ring ld-spin"></div></button>
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
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-hover" id="clubuserstable">
                        <thead>
                            <tr>
                                <th><?= __("Firstname"); ?></th>
                                <th><?= __("Lastname"); ?></th>
                                <th><?= __("Callsign"); ?></th>
                                <th><?= __("Username"); ?></th>
                                <th><?= __("E-Mail"); ?></th>
                                <th><?= __("Permission"); ?></th>
                                <th><?= __("Actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($club_members as $member) { ?>
                                <tr>
                                    <td style="text-align: center; vertical-align: middle;"><?php echo $member->user_firstname; ?></td>
                                    <td style="text-align: center; vertical-align: middle;"><?php echo $member->user_lastname; ?></td>
                                    <td style="text-align: center; vertical-align: middle;"><?php echo $member->user_callsign; ?></td>
                                    <td style="text-align: center; vertical-align: middle;"><?php echo $member->user_name; ?></td>
                                    <td style="text-align: center; vertical-align: middle;"><?php echo '<a href="mailto:' . $member->user_email . '">' . $member->user_email . '</a>'; ?></td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <?php if ($member->p_level == 3) { ?>
                                            <span class="badge bg-info"><?php echo $permissions[3]; ?></span>
                                        <?php } else if ($member->p_level == 9) { ?>
                                            <span class="badge bg-warning"><?php echo $permissions[9]; ?></span>
                                        <?php } ?>
                                        <?php if ($member->user_type == 99) { ?>
                                            <span class="badge bg-danger"><?= __("Wavelog Administrator"); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $member->user_id; ?>"><i class="fas fa-edit"></i> <?= __("Edit"); ?></button>
                                        <div class="modal fade" id="editModal_<?php echo $member->user_id; ?>" tabindex="-1" aria-labelledby="editLabel_<?php echo $member->user_id; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editLabel_<?php echo $member->user_id; ?>"><?= __("Edit User"); ?></h5>
                                                    </div>
                                                    <form action="<?= site_url('club/alter_member'); ?>" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="club_id" value="<?php echo $club->user_id; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $member->user_id; ?>">

                                                            <p>
                                                                <?= __("You can modify the users permission level for this Clubstation."); ?>
                                                            </p>

                                                            <div class="table-responsive">
                                                                <table class="table">
                                                                    <thead class="table">
                                                                        <tr>
                                                                            <th class="text-center"><?= __("User Callsign"); ?></th>
                                                                            <th class="text-center"><?= __("Permission"); ?></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="text-center pt-3">
                                                                                <p><b><?php echo $member->user_callsign; ?> - <?php echo $member->user_firstname . ' ' . $member->user_lastname; ?></b></p>
                                                                            </td>
                                                                            <td>
                                                                                <select class="form-select" id="permission" name="permission" required>
                                                                                    <option value="3" <?php if ($member->p_level == 3) { echo 'selected'; } ?>><?php echo $permissions[3]; ?></option>
                                                                                    <option value="9" <?php if ($member->p_level == 9) { echo 'selected'; } ?>><?php echo $permissions[9]; ?></option>
                                                                                </select>
                                                                                <div class="mt-2 form-check d-flex justify-content-end text-muted">
                                                                                    <input class="form-check-input me-2" type="checkbox" id="notify_user" name="notify_user">
                                                                                    <input type="hidden" name="notify_message" value="modified_member">
                                                                                    <label class="form-check-label" for="notify_user">
                                                                                        <?= __("Notify the user via email about the change"); ?>
                                                                                    </label>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-success ld-ext-right"><?= __("Save"); ?><div class="ld ld-ring ld-spin"></div></button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal_<?php echo $member->user_id; ?>"><i class="fas fa-trash"></i> <?= __("Delete"); ?></button>
                                        <div class="modal fade bg-black bg-opacity-50" id="deleteModal_<?php echo $member->user_id; ?>" tabindex="-1" aria-labelledby="deleteLabel_<?php echo $member->user_id; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteLabel_<?php echo $member->user_id; ?>"><?= __("Delete User") ?></h5>
                                                    </div>
                                                    <form action="<?= site_url('club/delete_member'); ?>" method="post">
                                                        <div class="modal-body" style="text-align: center !important;">
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