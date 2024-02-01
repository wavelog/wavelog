<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds the theme 'darkly' as standard/default theme to wavelog.
*/

class Migration_add_default_theme_to_options_table extends CI_Migration {

    public function up()
    {
        $data = array(
            array('option_name' => "theme", 'option_value' => "darkly", 'autoload' => "yes")
         );

         $this->db->insert_batch('options', $data);
    }

    public function down()
    {
        // No option to down
    }
}