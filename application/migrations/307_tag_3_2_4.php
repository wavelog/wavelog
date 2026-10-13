<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 3.2.4
*/

class Migration_tag_3_2_4 extends CI_Migration {

    public function up()
    {
        // Tag Wavelog New Version
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '3.2.4'));

        // Trigger Version Info Dialog
        $this->db->where('option_type', 'version_dialog');
        $this->db->where('option_name', 'confirmed');
        $this->db->update('user_options', array('option_value' => 'false'));

        // Also set Version Dialog to "both" if only custom text is applied
        $this->db->where('option_name', 'version_dialog');
        $this->db->where('option_value', 'custom_text');
        $this->db->update('options', array('option_value' => 'both'));

		// Force enable 'Other' contest. See commit 3ca96fb82e07b03902707d99b76557a79b198812
		$this->db->query("UPDATE " . $this->db->dbprefix('contest') . " SET active = 1 WHERE id = 1 AND name = 'Other'");
    }

    public function down()
    {
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '3.2.3'));
    }

}
