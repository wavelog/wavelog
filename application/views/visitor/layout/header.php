<!doctype html>
<html lang="<?php echo $language['code']; ?>">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <?php if($this->optionslib->get_theme()) { ?>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme();?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/visitor.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/selectize.bootstrap4.css"/>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-dialog.css"/>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme();?>/overrides.css">
	<?php } ?>

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/fontawesome/css/all.min.css">

	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery.fancybox.min.css" />

    <!-- Maps -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/js/leaflet/leaflet.css" />

	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/loading.min.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/ldbtn.min.css" />

    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/buttons.dataTables.min.css"/>

	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/datatables.min.css"/>

	<?php if (file_exists(APPPATH.'../assets/css/custom.css')) { echo '<link rel="stylesheet" href="'.base_url().'assets/css/custom.css">'; } ?>

	<script>
		var userName = 'visitor';
	</script>

	<?php if (file_exists(APPPATH . '../assets/js/sections/custom.js')) {
		echo '<script src="' . base_url() . 'assets/js/sections/custom.js"></script>';
	} ?>

    <link rel="icon" href="<?php echo base_url(); ?>favicon.ico">

    <title><?php if(isset($page_title)) { echo $page_title; } ?> - Wavelog</title>
  </head>
  <body>

<nav class="navbar navbar-expand-lg navbar-light bg-light main-nav">
<div class="container">

	<?php
		if (!empty($slug)) {
			echo '<a class="navbar-brand" href="' . site_url('visitor/'.$slug) .'"><img class="headerLogo" src="' . base_url() . 'assets/logo/' . $this->optionslib->get_logo('header_logo') . '.png" alt="Logo"/></a>';
		} else {
			echo '<a class="navbar-brand" href="' . site_url() .'"><img src="' . base_url() . 'assets/logo/' . $this->optionslib->get_logo('header_logo') . '.png" alt="Logo" style="width:50px; height:50px;" /></a>';
		}
	?>
	<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

	<div class="collapse navbar-collapse" id="navbarNav">

		<ul class="navbar-nav">
		<?php
		if (!empty($slug)) {
			$public_maps_option = $this->optionslib->get_option('public_maps') ?? 'true';
			if ($public_maps_option == 'true') { ?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo site_url('visitor/satellites/'.$slug);?>"><?= __("Gridsquares"); ?></a>
				</li>
		<?php }
			if ($oqrs_enabled && !$disable_oqrs) {
			?>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo site_url('oqrs/'.$slug);?>"><?= __("OQRS"); ?></a>
			</li>
			<?php }
		} ?>
		</ul>
		<ul class="navbar-nav ms-auto">
			<?php if($this->optionslib->get_option('public_github_button') != "false") { ?>  <!--  != false  causes to set it on per default -->
				<li class="nav-item">
					<a class="btn btn-secondary" href="https://github.com/wavelog/wavelog" target="_blank"><?= __("Visit Wavelog on Github"); ?></a>
				</li>
			<?php } ?>
			<?php if ($this->uri->segment(1) != "oqrs" && $this->optionslib->get_option('public_login_button') != "false") { ?>
				<li class="nav-item">
					<a class="btn btn-primary ms-2" href="<?php echo site_url('user/login');?>"><?= __("Login"); ?></a>
				</li>
			<?php } ?>
		</ul>
		<div class="m-2">
			<?php if (!empty($slug)) {
				if ($public_search_enabled) { ?>
					<form method="post" name="searchForm" action="<?php echo site_url('visitor/search'); ?>" onsubmit="return validateForm()" class="d-flex align-items-center">
						<input class="form-control me-sm-2" id="searchcall" type="search" name="callsign" placeholder="<?= __("Search Callsign"); ?>" <?php if (isset($callsign) && $callsign != '') { echo 'value="'.strtoupper($callsign).'"'; } ?> aria-label="Search" data-toogle="tooltip" data-bs-placement="bottom" title="<?= __("Please enter a callsign!"); ?>">
						<input type="hidden" name="public_slug" value="<?php echo $slug; ?>">
						<button title="<?= __("Search"); ?>" class="btn btn-outline-success my-2 my-sm-0" type="submit"><i class="fas fa-search"></i>
							<div class="d-inline d-lg-none" style="padding-left: 10px"><?= __("Search"); ?></div>
						</button>
					</form>
				<?php }
			} ?>
		</div>
	</div>
</div>
</nav>
