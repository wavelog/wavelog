<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_most_wanted_grids_table extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('most_wanted_grids')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                    'null' => FALSE
                ),
                'grid' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 4,
                    'null' => FALSE
                ),
                'perc' => array(
                    'type' => 'INT',
                    'constraint' => 3,
                    'null' => TRUE,
                )
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('most_wanted_grids');
        }

        // Add cron job for Most Wanted Grids update
        if ($this->db->table_exists('cron')) {
            $this->db->where('id', 'most_wanted_grids_file');
            $query = $this->db->get('cron');

            if ($query->num_rows() == 0) {
                $random_minute = mt_rand(1, 59);
                $random_hour = mt_rand(0, 23);

                $this->db->insert_batch('cron', array(
                    array(
                        'id' => 'most_wanted_grids_file',
                        'enabled' => '1',
                        'status' => 'pending',
                        'description' => 'Download Most Wanted Grids (Satellite)',
                        'function' => 'index.php/update/update_most_wanted_grids',
                        'expression' => $random_minute . ' ' . $random_hour . ' 1 * *',
                        'last_run' => null,
                        'next_run' => null
                    )
                ));
            }
        }
    }

    public function down()
    {
        $this->db->where('id', 'most_wanted_grids_file');
        $this->db->delete('cron');

        $this->dbforge->drop_table('most_wanted_grids');
    }

}
