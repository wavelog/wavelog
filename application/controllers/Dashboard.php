<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('user/login');
		}
	}

	public function index() {
		// Database connections
		$this->load->model('logbook_model');

		// LoTW infos
		$this->load->model('Lotw_model');
		$current_date = date('Y-m-d H:i:s');
		$data['lotw_cert_expired'] = $this->Lotw_model->lotw_cert_expired($this->session->userdata('user_id'), $current_date);
		$data['lotw_cert_expiring'] = $this->Lotw_model->lotw_cert_expiring($this->session->userdata('user_id'), $current_date);
		$data['lotw_cert_qsoenddate_expired'] = $this->Lotw_model->lotw_cert_qsoenddate_expired($this->session->userdata('user_id'), $current_date);
		$data['lotw_cert_qsoenddate_expiring'] = $this->Lotw_model->lotw_cert_qsoenddate_expiring($this->session->userdata('user_id'), $current_date);


		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));


		if (($logbooks_locations_array[0]>-1) && (!(in_array($this->stations->find_active(),$logbooks_locations_array)))) {
			$data['active_not_linked']=true;
		} else {
			$data['active_not_linked']=false;
		}

		if ($logbooks_locations_array[0] == -1) {
			$data['linkedCount']=0;
		} else {
			$data['linkedCount']=sizeof($logbooks_locations_array);
		}
		// Calculate Lat/Lng from Locator to use on Maps
		if ($this->session->userdata('user_locator')) {
			if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }

			$qra_position = $this->qra->qra2latlong($this->session->userdata('user_locator'));
			if ($qra_position) {
				$data['qra'] = "set";
				$data['qra_lat'] = $qra_position[0];
				$data['qra_lng'] = $qra_position[1];
			} else {
				$data['qra'] = "none";
			}
		} else {
			$data['qra'] = "none";
		}

		// We need the form_helper for the layout/messages
		$this->load->helper('form');

		$this->load->model('stations');
		$this->load->model('setup_model');

		$data['countryCount'] = $this->setup_model->getCountryCount();
		$data['logbookCount'] = $this->setup_model->getLogbookCount();
		$data['locationCount'] = $this->setup_model->getLocationCount();

		$data['current_active'] = $this->stations->find_active();

		$data['themesWithoutMode'] = $this->setup_model->checkThemesWithoutMode();
		if (($this->session->userdata('user_dashboard_map') ?? '') != '') {
			$data['dashboard_map'] = $this->session->userdata('user_dashboard_map') ?? 'Y';
		} else {
			$data['dashboard_map'] = 'N';
		}

		if (($this->session->userdata('user_dashboard_banner') ?? '') != '') {
			$data['dashboard_banner'] = $this->session->userdata('user_dashboard_banner') ?? 'Y';
		} else {
			$data['dashboard_banner'] = 'N';
		}

		// Check user preferrence to show Solar Data on Dashboard
		// Default to not show
		if (($this->session->userdata('user_dashboard_solar') ?? '') != '') {
			$data['dashboard_solar'] = $this->session->userdata('user_dashboard_solar') ?? 'N';
		} else {
			$data['dashboard_solar'] = 'N'; // Default to not show
		}

		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		$this->load->model('cat');
		$this->load->model('vucc');
		$this->load->model('dayswithqso_model');

		$data['radio_status'] = $this->cat->recent_status();

		$qso_counts = $this->logbook_model->get_qso_counts($logbooks_locations_array);
		$data['todays_qsos'] = $qso_counts['today'];
		$data['total_qsos'] = $qso_counts['total'];
		$data['month_qsos'] = $qso_counts['month'];
		$data['year_qsos'] = $qso_counts['year'];

		$rawstreak=$this->dayswithqso_model->getAlmostCurrentStreak();
		if (is_array($rawstreak)) {
			$data['current_streak']=$rawstreak['highstreak'];
		} else {
			$data['current_streak']=0;
		}

		// Load Dashboard stats (countries + QSL stats in one query)
		$stats = $this->logbook_model->dashboard_stats_batch($logbooks_locations_array);

		// Country stats
		$data['total_countries'] = $stats['Countries_Worked'];
		$data['total_countries_confirmed_paper'] = $stats['Countries_Worked_QSL'];
		$data['total_countries_confirmed_eqsl'] = $stats['Countries_Worked_EQSL'];
		$data['total_countries_confirmed_lotw'] = $stats['Countries_Worked_LOTW'];
		$current = $stats['Countries_Current'];

		// QSL stats
		$data['total_qsl_sent'] = $stats['QSL_Sent'];
		$data['total_qsl_rcvd'] = $stats['QSL_Received'];
		$data['total_qsl_requested'] = $stats['QSL_Requested'];
		$data['qsl_sent_today'] = $stats['QSL_Sent_today'];
		$data['qsl_rcvd_today'] = $stats['QSL_Received_today'];
		$data['qsl_requested_today'] = $stats['QSL_Requested_today'];

		$data['total_eqsl_sent'] = $stats['eQSL_Sent'];
		$data['total_eqsl_rcvd'] = $stats['eQSL_Received'];
		$data['eqsl_sent_today'] = $stats['eQSL_Sent_today'];
		$data['eqsl_rcvd_today'] = $stats['eQSL_Received_today'];

		$data['total_lotw_sent'] = $stats['LoTW_Sent'];
		$data['total_lotw_rcvd'] = $stats['LoTW_Received'];
		$data['lotw_sent_today'] = $stats['LoTW_Sent_today'];
		$data['lotw_rcvd_today'] = $stats['LoTW_Received_today'];

		$data['total_qrz_sent'] = $stats['QRZ_Sent'];
		$data['total_qrz_rcvd'] = $stats['QRZ_Received'];
		$data['qrz_sent_today'] = $stats['QRZ_Sent_today'];
		$data['qrz_rcvd_today'] = $stats['QRZ_Received_today'];

		$data['last_qso_count'] = empty($this->session->userdata('dashboard_last_qso_count')) ? DASHBOARD_DEFAULT_QSOS_COUNT : $this->session->userdata('dashboard_last_qso_count');
		$data['last_qsos_list'] = $this->logbook_model->get_last_qsos(
			$data['last_qso_count'],
			$logbooks_locations_array
		);

		$data['vucc'] = $this->vucc->fetchVuccSummary();
		$data['vuccSAT'] = $this->vucc->fetchVuccSummary('SAT');

		$data['page_title'] = __("Dashboard");

		$this->load->model('dxcc');
		$dxcc = $this->dxcc->list_current();

		$footerData['scripts'] = [
			'assets/js/sections/dashboard.js',
		];

		// First Login Wizard
		$fl_wiz_value = $this->session->userdata('FirstLoginWizard') ?? null;
		$show_fl_wiz = false;

		// if the value is empty, we check if the user has any station locations
		if ($fl_wiz_value === null) {
			$this->load->model('stations');
			if ($this->stations->all_of_user()->num_rows() == 0) {
				$show_fl_wiz = true;
			} else {
				$this->user_options_model->set_option('FirstLoginWizard', 'shown', ['boolean' => 1]);
				$this->session->set_userdata('FirstLoginWizard', 1);
			}
		} elseif ($fl_wiz_value == 0) {
			$show_fl_wiz = true;
		}

		$data['is_first_login'] = $show_fl_wiz;
		$data['firstloginwizard'] = '';
		if ($this->session->userdata('impersonate') == 0 &&		// Don't show to impersonated user
			$this->session->userdata('clubstation') == 0 &&		// Don't show to Clubstation
			$data['is_first_login']) {							// Don't show if already done

			$this->load->model('dxcc');
			$viewdata['dxcc_list'] = $this->dxcc->list();

			$footerData['scripts'][] = 'assets/js/bootstrap-multiselect.js';

			$this->load->library('form_validation');

			$data['firstloginwizard'] = $this->load->view('user/modals/first_login_wizard', $viewdata, true);
		}

		$data['total_countries_needed'] = count($dxcc->result()) - $current;

		// Check user preferrence to show Solar Data on Dashboard and load data if yes
		// Default to not show
		if($data['dashboard_solar'] == 'Y') {
			$this->load->model('Hamqsl_model');	// Load HAMQSL model

			if (!$this->Hamqsl_model->set_solardata()) {
				// Problem getting data, set to null
				$data['solar_bandconditions'] = null;
				$data['solar_solardata'] = null;
			} else {
				// Load data into arrays
				$data['solar_bandconditions'] = $this->Hamqsl_model->get_bandconditions_array();
				$data['solar_solardata'] = $this->Hamqsl_model->get_solarinformation_array();
			}
		}

		// Load the views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('dashboard/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function radio_display_component() {
		$this->load->model('cat');

		$data['radio_status'] = $this->cat->recent_status();
		$this->load->view('components/radio_display_table', $data);
	}
}
