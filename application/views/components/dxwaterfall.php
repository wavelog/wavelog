<!-- DX Waterfall Component -->
<?php if ($this->session->userdata('user_dxwaterfall_enable') == 'Y' && isset($manual_mode) && $manual_mode == 0) { ?>
<script language="javascript">
  let dxwaterfall_decont = '<?php echo $this->optionslib->get_option('dxcluster_decont'); ?>';
  let dxwaterfall_maxage = '<?php echo $this->optionslib->get_option('dxcluster_maxage'); ?>';
</script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/dxwaterfall.css">

<div class="row dxwaterfallpane">
  <div class="col-sm-12">
    <div id="dxWaterfallSpot">
      <div id="dxWaterfallSpotHeader">
        <div id="dxWaterfallSpotLeft">
          <span id="dxWaterfallMessage"></span>
        </div>
        <i id="dxWaterfallPowerOnIcon" class="fas fa-power-off"></i>
      </div>
      <div id="dxWaterfallSpotContent"></div>
      <i id="dxWaterfallPowerOffIcon" class="fas fa-power-off"></i>
    </div>
  </div>
  <div class="col-sm-12" id="dxWaterfallCanvasContainer" style="display: none;">
    <canvas id="dxWaterfall"></canvas>
  </div>
  <div class="col-sm-12" id="dxWaterfallMenuContainer" style="display: none;">
    <div id="dxWaterfallMenu">&nbsp;</div>
  </div>
</div>
<?php } ?>
