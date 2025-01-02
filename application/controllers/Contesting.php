<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	This controller contains features for contesting
*/

class Contesting extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('cat');
		$this->load->model('stations');
		$this->load->model('modes');
		$this->load->model('contesting_model');
		$this->load->model('bands');

		// Getting the live/post mode from GET command
        // 0 = live
        // 1 = post (manual)
        $get_manual_mode = $this->input->get('manual', true);
        if ($get_manual_mode == '0' || $get_manual_mode == '1') {
            $data['manual_mode'] = $get_manual_mode;
        } else {
            show_404();
        }

		$data['my_gridsquare'] = $this->stations->find_gridsquare();
		$data['radios'] = $this->cat->radios();
		$data['radio_last_updated'] = $this->cat->last_updated()->row();
		$data['modes'] = $this->modes->active();
		$data['contestnames'] = $this->contesting_model->getActivecontests();
		$data['bands'] = $this->bands->get_user_bands_for_qso_entry();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contesting.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/contesting.js")),
		];

		$this->load->library('form_validation');

		$this->form_validation->set_rules('start_date', 'Date', 'required');
		$this->form_validation->set_rules('start_time', 'Time', 'required');
		$this->form_validation->set_rules('callsign', 'Callsign', 'required');

		$data['page_title'] = __("Contest Logging");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function getSessionQsos() {
		session_write_close();
		$this->load->model('Contesting_model');

		$qso = $this->input->post('qso', true);

		header('Content-Type: application/json');
		echo json_encode($this->Contesting_model->getSessionQsos($qso));
	}

	public function getSession() {
		session_write_close();
		$this->load->model('Contesting_model');

		header('Content-Type: application/json');
		echo json_encode($this->Contesting_model->getSession());
	}

	public function deleteSession() {
		$this->load->model('Contesting_model');

		$qso = $this->input->post('qso', true);

		$data = $this->Contesting_model->deleteSession($qso);

		return json_encode($data);
	}

	public function setSession() {
		$this->load->model('Contesting_model');
		$this->Contesting_model->setSession();

		$this->session->set_userdata('radio', $this->input->post('radio', true));
		
		header('Content-Type: application/json');
		echo json_encode($this->Contesting_model->getSession());
	}

	public function create() {
		$this->load->model('Contesting_model');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('name', 'Contest Name', 'required');
		$this->form_validation->set_rules('adifname', 'Adif Contest Name', 'required');

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = "Create Mode";
			$this->load->view('contesting/create', $data);
		} else {
			$this->Contesting_model->add();
		}
	}

	public function add() {
		$this->load->model('Contesting_model');

		$data['contests'] = $this->Contesting_model->getAllContests();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contesting.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/contesting.js")),
		];

		// Render Page
		$data['page_title'] = __("Contests");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/add');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function edit($id) {
		$this->load->model('Contesting_model');
		$this->load->library('form_validation');

		$item_id_clean = $this->security->xss_clean($id);

		$data['contest'] = $this->Contesting_model->contest($item_id_clean);

		$data['page_title'] = __("Update Contest");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contesting.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/contesting.js")),
		];

		$this->form_validation->set_rules('name', 'Contest Name', 'required');
		$this->form_validation->set_rules('adifname', 'Adif Contest Name', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('interface_assets/header', $data);
			$this->load->view('contesting/edit');
			$this->load->view('interface_assets/footer', $footerData);
		}
		else
		{
			$this->Contesting_model->edit($item_id_clean);

			$data['notice'] = "Contest ".$this->security->xss_clean($this->input->post('name', true))." Updated";

			redirect('contesting/add');
		}
	}

	public function delete() {
		$id = $this->input->post('id', true);
		$this->load->model('Contesting_model');
		$this->Contesting_model->delete($id);
	}

	public function activate() {
		$id = $this->input->post('id', true);
		$this->load->model('Contesting_model');
		$this->Contesting_model->activate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivate() {
		$id = $this->input->post('id', true);
		$this->load->model('Contesting_model');
		$this->Contesting_model->deactivate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivateall() {
		$this->load->model('Contesting_model');
		$this->Contesting_model->deactivateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function activateall() {
		$this->load->model('Contesting_model');
		$this->Contesting_model->activateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	/*
	 *  Function is used for dupe-checking in contestinglogging
	 */
	public function checkIfWorkedBefore() {
		session_write_close();
		$call = $this->input->post('call', true);
		$band = $this->input->post('band', true);
		$mode = $this->input->post('mode', true);
		$contest = $this->input->post('contest', true);

		$this->load->model('Contesting_model');

		$result = $this->Contesting_model->checkIfWorkedBefore($call, $band, $mode, $contest);
		header('Content-Type: application/json');
		if ($result && $result->num_rows()) {
			$timeb4=substr($result->row()->b4,0,5);
			$custom_date_format = $this->session->userdata('user_date_format');
			$abstimeb4=date($custom_date_format, strtotime($result->row()->COL_TIME_OFF)).' '.date('H:i',strtotime($result->row()->COL_TIME_OFF));
			echo json_encode(array('message' => 'Worked at '.$abstimeb4.' ('.$timeb4.' ago) before'));
		}
		return;
	}
}
