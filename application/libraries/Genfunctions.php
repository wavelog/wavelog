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

	/**
	 * Function to convert a FontAwesome icon to a PNG image and returns filename if successful (or already exists)
	 * Source: https://github.com/sperelson/Awesome2PNG
	 * Modified by HB9HIL
	 * 
	 * @param string 	$unicode		Unicode of the FontAwesome icon (e.g. f0c8) - required
	 * @param string 	$color			Hexadecimal color of the icon (default: ffffff)
	 * @param string 	$dest_file		Destination file path (optional)
	 * @param int 		$pixelshigh		Height of the icon in pixels (default: 32)
	 * @param int 		$alpha			Alpha channel of the icon (default: 0)
	 * @param array 	$padding		Padding of the icon (default: array(3, 3, 3, 3))
	 * 
	 * @return bool
	 */
	function fas2png($unicode, $color='ffffff', $dest_file = null, $pixelshigh=32, $alpha=0, $padding=array(3, 3, 3, 3)) {
		try {
			// Set the target path
			if ($dest_file != null) {
				$icon = $dest_file;
			} else {
				$CI = &get_instance();
				$cachepath = $CI->config->item('cache_path') == '' ? APPPATH . 'cache/' : $CI->config->item('cache_path');
				$cacheDir = $cachepath . "fas_icons_cache/";
				if (!is_dir($cacheDir)) {
					mkdir($cacheDir, 0755, true);
				}
				$icon = $cacheDir . 'icon_' . $unicode . '_' . $color . '_' . $pixelshigh . '_a' . $alpha . '_p' . implode('-', $padding) . '.png';
			}

			// Check if the icon already exists
			if (file_exists($icon)) {
				return $icon;
			}

			// Check if the font file exists
			$font = realpath(APPPATH . '../') . '/assets/fontawesome/webfonts/fa-solid-900.ttf';
			if (!file_exists($font)) {
				throw new Exception('Font file not found: ' . $font);
			}

			// Variables for brute-forcing the correct point height
			$ratio = 96 / 72;
			$ratioadd = 0.0001;
			$heightalright = false;
			$count = 0;
			$maxcount = 20000;

			$text = json_decode('"&#x'.$unicode.';"');
			if ($text === null) {
				throw new Exception('Failed to decode unicode: &#x'.$unicode.';');
			}

			// Brute-force point height
			while (!$heightalright && $count < $maxcount) {
				$x = $pixelshigh / $ratio;
				$count++;
				$bounds = imagettfbbox($x, 0, $font, $text);

				if ($bounds === false) {
					throw new Exception('Failed to calculate bounding box with imagettfbbox.');
				}

				$height = abs($bounds[7] - abs($bounds[1]));

				if ($height == $pixelshigh) {
					$heightalright = true;
				} else {
					if ($height < $pixelshigh) {
						$ratio -= $ratioadd;
					} else {
						$ratio += $ratioadd;
					}
				}
			}

			if (!$heightalright) {
				throw new Exception('Could not calculate the correct height for the icon.');
			}

			$width = abs($bounds[4]) + abs($bounds[0]);

			// Create the image
			$im = imagecreatetruecolor($width + $padding[2] + $padding[3], $pixelshigh + $padding[0] + $padding[1]);
			if ($im === false) {
				throw new Exception('Failed to create image resource.');
			}

			imagesavealpha($im, true);
			$trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
			imagefill($im, 0, 0, $trans);
			imagealphablending($im, true);

			// Prepare font color
			$fontcolor = $alpha << 24 | hexdec($color);

			// Add the icon
			if (imagettftext($im, $x, 0, 1 + $padding[2], $height - abs($bounds[1]) - 1 + $padding[0], $fontcolor, $font, $text) === false) {
				throw new Exception('Failed to render icon with imagettftext.');
			}

			imagesavealpha($im, true);

			// Save the image
			if (imagepng($im, $icon) === false) {
				throw new Exception('Failed to save PNG image.');
			}

			// Sleep to make sure the file is available in the next run.
			usleep(100000); // 100ms

			// Clean up
			imagedestroy($im);

			return $icon;

		} catch (Exception $e) {
			log_message('error', $e->getMessage());
			return false;
		}
	}
}

