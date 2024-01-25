<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_create_modes_table extends CI_Migration {

	public function up() {
		$this->dbforge->add_field('id');

		$this->dbforge->add_field(array(
				'mode' => array(
						'type' => 'VARCHAR',
						'constraint' => 12,
				),
				'submode' => array(
						'type' => 'VARCHAR',
						'constraint' => 12,
						'null' => TRUE,
				),
				'qrgmode' => array(
						'type' => 'VARCHAR',
						'constraint' => 4,
				),
				'active' => array(
						'type' => 'INT',
				),
		));
		$this->dbforge->create_table('adif_modes');
	

		$this->db->query("INSERT INTO `adif_modes` (`mode`, `submode`, `qrgmode`, `active`) VALUES('AM', NULL, 'SSB', 1)
		,('ARDOP', NULL, 'DATA', 0)
		,('ATV', NULL, 'DATA', 0)
		,('C4FM', NULL, 'DATA', 1)
		,('CHIP', NULL, 'DATA', 0)
		,('CHIP', 'CHIP128', 'DATA', 0)
		,('CHIP', 'CHIP64', 'DATA', 0)
		,('CLO', NULL, 'DATA', 0)
		,('CONTESTI', NULL, 'DATA', 0)
		,('CW', NULL, 'CW', 1)
		,('CW', 'PCW', 'CW', 0)
		,('DIGITALVOICE', NULL, 'DATA', 0)
		,('DOMINO', NULL, 'DATA', 0)
		,('DOMINO', 'DOMINOEX', 'DATA', 0)
		,('DOMINO', 'DOMINOF', 'DATA', 0)
		,('DSTAR', NULL, 'DATA', 0)
		,('FAX', NULL, 'DATA', 0)
		,('FM', NULL, 'SSB', 1)
		,('FSK441', NULL, 'DATA', 1)
		,('FT8', NULL, 'DATA', 1)
		,('HELL', NULL, 'DATA', 1)
		,('HELL', 'FMHELL', 'DATA', 0)
		,('HELL', 'FSKHELL', 'DATA', 0)
		,('HELL', 'HELL80', 'DATA', 1)
		,('HELL', 'HFSK', 'DATA', 0)
		,('HELL', 'PSKHELL', 'DATA', 0)
		,('ISCAT', NULL, 'DATA', 1)
		,('ISCAT', 'ISCAT-A', 'DATA', 1)
		,('ISCAT', 'ISCAT-B', 'DATA', 1)
		,('JT4', NULL, 'DATA', 0)
		,('JT4', 'JT4A', 'DATA', 0)
		,('JT4', 'JT4B', 'DATA', 0)
		,('JT4', 'JT4C', 'DATA', 0)
		,('JT4', 'JT4D', 'DATA', 0)
		,('JT4', 'JT4E', 'DATA', 0)
		,('JT4', 'JT4F', 'DATA', 0)
		,('JT4', 'JT4G', 'DATA', 0)
		,('JT44', NULL, 'DATA', 0)
		,('JT65', NULL, 'DATA', 1)
		,('JT65', 'JT65A', 'DATA', 0)
		,('JT65', 'JT65B', 'DATA', 1)
		,('JT65', 'JT65B2', 'DATA', 0)
		,('JT65', 'JT65C', 'DATA', 0)
		,('JT65', 'JT65C2', 'DATA', 0)
		,('JT6C', NULL, 'DATA', 1)
		,('JT6M', NULL, 'DATA', 1)
		,('JT9', NULL, 'DATA', 1)
		,('JT9', 'JT9-1', 'DATA', 1)
		,('JT9', 'JT9-10', 'DATA', 0)
		,('JT9', 'JT9-2', 'DATA', 0)
		,('JT9', 'JT9-30', 'DATA', 0)
		,('JT9', 'JT9-5', 'DATA', 0)
		,('JT9', 'JT9A', 'DATA', 0)
		,('JT9', 'JT9B', 'DATA', 0)
		,('JT9', 'JT9C', 'DATA', 0)
		,('JT9', 'JT9D', 'DATA', 0)
		,('JT9', 'JT9E', 'DATA', 0)
		,('JT9', 'JT9E FAST', 'DATA', 0)
		,('JT9', 'JT9F', 'DATA', 0)
		,('JT9', 'JT9F FAST', 'DATA', 0)
		,('JT9', 'JT9G', 'DATA', 0)
		,('JT9', 'JT9G FAST', 'DATA', 0)
		,('JT9', 'JT9H', 'DATA', 0)
		,('JT9', 'JT9H FAST', 'DATA', 0)
		,('JTMS', NULL, 'DATA', 0)
		,('JTMSK', NULL, 'DATA', 0)
		,('MFSK', NULL, 'DATA', 1)
		,('MFSK', 'FSQCALL', 'DATA', 0)
		,('MFSK', 'FT4', 'DATA', 1)
		,('MFSK', 'JS8', 'DATA', 1)
		,('MFSK', 'MFSK11', 'DATA', 0)
		,('MFSK', 'MFSK128', 'DATA', 0)
		,('MFSK', 'MFSK16', 'DATA', 1)
		,('MFSK', 'MFSK22', 'DATA', 0)
		,('MFSK', 'MFSK31', 'DATA', 0)
		,('MFSK', 'MFSK32', 'DATA', 0)
		,('MFSK', 'MFSK4', 'DATA', 0)
		,('MFSK', 'MFSK64', 'DATA', 0)
		,('MFSK', 'MFSK8', 'DATA', 0)
		,('MSK144', NULL, 'DATA', 1)
		,('MT63', NULL, 'DATA', 0)
		,('OLIVIA', NULL, 'DATA', 0)
		,('OLIVIA', 'OLIVIA 16/10', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 16/50', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 32/10', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 4/125', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 4/250', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 8/250', 'DATA', 0)
		,('OLIVIA', 'OLIVIA 8/500', 'DATA', 0)
		,('OPERA', NULL, 'DATA', 0)
		,('OPERA', 'OPERA-BEACON', 'DATA', 0)
		,('OPERA', 'OPERA-QSO', 'DATA', 0)
		,('PAC', NULL, 'DATA', 0)
		,('PAC', 'PAC2', 'DATA', 0)
		,('PAC', 'PAC3', 'DATA', 0)
		,('PAC', 'PAC4', 'DATA', 0)
		,('PAX', NULL, 'DATA', 0)
		,('PAX', 'PAX2', 'DATA', 0)
		,('PKT', NULL, 'DATA', 1)
		,('PSK', NULL, 'DATA', 1)
		,('PSK', 'FSK31', 'DATA', 0)
		,('PSK', 'PSK10', 'DATA', 0)
		,('PSK', 'PSK1000', 'DATA', 0)
		,('PSK', 'PSK125', 'DATA', 1)
		,('PSK', 'PSK250', 'DATA', 0)
		,('PSK', 'PSK31', 'DATA', 1)
		,('PSK', 'PSK500', 'DATA', 0)
		,('PSK', 'PSK63', 'DATA', 1)
		,('PSK', 'PSK63F', 'DATA', 0)
		,('PSK', 'PSKAM10', 'DATA', 0)
		,('PSK', 'PSKAM31', 'DATA', 0)
		,('PSK', 'PSKAM50', 'DATA', 0)
		,('PSK', 'PSKFEC31', 'DATA', 0)
		,('PSK', 'QPSK125', 'DATA', 1)
		,('PSK', 'QPSK250', 'DATA', 0)
		,('PSK', 'QPSK31', 'DATA', 1)
		,('PSK', 'QPSK500', 'DATA', 0)
		,('PSK', 'QPSK63', 'DATA', 1)
		,('PSK', 'SIM31', 'DATA', 0)
		,('PSK2K', NULL, 'DATA', 0)
		,('Q15', NULL, 'DATA', 0)
		,('QRA64', NULL, 'DATA', 1)
		,('QRA64', 'QRA64A', 'DATA', 0)
		,('QRA64', 'QRA64B', 'DATA', 0)
		,('QRA64', 'QRA64C', 'DATA', 0)
		,('QRA64', 'QRA64D', 'DATA', 0)
		,('QRA64', 'QRA64E', 'DATA', 0)
		,('ROS', NULL, 'DATA', 1)
		,('ROS', 'ROS-EME', 'DATA', 0)
		,('ROS', 'ROS-HF', 'DATA', 0)
		,('ROS', 'ROS-MF', 'DATA', 0)
		,('RTTY', NULL, 'DATA', 1)
		,('RTTY', 'ASCI', 'DATA', 0)
		,('RTTYM', NULL, 'DATA', 0)
		,('SSB', NULL, 'SSB', 1)
		,('SSB', 'LSB', 'SSB', 1)
		,('SSB', 'USB', 'SSB', 1)
		,('SSTV', NULL, 'DATA', 1)
		,('T10', NULL, 'DATA', 0)
		,('THOR', NULL, 'DATA', 0)
		,('THRB', NULL, 'DATA', 0)
		,('THRB', 'THRBX', 'DATA', 0)
		,('TOR', NULL, 'DATA', 0)
		,('TOR', 'AMTORFEC', 'DATA', 0)
		,('TOR', 'GTOR', 'DATA', 0)
		,('V4', NULL, 'DATA', 0)
		,('VOI', NULL, 'DATA', 0)
		,('WINMOR', NULL, 'DATA', 0)
		,('WSPR', NULL, 'DATA', 0);");
	}

	public function down(){
		$this->dbforge->drop_table('config');
	}
}
