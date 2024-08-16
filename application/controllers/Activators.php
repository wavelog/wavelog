<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activators extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        if (!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!"));
            redirect('dashboard');
        }
    }

    public function index()
    {
        $data['page_title'] = __("Gridsquare Activators");

        $this->load->model('Activators_model');

        if ($this->input->post('band') != NULL) {   // Band is not set when page first loads.
            $band = $this->input->post('band');
        } else {
            $band = 'All';
        }

        if ($this->input->post('mincount') != NULL) {   // mincount is not set when page first loads.
            $mincount = $this->input->post('mincount');
        } else {
            $mincount = 2;
        }

        if ($this->input->post('leogeo') != NULL) {   // orbit is not set when page first loads.
            $orbit = $this->input->post('leogeo');
        } else {
            $orbit = 'both';
        }

        $this->load->model('bands');

        $data['worked_bands'] = $this->bands->get_worked_bands();
        $data['mincount'] = $mincount;
        $data['maxactivatedgrids'] = $this->Activators_model->get_max_activated_grids();
        $data['orbit'] = $orbit;
        $data['activators_array'] = $this->Activators_model->get_activators($band, $mincount, $orbit);
        $data['bandselect'] = $band;

        $footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/activators.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/activators.js")),
		];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('activators/index');
        $this->load->view('interface_assets/footer', $footerData);
    }

}
