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

	var $lotw_sats = array(
		'AISAT1',
		'AO-10',
		'AO-109',
		'AO-13',
		'AO-16',
		'AO-21',
		'AO-27',
		'AO-3',
		'AO-4',
		'AO-40',
		'AO-51',
		'AO-6',
		'AO-7',
		'AO-73',
		'AO-8',
		'AO-85',
		'AO-91',
		'AO-92',
		'ARISS',
		'Arsene',
		'BO-102',
		'BY70-1',
		'CAS-2T',
		'CAS-3H',
		'CAS-4A',
		'CAS-4B',
		'DO-64',
		'EO-79',
		'EO-88',
		'FO-118',
		'FO-12',
		'FO-20',
		'FO-29',
		'FO-99',
		'FS-3',
		'HO-107',
		'HO-113',
		'HO-119',
		'HO-68',
		'INSPR7',
		'IO-117',
		'IO-86',
		'JO-97',
		'KEDR',
		'LEDSAT',
		'LO-19',
		'LO-78',
		'LO-87',
		'LO-90',
		'MAYA-3',
		'MAYA-4',
		'MIREX',
		'MO-112',
		'NO-103',
		'NO-104',
		'NO-44',
		'NO-83',
		'NO-84',
		'PO-101',
		'QO-100',
		'RS-1',
		'RS-10',
		'RS-11',
		'RS-12',
		'RS-13',
		'RS-15',
		'RS-2',
		'RS-44',
		'RS-5',
		'RS-6',
		'RS-7',
		'RS-8',
		'SAREX',
		'SO-121',
		'SO-35',
		'SO-41',
		'SO-50',
		'SO-67',
		'TAURUS',
		'TEVEL1',
		'TEVEL2',
		'TEVEL3',
		'TEVEL4',
		'TEVEL5',
		'TEVEL6',
		'TEVEL7',
		'TEVEL8',
		'TO-108',
		'UKUBE1',
		'UO-14',
		'UVSQ',
		'VO-52',
		'XW-2A',
		'XW-2B',
		'XW-2C',
		'XW-2D',
		'XW-2E',
		'XW-2F',
	);

	public function up() {
		if ($this->db->table_exists('satellite')) {

			foreach ($this->satellites as $exportname => $name) {
				$this->update_sat_table($exportname, $name);
				$this->update_log_table($exportname, $name);
			}
		}

		if (!$this->db->field_exists('lotw', 'satellite')) {
			$fields = array(
				'lotw VARCHAR(1) NOT NULL DEFAULT "N" AFTER `orbit`',
			);
			$this->dbforge->add_column('satellite', $fields);
		}

		$this->set_lotw($this->lotw_sats);
	}

	public function down() {
		if ($this->db->table_exists('satellite')) {

			foreach ($this->satellites as $exportname => $name) {
				$this->update_sat_table($name, $exportname);
				$this->update_log_table($name, $exportname);
			}
		}

		if ($this->db->field_exists('lotw', 'satellite')) {
			$this->dbforge->drop_column('satellite', 'lotw');
		}
	}

	function update_sat_table($from, $to) {
		$sql= "UPDATE `satellite` SET `name` = '".$to."' WHERE `name` = '".$from."';";
		$this->db->query($sql);
	}

	function update_log_table($from, $to) {
		$sql= "UPDATE ".$this->config->item('table_name')." SET `COL_SAT_NAME` = '".$to."' WHERE `COL_SAT_NAME` = '".$from."';";
		$this->db->query($sql);
	}

	function set_lotw($sats) {
		$sql = "UPDATE `satellite` SET `lotw` = 'Y' WHERE `name` IN ('".implode('\',\'', $sats)."');";
		$this->db->query($sql);
	}

}
