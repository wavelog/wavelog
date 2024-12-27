<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class QSO extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('cat');
		$this->load->model('stations');
		$this->load->model('logbook_model');
		$this->load->model('user_model');
		$this->load->model('modes');
		$this->load->model('bands');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

        // Getting the live/post mode from GET command
        // 0 = live
        // 1 = post (manual)
        $get_manual_mode = $this->input->get('manual', TRUE);
        if ($get_manual_mode == '0' || $get_manual_mode == '1') {
            $data['manual_mode'] = $get_manual_mode;
        } else {
            show_404();
        }

		$data['active_station_profile'] = $this->stations->find_active();

		$data['notice'] = false;
		$data['stations'] = $this->stations->all_of_user();
		$data['radios'] = $this->cat->radios();
		$data['radio_last_updated'] = $this->cat->last_updated()->row();
		$data['query'] = $this->logbook_model->last_custom('5');
		$data['dxcc'] = $this->logbook_model->fetchDxcc();
		$data['iota'] = $this->logbook_model->fetchIota();
		$data['modes'] = $this->modes->active();
		$data['bands'] = $this->bands->get_user_bands_for_qso_entry();
		$data['user_default_band'] = $this->session->userdata('user_default_band');
		$data['sat_active'] = array_search("SAT", $this->bands->get_user_bands(), true);

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'iota','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_iota_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_iota_to_qso_tab'] = 0;
		}

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'sota','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_sota_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_sota_to_qso_tab'] = 0;
		}

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'wwff','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_wwff_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_wwff_to_qso_tab'] = 0;
		}

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'pota','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_pota_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_pota_to_qso_tab'] = 0;
		}

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'sig','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_sig_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_sig_to_qso_tab'] = 0;
		}

		$qkey_opt=$this->user_options_model->get_options('qso_tab',array('option_name'=>'dok','option_key'=>'show'))->result();
		if (count($qkey_opt)>0) {
			$data['user_dok_to_qso_tab'] = $qkey_opt[0]->option_value;
		} else {
			$data['user_dok_to_qso_tab'] = 0;
		}

		$this->load->library('form_validation');

		$this->form_validation->set_rules('start_date', 'Date', 'required');
		$this->form_validation->set_rules('start_time', 'Time', 'required');
		$this->form_validation->set_rules('callsign', 'Callsign', 'required');
		$this->form_validation->set_rules('band', 'Band', 'required');
		$this->form_validation->set_rules('mode', 'Mode', 'required');
		$this->form_validation->set_rules('locator', 'Locator', 'callback_check_locator');

		// [eQSL default msg] GET user options (option_type='eqsl_default_qslmsg'; option_name='key_station_id'; option_key=station_id) //
		$options_object = $this->user_options_model->get_options('eqsl_default_qslmsg',array('option_name'=>'key_station_id','option_key'=>$data['active_station_profile']))->result();
		$data['qslmsg'] = (isset($options_object[0]->option_value))?$options_object[0]->option_value:'';

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Add QSO");
			if (validation_errors() != '') {	// we're coming from a failed ajax-call
				echo json_encode(array('message' => 'Error','errors' => validation_errors()));
			} else {	// we're not coming from a POST
				$this->load->view('interface_assets/header', $data);
				$this->load->view('qso/index');
				$this->load->view('interface_assets/footer');
			}
		} else {
			// Store Basic QSO Info for reuse
			// Put data in an array first, then call set_userdata once.
			// This solves the problem of CI dumping out the session
			// cookie each time set_userdata is called.
			// For more info, see http://bizhole.com/codeigniter-nginx-error-502-bad-gateway/
			// $qso_data = [
			// 18-Jan-2016 - make php v5.3 friendly!
			$qso_data = array(
				'start_date' => $this->input->post('start_date', TRUE),
				'start_time' => $this->input->post('start_time', TRUE),
				'end_time' => $this->input->post('end_time'),
				'time_stamp' => time(),
				'mail' => $this->input->post('mail', TRUE),
				'band' => $this->input->post('band', TRUE),
				'band_rx' => $this->input->post('band_rx', TRUE),
				'freq' => $this->input->post('freq_display', TRUE),
				'freq_rx' => $this->input->post('freq_display_rx', TRUE),
				'mode' => $this->input->post('mode', TRUE),
				'sat_name' => $this->input->post('sat_name', TRUE),
				'sat_mode' => $this->input->post('sat_mode', TRUE),
				'prop_mode' => $this->input->post('prop_mode', TRUE),
				'radio' => $this->input->post('radio', TRUE),
				'station_profile_id' => $this->input->post('station_profile', TRUE),
				'operator_callsign' => $this->input->post('operator_callsign', TRUE),
				'transmit_power' => $this->input->post('transmit_power', TRUE)
			);
			// ];

			$this->session->set_userdata($qso_data);

			// If SAT name is set make it session set to sat
			if($this->input->post('sat_name', TRUE)) {
				$this->session->set_userdata('prop_mode', 'SAT');
			}

			// Add QSO
			// $this->logbook_model->add();
			//change to create_qso function as add and create_qso duplicate functionality
			$this->logbook_model->create_qso();

			$returner=[];
			$actstation=$this->stations->find_active() ?? '';
			$returner['activeStationId'] = $actstation;
			$profile_info = $this->stations->profile($actstation)->row();
			$returner['activeStationTXPower'] = xss_clean($profile_info->station_power ?? '');
			$returner['activeStationOP'] = xss_clean($this->session->userdata('operator_callsign'));
			$returner['message']='success';

			// Get last 5 qsos
			echo json_encode($returner);
		}
	}

	/*
	 * This is used for contest-logging and the ajax-call
	 */
	public function saveqso() {
        $this->load->model('logbook_model');
        $this->logbook_model->create_qso();
    }

	function edit() {

		$this->load->model('logbook_model');
		$this->load->model('user_model');
		$this->load->model('modes');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$query = $this->logbook_model->qso_info($this->uri->segment(3));

		$this->load->library('form_validation');

		$this->form_validation->set_rules('time_on', 'Start Date', 'required');
		$this->form_validation->set_rules('time_off', 'End Date', 'required');
		$this->form_validation->set_rules('callsign', 'Callsign', 'required');

        $data['qso'] = $query->row();
        $data['dxcc'] = $this->logbook_model->fetchDxcc();
        $data['iota'] = $this->logbook_model->fetchIota();
		$data['modes'] = $this->modes->all();

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('qso/edit', $data);
		}
		else
		{
			$this->logbook_model->edit();
			$this->session->set_flashdata('notice', 'Record Updated');
			$this->load->view('qso/edit_done');
		}
	}

    function winkeysettings() {

        // Load model Winkey
        $this->load->model('winkey');

        // call settings from model winkey
        $data['result'] = $this->winkey->settings($this->session->userdata('user_id'), $this->stations->find_active());

		$this->load->view('qso/components/winkeysettings', $data);
    }

    function cwmacrosave(){
        // Get the data from the form
        $function1_name = $this->input->post('function1_name', TRUE);
        $function1_macro = $this->input->post('function1_macro', TRUE);

        $function2_name = $this->input->post('function2_name', TRUE);
        $function2_macro = $this->input->post('function2_macro', TRUE);

        $function3_name = $this->input->post('function3_name', TRUE);
        $function3_macro = $this->input->post('function3_macro', TRUE);

        $function4_name = $this->input->post('function4_name', TRUE);
        $function4_macro = $this->input->post('function4_macro', TRUE);

        $function5_name = $this->input->post('function5_name', TRUE);
        $function5_macro = $this->input->post('function5_macro', TRUE);

        $data = [
            'user_id' => $this->session->userdata('user_id'),
            'station_location_id' => $this->stations->find_active(),
			'function1_name'  => $function1_name,
            'function1_macro' => $function1_macro,
            'function2_name'  => $function2_name,
            'function2_macro' => $function2_macro,
            'function3_name'  => $function3_name,
            'function3_macro' => $function3_macro,
            'function4_name'  => $function4_name,
            'function4_macro' => $function4_macro,
            'function5_name'  => $function5_name,
            'function5_macro' => $function5_macro,
		];

        // Load model Winkey
        $this->load->model('winkey');

        // save the data
        $this->winkey->save($data);

        echo "Macros Saved, Press Close and lets get sending!";
    }

    function cwmacros_json() {
        // Load model Winkey
        $this->load->model('winkey');

        header('Content-Type: application/json; charset=utf-8');

        // Call settings_json from model winkey
        echo $this->winkey->settings_json($this->session->userdata('user_id'), $this->stations->find_active());
    }

    function edit_ajax() {

        $this->load->model('logbook_model');
        $this->load->model('user_model');
        $this->load->model('modes');
        $this->load->model('bands');
        $this->load->model('contesting_model');

        $this->load->library('form_validation');

        if(!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard');
        }

        $id = str_replace('"', "", $this->input->post("id", TRUE));
        $query = $this->logbook_model->qso_info($id);

        $data['qso'] = $query->row();
        $data['dxcc'] = $this->logbook_model->fetchDxcc();
        $data['iota'] = $this->logbook_model->fetchIota();
        $data['modes'] = $this->modes->all();
        $data['bands'] = $this->bands->get_user_bands_for_qso_entry(true);
        $data['contest'] = $this->contesting_model->getActivecontests();

        $this->load->view('qso/edit_ajax', $data);
    }

    function qso_save_ajax() {
        $this->load->model('logbook_model');
        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard');
        }

        $this->logbook_model->edit();
    }

	function qsl_rcvd($id, $method) {
		$this->load->model('logbook_model');
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

			// Update Logbook to Mark Paper Card Received

			$this->logbook_model->paperqsl_update($id, $method);

			$this->session->set_flashdata('notice', 'QSL Card: Marked as Received');

			redirect('logbook');
	}

    function qsl_rcvd_ajax() {
        $id = str_replace('"', "", $this->input->post("id", TRUE));
        $method = str_replace('"', "", $this->input->post("method", TRUE));

        $this->load->model('logbook_model');
        $this->load->model('user_model');

        header('Content-Type: application/json');

        if(!$this->user_model->authorize(2)) {
            echo json_encode(array('message' => 'Error'));

        }
        else {
            // Update Logbook to Mark Paper Card Received
            $this->logbook_model->paperqsl_update($id, $method);

            echo json_encode(array('message' => 'OK'));
        }
    }

    function qsl_sent_ajax() {
        $id = str_replace('"', "", $this->input->post("id", TRUE));
        $method = str_replace('"', "", $this->input->post("method", TRUE));

        $this->load->model('logbook_model');
        $this->load->model('user_model');

        header('Content-Type: application/json');

        if(!$this->user_model->authorize(2)) {
            echo json_encode(array('message' => 'Error'));

        }
        else {
            // Update Logbook to Mark Paper Card Sent
            $this->logbook_model->paperqsl_update_sent($id, $method);

            echo json_encode(array('message' => 'OK'));
        }
    }

    function qsl_requested_ajax() {
        $id = str_replace('"', "", $this->input->post("id", TRUE));
        $method = str_replace('"', "", $this->input->post("method", TRUE));

        $this->load->model('logbook_model');
        $this->load->model('user_model');

        header('Content-Type: application/json');

        if(!$this->user_model->authorize(2)) {
            echo json_encode(array('message' => 'Error'));

        }
        else {
            // Update Logbook to Mark Paper Card Received
            $this->logbook_model->paperqsl_requested($id, $method);

            echo json_encode(array('message' => 'OK'));
        }
    }

	function qsl_ignore_ajax() {
        $id = str_replace('"', "", $this->input->post("id", TRUE));
        $method = str_replace('"', "", $this->input->post("method", TRUE));

        $this->load->model('logbook_model');
        $this->load->model('user_model');

        header('Content-Type: application/json');

        if(!$this->user_model->authorize(2)) {
            echo json_encode(array('message' => 'Error'));

        }
        else {
            // Update Logbook to Mark Paper Card Received
            $this->logbook_model->paperqsl_ignore($id, $method);

            echo json_encode(array('message' => 'OK'));
        }
    }

	/* Delete QSO */
	function delete($id) {
		$this->load->model('logbook_model');

		if ($this->logbook_model->check_qso_is_accessible($id)) {
			$this->logbook_model->delete($id);
			$this->session->set_flashdata('notice', 'QSO Deleted Successfully');
			$data['message_title'] = "Deleted";
			$data['message_contents'] = "QSO Deleted Successfully";
			$this->load->view('messages/message', $data);
		}

		// If deletes from /logbook dropdown redirect
		if (strpos($_SERVER['HTTP_REFERER'], '/logbook') !== false) {
		    redirect($_SERVER['HTTP_REFERER']);
		}
	}

    /* Delete QSO */
    function delete_ajax() {
        $id = str_replace('"', "", $this->input->post("id", TRUE));

        $this->load->model('logbook_model');
	if ($this->logbook_model->check_qso_is_accessible($id)) {
        	$this->logbook_model->delete($id);
        	header('Content-Type: application/json');
        	echo json_encode(array('message' => 'OK'));
	} else {
        	header('Content-Type: application/json');
        	echo json_encode(array('message' => 'not allowed'));
	}
        return;
    }


	function band_to_freq($band, $mode) {

		if ($band != null and $band != 'null') {
			echo $this->frequency->convert_band($band, $mode);
		}

	}

	/*
	 * Function is used for autocompletion of SOTA in the QSO entry form
	 */
	public function get_sota() {
		$this->load->library('sota');
		$json = [];

        $query = $this->input->get('query', TRUE) ?? FALSE;
        $json = $this->sota->get($query);

		header('Content-Type: application/json');
		echo json_encode($json);
	}

	public function get_wwff() {
        $json = [];

        $query = $this->input->get('query', TRUE) ?? FALSE;
        $wwff = strtoupper($query);

        $file = 'updates/wwff.txt';

        if (is_readable($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $input = preg_quote($wwff, '~');
            $reg = '~^'. $input .'(.*)$~';
            $result = preg_grep($reg, $lines);
            $json = [];
            $i = 0;
            foreach ($result as &$value) {
                // Limit to 100 as to not slowdown browser too much
                if (count($json) <= 100) {
                    $json[] = ["name"=>$value];
                }
            }
        } else {
            $src = 'assets/resources/wwff.txt';
            if (copy($src, $file)) {
                $this->get_wwff();
            } else {
                log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
    }

	public function get_pota() {
        $json = [];

        $query = $this->input->get('query', TRUE) ?? FALSE;
        $pota = strtoupper($query);

        $file = 'updates/pota.txt';

        if (is_readable($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $input = preg_quote($pota, '~');
            $reg = '~^'. $input .'(.*)$~';
            $result = preg_grep($reg, $lines);
            $json = [];
            $i = 0;
            foreach ($result as &$value) {
                // Limit to 100 as to not slowdown browser too much
                if (count($json) <= 100) {
                    $json[] = ["name"=>$value];
                }
            }
        } else {
            $src = 'assets/resources/pota.txt';
            if (copy($src, $file)) {
                $this->get_pota();
            } else {
                log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
    }

    /*
	 * Function is used for autocompletion of DOK in the QSO entry form
	 */
    public function get_dok() {
        $json = [];

        $query = $this->input->get('query', TRUE) ?? FALSE;
        $dok = strtoupper($query);

        $file = 'updates/dok.txt';

        if (is_readable($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $input = preg_quote($dok, '~');
            $reg = '~^'. $input .'(.*)$~';
            $result = preg_grep($reg, $lines);
            $json = [];
            $i = 0;
            foreach ($result as &$value) {
                // Limit to 100 as to not slowdown browser too much
                if (count($json) <= 100) {
                    $json[] = ["name"=>$value];
                }
            }
        } else {
            $src = 'assets/resources/dok.txt';
            if (copy($src, $file)) {
                $this->get_dok();
            } else {
                log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
    }

   public function get_sota_info() {
      $this->load->library('sota');

      $sota = $this->input->post('sota', TRUE);

      header('Content-Type: application/json');
      echo $this->sota->info($sota);
   }

   public function get_wwff_info() {
      $this->load->library('wwff');

      $wwff = $this->input->post('wwff', TRUE);

      header('Content-Type: application/json');
      echo $this->wwff->info($wwff);
   }

   public function get_pota_info() {
      $this->load->library('pota');

      $pota = $this->input->post('pota', TRUE);

      header('Content-Type: application/json');
      echo $this->pota->info($pota);
   }

   public function get_station_power() {
      $this->load->model('stations');
      $stationProfile = $this->input->post('stationProfile', TRUE);
      $data = array('station_power' => $this->stations->get_station_power($stationProfile));

      header('Content-Type: application/json');
      echo json_encode($data);
   }

   // Return Previous QSOs Made in the active logbook
   public function component_past_contacts() {
      $this->load->library('Qra');
      if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
      $this->load->model('logbook_model');
      session_write_close();

      $data['query'] = $this->logbook_model->last_custom('5');

      // Load view
      $this->load->view('qso/components/previous_contacts', $data);
   }

   public function get_eqsl_default_qslmsg() {	// Get ONLY Default eQSL-Message with this function. This is ONLY for QSO relevant!
	   $return_json = array();
	   $option_key = $this->input->post('option_key', TRUE);
	   if ($option_key > 0) {
		   $options_object = $this->user_options_model->get_options('eqsl_default_qslmsg', array('option_name' => 'key_station_id', 'option_key' => $option_key))->result();
		   $return_json['eqsl_default_qslmsg'] = (isset($options_object[0]->option_value)) ? $options_object[0]->option_value : '';
	   }
	   header('Content-Type: application/json');
	   echo json_encode($return_json);
   }

	public function unsupported_lotw_prop_modes() {
		echo json_encode($this->config->item('lotw_unsupported_prop_modes'));
	}

   function check_locator($grid) {
      $grid = $this->input->post('locator', TRUE);
      // Allow empty locator
      if (preg_match('/^$/', $grid)) return true;
      // Allow 6-digit locator
      if (preg_match('/^[A-Ra-r]{2}[0-9]{2}[A-Xa-x]{2}$/', $grid)) return true;
      // Allow 4-digit locator
      else if (preg_match('/^[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
      // Allow 4-digit grid line
      else if (preg_match('/^[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
      // Allow 4-digit grid corner
      else if (preg_match('/^[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2},[A-Ra-r]{2}[0-9]{2}$/', $grid)) return true;
      // Allow 2-digit locator
      else if (preg_match('/^[A-Ra-r]{2}$/', $grid)) return true;
      // Allow 8-digit locator
      else if (preg_match('/^[A-Ra-r]{2}[0-9]{2}[A-Xa-x]{2}[0-9]{2}$/', $grid)) return true;
      // Allow 10-digit locator
      else if (preg_match('/^[A-Ra-r]{2}[0-9]{2}[A-Xa-x]{2}[0-9]{2}[A-Xa-x]{2}$/', $grid)) return true;
      else {
         $this->form_validation->set_message('check_locator', 'Please check value for grid locator ('.strtoupper($grid).').');
         return false;
      }
   }

   /**
	 * Open the API url which causes the browser to open the QSO live logging and populate the callsign with the data from the API
	 * 
	 * Usage example:
	 * 			https://<URL to Wavelog>/index.php/qso/log_qso?callsign=4W7EST
	 */

	function log_qso() {
		// Check if users logged in
		$this->load->model('user_model');
		if ($this->user_model->validate_session() == 0) {
			// user is not logged in
			$this->session->set_flashdata('warning', __("You have to be logged in to access this URL."));
			redirect('user/login');
		}

		// get the data from the API
		$data['callsign'] = $this->input->get('callsign', TRUE);
        $data['page_title'] = __("Call Transfer");

		// load the QSO redirect page
		if ($data['callsign'] != "") {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('qso/log_qso');
		} else {
			$this->session->set_flashdata('warning', __("No callsign provided."));
			redirect('dashboard');
		}
	}

    /**
     * Easy modal Loader 
     * Used for Share Modal in QSO Details view
     */
    function getShareModal() {

        $data['qso'] = $this->input->post('qso_data', TRUE);

        if (empty($data['qso'])) {
            echo "No QSO data provided.";
            return;
        }

        $this->load->view('qso/components/share_modal', $data, false);
    }
}
