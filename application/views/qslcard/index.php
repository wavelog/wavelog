<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?= __("QSL Cards"); ?></h2>

	<?php $userdata_dir = $this->config->item('userdata');
	if (isset($userdata_dir)) { ?>
		<div class="alert alert-info" role="alert">
			<?= sprintf(__("You are using %s of disk space to store QSL Card assets"), $storage_used ); ?>
		</div>
	<?php } ?>

	<div class="card">
	  <div class="card-header">
	    <?= __("View QSL Cards"); ?>
	  </div>
	  <div class="card-body">

	<?php
	if ($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}

	$qsl_path = base_url().$this->paths->getUserdataPath('qsl_card').'/';

	if (isset($qslarray) && $qslarray->num_rows() > 0) {
		echo '<table style="width:100%" class="qsltable table table-sm table-bordered table-hover table-striped table-condensed">
        <thead>
        <tr>
        <th style=\'text-align: center\'>'.__("Callsign").'</th>
        <th style=\'text-align: center\'>'.__("Mode").'</th>
        <th style=\'text-align: center\'>'.__("Date").'</th>
        <th style=\'text-align: center\'>'.__("Time").'</th>
        <th style=\'text-align: center\'>'.__("Band").'</th>
        <th style=\'text-align: center\'>'.__("QSL Date").'</th>
        <th style=\'text-align: center\'></th>
        <th style=\'text-align: center\'></th>
        <th style=\'text-align: center\'></th>
        </tr>
        </thead><tbody>';

		foreach ($qslarray->result() as $qsl) {
			echo '<tr>';
			echo '<td style=\'text-align: center\'><a class="callsign" href="javascript:displayQso('.(int) $qsl->COL_PRIMARY_KEY.')">'.html_escape($qsl->COL_CALL).'</a></td>';
			echo '<td style=\'text-align: center\'>';
			echo $qsl->COL_SUBMODE == null ? html_escape($qsl->COL_MODE) : html_escape($qsl->COL_SUBMODE);
			echo '</td>';
			echo '<td style=\'text-align: center\'>';
			$timestamp = strtotime($qsl->COL_TIME_ON);
			echo date($custom_date_format, $timestamp);
			echo '</td>';
			echo '<td style=\'text-align: center\'>';
			$timestamp = strtotime($qsl->COL_TIME_ON);
			echo date('H:i', $timestamp);
			echo '</td>';
			echo '<td style=\'text-align: center\'>';
			if ($qsl->COL_SAT_NAME != null) {
				echo html_escape($qsl->COL_SAT_NAME);
			} else {
				echo html_escape(strtolower($qsl->COL_BAND));
			};
			echo '</td>';
			echo '<td style=\'text-align: center\'>';
			$timestamp = strtotime($qsl->COL_QSLRDATE ?? '');
			echo date($custom_date_format, $timestamp);
			echo '</td>';
			echo '<td id="'.$qsl->id.'" style=\'text-align: center\'><button onclick="deleteQsl(\''.$qsl->id.'\')" class="btn btn-sm btn-danger">' . __("Delete") . '</button></td>';
			echo '<td style=\'text-align: center\'><button onclick="addQsosToQsl(\''.$qsl->filename.'\')" class="btn btn-sm btn-success">' . __("Add Qsos") . '</button></td>';
			echo '<td style=\'text-align: center\'><a href=\''.$qsl_path.html_escape(basename($qsl->filename)).'\' data-fancybox=\'images\' class=\'btn btn-sm btn-success\'>' . __("View") . '<img loading=\'lazy\' src=\''.site_url('qsl/image/'.$qsl->id).'/160\' height="100px"></a></td>';
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
	</div>
</div>
