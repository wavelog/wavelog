<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds an trigger for the switch from 1.* to 2.*
*/

class Migration_add_version_two_trigger_to_options extends CI_Migration {

    public function up()
    {

        $data = array(
            array('option_name' => "version2_trigger", 'option_value' => "false", 'autoload' => "yes"),
        );

        $query = $this->db->select('option_name')->where('option_name', 'version2_trigger')->get('options');
        if($query->num_rows() == 0) {
            $this->db->insert_batch('options', $data);
        }

    }

    public function down()
    {
        // No option to down
    }
}