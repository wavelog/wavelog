<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Callstats extends CI_Controller
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

    public function index() {
	    $data['page_title'] = __("Callsign statistics");

	    $this->load->model('bands');
	    $this->load->model('Callstats_model');

	    if ($this->input->post('band') != NULL) {   // Band is not set when page first loads.
		    $band = $this->input->post('band',true);
	    } else {
		    $band = 'All';
	    }

	    if ($this->input->post('mode') != NULL) {   // Mode is not set when page first loads.
		    $mode = $this->input->post('mode',true);
	    } else {
		    $mode = 'All';
	    }

	    if ($this->input->post('propagation') != NULL) {   // Propagation is not set when page first loads.
		    $propagation = $this->input->post('propagation',true);
	    } else {
		    $propagation = '';
	    }

	    if ($this->input->post('mincount') != NULL) {   // mincount is not set when page first loads.
		    $mincount = $this->input->post('mincount',true);
	    } else {
		    $mincount = 2;
	    }

	    if ($this->input->post('orbit') != NULL) {   // orbit is not set when page first loads.
		    $orbit = $this->input->post('orbit',true);
	    } else {
		    $orbit = 'All';
	    }


	    if ($this->input->post('sat') != NULL) {   // Sat is not set when page first loads.
		    $sat = $this->input->post('sat',true);
	    } else {
		    $sat = 'All';
	    }

	    $data['sats'] = $this->bands->get_worked_sats();
	    $data['worked_bands'] = $this->bands->get_worked_bands();
	    $data['worked_modes'] = $this->Callstats_model->get_worked_modes();
       $data['orbits'] = $this->bands->get_worked_orbits();
	    $data['mincount'] = $mincount;
	    $data['maxactivatedgrids'] = $this->Callstats_model->get_max_qsos();
	    $data['orbit'] = $orbit;
	    $data['activators_array'] = $this->Callstats_model->get_activators($band, $mode, $propagation, $mincount, $orbit, $sat);
	    $data['bandselect'] = $band;
	    $data['modeselect'] = $mode;
	    $data['satselect'] = $sat;
	    $data['propagationselect'] = $propagation;
	    $data['user_default_band'] = $this->session->userdata('user_default_band');

	    $footerData = [];
	    $footerData['scripts'] = [
		    'assets/js/sections/callstats.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/callstats.js")),
	    ];

	    $this->load->view('interface_assets/header', $data);
	    $this->load->view('callstats/index');
	    $this->load->view('interface_assets/footer', $footerData);
    }

	/*
	 * Used to fetch QSOs from the table
	 */
	public function qso_details_callstats() {
		$this->load->model('callstats_model');

		$searchphrase = str_replace('"', "", $this->security->xss_clean($this->input->post("Searchphrase")));
		$band = str_replace('"', "", $this->security->xss_clean($this->input->post("Band")));
		$mode = str_replace('"', "", $this->security->xss_clean($this->input->post("Mode")));
		$sat = str_replace('"', "", $this->security->xss_clean($this->input->post("Sat")));
		$orbit = str_replace('"', "", $this->security->xss_clean($this->input->post("Orbit")));
		$propagation = str_replace('"', "", $this->security->xss_clean($this->input->post("Propagation")) ?? '');
		$data['results'] = $this->callstats_model->qso_details($searchphrase, $band, $mode, $sat, $orbit, $propagation);

		// Render Page
		$data['page_title'] = __("Log View");
		$data['filter'] = $searchphrase.__(" and band ").$band;
		if ($band == 'SAT') {
			if ($sat != 'All' && $sat != null) {
				$data['filter'] .= __(" and sat ").$sat;
			}
			if ($orbit != 'All' && $orbit != null) {
				$data['filter'] .= __(" and orbit type ").$orbit;
			}
		}
		if ($propagation != '' && $propagation != null) {
			$data['filter'] .= __(" and propagation ").$propagation;
		}
		if ($mode != null && strtolower($mode) != 'all') {
			$data['filter'] .= __(" and mode ").$mode;
		}
		if (!empty($qsltype)) {
			$data['filter'] .= __(" and ").implode('/', $qsltype);
		}
		$this->load->view('awards/details', $data);
	}

}
