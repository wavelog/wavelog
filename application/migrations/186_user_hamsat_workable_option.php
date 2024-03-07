<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Migration_amsat_status_upload_option
 *
 * Creates a boolean column with option to allow for activating uploading
 * a status info to https://amsat.org/status
 */

class Migration_user_hamsat_workable_option extends CI_Migration {

        public function up()
        {
                if (!$this->db->field_exists('hamsat_key', 'users')) {
                        $fields = array(
                                'hamsat_key VARCHAR(36) DEFAULT ""',
                        );
                        $this->dbforge->add_column('users', $fields);
                }
                if (!$this->db->field_exists('hamsat_workable_only', 'users')) {
                        $fields = array(
                                'hamsat_workable_only BOOLEAN DEFAULT FALSE',
                        );
                        $this->dbforge->add_column('users', $fields);
                }
        }

        public function down()
        {
                if ($this->db->field_exists('hamsat_key', 'users')) {
                        $this->dbforge->drop_column('users', 'hamsat_key');
                }
                if ($this->db->field_exists('hamsat_workable_only', 'users')) {
                        $this->dbforge->drop_column('users', 'hamsat_workable_only');
                }
        }
}
