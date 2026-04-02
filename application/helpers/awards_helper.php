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

if (!function_exists('awards_render_jcc_grid_slot')) {
	/**
	 * Renders a slot for the grouped JCC demo grid.
	 *
	 * @param array $slot The slot metadata
	 * @param array $postdata The postdata containing filter options
	 * @return string The HTML string for the slot
	 */
	function awards_render_jcc_grid_slot($slot, $postdata) {
		$classes = array(
			'award-grid-slot',
			'btn',
			'border',
			'd-inline-flex',
			'align-items-center',
			'justify-content-center',
		);
		if (($slot['status'] ?? '-') === 'C') {
			$classes[] = 'btn-success';
		} elseif (($slot['status'] ?? '-') === 'W') {
			$classes[] = 'btn-danger';
		} else {
			$classes[] = 'btn-light';
		}
		if (!empty($slot['deleted'])) {
			$classes[] = 'award-grid-slot-deleted';
		}

		$tooltip_lines = array();
		$title_parts = array();

		if (!empty($slot['entity'])) {
			$tooltip_lines[] = '<strong>' . html_escape($slot['entity']) . '</strong>';
			$title_parts[] = $slot['entity'];
		}
		if (!empty($slot['name'])) {
			$tooltip_lines[] = html_escape($slot['name']);
			$title_parts[] = $slot['name'];
		}
		if (!empty($slot['deleted'])) {
			$tooltip_lines[] = html_escape(__("Deleted"));
			$title_parts[] = __("Deleted");
		}

		$tooltip_html = implode('<br>', $tooltip_lines);
		$title = trim(implode(' - ', $title_parts));

		$label = html_escape($slot['short_number'] ?? $slot['entity'] ?? '');
		$class_name = html_escape(implode(' ', $classes));
		$title_attr = html_escape($title);
		$tooltip_attr = html_escape($tooltip_html);
		$tooltip_data = ' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" data-bs-title="' . $tooltip_attr . '" title="' . $title_attr . '"';

		if (($slot['status'] ?? '-') === 'W' || ($slot['status'] ?? '-') === 'C') {
			$qsl_string = ($slot['status'] ?? '-') === 'C' ? awards_build_qsl_string($postdata) : '';
			$href = awards_build_display_contacts_href(
				$slot['entity'] ?? '',
				$postdata['band'] ?? 'All',
				$postdata['mode'] ?? 'All',
				'JCC',
				$qsl_string,
			);

			return '<a class="' . $class_name . '" href="' . html_escape($href) . '"' . $tooltip_data . ' aria-label="' . $title_attr . '">' . $label . '</a>';
		}

		return '<span class="' . $class_name . '"' . $tooltip_data . ' aria-label="' . $title_attr . '">' . $label . '</span>';
	}
}
