<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Fix IOTA DXCC ADIF Code for P5 and AN-010
*/

class Migration_fix_iota_dxcc extends CI_Migration {

    public function up()
    {
		$this->db->query("UPDATE " . $this->db->dbprefix('iota') . " SET dxccid = 344 WHERE dxccid = 433");
		$this->db->query("UPDATE " . $this->db->dbprefix('iota') . " SET dxccid = 241 WHERE tag = 'an-010'");
    }

    public function down()
    {
    }

}
