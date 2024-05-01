<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_tle_table extends CI_Migration {

    public function up()
    {
		if (!$this->db->table_exists('tle')) {

			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 6,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					'null' => FALSE
				),
				'satelliteid' => array(
					'type' => 'INT',
					'constraint' => '6',
					'unsigned' => TRUE,
					'null' => FALSE,
				),
				'tle' => array(
					'type' => 'TEXT',
					'null' => TRUE,
				),
				'updated' => array(
					'type' => 'timestamp',
					'null' => false,
					'default' => 'CURRENT_TIMESTAMP'
				),
			));
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table('tle');

			$this->db->query("INSERT INTO tle (satelliteid, tle)	select id, '1 53106U 22080B   24108.26757684 -.00000003  00000-0  00000-0 0  9991
2 53106  70.1428 213.6795 0007982 119.5170 240.6286  6.42557181 41352' from satellite where name = 'IO-117'");

			$this->db->query("INSERT INTO tle (satelliteid, tle)	select id, '1 43700U 18090A   24108.89503844  .00000137  00000-0  00000-0 0  9999
2 43700   0.0157 247.0647 0001348 170.1236 137.3539  1.00275203 19758' from satellite where name = 'QO-100'");

			$this->db->query("INSERT INTO tle (satelliteid, tle)	select id, '1 27607U 02058C   24108.20375939  .00004950  00000+0  68675-3 0  9992
2 27607  64.5563  48.9470 0031938 141.6898 218.6483 14.78629101147515' from satellite where name = 'SO-50'");

		}
	}

    public function down()
    {
		$this->dbforge->drop_table('tle');
	}
}
