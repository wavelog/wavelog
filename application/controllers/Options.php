<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Options extends CI_Controller {

	function __construct() {
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

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/appearance');
			$this->load->view('interface_assets/footer');
		} else {
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

	// function used to display the /callbook url
	function callbook() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Callbook");

		$data['callbook_provider'] = $this->optionslib->get_option('callbook_provider') == '' ? 'disabled' : $this->optionslib->get_option('callbook_provider');
		$data['callbook_username'] = $this->optionslib->get_option('callbook_username') ?? '';
		$data['callbook_password'] = $this->optionslib->get_option('callbook_password') ?? '';

		$this->load->view('interface_assets/header', $data);
		$this->load->view('options/callbook');
		$this->load->view('interface_assets/footer');
	}

	// Handles saving the callbook options to the options system.
	function callbook_save() {
		$data['page_title'] = __("Wavelog Options");
		$data['sub_heading'] = __("Callbook");

		$this->load->helper(array('form', 'url'));

		$this->load->library('form_validation');

		$fvalidate = TRUE;
		if ($this->input->post('callbook', TRUE) != "disabled") {
			$this->form_validation->set_rules('callbook_username', 'Username', 'required');
			$this->form_validation->set_rules('callbook_password', 'Password', 'required');
			$fvalidate = $this->form_validation->run();
		}

		if ($fvalidate == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/callbook');
			$this->load->view('interface_assets/footer');
		} else {
			$success = $this->optionslib->update('callbook_provider', $this->input->post('callbook_provider', true), 'yes');
			$success = $this->optionslib->update('callbook_username', $this->input->post('callbook_username', true), 'yes');
			$success = $this->optionslib->update('callbook_password', $this->input->post('callbook_password', true), 'yes');
			if($success == TRUE) {
				$this->session->set_flashdata('success', __("Callbook settings saved"));
			} else {
				$this->session->set_flashdata('danger', __("Callbook settings not saved. Something went wrong."));
			}
			redirect('/options/callbook');
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

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/radios');
			$this->load->view('interface_assets/footer');
		} else {
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

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('options/email');
			$this->load->view('interface_assets/footer');
		} else {
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
