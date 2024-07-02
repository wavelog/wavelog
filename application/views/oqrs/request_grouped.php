<br />
<?php if ($result) { ?>
<?= __("The following QSO(s) were found. Please fill out the date and time and submit your request."); ?>
<table style="width:100%" class="result-table table-sm table table-bordered table-hover table-striped table-condensed text-center">
    <thead>
        <tr>
            <th>#</th>
            <th><?= __("Date"); ?></th>
            <th><?= __("Time (UTC)"); ?></th>
            <th class="center"><span class="larger_font band"><?= __("Band"); ?></th>
            <th class="center"><?= __("Mode"); ?></th>
            <th class="center"><?= __("Callsign"); ?></th>
            <?php
            $showStationName = $this->optionslib->get_option('groupedSearchShowStationName');
            if ($showStationName == 'on'): ?>
                <th class="center"><?= __("Station Name"); ?></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
            foreach ($result as $qso) {
                echo '<tr stationid="'. $qso->station_id.'">';
                    echo '<td>'. $i++ .'</td>';
                    echo '<td><input class="form-control" type="date" name="date" value="" id="date"></td>';
                    echo '<td><input class="form-control qsotime" type="text" name="time" value="" id="time" maxlength="5" placeholder="hh:mm"></td>';
                    echo '<td id="band">'. $qso->col_band .'</td>';
                    echo '<td id="mode">'; echo $qso->col_submode == null ? strtoupper($qso->col_mode) : strtoupper($qso->col_submode);  echo '</td>';      
                    echo '<td>'. $qso->station_callsign .'</td>';
                    $showStationName = $this->optionslib->get_option('groupedSearchShowStationName');
                    if ($showStationName == 'on'):
                        echo '<td>'. $qso->station_profile_name .'</td>';
                    endif;
                echo '</tr>';
            }
        ?>
    </tbody>
</table>
<br />

<form>
    <div class="form-check form-check-inline">
        <label class="form-check-label"><?= __("QSL Route"); ?></label>
    </div>

    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="qslroute" id="bureau" value="B" checked/>
        <label class="form-check-label" for="bureau"><?= __("Bureau"); ?></label>
    </div>

    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="qslroute" id="direct" value="D" />
        <label class="form-check-label" for="direct"><?= __("Direct (write address in message below)"); ?></label>
    </div>
    <br /><br />
    <div class="mb-3">
        <label for="message"><?= __("Message"); ?></label>
        <textarea name="message" class="form-control" id="messageInput" rows="3" aria-describedby="messageHelp"></textarea>
        <small id="messageHelp" class="form-text text-muted"><?= __("Any extra information we need to know about?"); ?></small>
    </div>

    <div class="mb-3">
        <label for="emailInput"><?= __("E-mail"); ?></label>
        <input type="text" class="form-control" name="mode" id="emailInput" aria-describedby="emailInputHelp" required>
        <small id="emailInputHelp" class="form-text text-muted"><?= __("Your e-mail address where we can contact you"); ?></small>
    </div>

    <button type="button" id="requestGroupedSubmit" onclick="submitOqrsRequestGrouped(this.form);" class="btn btn-sm btn-primary"><i
            class="fas fa-plus-square"></i> <?= __("Submit request"); ?></button>
</form>
<?php } else {
	echo __("No QSOs found in the log.").'<br />';
}
	?>