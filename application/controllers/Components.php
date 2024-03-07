<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the Clublog API
*/

class Components extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }
	}

    public function index() {
        $this->load->model('stations');
        $url = 'https://hams.at/api/alerts/upcoming';
        $hamsat_key = '';
        if ($this->session->userdata('user_hamsat_key') != '') {
           $hamsat_key = $this->session->userdata('user_hamsat_key');
           $options = array(
              'http' => array(
                 'method' => 'GET',
                 'header' => "Authorization: Bearer ".$hamsat_key."\r\n"
              )
           );
           $context = stream_context_create($options);
           $json = file_get_contents($url, false, $context);
        } else {
           $json = file_get_contents($url);
        }
        $data['rovedata'] = json_decode($json, true);
        $data['workable_only'] = $this->session->userdata('user_hamsat_workable_only');
        $data['gridsquare'] = strtoupper($this->stations->find_gridsquare());

        // load view
        $this->load->view('components/hamsat/table', $data);
    }
}
