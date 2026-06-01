<script type="text/javascript" src="<?php echo $this->paths->cache_buster('/assets/js/bootstrap.bundle.min.js'); ?>"></script>
<?php if ($this->session->userdata('isWinkeyEnabled')): ?>
<script src="<?php echo $this->paths->cache_buster('/assets/js/bootstrapdialog/js/bootstrap-dialog.min.js'); ?>"></script>
<script src="<?php echo $this->paths->cache_buster('/assets/js/winkey.js'); ?>"></script>
<?php endif; ?>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>"; // Base URL
    var wlversionhash = "<?php echo md5($this->optionslib->get_option('version')); ?>"; // Wavelog Version for cache busting in es6 imports
</script>
<?php
// define the contest_engine base path 
$ce = '/assets/js/sections/contesting/contest_engine/';

// define the core files
$core_files = [
    'data-store',
    'sync-engine',
    'transport-adapter',
    'ajax-transport',
    'ws-transport',
    'window-manager',
    'component-loader'
];

// now lets map the required files and add the filemtime version for cache busting in the importmap
$imports = [];

// core files
foreach ($core_files as $f) {
    $key   = base_url($ce . 'core/' . $f . '.js');          // absolute URL, no ?v= — this is what the browser resolves ./core/X.js to
    $value = $this->paths->cache_buster($ce . 'core/' . $f . '.js'); // absolute URL with ?v=filemtime
    $imports[] = '"' . $key . '": "' . $value . '"';
}

// components
foreach (array_keys($components ?? []) as $c) {
    $key   = base_url($ce . 'components/' . $c . '.js');
    $value = $this->paths->cache_buster($ce . 'components/' . $c . '.js');
    $imports[] = '"' . $key . '": "' . $value . '"';
}
?>

<script type="importmap">
{
    "imports": {
        <?php echo implode(",\n        ", $imports); ?>

    }
}
</script>

<?php
// Load Contest Engine as ES6 Module - all dependencies are auto-imported
?>
<script type="module" src="<?php echo $this->paths->cache_buster($ce . 'app.js'); ?>"></script>

</body>
<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 10100;"></div>

</html>