<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	This controller contains features for oqrs (Online QSL Request System)
*/

class Oqrs extends CI_Controller {

	function __construct() {
		parent::__construct();
		// Commented out to get public access
		// $this->load->model('user_model');
		// if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		if (($this->config->item('disable_oqrs') ?? false)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	function _remap($method) {
		$class = new ReflectionClass('Oqrs');
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
		$found = false;

		foreach ($methods as $m) {
			if ($m->name == $method) {
				$found = true;
				$this->{$m->name}();
        		break; // Exit the loop once the method is called
			}
		}

		if (!$found) {
			$this->index($method);
		}
	}

	public function index($public_slug = NULL) {
		$this->_initialize_visitor_language($public_slug);
		$this->load->model('oqrs_model');
		$this->load->model('publicsearch');
		$this->load->model('stationsetup_model');

		if ($public_slug === NULL) {
			show_404(__("Unknown Public Page."));
		}

		$data['slug'] = $this->security->xss_clean($public_slug);
        // check if the public slug exists
        $logbook_id = $this->stationsetup_model->public_slug_exists_logbook_id($data['slug']);
        if ($logbook_id == false) {
            $data['userid'] = null;
			$data['oqrs_enabled'] = null;
			$data['public_search_enabled'] = false;
			$data['disable_oqrs'] = $this->config->item('disable_oqrs');
			$data['stations'] = null;
			$data['page_title'] = __("Log Search & OQRS");
			$data['global_oqrs_text'] = '';
			$data['groupedSearch'] = false;
        } else {
			$data['userid'] = $this->publicsearch->get_userid_for_slug($data['slug']);
			$data['oqrs_enabled'] = $this->oqrs_model->oqrs_enabled($data['slug']);
			$data['public_search_enabled'] = $this->publicsearch->public_search_enabled($data['slug']);
			$data['disable_oqrs'] = $this->config->item('disable_oqrs');
			$data['stations'] = $this->oqrs_model->get_oqrs_stations($data['userid']);
			$data['page_title'] = __("Log Search & OQRS");
			$data['global_oqrs_text'] = $this->user_options_model->get_options('oqrs',array('option_name'=>'global_oqrs_text','option_key'=>'text'))->row()->option_value ?? '';
			$data['groupedSearch'] = $this->user_options_model->get_options('oqrs',array('option_name'=>'oqrs_grouped_search','option_key'=>'boolean'), $data['userid'])->row()->option_value;
		}

		$this->load->view('visitor/layout/header', $data);
		$this->load->view('oqrs/index');
		$this->load->view('interface_assets/footer');
    }

