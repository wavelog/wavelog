<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
    Migration to apply changes of the ADIF Standard 3.1.4 to 3.1.5
*/

class Migration_adif_315 extends CI_Migration {

	public function up()
	{
		/**
		 * ADIF Version 3.1.5
		 * The new ADIF version 3.1.5 comes with some changes.
		 */

		$this->db->trans_start();

		// ############################################################################################################

		/**
		 * 1. QSO Fields
		 */

		// Add new columns to the QSO table 
		$table_name = $this->config->item('table_name');
		$qso_fields = [];

		// Add CNTY_ALT
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_CNTY_ALT
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_CNTY_ALT';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_CNTY_ALT VARCHAR(255) DEFAULT NULL AFTER COL_CNTY;";
		} else {
			log_message('info', 'Column "COL_CNTY_ALT" already exists, skipping ALTER TABLE.');
		}

		// Add MY_CNTY_ALT
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_MY_CNTY_ALT
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_MY_CNTY_ALT';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_MY_CNTY_ALT VARCHAR(255) DEFAULT NULL AFTER COL_MY_CNTY;";
		} else {
			log_message('info', 'Column "COL_MY_CNTY_ALT" already exists, skipping ALTER TABLE.');
		}

		// Add MY_DARC_DOK
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_MY_DARC_DOK
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_MY_DARC_DOK';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_MY_DARC_DOK VARCHAR(10) DEFAULT NULL AFTER COL_MY_POTA_REF;";
		} else {
			log_message('info', 'Column "COL_MY_DARC_DOK" already exists, skipping ALTER TABLE.');
		}

		// Add DCL_QSLRDATE
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_DCL_QSLRDATE
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_DCL_QSLRDATE';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_DCL_QSLRDATE DATETIME DEFAULT NULL AFTER COL_CLUBLOG_QSO_DOWNLOAD_STATUS;";
		} else {
			log_message('info', 'Column "COL_DCL_QSLRDATE" already exists, skipping ALTER TABLE.');
		}

		// Add DCL_QSL_RCVD
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_DCL_QSL_RCVD
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_DCL_QSL_RCVD';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_DCL_QSL_RCVD VARCHAR(10) DEFAULT NULL AFTER COL_DCL_QSLRDATE;";
		} else {
			log_message('info', 'Column "COL_DCL_QSL_RCVD" already exists, skipping ALTER TABLE.');
		}

		// Add DCL_QSLSDATE
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_DCL_QSLSDATE
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_DCL_QSLSDATE';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_DCL_QSLSDATE DATETIME DEFAULT NULL AFTER COL_DCL_QSL_RCVD;";
		} else {
			log_message('info', 'Column "COL_DCL_QSLSDATE" already exists, skipping ALTER TABLE.');
		}

		// Add DCL_QSL_SENT
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_DCL_QSL_SENT
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_DCL_QSL_SENT';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_DCL_QSL_SENT VARCHAR(10) DEFAULT NULL AFTER COL_DCL_QSLSDATE;";
		} else {
			log_message('info', 'Column "COL_DCL_QSL_SENT" already exists, skipping ALTER TABLE.');
		}

		// Add MORSE_KEY_INFO
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_MORSE_KEY_INFO
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_MORSE_KEY_INFO';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_MORSE_KEY_INFO VARCHAR(255) DEFAULT NULL AFTER COL_REGION;";
		} else {
			log_message('info', 'Column "COL_MORSE_KEY_INFO" already exists, skipping ALTER TABLE.');
		}

		// Add MORSE_KEY_TYPE
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_MORSE_KEY_TYPE
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_MORSE_KEY_TYPE';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_MORSE_KEY_TYPE VARCHAR(10) DEFAULT NULL AFTER COL_MORSE_KEY_INFO;";
		} else {
			log_message('info', 'Column "COL_MORSE_KEY_TYPE" already exists, skipping ALTER TABLE.');
		}

		// Add QSLMSG_RCVD
		// https://adif.org/315/ADIF_315_annotated.htm#QSO_Field_QSLMSG_RCVD
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table_name` LIKE 'COL_QSLMSG_RCVD';")->num_rows() > 0;
		if (!$col_check) {
			$qso_fields[] = "ALTER TABLE `$table_name` ADD COLUMN COL_QSLMSG_RCVD VARCHAR(255) DEFAULT NULL AFTER COL_QSLMSG;";
		} else {
			log_message('info', 'Column "COL_QSLMSG_RCVD" already exists, skipping ALTER TABLE.');
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

		// ############################################################################################################

		/**
		 * 2. Primary Subdivisions
		 * 
		 * https://adif.org/315/ADIF_315_annotated.htm#Primary_Administrative_Subdivision
		 */

		// Add a column to flag entries as deprecated
		$col_check = $this->db->query("SHOW COLUMNS FROM `primary_subdivisions` LIKE 'deprecated';")->num_rows() > 0;
		if (!$col_check) {
			$this->db->query("ALTER TABLE `primary_subdivisions` ADD COLUMN deprecated TINYINT(1) DEFAULT 0");
		} else {
			log_message('info', 'Column "deprecated" already exists, skipping ALTER TABLE.');
		}

		// Clean Up primary_subdivisions from dupes, since there's no PK!!
		$this->db->query("CREATE TABLE tmp_psd AS SELECT DISTINCT ps.`adif`,ps.`state`,ps.`subdivision`,ps.`deprecated` FROM `primary_subdivisions` ps");
		$this->db->query("DELETE FROM `primary_subdivisions`");
		$this->db->query("INSERT INTO `primary_subdivisions` SELECT * FROM tmp_psd");
		$this->db->query("DROP TABLE tmp_psd");
		$this->db->query("DELETE FROM `primary_subdivisions` WHERE `adif`=15 and `state`='PM' and `subdivision`='Permskaya Kraj'");
		$this->db->query("DELETE FROM `primary_subdivisions` WHERE `adif`=206 and `state`='BM' and `subdivision`='Bruck/Mur'");

		// Add Unique IDX
		$this->add_unique_ix('`primary_subdivisions`','IDX_UNIQ_SUBDIVISON#adif#state#deprecated','`adif`,`state`,`deprecated`');

		$prim_subdiv = [];

		// Mark 'Märket' as deprecated
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '5' AND state = '051';";

		// Add Andaman & Nicobar Is.
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('11', 'AN', 'Andaman and Nicobar Islands (Union territory)', '0');";

		// Update Balearic Is.
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Balears' WHERE subdivision = 'Baleares';";

		// Add Canary Is.
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('29', 'GC', 'Las Palmas', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('29', 'TF', 'Santa Cruz de Tenerife', '0');";

		// Alter Mexico
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'DF';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'CMX', 'Ciudad de México', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'EMX';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'MEX', 'México', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'GTO';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'GUA', 'Guanajuato', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'HGO';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'HID', 'Hidalgo', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'QRO';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'QUE', 'Querétaro', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'TLX';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'TLA', 'Tlaxcala', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Veracruz de Ignacio de la Llave' WHERE adif = '50' AND state = 'VER';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'AGS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'BC';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'BCN', 'Baja California', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'DGO';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'DUR', 'Durango', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'NL';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'TMS';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'TAM', 'Tamaulipas', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'CHS';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'CHP', 'Chiapas', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '50' AND state = 'QTR';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('50', 'ROO', 'Quintana Roo', '0');";

		// Alter Franz Josef Land
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '61' AND state = 'FJL';";

		// Add Cuba
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '15', 'Artemisa (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '09', 'Camagüey (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '08', 'Ciego de Ávila (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '06', 'Cienfuegos (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '12', 'Granma (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '14', 'Guantánamo (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '11', 'Holguín (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '99', 'Isla de la Juventud (special municipality)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '03', 'La Habana (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '10', 'Las Tunas (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '04', 'Matanzas (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '16', 'Mayabeque (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '01', 'Pinar del Río (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '07', 'Sancti Spíritus (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '13', 'Santiago de Cuba (province)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('70', '05', 'Villa Clara (province)', '0');";

		// Update Argentina
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Córdoba' WHERE adif = '100' AND state = 'X';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Entre Ríos' WHERE adif = '100' AND state = 'E';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Río Negro' WHERE adif = '100' AND state = 'R';";

		// Update Brazil
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Amapá' WHERE adif = '108' AND state = 'AP';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Maranhão' WHERE adif = '108' AND state = 'MA';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Piauí' WHERE adif = '108' AND state = 'PI';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Distrito Federal' WHERE adif = '108' AND state = 'DF';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'São Paulo' WHERE adif = '108' AND state = 'SP';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Paraná' WHERE adif = '108' AND state = 'PR';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Pará' WHERE adif = '108' AND state = 'PA';";

		// Update Chile
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'I';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'II';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'III';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'XV';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'IV';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'V';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'VI';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'VII';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'VIII';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'IX';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'XIV';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'X';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'XI';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '112' AND state = 'XII';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'AN', 'Antofagasta', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'AT', 'Atacama', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'TA', 'Tarapacá', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'AP', 'Arica y Parinacota', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'CO', 'Coquimbo', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'VS', 'Valparaíso', '0');";
		// $prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'RM', 'Región Metropolitana de Santiago', '0');";  // already exists
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'LI', 'Libertador General Bernardo O\\'Higgins', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'ML', 'Maule', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'BI', 'Biobío', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'AR', 'La Araucanía', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'LR', 'Los Ríos', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'LL', 'Los Lagos', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'AI', 'Aisén del General Carlos Ibáñez del Campo', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'MA', 'Magallanes', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('112', 'NB', 'Ñuble', '0');";

		// Add Jan Mayen
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('118', '22', 'Jan Mayen', '0');";

		// Update Paraquay
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '1', 'Concepción', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '2', 'San Pedro', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '3', 'Cordillera', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '9', 'Paraguarí', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '6', 'Caazapá', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '5', 'Caeguazú', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '4', 'Guairá', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '8', 'Misiones', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('132', '7', 'Itapúa', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '01';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '02';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '03';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '09';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '06';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '05';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '04';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '08';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '132' AND state = '07';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Ñeembucú' WHERE adif = '132' AND state = '12';";

		// Add Lakshadweep Is.
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('142', 'LD', 'Lakshadweep (Union territory)', '0');";

		// Update Uruguay
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Río Negro' WHERE adif = '144' AND state = 'RN';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Paysandú' WHERE adif = '144' AND state = 'PA';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Artigas' WHERE adif = '144' AND state = 'AR';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Tacuarembó' WHERE adif = '144' AND state = 'TA';";

		// Update Malyj Vysotskij
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '151' AND state = 'MV';";

		// Update Papua New Guinea
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '163' AND state = 'NSA';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('163', 'NSB', 'Bougainville', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'West Sepik' WHERE adif = '163' AND state = 'SAN';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '163' AND state = 'WBR';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('163', 'WBK', 'West New Britain', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('163', 'HLA', 'Hela', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('163', 'JWK', 'Jiwaka', '0');";

		// Update New Zealand
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Hawke\\'s Bay' WHERE adif = '170' AND state = 'HBK';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Manawatū-Whanganui' WHERE adif = '170' AND state = 'MWT';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Greater Wellington' WHERE adif = '170' AND state = 'WGN';";

		// Update Sardinia (we have a bug here anyway)
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'CA' AND subdivision = 'Cagliari';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225', deprecated = 1 WHERE state = 'CI' AND subdivision = 'Carbonia-Iglesias';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('225', 'SU', 'Sud Sardegna', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225', deprecated = 1 WHERE state = 'MD' AND subdivision = 'Medio Campidano';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'NU' AND subdivision = 'Nuoro';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'OG' AND subdivision = 'Ogliastra';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'OR' AND subdivision = 'Oristano';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'OT' AND subdivision = 'Olbia-Tempio';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'SS' AND subdivision = 'Sassari';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET adif = '225' WHERE state = 'VS' AND subdivision = 'MedioCampidano';";

		// Update France
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bouches-du-Rhône' WHERE adif = '227' AND state = '13';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Côte-d\\'Or' WHERE adif = '227' AND state = '21';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Côtes-d\\'Armor' WHERE adif = '227' AND state = '22';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Gers' WHERE adif = '227' AND state = '32';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Nièvre' WHERE adif = '227' AND state = '58';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Hautes-Pyrénées' WHERE adif = '227' AND state = '65';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Hauts-de-Seine' WHERE adif = '227' AND state = '92';";

		// Update Germany
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bayern' WHERE adif = '230' AND state = 'BY';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bremen' WHERE adif = '230' AND state = 'HB';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Hamburg' WHERE adif = '230' AND state = 'HH';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Sachsen' WHERE adif = '230' AND state = 'SN';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Thüringen' WHERE adif = '230' AND state = 'TH';";

		// Update Ireland
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '245' AND state = 'C';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('245', 'CO', 'Cork (Corcaigh)', '0');";

		// Update Italy
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '248' AND state = 'FO';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '248' AND state = 'PS';";

		// Add Svalbard
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('259', '21', 'Svalbard', '0');";

		// Add Norway
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '42', 'Agder', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '34', 'Innlandet', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '15', 'Møre og Romsdal', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '18', 'Nordland', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '03', 'Oslo', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '11', 'Rogaland', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '54', 'Troms og Finnmark', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '50', 'Trøndelag', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '38', 'Vestfold og Telemark', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '46', 'Vestland', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('266', '30', 'Viken', '0');";

		// Update Netherlands
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Fryslân' WHERE adif = '263' AND state = 'FR';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '263' AND state = 'GD';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('263', 'GE', 'Gelderland', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '263' AND state = 'LB';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('263', 'LI', 'Limburg', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '263' AND state = 'ZL';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('263', 'ZE', 'Zeeland', '0');";

		// Update Romania
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Caraș-Severin' WHERE adif = '275' AND state = 'CS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Timiș' WHERE adif = '275' AND state = 'TM';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '275' AND state = 'BU';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('275', 'B', 'București', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Brăila' WHERE adif = '275' AND state = 'BR';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Constanța' WHERE adif = '275' AND state = 'CT';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Galați' WHERE adif = '275' AND state = 'GL';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bistrița-Năsăud' WHERE adif = '275' AND state = 'BN';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Maramureș' WHERE adif = '275' AND state = 'MM';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Sălaj' WHERE adif = '275' AND state = 'SJ';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Brașov' WHERE adif = '275' AND state = 'BV';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Mureș' WHERE adif = '275' AND state = 'MS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Argeș' WHERE adif = '275' AND state = 'AG';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Mehedinți' WHERE adif = '275' AND state = 'MH';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bacău' WHERE adif = '275' AND state = 'BC';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Botoșani' WHERE adif = '275' AND state = 'BT';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Iași' WHERE adif = '275' AND state = 'IS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Neamț' WHERE adif = '275' AND state = 'NT';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Buzău' WHERE adif = '275' AND state = 'BZ';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Călărași' WHERE adif = '275' AND state = 'CL';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Dâmbovița' WHERE adif = '275' AND state = 'DB';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Giurgiu' WHERE adif = '275' AND state = 'GR';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Ialomița' WHERE adif = '275' AND state = 'IL';";

		// Update Spain
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Ávila' WHERE adif = '281' AND state = 'AV';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'León' WHERE adif = '281' AND state = 'LE';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET deprecated = 1 WHERE adif = '281' AND state = 'OU';";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('281', 'OR', 'Ourense', '0');";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bizkaia' WHERE adif = '281' AND state = 'BI';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Gipuzkoa' WHERE adif = '281' AND state = 'SS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Álava' WHERE adif = '281' AND state = 'VI';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Cáceres' WHERE adif = '281' AND state = 'CC';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Castellón' WHERE adif = '281' AND state = 'CS';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'València' WHERE adif = '281' AND state = 'V';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Cádiz' WHERE adif = '281' AND state = 'CA';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Córdoba' WHERE adif = '281' AND state = 'CO';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Jaén' WHERE adif = '281' AND state = 'J';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Málaga' WHERE adif = '281' AND state = 'MA';";

		// Update Sweden
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Stockholms län' WHERE adif = '284' AND state = 'AB';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Dalarnas län' WHERE adif = '284' AND state = 'W';";

		// Add India
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'AP', 'Andhra Pradesh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'AR', 'Arunāchal Pradesh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'AS', 'Assam (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'BR', 'Bihār (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'CH', 'Chandīgarh (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'CG', 'Chhattīsgarh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'DD', 'Damān and Diu (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'DL', 'Delhi (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'DN', 'Dādra and Nagar Haveli (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'GA', 'Goa (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'GJ', 'Gujarāt (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'HR', 'Haryāna (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'HP', 'Himāchal Pradesh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'JK', 'Jammu and Kashmīr (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'JH', 'Jhārkhand (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'KA', 'Karnātaka (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'KL', 'Kerala (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'LA', 'Ladākh (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'MP', 'Madhya Pradesh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'MH', 'Mahārāshtra (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'MN', 'Manipur (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'ML', 'Meghālaya (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'MZ', 'Mizoram (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'NL', 'Nāgāland (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'OD', 'Odisha (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'PY', 'Puducherry (Union territory)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'PB', 'Punjab (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'RJ', 'Rājasthān (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'SK', 'Sikkim (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'TN', 'Tamil Nādu (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'TG', 'Telangāna (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'TR', 'Tripura (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'UP', 'Uttar Pradesh (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'UK', 'Uttarākhand (state)', '0');";
		$prim_subdiv[] = "REPLACE INTO `primary_subdivisions` (adif, state, subdivision, deprecated) VALUES ('324', 'WB', 'West Bengal (state)', '0');";

		// Notes:
		//     ISO 3166-2: India merges codes DD and DN into DH but the above list reflects the ARSI website's lists of codes for their VU-DX Contest.
		//     ISO 3166-2: India has Telangāna as code TS but the above list reflects the ARSI website's code TG for their VU-DX Contest.

		// Update Philippines
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Kalinga' WHERE adif = '375' AND state = 'KAL';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Bataan' WHERE adif = '375' AND state = 'BAN';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Negros Occidental' WHERE adif = '375' AND state = 'NEC';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Cotabato' WHERE adif = '375' AND state = 'NCO';";
		$prim_subdiv[] = "UPDATE `primary_subdivisions` SET subdivision = 'Davao de Oro' WHERE adif = '375' AND state = 'COM';";

		// Run the querys
		try {
			foreach ($prim_subdiv as $query) {
				$this->db->query($query);
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Migration failed: ' . $e->getMessage());
			log_message('error', 'The query was: ' . $query);
			return false;
		}

		// ############################################################################################################

		/**
		 * 3. Submode Enumeration
		 * 
		 * https://adif.org/315/ADIF_315_annotated.htm#Submode_Enumeration
		 */

		$submodes = [];

		// CleanUp ADIF-Table from possible dupes

		$this->db->query("CREATE TABLE tmp_adif_modes AS SELECT DISTINCT ad.`mode`,ad.`submode`,ad.`qrgmode`,ad.`active` FROM adif_modes ad");
		$this->db->query("DELETE FROM `adif_modes`");
		$this->db->query("INSERT INTO `adif_modes` (`mode`,`submode`,`qrgmode`,`active`) SELECT * FROM tmp_adif_modes");
		$this->db->query("DROP TABLE tmp_adif_modes");

		// Add Unique IDX

		$this->add_unique_ix('`adif_modes`','IDX_UNIQ_ADIF_MODES#mode#submode#qrgmode#active','`mode`,`submode`,`qrgmode`,`active`');


		$submodes[] = "REPLACE INTO `adif_modes` (mode, submode, qrgmode, active) VALUES ('HELL', 'FSKH105', 'DATA', 1);";
		$submodes[] = "REPLACE INTO `adif_modes` (mode, submode, qrgmode, active) VALUES ('HELL', 'FSKH245', 'DATA', 1);";

		// Run the querys
		try {
			foreach ($submodes as $query) {
				$this->db->query($query);
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Migration failed: ' . $e->getMessage());
			log_message('error', 'The query was: ' . $query);
			return false;
		}


		// ############################################################################################################

		/**
		 * 4. ADIF Contests
		 * 
		 * https://adif.org/315/ADIF_315_annotated.htm#Contest_ID
		 */

		$new_c = [
			['name' => 'ARI Italian EME Trophy', 'adifname' => 'ARI-EME'],
			['name' => 'ARI Italian Activity Contest (13cm+)', 'adifname' => 'ARI-IAC-13CM'],
			['name' => 'ARI Italian Activity Contest (23cm)', 'adifname' => 'ARI-IAC-23CM'],
			['name' => 'ARI Italian Activity Contest (6m)', 'adifname' => 'ARI-IAC-6M'],
			['name' => 'ARI Italian Activity Contest (UHF)', 'adifname' => 'ARI-IAC-UHF'],
			['name' => 'ARI Italian Activity Contest (VHF)', 'adifname' => 'ARI-IAC-VHF'],
			['name' => 'DARC FT4 Contest', 'adifname' => 'DARC-FT4'],
			['name' => 'K1USN Slow Speed Open', 'adifname' => 'K1USN-SSO'],
			['name' => 'PCCPro CW Contest', 'adifname' => 'PCC'],
		];

		try {
			foreach ($new_c as $c) {
				$query = $this->db->query("SELECT 1 FROM `contest` WHERE adifname = ?", [$c['adifname']]);
				if ($query->num_rows() == 0) {
					$this->db->query("INSERT INTO `contest` (name, adifname, active) VALUES (?, ?, 1);", [$c['name'], $c['adifname']]);
				}
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Migration failed: ' . $e->getMessage());
			log_message('error', 'The query was: ' . $query);
			return false;
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			log_message('error', 'Migration failed for unknown reasons.');
			return false;
		} else {
			log_message('debug', 'Migration to ADIF Version 3.1.5 completed successfully.');
			return true;
		}

	}

	private function add_unique_ix($table,$index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$table." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "ALTER TABLE ".$table." ADD UNIQUE INDEX `".$index."` (".$cols.");";
			$this->db->query($sql);
		}
	}


	public function down()
	{
		// No way back here
	}
}
