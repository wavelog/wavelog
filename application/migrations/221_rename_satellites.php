<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_rename_satellites extends CI_Migration {

	var $satellites = array(
		'INSPIRE-SAT 7' => 'INSPR7',
		'Lilacsat-1' => 'LO-90',
		'TEVEL-1'  => 'TEVEL1',
		'TEVEL-2'  => 'TEVEL2',
		'TEVEL-3'  => 'TEVEL3',
		'TEVEL-4'  => 'TEVEL4',
		'TEVEL-5'  => 'TEVEL5',
		'TEVEL-6'  => 'TEVEL6',
		'TEVEL-7'  => 'TEVEL7',
		'TEVEL-8'  => 'TEVEL8',
		'UVSQ-SAT'  => 'UVSQ',
	);

	public function up() {
		if ($this->db->table_exists('satellite')) {

			foreach ($this->satellites as $exportname => $name) {
				$this->update_sat_table($exportname, $name);
			}
		}

	}

	public function down() {
	}

	function update_sat_table($from, $to) {
		$sql= "UPDATE `satellite` SET `name` = '".$to."' WHERE `name` = '".$from."';";
		$this->db->query($sql);
      log_message('debug', 'SQL: '.$this->db->last_query());
	}
}
