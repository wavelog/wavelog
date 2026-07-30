<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Continents extends CI_Controller {

	function __construct() {
		parent::__construct();
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index()
	{
        $this->load->model('bands');
        $this->load->model('logbookadvanced_model');
		$this->load->model('gridmap_model');

        $data['bands'] = $this->bands->get_worked_bands();
		$data['modes'] = $this->gridmap_model->get_worked_modes();
		
		// Render User Interface

		// Set Page Title
		$data['page_title'] = __("Continents");

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('continents/index');
		$this->load->view('interface_assets/footer');
	}


	public function get_continents() {

		$searchCriteria = array(
			'mode' => xss_clean($this->input->post('mode')),
			'band' => xss_clean($this->input->post('band')),
		);

		$this->load->model('logbook_model');

		$continentsstats = array();

		$total_continents = $this->logbook_model->total_continents($searchCriteria);
		$i = 0;

		if ($total_continents) {
			foreach($total_continents->result() as $qso_numbers) {
				$continentsstats[$i]['cont'] = $qso_numbers->COL_CONT;
				$continentsstats[$i++]['count'] = $qso_numbers->count;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($continentsstats);
	}

}
