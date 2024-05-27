<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Visitor extends CI_Controller {

	function __construct()
	{
		parent::__construct();
	}

    function _remap($method) {
        if($method == "config") {
            $this->$method();
        }
		elseif($method == "map") {
            $this->map($method);
        }
        elseif($method == "satellites") {
            $this->satellites($method);
        }
        elseif($method == "search") {
            $this->search($method);
        }
		elseif($method == "exportmap") {
            $this->exportmap();
        }
		elseif($method == "mapqsos") {
            $this->mapqsos();
        }
		elseif($method == "get_map_custom") {
            $this->get_map_custom();
        }
        else {
            $this->index($method);
        }
    }

	/*
        This is the default function that is called when the user visits the root of the public controller
    */
	public function index($public_slug = NULL)
	{

        // Check slug passed and is valid
        if ($this->security->xss_clean($public_slug, TRUE) === FALSE) {

            // Public Slug failed the XSS test
            log_message('error', '[Visitor] XSS Attack detected on public_slug '. $public_slug);
            show_404('Unknown Public Page.');

        } else {

            // Checked slug passed and clean
            log_message('info', '[Visitor] public_slug '. $public_slug .' loaded');

            // Load necessary models
            $this->load->model('dxcc');
			$this->load->model('cat');
            $this->load->model('logbook_model');
			$this->load->model('logbooks_model');
			
            if($this->logbooks_model->public_slug_exists($public_slug)) {

                // Load the public view
				$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($public_slug);
				
                if($logbook_id != false) {
					
                    // Get associated station locations for mysql queries
                    $logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

					if (!$logbooks_locations_array) {
						show_404('Empty Logbook');
					}

                } else {
                    log_message('error', $public_slug.' has no associated station locations');
                    show_404('Unknown Public Page.');
                }

                // Public visitor so no QRA to setup
                $data['qra'] = "none";

				// Pagination Configuration
				$this->load->library('pagination');
				$config['base_url'] = base_url().'index.php/visitor/'.$public_slug;
				$config['total_rows'] = $this->logbook_model->total_qsos();
				$config['per_page'] = '25';
				$config['num_links'] = 6;
				$config['full_tag_open'] = '';
				$config['full_tag_close'] = '';
				$config['cur_tag_open'] = '<strong class="active"><a href="">';
				$config['cur_tag_close'] = '</a></strong>';

				$this->pagination->initialize($config);

                // Store info
                $data['todays_qsos'] = $this->logbook_model->todays_qsos($logbooks_locations_array);
                $data['total_qsos'] = $this->logbook_model->total_qsos($logbooks_locations_array);
                $data['month_qsos'] = $this->logbook_model->month_qsos($logbooks_locations_array);
                $data['year_qsos'] = $this->logbook_model->year_qsos($logbooks_locations_array);

                // Load  Countries Breakdown data into array
                $CountriesBreakdown = $this->logbook_model->total_countries_confirmed($logbooks_locations_array);

                $data['total_countries'] = $CountriesBreakdown['Countries_Worked'];
                $data['total_countries_confirmed_paper'] = $CountriesBreakdown['Countries_Worked_QSL'];
                $data['total_countries_confirmed_eqsl'] = $CountriesBreakdown['Countries_Worked_EQSL'];
                $data['total_countries_confirmed_lotw'] = $CountriesBreakdown['Countries_Worked_LOTW'];

				$dxcc = $this->dxcc->list_current();
                $current = $this->logbook_model->total_countries_current($logbooks_locations_array);
                $data['total_countries_needed'] = count($dxcc->result()) - $current;

                $QSLStatsBreakdownArray =$this->logbook_model->get_QSLStats($logbooks_locations_array);

                $data['total_qsl_sent'] = $QSLStatsBreakdownArray['QSL_Sent'];
                $data['total_qsl_rcvd'] = $QSLStatsBreakdownArray['QSL_Received'];
                $data['total_qsl_requested'] = $QSLStatsBreakdownArray['QSL_Requested'];

                $data['total_eqsl_sent'] = $QSLStatsBreakdownArray['eQSL_Sent'];
                $data['total_eqsl_rcvd'] = $QSLStatsBreakdownArray['eQSL_Received'];

                $data['total_lotw_sent'] = $QSLStatsBreakdownArray['LoTW_Sent'];
                $data['total_lotw_rcvd'] = $QSLStatsBreakdownArray['LoTW_Received'];

                $data['results'] = $this->logbook_model->get_qsos($config['per_page'],$this->uri->segment(3),$logbooks_locations_array);

                $data['page_title'] = "Dashboard";
                $data['slug'] = $public_slug;

                $this->load->view('visitor/layout/header', $data);
                $this->load->view('visitor/index');
                $this->load->view('visitor/layout/footer');

            } else {
                // Show 404
                log_message('error', '[Visitor] XSS Attack detected on public_slug '. $public_slug);
                show_404('Unknown Public Page.');
            }

        }
	}

    public function map() {
		$this->load->model('logbook_model');

		$this->load->library('qra');

        $slug = $this->security->xss_clean($this->uri->segment(3));

        $this->load->model('logbooks_model');
        $logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($slug);
        if($logbook_id != false)
        {
            // Get associated station locations for mysql queries
            $logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

			if (!$logbooks_locations_array) {
				show_404('Empty Logbook');
			}
        } else {
            log_message('error', $slug.' has no associated station locations');
            show_404('Unknown Public Page.');
        }

		$qsos = $this->logbook_model->get_qsos('18', null, $logbooks_locations_array);
		// [PLOT] ADD plot //
		$plot_array = $this->logbook_model->get_plot_array_for_map($qsos->result());

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($plot_array);
	}

    public function satellites()
	{

        $slug = $this->security->xss_clean($this->uri->segment(3));
        $data['slug'] = $slug;
        $this->load->model('logbooks_model');
        if($this->logbooks_model->public_slug_exists($slug)) {
            // Load the public view
			$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($slug);
			if($logbook_id != false)
			{
				// Get associated station locations for mysql queries
				$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

				if (!$logbooks_locations_array) {
					show_404('Empty Logbook');
				}
			} else {
				log_message('error', $slug.' has no associated station locations');
				show_404('Unknown Public Page.');
			}
        }

		$this->load->model('gridmap_model');

		$data['page_title'] = "Satellite Gridsquare Map";


		$array_grid_2char = array();
		$array_grid_4char = array();
		$array_grid_6char = array();


		$array_confirmed_grid_2char = array();
		$array_confirmed_grid_4char = array();
		$array_confirmed_grid_6char = array();

		$grid_2char = "";
		$grid_4char = "";
		$grid_6char = "";

		$grid_2char_confirmed = "";
		$grid_4char_confirmed = "";
		$grid_6char_confirmed = "";


		// Get Confirmed LoTW & Paper Squares (non VUCC)
		$query = $this->gridmap_model->get_band_confirmed('SAT', 'All', 'false', 'true', 'false', 'false', 'All', 'All', $logbooks_locations_array);


		if ($query && $query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{

				$grid_2char_confirmed = strtoupper(substr($row->GRID_SQUARES,0,2));
				$grid_4char_confirmed = strtoupper(substr($row->GRID_SQUARES,0,4));
				if ($this->config->item('map_6digit_grids')) {
					$grid_6char_confirmed = strtoupper(substr($row->GRID_SQUARES,0,6));
				}

				// Check if 2 Char is in array
				if(!in_array($grid_2char_confirmed, $array_confirmed_grid_2char)){
					array_push($array_confirmed_grid_2char, $grid_2char_confirmed);
				}


				if(!in_array($grid_4char_confirmed, $array_confirmed_grid_4char)){
					array_push($array_confirmed_grid_4char, $grid_4char_confirmed);
				}


				if ($this->config->item('map_6digit_grids')) {
					if(!in_array($grid_6char_confirmed, $array_confirmed_grid_6char)){
						array_push($array_confirmed_grid_6char, $grid_6char_confirmed);
					}
				}


			}
		}

		// Get worked squares
		$query = $this->gridmap_model->get_band('SAT', 'All', 'false', 'true', 'false', 'false', 'All', 'All', $logbooks_locations_array);

		if ($query && $query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{

				$grid_two = strtoupper(substr($row->GRID_SQUARES,0,2));
				$grid_four = strtoupper(substr($row->GRID_SQUARES,0,4));
				if ($this->config->item('map_6digit_grids')) {
					$grid_six = strtoupper(substr($row->GRID_SQUARES,0,6));
				}

				// Check if 2 Char is in array
				if(!in_array($grid_two, $array_grid_2char)){
					array_push($array_grid_2char, $grid_two);
				}


				if(!in_array($grid_four, $array_grid_4char)){
					array_push($array_grid_4char, $grid_four);
				}


				if ($this->config->item('map_6digit_grids')) {
					if(!in_array($grid_six, $array_grid_6char)){
						array_push($array_grid_6char, $grid_six);
					}
				}


			}
		}

		$query_vucc = $this->gridmap_model->get_band_worked_vucc_squares('SAT', 'All', 'false', 'true', 'false', 'false', 'All', 'All', $logbooks_locations_array);

		if ($query && $query_vucc->num_rows() > 0)
		{
			foreach ($query_vucc->result() as $row)
			{

				$grids = explode(",", $row->COL_VUCC_GRIDS);

				foreach($grids as $key) {
					$grid_two = strtoupper(substr($key,0,2));
					$grid_four = strtoupper(substr($key,0,4));

					// Check if 2 Char is in array
					if(!in_array($grid_two, $array_grid_2char)){
						array_push($array_grid_2char, $grid_two);
					}


					if(!in_array($grid_four, $array_grid_4char)){
						array_push($array_grid_4char, $grid_four);
					}
				}
			}
		}

		// Confirmed Squares
		$query_vucc = $this->gridmap_model->get_band_confirmed_vucc_squares('SAT', 'All', 'false', 'true', 'false', 'false', 'All', 'All', $logbooks_locations_array);

		if ($query && $query_vucc->num_rows() > 0)
		{
			foreach ($query_vucc->result() as $row)
			{

				$grids = explode(",", $row->COL_VUCC_GRIDS);

				foreach($grids as $key) {
					$grid_2char_confirmed = strtoupper(substr($key,0,2));
					$grid_4char_confirmed = strtoupper(substr($key,0,4));

					// Check if 2 Char is in array
					if(!in_array($grid_2char_confirmed, $array_confirmed_grid_2char)){
						array_push($array_confirmed_grid_2char, $grid_2char_confirmed);
					}


					if(!in_array($grid_4char_confirmed, $array_confirmed_grid_4char)){
						array_push($array_confirmed_grid_4char, $grid_4char_confirmed);
					}
				}
			}
		}


		function js_str($s)
		{
		    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
		}

		function js_array($array)
		{
		    $temp = array_map('js_str', $array);
		    return '[' . implode(',', $temp) . ']';
		}


		$data['grid_2char_confirmed'] = js_array($array_confirmed_grid_2char);
		$data['grid_4char_confirmed'] = js_array($array_confirmed_grid_4char);
		$data['grid_6char_confirmed'] = js_array($array_confirmed_grid_6char);

		$data['grid_2char'] = js_array($array_grid_2char);
		$data['grid_4char'] = js_array($array_grid_4char);
		$data['grid_6char'] = js_array($array_grid_6char);

		$data['layer'] = $this->optionslib->get_option('option_map_tile_server');
		$data['attribution'] = $this->optionslib->get_option('option_map_tile_server_copyright');

		$data['gridsquares_gridsquares'] = lang('gridsquares_gridsquares');
		$data['gridsquares_gridsquares_confirmed'] = lang('gridsquares_gridsquares_confirmed');
		$data['gridsquares_gridsquares_not_confirmed'] = lang('gridsquares_gridsquares_not_confirmed');
		$data['gridsquares_gridsquares_total_worked'] = lang('gridsquares_gridsquares_total_worked');

		$data['visitor'] = true;

		$this->load->view('visitor/layout/header', $data);
		$this->load->view('gridmap/index', $data);
		$this->load->view('visitor/layout/footer');
	}

	public function oqrs_enabled($slug) {
		$this->load->model('oqrs_model');
		$this->load->model('Logbooks_model');
		$logbook_id = $this->Logbooks_model->public_slug_exists_logbook_id($slug);
		if (!empty($this->oqrs_model->getOqrsStationsFromSlug($logbook_id))) {
			return true;
		} else {
			return false;
		}
	}

	public function public_search_enabled($slug) {
		$this->load->model('Logbooks_model');
		$logbook_id = $this->Logbooks_model->public_slug_exists_logbook_id($slug);
		if ($this->Logbooks_model->public_search_enabled($logbook_id)  == 1) {
			return true;
		} else {
			return false;
		}
	}

	public function search() {
		$callsign = trim($this->security->xss_clean($this->input->post('callsign')));
		$public_slug = $this->security->xss_clean($this->input->post('public_slug'));
		$this->load->model('publicsearch');
		$data['page_title'] = "Public Search";
		$data['callsign'] = $callsign;
		$data['slug'] = $public_slug;
		if ($callsign != '') {
			$result = $this->publicsearch->search($public_slug, $callsign);
		}
		if (!empty($result) && $result->num_rows() > 0) {
			$data['results'] = $result;
			$this->load->view('visitor/layout/header', $data);
			$this->load->view('public_search/result.php', $data);
			$this->load->view('visitor/layout/footer');
		} else {
			$this->load->view('visitor/layout/header', $data);
			$this->load->view('public_search/empty.php', $data);
			$this->load->view('visitor/layout/footer');
		}
	}

	public function exportmap() {
		$slug = $this->security->xss_clean($this->uri->segment(3));
		$lastqso = $this->security->xss_clean($this->uri->segment(4));

		if ($lastqso === "lastqso") {
			$this->load->model('visitor_model');
			$result = $this->visitor_model->getlastqsodate($slug)->row();
			header('Content-Type: application/json');
			echo json_encode($result);
			return;
		}

        $data['slug'] = $slug;

		$data['page_title'] = "Export Map";
		$this->load->view('visitor/exportmap/header', $data);
		$this->load->view('visitor/exportmap/exportmap', $data);
		$this->load->view('visitor/exportmap/footer');
	}

	public function mapqsos() {
		$this->load->model('visitor_model');

		$this->load->library('qra');

        $slug = $this->security->xss_clean($this->input->post('slug'));
		$qsocount = $this->security->xss_clean($this->input->post('qsocount')) == '' ? '100' : $this->security->xss_clean($this->input->post('qsocount'));
		$band = $this->security->xss_clean($this->input->post('band'));

		$this->load->model('stationsetup_model');
        $logbook_id = $this->stationsetup_model->public_slug_exists_logbook_id($slug);
        if ($logbook_id != false)
        {
            // Get associated station locations for mysql queries
            $logbooks_locations_array = $this->stationsetup_model->get_container_relations($logbook_id);

			if (!$logbooks_locations_array) {
				show_404('Empty Logbook');
			}
        } else {
            log_message('error', $slug.' has no associated station locations');
            show_404('Unknown Public Page.');
        }

		$qsos = $this->visitor_model->get_qsos($qsocount, $logbooks_locations_array, $band);
		$userid = $this->stationsetup_model->public_slug_exists_userid($slug);
		$user_default_confirmation = $this->get_user_default_confirmation($userid);

		$mappedcoordinates = array();
		foreach ($qsos->result('array') as $qso) {
			if (!empty($qso['COL_MY_GRIDSQUARE']) || !empty($qso['COL_MY_VUCC_GRIDS'])) {
				if (!empty($qso['COL_GRIDSQUARE'])  || !empty($qso['COL_VUCC_GRIDS'])) {
					$mappedcoordinates[] = $this->calculate($qso, ($qso['COL_MY_GRIDSQUARE'] ?? '') == '' ? $qso['COL_MY_VUCC_GRIDS'] : $qso['COL_MY_GRIDSQUARE'], ($qso['COL_GRIDSQUARE'] ?? '') == '' ? $qso['COL_VUCC_GRIDS'] : $qso['COL_GRIDSQUARE'], $user_default_confirmation);
				} else {
					if (!empty($qso['lat'])  || !empty($qso['long'])) {
						$mappedcoordinates[] = $this->calculateCoordinates($qso, $qso['lat'], $qso['long'], ($qso['COL_MY_GRIDSQUARE'] ?? '') == '' ? $qso['COL_MY_VUCC_GRIDS'] : $qso['COL_MY_GRIDSQUARE'], $user_default_confirmation);
					}
				}
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($mappedcoordinates);
	}

	public function calculate($qso, $locator1, $locator2, $user_default_confirmation) {
		$this->load->library('Qra');
		$this->load->model('logbook_model');

		$latlng1 = $this->qra->qra2latlong($locator1);
		$latlng2 = $this->qra->qra2latlong($locator2);
		$latlng1[0] = number_format((float)$latlng1[0], 3, '.', '');;
		$latlng1[1] = number_format((float)$latlng1[1], 3, '.', '');;
		$latlng2[0] = number_format((float)$latlng2[0], 3, '.', '');;
		$latlng2[1] = number_format((float)$latlng2[1], 3, '.', '');;

		$data['latlng1'] = $latlng1;
		$data['latlng2'] = $latlng2;
		$data['confirmed'] = ($this->qso_is_confirmed($qso, $user_default_confirmation)==true) ? true : false;

		return $data;
	}

	public function calculateCoordinates($qso, $lat, $long, $mygrid, $user_default_confirmation) {
		$this->load->library('Qra');
		$this->load->model('logbook_model');

		$latlng1 = $this->qra->qra2latlong($mygrid);
		$latlng2[0] = $lat;
		$latlng2[1] = $long;
		$latlng1[0] = number_format((float)$latlng1[0], 3, '.', '');;
		$latlng1[1] = number_format((float)$latlng1[1], 3, '.', '');;
		$latlng2[0] = number_format((float)$latlng2[0], 3, '.', '');;
		$latlng2[1] = number_format((float)$latlng2[1], 3, '.', '');;

		$data['latlng1'] = $latlng1;
		$data['latlng2'] = $latlng2;
		$data['confirmed'] = ($this->qso_is_confirmed($qso, $user_default_confirmation)==true) ? true : false;

		return $data;
	}

	// [MAP Custom] //
	public function get_map_custom() {
		$this->load->model('stationsetup_model');
		$slug = $this->security->xss_clean($this->input->post('slug'));
		$userid = $this->stationsetup_model->public_slug_exists_userid($slug);

		$this->load->model('user_options_model');

		$result = $this->user_options_model->get_options('map_custom', null, $userid);
		$jsonout = [];
		foreach($result->result() as $options) {
			if ($options->option_name=='icon') $jsonout[$options->option_key]=json_decode($options->option_value,true);
				else $jsonout[$options->option_name.'_'.$options->option_key]=$options->option_value;
		}

		if (count($jsonout) == 0) {
			$jsonout['qso'] = array(
				"icon" => "fas fa-dot-circle",
				"color" => "#ff0000"
			);
			$jsonout['qsoconfirm'] = array(
				"icon" => "fas fa-dot-circle",
				"color" => "#00aa00"
			);
			$jsonout['station'] = array(
				"icon" => "fas fa-broadcast-tower",
				"color" => "#0000ff"
			);
		}

		$jsonout['gridsquare_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'gridsquare_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
		$jsonout['path_lines'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'path_lines','option_key'=>$slug), $userid)->row()->option_value ?? true;
		$jsonout['cqzone_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'cqzone_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
		$jsonout['qsocount'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'qsocount','option_key'=>$slug), $userid)->row()->option_value ?? 250;
		$jsonout['nightshadow_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'nightshadow_layer','option_key'=>$slug), $userid)->row()->option_value ?? true;
		$jsonout['band'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'band','option_key'=>$slug), $userid)->row()->option_value ?? '';

		header('Content-Type: application/json');
		echo json_encode($jsonout);
	}

	function qso_is_confirmed($qso, $user_default_confirmation) {
		$confirmed = false;
		$qso = (array) $qso;
		if (strpos($user_default_confirmation, 'Q') !== false) { // QSL
			if ($qso['COL_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'L') !== false) { // LoTW
			if ($qso['COL_LOTW_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'E') !== false) { // eQsl
			if ($qso['COL_EQSL_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'Z') !== false) { // QRZ
			if ($qso['COL_QRZCOM_QSO_DOWNLOAD_STATUS']=='Y') { $confirmed = true; }
		}
		return $confirmed;
	}

	function get_user_default_confirmation($userid) {
		$this->db->where('user_id', $userid);
		$query = $this->db->get('users');

		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				return $row->user_default_confirmation;
			}
		} else {
			return '';
		}
	}
}