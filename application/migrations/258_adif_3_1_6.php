<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_adif_3_1_6 extends CI_Migration {

	public function up() {

		// Adds new modes from ADIF 3.1.6 specification
		$modes = array(
			array('mode' => "FSK", 'submode' => "SCAMP_FAST", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "FSK", 'submode' => "SCAMP_SLOW", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "FSK", 'submode' => "SCAMP_VSLOW", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "MTONE", 'submode' => "SCAMP_OO", 'qrgmode' => "DATA", 'active' => 1),
			array('mode' => "MTONE", 'submode' => "SCAMP_OO_SLW", 'qrgmode' => "DATA", 'active' => 1),
		);

		foreach ($modes as $mode) {
			$exists = $this->db->where('submode', $mode['submode'])
							->get('adif_modes')
							->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('adif_modes', $mode);
			}
		}

		// Add new contests from ADIF 3.1.6 specification
		$contests = array(
			array('name' => 'DARC CW Trainee Contest', 'adifname' => 'DARC-CWA', 'active' => 1),
			array('name' => 'DARC 10m Contest', 'adifname' => 'DARC-10', 'active' => 1),
			array('name' => 'DARC Trainee Contest', 'adifname' => 'DARC-TRAINEE', 'active' => 1),
			array('name' => 'DARC Hell Contest', 'adifname' => 'DARC-HELL', 'active' => 1),
			array('name' => 'DARC Microwave Contest', 'adifname' => 'DARC-MICROWAVE', 'active' => 1),
			array('name' => 'DARC RTTY Short Contest', 'adifname' => 'ShortRY', 'active' => 1),
			array('name' => 'DARC UKW Spring Contest', 'adifname' => 'DARC-UKW-SPRING', 'active' => 1),
			array('name' => 'DARC UKW Field Day Contests', 'adifname' => 'DARC-UKW-FIELD-DAY', 'active' => 1),
			array('name' => 'DARC VHF-, UHF-, Microwave Contest', 'adifname' => 'DARC-VHF-UHF-MICROWAVE', 'active' => 1),
			array('name' => 'DARC Easter Contest', 'adifname' => 'EASTER', 'active' => 1),
			array('name' => 'International Naval Contest (INC)', 'adifname' => 'NAVAL', 'active' => 1),
			array('name' => 'ORARI Banggai DX Contest', 'adifname' => 'BANGGAI-DX', 'active' => 1),
			array('name' => 'ORARI Bekasi Merdeka Contest', 'adifname' => 'BEKASI-MERDEKA-CONTEST', 'active' => 1),
			array('name' => 'ORARI DX Contest', 'adifname' => 'ORARI-DX', 'active' => 1),
		);

		foreach ($contests as $contest) {
			$exists = $this->db->where('adifname', $contest['adifname'])
							->get('contest')
							->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('contest', $contest);
			}
		}

		$table_name = $this->config->item('table_name');
		$qso_fields = [];

		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_EQSL_AG';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_EQSL_AG VARCHAR(10) DEFAULT NULL AFTER COL_EQSL_STATUS;";
		} else {
			log_message('info', 'Column "COL_EQSL_AG" already exists, skipping ALTER TABLE.');
		}

		// Run the querys
		try {
			foreach ($qso_fields as $query) {
				$this->db->query($query);
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Migration failed: ' . $e->getMessage());
			log_message('error', 'The query was: ' . $query);
			return false;
		}

	}

	public function down() {
		// remove the modes that were added in this migration
		$mode_names = array(
			'SCAMP_FAST',
			'SCAMP_SLOW',
			'SCAMP_VSLOW',
			'SCAMP_OO',
			'SCAMP_OO_SLW'
		);

		$this->db->where_in('submode', $mode_names);
		$this->db->delete('adif_modes');

		// Remove the contests added in this migration
		$contest_names = array(
			'DARC-CWA',
			'DARC-10',
			'DARC-TRAINEE',
			'DARC-HELL',
			'DARC-MICROWAVE',
			'ShortRY',
			'DARC-UKW-SPRING',
			'DARC-UKW-FIELD-DAY',
			'DARC-VHF-UHF-MICROWAVE',
			'EASTER',
			'NAVAL',
			'BANGGAI-DX',
			'BEKASI-MERDEKA-CONTEST',
			'ORARI-DX'
		);

		$this->db->where_in('adifname', $contest_names);
		$this->db->delete('contest');
	}
}
