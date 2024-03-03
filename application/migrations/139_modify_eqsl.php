<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_eqsl extends CI_Migration {

	public function up()
	{
        	$this->db->where('id', '1');
        	$this->db->update('config', array('eqsl_download_url' => 'https://www.eqsl.cc/qslcard/DownloadInBox.cfm'));
	}

	public function down()
	{
		// Will not go back to insecure connections
	}
}
