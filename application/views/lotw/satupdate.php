<div class="container">
<h2><?php echo $page_title; ?></h2>

<?php
if ($satupdates) {
   echo '<table class="table table-striped table-hover">';
   echo '<tr><th>'.__('Name').'</th><th>'.__('Display Name').'</th><th>'.__('Start Date').'</th><th>'.__('End Date').'</th><th>'.__('Status').'</th></tr>';
   foreach ($satupdates as $sat) {
      echo('<tr><td>'.$sat['name'].'</td><td>'.$sat['displayname'].'</td><td>'.$sat['startDate'].'</td><td>'.$sat['endDate'].'</td><td>'.$sat['status'].'</td></tr>');
   }
   echo '</table>';
} else {
   echo 'No updates found or file could not be parsed.';
}
?>

</div>
