<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_move_callbook_credentials extends CI_Migration
{
    /**
     * For better security we want to store the callbook credentials in the database.
     * This also allows us to use the encryption library to store the password securely.
     */

	public function up() {

        /** 
         * Before we can do anything here, we have to make sure the installer is locked. 
         * For example from Cloudlog migrated Wavelog installations may don't have this lockfile.  
         * To verify this, check if there are any QSOs in the database. 
         */
         
        $this->db->select('COUNT(COL_PRIMARY_KEY) as count');
        $this->db->from($this->config->item('table_name'));
        $qsos = $this->db->get()->row()->count;

        // if there are any QSOs in the database, we can assume that the installer should be locked
        if ($qsos > 0) {
            if (!touch('install/.lock')) {
                log_message("error", "Could not create lockfile. Please create a empty file named '.lock' in the 'install' directory.");
            } else {
                log_message("info", "Mig 226: Lockfile created. Installer is now locked.");
            }
        }

        /**
         * Ensure we don't move data here if the installer isn't locked. 
         * The installer handles this itself, why this is only for existing installations. 
         */

        if (file_exists('install/.lock')) { 
            $this->load->library('encryption');
            $this->load->library('optionslib');

            $callbook_provider = $this->config->item('callbook') ?? 'qrz';
            $callbook_username = $this->config->item($callbook_provider . '_username') ?? '';
            $callbook_password = $this->config->item($callbook_provider . '_password') == '' ? '' : $this->encryption->encrypt($this->config->item($callbook_provider . '_password'));
            $callbook_fullname = $this->config->item('use_fullname') ?? false;

            $this->optionslib->update('callbook_provider', $callbook_provider);
            $this->optionslib->update('callbook_username', $callbook_username);
            $this->optionslib->update('callbook_password', $callbook_password);
            $this->optionslib->update('callbook_fullname', $callbook_fullname);

            log_message("debug", "Mig 226: Moved callbook credentials successfully to options table.");
        }
	}

	public function down() {
		// Not Possible, We can't edit the config.php
	}
}
