<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Distances extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
    }

    public function index()
    {
        // Render Page
        $data['page_title'] = __("Distances Worked");

        $this->load->model('bands');
        $data['bands_available'] = $this->bands->get_worked_bands_distances();
        $data['sats_available'] = $this->bands->get_worked_sats();
        $data['orbits'] = $this->bands->get_worked_orbits();
        $data['user_default_band'] = $this->session->userdata('user_default_band');
        $data['adif_propmodes'] = $this->config->item('adif_propmodes');

        $this->load->model('distances_model');
        $data['modes'] = $this->distances_model->get_worked_modes();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('distances/index');
        $this->load->view('interface_assets/footer');
    }

    public function get_distances(){
        // POST data
        $postData = $this->input->post();

        //load model
        $this->load->model('Distances_model');

        if ($this->session->userdata('user_measurement_base') == NULL) {
            $measurement_base = $this->config->item('measurement_base');
        }
        else {
            $measurement_base = $this->session->userdata('user_measurement_base');
        }

        // get data
        $data = $this->Distances_model->get_distances($postData, $measurement_base);

        return json_encode($data);
    }

    public function test_distance(){
        // POST data
        $postdata['band'] = "sat";
        $postdata['sat'] = "All";

        //load model
        $this->load->model('Distances_model');

        if ($this->session->userdata('user_measurement_base') == NULL) {
            $measurement_base = $this->config->item('measurement_base');
        }
        else {
            $measurement_base = $this->session->userdata('user_measurement_base');
        }

        // get data
        $data = $this->Distances_model->get_distances($postdata, $measurement_base);

        return json_encode($data);
    }

	public function getDistanceQsos(){
		$this->load->model('distances_model');

		$distance = $this->input->post('distance', true);
		$band = $this->input->post('band', true);
		$sat = $this->input->post('sat', true);
		$propagation = $this->input->post('propagation', true);
		$mode = $this->input->post('mode', true);

		$data['results'] = $this->distances_model->qso_details($distance, $band, $sat, $propagation, $mode);
		$data['adif_propmodes'] = $this->config->item('adif_propmodes');

		// Render Page
		$data['page_title'] = "Log View - " . $distance;
		$data['filter'] = __("QSOs with") . " " . $distance . " " . __("and band"). " " . $band. " " . __("and propagation"). " " . $propagation;
		$this->load->view('awards/details', $data);
	}
}
