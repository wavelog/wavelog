<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_primary_subdivisions extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('primary_subdivisions')) {
			$this->dbforge->add_field(array(
				'adif' => array(
					'type' => 'SMALLINT',
					'constraint' => 20,
					'unsigned' => TRUE,
				),
				'state' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'null' => FALSE,
				),
				'subdivision' => array(
					'type' => 'VARCHAR',
					'constraint' => 250,
					'null' => FALSE,
				),
			));
			$this->dbforge->create_table('primary_subdivisions');
			$data = array(
				array('adif' => 339, 'state' => '01', 'subdivision' => 'Hokkaido'),
				array('adif' => 339, 'state' => '02', 'subdivision' => 'Aomori'),
				array('adif' => 339, 'state' => '03', 'subdivision' => 'Iwate'),
				array('adif' => 339, 'state' => '04', 'subdivision' => 'Akita'),
				array('adif' => 339, 'state' => '05', 'subdivision' => 'Yamagata'),
				array('adif' => 339, 'state' => '06', 'subdivision' => 'Miyagi'),
				array('adif' => 339, 'state' => '07', 'subdivision' => 'Fukushima'),
				array('adif' => 339, 'state' => '08', 'subdivision' => 'Niigata'),
				array('adif' => 339, 'state' => '09', 'subdivision' => 'Nagano'),
				array('adif' => 339, 'state' => '10', 'subdivision' => 'Tokyo'),
				array('adif' => 339, 'state' => '11', 'subdivision' => 'Kanagawa'),
				array('adif' => 339, 'state' => '12', 'subdivision' => 'Chiba'),
				array('adif' => 339, 'state' => '13', 'subdivision' => 'Saitama'),
				array('adif' => 339, 'state' => '14', 'subdivision' => 'Ibaraki'),
				array('adif' => 339, 'state' => '15', 'subdivision' => 'Tochigi'),
				array('adif' => 339, 'state' => '16', 'subdivision' => 'Gunma'),
				array('adif' => 339, 'state' => '17', 'subdivision' => 'Yamanashi'),
				array('adif' => 339, 'state' => '18', 'subdivision' => 'Shizuoka'),
				array('adif' => 339, 'state' => '19', 'subdivision' => 'Gifu'),
				array('adif' => 339, 'state' => '20', 'subdivision' => 'Aichi'),
				array('adif' => 339, 'state' => '21', 'subdivision' => 'Mie'),
				array('adif' => 339, 'state' => '22', 'subdivision' => 'Kyoto'),
				array('adif' => 339, 'state' => '23', 'subdivision' => 'Shiga'),
				array('adif' => 339, 'state' => '24', 'subdivision' => 'Nara'),
				array('adif' => 339, 'state' => '25', 'subdivision' => 'Osaka'),
				array('adif' => 339, 'state' => '26', 'subdivision' => 'Wakayama'),
				array('adif' => 339, 'state' => '27', 'subdivision' => 'Hyogo'),
				array('adif' => 339, 'state' => '28', 'subdivision' => 'Toyama'),
				array('adif' => 339, 'state' => '29', 'subdivision' => 'Fukui'),
				array('adif' => 339, 'state' => '30', 'subdivision' => 'Ishikawa'),
				array('adif' => 339, 'state' => '31', 'subdivision' => 'Okayama'),
				array('adif' => 339, 'state' => '32', 'subdivision' => 'Shimane'),
				array('adif' => 339, 'state' => '33', 'subdivision' => 'Yamaguchi'),
				array('adif' => 339, 'state' => '34', 'subdivision' => 'Tottori'),
				array('adif' => 339, 'state' => '35', 'subdivision' => 'Hiroshima'),
				array('adif' => 339, 'state' => '36', 'subdivision' => 'Kagawa'),
				array('adif' => 339, 'state' => '37', 'subdivision' => 'Tokushima'),
				array('adif' => 339, 'state' => '38', 'subdivision' => 'Ehime'),
				array('adif' => 339, 'state' => '39', 'subdivision' => 'Kochi'),
				array('adif' => 339, 'state' => '40', 'subdivision' => 'Fukuoka'),
				array('adif' => 339, 'state' => '41', 'subdivision' => 'Saga'),
				array('adif' => 339, 'state' => '42', 'subdivision' => 'Nagasaki'),
				array('adif' => 339, 'state' => '43', 'subdivision' => 'Kumamoto'),
				array('adif' => 339, 'state' => '44', 'subdivision' => 'Oita'),
				array('adif' => 339, 'state' => '45', 'subdivision' => 'Miyazaki'),
				array('adif' => 339, 'state' => '46', 'subdivision' => 'Kagoshima'),
				array('adif' => 339, 'state' => '47', 'subdivision' => 'Okinawa'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);
		}
	}

	public function down()
	{
		$this->dbforge->drop_table('primary_subdivisions', 'TRUE');
	}
}
