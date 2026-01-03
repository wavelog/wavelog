<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class eqsl extends CI_Controller {

	/* Controls who can access the controller and its functions */
	function __construct() {

		parent::__construct();

		$this->load->helper(array('form', 'url'));

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	// Default view when loading controller.
	public function index() {

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('eqsl_images');
		$this->load->library('Genfunctions');
		$folder_name = $this->eqsl_images->get_imagePath('p');
		$data['storage_used'] = $this->genfunctions->sizeFormat($this->genfunctions->folderSize($folder_name));

		// Pagination
		$this->load->library('pagination');
		$config['base_url'] = base_url().'index.php/eqsl/index/';
		$config['total_rows'] = $this->eqsl_images->count_eqsl_qso_list();
		$config['per_page'] = '25';
		$config['num_links'] = 6;
		$config['full_tag_open'] = '';
		$config['full_tag_close'] = '';
		$config['cur_tag_open'] = '<strong class="active"><a href="">';
		$config['cur_tag_close'] = '</a></strong>';

		$this->pagination->initialize($config);

		// Render Page
		$data['page_title'] = __("eQSL Cards");

		$offset = $this->uri->segment(3) ? $this->uri->segment(3) : 0;
		$data['qslarray'] = $this->eqsl_images->eqsl_qso_list($config['per_page'], $offset);

		// Calculate result range for display
		$total_rows = $config['total_rows'];
		$per_page = $config['per_page'];
		$start = $total_rows > 0 ? $offset + 1 : 0;
		$end = min($offset + $per_page, $total_rows);
		$data['result_range'] = sprintf(__("Showing %d to %d of %d entries"), $start, $end, $total_rows);

		$this->load->view('interface_assets/header', $data);
		$this->load->view('eqslcard/index');
		$this->load->view('interface_assets/footer');
	}

	public function import() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();
		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);
		$data['active_station_info'] = $station_profile->row();

		$this->load->model('cron_model');
		$data['next_run'] = $this->cron_model->get_next_run("eqsl_sync");

		// Check if eQSL Nicknames have been defined
		$this->load->model('eqslmethods_model');
		$eqsl_locations = $this->eqslmethods_model->all_of_user_with_eqsl_nick_defined();
		if ($eqsl_locations->num_rows() == 0) {
			$this->session->set_flashdata('error', __("eQSL Nicknames in Station Profiles aren't defined!"));
		}

		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'adi|ADI';

		$this->load->library('upload', $config);

		$eqsl_results = array();
		if (($this->input->post('eqslimport') == 'fetch') && (!($this->config->item('disable_manual_eqsl')))) {

			$this->load->library('EqslImporter');

			// Get credentials for eQSL
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$eqsl_password = $q->user_eqsl_password;

			// Validate that eQSL credentials are not empty
			if ($eqsl_password == '') {
				$this->session->set_flashdata('warning', __("You have not defined your eQSL.cc credentials!"));
				redirect('eqsl/import');
			}

			$eqsl_force_from_date = (!$this->input->post('eqsl_force_from_date') == "") ? $this->input->post('eqsl_force_from_date') : "";
			foreach ($eqsl_locations->result_array() as $eqsl_location) {
				$this->eqslimporter->from_callsign_and_QTH(
					$eqsl_location['station_callsign'],
					$eqsl_location['eqslqthnickname'],
					$config['upload_path'],
					$eqsl_location['station_id']
				);

				$eqsl_results[] = $this->eqslimporter->fetch($eqsl_password, $eqsl_force_from_date);
			}
		} elseif (($this->input->post('eqslimport') == 'upload')) {
			$station_id4upload = $this->input->post('station_profile');
			if ($this->stations->check_station_is_accessible($station_id4upload)) {
				$station_callsign = $this->stations->profile($station_id4upload)->row()->station_callsign;
				if (!$this->upload->do_upload()) {
					$data['page_title'] = "eQSL Import";
					$data['error'] = $this->upload->display_errors();

					$this->load->view('interface_assets/header', $data);
					$this->load->view('eqsl/import');
					$this->load->view('interface_assets/footer');

					return;
				} else {
					$data = array('upload_data' => $this->upload->data());

					$this->load->library('EqslImporter');
					$this->eqslimporter->from_file('./uploads/' . $data['upload_data']['file_name'], $station_callsign, $station_id4upload);

					$eqsl_results[] = $this->eqslimporter->import();
				}
			} else {
				log_message('error', $station_id4upload . " is not valid for user!");
			}
		} else {
			$data['page_title'] = __("eQSL Import");

			$this->load->view('interface_assets/header', $data);
			$this->load->view('eqsl/import');
			$this->load->view('interface_assets/footer');

			return;
		}

		$data['eqsl_results'] = $eqsl_results;
		$data['page_title'] = __("eQSL Import Information");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('eqsl/analysis');
		$this->load->view('interface_assets/footer');
	}

	public function export() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// Check if eQSL Nicknames have been defined
		$this->load->model('stations');
		if ($this->stations->are_eqsl_nicks_defined() == 0) {
			$this->session->set_flashdata('error', __('eQSL Nicknames in Station Profiles aren\'t defined!'));
		}

		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$this->load->model('eqslmethods_model');

		$data['page_title'] = __("eQSL QSO Upload");
		$custom_date_format = $this->session->userdata('user_date_format');

		if ((!($this->config->item('disable_manual_eqsl'))) && ($this->input->post('eqslexport') == "export")) {
			// Get credentials for eQSL
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$data['user_eqsl_name'] = $q->user_eqsl_name;
			$data['user_eqsl_password'] = $q->user_eqsl_password;

			// Validate that eQSL credentials are not empty
			if ($data['user_eqsl_name'] == '' || $data['user_eqsl_password'] == '') {
				$this->session->set_flashdata('warning', __('You have not defined your eQSL.cc credentials!'));
				redirect('eqsl/import');
			}

			$rows = '';
			// Grab the list of QSOs to send information about
			// perform an HTTP get on each one, and grab the status back
			$qslsnotsent = $this->eqslmethods_model->eqsl_not_yet_sent();

			foreach ($qslsnotsent->result_array() as $qsl) {
				$rows .= "<tr>";
				// eQSL username changes for linked account.
				// i.e. when operating /P it must be callsign/p
				// the password, however, is always the same as the main account
				$data['user_eqsl_name'] = $qsl['station_callsign'];
				$adif = $this->eqslmethods_model->generateAdif($qsl, $data);

				$status = $this->eqslmethods_model->uploadQso($adif, $qsl);

				if ($status == 'Login Error') {
					log_message('error', 'eQSL Credentials-Error for '.$data['user_eqsl_name'].'. Login will be disabled!');
					$this->eqslmethods_model->disable_eqsl_uid($this->session->userdata('user_id'));
					$status=__("User/Pass wrong for eQSL");
				} elseif ($status == 'Nick Error') {
					log_message('error', 'eQSL error for user '.$data['user_eqsl_name'].' with QTH Nickname '.($qsl['eqslqthnickname'] ?? '').' at station_profile '.($qsl['eqsl_station_id'] ?? '').'. eQSL QTH Nickname will be removed from station location!');
					$this->eqslmethods_model->disable_eqsl_station_id($this->session->userdata('user_id'),$qsl['eqsl_station_id']);
					$status=sprintf(__("No such eQSL QTH Nickname: %s"), $qsl['eqslqthnickname'] ?? '');
				}

				if($status == 'Error') {
					redirect('eqsl/export');
				}

				$timestamp = strtotime($qsl['COL_TIME_ON']);
				$rows .= "<td>" . date($custom_date_format, $timestamp) . "</td>";
				$rows .= "<td>" . date('H:i', $timestamp) . "</td>";
				$rows .= "<td>" . str_replace("0", "&Oslash;", $qsl['COL_CALL']) . "</td>";
				$rows .= "<td>" . $qsl['COL_MODE'] . "</td>";
				if (isset($qsl['COL_SUBMODE'])) {
					$rows .= "<td>" . $qsl['COL_SUBMODE'] . "</td>";
				} else {
					$rows .= "<td></td>";
				}
				$rows .= "<td>" . $qsl['COL_BAND'] . "</td>";
				$rows .= "<td>" . $status . "</td>";
			}
			$rows .= "</tr>";
			$data['eqsl_table'] = $this->generateResultTable($custom_date_format, $rows);
		} else {
			$qslsnotsent = $this->eqslmethods_model->eqsl_not_yet_sent();
			if ($qslsnotsent->num_rows() > 0) {
				$data['eqsl_table'] = $this->writeEqslNotSent($qslsnotsent->result_array(), $custom_date_format);
			}
		}

		$this->load->view('interface_assets/header', $data);
		$this->load->view('eqsl/export');
		$this->load->view('interface_assets/footer');
	}

	function generateResultTable($custom_date_format, $rows) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$table = '<table = style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">';
		$table .= "<thead><tr class=\"titles\">";
		$table .= "<th>Date</th>";
		$table .= "<th>Time</th>";
		$table .= "<th>Call</th>";
		$table .= "<th>Mode</th>";
		$table .= "<th>Submode</th>";
		$table .= "<th>Band</th>";
		$table .= "<th>Status</th>";
		$table .= "</tr></thead><tbody>";

		$table .= $rows;
		$table .= "</tbody></table>";

		return $table;
	}

	function writeEqslNotSent($qslsnotsent, $custom_date_format) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$table = '<table = style="width:100%" class="table-sm table qsotable table-bordered table-hover table-striped table-condensed text-center">';
		$table .= "<thead><tr class=\"titles\">";
		$table .= "<th>Date</th>";
		$table .= "<th>Time</th>";
		$table .= "<th>Call</th>";
		$table .= "<th>Mode</th>";
		$table .= "<th>Submode</th>";
		$table .= "<th>Band</th>";
		$table .= "<th>eQSL QTH Nickname</th>";
		$table .= "</tr></thead><tbody>";

		foreach ($qslsnotsent as $qsl) {
			$table .= "<tr>";
			$timestamp = strtotime($qsl['COL_TIME_ON']);
			$table .= "<td>" . date($custom_date_format, $timestamp) . "</td>";
			$table .= "<td>" . date('H:i', $timestamp) . "</td>";
			$table .= "<td><a href=\"javascript:displayQso(" . $qsl['COL_PRIMARY_KEY'] . ")\">" . str_replace("0", "&Oslash;", strtoupper($qsl['COL_CALL'])) . "</a></td>";
			$table .= "<td>" . $qsl['COL_MODE'] . "</td>";

			if (isset($qsl['COL_SUBMODE'])) {
				$table .= "<td>" . $qsl['COL_SUBMODE'] . "</td>";
			} else {
				$table .= "<td></td>";
			}
			$table .= "<td>" . $qsl['COL_BAND'] . "</td>";
			$table .= "<td>" . $qsl['eqslqthnickname'] . "</td>";
			$table .= "</tr>";
		}
		$table .= "</tbody></table>";

		return $table;
	}

	function image($id, $width=null) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$etag=$this->gen_check_etag("wl_eqsl_cacher_".$id,$width);
		if ($etag == '0') { // Cached on Client side
			return; 
		}
		$this->load->library('electronicqsl');
		$this->load->model('Eqsl_images');

		if ($this->Eqsl_images->get_image($id) == "No Image") {
			$this->load->model('logbook_model');
			$this->load->model('user_model');
			$qso_query = $this->logbook_model->get_qso($id);

			// Check if QSO exists and is accessible
			if (!$qso_query || $qso_query->num_rows() == 0) {
				show_error(__('QSO not found or not accessible'), 404);
				return;
			}

			$qso = $qso_query->row();

			$qso_timestamp = strtotime($qso->COL_TIME_ON);
			$callsign = $qso->COL_CALL;
			$band = $qso->COL_BAND;
			$mode = $qso->COL_MODE;
			$year = date('Y', $qso_timestamp);
			$month = date('m', $qso_timestamp);
			$day = date('d', $qso_timestamp);
			$hour = date('H', $qso_timestamp);
			$minute = date('i', $qso_timestamp);

			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			if ($query->num_rows() == 0) {
				show_error(__('User not found'), 404);
				return;
			}
			$q = $query->row();
			$username = $qso->COL_STATION_CALLSIGN;
			$password = $q->user_eqsl_password;

			// Check if eQSL password is set
			if (empty($password)) {
				show_error(__('eQSL password not configured for this user'), 400);
				return;
			}

			$image_url = $this->electronicqsl->card_image($username, urlencode($password), $callsign, $band, $mode, $year, $month, $day, $hour, $minute);

			// Use curl for better error handling instead of file_get_contents
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $image_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog-eQSL/1.0');
			$file = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($file === false || $http_code != 200) {
				show_error(__('Failed to fetch eQSL image data'), 503);
				return;
			}

			$dom = new domDocument;
			// Suppress warnings for malformed HTML
			libxml_use_internal_errors(true);
			$dom->loadHTML($file);
			libxml_clear_errors();
			$dom->preserveWhiteSpace = false;
			$images = $dom->getElementsByTagName('img');

			if (!isset($images) || count($images) == 0) {
				$h3 = $dom->getElementsByTagName('h3');
				if (isset($h3) && ($h3->item(0) !== null)) {
					$error_message = $h3->item(0)->nodeValue;
				} else {
					$error_message = "Rate Limited";
				}
				show_error(__('eQSL image not available') . ': ' . $error_message, 503);
				return;
			}

			foreach ($images as $image) {
				$image_src = "https://www.eqsl.cc" . $image->getAttribute('src');

				// Use curl for downloading the actual image
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $image_src);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog-eQSL/1.0');
				$content = curl_exec($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if ($content === false || $http_code != 200) {
					show_error(__('Failed to download eQSL image'), 503);
					return;
				}

				$filename = uniqid() . '.jpg';
				$image_path = $this->Eqsl_images->get_imagePath('p') . '/' . $filename;
				$save_result = file_put_contents($image_path, $content);

				if ($save_result !== false) {
					$this->Eqsl_images->save_image($id, $filename);
				} else {
					log_message('error', 'Failed to save eQSL image to: ' . $image_path);
				}

				$this->output_image_with_width($content, $width, $etag);	// This must be 1st time (because it's freshly fetched from eQSL) - so add the etag
				return; // Only process the first image found
			}
		} else {
			// Load server-cached image if etag isn't 0
			if ($etag != '0') {
				$image_file = $this->Eqsl_images->get_imagePath('p') . '/' . $this->Eqsl_images->get_image($id);
				$content = file_get_contents($image_file);
				if ($content !== false) {
					$this->output_image_with_width($content, $width, $etag);
				} else {
					show_error(__('Failed to load cached eQSL image'), 500);
				}
			}
		}
	}

	private function gen_check_etag($eta,$modifier) {

		$etag = '"' . md5($eta.$modifier) . '"';

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
		    trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			session_write_close();
			session_cache_limiter('public');
			header('HTTP/1.1 304 Not Modified');
			header('ETag: ' . $etag);
			header('Pragma: public');
			header('Cache-Control: public, max-age=31536000, immutable'); 
			header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 year')) . ' GMT'); // Never expire
			return '0';
		}
		return $etag;
	}

	/**
	 * Output image with optional width-based thumbnail generation
	 * @param string $image_data Binary image data
	 * @param int $width Desired width (null for original size)
	 */
	private function output_image_with_width($image_data, $width, $etag) {
		session_write_close();
		session_cache_limiter('public');

		header('Content-Type: image/jpg');
		header('ETag: ' . $etag);
		header('Pragma: public');
		header('Cache-Control: public, max-age=31536000, immutable'); 
		header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 year')) . ' GMT'); // Never expire

		// If width is null or 0, output original image
		if ($width!=(int)$width || $width === null || $width <= 0 || $width>1500) {	// Return original Image if huger 1500 or smaller 100 or crap
			echo $image_data;
			return;
		}

		// Generate thumbnail
		$original_image = imagecreatefromstring($image_data);
		if ($original_image === false) {
			// Failed to process, output original
			echo $image_data;
			return;
		}

		$original_width = imagesx($original_image);
		$original_height = imagesy($original_image);

		// Calculate proportional height
		$height = (int) (($original_height / $original_width) * $width);

		// Create new image
		$thumbnail = imagecreatetruecolor($width, $height);

		// Resample
		imagecopyresampled($thumbnail, $original_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

		// Output
		imagejpeg($thumbnail, null, 90); // 90% quality

		// Clean up
		imagedestroy($original_image);
		imagedestroy($thumbnail);
	}

	function bulk_download_image($id) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$this->load->model('Eqsl_images');

		$this->load->model('logbook_model');
		$this->load->model('user_model');
		$qso_query = $this->logbook_model->get_qso($id);
		$qso = $qso_query->row();
		$qso_timestamp = strtotime($qso->COL_TIME_ON);
		$callsign = $qso->COL_CALL;
		$band = $qso->COL_BAND;
		$mode = $qso->COL_MODE;
		$year = date('Y', $qso_timestamp);
		$month = date('m', $qso_timestamp);
		$day = date('d', $qso_timestamp);
		$hour = date('H', $qso_timestamp);
		$minute = date('i', $qso_timestamp);

		$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
		$q = $query->row();
		$username = $qso->COL_STATION_CALLSIGN;
		$password = $q->user_eqsl_password;
		$error = '';

		$image_url = $this->electronicqsl->card_image($username, urlencode($password), $callsign, $band, $mode, $year, $month, $day, $hour, $minute);
		$file = file_get_contents($image_url, true);
		if (strpos($file, 'Error') !== false) {
			$error = rtrim(preg_replace('/^\s*Error: /', '', $file));
			return $error;
		}

		$dom = new domDocument;
		$dom->loadHTML($file);
		$dom->preserveWhiteSpace = false;
		$images = $dom->getElementsByTagName('img');

		if (!isset($images) || count($images) == 0) {
			$h3 = $dom->getElementsByTagName('h3');
			if (isset($h3)) {
				$error = $h3->item(0)->nodeValue;
			} else {
				$error = "Rate Limited";
			}
			return $error;
		}

		session_write_close();
		foreach ($images as $image) {
			$content = file_get_contents("https://www.eqsl.cc" . $image->getAttribute('src'));
			if ($content === false) {
				$error = "No response";
				return $error;
			}
			$filename = uniqid() . '.jpg';
			if ($this->Eqsl_images->get_image($id) == "No Image") {
				if (file_put_contents($this->Eqsl_images->get_imagePath('p') . '/' . $filename, $content) !== false) {
					$this->Eqsl_images->save_image($id, $filename);
				}
			}
		}
	}

	public function tools() {
		// Check logged in
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$data['page_title'] = __("eQSL Tools");

		// Load frontend
		$this->load->view('interface_assets/header', $data);
		$this->load->view('eqsl/tools');
		$this->load->view('interface_assets/footer');
	}

	public function download() {
		// Check logged in
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$errors = 0;
		$this->load->library('electronicqsl');

		if ($this->input->post('eqsldownload') == 'download' && $this->config->item('enable_eqsl_massdownload')) {
			ini_set('memory_limit', '-1');
			set_time_limit(0);

			// Use new parallel library
			$this->load->library('EqslBulkDownloader');
			$this->load->model('eqslmethods_model');

			// Get and limit QSOs to 50
			$qsos = $this->eqslmethods_model->eqsl_not_yet_downloaded()->result_array();
			if (count($qsos) > 150) {
				$qsos = array_slice($qsos, 0, 150);
				$this->session->set_flashdata('warning', __('Limited to first 150 QSOs for this request. Please run again.'));
			}

			// Execute parallel download
			$results = $this->eqslbulkdownloader->downloadBatch($qsos);
			$data['eqsl_results'] = $results['errors'];
			$data['eqsl_stats'] = __("Successfully downloaded: ") . $results['success_count'] . __(" / Errors: ") . $results['error_count'];
			$data['page_title'] = "eQSL Download Information";

			// Check for rate limit
			if ($results['error_count'] > 0) {
				foreach ($results['errors'] as $err) {
					if ($err['status'] === 'Rate Limited') {
						$this->session->set_flashdata('warning', __('eQSL rate limit reached. Please wait before running again.'));
						break;
					}
				}
			}

			$this->load->view('interface_assets/header', $data);
			$this->load->view('eqsl/result');
			$this->load->view('interface_assets/footer');

		} else {

			$data['page_title'] = __("eQSL Card Image Download");
			$this->load->model('eqslmethods_model');

			$data['custom_date_format'] = $this->session->userdata('user_date_format');
			$data['qslsnotdownloaded'] = $this->eqslmethods_model->eqsl_not_yet_downloaded();

			$this->load->view('interface_assets/header', $data);
			$this->load->view('eqsl/download');
			$this->load->view('interface_assets/footer');
		}
	}

	public function mark_all_sent() {
		// Check logged in
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// mark all eqsls as sent
		$this->load->model('eqslmethods_model');
		$this->eqslmethods_model->mark_all_as_sent();

		$this->session->set_flashdata('success', __("All eQSLs marked as uploaded"));

		redirect('eqsl/tools');
	}

	/*
	 * Used for CRON job
	 */
	public function sync() {
		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$this->load->model('eqslmethods_model');
		$this->eqslmethods_model->sync();
	}
} // end class
