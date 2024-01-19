<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds a value to the options table which is used as cat_timeout
*/

class Migration_add_cat_timeout_to_options extends CI_Migration {

    public function up()
    {
        $data = array(
            array('option_name' => "cat_timeout_interval", 'option_value' => "1800", 'autoload' => "yes"),
         );

         $this->db->insert_batch('options', $data);
    }

    public function down()
    {
        // No option to down
    }
}