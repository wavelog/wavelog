<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 1.7
*/

class Migration_tag_1_7 extends CI_Migration {

    public function up()
    {

        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.7'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

        // small DB adjustment in this release to save mig versions 
        // see: https://github.com/wavelog/wavelog/discussions/511
        $this->db->query("ALTER TABLE ".$this->config->item('table_name')." MODIFY COLUMN `COL_QTH` VARCHAR(128) DEFAULT NULL;");

    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.6.1'));
    }
}
