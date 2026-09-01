<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_label_template extends CI_Migration {

    public function up()
    {
        // Create Label Designer Templates table
        if (!$this->db->table_exists('label_templates')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                    'null' => FALSE
                ),
				'user_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => FALSE
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => FALSE
                ),
                'label_type_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => FALSE
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
            $this->dbforge->create_table('label_templates');
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('label_templates');
    }

}
