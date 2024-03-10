<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_satellite_table extends CI_Migration {

    public function up()
    {
		if (!$this->db->table_exists('satellite')) {

			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 6,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					'null' => FALSE
				),
				'name' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'exportname' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'orbit' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
			));
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table('satellite');

			$data = array(
				array('name' => 'AISAT-1', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'ARISS', 'exportname' => 'ISS', 'orbit' => 'LEO'),
				array('name' => 'AO-7', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'AO-27', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'AO-73', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'AO-91', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'AO-92', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'CAS-3H', 'exportname' => 'LILACSAT-2', 'orbit' => 'LEO'),
				array('name' => 'CAS-4A', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'CAS-4B', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'FO-118', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TO-108', 'exportname' => 'CAS-6', 'orbit' => 'LEO'),
				array('name' => 'EO-88', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'FO-29', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'FO-99', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'AO-109', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'FS-3', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'HO-107', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'HO-119', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'IO-86', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'IO-117', 'exportname' => 'GREENCUBE', 'orbit' => 'MEO'),
				array('name' => 'JO-97', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'LEDSAT', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'Lilacsat-1', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'MO-112', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'NO-44', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'NO-84', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'NO-104', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'PO-101', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'QO-100', 'exportname' => '', 'orbit' => 'GEO'),
				array('name' => 'RS-44', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'HO-113', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'SO-50', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'SO-121', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-1', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-2', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-3', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-4', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-5', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-6', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-7', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'TEVEL-8', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'EASAT-2', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'HADES', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'INSPIRE-SAT 7', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'UVSQ-SAT', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'XW-2A', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'XW-2B', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'XW-2C', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'XW-2D', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'XW-2F', 'exportname' => '', 'orbit' => 'LEO'),
				array('name' => 'UKUBE1', 'exportname' => 'UKUBE-1', 'orbit' => 'LEO'),
				array('name' => 'KEDR', 'exportname' => 'ARISSAT-1', 'orbit' => 'LEO'),
				array('name' => 'TAURUS', 'exportname' => 'TAURUS-1', 'orbit' => 'LEO'),
			);
			$this->db->insert_batch('satellite', $data);
		}

		if (!$this->db->table_exists('satellitemode')) {

			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 6,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					'null' => FALSE
				),
				'name' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'satelliteid' => array(
					'type' => 'INT',
					'constraint' => 6,
					'unsigned' => TRUE,
					'null' => FALSE
				),
				'uplink_mode' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'uplink_freq' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'downlink_mode' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'downlink_freq' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
			));
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_key('callsign', TRUE);

			$this->dbforge->create_table('satellitemode');

			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V', id, 'PKT', '145825000', 'PKT', '145825000' FROM satellite WHERE name = 'AISAT-1';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V', id, 'PKT', '145825000', 'PKT', '145825000' FROM satellite WHERE name = 'ARISS';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'FM', '145990000', 'FM', '437800000' FROM satellite WHERE name = 'ARISS';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '432150000', 'USB', '145950000' FROM satellite WHERE name = 'AO-7';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/A', id, 'USB', '145900000', 'USB', '29450000' FROM satellite WHERE name = 'AO-7';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'FM', '145850000', 'FM', '436795000' FROM satellite WHERE name = 'AO-27';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '435140000', 'USB', '145960000' FROM satellite WHERE name = 'AO-73';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'FM', '435250000', 'FM', '145960000' FROM satellite WHERE name = 'AO-91';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'FM', '435250000', 'FM', '145880000' FROM satellite WHERE name = 'AO-92';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'L/V', id, 'FM', '1267350000', 'FM', '145880000' FROM satellite WHERE name = 'AO-92';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'FM', '144350000', 'FM', '437200000' FROM satellite WHERE name = 'CAS-3H';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '435220000', 'USB', '145870000' FROM satellite WHERE name = 'CAS-4A';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'H/U', id, 'USB', '21435000', 'USB', '435505000' FROM satellite WHERE name = 'FO-118';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145820000', 'USB', '435540000' FROM satellite WHERE name = 'FO-118';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'FM', '145925000', 'FM', '435600000' FROM satellite WHERE name = 'FO-118';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '435280000', 'USB', '145925000' FROM satellite WHERE name = 'TO-108';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '435030000', 'USB', '145975000' FROM satellite WHERE name = 'EO-88';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145950000', 'USB', '435850000' FROM satellite WHERE name = 'FO-29';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145915000', 'USB', '435895000' FROM satellite WHERE name = 'FO-99';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145875000', 'USB', '435775000' FROM satellite WHERE name = 'AO-109';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'PKT', '145840000', 'PKT', '435103000' FROM satellite WHERE name = 'FS-3';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '435925000', 'USB', '145925000' FROM satellite WHERE name = 'HO-107';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'LSB', '145870000', 'USB', '435180000' FROM satellite WHERE name = 'HO-119';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'V/U', id, 'FM', '145880000', 'FM', '435880000' FROM satellite WHERE name = 'IO-86';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/U', id, 'PKT', '435310000', 'PKT', '435310000' FROM satellite WHERE name = 'IO-117';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/V', id, 'LSB', '435110000', 'USB', '145865000' FROM satellite WHERE name = 'JO-97';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq)	SELECT 'U/U', id, 'PKT', '435310000', 'PKT', '435190000' FROM satellite WHERE name = 'LEDSAT';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '144350000', 'FM', '437200000' FROM satellite WHERE name = 'Lilacsat-1';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'PKT', '145987500', 'PKT', '436925000' FROM satellite WHERE name = 'MO-112';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V', id, 'PKT', '145825000', 'PKT', '145825000' FROM satellite WHERE name = 'NO-44';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'A/U', id, 'LSB', '28120000', 'FM', '435350000' FROM satellite WHERE name = 'NO-84';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V', id, 'PKT', '145825000', 'PKT', '145825000' FROM satellite WHERE name = 'NO-84';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'A/U', id, 'LSB', '29481500', 'FM', '435350000' FROM satellite WHERE name = 'NO-104';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'FM', '437500000', 'FM', '145900000' FROM satellite WHERE name = 'PO-101';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'S/X', id, 'USB', '2400175000', 'USB', '10489675000' FROM satellite WHERE name = 'QO-100';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'LSB', '145965000', 'USB', '435640000' FROM satellite WHERE name = 'RS-44';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'LSB', '145870000', 'USB', '435180000' FROM satellite WHERE name = 'HO-113';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145850000', 'FM', '436795000' FROM satellite WHERE name = 'SO-50';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145875000', 'FM', '436666000' FROM satellite WHERE name = 'SO-121';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-1';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-2';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-3';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-4';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-5';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-6';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-7';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '436400000' FROM satellite WHERE name = 'TEVEL-8';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145875000', 'FM', '436666000' FROM satellite WHERE name = 'EASAT-2';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145925000', 'FM', '436888000' FROM satellite WHERE name = 'HADES';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145970000', 'FM', '437410000' FROM satellite WHERE name = 'INSPIRE-SAT 7';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'V/U', id, 'FM', '145905000', 'FM', '437020000' FROM satellite WHERE name = 'UVSQ-SAT';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'LSB', '435040000', 'USB', '145675000' FROM satellite WHERE name = 'XW-2A';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'LSB', '435100000', 'USB', '145675000' FROM satellite WHERE name = 'XW-2B';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'LSB', '435160000', 'USB', '145805000' FROM satellite WHERE name = 'XW-2C';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'LSB', '435220000', 'USB', '145870000' FROM satellite WHERE name = 'XW-2D';");
			$this->db->query("INSERT INTO satellitemode (name, satelliteid, uplink_mode, uplink_freq, downlink_mode, downlink_freq) SELECT 'U/V', id, 'LSB', '435340000', 'USB', '145990000' FROM satellite WHERE name = 'XW-2F';");
		}
	}

    public function down()
    {
		$this->dbforge->drop_table('satellite');
		$this->dbforge->drop_table('satellitemode');
	}
}
