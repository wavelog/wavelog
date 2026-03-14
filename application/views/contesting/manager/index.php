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
                                    <tr>
                                        <td><a target="_blank" href="<?php echo site_url('contesting/logging_engine') . "/" . $logging_token; ?>" class="btn btn-success btn-sm"><i class="fas fa-play"></i> START</a></td>
                                        <td>XYZ</td> <!-- TODO: Add status indicator -->
                                        <td><?php echo !empty($row['time_start']) ? date($custom_date_format . ' H:i', strtotime($row['time_start'])) : '-'; ?></td>
                                        <td><?php echo !empty($row['time_end']) ? date($custom_date_format . ' H:i', strtotime($row['time_end'])) : '-'; ?></td>
                                        <td><?php echo isset($row['contestname']) ? $row['contestname'] : '-'; ?></td>
                                        <td><?php echo isset($row['station']) ? $row['station'] : '-'; ?></td>
                                        <td><?php echo isset($row['comment']) ? $row['comment'] : '-'; ?></td>
                                        <td><?php echo isset($row['qso_count']) ? $row['qso_count'] : '0'; ?></td>
                                        <td>
                                            <button onclick="edit_modal('<?php echo $row['contest_session_id']; ?>');" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></button>
                                            <button onclick="delete_modal('<?php echo $row['contest_session_id']; ?>');" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                                            <a href="<?php echo site_url('contesting/export') . "/" . $row['contest_session_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-export"></i></a>
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
</script>