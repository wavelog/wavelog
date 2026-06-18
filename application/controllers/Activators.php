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
        $validHex = function($color, $default) {
            return preg_match('/^#[0-9a-fA-F]{6}$/', $color ?? '') ? $color : $default;
        };
        $map_custom_colors = json_decode($this->optionslib->get_map_custom());
        $color_confirmed = $map_custom_colors->qsoconfirm->color;
        list($r, $g, $b) = sscanf($validHex($color_confirmed ?? '', '#90EE90'), "#%02x%02x%02x");
        $data['grid_color'] = "rgba(".$r.", ".$g.", ".$b.", 0.6)";

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
			'assets/js/sections/activators.js',
		];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('activators/index');
        $this->load->view('interface_assets/footer', $footerData);
    }

}
