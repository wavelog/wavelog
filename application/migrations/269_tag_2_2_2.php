<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
	Tag Wavelog as Version 2.2.2
*/

class Migration_tag_2_2_2 extends CI_Migration {

    public function up()
    {
        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.2.2'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

		$this->dbtry("UPDATE dxcc_master set ituzone = '63' where countrycode = 234;"); // E5/s
		$this->dbtry("UPDATE dxcc_master set ituzone = '47' where countrycode in (57, 244);"); // FQ8 and ST0/d
		$this->dbtry("UPDATE dxcc_master set ituzone = '12' where countrycode = 37;"); // TI9

		$this->dbtry("INSERT INTO dxcc_temp (prefix,name,adif,cont,cqz,ituz,`long`,lat)
			select prefix, name, adif, cont, cqz, (select ituzone from dxcc_master where countrycode = dxcc_entities.adif) ituzone, `long`, lat
			from dxcc_entities
			where not exists (select 1 from dxcc_temp where adif = dxcc_entities.adif)
			and dxcc_entities.adif > 0;");

		// Taking care of ITU Zones for deleted DXCCs in dxcc_entities
		$this->dbtry("UPDATE dxcc_entities
			join dxcc_temp on dxcc_entities.adif = dxcc_temp.adif
			set dxcc_entities.ituz = dxcc_temp.ituz;");
	}

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '2.2.1'));
    }

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering FKs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
