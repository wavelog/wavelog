<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {

	public function index()
	{
		$this->load->model('user_model');
		$this->load->library('form_validation');

		if (!$this->load->is_loaded('encryption')) {
			$this->load->library('encryption');
		}

		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['results'] = $this->user_model->users();
		$data['clubs'] = $this->user_model->users('is_club');
		$data['clubmode'] = $this->config->item('special_callsign');
		$data['session_uid'] = $this->session->userdata('user_id');

		// Check if impersonating is disabled in the config
		if ($this->config->item('disable_impersonate')) {
			$data['disable_impersonate'] = true;
		} else {
			$data['disable_impersonate'] = false;
		}

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$data['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$data['custom_date_format'] = $this->config->item('qso_date_format');
		}

		$data['has_flossie'] = ($this->config->item('encryption_key') == 'flossie1234555541') ? true : false;

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/user.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/user.js")),
		];

		$data['page_title'] = __("User Accounts");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('user/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function actions_modal() {

		$this->load->model('user_model');
		$this->load->library('encryption');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['user_id'] = $this->input->post('user_id', true) ?? '';
		$modal = $this->input->post('modal', true) ?? '';

		if($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		if ($this->user_model->exists_by_id($data['user_id']) && $modal != '') {
			$user = $this->user_model->get_by_id($data['user_id'])->row();
			$gettext = new Gettext;

			$data['user_name'] = $user->user_name;
			$data['user_callsign'] = $user->user_callsign;
			$data['user_email'] = $user->user_email;
			$data['user_firstname'] = $user->user_firstname;
			$data['user_lastname'] = $user->user_lastname;
			$data['user_language'] = $gettext->find_by('folder', $user->user_language)['name_en'];
			$data['is_clubstation'] = $user->clubstation == 1 ? true : false;
			$data['last_seen'] = $user->last_seen;
			$data['custom_date_format'] = $custom_date_format;
			$data['has_flossie'] = ($this->config->item('encryption_key') == 'flossie1234555541') ? true : false;

			$this->load->view('user/modals/'.$modal.'_modal', $data);
		} else {
			$this->session->set_flashdata('error', __("Invalid User ID or missing modal!"));
			redirect('user');
		}
	}

	public function convert() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$user_id = $this->input->post('user_id', true) ?? '';
		$convert_to = $this->input->post('convert_to', true) ?? '';

		if ($convert_to != '0' && $convert_to != '1') {
			$this->session->set_flashdata('error', __("Invalid Parameter!"));
			redirect('dashboard');
		}

		if ($this->user_model->exists_by_id($user_id)) {
			if ($this->user_model->convert($user_id, $convert_to)) {
				echo json_encode(true);
			} else {
				echo json_encode(false);
			}
		} else {
			log_message('error', 'User Conversion - User ID not found: '.$user_id);
			echo json_encode(false);
		}
	}

	function add() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['existing_languages'] = $this->config->item('languages');

		$this->load->model('bands');
		$this->load->library('form_validation');
		$this->load->library('Genfunctions');

		$this->form_validation->set_rules('user_name', 'Username', 'required');
		$this->form_validation->set_rules('user_email', 'E-mail', 'required');
		$this->form_validation->set_rules('user_password', 'Password', 'required');
		$this->form_validation->set_rules('user_type', 'Type', 'required');
		// $this->form_validation->set_rules('user_firstname', 'First name', 'required');
		// $this->form_validation->set_rules('user_lastname', 'Last name', 'required');
		$this->form_validation->set_rules('user_callsign', 'Callsign', 'required');
		$this->form_validation->set_rules('user_locator', 'Locator', 'required');
		$this->form_validation->set_rules('user_locator', 'Locator', 'callback_check_locator');
		$this->form_validation->set_rules('user_timezone', 'Timezone', 'required');

		$data['user_add'] = true;
		$data['user_form_action'] = site_url('user/add');
		$data['bands'] = $this->bands->get_user_bands();

		$data['clubstation'] = ($this->input->get('club') ?? '') == '1' ? true : false;

		// Get themes list
		$data['themes'] = $this->user_model->getThemes();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/user.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/user.js")),
		];

		// Get timezones
		$data['timezones'] = $this->user_model->timezones();
		$data['user_language'] = 'english';

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Add User");
			$data['measurement_base'] = $this->config->item('measurement_base');

			$this->load->view('interface_assets/header', $data);
			if($this->input->post('user_name')) {
				$data['user_name'] = $this->input->post('user_name');
				$data['user_email'] = $this->input->post('user_email');
				$data['user_password'] = $this->input->post('user_password');
				$data['user_type'] = $data['clubstation'] == true ? '3' : $this->input->post('user_type');
				$data['user_firstname'] = $this->input->post('user_firstname') ?? '';
				$data['user_lastname'] = $this->input->post('user_lastname') ?? '';
				$data['user_callsign'] = $this->input->post('user_callsign');
				$data['user_locator'] = $this->input->post('user_locator');
				$data['user_timezone'] = $this->input->post('user_timezone');
				$data['user_measurement_base'] = $this->input->post('user_measurement_base');
				$data['user_stylesheet'] = $this->input->post('user_stylesheet');
				$data['user_qth_lookup'] = $this->input->post('user_qth_lookup');
				$data['user_sota_lookup'] = $this->input->post('user_sota_lookup');
				$data['user_wwff_lookup'] = $this->input->post('user_wwff_lookup');
				$data['user_pota_lookup'] = $this->input->post('user_pota_lookup');
				$data['user_show_notes'] = $this->input->post('user_show_notes');
				$data['user_column1'] = $this->input->post('user_column1');
				$data['user_column2'] = $this->input->post('user_column2');
				$data['user_column3'] = $this->input->post('user_column3');
				$data['user_column4'] = $this->input->post('user_column4');
				$data['user_column5'] = $this->input->post('user_column5');
				$data['user_show_profile_image'] = $this->input->post('user_show_profile_image');
				$data['user_previous_qsl_type'] = $this->input->post('user_previous_qsl_type');
				$data['user_amsat_status_upload'] = $this->input->post('user_amsat_status_upload');
				$data['user_mastodon_url'] = $this->input->post('user_mastodon_url');
				$data['user_default_band'] = $this->input->post('user_default_band');
				$data['user_default_confirmation'] = ($this->input->post('user_default_confirmation_qsl') !== null ? 'Q' : '').($this->input->post('user_default_confirmation_lotw') !== null ? 'L' : '').($this->input->post('user_default_confirmation_eqsl') !== null ? 'E' : '').($this->input->post('user_default_confirmation_qrz') !== null ? 'Z' : '').($this->input->post('user_default_confirmation_clublog') !== null ? 'C' : '');
				$data['user_qso_end_times'] = $this->input->post('user_qso_end_times');
				$data['user_quicklog'] = $this->input->post('user_quicklog');
				$data['user_quicklog_enter'] = $this->input->post('user_quicklog_enter');
				$data['user_hamsat_key'] = $this->input->post('user_hamsat_key');
				$data['user_hamsat_workable_only'] = $this->input->post('user_hamsat_workable_only');
				$data['user_iota_to_qso_tab'] = $this->input->post('user_iota_to_qso_tab');
				$data['user_sota_to_qso_tab'] = $this->input->post('user_sota_to_qso_tab');
				$data['user_wwff_to_qso_tab'] = $this->input->post('user_wwff_to_qso_tab');
				$data['user_pota_to_qso_tab'] = $this->input->post('user_pota_to_qso_tab');
				$data['user_sig_to_qso_tab'] = $this->input->post('user_sig_to_qso_tab');
				$data['user_dok_to_qso_tab'] = $this->input->post('user_dok_to_qso_tab');
				$data['user_language'] = $this->input->post('user_language');
				$this->load->view('user/edit', $data);
			} else {
				$this->load->view('user/edit', $data);
			}
			$this->load->view('interface_assets/footer', $footerData);
		} else {
			switch($this->user_model->add($this->input->post('user_name'),
				$this->input->post('user_password'),
				$this->input->post('user_email'),
				$this->input->post('user_type'),
				$this->input->post('user_firstname') ?? '',
				$this->input->post('user_lastname') ?? '',
				$this->input->post('user_callsign'),
				$this->input->post('user_locator'),
				$this->input->post('user_timezone'),
				$this->input->post('user_measurement_base'),
				$this->input->post('user_date_format'),
				$this->input->post('user_stylesheet'),
				$this->input->post('user_qth_lookup'),
				$this->input->post('user_sota_lookup'),
				$this->input->post('user_wwff_lookup'),
				$this->input->post('user_pota_lookup'),
				$this->input->post('user_show_notes'),
				$this->input->post('user_column1'),
				$this->input->post('user_column2'),
				$this->input->post('user_column3'),
				$this->input->post('user_column4'),
				$this->input->post('user_column5'),
				$this->input->post('user_show_profile_image'),
				$this->input->post('user_previous_qsl_type'),
				$this->input->post('user_amsat_status_upload'),
				$this->input->post('user_mastodon_url'),
				$this->input->post('user_default_band'),
				($this->input->post('user_default_confirmation_qsl') !== null ? 'Q' : '').($this->input->post('user_default_confirmation_lotw') !== null ? 'L' : '').($this->input->post('user_default_confirmation_eqsl') !== null ? 'E' : '').($this->input->post('user_default_confirmation_qrz') !== null ? 'Z' : '').($this->input->post('user_default_confirmation_clublog') !== null ? 'C' : ''),
				$this->input->post('user_qso_end_times'),
				$this->input->post('user_quicklog'),
				$this->input->post('user_quicklog_enter'),
				$this->input->post('user_language'),
				$this->input->post('user_hamsat_key'),
				$this->input->post('user_hamsat_workable_only'),
				$this->input->post('user_iota_to_qso_tab'),
				$this->input->post('user_sota_to_qso_tab'),
				$this->input->post('user_wwff_to_qso_tab'),
				$this->input->post('user_pota_to_qso_tab'),
				$this->input->post('user_sig_to_qso_tab'),
				$this->input->post('user_dok_to_qso_tab'),
				$this->input->post('user_lotw_name'),
				$this->input->post('user_lotw_password'),
				$this->input->post('user_eqsl_name'),
				$this->input->post('user_eqsl_password'),
				$this->input->post('user_clublog_name'),
				$this->input->post('user_clublog_password'),
				$this->input->post('user_winkey'),
				$this->input->post('clubstation') == '1' ? true : false
				)) {
				// Check for errors
				case EUSERNAMEEXISTS:
					$data['username_error'] = sprintf(__("Username %s already in use!"), '<b>' . $this->input->post('user_name') . '</b>');
					break;
				case EEMAILEXISTS:
					$data['email_error'] = sprintf(__("E-mail %s already in use!"), '<b>' . $this->input->post('user_email') . '</b>');
					break;
				case EPASSWORDINVALID:
					$data['password_error'] = __("Invalid Password!");
					break;
				// All okay, return to user screen
				case OK:
					$this->session->set_flashdata('notice', sprintf(__("User %s added!"), '<b>' . $this->input->post('user_name') . '</b>'));
					redirect('user');
					return;
			}
			$data['page_title'] = __("Users");

			$this->load->view('interface_assets/header', $data);
			$data['user_name'] = $this->input->post('user_name');
			$data['user_email'] = $this->input->post('user_email');
			$data['user_password'] = $this->input->post('user_password');
			$data['user_type'] = $this->input->post('user_type');
			$data['user_firstname'] = $this->input->post('user_firstname') ?? '';
			$data['user_lastname'] = $this->input->post('user_lastname') ?? '';
			$data['user_callsign'] = $this->input->post('user_callsign');
			$data['user_locator'] = $this->input->post('user_locator');
			$data['user_measurement_base'] = $this->input->post('user_measurement_base');
			$data['user_stylesheet'] = $this->input->post('user_stylesheet');
			$data['user_qth_lookup'] = $this->input->post('user_qth_lookup');
			$data['user_sota_lookup'] = $this->input->post('user_sota_lookup');
			$data['user_wwff_lookup'] = $this->input->post('user_wwff_lookup');
			$data['user_pota_lookup'] = $this->input->post('user_pota_lookup');
			$data['user_show_notes'] = $this->input->post('user_show_notes');
			$data['user_column1'] = $this->input->post('user_column1');
			$data['user_column2'] = $this->input->post('user_column2');
			$data['user_column3'] = $this->input->post('user_column3');
			$data['user_column4'] = $this->input->post('user_column4');
			$data['user_column5'] = $this->input->post('user_column5');
			$data['user_show_profile_image'] = $this->input->post('user_show_profile_image');
			$data['user_previous_qsl_type'] = $this->input->post('user_previous_qsl_type');
			$data['user_amsat_status_upload'] = $this->input->post('user_amsat_status_upload');
			$data['user_mastodon_url'] = $this->input->post('user_mastodon_url');
			$data['user_default_band'] = $this->input->post('user_default_band');
			$data['user_default_confirmation'] = ($this->input->post('user_default_confirmation_qsl') !== null ? 'Q' : '').($this->input->post('user_default_confirmation_lotw') !== null ? 'L' : '').($this->input->post('user_default_confirmation_eqsl') !== null ? 'E' : '').($this->input->post('user_default_confirmation_qrz') !== null ? 'Z' : '').($this->input->post('user_default_confirmation_clublog') !== null ? 'C' : '');
			$data['user_qso_end_times'] = $this->input->post('user_qso_end_times');
			$data['user_quicklog'] = $this->input->post('user_quicklog');
			$data['user_quicklog_enter'] = $this->input->post('user_quicklog_enter');
			$data['user_language'] = $this->input->post('user_language');
			$this->load->view('user/edit', $data);
			$this->load->view('interface_assets/footer', $footerData);
		}
	}

	function edit() {
		$this->load->model('user_model');
		if ( ($this->session->userdata('user_id') == '') || ((!$this->user_model->authorize(99)) && ($this->session->userdata('user_id') != $this->uri->segment(3))) ) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		if ( $this->config->item('special_callsign') && $this->session->userdata('user_type') != '99' && $this->config->item('sc_hide_usermenu') ) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$query = $this->user_model->get_by_id($this->uri->segment(3));

		$data['existing_languages'] = $this->config->item('languages');
		$pwd_placeholder = '**********';

		$this->load->model('bands');
		$this->load->library('form_validation');
		$this->load->library('Genfunctions');

		$this->form_validation->set_rules('user_name', 'Username', 'required|xss_clean');
		$this->form_validation->set_rules('user_email', 'E-mail', 'required|xss_clean');
		if($this->session->userdata('user_type') == 99)
		{
			$this->form_validation->set_rules('user_type', 'Type', 'required|xss_clean');
		}
		$this->form_validation->set_rules('user_firstname', 'First name', 'required|xss_clean');
		$this->form_validation->set_rules('user_lastname', 'Last name', 'required|xss_clean');
		$this->form_validation->set_rules('user_callsign', 'Callsign', 'trim|required|xss_clean');
		$this->form_validation->set_rules('user_locator', 'Locator', 'callback_check_locator');
		$this->form_validation->set_rules('user_timezone', 'Timezone', 'required');

		$data['user_form_action'] = site_url('user/edit')."/".$this->uri->segment(3);
		$data['clubstation'] = ($query->row()->clubstation == 1) ? true : false;
		$data['bands'] = $this->bands->get_user_bands();

		// Get themes list
		$data['themes'] = $this->user_model->getThemes();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/user.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/user.js")),
		];

		// Get timezones
		$data['timezones'] = $this->user_model->timezones();

		if ($this->form_validation->run() == FALSE)
		{
			$data['page_title'] = __("Edit User");

			$q = $query->row();

			$data['id'] = $q->user_id;

			if($this->input->post('user_name', true)) {
				$data['user_name'] = $this->input->post('user_name', true);
			} else {
				$data['user_name'] = $q->user_name;
			}

			if($this->input->post('user_email', true)) {
				$data['user_email'] = $this->input->post('user_email', true);
			} else {
				$data['user_email'] = $q->user_email;
			}

			if($this->input->post('user_password', true)) {
				$data['user_password'] = $this->input->post('user_password',true);
			} else {
				if ($q->user_password !== '' && $q->user_password !== null) {
					$data['user_password'] = $pwd_placeholder;
				} else {
					$data['user_password'] = '';
				}
			}

			if($this->input->post('user_type', true)) {
				$data['user_type'] = $this->input->post('user_type',true);
			} else {
				$data['user_type'] = $q->user_type;
			}

			if($this->input->post('user_callsign', true)) {
				$data['user_callsign'] = $this->input->post('user_callsign', true);
			} else {
				$data['user_callsign'] = $q->user_callsign;
			}

			if($this->input->post('user_locator', true)) {
				$data['user_locator'] = $this->input->post('user_locator', true);
			} else {
				$data['user_locator'] = $q->user_locator;
			}

			if($this->input->post('user_firstname', true)) {
				$data['user_firstname'] = $this->input->post('user_firstname', true);
			} else {
				$data['user_firstname'] = $q->user_firstname;
			}

			if($this->input->post('user_lastname', true)) {
				$data['user_lastname'] = $this->input->post('user_lastname', true);
			} else {
				$data['user_lastname'] = $q->user_lastname;
			}

			if($this->input->post('user_callsign', true)) {
				$data['user_callsign'] = $this->input->post('user_callsign', true);
			} else {
				$data['user_callsign'] = $q->user_callsign;
			}

			if($this->input->post('user_locator', true)) {
				$data['user_locator'] = $this->input->post('user_locator', true);
			} else {
				$data['user_locator'] = $q->user_locator;
			}

			if($this->input->post('user_timezone')) {
				$data['user_timezone'] = $this->input->post('user_timezone', true);
			} else {
				$data['user_timezone'] = $q->user_timezone;
			}

			if($this->input->post('user_lotw_name')) {
				$data['user_lotw_name'] = $this->input->post('user_lotw_name', true);
			} else {
				$data['user_lotw_name'] = $q->user_lotw_name;
			}

			if($this->input->post('user_clublog_name')) {
				$data['user_clublog_name'] = $this->input->post('user_clublog_name', true);
			} else {
				$data['user_clublog_name'] = $q->user_clublog_name;
			}

			if($this->input->post('user_clublog_password')) {
				$data['user_clublog_password'] = $this->input->post('user_clublog_password', true);
			} else {
				if ($q->user_clublog_password !== '' && $q->user_clublog_password !== null) {
					$data['user_clublog_password'] = $pwd_placeholder;
				} else {
					$data['user_clublog_password'] = '';
				}
			}

			if($this->input->post('user_lotw_password')) {
				$data['user_lotw_password'] = $this->input->post('user_lotw_password', true);
			} else {
				if ($q->user_lotw_password !== '' && $q->user_lotw_password !== null) {
					$data['user_lotw_password'] = $pwd_placeholder;
				} else {
					$data['user_lotw_password'] = '';
				}
			}

			if($this->input->post('user_eqsl_name')) {
				$data['user_eqsl_name'] = $this->input->post('user_eqsl_name', true);
			} else {
				$data['user_eqsl_name'] = $q->user_eqsl_name;
			}

			if($this->input->post('user_eqsl_password')) {
				$data['user_eqsl_password'] = $this->input->post('user_eqsl_password', true);
			} else {
				if ($q->user_eqsl_password !== '' && $q->user_eqsl_password !== null) {
					$data['user_eqsl_password'] = $pwd_placeholder;
				} else {
					$data['user_eqsl_password'] = '';
				}
			}

			if($this->input->post('user_measurement_base')) {
				$data['user_measurement_base'] = $this->input->post('user_measurement_base', true);
			} else {
				$data['user_measurement_base'] = $q->user_measurement_base;
			}

			if($this->input->post('user_date_format')) {
				$data['user_date_format'] = $this->input->post('user_date_format', true);
			} else {
				$data['user_date_format'] = $q->user_date_format;
			}

			if($this->input->post('user_language')) {
				$data['user_language'] = $this->input->post('user_language', true);
			} else {
				$data['user_language'] = $q->user_language;
			}


			if($this->input->post('user_stylesheet')) {
				$data['user_stylesheet'] = $this->input->post('user_stylesheet', true);
			} else {
				$data['user_stylesheet'] = $q->user_stylesheet;
			}

			if($this->input->post('user_qth_lookup')) {
				$data['user_qth_lookup'] = $this->input->post('user_qth_lookup', true);
			} else {
				$data['user_qth_lookup'] = $q->user_qth_lookup;
			}

			if($this->input->post('user_sota_lookup')) {
				$data['user_sota_lookup'] = $this->input->post('user_sota_lookup', true);
			} else {
				$data['user_sota_lookup'] = $q->user_sota_lookup;
			}

			if($this->input->post('user_wwff_lookup')) {
				$data['user_wwff_lookup'] = $this->input->post('user_wwff_lookup', true);
			} else {
				$data['user_wwff_lookup'] = $q->user_wwff_lookup;
			}

			if($this->input->post('user_pota_lookup')) {
				$data['user_pota_lookup'] = $this->input->post('user_pota_lookup', true);
			} else {
				$data['user_pota_lookup'] = $q->user_pota_lookup;
			}

			if($this->input->post('user_show_notes')) {
				$data['user_show_notes'] = $this->input->post('user_show_notes', true);
			} else {
				$data['user_show_notes'] = $q->user_show_notes;
			}

			if($this->input->post('user_qso_end_times')) {
				$data['user_qso_end_times'] = $this->input->post('user_qso_end_times', true);
			} else {
				$data['user_qso_end_times'] = $q->user_qso_end_times;
			}

			if($this->input->post('user_quicklog')) {
				$data['user_quicklog'] = $this->input->post('user_quicklog', true);
			} else {
				$data['user_quicklog'] = $q->user_quicklog;
			}

			if($this->input->post('user_quicklog_enter')) {
				$data['user_quicklog_enter'] = $this->input->post('user_quicklog_enter', true);
			} else {
				$data['user_quicklog_enter'] = $q->user_quicklog_enter;
			}

			if($this->input->post('user_show_profile_image')) {
				$data['user_show_profile_image'] = $this->input->post('user_show_profile_image', false);
			} else {
				$data['user_show_profile_image'] = $q->user_show_profile_image;
			}

			if($this->input->post('user_previous_qsl_type')) {
				$data['user_previous_qsl_type'] = $this->input->post('user_previous_qsl_type', false);
			} else {
				$data['user_previous_qsl_type'] = $q->user_previous_qsl_type;
			}

			if($this->input->post('user_amsat_status_upload')) {
				$data['user_amsat_status_upload'] = $this->input->post('user_amsat_status_upload', false);
			} else {
				$data['user_amsat_status_upload'] = $q->user_amsat_status_upload;
			}

			if($this->input->post('user_mastodon_url')) {
				$data['user_mastodon_url'] = $this->input->post('user_mastodon_url', false);
			} else {
				$data['user_mastodon_url'] = $q->user_mastodon_url;
			}

			if($this->input->post('user_default_band')) {
				$data['user_default_band'] = $this->input->post('user_default_band', false);
			} else {
				$data['user_default_band'] = $q->user_default_band;
			}

			if($this->input->post('user_default_confirmation')) {
			   $data['user_default_confirmation'] = ($this->input->post('user_default_confirmation_qsl') !== null ? 'Q' : '').($this->input->post('user_default_confirmation_lotw') !== null ? 'L' : '').($this->input->post('user_default_confirmation_eqsl') !== null ? 'E' : '').($this->input->post('user_default_confirmation_qrz') !== null ? 'Z' : '').($this->input->post('user_default_confirmation_clublog') !== null ? 'C' : '');
			} else {
				$data['user_default_confirmation'] = $q->user_default_confirmation;
			}

			if($this->input->post('user_column1')) {
				$data['user_column1'] = $this->input->post('user_column1', true);
			} else {
				$data['user_column1'] = $q->user_column1;
			}

			if($this->input->post('user_column2')) {
				$data['user_column2'] = $this->input->post('user_column2', true);
			} else {
				$data['user_column2'] = $q->user_column2;
			}

			if($this->input->post('user_column3')) {
				$data['user_column3'] = $this->input->post('user_column3', true);
			} else {
				$data['user_column3'] = $q->user_column3;
			}

			if($this->input->post('user_column4')) {
				$data['user_column4'] = $this->input->post('user_column4', true);
			} else {
				$data['user_column4'] = $q->user_column4;
			}

			if($this->input->post('user_column5')) {
				$data['user_column5'] = $this->input->post('user_column5', true);
			} else {
				$data['user_column5'] = $q->user_column5;
			}

			if($this->input->post('user_winkey')) {
				$data['user_winkey'] = $this->input->post('user_winkey', true);
			} else {
				$data['user_winkey'] = $q->winkey;
			}

			if($this->input->post('user_hamsat_key', true)) {
				$data['user_hamsat_key'] = $this->input->post('user_hamsat_key', true);
			} else {
				$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'api'), $this->uri->segment(3))->result();
				if (count($hkey_opt)>0) {
					$data['user_hamsat_key'] = $hkey_opt[0]->option_value;
				} else {
					$data['user_hamsat_key'] = '';
				}
			}

			if($this->input->post('user_hamsat_workable_only')) {
				$data['user_hamsat_workable_only'] = $this->input->post('user_hamsat_workable_only', false);
			} else {
				$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'workable'), $this->uri->segment(3))->result();
				if (count($hkey_opt)>0) {
					$data['user_hamsat_workable_only'] = $hkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_iota_to_qso_tab')) {
				$data['user_iota_to_qso_tab'] = $this->input->post('user_iota_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'iota','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_iota_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_sota_to_qso_tab')) {
				$data['user_sota_to_qso_tab'] = $this->input->post('user_sota_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'sota','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_sota_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_wwff_to_qso_tab')) {
				$data['user_wwff_to_qso_tab'] = $this->input->post('user_wwff_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'wwff','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_wwff_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_pota_to_qso_tab')) {
				$data['user_pota_to_qso_tab'] = $this->input->post('user_pota_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'pota','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_pota_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_sig_to_qso_tab')) {
				$data['user_sig_to_qso_tab'] = $this->input->post('user_sig_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'sig','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_sig_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			if($this->input->post('user_dok_to_qso_tab')) {
				$data['user_dok_to_qso_tab'] = $this->input->post('user_dok_to_qso_tab', false);
			} else {
				$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'dok','option_key'=>'show'), $this->uri->segment(3))->result();
				if (count($qkey_opt)>0) {
					$data['user_dok_to_qso_tab'] = $qkey_opt[0]->option_value;
				}
			}

			// [MAP Custom] GET user options //
			$options_object = $this->user_options_model->get_options('map_custom')->result();
			if (count($options_object)>0) {
				foreach ($options_object as $row) {
					if ($row->option_name=='icon') {
						$option_value = json_decode($row->option_value,true);
						foreach ($option_value as $ktype => $vtype) {
							if($this->input->post('user_map_'.$row->option_key.'_icon')) {
								$data['user_map_'.$row->option_key.'_'.$ktype] = $this->input->post('user_map_'.$row->option_key.'_'.$ktype, true);
							} else {
								$data['user_map_'.$row->option_key.'_'.$ktype] = $vtype;
							}
						}
					} else {
						$data['user_map_'.$row->option_name.'_'.$row->option_key] = $row->option_value;
					}
				}
			} else {
				$data['user_map_qso_icon'] = "fas fa-dot-circle";
				$data['user_map_qso_color'] = "#FF0000";
				$data['user_map_station_icon'] = "0";
				$data['user_map_station_color'] = "#0000FF";
				$data['user_map_qsoconfirm_icon'] = "0";
				$data['user_map_qsoconfirm_color'] = "#00AA00";
				$data['user_map_gridsquare_show'] = "0";
			}
			$data['map_icon_select'] = array(
				'station'=>array('0', 'fas fa-home', 'fas fa-broadcast-tower', 'fas fa-user', 'fas fa-dot-circle' ),
				'qso'=>array('fas fa-broadcast-tower', 'fas fa-user', 'fas fa-dot-circle' ),
				'qsoconfirm'=>array('0', 'fas fa-broadcast-tower', 'fas fa-user', 'fas fa-dot-circle', 'fas fa-check-circle' ));

			$data['user_locations_quickswitch'] = ($this->user_options_model->get_options('header_menu', array('option_name'=>'locations_quickswitch'), $this->uri->segment(3))->row()->option_value ?? 'false');
			$data['user_utc_headermenu'] = ($this->user_options_model->get_options('header_menu', array('option_name'=>'utc_headermenu'), $this->uri->segment(3))->row()->option_value ?? 'false');

			$this->load->view('interface_assets/header', $data);
			$this->load->view('user/edit', $data);
			$this->load->view('interface_assets/footer', $footerData);
		} else {
			unset($data);
			switch($this->user_model->edit($this->input->post())) {
				// Check for errors
				case EUSERNAMEEXISTS:
					$data['username_error'] = 'Username <b>'.$this->input->post('user_name', true).'</b> already in use!';
					break;
				case EEMAILEXISTS:
					$data['email_error'] = 'E-mail address <b>'.$this->input->post('user_email', true).'</b> already in use!';
					break;
				case EPASSWORDINVALID:
					$data['password_error'] = 'Invalid password!';
					break;
				// All okay, return to user screen
				case OK:
					if ($this->session->userdata('user_id') == $this->uri->segment(3)) { // Editing own User? Set cookie!
						$cookie= array(

							'name'   => $this->config->item('gettext_cookie', 'gettext'),
							'value'  => $this->input->post('user_language', true),
							'expire' => 1000,
							'secure' => FALSE

						);
						$this->input->set_cookie($cookie);
					}
					if($this->session->userdata('user_id') == $this->input->post('id', true)) {
						// [MAP Custom] ADD to user options //
						$array_icon = array('station','qso','qsoconfirm');
						foreach ($array_icon as $icon) {
							$data_options['user_map_'.$icon.'_icon'] = xss_clean($this->input->post('user_map_'.$icon.'_icon', true));
							$data_options['user_map_'.$icon.'_color'] = xss_clean($this->input->post('user_map_'.$icon.'_color', true));
						}
						if (!empty($data_options['user_map_qso_icon'])) {
							foreach ($array_icon as $icon) {
								$json = json_encode(array('icon'=>$data_options['user_map_'.$icon.'_icon'], 'color'=>$data_options['user_map_'.$icon.'_color']));
								$this->user_options_model->set_option('map_custom','icon',array($icon=>$json));
							}
							$this->user_options_model->set_option('map_custom','gridsquare',array('show'=>xss_clean($this->input->post('user_map_gridsquare_show', true))));
						} else {
							$this->user_options_model->del_option('map_custom','icon');
							$this->user_options_model->del_option('map_custom','gridsquare');
						}
						$this->user_options_model->set_option('header_menu', 'locations_quickswitch', array('boolean'=>xss_clean($this->input->post('user_locations_quickswitch', true))));
						$this->user_options_model->set_option('header_menu', 'utc_headermenu', array('boolean'=>xss_clean($this->input->post('user_utc_headermenu', true))));
						$this->session->set_flashdata('success', sprintf(__("User %s edited"), $this->input->post('user_name', true)));
						redirect('user/edit/'.$this->uri->segment(3));
					} else {
						$this->session->set_flashdata('success', sprintf(__("User %s edited"), $this->input->post('user_name', true)));
						redirect('user');
					}
					return;
			}
			$data['page_title'] = __("Edit User");

			$this->load->view('interface_assets/header', $data);
			$data['user_name'] = $this->input->post('user_name', true);
			$data['user_email'] = $this->input->post('user_email', true);
			$data['user_password'] = $this->input->post('user_password', true);
			$data['user_type'] = $this->input->post('user_type', true);
			$data['user_firstname'] = $this->input->post('user_firstname', true);
			$data['user_lastname'] = $this->input->post('user_lastname', true);
			$data['user_callsign'] = $this->input->post('user_callsign', true);
			$data['user_locator'] = $this->input->post('user_locator', true);
			$data['user_timezone'] = $this->input->post('user_timezone', true);
			$data['user_stylesheet'] = $this->input->post('user_stylesheet');
			$data['user_qth_lookup'] = $this->input->post('user_qth_lookup');
			$data['user_sota_lookup'] = $this->input->post('user_sota_lookup');
			$data['user_wwff_lookup'] = $this->input->post('user_wwff_lookup');
			$data['user_pota_lookup'] = $this->input->post('user_pota_lookup');
			$data['user_show_notes'] = $this->input->post('user_show_notes');
			$data['user_column1'] = $this->input->post('user_column1');
			$data['user_column2'] = $this->input->post('user_column2');
			$data['user_column3'] = $this->input->post('user_column3');
			$data['user_column4'] = $this->input->post('user_column4');
			$data['user_column4'] = $this->input->post('user_column4');
			$data['user_column5'] = $this->input->post('user_column5');
			$data['user_show_profile_image'] = $this->input->post('user_show_profile_image');
			$data['user_previous_qsl_type'] = $this->input->post('user_previous_qsl_type');
			$data['user_amsat_status_upload'] = $this->input->post('user_amsat_status_upload');
			$data['user_mastodon_url'] = $this->input->post('user_mastodon_url');
			$data['user_default_band'] = $this->input->post('user_default_band');
			$data['user_default_confirmation'] = ($this->input->post('user_default_confirmation_qsl') !== null ? 'Q' : '').($this->input->post('user_default_confirmation_lotw') !== null ? 'L' : '').($this->input->post('user_default_confirmation_eqsl') !== null ? 'E' : '').($this->input->post('user_default_confirmation_qrz') !== null ? 'Z' : '').($this->input->post('user_default_confirmation_clublog') !== null ? 'C' : '');
			$data['user_qso_end_times'] = $this->input->post('user_qso_end_times');
			$data['user_quicklog'] = $this->input->post('user_quicklog');
			$data['user_quicklog_enter'] = $this->input->post('user_quicklog_enter');
			$data['user_locations_quickswitch'] = $this->input->post('user_locations_quickswitch', true);
			$data['user_utc_headermenu'] = $this->input->post('user_utc_headermenu', true);
			$data['user_language'] = $this->input->post('user_language');
			$data['user_winkey'] = $this->input->post('user_winkey');
			$data['user_hamsat_key'] = $this->input->post('user_hamsat_key');
			$data['user_hamsat_workable_only'] = $this->input->post('user_hamsat_workable_only');
			$this->load->view('user/edit');
			$this->load->view('interface_assets/footer');
		}
	}

	function profile() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
		$q = $query->row();
		$data['page_title'] = __("Profile");
		$data['user_name'] = $q->user_name;
		$data['user_type'] = $q->user_type;
		$data['user_email'] = $q->user_email;
		$data['user_firstname'] = $q->user_firstname;
		$data['user_lastname'] = $q->user_lastname;
		$data['user_callsign'] = $q->user_callsign;
		$data['user_locator'] = $q->user_locator;

		$this->load->view('interface_assets/header', $data);
		$this->load->view('user/profile');
		$this->load->view('interface_assets/footer');
	}

	function delete() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$query = $this->user_model->get_by_id($this->uri->segment(3));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', 'user_id', 'required');

		$data = $query->row();
		$data->page_title = "Delete User";

		if ($this->form_validation->run() == FALSE)
		{

			$this->load->view('interface_assets/header', $data);
			$this->load->view('user/delete');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			if($this->user_model->delete($data->user_id))
			{
				$this->session->set_flashdata('notice', 'User deleted');
				redirect('user');
			} else {
				$this->session->set_flashdata('notice', '<b>Database error:</b> Could not delete user!');
				redirect('user');
			}
		}
	}

	function login($firstlogin = false) {

		// Due the fact there was a new session generated, we need to get flash messages from a temporary cookie
		$tmpdata = json_decode($this->input->cookie(config_item('cookie_prefix') . 'tmp_msg') ?? '') ?? false;
		if ($tmpdata) {
			$this->session->set_flashdata($tmpdata[0], $tmpdata[1]);
			$this->input->set_cookie('tmp_msg', '', -3600, '');
		}

		// Check our version and run any migrations
		if (!$this->load->is_loaded('Migration')) {
			$this->load->library('Migration');
		}
		if (!$this->load->is_loaded('Encryption')) {
			$this->load->library('Encryption');
		}
		$this->migration->current();

		if($firstlogin == true) {
			$this->session->set_flashdata('success', __("Congrats! Wavelog was successfully installed. You can now login for the first time."));
		}

		$this->load->model('user_model');
		$query = $this->user_model->get($this->input->post('user_name', true));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('user_name', 'Username', 'required');
		$this->form_validation->set_rules('user_password', 'Password', 'required');

		$data['user'] = $query->row();

		// Read the cookie keep_login and allow the login
		if ($this->input->cookie(config_item('cookie_prefix') . 'keep_login') || $this->input->cookie(config_item('cookie_prefix') . 're_login')) {

			try {

				if ($this->config->item('encryption_key') == 'flossie1234555541') {
					throw new Exception("Encryption Key is still default. Change config['encryption_key'] to another value then flossie...");
				}

				// process the incoming string
				$incoming_string = $this->input->cookie(config_item('cookie_prefix') . 'keep_login') ?? $this->input->cookie(config_item('cookie_prefix') . 're_login');
				$i_str_parts_a = explode(base64_encode($this->config->item('base_url')), $incoming_string);
				$uid = base64_decode($i_str_parts_a[1]);
				$a = $i_str_parts_a[0];

				// process the string to compare with
				$compare_string = $this->user_model->keep_cookie_hash($uid);
				$i_str_parts_b = explode(base64_encode($this->config->item('base_url')), $compare_string);
				$b = $i_str_parts_b[0];

				$user = $this->user_model->get_by_id($uid)->row();
				$user_type = $user->user_type;

				// direct login to clubstations are not allowed, especially not with a keeplogin cookie
				if ($user->clubstation == 1) {
					log_message('debug', "User ID: [$uid] Login rejected because of a external clubstation login attempt with a modified cookie. Attack?");
					$this->session->set_flashdata('error', __("This is not allowed!"));
					redirect('user/login');
				}

				// compare both strings the hard way and log in if they match
				if ($this->user_model->check_keep_hash($a, $b)) {

					// check if maintenance mode is active or the user is an admin
					if (ENVIRONMENT != 'maintenance' || $user_type == 99) {

						// if everything is fine we can log in the user
						$this->user_model->update_session($uid);
						$this->user_model->set_last_seen($uid);
						log_message('info', "User ID: [$uid] logged in successfully with 'Keep Login'.");
						$this->input->set_cookie('re_login', '', -3600, ''); // delete re_login cookie in case this was a re-login from a clubstation or impersonated user
						redirect('dashboard');

					} else {

						// user not allowed to log in
						log_message('debug', "User ID: [$uid] Login rejected because of an active maintenance mode (and he is no admin).");

						// Delete keep_login cookie
						$this->input->set_cookie('keep_login', '', -3600, '');
						$this->input->set_cookie('re_login', '', -3600, '');

						redirect('user/login');
					}
				} else {
					// user not allowed to log in
					log_message('debug', "User ID: [$uid] Login rejected because of non matching hash key ('Keep Login').");

					// Delete keep_login cookie
					$this->input->set_cookie('keep_login', '', -3600, '');
					$this->input->set_cookie('re_login', '', -3600, '');
					$this->session->set_flashdata('error', __("Login failed. Try again."));
					redirect('user/login');
				}
			} catch (Exception $e) {
				// Something went wrong with the cookie
				log_message('error', "User ID: [".$uid."]; 'Keep Login' failed. Cookie deleted. Message: ".$e);

				// Delete keep_login cookie
				$this->input->set_cookie('keep_login', '', -3600, '');
				$this->input->set_cookie('re_login', '', -3600, '');

				$this->session->set_flashdata('error', __("Login failed. Try again."));
				redirect('user/login');
			}

		}

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Login");
			$data['https_check'] = $this->https_check();
			$this->load->view('interface_assets/mini_header', $data);
			$this->load->view('user/login');
			$this->load->view('interface_assets/footer');

		} else {
			$login_attempt = $this->user_model->login();
			if($login_attempt === 1) {
				$this->user_model->update_session($data['user']->user_id);
				$cookie= array(

					'name'   => $this->config->item('gettext_cookie', 'gettext'),
					'value'  => $data['user']->user_language,
					'expire' => 1000,
					'secure' => FALSE

				);
				$this->input->set_cookie($cookie);

				// Create a keep_login cookie
				if ($this->input->post('keep_login') == '1') {

					$encrypted_string = $this->user_model->keep_cookie_hash($data['user']->user_id);

					$cookie = array(
						'name'   => 'keep_login',
						'value'  => $encrypted_string,
						'expire' => 2592000,  // 30 days
						'secure' => TRUE,
						'httponly' => TRUE
					);
					$this->input->set_cookie($cookie);
				}
				$this->user_model->set_last_seen($data['user']->user_id);
				redirect('dashboard');
			
			} else if ($login_attempt === 2) {
				$this->session->set_flashdata('warning', __("You can't login to a clubstation directly. Use your personal account instead."));
				redirect('user/login');
			} else {
				if(ENVIRONMENT == 'maintenance') {
					$this->session->set_flashdata('notice', __("Sorry. This instance is currently in maintenance mode. If this message appears unexpectedly or keeps showing up, please contact an administrator. Only administrators are currently allowed to log in."));
					redirect('user/login');
				} else {
					$this->session->set_flashdata('error', __("Incorrect username or password!"));
					redirect('user/login');
				}
			}
		}
	}

	function logout($custom_message = null, $hard_logout = true) {
		$this->load->model('user_model');

		$user_name = $this->session->userdata('user_name');

		// Delete keep_login cookie
		if ($hard_logout) {
			$this->input->set_cookie('re_login', '', -3600, '');
			$this->input->set_cookie('keep_login', '', -3600, '');
		}

		$this->user_model->clear_session();
		
		if ($custom_message != null && is_array($custom_message)) {
			$this->input->set_cookie('tmp_msg', json_encode([$custom_message[0], $custom_message[1]]), 10, '');
		} else {
			$this->input->set_cookie('tmp_msg', json_encode(['notice', sprintf(__("User %s logged out."), $user_name)]), 10, '');
		}
		
		redirect('user/login');
	}

	/**
	 * Function: forgot_password
	 *
	 * Allows users to input an email address and a password will be sent to that address.
	 *
	 */
	function forgot_password() {

		if (file_exists('.demo')) {

			$this->session->set_flashdata('error', __("Password Reset is disabled on the Demo!"));
			redirect('user/login');

		} else {

			$this->load->helper(array('form', 'url'));

			$this->load->library('form_validation');

			$this->form_validation->set_rules('email', 'Email', 'required');

			if ($this->form_validation->run() == FALSE)
			{
				$data['page_title'] = __("Forgot Password");
				$this->load->view('interface_assets/mini_header', $data);
				$this->load->view('user/forgot_password');
				$this->load->view('interface_assets/footer');
			}
			else
			{
				// Check email address exists
				$this->load->model('user_model');
				$email = $this->input->post('email', TRUE);

				$check_email = $this->user_model->check_email_address($email);

				if($check_email == TRUE) {
					// Generate password reset code 50 characters long
					$this->load->helper('string');
					$reset_code = random_string('alnum', 50);

					$this->user_model->set_password_reset_code($email, $reset_code);

					// Send email with reset code

					$this->data['reset_code'] = $reset_code;
					$this->load->library('email');

					if($this->optionslib->get_option('emailProtocol') == "smtp") {
						$config = Array(
							'protocol' => $this->optionslib->get_option('emailProtocol'),
							'smtp_crypto' => $this->optionslib->get_option('smtpEncryption'),
							'smtp_host' => $this->optionslib->get_option('smtpHost'),
							'smtp_port' => $this->optionslib->get_option('smtpPort'),
							'smtp_user' => $this->optionslib->get_option('smtpUsername'),
							'smtp_pass' => $this->optionslib->get_option('smtpPassword'),
							'crlf' => "\r\n",
							'newline' => "\r\n"
						);

						$this->email->initialize($config);
					}

					$message = $this->email->load('email/forgot_password', $this->data, $this->user_model->get_by_email($email)->row()->user_language);

					$this->email->from($this->optionslib->get_option('emailAddress'), $this->optionslib->get_option('emailSenderName'));
					$this->email->to($email);

					$this->email->subject($message['subject']);
					$this->email->message($message['body']);

					if (! $this->email->send())
					{
						// Redirect to login page with message
						$this->session->set_flashdata('warning', __("Email settings are incorrect."));
						redirect('user/login');
					} else {
						// Redirect to login page with message
						$this->session->set_flashdata('notice', __("Password Reset Processed."));
						redirect('user/login');
					}
				} else {
					// No account found just return to login page
					$this->session->set_flashdata('notice', __("Password Reset Processed."));
					redirect('user/login');
				}
			}
		}
	}

	// Send an E-Mail to the user. Function is similar to forgot_password() but will be called by an AJAX
	public function admin_send_password_reset() {

		header('Content-Type: application/json');

		if ($this->input->is_ajax_request()) { // just additional, to make sure request is from ajax
			if ($this->input->post('submit_allowed')) {

				$this->load->model('user_model');

				if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

				$query = $this->user_model->get_by_id($this->input->post('user_id'));

				$this->load->library('form_validation');

				$this->form_validation->set_rules('id', 'user_id', 'required');

				$data = $query->row();

				if ($this->form_validation->run() != FALSE)
				{
					$this->session->set_flashdata('notice', 'Something went wrong! User has no user_id.');
					redirect('user');
				}
				else
				{
					// Check email address exists
					$this->load->model('user_model');

					$check_email = $this->user_model->check_email_address($data->user_email);

					if($check_email == TRUE) {
						// Generate password reset code 50 characters long
						$this->load->helper('string');
						$reset_code = random_string('alnum', 50);
						$this->user_model->set_password_reset_code(($data->user_email), $reset_code);

						// Send email with reset code and first Name of the User

						$this->data['reset_code'] = $reset_code;
						$this->data['user_firstname'] = $data->user_firstname; // We can call the user by his first name in the E-Mail
						$this->data['user_callsign'] = $data->user_callsign;
						$this->data['user_name'] = $data->user_name;
						$this->load->library('email');

						if($this->optionslib->get_option('emailProtocol') == "smtp") {
							$config = Array(
								'protocol' => $this->optionslib->get_option('emailProtocol'),
								'smtp_crypto' => $this->optionslib->get_option('smtpEncryption'),
								'smtp_host' => $this->optionslib->get_option('smtpHost'),
								'smtp_port' => $this->optionslib->get_option('smtpPort'),
								'smtp_user' => $this->optionslib->get_option('smtpUsername'),
								'smtp_pass' => $this->optionslib->get_option('smtpPassword'),
								'crlf' => "\r\n",
								'newline' => "\r\n"
							);

							$this->email->initialize($config);
						}

						$message = $this->email->load('email/admin_reset_password', $this->data,  $data->user_language);

						$this->email->from($this->optionslib->get_option('emailAddress'), $this->optionslib->get_option('emailSenderName'));
						$this->email->to($data->user_email);
						$this->email->subject($message['subject']);
						$this->email->message($message['body']);

						if (! $this->email->send())
						{
        					echo json_encode(false);
						} else {
        					echo json_encode(true);
						}
					} else {
        				echo json_encode(false);
					}
				}
			}
		}
	}

	function reset_password($reset_code = NULL) {
		$data['reset_code'] = $reset_code;
		if($reset_code != NULL) {
			$this->load->helper(array('form', 'url'));

			$this->load->library('form_validation');

			$this->form_validation->set_rules('password', 'Password', 'required');
			$this->form_validation->set_rules('password_confirm', 'Password Confirmation', 'required|matches[password]');

			if ($this->form_validation->run() == FALSE)
			{
				$data['page_title'] = __("Reset Password");
				$this->load->view('interface_assets/mini_header', $data);
				$this->load->view('user/reset_password');
				$this->load->view('interface_assets/footer');
			}
			else
			{
				// Lets reset the password!
				$this->load->model('user_model');

				$this->user_model->reset_password($this->input->post('password', true), $reset_code);
				$this->session->set_flashdata('notice', 'Password Reset.');
				redirect('user/login');
			}
		} else {
			redirect('user/login');
		}
	}

	function check_locator($grid) {
		$grid = $this->input->post('user_locator');
		// Allow empty locator
		if (preg_match('/^$/', $grid)) return true;
		// Allow 6-digit locator
		if (preg_match('/^[A-Ra-r]{2}[0-9]{2}[A-Za-z]{2}$/', $grid)) return true;
		// Allow 4-digit locator
		else if (preg_match('/^[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
		// Allow 4-digit grid line
		else if (preg_match('/^[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
		// Allow 4-digit grid corner
		else if (preg_match('/^[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
		// Allow 2-digit locator
		else if (preg_match('/^[A-Ra-r]{2}$/', $grid)) return true;
		// Allow 8-digit locator
		else if (preg_match('/^[A-Ra-r]{2}[0-9]{2}[A-Za-z]{2}[0-9]{2}$/', $grid)) return true;
		else {
			$this->form_validation->set_message('check_locator', 'Please check value for grid locator ('.strtoupper($grid).').');
			return false;
		}
	}

   	function https_check() {
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
			return true;
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
			return true;
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
			return true;
		}
		return false;
	}

	public function impersonate() {

		// Check if impersonating is disabled in the config
		if ($this->config->item('disable_impersonate')) {
			$this->session->set_flashdata('error', sprintf(__("You currently can't impersonate another user. You need to set %s to %s in your config.php!"), "'disable_impersonate'", "'false'"));
			redirect('dashboard');
		}

		// Load the encryption library
		if (!$this->load->is_loaded('encryption')) {
			$this->load->library('encryption');
		}

		// Load the user model
		$this->load->model('user_model');

		// Precheck: If the encryption key is still default, we can't impersonate another user for security reasons
		if ($this->config->item('encryption_key') == 'flossie1234555541') {
			$this->session->set_flashdata('error', __("You currently can't impersonate another user. Please change the encryption_key in your config.php file first!"));
			redirect('dashboard');
		}

		// Prepare the hash
		$raw_hash = $this->encryption->decrypt($this->input->post('hash', TRUE) ?? '');
		if (!$raw_hash) {
			$this->session->set_flashdata('error', __("Invalid Hash"));
			redirect('dashboard');
		}
		$hash_parts = explode('/', $raw_hash);
		$source_uid = $hash_parts[0];
		$target_uid = $hash_parts[1];
		$timestamp = $hash_parts[2];

		/**
		 * Security Checks
		 */
		// make sure the timestamp is not too old
		if (time() - $timestamp > 600) {  // 10 minutes
			$this->session->set_flashdata('error', __("The impersonation hash is too old. Please try again."));
			redirect('dashboard');
		}

		// is the source user still logged in? 
		// We fetch the source user from database to also make sure the user exists. We could use source_uid directly, but this is more secure
		if ($this->session->userdata('user_id') !=  $this->user_model->get_by_id($source_uid)->row()->user_id) {
			$this->session->set_flashdata('error', __("You can't impersonate another user while you're not logged in as the source user"));
			redirect('dashboard');
		}

		// in addition to the check if the user is logged in, we also can check if the session id matches the cookie
		if ($this->session->session_id != $this->input->cookie($this->config->item('sess_cookie_name'), TRUE)) {
			$this->session->set_flashdata('error', __("There was a problem with your session. Please try again."));
			redirect('dashboard');
		}

		// make sure the target user exists
		$target_user = $this->user_model->get_by_id($target_uid)->row();
		if (!$target_user) {
			$this->session->set_flashdata('error', __("The requested user to impersonate does not exist"));
			redirect('dashboard');
		}

		// before we can impersonate a user, we need to make sure the current user is allowed to do so
		$clubswitch = $this->input->post('clubswitch', TRUE) ?? '';
		$custom_sessiondata = [];
		$source_user = $this->user_model->get_by_id($source_uid)->row();
		if ($clubswitch == 1) {
			$this->load->model('club_model');
			if (!$this->club_model->club_authorize(3, $target_uid, $source_uid) || !$this->user_model->authorize(3)) {
				$this->session->set_flashdata('error', __("You're not allowed to do that!"));
				redirect('dashboard');
			} else {
				$targetclub = array_filter($this->session->userdata('available_clubstations'), function($club) use ($target_uid) {
					return $club->user_id == $target_uid;
				});
				$p_level = !empty($targetclub) ? reset($targetclub)->p_level : null;
				if ($p_level != null) {
					$custom_sessiondata['p_level'] = $p_level;
				} else {
					$this->session->set_flashdata('error', __("Could not determine the correct permission level for the clubstation. Try again after re-login."));
					redirect('dashboard');
				}
			}
		} else {
			if(!$source_user || !$this->user_model->authorize(99)) {
				$this->session->set_flashdata('error', __("You're not allowed to do that!"));
				redirect('dashboard'); 
			} else {
				$custom_sessiondata['p_level'] = 99;  // if the user is an admin he also should have full rights in the clubstations
			}
		}
		$custom_sessiondata['src_call'] = $source_user->user_callsign;
		$custom_sessiondata['src_user_type'] = $source_user->user_type;
		$custom_sessiondata['src_hash'] = $this->input->post('hash', TRUE) ?? '';

		/**
		 * Impersonate the user
		 */
		// Update the session with the new user_id
		// TODO: Find a solution for sessiondata 'radio', so a user would be able to use e.g. his own radio while impersonating another user
		// Due the fact that the user is now impersonating another user, he can't use his default radio anymore
		$this->session->set_userdata('source_uid', $source_uid);
		$this->user_model->update_session($target_uid, null, true, $custom_sessiondata); 
		
		// Redirect to the dashboard, the user should now be logged in as the other user
		redirect('dashboard');
	}

	public function stop_impersonate_modal() {
		// Load the user model
		$this->load->model('user_model');
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->view('user/modals/stop_impersonate_modal');
	}

	public function stop_impersonate() {
		// Load the user model
		$this->load->model('user_model');

		// there is no source_uid, there is probably something fishy going on. So we clear the session at this point
		$source_uid = $this->session->userdata('source_uid') ?? false;
		$post_chk = $this->input->post('stopImpersonate', TRUE) ?? false;
		if (!$source_uid || $post_chk != 1) {
			$this->logout(['error', __("Ups.. Something went wrong. Try to log back in.")]);
			exit;
		}

		// is the current user a clubstation we need to check if the source user was allowed to impersonate the clubstation
		$club = $this->user_model->get_by_id($this->session->userdata('user_id'))->row();
		$current_is_club = $club->clubstation == 1 ? true : false;
		$source_user = $this->user_model->get_by_id($source_uid)->row();

		if ($current_is_club) {
			$this->load->model('club_model');
			if (!$this->club_model->club_authorize(3, $this->session->userdata('user_id'), $source_uid)) {
				$this->logout(['error', __("Ups.. Something went wrong. Try to log back in.")]);
				exit;
			}
		} else {
			// if the current user is not a clubstation, we need to check if the source user was allowed to impersonate the current user (has to be an admin)
			if($source_user->user_type != 99) {
				$this->logout(['error', __("Ups.. Something went wrong. Try to log back in.")]);
				exit;
			}
		}

		// Validate the impersonate hash
		$this->load->library('encryption');
		$raw_hash = $this->encryption->decrypt($this->session->userdata('cd_src_hash') ?? false);
		if (!$raw_hash) {
			$this->logout(['error', __("Ups.. Something went wrong. Try to log back in.")]);
			exit;
		}
		$hash_parts = explode('/', $raw_hash);
		$src_in_hash = $hash_parts[0];
		$tgt_in_hash = $hash_parts[1];
		$timestamp = $hash_parts[2];
		if ($src_in_hash != $source_uid || $tgt_in_hash != $this->session->userdata('user_id')) {
			$this->logout(['error', __("Ups.. Something went wrong. Try to log back in.")]);
			exit;
		}

		// The timestamp can't be older then 2 hours
		if (time() - $timestamp > 7200) {
			$this->logout(['notice', __("The ability to return quickly has been disabled after the security hash expired. Please log in again.")]);
			exit;
		}

		// Create a keep login cookie which will be used to log back in as the source user
		$encrypted_string = $this->user_model->keep_cookie_hash($source_uid);
		$cookie = array(
			'name'   => 're_login',  // we use a different cookie name to avoid conflicts with the regular keep_login cookie
			'value'  => $encrypted_string,
			'expire' => 20,  // seconds should be enough
			'secure' => FALSE,
			'httponly' => TRUE
		);
		$this->input->set_cookie($cookie);

		// log out on the regular way
		$msg = ['notice', sprintf(__("You have been logged out of the clubstation %s. Welcome back, %s, to your personal account!"), $club->user_callsign, $source_user->user_callsign)];
		$this->logout($msg, false);
	}
}
