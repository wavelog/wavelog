<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 2.0.3
*/

class Migration_tag_2_0_3 extends CI_Migration {

    public function up()
    {
        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.0.3'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));
    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.0.2'));
    }
}
