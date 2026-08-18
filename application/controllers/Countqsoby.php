<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Countqsoby extends CI_Controller {

    function __construct() {
        parent::__construct();

        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
    }

    public function index() {
        $data['page_title'] = __("Count QSOs by...");

        // Dropdown data comes from catalog tables only, so the first load
        // fires no queries against the big QSO log table.
        $this->load->model('bands');
        $data['user_bands'] = $this->bands->get_user_bands();

        $data['sats_available'] = $this->db->select('name, displayname')->order_by('name', 'ASC')->get('satellite')->result();
        $data['orbits'] = $this->db->select('orbit')->distinct()->where('orbit IS NOT NULL', null, false)->order_by('orbit', 'ASC')->get('satellite')->result();

        $this->load->model('modes');
        $data['modes'] = $this->modes->active();

        $data['user_default_band'] = $this->session->userdata('user_default_band');
        $data['user_default_confirmation'] = $this->session->userdata('user_default_confirmation');
        $data['adif_propmodes'] = $this->config->item('adif_propmodes');

        $this->load->view('interface_assets/header', $data);
        $this->load->view('countqsoby/index');
        $this->load->view('interface_assets/footer');
    }

    public function get_counts() {
        $this->load->model('countqsoby_model');

        $data = $this->countqsoby_model->get_counts($this->input->post());

        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function details() {
        $this->load->model('countqsoby_model');

        $type = $this->input->post('type', true);
        $group = $this->input->post('group', true);
        $band = $this->input->post('band', true);
        $sat = $this->input->post('sat', true);
        $orbit = $this->input->post('orbit', true);
        $propagation = $this->input->post('propagation', true);
        $mode = $this->input->post('mode', true);
        $dateFrom = $this->input->post('dateFrom', true);
        $dateTo = $this->input->post('dateTo', true);

        $data['results'] = $this->countqsoby_model->qso_details($type, $group, $band, $sat, $propagation, $mode, $orbit, $dateFrom, $dateTo);
        $data['adif_propmodes'] = $this->config->item('adif_propmodes');

        $data['page_title'] = "Log View - " . $group;
        // awards/details echoes $filter raw, so escape the user-supplied part here
        $data['filter'] = __("QSOs with") . " " . htmlspecialchars((string)$group);
        $this->load->view('awards/details', $data);
    }
}
