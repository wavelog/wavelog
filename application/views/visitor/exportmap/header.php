<!doctype html>
<html lang="<?php echo $language['code']; ?>">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <?php if ($this->optionslib->get_theme()) { ?>
    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $this->optionslib->get_theme() . '/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/general.css'); ?>">
    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $this->optionslib->get_theme() . '/overrides.css'); ?>">
  <?php } ?>

  <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/fontawesome/css/all.min.css'); ?>">

  <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/js/leaflet/leaflet.css'); ?>" />

  <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/css/loading.min.css'); ?>" />
  <link rel="stylesheet" type="text/css" href="<?php echo $this->paths->cache_buster('/assets/css/ldbtn.min.css'); ?>" />

  <link rel="icon" href="<?php echo $this->paths->cache_buster('/favicon.ico'); ?>">

  <title><?php if (isset($page_title)) { echo $page_title; } ?> - Wavelog</title>
</head>

<body>