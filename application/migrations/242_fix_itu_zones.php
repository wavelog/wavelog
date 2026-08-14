<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_fix_itu_zones extends CI_Migration {

	public function up() {
		$this->dbtry("UPDATE dxcc_master set ituzone = 22 where countrycode = 15 and latitude > 60 and latitude < 80 and longitude > 90 and longitude < 110; -- UA9");
		$this->dbtry("UPDATE dxcc_master set ituzone = 20 where countrycode = 15 and latitude > 60 and latitude < 80 and longitude > 50 and longitude < 75; -- UA9");
		$this->dbtry("UPDATE dxcc_master set ituzone = 20 where countrycode = 54 and latitude > 60 and latitude < 80 and longitude > 50 and ituzone <> ''; -- UA");
		$this->dbtry("UPDATE dxcc_master set ituzone = '33' where countrycode = 318 and latitude > 44; -- BY");
		$this->dbtry("UPDATE dxcc_master set ituzone = '29' where countrycode = 54 and location = 'St. Petersburg <Pri:SP>'; -- UA");
		$this->dbtry("UPDATE dxcc_master set ituzone = '29' where countrycode = 130 and longitude = 49; -- un");
		$this->dbtry("UPDATE dxcc_master set ituzone = '61' where countrycode = 48; -- t32");
		$this->dbtry("UPDATE dxcc_master set ituzone = '48' where countrycode = 521; -- st0");
		$this->dbtry("UPDATE dxcc_master set ituzone = '48' where countrycode = 466; -- st");
		$this->dbtry("UPDATE dxcc_master set ituzone = '4' where countrycode = 1 and location = 'Ontario <Pri:ON>';");
		$this->dbtry("UPDATE dxcc_master set ituzone = '75', cqzone = '1' where countrycode = 1 and location = 'Nunavut <Pri:NU>';");
		$this->dbtry("UPDATE dxcc_master set ituzone = '75' where countrycode = 1 and location = 'Northwest Territory <Pri:NT>';");
		$this->dbtry("DELETE from dxcc_master where countrycode = 6;");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('KL7', 'K', 'KL7', 1, 6, 'KL#,NL#,WL#,AL#', 'Alaska', 'Alaska', 'NA', '1', '1', NULL, 9.0, 65.0, -151.0, NULL, NULL);");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('KL7', 'K', 'KL7', 1, 6, 'KL#,NL#,WL#,AL#', 'Alaska', 'Alaska', 'NA', '1', '2', NULL, 9.0, 65.0, -151.0, NULL, NULL);");
		$this->dbtry("DELETE from dxcc_master where countrycode = 363;");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('JT', 'JT', 'JT', 1, 363, 'JT,JU,JV', 'Mongolia', 'Mongolia', 'AS', '23', '32', NULL, -8.0, 47.8, 107.0, NULL, NULL);");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('JT', 'JT', 'JT', 1, 363, 'JT,JU,JV', 'Mongolia', 'Mongolia', 'AS', '23', '33', NULL, -8.0, 47.8, 107.0, NULL, NULL);");
		$this->dbtry("DELETE from dxcc_master where countrycode = 237;");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('OX', 'OX', 'OX', 1, 237, 'OX,XP', 'Greenland', 'Greenland', 'NA', '40', '5', NULL, 3.0, 64.1917, -51.6778, NULL, NULL);");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('OX', 'OX', 'OX', 1, 237, 'OX,XP', 'Greenland', 'Greenland', 'NA', '40', '75', NULL, 3.0, 64.1917, -51.6778, NULL, NULL);");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('UA0', 'UA0', 'UA', 25, 15, 'R?0,U!0,R0,U0', 'Asiatic Russia', '', 'AS', '18', '21', NULL, -8.0, 61.0, 76.0, NULL, NULL);");
		$this->dbtry("INSERT INTO dxcc_master (DXCCPrefix, DXCCSearch, DXCCMap, DXCCSort, CountryCode, PrefixList, DXCCName, Location, Continent, CQZone, ITUZone, IOTA, TimeZone, Latitude, Longitude, StartDate, EndDate) VALUES('UA0', 'UA0', 'UA', 25, 15, 'R?8,U!8,R8,U8', 'Asiatic Russia', '', 'AS', '16', '19', NULL, -4.0, 70.0, 49.0, NULL, NULL);");
	}

	public function down() {

	}
	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering FKs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
