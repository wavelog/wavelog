<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	
	Widgets are designed to be addons to use around the internet.
		
*/

class Widgets extends CI_Controller {

	public function index()
	{
		// Show a help page
	}
	
	
	// Can be used to embed last 11 QSOs in a iframe or javascript include.
	public function qsos($logbook_slug = null) {

		if($logbook_slug == null) {
			show_error(__("Unknown Public Page, please make sure the public slug is correct."));
		}
		$this->load->model('logbook_model');

		$this->load->model('logbooks_model');
		if($this->logbooks_model->public_slug_exists($logbook_slug)) {
			// Load the public view

			$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($logbook_slug);
			if($logbook_id != false)
			{
				// Get associated station locations for mysql queries
				$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

				if (!$logbooks_locations_array) {
					show_404(__("Empty Logbook"));
				}
			} else {
				log_message('error', $logbook_slug.' has no associated station locations');
				show_404(__("Unknown Public Page."));
			}

			$data['last_five_qsos'] = $this->logbook_model->get_last_qsos(15, $logbooks_locations_array);
			
			$this->load->view('widgets/qsos', $data);
		}
	}

	public function oqrs($user_callsign = 'CALL MISSING') {
		$this->load->model('oqrs_model');
		$stations = $this->oqrs_model->get_oqrs_stations();
	
		if ($stations->result() === NULL) {
			show_404(__("No stations found that are using Wavelog OQRS."));
			return;
		}

		$slug = $this->security->xss_clean($this->input->get('slug'));
		if ($slug != null) {
			$data['logo_url'] = base_url() . 'index.php/visitor/' . $slug;
		} else {
			$data['logo_url'] = 'https://github.com/wavelog/wavelog';
		}

		$theme = $this->security->xss_clean($this->input->get('theme'));
		if ($theme != null) {
			$data['theme'] = $theme;
		} else {
			$data['theme'] = $this->config->item('option_theme');
		}
	
		$data['user_callsign'] = strtoupper($this->security->xss_clean($user_callsign));
		$this->load->view('widgets/oqrs', $data);
	}
}