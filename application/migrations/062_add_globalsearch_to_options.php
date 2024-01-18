<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds the global_search to the options table.
*/

class Migration_add_globalsearch_to_options extends CI_Migration {

    public function up()
    {
        $data = array(
            array('option_name' => "global_search", 'option_value' => "false", 'autoload' => "yes"),
         );

         $this->db->insert_batch('options', $data);
    }

    public function down()
    {
        // No option to down
    }
}