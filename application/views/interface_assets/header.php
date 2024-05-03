<!doctype html>
<html lang="en">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link rel="manifest" href="<?php echo base_url(); ?>manifest.json" />

	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/buttons.dataTables.min.css" />

	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/datatables.min.css" />

	<!-- Bootstrap CSS -->
	<?php if ($this->optionslib->get_theme()) { ?>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme(); ?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/selectize.bootstrap4.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-dialog.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $this->optionslib->get_theme(); ?>/overrides.css">
	<?php } ?>

	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/fontawesome/css/all.css">

	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery.fancybox.min.css" />

	<!-- Maps -->
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/js/leaflet/leaflet.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/js/leaflet/Control.FullScreen.css" />

	<?php if ($this->uri->segment(1) == "search" && $this->uri->segment(2) == "filter") { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/query-builder.default.min.css" />
	<?php } ?>

	<?php if ($this->uri->segment(1) == "notes" && ($this->uri->segment(2) == "add" || $this->uri->segment(2) == "edit")) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/plugins/quill/quill.snow.css" />
	<?php } ?>

	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/loading.min.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/ldbtn.min.css" />


	<?php if ($this->uri->segment(1) == "sattimers") { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/sattimers.css" />
	<?php } ?>

	<?php if (file_exists(APPPATH . '../assets/css/custom.css')) {
		echo '<link rel="stylesheet" href="' . base_url() . 'assets/css/custom.css">';
	} ?>

	<script>
		var userName = '<?php echo $this->session->userdata('user_name'); ?>';
		<?php
		if ($this->uri->segment(1) == "qso") {
                	$actstation=$this->stations->find_active() ?? '';
                	echo "var activeStationId = '".$actstation."';\n";
                	$profile_info = $this->stations->profile($actstation)->row();
                	echo "var activeStationTXPower = '".xss_clean($profile_info->station_power)."';\n";
                	echo "var activeStationOP = '".xss_clean($this->session->userdata('operator_callsign'))."';\n";
		}
                ?>
	</script>

	<?php if (file_exists(APPPATH . '../assets/js/sections/custom.js')) {
		echo '<script src="' . base_url() . 'assets/js/sections/custom.js"></script>';
	} ?>

	<link rel="icon" href="<?php echo base_url(); ?>favicon.ico">

	<title><?php if (isset($page_title)) {
				echo $page_title;
			} ?> - Wavelog</title>
</head>

