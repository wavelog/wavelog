<div class="container">
    <br>
    <h2>Hamsat - Satellite Rovers</h2>
    <p>This data is from <a target="_blank" href="https://hams.at/">https://hams.at/</a>.
    <script type="text/javascript">
       var workable_preset = <?php echo $user_hamsat_workable_only; ?>;
       var feed_key_set = <?php echo strlen($user_hamsat_key); ?>;
    </script>
    <?php if ($user_hamsat_key != '') { ?>
    <span id="workable_hint">
       <?php if ($user_hamsat_workable_only == '1') {
          echo " Only workable passes shown.";
       } else {
          echo " All passes shown.";
       } ?>
    </span>
    <button id="toggle_workable" value="<?php echo $user_hamsat_workable_only ? '0' : '1'; ?>" type="button" onclick="loadHamsAt(this);" class="btn btn-info btn-sm"><?php echo $user_hamsat_workable_only ? 'Show all passes' : 'Show workable passes only'; ?></button>
    <?php } ?>
    </p>
    <p>
    <?php if ($user_hamsat_workable_only && $user_hamsat_key == '') { ?>
    <div class="alert alert-warning" role="warning">
       Cannot filter workable passes only without private feed key. Please set the feed key in your profile.
    </div>
    <?php } ?>
    <table style="width:100%" class="table table-hover table-striped" id="activationsList">
    <thead>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Callsign</th>
            <th>Comment</th>
            <th>Satellite</th>
            <th>Mode</th>
            <th>Gridsquare(s)</th>
            <th>Workable</th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
