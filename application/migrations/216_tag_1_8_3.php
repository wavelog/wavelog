<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 1.8.3
*/

class Migration_tag_1_8_3 extends CI_Migration {

    public function up()
    {

        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.8.3'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

        if ($this->db->field_exists('exportname', 'satellite')) {
            $this->db->query("INSERT INTO satellite (name, exportname, orbit) SELECT distinct 'MESAT1','', 'LEO' FROM satellite WHERE NOT EXISTS (SELECT 1 FROM satellite WHERE name = 'MESAT1');");
            $this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145925000', 'USB', '435825000' FROM satellite WHERE name = 'MESAT1' and NOT EXISTS (SELECT 1 FROM satellitemode WHERE satelliteid = satellite.id) ;");
        }
        $this->db->query("UPDATE cron SET description = 'Up- and Download QSOs to LoTW' WHERE id = 'lotw_lotw_upload';");

        //Mark MESAT1 QSOs LoTW sent state as invalid/ignore until it is recognized by LoTW
        $this->db->query("UPDATE ".$this->config->item('table_name')." SET COL_LOTW_QSL_SENT = 'I', COL_LOTW_QSLSDATE = null WHERE COL_SAT_NAME = 'MESAT1' OR COL_SAT_NAME = 'MESAT-1';");

    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.8.2'));
    }
}
