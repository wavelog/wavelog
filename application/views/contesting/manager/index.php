<div class="container">

    <br>
    <?php if ($this->session->flashdata('message')) { ?>
        <!-- Display Message -->
        <div class="alert-message error">
            <p><?php echo $this->session->flashdata('message'); ?></p>
        </div>
    <?php } ?>

    <h2><?php echo $page_title; ?></h2>
    <div class="row">
        <div>
            <?php $this->load->view('layout/messages'); ?>
            <div class="card">
                <div class="card-header">
                    <?= __("Contests") ?>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= __("Here you can manage your contests, create new, edit or export them in various formats.") ?></p>
                    <button class="btn btn-primary btn-sm" onclick="create_modal();"><i class="fas fa-plus"></i> <?= __("Create New Contest") ?></button>
                    <hr>
                    <div class="table-responsive">
                        <table id="user_contests_table" class="table-sm table table-hover table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th scope="col"><?= __("Status") ?></th>
                                    <th scope="col"><?= __("Start") ?></th>
                                    <th scope="col"><?= __("End") ?></th>
                                    <th scope="col"><?= __("Contest") ?></th>
                                    <th scope="col"><?= __("Station") ?></th>
                                    <th scope="col"><?= __("Comment") ?></th>
                                    <th scope="col"><?= __("#QSO"); ?></th>
                                    <th scope="col"><?= __("Actions") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_contests as $row) {
                                    $logging_token = $this->paths->create_contesting_logging_token($row['contest_session_id']);
                                ?>
                                    <?php
                                    $now = time();
                                    $start = !empty($row['time_start']) ? strtotime($row['time_start']) : null;
                                    $end   = !empty($row['time_end'])   ? strtotime($row['time_end'])   : null;

                                    if ($start && $start > $now) {
                                        $status = '<span class="badge text-bg-primary me-1">' . __("Coming Up") . '</span>';
                                    } elseif ($start && $end && $now >= $start && $now <= $end) {
                                        $status = '<span class="badge text-bg-warning me-1">' . __("In Progress") . '</span>';
                                    } elseif ($end && $end < $now) {
                                        $status = '<span class="badge text-bg-secondary me-1">' . __("Completed") . '</span>';
                                    } else {
                                        $status = "-";
                                    }
                                    ?>
                                    <tr>
                                        <td><a target="_blank" href="<?php echo site_url('contesting/logging_engine') . "/" . $logging_token; ?>" class="btn btn-success btn-sm"><i class="fas fa-play"></i> <?= __("START") ?></a></td>
                                        <td><?php echo $status; ?></td>
                                        <td><?php echo !empty($row['time_start']) ? date($custom_date_format . ' H:i', strtotime($row['time_start'])) : '-'; ?></td>
                                        <td><?php echo !empty($row['time_end']) ? date($custom_date_format . ' H:i', strtotime($row['time_end'])) : '-'; ?></td>
                                        <td><?php echo isset($row['contestname']) ? $row['contestname'] : '-'; ?></td>
                                        <td><?php echo isset($row['station']) ? $row['station'] : '-'; ?></td>
                                        <td><?php echo isset($row['comment']) ? $row['comment'] : '-'; ?></td>
                                        <td><?php echo isset($row['qso_count']) ? $row['qso_count'] : '0'; ?></td>
                                        <td>
                                            <button onclick="edit_modal('<?php echo $row['contest_session_id']; ?>');" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="<?= __("Edit") ?>"><i class="fas fa-edit"></i></button>
                                            <button onclick="delete_modal('<?php echo $row['contest_session_id']; ?>');" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" title="<?= __("Delete") ?>"><i class="fas fa-trash-alt"></i></button>
                                            <a href="<?php echo site_url('contesting/export') . "/" . $row['contest_session_id']; ?>" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" title="<?= __("Export") ?>"><i class="fas fa-file-export"></i></a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="contestSessionModal-container"></div>
<script>
    var custom_date_format = "<?php echo $custom_date_format ?>";
    var lang_admin_contest_add_contest = '<?= __("Add a Contest"); ?>';
    var lang_error = "<?= __("Error") ?>";
</script>
<?php 
/**
 * The following code block is for the legacy import feature. It allows users to import historical contest data from their logbook into the contesting module.
 * 
 * This can be disabled by setting 'contest_legacy_import' to false in the configuration. Access to this feature is restricted to users with club access level 9 or higher.
 * 
 * This feature will get removed in the future. For a complete removal delete the following code block beside the following files:
 * - application/controllers/Contesting_import.php
 * - application/models/Contesting_import_model.php
 * - application/views/contesting/manager/import.php
 * 
 * Vy 73 de HB9HIL
 */
if (($this->config->item('contest_legacy_import') ?? true) && clubaccess_check(9)): ?>
<div class="container mt-2 mb-4">
    <p class="text-muted small mb-0">
        <a href="<?= site_url('contesting_import') ?>"><?= __("Import historical contests from logbook") ?></a>
        <?php if ($this->user_model->authorize(99)): ?>
            |
            <a href="<?= site_url('contesting_import/all') ?>"><?= __("Import for all users of this instance. You can do that because you are an administrator.") ?></a>
        <?php endif; ?>
    </p>
</div>
<?php endif; 
// END of legacy import block
?>