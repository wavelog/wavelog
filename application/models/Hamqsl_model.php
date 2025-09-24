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

	function get_bandconditions(string $name, string $time) {
		// Returns the band condition for a given band name and time of day from the provided XML root
		// If the data is not available, returns null
		// Example: get_band_condition('80m-40m', 'day')
		// Returns: 'Good', 'Fair', 'Poor', or null if not found

		if (!$this->solarData) {
			if (!$this->set_solardata()) {
				return null; // Unable to load data
			}
		}

		// Properly escape values for XPath
		$escapeForXPath = function($value) {
			if (strpos($value, "'") === false) {
				return "'$value'";
			} elseif (strpos($value, '"') === false) {
				return "\"$value\"";
			} else {
				return "concat('" . str_replace("'", "',\"'\",'", $value) . "')";
			}
		};
		$nameEscaped = $escapeForXPath($name);
		$timeEscaped = $escapeForXPath($time);
		$xpathQuery = "/solardata/calculatedconditions/band[@name=$nameEscaped and @time=$timeEscaped]";
		$result = $this->solarData->xpath($xpathQuery);

		if ($result && count($result) > 0) {
			return trim((string)$result[0]);
		} else {
			return null; // Not found
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
