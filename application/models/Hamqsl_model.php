<?php

class Hamqsl_model extends CI_Model {
	public $solarData = null;

	function set_solardata() {
		// Reads solar data from local XML file and sets $this->solarData
		// Returns true if data was read, false if not
		// The XML file shall be updated every 60 minutes by a cron job
		$xmlData = null;

		if (file_exists("./updates/solarxml.xml")) {

			$xmlstr = file_get_contents("./updates/solarxml.xml");

			if ($xmlstr !== false) {
				try {
					$xmlData = new SimpleXMLElement($xmlstr);
				} catch (Exception $e) {
					// Do nothing
				}
			}
		}

		if($xmlData) {
			$this->solarData = $xmlData;
			return true;
		} else {
			$this->solarData = null;
			return false;
		}
	}

	function get_bandconditions_array() {
		// Returns an associative array of all band conditions from the XML data
		// The array structure is: [band_name][time_of_day] = condition
		// Example: $conditions['80m-40m']['day'] = 'Good'
		// Returns null if data is not available

		if (!$this->solarData) {
			if (!$this->set_solardata()) {
				return null; // Unable to load data
			}
		}

		$conditions = [];
		if (isset($this->solarData->solardata->calculatedconditions->band)) {
			foreach ($this->solarData->solardata->calculatedconditions->band as $band) {
				$name = (string)$band['name'];
				$time = (string)$band['time'];
				$condition = trim((string)$band);
				if (!isset($conditions[$name])) {
					$conditions[$name] = [];
				}
				$conditions[$name][$time] = $condition;
			}
		}
		return $conditions; // Return the associative array
	}

	function get_solarinformation_array() {
		// Returns an associative array of all information from the XML data,
		// including band conditions, without filtering anything out.
		// The 'updated' field is converted to "d M H:i \G\M\T" format.
		// Returns null if data is not available.

		if (!$this->solarData) {
			if (!$this->set_solardata()) {
				return null; // Unable to load data
			}
		}

		// Find the <solardata> node (handle both root and nested)
		$solardata = isset($this->solarData->solardata) ? $this->solarData->solardata : $this->solarData;

		// Convert the entire <solardata> node to an associative array
		$solarinformation = json_decode(json_encode($solardata), true);

		// Format the 'updated' field if it exists
		if (isset($solarinformation['updated'])) {
			$timestamp = strtotime($solarinformation['updated']);
			if ($timestamp !== false) {
				$solarinformation['updated'] = gmdate('d M H:i \G\M\T', $timestamp);
			}
		}

		return $solarinformation; // Return the associative array
	}
}

?>
