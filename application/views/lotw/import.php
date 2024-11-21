<div class="container lotw">

  <h2><?= __("Logbook of the World"); ?> - <?= __("ADIF Import"); ?></h2>

  <?php if (isset($errormsg)) { ?>
    <div class="alert alert-danger" role="alert">
    <?php echo $errormsg; ?>
    </div>
  <?php } ?>

  <div class="card">
    <div class="card-header"><?= __("Import Options"); ?></div>
    <div class="card-body">


      <?php $this->load->view('layout/messages'); ?>

      <?php echo form_open_multipart('lotw/import'); ?>

      <div class="form-check">
        <input type="radio" id="lotwimport" name="lotwimport" class="form-check-input"<?php if ($this->config->item('disable_manual_lotw')) { echo ' checked="checked"'; } ?>>
        <label class="form-check-label" for="lotwimport"><?= __("Upload a File"); ?></label>
        <br><br>
        <p><?= sprintf(__("Upload the Exported ADIF file from LoTW from the %s Area, to mark QSOs as confirmed on LoTW."), "<a href='https://p1k.arrl.org/lotwuser/qsos?qsoscmd=adif' target='_blank'>".__("Download Report")."</a>"); ?></p>
        <p><span class="badge text-bg-info"><?= __("Important"); ?></span> <?= __("Log files must have the file type .adi"); ?></p>

        <label class="visually-hidden" for="adiffile"><?= __("Choose file"); ?></label>
        <input type="file" class="file-input mb-2 me-sm-2" id="adiffile" name="userfile" size="20" />
      </div>

      <br><br>

<?php if (!$this->config->item('disable_manual_lotw')) { ?>
      <div>
        <div class="form-check">
          <input type="radio" name="lotwimport" id="fetch" class="form-check-input" value="fetch" checked="checked" />
          <label class="form-check-label" for="fetch"><?= __("Pull LoTW data for me"); ?></label>
          <br><br>
          <p class="card-text"><?= __("From date"); ?>:</p>
          <div class="row">
            <div class="col-md-3">
              <input name="from" id="from" type="date" class="form-control w-auto">
            </div>
          </div>
          <br />
          <div class="row">
            <div class="col-md-3">
              <label class="form-check-label" for="callsign"><?= __("Select callsign to pull LoTW confirmations for."); ?></label>
              <?php
              $options = [];
              foreach ($callsigns->result() as $call) {
                $options[$call->callsign] = $call->callsign;
              }
              ksort($options);
              array_unshift($options, __("All"));
              echo form_dropdown('callsign', $options, 'All');
              ?>
            </div>
          </div>
          <br />

          <p class="form-text text-muted"><?= __("Wavelog will use the LoTW username and password stored in your user profile to download a report from LoTW for you. The report Wavelog downloads will have all confirmations since chosen date, or since your last LoTW confirmation (fetched from your log), up until now."); ?></p>
        </div>
<?php } ?>

        <input class="btn btn-primary" type="submit" value="<?= __("Import LoTW Matches"); ?>" />

        </form>
      </div>
    </div>

  </div>
