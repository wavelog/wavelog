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
			$this->session->set_flashdata('notice', 'You\'re not allowed to do that!');
			redirect('dashboard');
		}
	}

	public function index()
	{
		$this->load->model('stations');
		$this->load->model('Logbook_model');
		$this->load->model('user_model');

		$data['is_admin'] = ($this->user_model->authorize(99));

		$data['stations'] = $this->stations->all_with_count();
		$data['current_active'] = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		// Render Page
		$data['page_title'] = lang('station_location');
		$this->load->view('interface_assets/header', $data);
		$this->load->view('station_profile/index');
		$this->load->view('interface_assets/footer');
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

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = lang('station_location_create_header');
			$this->load->view('interface_assets/header', $data);
			$this->load->view('station_profile/create');
			$this->load->view('interface_assets/footer');
		} else {
			$this->stations->add();
			redirect('stationsetup');
		}
	}

	public function edit($id)
	{
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = lang('station_location_edit') . $data['my_station_profile']->station_profile_name;

			if ($this->form_validation->run() == FALSE) {
				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			} else {
				if ($this->stations->edit()) {
					$data['notice'] = lang('station_location') . $this->security->xss_clean($this->input->post('station_profile_name', true)) . " Updated";
				}
				redirect('stationsetup');
			}
		} else {
			redirect('stationsetup');
		}
	}

	public function copy($id)
	{
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = "Duplicate Station Location: {$data['my_station_profile']->station_profile_name}";

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

	public function edit_favourite($id)
	{
		$this->load->model('stations');
		$this->stations->edit_favourite($id);

		redirect('stationsetup');
	}

	function load_station_for_editing($id): array
	{
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


	function reassign_profile($id)
	{
		// $id is the profile that needs reassigned to QSOs // ONLY Admin can do that!
		$this->load->model('stations');
		if ($this->user_model->authorize(99)) {
			$this->stations->reassign($id);
		}

		//$this->stations->logbook_session_data();
		redirect('stationsetup');
	}

	function set_active($current, $new, $is_ajax = null)
	{
		$this->load->model('stations');
		$this->stations->set_active($current, $new);

		if ($is_ajax != null) {
			return;
		}

		redirect('stationsetup');
	}

	public function delete($id)
	{
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$this->stations->delete($id);
		}
		redirect('stationsetup');
	}

	public function deletelog($id)
	{
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$this->stations->deletelog($id);
		}
		redirect('stationsetup');
	}

	/*
	 * Function is used for autocompletion of Counties in the station profile form
	 */
	public function get_county()
	{
		$json = [];

		if (!empty($this->input->get("query"))) {
			$query = isset($_GET['query']) ? $_GET['query'] : FALSE;
			$county = $this->input->get("state");

			$file = 'assets/json/US_counties.csv';

			if (is_readable($file)) {
				$lines = file($file, FILE_IGNORE_NEW_LINES);
				$input = preg_quote($county, '~');
				$reg = '~^' . $input . '(.*)$~';
				$result = preg_grep($reg, $lines);
				$json = [];
				$i = 0;
				foreach ($result as &$value) {
					$county = explode(',', $value);
					// Limit to 300 as to not slowdown browser too much
					if (count($json) <= 300) {
						$json[] = ["name" => $county[1]];
					}
				}
			}
		}

		header('Content-Type: application/json');
		echo json_encode($json);
	}

}