	public function get_station_info() {
		$this->load->model('oqrs_model');
		$result = $this->oqrs_model->get_station_info($this->input->post('station_id', TRUE));

		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function get_qsos() {
		$station_id = $this->input->post('station_id', TRUE);

		if (!is_numeric($station_id)) {
			$this->session->set_flashdata('warning', __("Invalid Station ID"));
			redirect('oqrs');
		}

		$this->load->model('bands');
		$data['bands'] = $this->bands->get_worked_bands_oqrs($station_id);

		$this->load->model('oqrs_model');
		$result = $this->oqrs_model->get_qsos($station_id, $this->input->post('callsign', TRUE), $data['bands']);
		$data['callsign'] = $this->input->post('callsign', TRUE);
		$data['result'] = $result['qsoarray'];
		$data['qsocount'] = $result['qsocount'];

		$this->load->view('oqrs/result', $data);
	}

	public function get_qsos_grouped() {
		$this->load->model('oqrs_model');
		$this->load->model('publicsearch');

		$slug = $this->input->post('slug', TRUE);
		$userid = $this->publicsearch->get_userid_for_slug($slug);
		$data['disable_oqrs'] = $this->config->item('disable_oqrs');
		$data['oqrs_enabled'] = $this->oqrs_model->oqrs_enabled($slug);
		$data['public_search_enabled'] = $this->publicsearch->public_search_enabled($slug);
		$data['groupedSearchShowStationName'] = $this->user_options_model->get_options('oqrs',array('option_name'=>'oqrs_grouped_search_show_station_name','option_key'=>'boolean'), $userid)->row()->option_value;

		$data['result'] = $this->oqrs_model->getQueryDataGrouped($this->input->post('callsign', TRUE), $userid);
		$data['callsign'] = $this->input->post('callsign', TRUE);
		$data['userid'] = $this->input->post('userid', TRUE);
		$data['slug'] = $this->input->post('slug', TRUE);

		if($this->input->post('widget') != 'true') {
			$this->load->view('oqrs/request_grouped', $data);
		} else {
			$data['stations'] = $this->oqrs_model->get_oqrs_stations($userid);
			$data['page_title'] = __("Log Search & OQRS");
			$data['global_oqrs_text'] = $this->optionslib->get_option('global_oqrs_text');
			$data['groupedSearch'] = 'on';
			$data['widget_call'] = true;

			$this->load->view('visitor/layout/header', $data);
			$this->load->view('oqrs/index');
			$this->load->view('interface_assets/footer');
		}
	}

	public function not_in_log() {
		$data['page_title'] = __("Log Search & OQRS");

		$this->load->model('bands');

		$this->load->view('oqrs/notinlogform', $data);
	}

	public function save_not_in_log() {
		$station_ids = array();

		$postdata = $this->input->post(NULL, TRUE); // index is null means we get all postdata, TRUE means we XSS clean everything
		$this->load->model('oqrs_model');
		$this->oqrs_model->save_not_in_log($postdata);
		array_push($station_ids, $this->input->post('station_id', TRUE));
		$this->alert_oqrs_request($postdata, $station_ids);
	}

	/*
	* Fetches data when the user wants to make a request form, and loads info via the view
	*/
	public function request_form() {
		$this->load->model('oqrs_model');
		$data['result'] = $this->oqrs_model->getQueryData($this->input->post('station_id', TRUE), $this->input->post('callsign', TRUE));
		$data['callsign'] = $this->input->post('callsign', TRUE);
		$data['qslinfo'] =  $this->oqrs_model->getQslInfo($this->input->post('station_id', TRUE));

		$this->load->view('oqrs/request', $data);
	}

	public function requests() {
		$data['page_title'] = __("OQRS Requests");
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array) {
			$location_list = "'".implode("','",$logbooks_locations_array)."'";
		} else {
            $location_list = null;
        }

		$this->load->model('oqrs_model');
		$data['result'] = $this->oqrs_model->getOqrsRequests($location_list);
		$data['stations'] = $this->oqrs_model->get_oqrs_stations((int)$this->session->userdata('user_id'));

		$this->load->view('interface_assets/header', $data);
		$this->load->view('oqrs/showrequests');
		$this->load->view('interface_assets/footer');
	}

	public function save_oqrs_request() {
		$postdata = $this->input->post(NULL, TRUE); // index is null means we get all postdata, TRUE means we XSS clean everything
		$this->load->model('oqrs_model');
		$station_ids = $this->oqrs_model->save_oqrs_request($postdata);
		$this->alert_oqrs_request($postdata, $station_ids);
	}

	public function save_oqrs_request_grouped() {
		$postdata = $this->input->post(NULL, TRUE); // index is null means we get all postdata, TRUE means we XSS clean everything
		$this->load->model('oqrs_model');
		$station_ids = $this->oqrs_model->save_oqrs_request_grouped($postdata);
		$this->alert_oqrs_request($postdata, $station_ids);
	}

	public function delete_oqrs_line() {
		$id = $this->input->post('id', TRUE);
		$this->load->model('oqrs_model');
		$this->oqrs_model->delete_oqrs_line($id);
	}

