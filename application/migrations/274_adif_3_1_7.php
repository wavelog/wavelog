<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_adif_3_1_7 extends CI_Migration {

	public function up() {

		// Adds new modes from ADIF 3.1.7 specification
		$modes = array(
			array('mode' => "MFSK", 'submode' => "FT2", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "DYNAMIC", 'submode' => "FREEDATA", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "OFDM", 'submode' => "RIBBIT_PIX", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "OFDM", 'submode' => "RIBBIT_SMS", 'qrgmode' => "DATA", 'active' => 1),
		);

		foreach ($modes as $mode) {
			$exists = $this->db->where('submode', $mode['submode'])
							->get('adif_modes')
							->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('adif_modes', $mode);
			}
		}

		$exists = $this->db->where('option_name', 'adif_version')->get('options')->num_rows() > 0;
		if (!$exists) {
			$this->db->insert('options', array('option_name' => 'adif_version', 'option_value' => '3.1.7', 'autoload' => 'yes'));
		}

	}

	public function down() {
		// remove the modes that were added in this migration
		$mode_names = array(
			'FT2',
			'FREEDATA',
			'RIBBIT_PIX',
			'RIBBIT_SMS'
		);

		$this->db->where_in('submode', $mode_names);
		$this->db->delete('adif_modes');

		$this->db->where('option_name', 'adif_version');
		$this->db->delete('options');
	}
}
