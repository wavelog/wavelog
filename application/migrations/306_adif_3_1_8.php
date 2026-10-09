<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_adif_3_1_8 extends CI_Migration {
private $current_version = "3.1.8";
private $previous_version = "3.1.7";

	public function up() {

		// Adds new modes from ADIF 3.1.8 specification
		$modes = array(
			array('mode' => "MFSK", 'submode' => "JTTY", 'qrgmode' => "DATA", 'active' => 1),
		);

		foreach ($modes as $mode) {
			$exists = $this->db->where('submode', $mode['submode'])
							->get('adif_modes')
							->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('adif_modes', $mode);
			}
		}

		$contests = array(
			array('name' => 'SKCC Weekend Sprintathon', 'adifname' => 'SKCC-WES', 'active' => 1),
			array('name' => 'SKCC Straight Key Sprint', 'adifname' => 'SKCC-SKS', 'active' => 1),
			array('name' => 'SKCC Straight Key Sprint Asia', 'adifname' => 'SKCC-SKSA', 'active' => 1),
			array('name' => 'SKCC Straight Key Sprint Europe', 'adifname' => 'SKCC-SKSE', 'active' => 1)
		);

		foreach ($contests as $contest) {
			$exists = $this->db->where('adifname', $contest['adifname'])
				->get('contest')
				->num_rows() > 0;
			if (!$exists) {
				$this->db->insert('contest', $contest);
			}
		}

		$this->db->where('option_name', 'adif_version');
		$this->db->delete('options');
		$this->db->insert('options', array('option_name' => 'adif_version', 'option_value' => $this->current_version));

		// Fix some missing contests or wrong names. Excluded from downgrade
		$this->db->where('adifname', 'K1USNSST');
		$this->db->update('contest', array('adifname' => 'K1USN-SST'));

		$exists = $this->db->where('adifname', 'WFD')->get('contest')->num_rows() > 0;
		if (!$exists) {
			$this->db->insert('contest', array('name' => 'Winter Field Day (2017 and later)','adifname' => 'WFD'));
		}

		// Remove the " (import-only)" tags from ADIF names of the contests
		$this->db->query("UPDATE contest SET adifname = REPLACE(adifname, ' (import-only)', '') WHERE adifname LIKE '%(import-only)%'");

		// Replace some non-printable chars from name and adifname which were instroduced by old mig 063 (only affects migrated CL instances)
		$this->db->query("UPDATE contest SET name = 'WIA Harry Angel Memorial 80m Sprint' WHERE adifname = 'WIA-HARRY ANGEL'");
		$this->db->query("UPDATE contest SET name = 'WIA John Moyle Memorial Field Day' WHERE adifname = 'WIA-JMMFD'");
		$this->db->query("UPDATE contest SET name = 'WIA Oceania DX (OCDX) Contest' WHERE adifname = 'WIA-OCDX'");
		$this->db->query("UPDATE contest SET name = 'WIA Remembrance Day' WHERE adifname = 'WIA-REMEMBRANCE'");
		$this->db->query("UPDATE contest SET name = 'WIA Ross Hull Memorial VHF/UHF Contest' WHERE adifname = 'WIA-ROSS HULL'");
		$this->db->query("UPDATE contest SET name = 'WIA Trans Tasman Low Bands Challenge' WHERE adifname = 'WIA-TRANS TASMAN'");
		$this->db->query("UPDATE contest SET name = 'WIA VHF UHF Field Days' WHERE adifname = 'WIA-VHF/UHF FD'");
		$this->db->query("UPDATE contest SET name = 'WIA VK Shires' WHERE adifname = 'WIA-VK SHIRES'");
		$this->db->query("UPDATE contest SET name = 'IARC Holyland Contest' WHERE adifname = 'HOLYLAND'");
		$this->db->query("UPDATE contest SET adifname = 'URE-DX' WHERE name = 'Ukrainian DX Contest'");

	}

	public function down() {
		// remove added contests of this migration
		$contests = array(
			'SKCC-WES',
			'SKCC-SKS',
			'SKCC-SKSA',
			'SKCC-SKSE'
		);
		$this->db->where_in('adifname', $contests);
		$this->db->delete('contest');

		// Reset auto increment value
		$this->db->select('id');
		$this->db->order_by('id', 'DESC');
		$this->db->limit(1);
		$no = $this->db->get('contest')->row()->id;

		$sql = 'ALTER TABLE `contest` AUTO_INCREMENT = ?;';
		$this->db->query($sql, array(++$no));

		// remove the modes that were added in this migration
		$mode_names = array(
			'JTTY'
		);

		$this->db->where_in('submode', $mode_names);
		$this->db->delete('adif_modes');

		$this->db->where('option_name', 'adif_version');
		$this->db->delete('options');

		$this->db->insert('options', array('option_name' => 'adif_version', 'option_value' => $this->previous_version));
	}
}
