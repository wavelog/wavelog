<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Library for common functions, used at many places
 */
class Genfunctions
{
	function addQslToQuery($postdata) {
		$sql = '';
		$qsl = array();
		if ( (($postdata['clublog'] ?? '') != '') || 
			(($postdata['qrz'] ?? '') != '') || 
			(($postdata['lotw'] ?? '') != '') || 
			(($postdata['qsl'] ?? '') != '') ||
			(($postdata['eqsl'] ?? '') != '') ) {
			$sql .= ' and (';
			if (($postdata['qsl'] ?? '') != '') {
				array_push($qsl, "col_qsl_rcvd = 'Y'");
			}
			if (($postdata['lotw'] ?? '') != '') {
				array_push($qsl, "col_lotw_qsl_rcvd = 'Y'");
			}
			if (($postdata['eqsl'] ?? '') != '') {
				array_push($qsl, "col_eqsl_qsl_rcvd = 'Y'");
			}
			if (($postdata['qrz'] ?? '') != '') {
				array_push($qsl, "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'");
			}
			if (($postdata['clublog'] ?? '') != '') {
				array_push($qsl, "COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y'");
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

	function addBandToQuery($band,&$binding) {
		$sql = '';
		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ?";
				$binding[]=$band;
			} else {
				$sql .= " and col_prop_mode !='SAT'";
				$sql .= " and col_band = ?";
				$binding[]=$band;
			}
		}
		return $sql;
	}

	function gen_qsl_from_postdata($postdata) {
		$qsl='';
		if ($postdata['confirmed'] != NULL) {
			if (($postdata['qsl'] ?? '')!= '' ) {
				$qsl .= "Q";
			}
			if (($postdata['lotw'] ?? '')!= '' ) {
				$qsl .= "L";
			}
			if (($postdata['eqsl'] ?? '')!= '' ) {
				$qsl .= "E";
			}
			if (($postdata['clublog'] ?? '')!= '' ) {
				$qsl .= "C";
			}
			if (($postdata['qrz'] ?? '')!= '' ) {
				$qsl .= "Z";
			}
		}
		return $qsl;
	}

	// functions to read folder size and calculate size format

	function folderSize($dir){
		$count_size = 0;
		$count = 0;
		$dir_array = scandir($dir);
		foreach($dir_array as $key=>$filename){
			if($filename!=".." && $filename!="."){
				if(is_dir($dir."/".$filename)){
					$new_foldersize = $this->foldersize($dir."/".$filename);
					$count_size = $count_size+ $new_foldersize;
				}else if(is_file($dir."/".$filename)){
					$count_size = $count_size + filesize($dir."/".$filename);
					$count++;
				}
			}
		}
		return $count_size;
	}

	function sizeFormat($bytes){
		$kb = 1024;
		$mb = $kb * 1024;
		$gb = $mb * 1024;
		$tb = $gb * 1024;

		if (($bytes >= 0) && ($bytes < $kb)) {
			return $bytes . ' B';

		} elseif (($bytes >= $kb) && ($bytes < $mb)) {
			return ceil($bytes / $kb) . ' KB';

		} elseif (($bytes >= $mb) && ($bytes < $gb)) {
			return ceil($bytes / $mb) . ' MB';

		} elseif (($bytes >= $gb) && ($bytes < $tb)) {
			return ceil($bytes / $gb) . ' GB';

		} elseif ($bytes >= $tb) {
			return ceil($bytes / $tb) . ' TB';
		} else {
			return $bytes . ' B';
		}
	}

	function country2flag($code) {
		$code = strtoupper($code);
	
		$offset = 0x1F1E6;
		$code_p1 = $offset + ord($code[0]) - ord('A');
		$code_p2 = $offset + ord($code[1]) - ord('A');
	
		$flag = mb_chr($code_p1, 'UTF-8') . mb_chr($code_p2, 'UTF-8');
		return $flag;
	}

}
