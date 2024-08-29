<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Distancerecords extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
    }

    public function index()
    {

        $this->load->model('distancerecords_model');

        // Render Page
        $data['page_title'] = __("Distance Records");
        $data['distances'] = $this->distancerecords_model->get_records();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('distancerecords/index');
        $this->load->view('interface_assets/footer');
    }

}
