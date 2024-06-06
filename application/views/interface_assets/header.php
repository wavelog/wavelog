<!doctype html>
<html lang="<?php echo $language['code']; ?>">

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
	<?php
	$theme = $this->optionslib->get_theme();
	if ($theme) { ?>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/selectize.bootstrap4.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-dialog.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/overrides.css">
	<?php } ?>

	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/fontawesome/css/all.min.css">

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

<body dir="<?php echo $language['direction']; ?>">
	<nav class="navbar navbar-expand-lg navbar-light bg-light main-nav" id="header-menu">
		<div class="container">
			<a class="navbar-brand" href="<?php echo site_url(); ?>"><img class="headerLogo" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo'); ?>.png" alt="Logo" /></a>
			<?php if (ENVIRONMENT == "development") { ?>
				<span class="badge text-bg-danger"><?php echo __("Developer Mode"); ?></span>
			<?php } ?>
			<?php if (ENVIRONMENT == "maintenance") { ?>
				<span class="badge text-bg-info">Maintenance</span>
			<?php } ?>

			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav navbar-nav-left">
					<li class="nav-item dropdown"> <!-- LOGBOOK -->
						<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"> <?php echo __("Logbook"); ?></a>
						<ul class="dropdown-menu header-dropdown">
							<li><a class="dropdown-item" href="<?php echo site_url('logbook'); ?>"><i class="fas fa-book"></i> <?php echo __("Overview"); ?></a></li>
							<div class="dropdown-divider"></div>
							<li><a class="dropdown-item" href="<?php echo site_url('logbookadvanced'); ?>"><i class="fas fa-book-open"></i> <?php echo __("Advanced"); ?></a></li>
							<div class="dropdown-divider"></div>
							<?php if (!($this->config->item('disable_qsl') ?? false)) { ?>
							<li><a class="dropdown-item" href="<?php echo site_url('qsl'); ?>" title="QSL"><i class="fa fa-id-card"></i> <?php echo __("View QSL Cards"); ?></a></li>
							<div class="dropdown-divider"></div>
							<?php } ?>
							<li><a class="dropdown-item" href="<?php echo site_url('eqsl'); ?>" title="eQSL"><i class="fa fa-id-card"></i> <?php echo __("View eQSL Cards"); ?></a></li>
						</ul>
					</li>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
						<li class="nav-item dropdown"> <!-- QSO -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo __("QSO"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=0'); ?>" title="Log Live QSOs"><i class="fas fa-list"></i> <?php echo __("Live QSO"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=1'); ?>" title="Log QSO made in the past"><i class="fas fa-list"></i> <?php echo __("Post QSO"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('simplefle'); ?>" title="Simple Fast Log Entry"><i class="fas fa-list"></i> <?php echo __("Simple Fast Log Entry"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=0'); ?>" title="Live contest QSOs"><i class="fas fa-list"></i> <?php echo __("Live Contest Logging"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=1'); ?>" title="Post contest QSOs"><i class="fas fa-list"></i> <?php echo __("Post Contest Logging"); ?></a></li>
							</ul>
						</li>

						<?php if ($this->session->userdata('user_show_notes') == 1) { ?><!-- NOTES -->
							<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?php echo __("Notes"); ?></a>
						<?php } ?>

						<li class="nav-item dropdown"> <!-- ANALYTICS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo __("Analytics"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('statistics'); ?>" title="Statistics"><i class="fas fa-chart-area"></i> <?php echo __("Statistics"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('gridmap'); ?>" title="Gridmap"><i class="fas fa-globe-europe"></i> <?php echo __("Gridsquare Map"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activated_gridmap'); ?>" title="Activated Gridsquares"><i class="fas fa-globe-europe"></i> <?php echo __("Activated Gridsquares"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activators'); ?>" title="Gridsquare Activators"><i class="fas fa-globe-europe"></i> <?php echo __("Gridsquare Activators"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('distances'); ?>" title="Distances"><i class="fas fa-chart-area"></i> <?php echo __("Distances Worked"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('dayswithqso'); ?>" title="Days with QSOs"><i class="fas fa-chart-area"></i> <?php echo __("Days with QSOs"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeline'); ?>" title="Timeline"><i class="fas fa-chart-area"></i> <?php echo __("Timeline"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('accumulated'); ?>" title="Accumulated Statistics"><i class="fas fa-chart-area"></i> <?php echo __("Accumulated Statistics"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeplotter'); ?>" title="View time when worked"><i class="fas fa-chart-area"></i> <?php echo __("Timeplotter"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('continents'); ?>" title="Continents"><i class="fas fa-globe-europe"></i> <?php echo __("Continents"); ?></a></li>
							</ul>
						</li>
						<li class="nav-item dropdown"> <!-- AWARDS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo __("Awards"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-globe"></i> International</a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/cq'); ?>"><i class="fas fa-trophy"></i> <?php echo __("CQ"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/dxcc'); ?>"><i class="fas fa-trophy"></i> <?php echo __("DXCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/itu'); ?>"><i class="fas fa-trophy"></i> <?php echo __("ITU"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/sig'); ?>"><i class="fas fa-trophy"></i> <?php echo __("SIG"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/vucc'); ?>"><i class="fas fa-trophy"></i> <?php echo __("VUCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wwff'); ?>"><i class="fas fa-trophy"></i> <?php echo __("WWFF"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-trophy"></i> xOTA</a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/sota'); ?>"><i class="fas fa-trophy"></i> <?php echo __("SOTA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/iota'); ?>"><i class="fas fa-trophy"></i> <?php echo __("IOTA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/pota'); ?>"><i class="fas fa-trophy"></i> <?php echo __("POTA"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇦 <?php echo __("Canada"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/rac'); ?>"><i class="fas fa-trophy"></i> <?php echo __("RAC"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇩🇪 <?php echo __("Germany"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/dok'); ?>"><i class="fas fa-trophy"></i> <?php echo __("DOK"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/dl'); ?>"><i class="fas fa-trophy"></i> <?php echo __("DL Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇬🇧 <?php echo __("Great Britain"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wab'); ?>"><i class="fas fa-trophy"></i> <?php echo __("WAB"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇯🇵 <?php echo __("Japan"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/waja'); ?>"><i class="fas fa-trophy"></i> <?php echo __("WAJA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/jcc'); ?>"><i class="fas fa-trophy"></i> <?php echo __("JCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/ja'); ?>"><i class="fas fa-trophy"></i> <?php echo __("JA Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇱🇺 <?php echo __("Luxemburg"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/lx'); ?>"><i class="fas fa-trophy"></i> <?php echo __("LX Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇭 <?php echo __("Switzerland"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/helvetia'); ?>"><i class="fas fa-trophy"></i> H26</a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇺🇸 <?php echo __("USA"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/counties'); ?>"><i class="fas fa-trophy"></i> <?php echo __("US Counties"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/was'); ?>"><i class="fas fa-trophy"></i> <?php echo __("WAS"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/us'); ?>"><i class="fas fa-trophy"></i> <?php echo __("US Gridmaster"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/ffma'); ?>"><i class="fas fa-trophy"></i> <?php echo __("Fred Fish Memorial Award"); ?></a></li>
									</ul>
								</li>
							</ul>
						</li>

						<li class="nav-item dropdown"> <!-- TOOLS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo __("Tools"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('dxcalendar'); ?>" title="DX Calendar"><i class="fas fa-calendar"></i> <?php echo __("DX Calendar"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contestcalendar'); ?>" title="Contest Calendar"><i class="fas fa-calendar"></i> <?php echo __("Contest Calendar"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('hamsat'); ?>" title="Hams.at"><i class="fas fa-list"></i> Hams.at</a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('bandmap/list'); ?>" title="Bandmap"><i class="fa fa-id-card"></i> <?php echo __("Bandmap"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('sattimers'); ?>" title="SAT Timers"><i class="fas fa-satellite"></i> <?php echo __("SAT Timers"); ?></a></li>
							</ul>
						</li>
					<?php } ?>
					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') == 99)) { ?> <!-- ADMIN -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" title="<?php echo __("Admin"); ?>"><i class="fas fa-users-cog"></i></a>

							<div class="dropdown-menu header-dropdown">
								<a class="dropdown-item" href="<?php echo site_url('user'); ?>" title="Manage user accounts"><i class="fas fa-user"></i> <?php echo __("User Accounts"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('options'); ?>" title="Manage global options"><i class="fas fa-cog"></i> <?php echo __("Global Options"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('mode'); ?>" title="Manage QSO modes"><i class="fas fa-broadcast-tower"></i> <?php echo __("Modes"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('contesting/add'); ?>" title="Manage Contest names"><i class="fas fa-broadcast-tower"></i> <?php echo __("Contests"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('satellite'); ?>" title="Manage Satellites"><i class="fas fa-satellite"></i> <?php echo __("Satellites"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('themes'); ?>" title="Manage Themes"><i class="fas fa-cog"></i> <?php echo __("Themes"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('backup'); ?>" title="Backup Wavelog content"><i class="fas fa-save"></i> <?php echo __("Backup"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('update'); ?>" title="Update Country Files"><i class="fas fa-sync"></i> <?php echo __("Update Country Files"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('cron'); ?>" title="Cron Manager"><i class="fas fa-clock"></i> Cron Manager</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('debug'); ?>" title="Debug Information"><i class="fas fa-tools"></i> <?php echo __("Debug Information"); ?></a>
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
								<input class="form-control border" id="nav-bar-search-input" type="text" name="callsign" placeholder="<?php echo __("Add/Search Callsign"); ?>" aria-label="Quicklog" onkeypress="handleKeyPress(event)">

								<button title="<?php echo __("Log"); ?>" class="btn btn-outline-success border" type="button" onclick="logQuicklog()"><i class="fas fa-plus"></i></button>
								<button title="<?php echo __("Search"); ?>" class="btn btn-outline-success border" type="button" onclick="submitForm('search')"><i class="fas fa-search"></i></button>
							</div>
						</form>
					<?php } else { ?>
						<form id="searchbar-form" method="post" class="d-flex align-items-center me-2" action="<?php echo site_url('search'); ?>">
							<div class="input-group">
								<input class="form-control border" id="nav-bar-search-input" type="search" name="callsign" placeholder="<?php echo __("Search Callsign"); ?>" aria-label="Search">
								<button title="<?php echo __("Search"); ?>" class="btn btn-outline-success border" type="submit"><i class="fas fa-search"></i></button>
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
							<button class="btn btn-outline-success me-sm-2" type="submit"><?php echo __("Login"); ?></button>
						</form>
					<?php } ?>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>

						<!-- Logged in As -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-user"></i> <?php echo $this->session->userdata('user_callsign'); ?></a>

							<ul class="dropdown-menu dropdown-menu-right header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('user/edit') . "/" . $this->session->userdata('user_id'); ?>" title="Account"><i class="fas fa-user"></i> <?php echo __("Account"); ?></a></li>
								<?php
								$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name' => 'locations_quickswitch'))->row()->option_value ?? 'false');
								if ($quickswitch_enabled != 'true') {
								?>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station setup"><i class="fas fa-home"></i> <?php echo __("Station Setup"); ?></a></li>
								<?php } ?>
								<li><a class="dropdown-item" href="<?php echo site_url('band'); ?>" title="Manage Bands"><i class="fas fa-cog"></i> <?php echo __("Bands"); ?></a></li>


								<div class="dropdown-divider"></div>

								<li><a class="dropdown-item" href="<?php echo site_url('adif'); ?>" title="Amateur Data Interchange Format (ADIF) import / export"><i class="fas fa-sync"></i> <?php echo __("ADIF Import / Export"); ?></a></li>

								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?php echo __("Other Export Options"); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('kmlexport'); ?>" title="KML Export for Google Earth"><i class="fas fa-sync"></i> <?php echo __("KML Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('dxatlas'); ?>" title="DX Atlas Gridsquare Export"><i class="fas fa-sync"></i> <?php echo __("DX Atlas Gridsquare Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('csv'); ?>" title="SOTA CSV Export"><i class="fas fa-sync"></i> <?php echo __("SOTA CSV Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cabrillo'); ?>" title="Cabrillo Export"><i class="fas fa-sync"></i> <?php echo __("Cabrillo Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cfdexport'); ?>" title="CFD Export"><i class="fas fa-sync"></i> <?php echo __("CFD Export"); ?></a></li>
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
								<li><a class="dropdown-item" href="<?php echo site_url('oqrs/requests'); ?>" title="OQRS Requests"><i class="fa fa-id-card"></i> <?php echo __("OQRS Requests"); ?> <?php if ($oqrs_requests > 0) {
																																																				echo "<span class=\"badge text-bg-light\">" . $oqrs_requests . "</span>";
																																																			} ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('qslprint'); ?>" title="Print Requested QSLs"><i class="fas fa-print"></i> <?php echo __("Print Requested QSLs"); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('labels'); ?>" title="Label setup"><i class="fas fa-print"></i> <?php echo __("Labels"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?php echo __("Third-Party Services"); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('lotw'); ?>" title="Synchronise with Logbook of the World (LoTW)"><i class="fas fa-sync"></i> <?php echo __("Logbook of the World"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('eqsl/import'); ?>" title="eQSL import / export"><i class="fas fa-sync"></i> <?php echo __("eQSL Import / Export"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('hrdlog/export'); ?>" title="Upload to HRDLog.net logbook"><i class="fas fa-sync"></i> <?php echo __("HRDLog Logbook"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('qrz/export'); ?>" title="Upload to QRZ.com logbook"><i class="fas fa-sync"></i> <?php echo __("QRZ Logbook"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('webadif/export'); ?>" title="Upload to webADIF"><i class="fas fa-sync"></i> <?php echo __("QO-100 Dx Club Upload"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('api/help'); ?>" title="Manage API keys"><i class="fas fa-key"></i> <?php echo __("API Keys"); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('radio'); ?>" title="Interface with one or more radios"><i class="fas fa-broadcast-tower"></i> <?php echo __("Hardware Interfaces"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="javascript:displayVersionDialog();" title="Version Information"><i class="fas fa-star"></i> <?php echo __("Version Info"); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/wiki" title="Help"><i class="fas fa-question"></i> <?php echo __("Help"); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/discussions" title="Forum"><i class="far fa-comment-dots"></i> <?php echo __("Forum"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('user/logout'); ?>" title="Logout"><i class="fas fa-sign-out-alt"></i> <?php echo __("Logout"); ?></a></li>
							</ul>
						</li>
						<?php
						if ($quickswitch_enabled == 'true') { ?>
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-map-marker-alt"></i> | <i class="fas fa-book"></i></a>
								<ul class="dropdown-menu dropdown-menu-right header-dropdown">
									<li><a class="dropdown-item disabled"><?php echo __("Select a Location"); ?>:</a></li>
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
										$active_badge = '<span id="quickswitcher_active_badge_' . $station_id . '" class="badge bg-success ms-2 d-none">' . __("Active") . '</span>';

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
									<li><a class="dropdown-item quickswitcher disabled"><?php echo __("Active Logbook"); ?>:<span class="badge text-bg-info ms-1"><?php echo $this->logbooks_model->find_name($this->session->userdata('active_station_logbook')); ?></span></a></li>
									<div class="dropdown-divider"></div>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station locations"><?php echo __("Station Setup"); ?>...</a></li>
								</ul>
							</li>
							<?php } ?>
						<?php
						$utc_headermenu = ($this->user_options_model->get_options('header_menu', array('option_name' => 'utc_headermenu'))->row()->option_value ?? 'false');
						if ($utc_headermenu == 'true') {
						?>
							<li class="nav-link disabled" id="utc_header_li">
								<a id="utc_header" style="width: 70px; display: inline-block;"></a>
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
						$menuitems = $this->optionslib->get_option('menuitems');

						if ($menuitems) { ?>
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?php echo __("Extras"); ?></a>
								<div class="dropdown-menu header-dropdown">
									<?php
									foreach (json_decode($menuitems) as $item) {
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
