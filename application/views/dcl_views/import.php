<div class="container dcl">

  <h2><?= __("DCL"); ?> - <?= __("ADIF Import"); ?></h2>

  <?php if (isset($errormsg)) { ?>
    <div class="alert alert-danger" role="alert">
    <?php echo $errormsg; ?>
    </div>
  <?php } ?>

  <div class="card">
    <div class="card-header"><?= __("Import Options"); ?></div>
    <div class="card-body">


      <?php $this->load->view('layout/messages'); ?>

      <?php echo form_open_multipart('dcl_views/import'); ?>


<?php if (!$this->config->item('disable_manual_dcl')) { ?>
      <div>
        <div class="form-check">
          <input type="radio" name="dclimport" id="fetch" class="form-check-input" value="fetch" checked="checked" />
          <label class="form-check-label" for="fetch"><?= __("Pull DCL data for me"); ?></label>
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
              <label class="form-check-label" for="callsign"><?= __("Select callsign to pull DCL confirmations for."); ?></label>
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

          <p class="form-text text-muted"><?= __("Wavelog will use the DCL Keys stored in your user profile to download a report from DCL for you. The report Wavelog downloads will have all confirmations since chosen date, or since your last DCL confirmation (fetched from your log), up until now."); ?></p>
        </div>
<?php } ?>

        <input class="btn btn-primary" type="submit" value="<?= __("Import DCL Matches"); ?>" />

        </form>
      </div>
    </div>

  </div>
