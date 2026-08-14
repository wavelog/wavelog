<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 2.3.1
*/

class Migration_tag_2_3_1 extends CI_Migration {

    public function up()
    {
        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.3.1'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

        $this->dbtry("update dxcc_temp set ituz = 12 where adif = 37;"); // TI9
        $this->dbtry("update iota set dxccid = 202 where tag = 'NA-249';"); // Fix so that NA-249 is associated with Puerto Rico DXCC
        $this->dbtry("update iota set dxccid = 225 where tag = 'EU-041';"); // Fix so that EU-041 is associated with Sardinia DXCC

        // Taking care of ITU Zones for deleted DXCCs in dxcc_entities
        $this->dbtry("UPDATE dxcc_entities join dxcc_temp on dxcc_entities.adif = dxcc_temp.adif set dxcc_entities.ituz = dxcc_temp.ituz;");
    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.3'));
    }

    function dbtry($what) {
        try {
            $this->db->query($what);
        } catch (Exception $e) {
            log_message("error", "Error setting character set/collation: ".$e." // Executing: ".$this->db->last_query());
        }
    }
}
