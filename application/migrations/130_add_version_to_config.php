<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This adds the version to the database.
*/

class Migration_add_version_to_config extends CI_Migration {

    public function up()
    {
        $data = array(
            array('option_name' => "version", 'option_value' => "2.4.5", 'autoload' => "yes"),
         );

         $this->db->insert_batch('options', $data);
    }

    public function down()
    {
        // No option to down
    }
}