<html>
<head>
<title>404 Page Not Found</title>
	<!-- Bootstrap CSS -->
	<?php
	$CI =& get_instance();
	$theme = $CI->optionslib->get_theme();
	if ($theme) { ?>
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-multiselect.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/bootstrap.min.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/selectize.bootstrap4.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap-dialog.css" />
		<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/overrides.css">
	<?php } ?>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .error-container {
            width: 100%;
            max-width: 430px;
            padding: 15px;
            margin: auto;
        }

        .error-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 2rem;
        }

        .error-card {
            border: none;
            border-radius: 8px;
        }

        .error-icon {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .back-link {
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="error-container">
        
        <img src="<?php echo base_url(); ?>assets/logo/<?php echo $CI->optionslib->get_logo('header_logo'); ?>.png" class="mx-auto d-block mainLogo" alt="">
        
        <div class="card error-card shadow-sm">
            <div class="card-body text-center">
                <div class="error-icon">⚠️</div>
                
                <h2 class="card-title h4 mb-3 text-danger">
                    <?php echo $heading; ?>
                </h2>
                
                <p class="card-text text-muted mb-4">
                    <?php echo $message; ?>
                </p>
            </div>
        </div>
    </main>
</body>
</html>