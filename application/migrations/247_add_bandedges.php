<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Add bandedges table in use for the dxcluster
*/

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_bandedges extends CI_Migration {

    public function up()
    {
		if (!$this->db->table_exists('bandedges')) {
			// Create the bandedges table
			$this->dbforge->add_field([
				'id' => [
					'type'           => 'INT',
					'constraint'     => 11,
					'unsigned'       => TRUE,
					'auto_increment' => TRUE,
				],
				'userid' => [
					'type'       => 'INT',
					'constraint' => 11,
				],
				'frequencyfrom' => [
					'type'       => 'BIGINT',
					'constraint' => 20,
				],
				'frequencyto' => [
					'type'       => 'BIGINT',
					'constraint' => 20,
				],
				'mode' => [
					'type'       => 'VARCHAR',
					'constraint' => 20,
				],
			]);

			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table('bandedges');

			// Insert initial data (for all users: userid = -1)
			$data = [
				['userid' => -1, 'frequencyfrom' => 1800000,  'frequencyto' => 1840000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 3500000,  'frequencyto' => 3600000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 3700000,  'frequencyto' => 4000000,  'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 5350000,  'frequencyto' => 5367000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 7000000,  'frequencyto' => 7040000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 7100000,  'frequencyto' => 7300000,  'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 10100000, 'frequencyto' => 10130000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 14000000, 'frequencyto' => 14070000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 14125000, 'frequencyto' => 14350000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 14070000, 'frequencyto' => 14125000, 'mode' => 'digi'],
			];

			$this->db->insert_batch('bandedges', $data);
		}
    }

    public function down()
    {
        $this->dbforge->drop_table('bandedges');
    }
}

