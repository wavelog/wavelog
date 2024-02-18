<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Libary for common functions, used at many places
 */
class Genfunctions
{
	function addQslToQuery($postdata) {
		$sql = '';
		$qsl = array();
		if ($postdata['qrz'] != NULL || $postdata['lotw'] != NULL || $postdata['qsl'] != NULL || $postdata['eqsl'] != NULL) {
			$sql .= ' and (';
			if ($postdata['qsl'] != NULL) {
				array_push($qsl, "col_qsl_rcvd = 'Y'");
			}
			if ($postdata['lotw'] != NULL) {
				array_push($qsl, "col_lotw_qsl_rcvd = 'Y'");
			}
			if ($postdata['eqsl'] != NULL) {
				array_push($qsl, "col_eqsl_qsl_rcvd = 'Y'");
			}
			if ($postdata['qrz'] != NULL) {
				array_push($qsl, "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'");
			}
			if (count($qsl) > 0) {
				$sql .= implode(' or ', $qsl);
			} else {
				$sql .= '1=0';
			}
			$sql .= ')';
		} else {
			$sql.=' and 1=0';
		}
		return $sql;
	}

	function addBandToQuery($band) {
		$sql = '';
		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode ='" . $band . "'";
			} else {
				$sql .= " and col_prop_mode !='SAT'";
				$sql .= " and col_band ='" . $band . "'";
			}
		}
		return $sql;
	}

	function gen_qsl_from_postdata($postdata) {
		$qsl='';
		if ($postdata['confirmed'] != NULL) {
			if ($postdata['qsl'] != NULL ) {
				$qsl .= "Q";
			}
			if ($postdata['lotw'] != NULL ) {
				$qsl .= "L";
			}
			if ($postdata['eqsl'] != NULL ) {
				$qsl .= "E";
			}
			if ($postdata['qrz'] != NULL ) {
				$qsl .= "Z";
			}
		}
		return $qsl;
	}

}
