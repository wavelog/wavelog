<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds a theme for color impaired hams (based on default skin)
*/

class Migration_add_color_impaired_theme extends CI_Migration {

    public function up()
    {
        if ($this->entrycheck() == 0) { 
            $this->dbtry("INSERT INTO `themes` SET name = 'Color Impaired Support', foldername = 'color_impaired', header_logo = 'wavelog_icon_only_default', main_logo = 'wavelog_logo_default', theme_mode = 'light';");
        }
    }

    public function down()
    {
        if ($this->entrycheck() > 0) { 
            $this->dbtry("DELETE FROM themes WHERE name = 'Color Impaired Support';");
            $this->dbtry("UPDATE `users` SET user_stylesheet = 'default' WHERE user_stylesheet = 'color_impaired';");
        }
    }

    function dbtry($what) {
        try {
            $this->db->query($what);
        } catch (Exception $e) {
            log_message("error", "Something gone wrong while altering the themes table: ".$e." // Executing: ".$this->db->last_query());
        }
    }

    function entrycheck() {
       return $this->db->query("SELECT * FROM themes WHERE name = 'Color Impaired Support';")->num_rows();
    }
}
