<div class="container">
<h2><?php echo $page_title; ?></h2>

<?php
print '<table class="table table-striped table-hover">';
print '<tr><th>'.__('Name').'</th><th>'.__('Display Name').'</th><th>'.__('Start Date').'</th><th>'.__('End Date').'</th><th>'.__('Status').'</th></tr>';
foreach ($satupdates as $sat) {
   print('<tr><td>'.$sat['name'].'</td><td>'.$sat['displayname'].'</td><td>'.$sat['startDate'].'</td><td>'.$sat['endDate'].'</td><td>'.$sat['status'].'</td></tr>');
}
print '</table>';
?>

</div>
