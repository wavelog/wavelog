<script>
var slug = '<?php echo $slug; ?>';
var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"
</script>
<style>
    #exportmap {
	height: 100vh;
	max-height: 900px !important;
}
</style>
<div id="exportmap" class="map-leaflet"></div>