	public function reject_oqrs_line() {
		$id = $this->input->post('id', TRUE);
		$this->load->model('oqrs_model');
		$this->oqrs_model->reject_oqrs_line($id);
	}

	public function search_log() {
		$this->load->model('oqrs_model');
		$callsign = $this->input->post('callsign', TRUE);
		$data['qsoid'] = $this->input->post('qsoid', TRUE);
		$data['oqrsid'] = $this->input->post('oqrsid', TRUE);

        $data['qsos'] = $this->oqrs_model->search_log($callsign);

		$this->load->view('oqrs/qsolist', $data);
	}

	public function search_log_time_date() {
		// Get user-preferred date format
		if ($this->session->userdata('user_date_format')) {
			$date_format = $this->session->userdata('user_date_format');
		} else {
			$date_format = $this->config->item('qso_date_format');
		}

		$time = $this->input->post('time', TRUE);
		$date = $this->input->post('date', TRUE);
		$mode = $this->input->post('mode', TRUE);
		$band = $this->input->post('band', TRUE);
		$data['qsoid'] = $this->input->post('qsoid', TRUE);
		$data['oqrsid'] = $this->input->post('oqrsid', TRUE);

		// Parse datetime using createFromFormat
		$datetime_obj = DateTime::createFromFormat("$date_format", "$date");

		$formatted_date = $datetime_obj->format('Y-m-d'); // Format for SQL DATE comparison

		$this->load->model('oqrs_model');

        $data['qsos'] = $this->oqrs_model->search_log_time_date($time, $formatted_date, $band, $mode);

		$this->load->view('oqrs/qsolist', $data);
	}

	public function alert_oqrs_request($postdata, $station_ids) {
		foreach ($station_ids as $id) {
			$this->load->model('user_model');

			$email = $this->user_model->get_email_address($id);

			$this->load->model('oqrs_model');

			$sendEmail = $this->oqrs_model->getOqrsEmailSetting($id);

			if($email != "" && $sendEmail == "1") {

				$this->load->library('email');

				if($this->optionslib->get_option('emailProtocol') == "smtp") {
					if ($this->optionslib->get_option('smtpHost') == '') {
						log_message('error', 'OQRS request email message failed. Email settings are not configured properly.');
						return;
					}

					$config = Array(
						'protocol' => $this->optionslib->get_option('emailProtocol'),
						'smtp_crypto' => $this->optionslib->get_option('smtpEncryption'),
						'smtp_host' => $this->optionslib->get_option('smtpHost'),
						'smtp_port' => $this->optionslib->get_option('smtpPort'),
						'smtp_user' => $this->optionslib->get_option('smtpUsername'),
						'smtp_pass' => $this->optionslib->get_option('smtpPassword'),
						'crlf' => "\r\n",
						'newline' => "\r\n"
					);

					$this->email->initialize($config);
				}

				$data['callsign'] = $this->security->xss_clean($postdata['callsign']);
				$data['usermessage'] = $this->security->xss_clean($postdata['message']);

				$this->load->model('Stations');
				$uid = $this->Stations->profile($id)->row()->user_id;
				$message = $this->email->load('email/oqrs_request', $data,  $this->user_model->get_by_id($uid)->row()->user_language);

				$this->email->from($this->optionslib->get_option('emailAddress'), $this->optionslib->get_option('emailSenderName'));
				$this->email->to($email);
				$this->email->reply_to($this->security->xss_clean($postdata['email']), strtoupper($data['callsign']));

				$this->email->subject($message['subject']);
				$this->email->message($message['body']);

				if (! $this->email->send()) {
					log_message('error', 'OQRS Alert! Email settings are incorrect.');
				} else {
					log_message('debug', 'An OQRS request is made.');
				}
			}
		}
	}

	public function add_oqrs_to_print_queue() {
		$this->load->model('oqrs_model');
		$id = $this->input->post('id', TRUE);

		$this->oqrs_model->add_oqrs_to_print_queue($id);
	}

