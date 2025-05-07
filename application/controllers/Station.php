<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Station extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	public function create()
	{
		$this->load->model('stations');
		$this->load->model('dxcc');
		$data['dxcc_list'] = $this->dxcc->list();

		$this->load->model('logbook_model');
		$data['iota_list'] = $this->logbook_model->fetchIota();

		$this->load->library('form_validation');

		$this->form_validation->set_rules('station_profile_name', 'Station Profile Name', 'required');
		$this->form_validation->set_rules('dxcc', 'DXCC', 'required');
		$this->form_validation->set_rules('gridsquare', 'Locator', 'callback_check_locator');

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Create Station Location");
			$data['station_profile_name'] = $this->input->post('station_profile_name');
			$data['station_callsign'] = $this->input->post('station_callsign');
			$data['station_power'] = $this->input->post('station_power');
			$data['dxcc'] = $this->input->post('dxcc');
			$data['city'] = $this->input->post('city');
			$data['station_state'] = $this->input->post('station_state');
			$data['station_cnty'] = $this->input->post('station_cnty');
			$data['station_cq'] = $this->input->post('station_cq');
			$data['station_itu'] = $this->input->post('station_itu');
			$data['gridsquare'] = $this->input->post('gridsquare');
			$data['iota'] = $this->input->post('iota');
			$data['sota'] = $this->input->post('sota');
			$data['wwff'] = $this->input->post('wwff');
			$data['pota'] = $this->input->post('pota');
			$data['sig'] = $this->input->post('sig');
			$data['sig_info'] = $this->input->post('sig_info');
			$data['eqslnickname'] = $this->input->post('eqslnickname');
			$data['eqsl_default_qslmsg'] = $this->input->post('eqsl_default_qslmsg');
			$data['clublogignore'] = $this->input->post('clublogignore');
			$data['clublogrealtime'] = $this->input->post('clublogrealtime');
			$data['hrdlog_username'] = $this->input->post('hrdlog_username');
			$data['hrdlog_code'] = $this->input->post('hrdlog_code');
			$data['hrdlogrealtime'] = $this->input->post('hrdlogrealtime');
			$data['qrzapikey'] = $this->input->post('qrzapikey');
			$data['qrzrealtime'] = $this->input->post('qrzrealtime');
			$data['webadifapikey'] = $this->input->post('webadifapikey');
			$data['webadifrealtime'] = $this->input->post('webadifrealtime');
			$data['oqrs'] = $this->input->post('oqrs');
			$data['oqrsemail'] = $this->input->post('oqrsemail');
			$data['oqrstext'] = $this->input->post('oqrstext');
			$this->load->view('interface_assets/header', $data);
			$this->load->view('station_profile/create', $data);
			$this->load->view('interface_assets/footer');
		} else {
			$this->stations->add();
			redirect('stationsetup');
		}
	}

	public function edit($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = __("Edit Station Location: ") . $data['my_station_profile']->station_profile_name;

			$this->form_validation->set_rules('dxcc', 'DXCC', 'required');
			$this->form_validation->set_rules('gridsquare', 'Locator', 'callback_check_locator');
			if ($this->form_validation->run() == FALSE) {
				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			} else {
				if ($this->stations->edit()) {
					$data['notice'] = __("Station Location") . $this->security->xss_clean($this->input->post('station_profile_name', true)) . " Updated";
				}
				// Also clean up static map images
				if (!$this->load->is_loaded('staticmap_model')) {
					$this->load->model('staticmap_model');
				}
				$this->staticmap_model->remove_static_map_image($id);
				redirect('stationsetup');
			}
		} else {
			redirect('stationsetup');
		}
	}

	public function copy($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = __("Duplicate Station Location:"). "{$data['my_station_profile']->station_profile_name}";

			// we NULLify station_id and station_profile_name to make sure we are creating a new station
			$data['copy_from'] = $data['my_station_profile']->station_id;
			$data['my_station_profile']->station_id = NULL;
			$data['my_station_profile']->station_profile_name = '';
			$this->form_validation->set_rules('gridsquare', 'Locator', 'callback_check_locator');

			if ($this->form_validation->run() == FALSE) {
				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			} else {
				$this->stations->add();

				redirect('stationsetup');
			}
		} else {
			redirect('stationsetup');
		}
	}

	public function edit_favourite($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		$this->stations->edit_favourite($id);

		redirect('stationsetup');
	}

	function load_station_for_editing($id): array {
		$id = $this->security->xss_clean($id);
		$this->load->library('form_validation');

		$this->load->model('stations');
		$this->load->model('dxcc');
		$this->load->model('logbook_model');

		$data['iota_list'] = $this->logbook_model->fetchIota();

		$item_id_clean = $this->security->xss_clean($id);

		$data['my_station_profile'] = $this->stations->profile_full($item_id_clean);

		$data['dxcc_list'] = $this->dxcc->list();

		$this->form_validation->set_rules('station_profile_name', 'Station Profile Name', 'required');

		return $data;
	}


	function reassign_profile($id) {
		$id = $this->security->xss_clean($id);
		// $id is the profile that needs reassigned to QSOs // ONLY Admin can do that!
		$this->load->model('stations');
		if ($this->user_model->authorize(99)) {
			$this->stations->reassign($id);
		}

		//$this->stations->logbook_session_data();
		redirect('stationsetup');
	}

	function set_active($current, $new, $is_ajax = null) {
		$current = $this->security->xss_clean($current);
		$new = $this->security->xss_clean($new);
		$this->load->model('stations');
		$this->stations->set_active($current, $new);

		if ($is_ajax != null) {
			return;
		}

		redirect('stationsetup');
	}

	public function delete($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$this->stations->delete($id);
		}
		redirect('stationsetup');
	}

	public function deletelog($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$this->stations->deletelog($id);
		}
		redirect('stationsetup');
	}

	public function stationProfileCoords($id) {
		$id = $this->security->xss_clean($id);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$coords = $this->stations->lookupProfileCoords($id);
			print json_encode($coords);
		}
	}

	function check_locator($grid = '') {
		$this->load->library('Qra');
		if ($this->qra->validate_grid($grid)) {
			return true;
		} else {
			$this->form_validation->set_message('check_locator', sprintf(__("Please check value for grid locator (%s)"), strtoupper($grid)));
			return false;
		}
	}

}
