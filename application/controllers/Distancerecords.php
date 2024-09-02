<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Distancerecords extends CI_Controller {

    function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
    }

    public function index() {

        $this->load->model('distancerecords_model');

        if($this->session->userdata('user_date_format')) {
            $custom_date_format = $this->session->userdata('user_date_format');
        } else {
            $custom_date_format = $this->config->item('qso_date_format');
        }

        switch ($custom_date_format) {
            case 'd/m/y': $usethisformat = 'D/MM/YY';break;
            case 'd/m/Y': $usethisformat = 'D/MM/YYYY';break;
            case 'm/d/y': $usethisformat = 'MM/D/YY';break;
            case 'm/d/Y': $usethisformat = 'MM/D/YYYY';break;
            case 'd.m.Y': $usethisformat = 'D.MM.YYYY';break;
            case 'y/m/d': $usethisformat = 'YY/MM/D';break;
            case 'Y-m-d': $usethisformat = 'YYYY-MM-D';break;
            case 'M d, Y': $usethisformat = 'MMM D, YYYY';break;
            case 'M d, y': $usethisformat = 'MMM D, YY';break;
        }

        // Render Page
        $data['custom_date_format'] = $custom_date_format;
        $data['page_title'] = __("Satellite Distance Records");
        $data['distances'] = $this->distancerecords_model->get_records();

        $footerData['usethisformat'] = $usethisformat;

        $this->load->view('interface_assets/header', $data);
        $this->load->view('distancerecords/index');
        $this->load->view('interface_assets/footer', $footerData);
    }

}
