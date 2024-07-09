<br />
<?= __("If you can't find your QSO in the log, please fill out the form below. You will be contacted after the log has been checked."); ?><br />
<table style="width:100%"
    class="notinlog-table table-sm table table-bordered table-hover table-striped table-condensed text-center">
    <thead>
        <tr>
            <th>#</th>
            <th><?= __("Date"); ?></th>
            <th><?= __("Time (UTC)"); ?></th>
            <th class="center"><span class="larger_font band"><?= __("Band"); ?></th>
            <th class="center"><?= __("Mode"); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td><input class="form-control" type="date" name="date" value="" id="date"></td>
            <td><input class="form-control qsotime" type="text" name="time" value="" id="time" maxlength="5" placeholder="hh:mm"></td>
			<td><input class="form-control" type="text" name="band" value="" id="band"></td>
            <td><input class="form-control" type="text" name="mode" value="" id="mode"></td>
        </tr>
    </tbody>
</table>
<button type="button" onclick="oqrsAddLine(this.form);" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i> <?= __("Add line"); ?></button>
<br />
<form>
    <div class="mb-3">
        <label for="message"><?= __("Message"); ?></label>
        <textarea name="message" class="form-control" id="messageInput" rows="3" aria-describedby="messageHelp"></textarea>
        <small id="messageHelp" class="form-text text-muted"><?= __("Any extra information we need to know about?"); ?></small>
    </div>

    <div class="mb-3">
        <label for="emailInput"><?= __("E-mail"); ?></label>
        <input type="text" class="form-control" name="email" id="emailInput" aria-describedby="emailInputHelp" required>
        <small id="emailInputHelp" class="form-text text-muted"><?= __("Your e-mail address where we can contact you"); ?></small>
    </div>

    <button type="button" onclick="saveNotInLogRequest(this.form);" class="btn btn-sm btn-primary"><i
            class="fas fa-plus-square"></i> <?= __("Send not in log request"); ?></button>

</form>
