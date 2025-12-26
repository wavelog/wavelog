<div class="container-fluid">

    <br>

    <h2><?= __("eQSL Cards"); ?></h2>

    <?php $userdata_dir = $this->config->item('userdata');
    if (isset($userdata_dir)) { ?>
        <div class="alert alert-info" role="alert">
            <?= sprintf(__("You are using %s of disk space to store eQSL Card assets"), $storage_used ); ?>
        </div>
    <?php } ?>

    <?php

   if($this->session->userdata('user_date_format')) {
      // If Logged in and session exists
      $custom_date_format = $this->session->userdata('user_date_format');
   } else {
      // Get Default date format from /config/wavelog.php
      $custom_date_format = $this->config->item('qso_date_format');
   }

    if (is_array($qslarray->result())) {
        echo '<table class="eqsltable table table-sm table-bordered table-hover table-striped table-condensed">
        <thead>
        <tr>
        <th style=\'text-align: center\'>'.__("Callsign").'</th>
        <th style=\'text-align: center\'>'.__("Mode").'</th>
        <th style=\'text-align: center\'>'.__("Date").'</th>
        <th style=\'text-align: center\'>'.__("Time").'</th>
        <th style=\'text-align: center\'>'.__("Band").'</th>
        <th style=\'text-align: center\'>'.__("Propagation Mode").'</th>
        <th style=\'text-align: center\'>'.__("QSL Message").'</th>
        <th style=\'text-align: center\'>'.__("QSL Date").'</th>
        <th style=\'text-align: center\'></th>
        </tr>
        </thead><tbody>';

        foreach ($qslarray->result() as $qsl) {
            echo '<tr>';
            echo '<td style=\'text-align: center\'><a id="edit_qso" href="javascript:displayQso('.$qsl->COL_PRIMARY_KEY.')">' . str_replace("0","&Oslash;",$qsl->COL_CALL) . '</a></td>';
         echo '<td style=\'text-align: center\'>';
         echo $qsl->COL_SUBMODE==null?$qsl->COL_MODE:$qsl->COL_SUBMODE;
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         $timestamp = strtotime($qsl->COL_TIME_ON); echo date($custom_date_format, $timestamp);
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         $timestamp = strtotime($qsl->COL_TIME_ON); echo date('H:i', $timestamp);
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         if($qsl->COL_SAT_NAME != null) { echo $qsl->COL_SAT_NAME; } else { echo strtolower($qsl->COL_BAND); };
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         if($qsl->COL_PROP_MODE != null) { echo $qsl->COL_PROP_MODE; };
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         if($qsl->COL_QSLMSG_RCVD != null) { echo htmlentities($qsl->COL_QSLMSG_RCVD); };
         echo '</td>';
         echo '<td style=\'text-align: center\'>';
         if ($qsl->COL_EQSL_QSLRDATE) { $timestamp = strtotime($qsl->COL_EQSL_QSLRDATE); echo date($custom_date_format, $timestamp); }
         echo '</td>';
            echo '<td style=\'text-align: center\'><a href=\''.site_url('eqsl/image/'.$qsl->COL_PRIMARY_KEY).'\' data-fancybox=\'images\' data-width=\'528\' data-height=\'336\' class=\'btn btn-sm btn-success\'>' . __("View") . '<img loading=\'lazy\' src=\''.site_url('eqsl/image/'.$qsl->COL_PRIMARY_KEY).'/160\' height="100px"></a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
    ?>

    <?php if (isset($this->pagination)){ ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
    	<?php
	$config['full_tag_open'] = '<ul class="pagination">';
	$config['full_tag_close'] = '</ul>';
	$config['attributes'] = ['class' => 'page-link'];
	$config['first_link'] = false;
	$config['last_link'] = false;
	$config['first_tag_open'] = '<li class="page-item">';
	$config['first_tag_close'] = '</li>';
	$config['prev_link'] = '&laquo';
	$config['prev_tag_open'] = '<li class="page-item">';
	$config['prev_tag_close'] = '</li>';
	$config['next_link'] = '&raquo';
	$config['next_tag_open'] = '<li class="page-item">';
	$config['next_tag_close'] = '</li>';
	$config['last_tag_open'] = '<li class="page-item">';
	$config['last_tag_close'] = '</li>';
	$config['cur_tag_open'] = '<li class="page-item active"><a href="#" class="page-link">';
	$config['cur_tag_close'] = '<span class="visually-hidden">(current)</span></a></li>';
	$config['num_tag_open'] = '<li class="page-item">';
	$config['num_tag_close'] = '</li>';
    	$this->pagination->initialize($config);
    	?>

		<?php echo $this->pagination->create_links(); ?>
            <?php if (isset($result_range)){ ?>
                <span><?= $result_range; ?></span>
            <?php } ?>
        </div>
	<?php } ?>

</div>
