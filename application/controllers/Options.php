<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Options extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }


	}




	// Default /options view just gives some text to explain the options area
    function index() {


        //echo $this->config->item('option_theme');

		//echo $this->optionslib->get_option('theme');

		$data['page_title'] = __("Wavelog Options");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/index');
		$this->load->view('interface_assets/footer');
	}

	// function used to display the /appearance url
	function appearance() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Appearance");

		$this->load->model('Themes_model');

		$data['themes'] = $this->Themes_model->getThemes();

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/appearance');
		$this->load->view('interface_assets/footer');
    }

	// Handles saving the appreance options to the options system.
	function appearance_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Appearance");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('theme', 'theme', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/appearance');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			// Update theme choice within the options system
			$theme_update_status = $this->optionslib->update('theme', $this->input->post('theme'), 'yes');

			// If theme update is complete set a flashsession with a success note
			if($theme_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update theme choice within the options system
			$search_update_status = $this->optionslib->update('global_search', $this->input->post('globalSearch'));

			// If theme update is complete set a flashsession with a success note
			if($search_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update dashboard banner within the options system
			$dasboard_banner_update_status = $this->optionslib->update('dashboard_banner', $this->input->post('dashboardBanner'), 'yes');

			// If dashboard banner update is complete set a flashsession with a success note
			if($dasboard_banner_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update dashboard map within the options system
			$dashboard_map_update_status = $this->optionslib->update('dashboard_map', $this->input->post('dashboardMap'), 'yes');

			// If dashboard map update is complete set a flashsession with a success note
			if($dashboard_map_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update logbook map within the options system
			$logbook_map_update_status = $this->optionslib->update('logbook_map', $this->input->post('logbookMap'), 'yes');

			// If logbook map update is complete set a flashsession with a success note
			if($logbook_map_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public maps within the options system
			$public_maps_update_status = $this->optionslib->update('public_maps', $this->input->post('publicMaps'), 'yes');

			// If the option was saved successfully set a flashsession with success note
			if($public_maps_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public github button within the options system
			$public_github_button_update_status = $this->optionslib->update('public_github_button', $this->input->post('publicGithubButton'), 'yes');

			// If the option was saved successfully set a flashsession with success note
			if($public_github_button_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public login button within the options system
			$public_login_button_update_status = $this->optionslib->update('public_login_button', $this->input->post('publicLoginButton'), 'yes');

			// If the option was saved successfully set a flashsession with success note
			if($public_login_button_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Redirect back to /appearance
			redirect('/options/appearance');
		}
    }

	// function used to display the /dxcluster url
	function dxcluster() {
			$data['page_title'] = __("Wavelog Options");
			$data['sub_heading'] = __("DXCluster");

			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/dxcluster');
			$this->load->view('interface_assets/footer');
	}

	// Handles saving the DXCluster options to the options system.
	function dxcluster_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("DXCluster");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('dxcache_url', 'URL of DXCache', 'valid_url');
		$this->form_validation->set_rules('dxcluster_maxage', 'Max Age of Spots', 'required');
		$this->form_validation->set_rules('dxcluster_decont', 'de continent', 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/dxcluster');
			$this->load->view('interface_assets/footer');
		} else {
			$dxcluster_decont_update = $this->optionslib->update('dxcluster_decont', $this->input->post('dxcluster_decont'), 'yes');
			if($dxcluster_decont_update == TRUE) {
				$this->session->set_flashdata('success', __("de continent changed to ").$this->input->post('dxcluster_decont'));
			}

			$dxcluster_maxage_update = $this->optionslib->update('dxcluster_maxage', $this->input->post('dxcluster_maxage'), 'yes');
			if($dxcluster_maxage_update == TRUE) {
				$this->session->set_flashdata('success', __("Maximum age of spots changed to ").$this->input->post('dxcluster_maxage'));
			}

			$dxcache_url_update = $this->optionslib->update('dxcache_url', $this->input->post('dxcache_url'), 'yes');
			if($dxcache_url_update == TRUE) {
				$this->session->set_flashdata('success', __("DXCluster Cache URL changed to ").$this->input->post('dxcache_url'));
			}
			redirect('/options/dxcluster');
		}
	}

		// function used to display the /radio url
		function radio() {

			$data['page_title'] = __("Wavelog Options");
			$data['sub_heading'] = __("Radio Settings");

			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/radios');
			$this->load->view('interface_assets/footer');
		}

	// Handles saving the radio options to the options system.
	function radio_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Radio Settings");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('radioTimeout', 'radioTimeout', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/radios');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			// Update theme choice within the options system
			$radioTimeout_update = $this->optionslib->update('cat_timeout_interval', $this->input->post('radioTimeout'), 'yes');

			// If theme update is complete set a flashsession with a success note
			if($radioTimeout_update == TRUE) {
				$this->session->set_flashdata('success', __("Radio Timeout Warning changed to ").$this->input->post('radioTimeout').' seconds');
			}

			// Redirect back to /appearance
			redirect('/options/radio');
		}
    }

	// function used to display the /appearance url
	function email() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Email");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/email');
		$this->load->view('interface_assets/footer');
    }

	// Handles saving the radio options to the options system.
	function email_save() {

			$data['page_title'] = __("Wavelog Options");
			$data['sub_heading'] = __("Email");
	
			$this->load->helper(array('form', 'url'));
	
			$this->load->library('form_validation');
	
			$this->form_validation->set_rules('emailProtocol', 'Email Protocol', 'required');
	
			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('interface_assets/header', $data);
				$this->load->view('options/email');
				$this->load->view('interface_assets/footer');
			}
			else
			{

				// Update emailProtocol choice within the options system
				$emailProtocolupdate = $this->optionslib->update('emailProtocol', $this->input->post('emailProtocol'), 'yes');

				// Update smtpEncryption choice within the options system
				$smtpEncryptionupdate = $this->optionslib->update('smtpEncryption', $this->input->post('smtpEncryption'), 'yes');

				// Update email sender name within the options system
				$emailSenderName_value = $this->input->post('emailSenderName');
				if (empty($emailSenderName_value)) {
					$emailSenderName_value = 'Wavelog';
				}
				$emailSenderNameupdate = $this->optionslib->update('emailSenderName', $emailSenderName_value, 'yes');

				// Update email address choice within the options system
				$emailAddressupdate = $this->optionslib->update('emailAddress', $this->input->post('emailAddress'), 'yes');

				// Update smtpHost choice within the options system
				$smtpHostupdate = $this->optionslib->update('smtpHost', $this->input->post('smtpHost'), 'yes');

				// Update smtpPort choice within the options system
				$smtpPortupdate = $this->optionslib->update('smtpPort', $this->input->post('smtpPort'), 'yes');
	
				// Update smtpUsername choice within the options system
				$smtpUsernameupdate = $this->optionslib->update('smtpUsername', $this->input->post('smtpUsername'), 'yes');

				// Update smtpPassword choice within the options system
				$smtpPasswordupdate = $this->optionslib->update('smtpPassword', $this->input->post('smtpPassword'), 'yes');
	
				// Check if all updates are successful
				$updateSuccessful = $emailProtocolupdate &&
									$smtpEncryptionupdate &&
									$emailSenderNameupdate &&
									$emailAddressupdate &&
									$smtpHostupdate &&
									$smtpPortupdate &&
									$smtpUsernameupdate &&
									$smtpPasswordupdate;

				// Set flash session based on update success
				if ($updateSuccessful) {
					$this->session->set_flashdata('success', __("The settings were saved successfully."));
				} else {
					$this->session->set_flashdata('saveFailed', __("Something went wrong with saving the settings. Try again."));
				}
	
				// Redirect back to /email
				redirect('/options/email');
			}
		}

		function oqrs() {

			$data['page_title'] = __("Wavelog Options");
			$data['sub_heading'] = __("OQRS Options");

			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/oqrs');
			$this->load->view('interface_assets/footer');
		}

		function oqrs_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("OQRS Options");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$global_oqrs_text = $this->optionslib->update('global_oqrs_text', $this->input->post('global_oqrs_text'), null);

		$global_oqrs_text = $this->optionslib->update('groupedSearch', $this->input->post('groupedSearch'), null);

		$global_oqrs_text = $this->optionslib->update('groupedSearchShowStationName', $this->input->post('groupedSearchShowStationName'), null);

		if($global_oqrs_text == TRUE) {
			$this->session->set_flashdata('success', __("OQRS options have been saved."));
		}

		redirect('/options/oqrs');
    }

	function sendTestMail() {
		$this->load->model('user_model');

		$id = $this->session->userdata('user_id');

		$email = $this->user_model->get_user_email_by_id($id);

		if($email != "") {

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

			$message = $this->email->load('email/testmail', NULL);

			$this->email->from($this->optionslib->get_option('emailAddress'), $this->optionslib->get_option('emailSenderName'));
			$this->email->to($email);
			$this->email->subject($message['subject']);
			$this->email->message($message['body']);

			if (! $this->email->send()){
				$this->session->set_flashdata('testmailFailed', __("Testmail failed. Something went wrong."));
			} else {
				$this->session->set_flashdata('testmailSuccess', __("Testmail sent. Email settings seem to be correct."));
			}
		} else {
			$this->session->set_flashdata('testmailFailed', __("Testmail failed. Something went wrong."));
		}
		
		redirect('/options/email');
	}

	// function used to display the /maptiles url in global options
	function maptiles() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Maptiles Server");

		$data['maptile_server_url'] = $this->optionslib->get_option('map_tile_server') ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		$data['maptile_server_url_dark'] = $this->optionslib->get_option('map_tile_server_dark') ?? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
		$data['subdomain_system'] = $this->optionslib->get_option('map_tile_subdomains') ?? 'abc';
		$map_tile_server_copyright = $this->optionslib->get_option('map_tile_server_copyright') ?? 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>';
		preg_match('/<a href="([^"]+)">([^<]+)<\/a>/', $map_tile_server_copyright, $matches);
		$data['copyright_url'] = $matches[1] ?? 'https://www.openstreetmap.org/';
		$data['copyright_text'] = $matches[2] ?? 'OpenStreetMap';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/maptiles');
		$this->load->view('interface_assets/footer');
	}

	// Handles saving the Maptiles options to the options system.
	function maptiles_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Maptiles Server");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('maptile_server_url', 'URL of Maptile Server', 'required');
		$this->form_validation->set_rules('maptile_server_url_dark', 'URL of Dark Maptile Server', 'required');
		$this->form_validation->set_rules('subdomain_system', 'Subdomains for Loadbalancing', 'required');
		$this->form_validation->set_rules('copyright_url', 'URL for Copyright', 'required');
		$this->form_validation->set_rules('copyright_text', 'Text for Copyright', 'required');

		if ($this->form_validation->run() == FALSE) {

			$this->maptiles();
			
		} else {
			$saved = false;
			if ($this->input->post('reset_defaults') == '1') {
				$map_tile_server_copyright = 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>';
				$saved = $this->optionslib->update('map_tile_server', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'yes');
				$saved = $this->optionslib->update('map_tile_server_dark', 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', 'yes');
				$saved = $this->optionslib->update('map_tile_subdomains', 'abc', 'yes');
			} else {
				$map_tile_server_copyright = 'Map data &copy; <a href="' . $this->input->post('copyright_url', true) . '">' . $this->input->post('copyright_text', true) . '</a>';
				$saved = $this->optionslib->update('map_tile_server', $this->input->post('maptile_server_url', true), 'yes');
				$saved = $this->optionslib->update('map_tile_server_dark', $this->input->post('maptile_server_url_dark', true), 'yes');
				$saved = $this->optionslib->update('map_tile_subdomains', $this->input->post('subdomain_system', true), 'yes');
			}
			$saved = $this->optionslib->update('map_tile_server_copyright', $map_tile_server_copyright, 'yes');

			// Also clean up static map images
			if (!$this->load->is_loaded('staticmap_model')) {
				$this->load->model('staticmap_model');
			}
			if (!$this->load->is_loaded('stations')) {
				$this->load->model('stations');
			}
			$station_ids = explode(',',$this->stations->all_station_ids_of_user());
			foreach ($station_ids as $station_id) {
				$this->staticmap_model->remove_static_map_image($station_id);
				log_message('debug', 'Removed static map image for station ID ' . $station_id);
			}
			// also remove the tilecache
			$cachepath = $this->config->item('cache_path') == '' ? APPPATH . 'cache/' : $this->config->item('cache_path');
        	$cacheDir = $cachepath . "tilecache/";
			$tilecache_warning = false;
			if (function_usable('exec')) {
				try {
					if (is_dir($cacheDir)) {
						exec('rm -rf ' . $cacheDir);
					}
				} catch (\Throwable $th) {
					$tilecache_warning = true;
				}
			} else {
				$tilecache_warning = true;
			}
			if ($tilecache_warning) {
				$this->session->set_flashdata('warning', sprintf(__("Maptile cache could not be removed. Delete the folder manually. Path: %s"), str_replace(FCPATH, '', $cacheDir)));
				log_message('debug', 'Maptile cache could not be removed. Delete the folder manually. Path: ' . str_replace(FCPATH, '', $cacheDir));
			}
			if($saved == true) {
				$this->session->set_flashdata('success', __("Maptile Options saved!"));
			} else {
				$this->session->set_flashdata('error', __("Maptile Options could not be saved!"));
				log_message('error', 'Maptile Options could not be saved!');
			}
			redirect('/options/maptiles');
		}
	}

	// function used to display the /version_dialog url
	function version_dialog() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Version Info Settings");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/version_dialog');
		$this->load->view('interface_assets/footer');
    }

	function version_dialog_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Version Info Settings");

		$this->load->helper(array('form', 'url'));

		$version_dialog_header_update = $this->optionslib->update('version_dialog_header', $this->input->post('version_dialog_header'), 'yes');
		if($version_dialog_header_update == TRUE) {
			$this->session->set_flashdata('success0', __("Version Info Header changed to")." "."'".$this->input->post('version_dialog_header')."'");
		}
		$version_dialog_mode_update = $this->optionslib->update('version_dialog', $this->input->post('version_dialog_mode'), 'yes');
		if($version_dialog_mode_update == TRUE) {
			$this->session->set_flashdata('success1', __("Version Info Mode changed to")." "."'".$this->input->post('version_dialog_mode')."'");
		}
		if ($this->input->post('version_dialog_mode') == "both" || $this->input->post('version_dialog_mode') == "custom_text" ) { 
			$version_dialog_custom_text_update = $this->optionslib->update('version_dialog_text', $this->input->post('version_dialog_custom_text'), 'yes');
			if($version_dialog_custom_text_update == TRUE) {
				$this->session->set_flashdata('success2', __("Version Info Custom Text saved!"));
			}
		}

		redirect('/options/version_dialog');
		
	}

	function version_dialog_show_to_all() {
		$update_vd_confirmation_to_false = $this->user_options_model->set_option_at_all_users('version_dialog', 'confirmed', array('boolean' => 'false'));
		if($update_vd_confirmation_to_false == TRUE) {
			$this->session->set_flashdata('success_trigger', __("Version Info will be shown to all users again"));
		}
		redirect('/options/version_dialog');
	}

	function version_dialog_show_to_none() {
		$update_vd_confirmation_to_true = $this->user_options_model->set_option_at_all_users('version_dialog', 'confirmed', array('boolean' => 'true'));
		if($update_vd_confirmation_to_true == TRUE) {
			$this->session->set_flashdata('success_trigger', __("Version Info will not be shown to any user"));
		}
		redirect('/options/version_dialog');
	}

}
