<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_move_callbook_credentials extends CI_Migration
{
	public function up() {

        if (file_exists('install/.lock')) { // Ensure we're not running from the installer
            $this->load->library('encryption');
            $this->load->library('optionslib');

            $callbook_provider = $this->config->item('callbook') ?? 'qrz';
            $callbook_username = $this->config->item($callbook_provider . '_username') ?? '';
            $callbook_password = $this->config->item($callbook_provider . '_password') ?? '';
            $callbook_fullname = $this->config->item('use_fullname') ?? false;

            $this->optionslib->update('callbook_provider', $callbook_provider);
            $this->optionslib->update('callbook_username', $callbook_username);
            $this->optionslib->update('callbook_password', $this->encryption->encrypt($callbook_password));
            $this->optionslib->update('callbook_fullname', $callbook_fullname);
        }


	}

	public function down() {
		// Not Possible
	}
}
