<script src="https://d3js.org/d3.v5.min.js"></script>
<script src="https://d3js.org/d3-geo-projection.v2.min.js"></script>
<script>
	const homelat = "<?php echo $latlng[0]; ?>";
	const homelon = "<?php echo $latlng[1]; ?>";
	const homegrid = "<?php echo $homegrid; ?>";
	var icon_home_url = "<?php echo base_url();?>assets/images/dot.png";
	var tileUrl = "<?php echo $this->optionslib->get_option('option_map_tile_server'); ?>";
</script>
<script type="text/javascript">
    var lang_gen_hamradio_gridsquares = '<?= _pgettext("Map Options", "Gridsquares"); ?>';
	let lang_gen_hamradio_upcoming_passes = '<?= "Upcoming satellite passes for"; ?>';
</script>

<style>
    .footprint--LEO {
      fill: rgba(255, 0, 0, 0.5);
      stroke: rgba(255, 0, 0, 0.5);
    }

    .footprint--MEO {
      fill: rgba(0, 255, 0, 0.5);
      stroke: rgba(0, 255, 0, 0.5);
    }

    .footprint--GEO {
      fill: rgba(0, 0, 255, 0.5);
      stroke: rgba(0, 0, 255, 0.5);
    }

  </style>
<div class="container">

	<br>

	<h2><?php echo $page_title; ?></h2>

	<?php if ($satellites) { ?>
		<form class="d-flex align-items-center">
			<label class="my-1 me-2" id="satslabel" for="distplot_sats"><?= __("Satellite"); ?></label>
			<select class="form-select my-1 me-sm-2 w-auto"  id="sats" onchange="plot_sat()">
				<?php foreach($satellites as $sat) {
					echo '<option value="' . $sat->satname . '"';
					if ($sat->satname == $selsat) { echo ' selected'; }
					echo '>' . $sat->satname . '</option>'."\n";
				} ?>
			</select>
		</form>

		<?php } else { ?>
			<?= __("No satellites with TLE found. Please update via CRON or satellite page. If you have no access to do this, ask your admin!"); ?>
		<?php } ?>



</div>
<?php if ($satellites) { ?>
	<div id="satcontainer">
		<div id="sat_map" class="map-leaflet" style="width: 100%; height: 85vh"></div>
	</div>
<?php } ?>
