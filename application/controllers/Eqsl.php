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


		// Render Page
		$data['page_title'] = __("eQSL Cards");


		$data['qslarray'] = $this->eqsl_images->eqsl_qso_list();

		$this->load->view('interface_assets/header', $data);
		$this->load->view('eqslcard/index');
		$this->load->view('interface_assets/footer');
	}

	public function import() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();
		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);
		$data['active_station_info'] = $station_profile->row();

		// Check if eQSL Nicknames have been defined
		$this->load->model('eqslmethods_model');
		$eqsl_locations = $this->eqslmethods_model->all_of_user_with_eqsl_nick_defined();
		if ($eqsl_locations->num_rows() == 0) {
			$this->session->set_flashdata('error', 'eQSL Nicknames in Station Profiles aren\'t defined!');
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
				$this->session->set_flashdata('warning', 'You have not defined your eQSL.cc credentials!');
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
			$this->session->set_flashdata('error', 'eQSL Nicknames in Station Profiles aren\'t defined!');
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
				$this->session->set_flashdata('warning', 'You have not defined your eQSL.cc credentials!');
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

	function image($id) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$this->load->library('electronicqsl');
		$this->load->model('Eqsl_images');

		if ($this->Eqsl_images->get_image($id) == "No Image") {
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

			$image_url = $this->electronicqsl->card_image($username, urlencode($password), $callsign, $band, $mode, $year, $month, $day, $hour, $minute);
			$file = file_get_contents($image_url, true);  // TODO use curl instead

			$dom = new domDocument;
			$dom->loadHTML($file);
			$dom->preserveWhiteSpace = false;
			$images = $dom->getElementsByTagName('img');

			if (!isset($images) || count($images) == 0) {
				$h3 = $dom->getElementsByTagName('h3');
				if (isset($h3) && ($h3->item(0) !== null)) {
					echo $h3->item(0)->nodeValue;
				} else {
					echo "Rate Limited";
				}
				exit;
			}

			foreach ($images as $image) {
				header('Content-Type: image/jpg');
				$content = file_get_contents("https://www.eqsl.cc" . $image->getAttribute('src'));
				if ($content === false) {
					echo "No response";
					exit;
				}
				echo $content;
				$filename = uniqid() . '.jpg';
				if (file_put_contents($this->Eqsl_images->get_imagePath('p') . '/' . $filename, $content) !== false) {
					$this->Eqsl_images->save_image($id, $filename);
				}
			}
		} else {
			header('Content-Type: image/jpg');
			$image_url = base_url($this->Eqsl_images->get_imagePath() . '/' . $this->Eqsl_images->get_image($id));
			header('Location: ' . $image_url);
		}
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

		if ($this->input->post('eqsldownload') == 'download') {
			$i = 0;
			$this->load->model('eqslmethods_model');
			$qslsnotdownloaded = $this->eqslmethods_model->eqsl_not_yet_downloaded();
			$eqsl_results = array();
			foreach ($qslsnotdownloaded->result_array() as $qsl) {
				$result = $this->bulk_download_image($qsl['COL_PRIMARY_KEY']);
				if ($result != '') {
					$errors++;
					if ($result == 'Rate Limited') {
						break;
					} else {
						$eqsl_results[] = array(
							'date' => $qsl['COL_TIME_ON'],
							'call' => $qsl['COL_CALL'],
							'mode' => $qsl['COL_MODE'],
							'submode' => $qsl['COL_SUBMODE'],
							'status' => $result,
							'qsoid' => $qsl['COL_PRIMARY_KEY']
						);
						continue;
					}
				} else {
					$i++;
				}
				if ($i > 0) {
					sleep(15);
				}
			}
			$data['eqsl_results'] = $eqsl_results;
			$data['eqsl_stats'] = __("Successfully downloaded: ") . $i . __(" / Errors: ") . count($eqsl_results);
			$data['page_title'] = "eQSL Download Information";

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

		$this->session->set_flashdata('success', 'All eQSLs Marked as Uploaded');

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
