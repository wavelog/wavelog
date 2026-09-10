<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Options extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}


	// Default /options redirects to the appearance tab
	function index() {
		redirect('options/appearance');
	}

	// function used to display the /appearance url
	function appearance() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Appearance");
		$data['active_tab'] = 'appearance';

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
		$data['active_tab'] = 'appearance';

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('theme', 'theme', 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/appearance');
			$this->load->view('interface_assets/footer');
		} else {
			// Update theme choice within the options system
			$theme_update_status = $this->optionslib->update('theme', $this->input->post('theme', true));

			// If theme update is complete set a flashsession with a success note
			if($theme_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update logbook map within the options system
			$logbook_map_update_status = $this->optionslib->update('logbook_map', $this->input->post('logbookMap', true));

			// If logbook map update is complete set a flashsession with a success note
			if($logbook_map_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public maps within the options system
			$public_maps_update_status = $this->optionslib->update('public_maps', $this->input->post('publicMaps', true));

			// If the option was saved successfully set a flashsession with success note
			if($public_maps_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public github button within the options system
			$public_github_button_update_status = $this->optionslib->update('public_github_button', $this->input->post('publicGithubButton', true));

			// If the option was saved successfully set a flashsession with success note
			if($public_github_button_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Update public login button within the options system
			$public_login_button_update_status = $this->optionslib->update('public_login_button', $this->input->post('publicLoginButton', true));

			// If the option was saved successfully set a flashsession with success note
			if($public_login_button_update_status == TRUE) {
				$this->session->set_flashdata('success', __("Options saved"));
			}

			// Redirect back to /appearance
			redirect('/options/appearance');
		}
	}

	// function used to display the /dxcluster url
	function hon() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Hams Of Note");
		$data['active_tab'] = 'hon';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/hon');
		$this->load->view('interface_assets/footer');
	}

	// Handles saving the DXCluster options to the options system.
	function hon_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Hams Of Note");
		$data['active_tab'] = 'hon';

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('hon_url', 'URL for Hams Of Note ', 'valid_url');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/hon');
			$this->load->view('interface_assets/footer');
		} else {
			$hon_url_update = $this->optionslib->update('hon_url', $this->input->post('hon_url', false)); // no xss cleaning of urls
			if($hon_url_update == TRUE) {
				$this->session->set_flashdata('success', __("Hams-Of-Note URL changed to ").$this->input->post('hon_url',true));
			}
			redirect('/options/hon');
		}
	}

	function dxcluster() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("DXCluster");
		$data['active_tab'] = 'dxcluster';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/dxcluster');
		$this->load->view('interface_assets/footer');
	}

	// Handles saving the DXCluster options to the options system.
	function dxcluster_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("DXCluster");
		$data['active_tab'] = 'dxcluster';

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
			$dxcluster_decont_update = $this->optionslib->update('dxcluster_decont', $this->input->post('dxcluster_decont', true));
			if($dxcluster_decont_update == TRUE) {
				$this->session->set_flashdata('success', __("de continent changed to ").$this->input->post('dxcluster_decont'));
			}

			$dxcluster_maxage_update = $this->optionslib->update('dxcluster_maxage', $this->input->post('dxcluster_maxage', true));
			if($dxcluster_maxage_update == TRUE) {
				$this->session->set_flashdata('success', __("Maximum age of spots changed to ").$this->input->post('dxcluster_maxage'));
			}

			$dxcache_url_update = $this->optionslib->update('dxcache_url', $this->input->post('dxcache_url', false)); // no xss cleaning of urls
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
		$data['active_tab'] = 'radio';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/radios');
		$this->load->view('interface_assets/footer');
	}

	// Handles saving the radio options to the options system.
	function radio_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Radio Settings");
		$data['active_tab'] = 'radio';

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
			$radioTimeout_update = $this->optionslib->update('cat_timeout_interval', $this->input->post('radioTimeout', true));

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
		$data['active_tab'] = 'email';

		$footerData['scripts'] = [
			'assets/js/sections/options_email.js'
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/email');
		$this->load->view('interface_assets/footer', $footerData);
	}

	// Handles saving the email options to the options system. Answers the AJAX call
	// from assets/js/sections/options_email.js, the result is shown as a toast.
	function email_save() {

		$this->load->library('form_validation');

		$this->form_validation->set_rules('emailProtocol', 'Email Protocol', 'required');

		header('Content-Type: application/json');

		if ($this->form_validation->run() == FALSE)
		{
			echo json_encode([
				'success' => false,
				'message' => trim(strip_tags(validation_errors())) ?: __("Something went wrong with saving the settings. Try again.")
			]);
		}
		else
		{

			// Update emailProtocol choice within the options system
			$emailProtocolupdate = $this->optionslib->update('emailProtocol', $this->input->post('emailProtocol', true));

			// Update smtpEncryption choice within the options system
			$smtpEncryptionupdate = $this->optionslib->update('smtpEncryption', $this->input->post('smtpEncryption', true));

			// Update email sender name within the options system
			$emailSenderName_value = $this->input->post('emailSenderName');
			if (empty($emailSenderName_value)) {
				$emailSenderName_value = 'Wavelog';
			}
			$emailSenderNameupdate = $this->optionslib->update('emailSenderName', $emailSenderName_value);

			// Update email address choice within the options system
			$emailAddressupdate = $this->optionslib->update('emailAddress', $this->input->post('emailAddress', false));

			// Update smtpHost choice within the options system
			$smtpHostupdate = $this->optionslib->update('smtpHost', $this->input->post('smtpHost', false));

			// Update smtpPort choice within the options system
			$smtpPortupdate = $this->optionslib->update('smtpPort', $this->input->post('smtpPort', true));

			// Update smtpTimeout choice within the options system
			$smtpTimeout_value = (int) $this->input->post('smtpTimeout');
			if ($smtpTimeout_value < 5 || $smtpTimeout_value > 120) {
				$smtpTimeout_value = 30;
			}
			// Options_model::update() reports FALSE when it has to create the row instead
			// of updating it. Create it up front so the return value stays meaningful.
			$this->optionslib->update('smtpTimeout', $smtpTimeout_value);
			$smtpTimeoutupdate = $this->optionslib->update('smtpTimeout', $smtpTimeout_value);

			// Update smtpUsername choice within the options system
			$smtpUsernameupdate = $this->optionslib->update('smtpUsername', $this->input->post('smtpUsername', false));

			// Update smtpPassword choice within the options system - an empty field keeps the
			// stored password, the clear checkbox removes it
			$smtpPassword_value = (string) $this->input->post('smtpPassword', false);
			if ($this->input->post('smtpPasswordClear') !== null) {
				$smtpPasswordupdate = $this->optionslib->update('smtpPassword', '');
			} else if ($smtpPassword_value === '') {
				$smtpPasswordupdate = true;
			} else {
				$smtpPasswordupdate = $this->optionslib->update('smtpPassword', $smtpPassword_value);
			}

			// Check if all updates are successful
			$updateSuccessful = $emailProtocolupdate &&
				$smtpEncryptionupdate &&
				$emailSenderNameupdate &&
				$emailAddressupdate &&
				$smtpHostupdate &&
				$smtpPortupdate &&
				$smtpTimeoutupdate &&
				$smtpUsernameupdate &&
				$smtpPasswordupdate;

			if ($updateSuccessful) {
				echo json_encode([
					'success' => true,
					'message' => __("The settings were saved successfully.")
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => __("Something went wrong with saving the settings. Try again.")
				]);
			}
		}
	}

	// Sends a test mail to the address of the logged in user. Answers the AJAX call
	// from assets/js/sections/options_email.js, the result is shown as a toast.
	function sendTestMail() {
		$id = $this->session->userdata('user_id');

		$email = $this->user_model->get_user_email_by_id($id);

		header('Content-Type: application/json');

		if ($email == "") {
			echo json_encode([
				'success' => false,
				'message' => __("Testmail failed. Something went wrong."),
				'detail' => __("There is no email address set in your account settings.")
			]);
			return;
		}

		$this->load->helper('mailer');

		$result = mailer_send('email/testmail', $email);

		if ($result['success']) {
			echo json_encode([
				'success' => true,
				'message' => __("Testmail sent. Email settings seem to be correct.")
			]);
		} else {
			// The mailer debug output is handed over separately: it can be several lines
			// of SMTP dialogue, which is too much for a toast.
			echo json_encode([
				'success' => false,
				'message' => __("Testmail failed. Something went wrong."),
				'detail' => $result['error']
			]);
		}
	}

	// function used to display the /maptiles url in global options
	function maptiles() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Maptiles Server");
		$data['active_tab'] = 'maptiles';

		$this->load->library('MaptileCache');
		$data['maptile_server_url'] = $this->options_model->item('map_tile_server') ?? MaptileCache::DEFAULT_SERVER;
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
		$this->load->library('MaptileCache');

		$this->load->library('form_validation');

		$this->form_validation->set_rules('maptile_server_url', 'URL of Maptile Server', 'required');
		$this->form_validation->set_rules('subdomain_system', 'Subdomains for Loadbalancing', 'required');
		$this->form_validation->set_rules('copyright_url', 'URL for Copyright', 'required');
		$this->form_validation->set_rules('copyright_text', 'Text for Copyright', 'required');

		if ($this->form_validation->run() == FALSE) {

			$this->maptiles();

		} else {
			$saved = false;
			if ($this->input->post('reset_defaults') == '1') {
				$map_tile_server_copyright = 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>';
				$saved = $this->optionslib->update('map_tile_server', MaptileCache::DEFAULT_SERVER);
				$saved = $this->optionslib->update('map_tile_subdomains', 'abc');
			} else {
				$map_tile_server_copyright = 'Map data &copy; <a href="' . $this->input->post('copyright_url', true) . '">' . $this->input->post('copyright_text', true) . '</a>';
				$saved = $this->optionslib->update('map_tile_server', $this->input->post('maptile_server_url', false));
				$saved = $this->optionslib->update('map_tile_subdomains', $this->input->post('subdomain_system', false));
			}
			$saved = $this->optionslib->update('map_tile_server_copyright', $map_tile_server_copyright);
			MaptileCache::flush_config();

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
		$data['active_tab'] = 'version_dialog';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/version_dialog');
		$this->load->view('interface_assets/footer');
	}

	function version_dialog_save() {

		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Version Info Settings");

		$this->load->helper(array('form', 'url'));

		$version_dialog_header_update = $this->optionslib->update('version_dialog_header', $this->input->post('version_dialog_header', true));
		if($version_dialog_header_update == TRUE) {
			$this->session->set_flashdata('success0', __("Version Info Header changed to")." "."'".$this->input->post('version_dialog_header')."'");
		}
		$version_dialog_mode_update = $this->optionslib->update('version_dialog', $this->input->post('version_dialog_mode', true));
		if($version_dialog_mode_update == TRUE) {
			$this->session->set_flashdata('success1', __("Version Info Mode changed to")." "."'".$this->input->post('version_dialog_mode')."'");
		}
		if ($this->input->post('version_dialog_mode') == "both" || $this->input->post('version_dialog_mode') == "custom_text" ) {
			$version_dialog_custom_text_update = $this->optionslib->update('version_dialog_text', $this->input->post('version_dialog_custom_text', true));
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
