<div class="modal fade" id="editCronModal" tabindex="-1" aria-labelledby="editCronModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cronEditLabel">Edit Cronjob</h5>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th scope="row">Identifier</th>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control" disabled style="font-family: Courier New;" name="edit_cron_id" id="edit_cron_id" value="<?php echo $cron->id; ?>">
                                    <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="right" title="ID's can't be changed">
                                        <i class="fas fa-info"></i>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enabled</th>
                            <td>
                                <div class="form-check form-switch">
                                    <input name="edit_cron_enable_switch" class="form-check-input" type="checkbox" role="switch" id="edit_<?php echo $cron->id; ?>" <?php if ($cron->enabled ?? '0') { echo 'checked'; } ?>>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td>
                                <textarea class="form-control" name="edit_cron_description" id="edit_cron_description" maxlength="240" rows="2" style="width:100%;"><?php echo $cron->description; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Intervall</th>
                            <td>
                                <p>Choose a preset from the dropdown</p>
                                <?php
                                // List of available Presets
                                $presets = array(
                                    '' => 'Custom',
                                    '*/5 * * * *' => 'Every 5 Minutes',
                                    '*/15 * * * *' => 'Every 15 Minutes',
                                    '0 * * * *' => 'Every Hour',
                                    '0 */2 * * *' => 'Every 2 Hours',
                                    '0 0 * * *' => 'Every Day at Midnight',
                                    '0 3 * * 1' => 'Every Monday at 03:00',
                                    '0 0 1 * *' => 'First Day of Every Month at midnight',
                                    '0 2 1 */2 *' => 'Every 2 Months at 02:00'
                                );
                                ?>

                                <select class="form-select mb-4" id="edit_cron_expression_dropdown" name="edit_cron_expression_dropdown">
                                    <?php foreach ($presets as $cron_preset => $label) : ?>
                                        <option value="<?php echo $cron_preset; ?>" <?php if ($cron->expression == $cron_preset) {
                                                                                        echo " selected=\"selected\"";
                                                                                    } ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <p class="text-center"> - OR -</p>
                                <p>Enter your own Cron Expression</p>
                                <input type="text" class="form-control mb-1" style="font-family: Courier New;" name="edit_cron_expression_custom" id="edit_cron_expression_custom" value="<?php echo htmlspecialchars($cron->expression); ?>">
                                <em id="exp_humanreadable" style="display: none;"></em>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="editCron()" ><?php echo lang('admin_save'); ?></button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo lang('general_word_cancel'); ?></button>
            </div>
        </div>
    </div>
</div>