<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
	Lookup functions for subdivisions
*/


class Subdivisions {

	public function get_primary_subdivision_name($dxcc) {
		// ref. http://adif.org.uk/314/ADIF_314_annotated.htm#Primary_Administrative_Subdivision
		switch($dxcc) {
			case '1':
			case '29':
			case '32':
			case '100':
			case '137':
			case '163':
			case '206':
			case '209':
			case '212':
			case '225':
			case '248':
			case '263':
			case '269':
			case '281':
			case '284':
			case '318':
			case '375':
			case '386':
				return _pgettext("Division Name (States in various countries).", "Province");
			case '27':
			case '15':
			case '54':
			case '61':
			case '126':
			case '151':
			case '288':
				return _pgettext("Division Name (States in various countries).", "Oblast");
			case '112':
				return _pgettext("Division Name (States in various countries).", "Region");
			case '132':
			case '144':
			case '227':
				return _pgettext("Division Name (States in various countries).", "Department");
			case '170':
				return _pgettext("Division Name (States in various countries).", "Region");
			case '224':
				return _pgettext("Division Name (States in various countries).", "Municipality");
			case '230':
				return _pgettext("Division Name (States in various countries).", "Federal State");
			case '239':
			case '245':
			case '275':
			case '497':
				return _pgettext("Division Name (States in various countries).", "County");
			case '272':
			case '503':
			case '504':
				return _pgettext("Division Name (States in various countries).", "District");
			case '287':
				return _pgettext("Division Name (States in various countries).", "Canton");
			case '291':
				return _pgettext("Division Name (States in various countries).", "US State");
			case '318':
			case '339':
				return _pgettext("Division Name (States in various countries).", "Prefecture");
		}
		return _pgettext("Division Name (States in various countries).", "State");
	}
	
	public function get_secondary_subdivision_name($dxcc) {
		// ref. http://adif.org.uk/314/ADIF_314_annotated.htm#Secondary_Administrative_Subdivision
		switch($dxcc) {
			case '6':
			case '110':
			case '291':
				return _pgettext("Division Name (States in various countries).", "US County");
			case '15':
			case '54':
			case '61':
			case '126':
			case '151':
			case '288':
				return _pgettext("Division Name (States in various countries).", "District");
			case '21':
			case '29':
			case '32':
			case '281':
				return _pgettext("Division Name (States in various countries).", "DME");
			case '339':
				return _pgettext("Division Name (States in various countries).", "City / Ku / Gun");
		}
		return _pgettext("Division Name (States in various countries).", "County");
	}

	public function get_state_list($dxcc) {
		$CI =& get_instance();
		$CI->load->model('logbook_model');

		$states = $CI->logbook_model->get_states_by_dxcc($dxcc);

		return $states;
	}
}
