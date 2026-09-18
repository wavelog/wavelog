<?php
$aos_time = date_create($aos);
$tca_time = date_create($tca);
$los_time = date_create($los);
?>
<div id="error-messages-hamsat-post"></div>
<form name="hamsatpost" id="hamsatform">
   <div class="container-fluid">
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="sat_name"><?= __("Satellite"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <span><?= $satinfo[0]->satname." (".$satinfo[0]->displayname.")"; ?></span>
         </div>
      </div>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="date"><?= __("Date"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <span><?= date_format($aos_time, $custom_date_format); ?></span>
         </div>
      </div>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="pass_time"><?= __("Pass Time"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <span><?= date_format($aos_time, 'H:i:s'); ?></span> - <span><?= date_format($los_time, 'H:i:s'); ?></span>
         </div>
      </div>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="mode"><?= __("Mode"); ?></label>
         </div>
         <div class="mb-3 col-sm-2">
            <?php
               $sat_modes = array();
               foreach ($satinfo as $sat) {
                  $sat_modes[] = $sat->uplink_mode;
               }
            ?>
            <select id ="mode" name="mode" tabindex="1" class="form-select" <?php echo (count($sat_modes) == 1 && ($sat_modes[0] == 'FM' || $sat_modes[0] == 'PKT')) ? 'disabled' : ''; ?> onchange="toggleTpx()">
            <?php if (in_array('FM', $sat_modes)) { ?>
                  <option value="Data">FM</option>
            <?php } ?>
            <?php if (in_array('SSB', $sat_modes) || in_array('USB', $sat_modes) || in_array('LSB', $sat_modes)) { ?>
                  <option value="SSB">SSB</option>
                  <option value="CW">CW</option>
            <?php } ?>
            <?php if (in_array('PKT', $sat_modes) || in_array('SSB', $sat_modes) || in_array('USB', $sat_modes) || in_array('LSB', $sat_modes)) { ?>
                  <option value="Data">Data</option>
            <?php } ?>
               </select>
         </div>
      </div>
      <?php if ($satinfo[0]->uplink_mode != 'FM') { ?>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="callsign"><?= __("Frequency"); ?></label>
         </div>
         <div class="mb-3 col-sm-2">
            <input class="form-control" type="text" id="mhz" name="mhz" placeholder="<?= __("Optional"); ?>"/>
            <small><span id="tpx_hint"><?= __("Center").": "; ?><span id="tpx_center_freq"></span></span></small>
         </div>
         <div class="mb-3 col-sm-2">
            <input type="radio" value="up" id="mhz_direction_up" name="mhz_direction" onClick="toggleTpx()"> <?= __("Uplink"); ?>
            <input type="radio" value="down" id="mhz_direction_down" name="mhz_direction" checked onClick="toggleTpx()"> <?= __("Downlink"); ?>
         </div>
      </div>
      <?php } ?>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="callsign"><?= __("Callsign"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <input class="form-control uppercase" type="text" name="callsign" value="<?= $callsign; ?>" />
         </div>
      </div>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="homegrid"><?= __("Gridsquare(s)"); ?></label>
         </div>
            <?php
               $grids = explode(',', $homegrid);
            ?>
         <div class="mb-3 col-sm-1">
            <input class="form-control uppercase" type="text" name="grid0" value="<?= substr($grids[0] ?? '', 0, 4); ?>" />
         </div>
         <div class="mb-3 col-sm-1">
            <input class="form-control uppercase" type="text" name="grid1" value="<?= substr($grids[1] ?? '', 0, 4); ?>" />
         </div>
         <div class="mb-3 col-sm-1">
            <input class="form-control uppercase" type="text" name="grid2" value="<?= substr($grids[2] ?? '', 0, 4); ?>" />
         </div>
         <div class="mb-3 col-sm-1">
            <input class="form-control uppercase" type="text" name="grid3" value="<?= substr($grids[3] ?? '', 0, 4); ?>" />
         </div>
      </div>
      <?php
         if ($refs) {
            $reftxt = array();
            $refs['iota'] && $reftxt[] = 'IOTA '.$refs['iota'];
            $refs['sota'] && $reftxt[] = 'SOTA '.$refs['sota'];
            ($refs['sig'] && $refs['sig_info']) && $reftxt[] = $refs['sig'].' '.$refs['sig_info'];
            $refs['wwff'] && $reftxt[] = 'WWFF '.$refs['wwff'];
            $refs['pota'] && $reftxt[] = 'POTA '.$refs['pota'];
         }
      ?>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="comment"><?= __("Comment"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <input class="form-control" type="text" name="comment" placeholder="<?= __("Optional") ;?>"value="<?= implode(', ', $reftxt); ?>" maxlength="50"/>
            <small><?= __("Max 50 characters"); ?></small>
         </div>
      </div>
      <div class="row">
         <div class="mb-3 col-sm-2">
            <label for="chat"><?= __("Realtime chat enabled"); ?></label>
         </div>
         <div class="mb-3 col-sm-4">
            <input class="form-check-input" type="checkbox" name="chat" value="true" aria-describedby="chat_help"/>
            <small id="chat_help" class="form-text text-muted">(<?= __("Chat is open until 1 day after the pass ends"); ?>)</small>
         </div>
      </div>
   </div>
<input type="hidden" name="tca" value="<?= date_format($tca_time, 'c'); ?>" />
<input type="hidden" name="catnr" value="<?= $satinfo[0]->norad_id; ?>" />
<input type="hidden" name="lat" value="<?= $lat; ?>" />
<input type="hidden" name="lon" value="<?= $lon; ?>" />
<input type="hidden" name="downlink_freq" id="downlink_freq" value="<?= $satinfo[0]->downlink_freq; ?>" />
<input type="hidden" name="uplink_freq" id="uplink_freq" value="<?= $satinfo[0]->uplink_freq; ?>" />
<input type="hidden" name="tpxdata" id="tpxdata" value='<?= json_encode($satinfo); ?> ' />
</form>
