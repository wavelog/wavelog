<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_none_dxcc extends CI_Migration {

	public function up() {
		$this->db->query("insert ignore into dxcc_entities (adif,`name`,prefix,ituz,cqz,cont,`long`,lat,`start`,`end`) values (0,'- NONE - (e.g. /MM, /AM)','',0,0,'',0,0,null,null)");
	}

	public function down(){
		$this->db->query("delete from dxcc_entities where adif=0");
	}
}
