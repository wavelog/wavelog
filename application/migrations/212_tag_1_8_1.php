<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 1.8.1
*/

class Migration_tag_1_8_1 extends CI_Migration {

    public function up()
    {

        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.8.1'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

        // small DB adjustment in this release to save mig versions 
        // see: https://github.com/wavelog/wavelog/issues/698
        $this->db->query("ALTER TABLE ".$this->config->item('table_name')." MODIFY COLUMN `COL_POTA_REF` VARCHAR(128) DEFAULT NULL;");
        $this->db->query("ALTER TABLE ".$this->config->item('table_name')." MODIFY COLUMN `COL_MY_POTA_REF` VARCHAR(128) DEFAULT NULL;");

    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.8'));
    }
}
