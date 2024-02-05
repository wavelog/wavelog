<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as 1.0 FIRST RELEASE!!     

        Wavelog Version 1.0
        Dear Hams, Friends of Amateur Radio, and Tech Enthusiasts
        It is our pleasure to share with you today a significant development in the world of log software.
        Over the past years, the web-based log software Cloudlog, founded by 2M0SQL, Peter, has steadily evolved. LA8AJA, DF2ET, DJ7NT, and HB9HIL have also 
        made crucial contributions, shaping Cloudlog into what it is today.
        Due to differing visions and perspectives, we have decided to take a new direction and create a fork named "Wavelog." After several weeks of preparation, 
        we are excited to present our initial release.
        In this version, we have addressed numerous bugs, improved the installer's stability, and added several new features. We acknowledge that this marks just 
        the beginning of our journey, and we look forward to receiving your feedback.
        More Information can be found here: https://github.com/wavelog/wavelog
        We want to express our gratitude for your ongoing support, and we are eager to hear about the experiences and insights you have with Wavelog. Your feedback 
        will be invaluable as we continue to enhance Wavelog.

        Vy 73
*/

class Migration_tag_1_0 extends CI_Migration {      

    public function up()
    {
    
        // Tag Wavelog 1.0                                   
        $this->db->where('option_name', 'version');
        $this->db->update('options', array('option_value' => '1.0'));

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
        $this->db->update('options', array('option_value' => '2.6.3'));  
    }
}