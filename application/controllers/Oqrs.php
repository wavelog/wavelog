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
		$this->load->model('oqrs_model');
		$this->load->model('publicsearch');

       	$slug = $this->security->xss_clean($public_slug);
		$data['slug'] = $slug;
		$data['oqrs_enabled'] = $this->oqrs_model->oqrs_enabled($slug);
		$data['public_search_enabled'] = $this->publicsearch->public_search_enabled($slug);
		$data['disable_oqrs'] = $this->config->item('disable_oqrs');
		$data['stations'] = $this->oqrs_model->get_oqrs_stations();
		$data['page_title'] = __("Log Search & OQRS");
		$data['global_oqrs_text'] = $this->optionslib->get_option('global_oqrs_text');
		$data['groupedSearch'] = $this->optionslib->get_option('groupedSearch');

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
		$data['result'] = $this->oqrs_model->getQueryDataGrouped($this->input->post('callsign', TRUE));
		$data['callsign'] = $this->input->post('callsign', TRUE);

		if($this->input->post('widget') != 'true') {
			$this->load->view('oqrs/request_grouped', $data);
		} else {
			$data['stations'] = $this->oqrs_model->get_oqrs_stations();
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
		$data['stations'] = $this->oqrs_model->get_oqrs_stations();

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

	public function search_log() {
		$this->load->model('oqrs_model');
		$callsign = $this->input->post('callsign', TRUE);

        $data['qsos'] = $this->oqrs_model->search_log($callsign);

		$this->load->view('qslprint/qsolist', $data);
	}

	public function search_log_time_date() {
		$this->load->model('oqrs_model');
		$time = $this->input->post('time', TRUE);
		$date = $this->input->post('date', TRUE);
		$mode = $this->input->post('mode', TRUE);
		$band = $this->input->post('band', TRUE);

        $data['qsos'] = $this->oqrs_model->search_log_time_date($time, $date, $band, $mode);

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

	public function mark_oqrs_line_as_done() {
		$this->load->model('oqrs_model');
		$id = $this->input->post('id', TRUE);

        $this->oqrs_model->mark_oqrs_line_as_done($id);
	}

	public function search() {
		$this->load->model('oqrs_model');

		$searchCriteria = array(
			'user_id' => (int)$this->session->userdata('user_id'),
			'de' => $this->input->post('de', TRUE),
			'dx' => $this->input->post('dx', TRUE),
			'status' => $this->input->post('status', TRUE),
			'oqrsResults' => $this->input->post('oqrsResults', TRUE),
		);

		$qsos = $this->oqrs_model->searchOqrs($searchCriteria);

		header("Content-Type: application/json");
		print json_encode($qsos);
	}

}
