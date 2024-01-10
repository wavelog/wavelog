<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Adds a table with names for primary subdivisions
 * ref:  http://adif.org.uk/314/ADIF_314.htm#Primary_Administrative_Subdivision
*/

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
				array('adif' => 1, 'state' => 'NS', 'subdivision' => 'Nova Scotia'),
				array('adif' => 1, 'state' => 'QC', 'subdivision' => 'Québec'),
				array('adif' => 1, 'state' => 'ON', 'subdivision' => 'Ontario'),
				array('adif' => 1, 'state' => 'MB', 'subdivision' => 'Manitoba'),
				array('adif' => 1, 'state' => 'SK', 'subdivision' => 'Saskatchewan'),
				array('adif' => 1, 'state' => 'AB', 'subdivision' => 'Alberta'),
				array('adif' => 1, 'state' => 'BC', 'subdivision' => 'British Columbia'),
				array('adif' => 1, 'state' => 'NT', 'subdivision' => 'Northwest Territories'),
				array('adif' => 1, 'state' => 'NB', 'subdivision' => 'New Brunswick'),
				array('adif' => 1, 'state' => 'NL', 'subdivision' => 'Newfoundland and Labrador'),
				array('adif' => 1, 'state' => 'YT', 'subdivision' => 'Yukon'),
				array('adif' => 1, 'state' => 'PE', 'subdivision' => 'Prince Edward Island'),
				array('adif' => 1, 'state' => 'NU', 'subdivision' => 'Nunavut'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 5, 'state' => '001', 'subdivision' => 'Brändö'),
				array('adif' => 5, 'state' => '002', 'subdivision' => 'Eckerö'),
				array('adif' => 5, 'state' => '003', 'subdivision' => 'Finström'),
				array('adif' => 5, 'state' => '004', 'subdivision' => 'Föglö'),
				array('adif' => 5, 'state' => '005', 'subdivision' => 'Geta'),
				array('adif' => 5, 'state' => '006', 'subdivision' => 'Hammarland'),
				array('adif' => 5, 'state' => '007', 'subdivision' => 'Jomala'),
				array('adif' => 5, 'state' => '008', 'subdivision' => 'Kumlinge'),
				array('adif' => 5, 'state' => '009', 'subdivision' => 'Kökar'),
				array('adif' => 5, 'state' => '010', 'subdivision' => 'Lemland'),
				array('adif' => 5, 'state' => '011', 'subdivision' => 'Lumparland'),
				array('adif' => 5, 'state' => '012', 'subdivision' => 'Maarianhamina'),
				array('adif' => 5, 'state' => '013', 'subdivision' => 'Saltvik'),
				array('adif' => 5, 'state' => '014', 'subdivision' => 'Sottunga'),
				array('adif' => 5, 'state' => '015', 'subdivision' => 'Sund'),
				array('adif' => 5, 'state' => '016', 'subdivision' => 'Vårdö'),
				array('adif' => 5, 'state' => '051', 'subdivision' => 'Märket'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 6, 'state' => 'AK', 'subdivision' => 'Alaska'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 15, 'state' => 'UO', 'subdivision' => 'Ust-Ordynsky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'AB', 'subdivision' => 'Aginsky Buryatsky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'CB', 'subdivision' => 'Chelyabinsk (Chelyabinskaya oblast)'),
				array('adif' => 15, 'state' => 'SV', 'subdivision' => 'Sverdlovskaya oblast'),
				array('adif' => 15, 'state' => 'PM', 'subdivision' => 'Perm (Permskaya oblast)'),
				array('adif' => 15, 'state' => 'PM', 'subdivision' => 'Permskaya Kraj'),
				array('adif' => 15, 'state' => 'KP', 'subdivision' => 'Komi-Permyatsky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'TO', 'subdivision' => 'Tomsk (Tomskaya oblast)'),
				array('adif' => 15, 'state' => 'HM', 'subdivision' => 'Khanty-Mansyisky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'YN', 'subdivision' => 'Yamalo-Nenetsky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'TN', 'subdivision' => 'Tyumen (Tyumenskaya oblast)'),
				array('adif' => 15, 'state' => 'OM', 'subdivision' => 'Omsk (Omskaya oblast)'),
				array('adif' => 15, 'state' => 'NS', 'subdivision' => 'Novosibirsk (Novosibirskaya oblast)'),
				array('adif' => 15, 'state' => 'KN', 'subdivision' => 'Kurgan (Kurganskaya oblast)'),
				array('adif' => 15, 'state' => 'OB', 'subdivision' => 'Orenburg (Orenburgskaya oblast)'),
				array('adif' => 15, 'state' => 'KE', 'subdivision' => 'Kemerovo (Kemerovskaya oblast)'),
				array('adif' => 15, 'state' => 'BA', 'subdivision' => 'Republic of Bashkortostan'),
				array('adif' => 15, 'state' => 'KO', 'subdivision' => 'Republic of Komi'),
				array('adif' => 15, 'state' => 'AL', 'subdivision' => 'Altaysky Kraj'),
				array('adif' => 15, 'state' => 'GA', 'subdivision' => 'Republic Gorny Altay'),
				array('adif' => 15, 'state' => 'KK', 'subdivision' => 'Krasnoyarsk (Krasnoyarsk Kraj)'),
				array('adif' => 15, 'state' => 'TM', 'subdivision' => 'Taymyr Autonomous Okrug'),
				array('adif' => 15, 'state' => 'HK', 'subdivision' => 'Khabarovsk (Khabarovsky Kraj)'),
				array('adif' => 15, 'state' => 'EA', 'subdivision' => 'Yevreyskaya Autonomous Oblast'),
				array('adif' => 15, 'state' => 'SL', 'subdivision' => 'Sakhalin (Sakhalinskaya oblast)'),
				array('adif' => 15, 'state' => 'EV', 'subdivision' => 'Evenkiysky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'MG', 'subdivision' => 'Magadan (Magadanskaya oblast)'),
				array('adif' => 15, 'state' => 'AM', 'subdivision' => 'Amurskaya oblast'),
				array('adif' => 15, 'state' => 'CK', 'subdivision' => 'Chukotka Autonomous Okrug'),
				array('adif' => 15, 'state' => 'PK', 'subdivision' => 'Primorsky Kraj'),
				array('adif' => 15, 'state' => 'BU', 'subdivision' => 'Republic of Buryatia'),
				array('adif' => 15, 'state' => 'YA', 'subdivision' => 'Sakha (Yakut) Republic'),
				array('adif' => 15, 'state' => 'IR', 'subdivision' => 'Irkutsk (Irkutskaya oblast)'),
				array('adif' => 15, 'state' => 'CT', 'subdivision' => 'Zabaykalsky Kraj'),
				array('adif' => 15, 'state' => 'HA', 'subdivision' => 'Republic of Khakassia'),
				array('adif' => 15, 'state' => 'KY', 'subdivision' => 'Koryaksky Autonomous Okrug'),
				array('adif' => 15, 'state' => 'TU', 'subdivision' => 'Republic of Tuva'),
				array('adif' => 15, 'state' => 'KT', 'subdivision' => 'Kamchatka (Kamchatskaya oblast)'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

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