<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-light main-nav" id="header-menu">
		<div class="container">
			<a class="navbar-brand" href="<?php echo site_url(); ?>"><img class="headerLogo" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo'); ?>.png" alt="Logo" /></a>
			<?php if (ENVIRONMENT == "development") { ?>
				<span class="badge text-bg-danger"><?php echo lang('menu_badge_developer_mode'); ?></span>
			<?php } ?>
			<?php if (ENVIRONMENT == "maintenance") { ?>
				<span class="badge text-bg-info">Maintenance</span>
			<?php } ?>

			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav navbar-nav-left">
					<li class="nav-item dropdown"> <!-- LOGBOOK -->
						<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"> <?php echo lang('menu_logbook'); ?></a>
						<ul class="dropdown-menu header-dropdown">
							<li><a class="dropdown-item" href="<?php echo site_url('logbook'); ?>"><i class="fas fa-book"></i> <?php echo lang('menu_overview'); ?></a></li>
							<div class="dropdown-divider"></div>
							<li><a class="dropdown-item" href="<?php echo site_url('logbookadvanced'); ?>"><i class="fas fa-book-open"></i> <?php echo lang('menu_advanced'); ?></a></li>
							<div class="dropdown-divider"></div>
							<li><a class="dropdown-item" href="<?php echo site_url('qsl'); ?>" title="QSL"><i class="fa fa-id-card"></i> <?php echo lang('menu_view_qsl'); ?></a></li>
							<div class="dropdown-divider"></div>
							<li><a class="dropdown-item" href="<?php echo site_url('eqsl'); ?>" title="eQSL"><i class="fa fa-id-card"></i> <?php echo lang('menu_view_eqsl'); ?></a></li>
						</ul>
					</li>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
						<li class="nav-item dropdown"> <!-- QSO -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo lang('menu_qso'); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=0'); ?>" title="Log Live QSOs"><i class="fas fa-list"></i> <?php echo lang('menu_live_qso'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=1'); ?>" title="Log QSO made in the past"><i class="fas fa-list"></i> <?php echo lang('menu_post_qso'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('simplefle'); ?>" title="Simple Fast Log Entry"><i class="fas fa-list"></i> <?php echo lang('menu_fast_log_entry'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=0'); ?>" title="Live contest QSOs"><i class="fas fa-list"></i> <?php echo lang('menu_live_contest_logging'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=1'); ?>" title="Post contest QSOs"><i class="fas fa-list"></i> <?php echo lang('menu_post_contest_logging'); ?></a></li>
							</ul>
						</li>

						<?php if ($this->session->userdata('user_show_notes') == 1) { ?><!-- NOTES -->
							<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?php echo lang('menu_notes'); ?></a>
						<?php } ?>

						<li class="nav-item dropdown"> <!-- ANALYTICS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo lang('menu_analytics'); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('statistics'); ?>" title="Statistics"><i class="fas fa-chart-area"></i> <?php echo lang('menu_statistics'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('gridmap'); ?>" title="Gridmap"><i class="fas fa-globe-europe"></i> <?php echo lang('menu_gridmap'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activated_gridmap'); ?>" title="Activated Gridsquares"><i class="fas fa-globe-europe"></i> <?php echo lang('menu_activated_gridsquares'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activators'); ?>" title="Gridsquare Activators"><i class="fas fa-globe-europe"></i> <?php echo lang('menu_gridsquare_activators'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('distances'); ?>" title="Distances"><i class="fas fa-chart-area"></i> <?php echo lang('menu_distances_worked'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('dayswithqso'); ?>" title="Days with QSOs"><i class="fas fa-chart-area"></i> <?php echo lang('menu_days_with_qsos'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeline'); ?>" title="Timeline"><i class="fas fa-chart-area"></i> <?php echo lang('menu_timeline'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('accumulated'); ?>" title="Accumulated Statistics"><i class="fas fa-chart-area"></i> <?php echo lang('menu_accumulated_statistics'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeplotter'); ?>" title="View time when worked"><i class="fas fa-chart-area"></i> <?php echo lang('menu_timeplotter'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('continents'); ?>" title="Continents"><i class="fas fa-globe-europe"></i> <?php echo lang('menu_continents'); ?></a></li>
							</ul>
						</li>
						<li class="nav-item dropdown"> <!-- AWARDS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo lang('menu_awards'); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('awards/cq'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_cq'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('awards/itu'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_itu'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('awards/dxcc'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_dxcc'); ?></a></li>
								<div class="dropdown-divider"></div>
								
								
								<li><a class="dropdown-item" href="<?php echo site_url('awards/wwff'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_wwff'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('awards/sig'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_sig'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('awards/vucc'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_vucc'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('awards/ffma'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_ffma'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-trophy"></i> xOTA</a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/sota'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_sota'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/iota'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_iota'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/pota'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_pota'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇦 <?php echo lang('menu_canada'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/rac'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_rac'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇩🇪 <?php echo lang('menu_germany'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/dok'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_dok'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/dl'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_dl_gridmaster'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇬🇧 <?php echo lang('menu_great_britain'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wab'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_wab'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇯🇵 <?php echo lang('menu_japan'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/waja'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_waja'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/jcc'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_jcc'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/ja'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_ja_gridmaster'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇱🇺 <?php echo lang('menu_luxemburg'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/lx'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_lx_gridmaster'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇭 <?php echo lang('menu_switzerland'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/helvetia'); ?>"><i class="fas fa-trophy"></i> H26</a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇺🇸 <?php echo lang('menu_usa'); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/counties'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_us_counties'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/was'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_was'); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/us'); ?>"><i class="fas fa-trophy"></i> <?php echo lang('menu_us_gridmaster'); ?></a></li>
									</ul>
								</li>
							</ul>
						</li>

						<li class="nav-item dropdown"> <!-- TOOLS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo lang('menu_tools'); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('dxcalendar'); ?>" title="DX Calendar"><i class="fas fa-calendar"></i> <?php echo lang('menu_dx_calendar'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contestcalendar'); ?>" title="Contest Calendar"><i class="fas fa-calendar"></i> <?php echo lang('menu_contest_calendar'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('hamsat'); ?>" title="Hams.at"><i class="fas fa-list"></i> Hams.at</a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('bandmap/list'); ?>" title="Bandmap"><i class="fa fa-id-card"></i> <?php echo lang('menu_bandmap'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('sattimers'); ?>" title="SAT Timers"><i class="fas fa-satellite"></i> <?php echo lang('menu_sat_timers'); ?></a></li>
							</ul>
						</li>
					<?php } ?>
					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') == 99)) { ?> <!-- ADMIN -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" title="<?php echo lang('menu_admin'); ?>"><i class="fas fa-users-cog"></i></a>

							<div class="dropdown-menu header-dropdown">
								<a class="dropdown-item" href="<?php echo site_url('user'); ?>" title="Manage user accounts"><i class="fas fa-user"></i> <?php echo lang('menu_user_account'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('options'); ?>" title="Manage global options"><i class="fas fa-cog"></i> <?php echo lang('menu_global_options'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('mode'); ?>" title="Manage QSO modes"><i class="fas fa-broadcast-tower"></i> <?php echo lang('menu_modes'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('contesting/add'); ?>" title="Manage Contest names"><i class="fas fa-broadcast-tower"></i> <?php echo lang('menu_contests'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('satellite'); ?>" title="Manage Satellites"><i class="fas fa-satellite"></i> <?php echo lang('menu_satellites'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('themes'); ?>" title="Manage Themes"><i class="fas fa-cog"></i> <?php echo lang('menu_themes'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('backup'); ?>" title="Backup Wavelog content"><i class="fas fa-save"></i> <?php echo lang('menu_backup'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('update'); ?>" title="Update Country Files"><i class="fas fa-sync"></i> <?php echo lang('menu_update_country_files'); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('cron'); ?>" title="Cron Manager"><i class="fas fa-clock"></i> Cron Manager</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('debug'); ?>" title="Debug Information"><i class="fas fa-tools"></i> <?php echo lang('menu_debug_information'); ?></a>
							</div>
						</li>
					<?php } ?>
				</ul>

				<ul class="navbar-nav navbar-nav-right">

					<?php if ($this->session->userdata('user_quicklog')  == 1) { ?> <!-- QUICKLOG/SEARCHBAR -->
						<script>
							function submitForm(action) {
								var form = document.getElementById('quicklog-form');
								var input = document.getElementById('quicklog-input');
								if (action === 'search') {
									form.action = "<?php echo site_url('search'); ?>";
									form.method = "post";
								}
								form.submit();
							}

							function logQuicklog() {
								if (localStorage.getItem("quicklogCallsign") !== "") {
									localStorage.removeItem("quicklogCallsign");
								}
								localStorage.setItem("quicklogCallsign", $("input[name='callsign']").val());
								window.open("<?php echo site_url('qso?manual=0'); ?>", "_self");
							}
						</script>
						<?php if ($this->session->userdata('user_quicklog_enter')  == 1) { ?>
							<script>
								function handleKeyPress(event) {
									if (event.key === 'Enter') {
										submitForm('search'); // Treat Enter key press as clicking the 'quicksearch-search' button
									}
								}
							</script>
						<?php } else { ?>
							<script>
								function handleKeyPress(event) {
									if (event.key === 'Enter') {
										logQuicklog(); // Treat Enter key press as clicking the 'quicksearch-log' button
									}
								}
							</script>
						<?php } ?>
						<form id="quicklog-form" class="d-flex align-items-center me-3" onsubmit="return false;">
							<div class="input-group">
								<input class="form-control border" id="nav-bar-search-input" type="text" name="callsign" placeholder="<?php echo lang('menu_search_text_quicklog'); ?>" aria-label="Quicklog" onkeypress="handleKeyPress(event)">

								<button title="<?php echo lang('menu_search_button_qicksearch_log'); ?>" class="btn btn-outline-success border" type="button" onclick="logQuicklog()"><i class="fas fa-plus"></i></button>
								<button title="<?php echo lang('menu_search_button'); ?>" class="btn btn-outline-success border" type="button" onclick="submitForm('search')"><i class="fas fa-search"></i></button>
							</div>
						</form>
					<?php } else { ?>
						<form id="searchbar-form" method="post" class="d-flex align-items-center me-2" action="<?php echo site_url('search'); ?>">
							<div class="input-group">
								<input class="form-control border" id="nav-bar-search-input" type="search" name="callsign" placeholder="<?php echo lang('menu_search_text'); ?>" aria-label="Search">
								<button title="<?php echo lang('menu_search_button'); ?>" class="btn btn-outline-success border" type="submit"><i class="fas fa-search"></i></button>
							</div>
						</form>
					<?php } ?>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
						<!-- Logged in Content-->
					<?php } else { ?>
						<!-- Not Logged In-->
						<form method="post" action="<?php echo site_url('user/login'); ?>" style="padding-left: 5px;" class="form-inline">
							<input class="form-control me-sm-2" type="text" name="user_name" placeholder="Username" aria-label="Username">
							<input class="form-control me-sm-2" type="password" name="user_password" placeholder="Password" aria-label="Password">
							<input type="hidden" name="id" value="<?php echo $this->uri->segment(3); ?>" />
							<button class="btn btn-outline-success me-sm-2" type="submit"><?php echo lang('menu_login_button'); ?></button>
						</form>
					<?php } ?>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>

						<!-- Logged in As -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-user"></i> <?php echo $this->session->userdata('user_callsign'); ?></a>

							<ul class="dropdown-menu dropdown-menu-right header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('user/edit') . "/" . $this->session->userdata('user_id'); ?>" title="Account"><i class="fas fa-user"></i> <?php echo lang('menu_account'); ?></a></li>
								<?php
								$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name' => 'locations_quickswitch'))->row()->option_value ?? 'false');
								if ($quickswitch_enabled != 'true') {
								?>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station setup"><i class="fas fa-home"></i> <?php echo lang('menu_station_setup'); ?></a></li>
								<?php } ?>
								<li><a class="dropdown-item" href="<?php echo site_url('band'); ?>" title="Manage Bands"><i class="fas fa-cog"></i> <?php echo lang('menu_bands'); ?></a></li>


								<div class="dropdown-divider"></div>

								<li><a class="dropdown-item" href="<?php echo site_url('adif'); ?>" title="Amateur Data Interchange Format (ADIF) import / export"><i class="fas fa-sync"></i> <?php echo lang('menu_adif_import_export'); ?></a></li>

								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?php echo lang('menu_other_export'); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('kmlexport'); ?>" title="KML Export for Google Earth"><i class="fas fa-sync"></i> <?php echo lang('menu_kml_export'); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('dxatlas'); ?>" title="DX Atlas Gridsquare Export"><i class="fas fa-sync"></i> <?php echo lang('menu_dx_atlas_gridsquare_export'); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('csv'); ?>" title="SOTA CSV Export"><i class="fas fa-sync"></i> <?php echo lang('menu_sota_csv_export'); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cabrillo'); ?>" title="Cabrillo Export"><i class="fas fa-sync"></i> <?php echo lang('menu_cabrillo_export'); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cfdexport'); ?>" title="CFD Export"><i class="fas fa-sync"></i> <?php echo lang('menu_cfd_export'); ?></a></li>
									</ul>
								</li>

								<div class="dropdown-divider"></div>

								<?php
								$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
								if ($logbooks_locations_array) {
									$location_list = "'" . implode("','", $logbooks_locations_array) . "'";
								} else {
									$location_list = null;
								}

								$oqrs_requests = $this->oqrs_model->oqrs_requests($location_list);
								?>
								<li><a class="dropdown-item" href="<?php echo site_url('oqrs/requests'); ?>" title="OQRS Requests"><i class="fa fa-id-card"></i> <?php echo lang('menu_oqrs_requests'); ?> <?php if ($oqrs_requests > 0) {
																																																				echo "<span class=\"badge text-bg-light\">" . $oqrs_requests . "</span>";
																																																			} ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('qslprint'); ?>" title="Print Requested QSLs"><i class="fas fa-print"></i> <?php echo lang('menu_print_requested_qsls'); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('labels'); ?>" title="Label setup"><i class="fas fa-print"></i> <?php echo lang('menu_labels'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?php echo lang('menu_third_party_services'); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('lotw'); ?>" title="Synchronise with Logbook of the World (LoTW)"><i class="fas fa-sync"></i> <?php echo lang('menu_logbook_of_the_world'); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('eqsl/import'); ?>" title="eQSL import / export"><i class="fas fa-sync"></i> <?php echo lang('menu_eqsl_import_export'); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('hrdlog/export'); ?>" title="Upload to HRDLog.net logbook"><i class="fas fa-sync"></i> <?php echo lang('menu_hrd_logbook'); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('qrz/export'); ?>" title="Upload to QRZ.com logbook"><i class="fas fa-sync"></i> <?php echo lang('menu_qrz_logbook'); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('webadif/export'); ?>" title="Upload to webADIF"><i class="fas fa-sync"></i> <?php echo lang('menu_qo_100_dx_club_upload'); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('api/help'); ?>" title="Manage API keys"><i class="fas fa-key"></i> <?php echo lang('menu_api_keys'); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('radio'); ?>" title="Interface with one or more radios"><i class="fas fa-broadcast-tower"></i> <?php echo lang('menu_hardware_interfaces'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="javascript:displayVersionDialog();" title="Version Information"><i class="fas fa-star"></i> <?php echo lang('options_version_dialog'); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/wiki" title="Help"><i class="fas fa-question"></i> <?php echo lang('menu_help'); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/discussions" title="Forum"><i class="far fa-comment-dots"></i> <?php echo lang('menu_forum'); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('user/logout'); ?>" title="Logout"><i class="fas fa-sign-out-alt"></i> <?php echo lang('menu_logout'); ?></a></li>
							</ul>
						</li>
						<?php
						if ($quickswitch_enabled == 'true') { ?>
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-map-marker-alt"></i> | <i class="fas fa-book"></i></a>
								<ul class="dropdown-menu dropdown-menu-right header-dropdown">
									<li><a class="dropdown-item disabled"><?php echo lang('menu_select_location'); ?>:</a></li>
									<?php
									// let's get all stations for the logged in user
									$all_user_locations = $this->stations->all_of_user($this->session->userdata('user_id'));

									// and the set favourites as array
									$location_favorites_result = $this->user_options_model->get_options('station_location', array('option_name' => 'is_favorite', 'option_value' => 'true'));
									$location_favorites = $location_favorites_result->result_array();

									// also we need the current active station
									$current_active_location = $this->stations->find_active();

									// iterate through all available stations
									foreach ($all_user_locations->result() as $row) {
										// get information about this station like the name and the station id
										$profile_info = $this->stations->profile($row->station_id)->row();
										$station_profile_name = ($profile_info) ? $profile_info->station_profile_name : 'Unknown Location';
										$station_id = $row->station_id;

										// the active badge, not shown by default
										$active_badge = '<span id="quickswitcher_active_badge_' . $station_id . '" class="badge bg-success ms-2 d-none">' . lang('general_word_active') . '</span>';

										// only continue if the station id is a favourite and show the station in the list
										$is_favorite = false;
										foreach ($location_favorites as $favorite) {
											if ($favorite['option_value'] == true && $favorite['option_key'] == $station_id) {
												$is_favorite = true;
												break;
											}
										}

										if ($is_favorite) { ?>
											<li id="quickswitcher_list_item_<?php echo $station_id; ?>">
												<a id="quickswitcher_list_button_<?php echo $station_id; ?>" type="button" onclick="set_active_loc_quickswitcher('<?php echo $station_id; ?>')" class="dropdown-item quickswitcher">
													<i class="fas fa-map-marker-alt me-2"></i><?php echo $station_profile_name; echo $active_badge; ?>
												</a>
											</li>
										<?php }
									} ?>
									<div class="dropdown-divider"></div>
									<li><a class="dropdown-item quickswitcher disabled"><?php echo lang('gen_hamradio_active_logbook'); ?>:<span class="badge text-bg-info ms-1"><?php echo $this->logbooks_model->find_name($this->session->userdata('active_station_logbook')); ?></span></a></li>
									<div class="dropdown-divider"></div>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station locations"><?php echo lang('menu_station_setup'); ?>...</a></li>
								</ul>
							</li>
						<?php }

						// Can add extra menu items by defining them in options. The format is json.
						// Useful to add extra things in Wavelog without the need for modifying files. If you add extras, these files will not be overwritten when updating.
						//
						// The menu items will be displayed to the top right under extras.
						//
						// Example:
						// INSERT INTO options (option_name,option_value,autoload) VALUES
						// 	('menuitems','[
						// {
						// 		"url":"gridmap",
						// 		"text":"Gridmap",
						// 		"icon":"fa-globe-europe"
						// },
						// {
						// 		"url":"gallery",
						// 		"text":"Gallery",
						// 		"icon":"fa-globe-europe"
						// }
						// ]','yes');

						if ($this->optionslib->get_option('menuitems')) { ?>
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo lang('menu_extras'); ?></a>
								<div class="dropdown-menu header-dropdown">
									<?php
									foreach (json_decode($this->optionslib->get_option('menuitems')) as $item) {
										echo '<a class="dropdown-item" href="' . site_url($item->url) . '" title="' . $item->text . '"><i class="fas ' . $item->icon . '"></i> ' . $item->text . '</a>';
									}
									?>
								</div>
							</li>
						<?php } ?>
					<?php } ?>

				</ul>
			</div>
		</div>
	</nav>
	<script>
		let headerMenu = document.getElementById('header-menu');
		let dropdowns = document.querySelectorAll('.dropdown-toggle');

		dropdowns.forEach((dd) => {
			dd.addEventListener('click', function(e) {
				if (headerMenu.clientWidth < 992) {
					var el = this.nextElementSibling;
					el.style.display = el.style.display === 'block' ? 'none' : 'block';
				}
			});
		});
	</script>
