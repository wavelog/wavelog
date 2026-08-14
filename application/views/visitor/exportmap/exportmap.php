<script>
	var slug = '<?php echo $slug; ?>';
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"
	var option_map_tile_subdomains = '<?php echo $this->optionslib->get_option('option_map_tile_subdomains') ?? 'abc';?>';
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<style>
    #exportmap {
	height: 100vh;
	max-height: 900px !important;
}
</style>
<div id="exportmap" class="map-leaflet"></div>
