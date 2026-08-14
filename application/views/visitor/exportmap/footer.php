	<script type="text/javascript">
		/*
		*
		* Define global javascript variables
		*
		*/
		var base_url = "<?php echo base_url(); ?>"; // Base URL
		var site_url = "<?php echo site_url(); ?>"; // Site URL
		var icon_dot_url = "<?php echo $this->paths->cache_buster('/assets/images/dot.png'); ?>";
		var option_map_tile_server_copyright = '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>';
		var option_map_tile_subdomains = '<?php echo $this->optionslib->get_option('option_map_tile_subdomains') ?? 'abc';?>';
	</script>

	<!-- General JS Files used across Wavelog -->
	<script src="<?php echo $this->paths->cache_buster('/assets/js/jquery-3.3.1.min.js'); ?>"></script>
	<script src="<?php echo $this->paths->cache_buster('/assets/js/bootstrap.bundle.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/leaflet/leaflet.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/leaflet/L.Maidenhead.qrb.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/leaflet/leaflet.geodesic.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/darkmodehelpers.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/easyprint.js'); ?>"></script>

    <script id="leafembed" type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/leaflet/leafembed.js'); ?>" tileUrl="<?php echo $this->optionslib->get_option('map_tile_server');?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/sections/exportmap.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/sections/cqmap_geojson.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/leaflet/L.Terminator.js'); ?>"></script>
	</body>
</html>
