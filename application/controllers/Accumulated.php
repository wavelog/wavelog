<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accumulated extends CI_Controller
{

    function __construct() {
        parent::__construct();

        $this->load->model('user_model');
        if (!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!"));
            redirect('dashboard');
        }
    }

    public function index() {
	    // Render Page
	    $data['page_title'] = __("Accumulated Statistics");

	    $this->load->model('bands');

	    $data['worked_bands'] = $this->bands->get_worked_bands(); // Used in the view for band select

	    $this->load->model('modes');

	    if (($this->input->post('Propmode') != NULL) && ($this->input->post('Propmode') != '0')) {	// Set, but not "All"
		    $data['propmode'] = $this->security->xss_clean($this->input->post('Propmode'));
	    } else {
		    if (($this->session->userdata('user_default_band') == 'SAT') && ($this->input->post('propmode') == NULL)){	// Not set, and empty and default is SAT?
			    $data['propmode']='SAT';
		    } else {													// Not set and empty and no SAT as default?
			    $data['propmode'] = 'All';
		    }
	    }


	    $data['modes'] = $this->modes->active();

	    $this->load->view('interface_assets/header', $data);
	    $this->load->view('accumulate/index');
	    $this->load->view('interface_assets/footer');
    }

    /*
     * Used for ajax-call in javascript to fetch the data and insert into table and chart
     */
    public function get_accumulated_data() {

	    if (($this->input->post('Propmode') != NULL) && ($this->input->post('Propmode') != '0')) {	// Set, but not "All"
		    $propmode = $this->security->xss_clean($this->input->post('Propmode'));
	    } else {
		    if (($this->session->userdata('user_default_band') == 'SAT') && ($this->input->post('Propmode') == NULL)){	// Not set, and empty and default is SAT?
			    $propmode='SAT';
		    } else {													// Not set and empty and no SAT as default?
			    $propmode = 'All';
		    }
	    }

	    //load model
	    $this->load->model('accumulate_model');
	    $band = xss_clean($this->input->post('Band'));
	    $award = xss_clean($this->input->post('Award'));
	    $mode = xss_clean($this->input->post('Mode'));
	    $period = xss_clean($this->input->post('Period'));

	    // get data
	    $data = $this->accumulate_model->get_accumulated_data($band, $award, $mode, $propmode, $period);
	    header('Content-Type: application/json');
	    echo json_encode($data);
    }
}
