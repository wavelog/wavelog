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
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-multiselect.css">
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
                	echo "var activeStationTXPower = '".xss_clean($profile_info->station_power ?? 0)."';\n";
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
				<span class="badge text-bg-danger"><?= __("Developer Mode"); ?></span>
			<?php } ?>
			<?php if (ENVIRONMENT == "maintenance") { ?>
				<span class="badge text-bg-info"><?= __("Maintenance Mode"); ?></span>
			<?php } ?>

			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav navbar-nav-left">
					<li class="nav-item dropdown"> <!-- LOGBOOK -->
						<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"> <?= __("Logbook"); ?></a>
						<ul class="dropdown-menu header-dropdown">
							<li><a class="dropdown-item" href="<?php echo site_url('logbook'); ?>"><i class="fas fa-book"></i> <?= __("Overview"); ?></a></li>
							<div class="dropdown-divider"></div>
							<li><a class="dropdown-item" href="<?php echo site_url('logbookadvanced'); ?>"><i class="fas fa-book-open"></i> <?= __("Advanced"); ?></a></li>
							<div class="dropdown-divider"></div>
							<?php if (!($this->config->item('disable_qsl') ?? false)) { ?>
							<li><a class="dropdown-item" href="<?php echo site_url('qsl'); ?>" title="QSL"><i class="fa fa-id-card"></i> <?= __("View QSL Cards"); ?></a></li>
							<div class="dropdown-divider"></div>
							<?php } ?>
							<li><a class="dropdown-item" href="<?php echo site_url('eqsl'); ?>" title="eQSL"><i class="fa fa-id-card"></i> <?= __("View eQSL Cards"); ?></a></li>
						</ul>
					</li>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>
						<li class="nav-item dropdown"> <!-- QSO -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= __("QSO"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=0'); ?>" title="Log Live QSOs"><i class="fas fa-list"></i> <?= __("Live QSO"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('qso?manual=1'); ?>" title="Log QSO made in the past"><i class="fas fa-list"></i> <?= __("Post QSO"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('simplefle'); ?>" title="Simple Fast Log Entry"><i class="fas fa-list"></i> <?= __("Simple Fast Log Entry"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=0'); ?>" title="Live contest QSOs"><i class="fas fa-list"></i> <?= __("Live Contest Logging"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contesting?manual=1'); ?>" title="Post contest QSOs"><i class="fas fa-list"></i> <?= __("Post Contest Logging"); ?></a></li>
							</ul>
						</li>

						<?php if ($this->session->userdata('user_show_notes') == 1) { ?><!-- NOTES -->
							<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?= __("Notes"); ?></a>
						<?php } ?>

						<li class="nav-item dropdown"> <!-- ANALYTICS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= __("Analytics"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('statistics'); ?>" title="Statistics"><i class="fas fa-chart-area"></i> <?= __("Statistics"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('statistics/qslstats'); ?>" title="QSL Statistics"><i class="fas fa-chart-area"></i> <?= __("QSL Statistics"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('gridmap'); ?>" title="Gridmap"><i class="fas fa-globe-europe"></i> <?= __("Gridsquare Map"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activated_gridmap'); ?>" title="Activated Gridsquares"><i class="fas fa-globe-europe"></i> <?= __("Activated Gridsquares"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('activators'); ?>" title="Gridsquare Activators"><i class="fas fa-globe-europe"></i> <?= __("Gridsquare Activators"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('distances'); ?>" title="Distances"><i class="fas fa-chart-area"></i> <?= __("Distances Worked"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('distancerecords'); ?>" title="Satellite Distance records"><i class="fas fa-chart-area"></i> <?= __("Satellite Distance Records"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('dayswithqso'); ?>" title="Days with QSOs"><i class="fas fa-chart-area"></i> <?= __("Days with QSOs"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeline'); ?>" title="Timeline"><i class="fas fa-chart-area"></i> <?= __("Timeline"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('accumulated'); ?>" title="Accumulated Statistics"><i class="fas fa-chart-area"></i> <?= __("Accumulated Statistics"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('timeplotter'); ?>" title="View time when worked"><i class="fas fa-chart-area"></i> <?= __("Timeplotter"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('continents'); ?>" title="Continents"><i class="fas fa-globe-europe"></i> <?= __("Continents"); ?></a></li>
							</ul>
						</li>
						<li class="nav-item dropdown"> <!-- AWARDS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= __("Awards"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-globe"></i> <?= __("International"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/cq'); ?>"><i class="fas fa-trophy"></i> <?= __("CQ"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/dxcc'); ?>"><i class="fas fa-trophy"></i> <?= __("DXCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/itu'); ?>"><i class="fas fa-trophy"></i> <?= __("ITU"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/sig'); ?>"><i class="fas fa-trophy"></i> <?= __("SIG"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/vucc'); ?>"><i class="fas fa-trophy"></i> <?= __("VUCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wac'); ?>"><i class="fas fa-trophy"></i> <?= __("Worked All Continents (WAC)"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wwff'); ?>"><i class="fas fa-trophy"></i> <?= __("WWFF"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-trophy"></i> xOTA</a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/sota'); ?>"><i class="fas fa-trophy"></i> <?= __("SOTA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/iota'); ?>"><i class="fas fa-trophy"></i> <?= __("IOTA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/pota'); ?>"><i class="fas fa-trophy"></i> <?= __("POTA"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇦 <?= __("Canada"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/rac'); ?>"><i class="fas fa-trophy"></i> <?= __("RAC"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇩🇪 <?= __("Germany"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/dok'); ?>"><i class="fas fa-trophy"></i> <?= __("DOK"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/dl'); ?>"><i class="fas fa-trophy"></i> <?= __("DL Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇬🇧 <?= __("Great Britain"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/wab'); ?>"><i class="fas fa-trophy"></i> <?= __("WAB"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇯🇵 <?= __("Japan"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/waja'); ?>"><i class="fas fa-trophy"></i> <?= __("WAJA"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/jcc'); ?>"><i class="fas fa-trophy"></i> <?= __("JCC"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/ja'); ?>"><i class="fas fa-trophy"></i> <?= __("JA Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇱🇺 <?= __("Luxemburg"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/lx'); ?>"><i class="fas fa-trophy"></i> <?= __("LX Gridmaster"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇨🇭 <?= __("Switzerland"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/helvetia'); ?>"><i class="fas fa-trophy"></i> H26</a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" href="#">🇺🇸 <?= __("USA"); ?></a>
									<ul class="submenu dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('awards/counties'); ?>"><i class="fas fa-trophy"></i> <?= __("US Counties"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/was'); ?>"><i class="fas fa-trophy"></i> <?= __("WAS"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/gridmaster/us'); ?>"><i class="fas fa-trophy"></i> <?= __("US Gridmaster"); ?></a></li>
										<div class="dropdown-divider"></div>
										<li><a class="dropdown-item" href="<?php echo site_url('awards/ffma'); ?>"><i class="fas fa-trophy"></i> <?= __("Fred Fish Memorial Award"); ?></a></li>
									</ul>
								</li>
							</ul>
						</li>

						<li class="nav-item dropdown"> <!-- TOOLS -->
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= __("Tools"); ?></a>
							<ul class="dropdown-menu header-dropdown">
								<li><a class="dropdown-item" href="<?php echo site_url('dxcalendar'); ?>" title="DX Calendar"><i class="fas fa-calendar"></i> <?= __("DX Calendar"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('contestcalendar'); ?>" title="Contest Calendar"><i class="fas fa-calendar"></i> <?= __("Contest Calendar"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('hamsat'); ?>" title="Hams.at"><i class="fas fa-list"></i> Hams.at</a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('bandmap/list'); ?>" title="Bandmap"><i class="fa fa-id-card"></i> <?= __("Bandmap"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('sattimers'); ?>" title="SAT Timers"><i class="fas fa-satellite"></i> <?= __("SAT Timers"); ?></a></li>
								<?php if (ENVIRONMENT == "development") { ?>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="<?php echo site_url('satellite/flightpath'); ?>" title="Manage Satellites"><i class="fas fa-satellite"></i> <?= __("Satellite Flightpath"); ?> <span class="badge text-bg-danger">Beta</span></a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="<?php echo site_url('satellite/pass'); ?>" title="Search for satellite passes"><i class="fas fa-satellite"></i> <?= __("Satellite Pass"); ?> <span class="badge text-bg-danger">Beta</span></a>
								<?php } ?>
							</ul>
						</li>
					<?php } ?>
					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') == 99)) { ?> <!-- ADMIN -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" title="<?= __("Admin"); ?>"><i class="fas fa-users-cog"></i></a>

							<div class="dropdown-menu header-dropdown">
								<a class="dropdown-item" href="<?php echo site_url('user'); ?>" title="Manage user accounts"><i class="fas fa-user"></i> <?= __("User Accounts"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('options'); ?>" title="Manage global options"><i class="fas fa-cog"></i> <?= __("Global Options"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('mode'); ?>" title="Manage QSO modes"><i class="fas fa-broadcast-tower"></i> <?= __("Modes"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('contesting/add'); ?>" title="Manage Contest names"><i class="fas fa-broadcast-tower"></i> <?= __("Contests"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('satellite'); ?>" title="Manage Satellites"><i class="fas fa-satellite"></i> <?= __("Satellites"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('themes'); ?>" title="Manage Themes"><i class="fas fa-cog"></i> <?= __("Themes"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('backup'); ?>" title="Backup Wavelog content"><i class="fas fa-save"></i> <?= __("Backup"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('update'); ?>" title="Update Country Files"><i class="fas fa-sync"></i> <?= __("Update Country Files"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('cron'); ?>" title="Cron Manager"><i class="fas fa-clock"></i> <?= __("Cron Manager"); ?></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo site_url('debug'); ?>" title="Debug Information"><i class="fas fa-tools"></i> <?= __("Debug Information"); ?></a>
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
								<input class="form-control border" id="nav-bar-search-input" type="text" name="callsign" placeholder="<?= __("Add/Search Callsign"); ?>" aria-label="Quicklog" onkeypress="handleKeyPress(event)">

								<button title="<?= __("Log"); ?>" class="btn btn-outline-success border" type="button" onclick="logQuicklog()"><i class="fas fa-plus"></i></button>
								<button title="<?= __("Search"); ?>" class="btn btn-outline-success border" type="button" onclick="submitForm('search')"><i class="fas fa-search"></i></button>
							</div>
						</form>
					<?php } else { ?>
						<form id="searchbar-form" method="post" class="d-flex align-items-center me-2" action="<?php echo site_url('search'); ?>">
							<div class="input-group">
								<input class="form-control border" id="nav-bar-search-input" type="search" name="callsign" placeholder="<?= __("Search Callsign"); ?>" aria-label="Search">
								<button title="<?= __("Search"); ?>" class="btn btn-outline-success border" type="submit"><i class="fas fa-search"></i></button>
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
							<button class="btn btn-outline-success me-sm-2" type="submit"><?= __("Login"); ?></button>
						</form>
					<?php } ?>

					<?php if (($this->config->item('use_auth')) && ($this->session->userdata('user_type') >= 2)) { ?>

						<!-- Logged in As -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-user"></i> <?php echo str_replace("0","&Oslash;", strtoupper($this->session->userdata('user_callsign'))); ?></a>

							<ul class="dropdown-menu dropdown-menu-right header-dropdown">
								<?php
								if (!$this->config->item('special_callsign') ||
									$this->session->userdata('user_type') == '99' ||
									($this->config->item('special_callsign') && !$this->config->item('sc_hide_usermenu'))) { ?>
									<li><a class="dropdown-item" href="<?php echo site_url('user/edit') . "/" . $this->session->userdata('user_id'); ?>" title="Account"><i class="fas fa-user"></i> <?= __("Account"); ?></a></li>
								<?php } ?>
								<?php
								$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name' => 'locations_quickswitch'))->row()->option_value ?? 'false');
								if ($quickswitch_enabled != 'true') {
								?>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station setup"><i class="fas fa-home"></i> <?= __("Station Setup"); ?></a></li>
								<?php } ?>
								<li><a class="dropdown-item" href="<?php echo site_url('band'); ?>" title="Manage Bands"><i class="fas fa-cog"></i> <?= __("Bands"); ?></a></li>


								<div class="dropdown-divider"></div>

								<li><a class="dropdown-item" href="<?php echo site_url('adif'); ?>" title="Amateur Data Interchange Format (ADIF) import / export"><i class="fas fa-sync"></i> <?= __("ADIF Import / Export"); ?></a></li>

								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?= __("Other Export Options"); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('kmlexport'); ?>" title="KML Export for Google Earth"><i class="fas fa-sync"></i> <?= __("KML Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('dxatlas'); ?>" title="DX Atlas Gridsquare Export"><i class="fas fa-sync"></i> <?= __("DX Atlas Gridsquare Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('csv'); ?>" title="SOTA CSV Export"><i class="fas fa-sync"></i> <?= __("SOTA CSV Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cabrillo'); ?>" title="Cabrillo Export"><i class="fas fa-sync"></i> <?= __("Cabrillo Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('reg1test'); ?>" title="EDI Export"><i class="fas fa-sync"></i> <?= __("EDI Export"); ?></a></li>

										<li><a class="dropdown-item" href="<?php echo site_url('cfdexport'); ?>" title="CFD Export"><i class="fas fa-sync"></i> <?= __("CFD Export"); ?></a></li>
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

								if (!($this->config->item('disable_oqrs') ?? false)) {
									$oqrs_requests = $this->oqrs_model->oqrs_requests($location_list);
									?>
								<li><a class="dropdown-item" href="<?php echo site_url('oqrs/requests'); ?>" title="OQRS Requests"><i class="fa fa-id-card"></i> <?= __("OQRS Requests"); ?>
									<?php if ($oqrs_requests > 0) {
									echo "<span id=\"oqrs_requests\" class=\"badge text-bg-light\">" . $oqrs_requests . "</span>";
									} ?></a></li>
								<?php } ?>
								<li><a class="dropdown-item" href="<?php echo site_url('qslprint'); ?>" title="<?= __("QSL Queue"); ?>"><i class="fas fa-print"></i> <?= __("QSL Queue"); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('labels'); ?>" title="Label setup"><i class="fas fa-print"></i> <?= __("Labels"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-sync"></i> <?= __("Third-Party Services"); ?></a>
									<ul class="submenu submenu-left dropdown-menu">
										<li><a class="dropdown-item" href="<?php echo site_url('lotw'); ?>" title="Synchronise with Logbook of the World (LoTW)"><i class="fas fa-sync"></i> <?= __("Logbook of the World"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('eqsl/import'); ?>" title="eQSL import / export"><i class="fas fa-sync"></i> <?= __("eQSL Import / Export"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('hrdlog/export'); ?>" title="Upload to HRDLog.net logbook"><i class="fas fa-sync"></i> <?= __("HRDLog Logbook"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('qrz/export'); ?>" title="Upload to QRZ.com logbook"><i class="fas fa-sync"></i> <?= __("QRZ Logbook"); ?></a></li>
										<li><a class="dropdown-item" href="<?php echo site_url('webadif/export'); ?>" title="Upload to webADIF"><i class="fas fa-sync"></i> <?= __("QO-100 Dx Club Upload"); ?></a></li>
									</ul>
								</li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('api/help'); ?>" title="Manage API keys"><i class="fas fa-key"></i> <?= __("API Keys"); ?></a></li>
								<li><a class="dropdown-item" href="<?php echo site_url('radio'); ?>" title="Interface with one or more radios"><i class="fas fa-broadcast-tower"></i> <?= __("Hardware Interfaces"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="javascript:displayVersionDialog();" title="Version Information"><i class="fas fa-star"></i> <?= __("Version Info"); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/wiki" title="Help"><i class="fas fa-question"></i> <?= __("Help"); ?></a></li>
								<li><a class="dropdown-item" target="_blank" href="https://github.com/wavelog/wavelog/discussions" title="Forum"><i class="far fa-comment-dots"></i> <?= __("Forum"); ?></a></li>
								<div class="dropdown-divider"></div>
								<li><a class="dropdown-item" href="<?php echo site_url('user/logout'); ?>" title="Logout"><i class="fas fa-sign-out-alt"></i> <?= __("Logout"); ?></a></li>
							</ul>
						</li>
						<?php
						if ($quickswitch_enabled == 'true') { ?>
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="fas fa-map-marker-alt"></i> | <i class="fas fa-book"></i></a>
								<ul class="dropdown-menu dropdown-menu-right header-dropdown">
									<li><a class="dropdown-item disabled"><?= __("Select a Location"); ?>:</a></li>
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
									<li><a class="dropdown-item quickswitcher disabled"><?= __("Active Logbook"); ?>:<span class="badge text-bg-info ms-1"><?php echo $this->logbooks_model->find_name($this->session->userdata('active_station_logbook')); ?></span></a></li>
									<div class="dropdown-divider"></div>
									<li><a class="dropdown-item" href="<?php echo site_url('stationsetup'); ?>" title="Manage station locations"><?= __("Station Setup"); ?>...</a></li>
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
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= __("Extras"); ?></a>
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
