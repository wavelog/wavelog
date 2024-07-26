<script type="text/javascript">
    /* Define custom date format */
    var custom_date_format = "<?php echo $custom_date_format ?>";
</script>
<div class="container">
    <br>
    <h2><?= __("Hamsat - Satellite Rovers"); ?></h2>
    <p><?= __("This data comes from"); ?> <a target="_blank" href="https://hams.at/">https://hams.at/</a>.
    <script type="text/javascript">
       var workable_preset = <?php echo $user_hamsat_workable_only; ?>;
       var feed_key_set = <?php echo strlen($user_hamsat_key); ?>;
    </script>
    <?php if ($user_hamsat_key != '') { ?>
    <span id="workable_hint">
    </span>
    <button id="toggle_workable" value="<?php echo $user_hamsat_workable_only ? '0' : '1'; ?>" type="button" onclick="loadHamsAt(this);" class="btn btn-info btn-sm" style="display: none;"><?php echo $user_hamsat_workable_only ? 'Show all passes' : 'Show workable passes only'; ?></button>
    <?php } ?>
    </p>
    <p>
    <?php if ($user_hamsat_workable_only && $user_hamsat_key == '') { ?>
    <div class="alert alert-warning" role="warning">
     <?= sprintf(__("Cannot filter workable passes only without private feed key. Please set the feed key in %s."), '<a href="' . site_url('user/edit/') . $this->session->userdata('user_id') . '">' . __("your profile") . '</a>'); ?>
    </div>
    <?php } ?>
    <table style="width:100%" class="table table-hover table-striped" id="activationsList">
    <thead>
        <tr>
            <th><?= __("Date"); ?></th>
            <th><?= __("Time"); ?></th>
            <th><?= __("Callsign"); ?></th>
            <th><?= __("Comment"); ?></th>
            <th><?= __("Satellite"); ?></th>
            <th><?= __("Mode"); ?></th>
            <th><?= __("Gridsquare(s)"); ?></th>
            <th><?= __("Workable"); ?></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
