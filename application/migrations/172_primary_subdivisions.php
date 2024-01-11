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
				array('adif' => 21, 'state' => 'IB', 'subdivision' => 'Baleares'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 27, 'state' => 'MI', 'subdivision' => 'Minsk (Minskaya voblasts)'),
				array('adif' => 27, 'state' => 'BR', 'subdivision' => 'Brest (Brestskaya voblasts)'),
				array('adif' => 27, 'state' => 'HR', 'subdivision' => 'Grodno (Hrodzenskaya voblasts)'),
				array('adif' => 27, 'state' => 'VI', 'subdivision' => 'Vitebsk (Vitsyebskaya voblasts)'),
				array('adif' => 27, 'state' => 'MA', 'subdivision' => 'Mogilev (Mahilyowskaya voblasts)'),
				array('adif' => 27, 'state' => 'HO', 'subdivision' => 'Gomel (Homyel skaya voblasts)'),
				array('adif' => 27, 'state' => 'HM', 'subdivision' => 'Horad Minsk'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 32, 'state' => 'CE', 'subdivision' => 'Ceuta'),
				array('adif' => 32, 'state' => 'ML', 'subdivision' => 'Melilla'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 50, 'state' => 'COL', 'subdivision' => 'Colima'),
				array('adif' => 50, 'state' => 'DF', 'subdivision' => 'Distrito Federal'),
				array('adif' => 50, 'state' => 'EMX', 'subdivision' => 'Estado de México'),
				array('adif' => 50, 'state' => 'GTO', 'subdivision' => 'Guanajuato'),
				array('adif' => 50, 'state' => 'HGO', 'subdivision' => 'Hidalgo'),
				array('adif' => 50, 'state' => 'JAL', 'subdivision' => 'Jalisco'),
				array('adif' => 50, 'state' => 'MIC', 'subdivision' => 'Michoacán de Ocampo'),
				array('adif' => 50, 'state' => 'MOR', 'subdivision' => 'Morelos'),
				array('adif' => 50, 'state' => 'NAY', 'subdivision' => 'Nayarit'),
				array('adif' => 50, 'state' => 'PUE', 'subdivision' => 'Puebla'),
				array('adif' => 50, 'state' => 'QRO', 'subdivision' => 'Querétaro de Arteaga'),
				array('adif' => 50, 'state' => 'TLX', 'subdivision' => 'Tlaxcala'),
				array('adif' => 50, 'state' => 'VER', 'subdivision' => 'Veracruz-Llave'),
				array('adif' => 50, 'state' => 'AGS', 'subdivision' => 'Aguascalientes'),
				array('adif' => 50, 'state' => 'BC', 'subdivision' => ' Baja California'),
				array('adif' => 50, 'state' => 'BCS', 'subdivision' => 'Baja California Sur'),
				array('adif' => 50, 'state' => 'CHH', 'subdivision' => 'Chihuahua'),
				array('adif' => 50, 'state' => 'COA', 'subdivision' => 'Coahuila de Zaragoza'),
				array('adif' => 50, 'state' => 'DGO', 'subdivision' => 'Durango'),
				array('adif' => 50, 'state' => 'NL', 'subdivision' => ' Nuevo Leon'),
				array('adif' => 50, 'state' => 'SLP', 'subdivision' => 'San Luis Potosí'),
				array('adif' => 50, 'state' => 'SIN', 'subdivision' => 'Sinaloa'),
				array('adif' => 50, 'state' => 'SON', 'subdivision' => 'Sonora'),
				array('adif' => 50, 'state' => 'TMS', 'subdivision' => 'Tamaulipas'),
				array('adif' => 50, 'state' => 'ZAC', 'subdivision' => 'Zacatecas'),
				array('adif' => 50, 'state' => 'CAM', 'subdivision' => 'Campeche'),
				array('adif' => 50, 'state' => 'CHS', 'subdivision' => 'Chiapas'),
				array('adif' => 50, 'state' => 'GRO', 'subdivision' => 'Guerrero'),
				array('adif' => 50, 'state' => 'OAX', 'subdivision' => 'Oaxaca'),
				array('adif' => 50, 'state' => 'QTR', 'subdivision' => 'Quintana Roo'),
				array('adif' => 50, 'state' => 'TAB', 'subdivision' => 'Tabasco'),
				array('adif' => 50, 'state' => 'YUC', 'subdivision' => 'Yucatán'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 54, 'state' => 'SP', 'subdivision' => 'City of St. Petersburg'),
				array('adif' => 54, 'state' => 'LO', 'subdivision' => 'Leningradskaya oblast'),
				array('adif' => 54, 'state' => 'KL', 'subdivision' => 'Republic of Karelia'),
				array('adif' => 54, 'state' => 'AR', 'subdivision' => 'Arkhangelsk (Arkhangelskaya oblast)'),
				array('adif' => 54, 'state' => 'NO', 'subdivision' => 'Nenetsky Autonomous Okrug'),
				array('adif' => 54, 'state' => 'VO', 'subdivision' => 'Vologda (Vologodskaya oblast)'),
				array('adif' => 54, 'state' => 'NV', 'subdivision' => 'Novgorodskaya oblast'),
				array('adif' => 54, 'state' => 'PS', 'subdivision' => 'Pskov (Pskovskaya oblast)'),
				array('adif' => 54, 'state' => 'MU', 'subdivision' => 'Murmansk (Murmanskaya oblast)'),
				array('adif' => 54, 'state' => 'MA', 'subdivision' => 'City of Moscow'),
				array('adif' => 54, 'state' => 'MO', 'subdivision' => 'Moscowskaya oblast'),
				array('adif' => 54, 'state' => 'OR', 'subdivision' => 'Oryel (Orlovskaya oblast)'),
				array('adif' => 54, 'state' => 'LP', 'subdivision' => 'Lipetsk (Lipetskaya oblast)'),
				array('adif' => 54, 'state' => 'TV', 'subdivision' => 'Tver (Tverskaya oblast)'),
				array('adif' => 54, 'state' => 'SM', 'subdivision' => 'Smolensk (Smolenskaya oblast)'),
				array('adif' => 54, 'state' => 'YR', 'subdivision' => 'Yaroslavl (Yaroslavskaya oblast)'),
				array('adif' => 54, 'state' => 'KS', 'subdivision' => 'Kostroma (Kostromskaya oblast)'),
				array('adif' => 54, 'state' => 'TL', 'subdivision' => 'Tula (Tul skaya oblast)'),
				array('adif' => 54, 'state' => 'VR', 'subdivision' => 'Voronezh (Voronezhskaya oblast)'),
				array('adif' => 54, 'state' => 'TB', 'subdivision' => 'Tambov (Tambovskaya oblast)'),
				array('adif' => 54, 'state' => 'RA', 'subdivision' => 'Ryazan (Ryazanskaya oblast)'),
				array('adif' => 54, 'state' => 'NN', 'subdivision' => 'Nizhni Novgorod (Nizhegorodskaya oblast)'),
				array('adif' => 54, 'state' => 'IV', 'subdivision' => 'Ivanovo (Ivanovskaya oblast)'),
				array('adif' => 54, 'state' => 'VL', 'subdivision' => 'Vladimir (Vladimirskaya oblast)'),
				array('adif' => 54, 'state' => 'KU', 'subdivision' => 'Kursk (Kurskaya oblast)'),
				array('adif' => 54, 'state' => 'KG', 'subdivision' => 'Kaluga (Kaluzhskaya oblast)'),
				array('adif' => 54, 'state' => 'BR', 'subdivision' => 'Bryansk (Bryanskaya oblast)'),
				array('adif' => 54, 'state' => 'BO', 'subdivision' => 'Belgorod (Belgorodskaya oblast)'),
				array('adif' => 54, 'state' => 'VG', 'subdivision' => 'Volgograd (Volgogradskaya oblast)'),
				array('adif' => 54, 'state' => 'SA', 'subdivision' => 'Saratov (Saratovskaya oblast)'),
				array('adif' => 54, 'state' => 'PE', 'subdivision' => 'Penza (Penzenskaya oblast)'),
				array('adif' => 54, 'state' => 'SR', 'subdivision' => 'Samara (Samarskaya oblast)'),
				array('adif' => 54, 'state' => 'UL', 'subdivision' => 'Ulyanovsk (Ulyanovskaya oblast)'),
				array('adif' => 54, 'state' => 'KI', 'subdivision' => 'Kirov (Kirovskaya oblast)'),
				array('adif' => 54, 'state' => 'TA', 'subdivision' => 'Republic of Tataria'),
				array('adif' => 54, 'state' => 'MR', 'subdivision' => 'Republic of Marij-El'),
				array('adif' => 54, 'state' => 'MD', 'subdivision' => 'Republic of Mordovia'),
				array('adif' => 54, 'state' => 'UD', 'subdivision' => 'Republic of Udmurtia'),
				array('adif' => 54, 'state' => 'CU', 'subdivision' => 'Republic of Chuvashia'),
				array('adif' => 54, 'state' => 'KR', 'subdivision' => 'Krasnodar (Krasnodarsky Kraj)'),
				array('adif' => 54, 'state' => 'KC', 'subdivision' => 'Republic of Karachaevo-Cherkessia'),
				array('adif' => 54, 'state' => 'ST', 'subdivision' => 'Stavropol (Stavropolsky Kraj)'),
				array('adif' => 54, 'state' => 'KM', 'subdivision' => 'Republic of Kalmykia'),
				array('adif' => 54, 'state' => 'SO', 'subdivision' => 'Republic of Northern Ossetia'),
				array('adif' => 54, 'state' => 'RO', 'subdivision' => 'Rostov-on-Don (Rostovskaya oblast)'),
				array('adif' => 54, 'state' => 'CN', 'subdivision' => 'Republic Chechnya'),
				array('adif' => 54, 'state' => 'IN', 'subdivision' => 'Republic of Ingushetia'),
				array('adif' => 54, 'state' => 'AO', 'subdivision' => 'Astrakhan (Astrakhanskaya oblast)'),
				array('adif' => 54, 'state' => 'DA', 'subdivision' => 'Republic of Daghestan'),
				array('adif' => 54, 'state' => 'KB', 'subdivision' => 'Republic of Kabardino-Balkaria'),
				array('adif' => 54, 'state' => 'AD', 'subdivision' => 'Republic of Adygeya'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 61, 'state' => 'AR', 'subdivision' => 'Arkhangelsk (Arkhangelskaya oblast)'),
				array('adif' => 61, 'state' => 'FJL', 'subdivision' => 'Franz Josef Land'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 100, 'state' => 'C', 'subdivision' => 'Capital federal (Buenos Aires City)'),
				array('adif' => 100, 'state' => 'B', 'subdivision' => 'Buenos Aires Province'),
				array('adif' => 100, 'state' => 'S', 'subdivision' => 'Santa Fe'),
				array('adif' => 100, 'state' => 'H', 'subdivision' => 'Chaco'),
				array('adif' => 100, 'state' => 'P', 'subdivision' => 'Formosa'),
				array('adif' => 100, 'state' => 'X', 'subdivision' => 'Cordoba'),
				array('adif' => 100, 'state' => 'N', 'subdivision' => 'Misiones'),
				array('adif' => 100, 'state' => 'E', 'subdivision' => 'Entre Rios'),
				array('adif' => 100, 'state' => 'T', 'subdivision' => 'Tucumán'),
				array('adif' => 100, 'state' => 'W', 'subdivision' => 'Corrientes'),
				array('adif' => 100, 'state' => 'M', 'subdivision' => 'Mendoza'),
				array('adif' => 100, 'state' => 'G', 'subdivision' => 'Santiago del Estero'),
				array('adif' => 100, 'state' => 'A', 'subdivision' => 'Salta'),
				array('adif' => 100, 'state' => 'J', 'subdivision' => 'San Juan'),
				array('adif' => 100, 'state' => 'D', 'subdivision' => 'San Luis'),
				array('adif' => 100, 'state' => 'K', 'subdivision' => 'Catamarca'),
				array('adif' => 100, 'state' => 'F', 'subdivision' => 'La Rioja'),
				array('adif' => 100, 'state' => 'Y', 'subdivision' => 'Jujuy'),
				array('adif' => 100, 'state' => 'L', 'subdivision' => 'La Pampa'),
				array('adif' => 100, 'state' => 'R', 'subdivision' => 'Rió Negro'),
				array('adif' => 100, 'state' => 'U', 'subdivision' => 'Chubut'),
				array('adif' => 100, 'state' => 'Z', 'subdivision' => 'Santa Cruz'),
				array('adif' => 100, 'state' => 'V', 'subdivision' => 'Tierra del Fuego'),
				array('adif' => 100, 'state' => 'Q', 'subdivision' => 'Neuquén'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 108, 'state' => 'ES', 'subdivision' => 'Espírito Santo'),
				array('adif' => 108, 'state' => 'GO', 'subdivision' => 'Goiás'),
				array('adif' => 108, 'state' => 'SC', 'subdivision' => 'Santa Catarina'),
				array('adif' => 108, 'state' => 'SE', 'subdivision' => 'Sergipe'),
				array('adif' => 108, 'state' => 'AL', 'subdivision' => 'Alagoas'),
				array('adif' => 108, 'state' => 'AM', 'subdivision' => 'Amazonas'),
				array('adif' => 108, 'state' => 'TO', 'subdivision' => 'Tocantins'),
				array('adif' => 108, 'state' => 'AP', 'subdivision' => 'Amapã'),
				array('adif' => 108, 'state' => 'PB', 'subdivision' => 'Paraíba'),
				array('adif' => 108, 'state' => 'MA', 'subdivision' => 'Maranhao'),
				array('adif' => 108, 'state' => 'RN', 'subdivision' => 'Rio Grande do Norte'),
				array('adif' => 108, 'state' => 'PI', 'subdivision' => 'Piaui'),
				array('adif' => 108, 'state' => 'DF', 'subdivision' => 'Oietrito Federal (Brasila)'),
				array('adif' => 108, 'state' => 'CE', 'subdivision' => 'Ceará'),
				array('adif' => 108, 'state' => 'AC', 'subdivision' => 'Acre'),
				array('adif' => 108, 'state' => 'MS', 'subdivision' => 'Mato Grosso do Sul'),
				array('adif' => 108, 'state' => 'RR', 'subdivision' => 'Roraima'),
				array('adif' => 108, 'state' => 'RO', 'subdivision' => 'Rondônia'),
				array('adif' => 108, 'state' => 'RJ', 'subdivision' => 'Rio de Janeiro'),
				array('adif' => 108, 'state' => 'SP', 'subdivision' => 'Sao Paulo'),
				array('adif' => 108, 'state' => 'RS', 'subdivision' => 'Rio Grande do Sul'),
				array('adif' => 108, 'state' => 'MG', 'subdivision' => 'Minas Gerais'),
				array('adif' => 108, 'state' => 'PR', 'subdivision' => 'Paranã'),
				array('adif' => 108, 'state' => 'BA', 'subdivision' => 'Bahia'),
				array('adif' => 108, 'state' => 'PE', 'subdivision' => 'Pernambuco'),
				array('adif' => 108, 'state' => 'PA', 'subdivision' => 'Parã'),
				array('adif' => 108, 'state' => 'MT', 'subdivision' => 'Mato Grosso'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 110, 'state' => 'HI', 'subdivision' => 'Hawaii'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 112, 'state' => 'II', 'subdivision' => 'Antofagasta'),
				array('adif' => 112, 'state' => 'III', 'subdivision' => 'Atacama'),
				array('adif' => 112, 'state' => 'I', 'subdivision' => 'Tarapacá'),
				array('adif' => 112, 'state' => 'XV', 'subdivision' => 'Arica y Parinacota'),
				array('adif' => 112, 'state' => 'IV', 'subdivision' => 'Coquimbo'),
				array('adif' => 112, 'state' => 'V', 'subdivision' => 'Valparaíso'),
				array('adif' => 112, 'state' => 'RM', 'subdivision' => 'Region Metropolitana de Santiago'),
				array('adif' => 112, 'state' => 'VI', 'subdivision' => 'Libertador General Bernardo O Higgins'),
				array('adif' => 112, 'state' => 'VII', 'subdivision' => 'Maule'),
				array('adif' => 112, 'state' => 'VIII', 'subdivision' => 'Bío-Bío'),
				array('adif' => 112, 'state' => 'IX', 'subdivision' => 'La Araucanía'),
				array('adif' => 112, 'state' => 'XIV', 'subdivision' => 'Los Ríos'),
				array('adif' => 112, 'state' => 'X', 'subdivision' => 'Los Lagos'),
				array('adif' => 112, 'state' => 'XI', 'subdivision' => 'Aisén del General Carlos Ibáñez del Campo'),
				array('adif' => 112, 'state' => 'XII', 'subdivision' => 'Magallanes'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 126, 'state' => 'KA', 'subdivision' => 'Kalingrad (Kaliningradskaya oblast)'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 132, 'state' => '16', 'subdivision' => 'Alto Paraguay'),
				array('adif' => 132, 'state' => '19', 'subdivision' => 'Boquerón'),
				array('adif' => 132, 'state' => '15', 'subdivision' => 'Presidente Hayes'),
				array('adif' => 132, 'state' => '13', 'subdivision' => 'Amambay'),
				array('adif' => 132, 'state' => '01', 'subdivision' => 'Concepción'),
				array('adif' => 132, 'state' => '14', 'subdivision' => 'Canindeyú'),
				array('adif' => 132, 'state' => '02', 'subdivision' => 'San Pedro'),
				array('adif' => 132, 'state' => 'ASU', 'subdivision' => 'Asunción'),
				array('adif' => 132, 'state' => '11', 'subdivision' => 'Central'),
				array('adif' => 132, 'state' => '03', 'subdivision' => 'Cordillera'),
				array('adif' => 132, 'state' => '09', 'subdivision' => 'Paraguarí'),
				array('adif' => 132, 'state' => '06', 'subdivision' => 'Caazapl'),
				array('adif' => 132, 'state' => '05', 'subdivision' => 'Caeguazú'),
				array('adif' => 132, 'state' => '04', 'subdivision' => 'Guairá'),
				array('adif' => 132, 'state' => '08', 'subdivision' => 'Miaiones'),
				array('adif' => 132, 'state' => '12', 'subdivision' => 'Ñeembucu'),
				array('adif' => 132, 'state' => '10', 'subdivision' => 'Alto Paraná'),
				array('adif' => 132, 'state' => '07', 'subdivision' => 'Itapua'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 137, 'state' => 'A', 'subdivision' => 'Seoul (Seoul Teugbyeolsi)'),
				array('adif' => 137, 'state' => 'N', 'subdivision' => 'Inchon (Incheon Gwang yeogsi)'),
				array('adif' => 137, 'state' => 'D', 'subdivision' => 'Kangwon-do (Gang weondo)'),
				array('adif' => 137, 'state' => 'C', 'subdivision' => 'Kyunggi-do (Gyeonggido)'),
				array('adif' => 137, 'state' => 'E', 'subdivision' => 'Choongchungbuk-do (Chungcheongbugdo)'),
				array('adif' => 137, 'state' => 'F', 'subdivision' => 'Choongchungnam-do (Chungcheongnamdo)'),
				array('adif' => 137, 'state' => 'R', 'subdivision' => 'Taejon (Daejeon Gwang yeogsi)'),
				array('adif' => 137, 'state' => 'M', 'subdivision' => 'Cheju-do (Jejudo)'),
				array('adif' => 137, 'state' => 'G', 'subdivision' => 'Chollabuk-do (Jeonrabugdo)'),
				array('adif' => 137, 'state' => 'H', 'subdivision' => 'Chollanam-do (Jeonranamdo)'),
				array('adif' => 137, 'state' => 'Q', 'subdivision' => 'Kwangju (Gwangju Gwang yeogsi)'),
				array('adif' => 137, 'state' => 'K', 'subdivision' => 'Kyungsangbuk-do (Gyeongsangbugdo)'),
				array('adif' => 137, 'state' => 'L', 'subdivision' => 'Kyungsangnam-do (Gyeongsangnamdo)'),
				array('adif' => 137, 'state' => 'B', 'subdivision' => 'Pusan (Busan Gwang yeogsi)'),
				array('adif' => 137, 'state' => 'P', 'subdivision' => 'Taegu (Daegu Gwang yeogsi)'),
				array('adif' => 137, 'state' => 'S', 'subdivision' => 'Ulsan (Ulsan Gwanq yeogsi)'),
				array('adif' => 137, 'state' => 'T', 'subdivision' => 'Sejong'),
				array('adif' => 137, 'state' => 'IS', 'subdivision' => 'Special Island'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 138, 'state' => 'KI', 'subdivision' => 'Kure Island'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 144, 'state' => 'MO', 'subdivision' => 'Montevideo'),
				array('adif' => 144, 'state' => 'CA', 'subdivision' => 'Canelones'),
				array('adif' => 144, 'state' => 'SJ', 'subdivision' => 'San José'),
				array('adif' => 144, 'state' => 'CO', 'subdivision' => 'Colonia'),
				array('adif' => 144, 'state' => 'SO', 'subdivision' => 'Soriano'),
				array('adif' => 144, 'state' => 'RN', 'subdivision' => 'Rio Negro'),
				array('adif' => 144, 'state' => 'PA', 'subdivision' => 'Paysandu'),
				array('adif' => 144, 'state' => 'SA', 'subdivision' => 'Salto'),
				array('adif' => 144, 'state' => 'AR', 'subdivision' => 'Artigsa'),
				array('adif' => 144, 'state' => 'FD', 'subdivision' => 'Florida'),
				array('adif' => 144, 'state' => 'FS', 'subdivision' => 'Flores'),
				array('adif' => 144, 'state' => 'DU', 'subdivision' => 'Durazno'),
				array('adif' => 144, 'state' => 'TA', 'subdivision' => 'Tacuarembo'),
				array('adif' => 144, 'state' => 'RV', 'subdivision' => 'Rivera'),
				array('adif' => 144, 'state' => 'MA', 'subdivision' => 'Maldonado'),
				array('adif' => 144, 'state' => 'LA', 'subdivision' => 'Lavalleja'),
				array('adif' => 144, 'state' => 'RO', 'subdivision' => 'Rocha'),
				array('adif' => 144, 'state' => 'TT', 'subdivision' => 'Treinta y Tres'),
				array('adif' => 144, 'state' => 'CL', 'subdivision' => 'Cerro Largo'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 147, 'state' => 'LH', 'subdivision' => 'Lord Howe Is'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 148, 'state' => 'AM', 'subdivision' => 'Amazonas'),
				array('adif' => 148, 'state' => 'AN', 'subdivision' => 'Anzoátegui'),
				array('adif' => 148, 'state' => 'AP', 'subdivision' => 'Apure'),
				array('adif' => 148, 'state' => 'AR', 'subdivision' => 'Aragua'),
				array('adif' => 148, 'state' => 'BA', 'subdivision' => 'Barinas'),
				array('adif' => 148, 'state' => 'BO', 'subdivision' => 'Bolívar'),
				array('adif' => 148, 'state' => 'CA', 'subdivision' => 'Carabobo'),
				array('adif' => 148, 'state' => 'CO', 'subdivision' => 'Cojedes'),
				array('adif' => 148, 'state' => 'DA', 'subdivision' => 'Delta Amacuro'),
				array('adif' => 148, 'state' => 'DC', 'subdivision' => 'Distrito Capital'),
				array('adif' => 148, 'state' => 'FA', 'subdivision' => 'Falcón'),
				array('adif' => 148, 'state' => 'GU', 'subdivision' => 'Guárico'),
				array('adif' => 148, 'state' => 'LA', 'subdivision' => 'Lara'),
				array('adif' => 148, 'state' => 'ME', 'subdivision' => 'Mérida'),
				array('adif' => 148, 'state' => 'MI', 'subdivision' => 'Miranda'),
				array('adif' => 148, 'state' => 'MO', 'subdivision' => 'Monagas'),
				array('adif' => 148, 'state' => 'NE', 'subdivision' => 'Nueva Esparta'),
				array('adif' => 148, 'state' => 'PO', 'subdivision' => 'Portuguesa'),
				array('adif' => 148, 'state' => 'SU', 'subdivision' => 'Sucre'),
				array('adif' => 148, 'state' => 'TA', 'subdivision' => 'Táchira'),
				array('adif' => 148, 'state' => 'TR', 'subdivision' => 'Trujillo'),
				array('adif' => 148, 'state' => 'VA', 'subdivision' => 'Vargas'),
				array('adif' => 148, 'state' => 'YA', 'subdivision' => 'Yaracuy'),
				array('adif' => 148, 'state' => 'ZU', 'subdivision' => 'Zulia'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 149, 'state' => 'AC', 'subdivision' => 'Açores'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);

			$data = array(
				array('adif' => 150, 'state' => 'ACT', 'subdivision' => 'Australian Capital Territory'),
				array('adif' => 150, 'state' => 'NSW', 'subdivision' => 'New South Wales'),
				array('adif' => 150, 'state' => 'VIC', 'subdivision' => 'Victoria'),
				array('adif' => 150, 'state' => 'QLD', 'subdivision' => 'Queensland'),
				array('adif' => 150, 'state' => 'SA', 'subdivision' => 'South Australia'),
				array('adif' => 150, 'state' => 'WA', 'subdivision' => 'Western Australia'),
				array('adif' => 150, 'state' => 'TAS', 'subdivision' => 'Tasmania'),
				array('adif' => 150, 'state' => 'NT', 'subdivision' => 'Northern Territory'),
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
