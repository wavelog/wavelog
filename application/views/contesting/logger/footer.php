<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>"; // Base URL
</script>

<?php
// Load Contest Engine as ES6 Module - all dependencies are auto-imported
$appScript = 'assets/js/sections/contesting/contest_engine/app.js?' . filemtime(realpath(__DIR__ . "/../../../../assets/js/sections/contesting/contest_engine/app.js"));
?>
<script type="module" src="<?php echo base_url() . $appScript; ?>"></script>

</body>
<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 10100;"></div>

</html>