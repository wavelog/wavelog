<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_qsls_to_userdata extends CI_Migration {

	public function up() {
		$userdata_dir = $this->config->item('userdata');
		if (isset($userdata_dir)) {
			if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir)) {
				mkdir(realpath(APPPATH.'../').'/'.$userdata_dir, 0755, true);
			}
		}
		$eqsl_dir = "eqsl_card";
		if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$eqsl_dir)) {
			mkdir(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$eqsl_dir, 0755, true);
		}
		$qsl_dir = "qsl_card";
		if (!file_exists(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$qsl_dir)) {
			mkdir(realpath(APPPATH.'../').'/'.$userdata_dir.'/'.$qsl_dir, 0755, true);
		}

		$src = 'images/eqsl_card_images';
		foreach (scandir($src) as $file) {
			if (!is_readable($src . '/' . $file)) continue;
			if ($file != '.' && $file != '..') {
				copy($src.'/'.$file, $userdata_dir.'/'.$eqsl_dir.'/'. $file);
			}
		}
		$src = 'assets/qslcard';
		foreach (scandir($src) as $file) {
			if (!is_readable($src . '/' . $file)) continue;
			if ($file != '.' && $file != '..') {
				copy($src.'/'.$file, $userdata_dir.'/'.$qsl_dir.'/'. $file);
			}
		}
	}

	public function down(){
	}
}
