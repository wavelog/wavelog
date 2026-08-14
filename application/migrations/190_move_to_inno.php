<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_move_to_inno extends CI_Migration {

	public function up() {
		$this->db->query("ALTER TABLE contest ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE iota ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE qsl_images ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE queries ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE themes ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE timezones ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE users ENGINE=InnoDB;");
		$this->db->query("ALTER TABLE lotw_users ENGINE=InnoDB;");
	}

	public function down(){
		// No Way back here!
	}
}	