	public function mark_oqrs_line_as_done() {
		$this->load->model('oqrs_model');
		$id = $this->input->post('id', TRUE);

        $this->oqrs_model->mark_oqrs_line_as_done($id);
	}

	public function search() {
		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$this->load->model('oqrs_model');

		$searchCriteria = array(
			'user_id' => (int)$this->session->userdata('user_id'),
			'de' => $this->input->post('de', TRUE),
			'dx' => $this->input->post('dx', TRUE),
			'status' => $this->input->post('status', TRUE),
			'oqrsResults' => $this->input->post('oqrsResults', TRUE),
		);

		$qsos = $this->oqrs_model->searchOqrs($searchCriteria);
		foreach ($qsos as &$qso) {
			$qso['requesttime'] = date($custom_date_format . " H:i", strtotime($qso['requesttime']));
			$qso['date'] = date($custom_date_format, strtotime($qso['date']));
			$qso['time'] = date('H:i', strtotime($qso['time']));
		}

		header("Content-Type: application/json");
		print json_encode($qsos);
	}

	public function status_info() {
		$this->load->view('oqrs/status_info');
	}

	public function delete_oqrs_qso_match() {
		$this->load->model('oqrs_model');
		$id = $this->input->post('id', TRUE);
		$qsoid = $this->input->post('qsoid', TRUE);
		$this->oqrs_model->delete_oqrs_qso_match($id, $qsoid);
		header('Content-Type: application/json');
		echo json_encode(array('status' => 'success', 'message' => __("QSO match deleted successfully.")));
	}

	public function add_qso_match_to_oqrs() {
		$this->load->model('oqrs_model');
		$qsoid = $this->input->post('qsoid', TRUE);
		$oqrsid = $this->input->post('oqrsid', TRUE);
		$this->oqrs_model->add_qso_match_to_oqrs($qsoid, $oqrsid);
		header('Content-Type: application/json');
		echo json_encode(array('status' => 'success', 'message' => __("QSO match added successfully.")));
	}
	/**
	 * Initializes the visitor's language and applies it immediately.
	 *
	 * This function checks for a language cookie. If not present, it detects the visitor's
	 * browser language, sets a corresponding language cookie, and then performs a
	 * redirect to the current URL. The redirect forces the browser to make a new
	 * request with the new cookie, allowing the Gettext hook to apply the language
	 * change on the initial visit.
	 *
	 * @param string|null $public_slug The public slug from the URL.
	 */
	private function _initialize_visitor_language($public_slug = NULL) {
		$cookie_name = $this->config->item('gettext_cookie', 'gettext');

		if ($this->input->cookie($cookie_name, TRUE)) {
			return;
		}

		if (empty($public_slug)) {
			return;
		}

		$this->load->model('publicsearch');
		$this->load->model('user_options_model');

		$user_id = $this->publicsearch->get_userid_for_slug($public_slug);

		if ($user_id) {
			$lang_setting = $this->user_options_model->get_options('oqrs', array('option_name' => 'oqrs_use_visitor_browser_language', 'option_key' => 'boolean'), $user_id)->row();

			if (($lang_setting->option_value ?? 'on') == 'on') {
				$this->load->helper('language');
				$available_languages = $this->config->item('languages');
				$browser_languages = parse_accept_language($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

				foreach ($browser_languages as $browser_lang_code => $priority) {
					foreach ($available_languages as $app_lang) {
						if (strcasecmp($browser_lang_code, $app_lang['locale']) === 0) {
							
							$cookie = array(
								'name'   => $cookie_name,
								'value'  => $app_lang['gettext'], 
								'expire' => 3600 * 24 * 30, 
								'secure' => FALSE,
							);
							$this->input->set_cookie($cookie);

							$this->load->helper('url'); 
							redirect(current_url());    

							exit;
						}
					}
				}
			}
		}
	}
}
