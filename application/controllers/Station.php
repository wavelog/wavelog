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

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Create Station Location");
			$this->load->view('interface_assets/header', $data);
			$this->load->view('station_profile/create');
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
			if ($this->form_validation->run() == FALSE) {
				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			} else {
				if ($this->stations->edit()) {
					$data['notice'] = __("Station Location") . $this->security->xss_clean($this->input->post('station_profile_name', true)) . " Updated";
				}
				// Also clean up static map images first
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

}
