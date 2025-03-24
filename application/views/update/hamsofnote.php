<div class="container">
<h2><?php echo $page_title; ?></h2>

<?php
if ($hamsofnote) {
   echo '<table class="table table-striped table-hover">';
   echo '<tr><th>'.__('Callsign').'</th><th>'.__('Name / Description').'</th></tr>';
   foreach ($hamsofnote as $hamofnote) {
      echo('<tr><td>'.$hamofnote['callsign'].'</td><td>'.$hamofnote['name'].' <a target="_blank" href="'.$hamofnote['link'].'">'.$hamofnote['linkname'].'</a></td></tr>');
   }
   echo '</table>';
} else {
   echo 'No updates found or file could not be parsed.';
}
?>

</div>
