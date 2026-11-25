<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Version_Dialog extends CI_Controller {

	/**
	 * Version_Dialog constructor.
	 * Loads required models and ensures the requester is authenticated
	 * before any version information is served.
	 */
	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize($this->config->item('auth_mode'))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	public function displayVersionDialog() {
		$this->load->view('version_dialog/index');
	}
}
?>