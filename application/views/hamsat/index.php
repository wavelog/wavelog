<div class="container">
    <br>
    <h2>Hamsat - Satellite Rovers</h2>
    <p>This data is from <a target="_blank" href="https://hams.at/">https://hams.at/</a>.
    <?php if ($user_hamsat_workable_only) {
    echo " Only workable passes shown.";
    } else {
    echo " All passes shown.";
    }?>
    </p>
    <button type="button" onclick="loadHamsAt();" class="btn btn-info btn-sm">Show All Passes</button>
    <p>
    <?php if ($user_hamsat_workable_only && $user_hamsat_key == '') { ?>
    <div class="alert alert-warning" role="warning">
       Private feed key empty. Please set the feed key in your profile.
    </div>
    <?php } else { ?>
<!-- <table style="width:100%" class="table-sm table table-hover table-striped table-condensed text-center" id="activationsList">-->
<table style="width:100%" class="table table-hover table-striped table-condensed text-center" id="activationsList">
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
    <?php } ?>
</div>
