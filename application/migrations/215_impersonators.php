<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_impersonators extends CI_Migration {

    // This migration is to add the impersonators table to the database
    // This table is used to authorize a user to impersonate another user (special callsigns or club stations)

    public function up() {

        if (!$this->db->table_exists('impersonators')) {

            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 6,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                    'null' => FALSE
                ),
                'user_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => TRUE,
                    'auto_increment' => FALSE,
                    'null' => FALSE
                ),
                'impersonate_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => TRUE,
                    'auto_increment' => FALSE,
                    'null' => FALSE
                )
            ));

            $this->dbforge->add_key('id', TRUE);

            $this->dbforge->create_table('impersonators');

        }
    }

    public function down() {

        $this->dbforge->drop_table('impersonators');

    }

}
