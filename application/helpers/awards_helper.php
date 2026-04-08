<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('awards_build_qsl_string')) {
	/**
	 * Builds a QSL string based on the provided postdata.
	 * Each character in the string represents a different QSL method.
	 * 
	 * Check JavaScript function displayContacts() in views/interface_assets/footer.php
	 * 	and PHP function qso_details_ajax in controllers/Awards.php
	 * 
	 * @param array $postdata The data from which to build the QSL string.
	 * @return string The constructed QSL string.
	 */
	function awards_build_qsl_string($postdata) {
		$qsl = '';
		if (($postdata['qsl'] ?? null) == 1) {
			$qsl .= 'Q';
		}
		if (($postdata['lotw'] ?? null) == 1) {
			$qsl .= 'L';
		}
		if (($postdata['eqsl'] ?? null) == 1) {
			$qsl .= 'E';
		}
		if (($postdata['dcl'] ?? null) == 1) {
			$qsl .= 'D';
		}
		if (($postdata['clublog'] ?? null) == 1) {
			$qsl .= 'C';
		}
		if (($postdata['qrz'] ?? null) == 1) {
			$qsl .= 'Z';
		}

		return $qsl;
	}
}

if (!function_exists('awards_build_display_contacts_href')) {
	/**
	 * Builds a JavaScript href for displaying contacts based on the provided parameters.
	 * 
	 * @param string $searchphrase The search phrase for filtering contacts.
	 * @param string $band The band to filter contacts.
	 * @param string $mode The mode to filter contacts.
	 * @param string $type The type of award.
	 * @param string $qsl The QSL string.
	 * @param string $datefrom The start date for filtering contacts.
	 * @param string $dateto The end date for filtering contacts.
	 * @param string $sat The satellite filter.
	 * @param string $orbit The orbit filter.
	 * @return string The constructed JavaScript href.
	 */
	function awards_build_display_contacts_href($searchphrase, $band, $mode, $type, $qsl = '', $datefrom = '', $dateto = '', $sat = 'All', $orbit = 'All') {
		$args = array(
			(string) $searchphrase,
			(string) $band,
			(string) $sat,
			(string) $orbit,
			(string) $mode,
			(string) $type,
			(string) $qsl,
			(string) $datefrom,
			(string) $dateto,
		);

		return 'javascript:displayContacts(' . implode(',', array_map('json_encode', $args)) . ')';
	}
}

if (!function_exists('awards_render_jcc_cell')) {
	/**
	 * Renders a cell for the JCC award table based on the entity, band, status, and postdata.
	 * 
	 * @param string $entity The entity of the slot.
	 * @param string $band The band of the slot.
	 * @param string $status The status of the slot.
	 * @param array $postdata The postdata containing filter options.
	 * @return string The HTML string for the cell.
	 */
	function awards_render_jcc_cell($entity, $band, $status, $postdata) {
		if ($status !== 'W' && $status !== 'C') {
			return '-';
		}

		$qsl_string = $status === 'C' ? awards_build_qsl_string($postdata) : '';
		$href = awards_build_display_contacts_href(
			$entity,
			$band,
			$postdata['mode'] ?? 'All',
			'JCC',
			$qsl_string,
		);
		$class_name = $status === 'C' ? 'bg-success awardsBgSuccess' : 'bg-danger awardsBgWarning';

		return '<div class="' . $class_name . '"><a href="' . html_escape($href) . '">' . $status . '</a></div>';
	}
}
