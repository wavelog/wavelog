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
				['userid' => -1, 'frequencyfrom' => 1800000,  'frequencyto' => 1838000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 1838000,  'frequencyto' => 1840000,  'mode' => 'dig'],
				['userid' => -1, 'frequencyfrom' => 1840000,  'frequencyto' => 2000000,  'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 3500000,  'frequencyto' => 3570000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 3570000,  'frequencyto' => 3600000,  'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 3600000,  'frequencyto' => 4000000,  'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 5350000,  'frequencyto' => 5367000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 7000000,  'frequencyto' => 7040000,  'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 7040000,  'frequencyto' => 7050000,  'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 7050000,  'frequencyto' => 7300000,  'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 10100000, 'frequencyto' => 10130000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 10130000, 'frequencyto' => 10150000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 14000000, 'frequencyto' => 14070000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 14070000, 'frequencyto' => 14125000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 14125000, 'frequencyto' => 14350000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 18068000, 'frequencyto' => 18095000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 18095000, 'frequencyto' => 18109000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 18111000, 'frequencyto' => 18168000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 21000000, 'frequencyto' => 21070000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 21070000, 'frequencyto' => 21110000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 21125000, 'frequencyto' => 21450000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 24890000, 'frequencyto' => 24915000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 24915000, 'frequencyto' => 24929000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 24931000, 'frequencyto' => 24990000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 28000000, 'frequencyto' => 28070000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 28070000, 'frequencyto' => 28120000, 'mode' => 'digi'],
				['userid' => -1, 'frequencyfrom' => 28225000, 'frequencyto' => 29700000, 'mode' => 'phone'],
				['userid' => -1, 'frequencyfrom' => 50000000, 'frequencyto' => 50109000, 'mode' => 'cw'],
				['userid' => -1, 'frequencyfrom' => 50110000, 'frequencyto' => 52000000, 'mode' => 'phone']
			];

			$this->db->insert_batch('bandedges', $data);
		}
    }

    public function down()
    {
        $this->dbforge->drop_table('bandedges');
    }
}

