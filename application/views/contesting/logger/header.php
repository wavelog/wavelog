<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="<?php echo base_url(); ?>manifest.json" />

    <!-- TODO: Do we need this? -->
    <!-- <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/buttons.dataTables.min.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/datatables.min.css" /> -->

    <!-- Bootstrap CSS -->
    <?php
    $theme = $this->optionslib->get_theme();
    if ($theme) { ?>
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/bootstrap-multiselect.css'); ?>">
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $theme . '/bootstrap.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/general.css'); ?>">
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/selectize.bootstrap4.css'); ?>" />
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/bootstrap-dialog.css'); ?>" />
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $theme . '/overrides.css'); ?>">
        <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/contesting/contesting.css'); ?>">
    <?php } ?>

    <?php
    // Load component-specific CSS files if they exist
    foreach (array_keys($components) as $component) {
        $componentPath = __DIR__ . "/../../../../assets/css/contesting/components/" . $component . ".css";
        if (file_exists($componentPath)) { ?>
            <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/contesting/components/' . $component . '.css'); ?>">
    <?php }
    }
    ?>

    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/fontawesome/css/all.min.css'); ?>">

    <!-- Maps -->
    <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/js/leaflet/leaflet.css'); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/js/leaflet/Control.FullScreen.css'); ?>" />

    <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/css/loading.min.css'); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/css/ldbtn.min.css'); ?>" />

    <?php if (file_exists(APPPATH . '../assets/css/custom.css')) {
        echo '<link rel="stylesheet" href="' . $this->paths->cache_buster('/assets/css/custom.css') . '">';
    } ?>

    <?php if (file_exists(APPPATH . '../assets/js/sections/custom.js')) {
        echo '<script src="' . $this->paths->cache_buster('/assets/js/sections/custom.js') . '"></script>';
    } ?>

    <link rel="icon" href="<?php echo base_url(); ?>favicon.ico">

    <title>
        <?php if (isset($page_title)) {
            echo $page_title . " - Wavelog";
        } else {
            echo "WL Contest Engine"; // brand name. We keep this ;-)
        } ?>
    </title>

    <?php if ($this->session->userdata('isWinkeyEnabled')): ?>
    <script src="<?php echo $this->paths->cache_buster('/assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <?php endif; ?>
</head>

<body>