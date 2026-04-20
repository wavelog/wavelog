<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 2.4.1
*/

class Migration_add_table_postcard_template extends CI_Migration {

    public function up()
    {
        // Create QSL Postcard Templates table
        if (!$this->db->table_exists('qsl_postcard_templates')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                    'null' => FALSE
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => FALSE
                ),
                'orientation' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => FALSE,
                    'default' => 'landscape'
                ),
                'width_in' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'null' => FALSE,
                    'default' => 6.00
                ),
                'height_in' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'null' => FALSE,
                    'default' => 4.00
                ),
                'preview_image' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                    'default' => NULL
                ),
                'print_background' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => FALSE,
                    'default' => 0
                ),
                'layout_json' => array(
                    'type' => 'LONGTEXT',
                    'null' => FALSE
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE,
                    'default' => 'CURRENT_TIMESTAMP'
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE,
                    'default' => 'CURRENT_TIMESTAMP'
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('qsl_postcard_templates');
        }

        // Create Callbook Cache table
        if (!$this->db->table_exists('callbook_cache')) {
            $this->dbforge->add_field(array(
                'callsign' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => FALSE
                ),
                'source' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => FALSE,
                    'default' => 'hamqth'
                ),
                'address_json' => array(
                    'type' => 'TEXT',
                    'null' => FALSE
                ),
                'fetched_at' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE
                ),
                'expires_at' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE
                ),
            ));
            $this->dbforge->add_key('callsign', TRUE);
            $this->dbforge->add_key('expires_at');
            $this->dbforge->create_table('callbook_cache');
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('qsl_postcard_templates');
        $this->dbforge->drop_table('callbook_cache');
    }

}
